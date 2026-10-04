<?php

namespace App\Http\Controllers;

use App\Models\ChannelMessage;
use App\Models\Conversation;
use App\Models\SocialIntegration;
use App\Models\User;
use App\Services\MetaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SocialChatController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('conversations')) {
            return view('social-chat.index', [
                'conversations' => collect(),
                'members' => collect(),
                'selectedConversation' => null,
            ]);
        }

        $query = Conversation::with(['assignee', 'messages'])->latest('last_message_at');

        if (! Auth::user()->isAdmin()) {
            $query->where('assigned_to', Auth::id());
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('assignment')) {
            if ($request->assignment === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assignment);
            }
        }

        $conversations = $query->get();
        $members = User::where('is_approved', true)->get();
        $selectedConversation = $conversations->first();

        return view('social-chat.index', compact('conversations', 'members', 'selectedConversation'));
    }

    public function show(Conversation $conversation)
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasTable('channel_messages')) {
            abort(404, 'Social chat tables have not been migrated yet.');
        }

        $user = Auth::user();

        if (! $user->isAdmin() && $conversation->assigned_to !== $user->id) {
            abort(403, 'This conversation is not assigned to you.');
        }

        $conversation->load(['assignee', 'messages.agent']);
        $members = User::where('is_approved', true)->get();

        return view('social-chat.show', compact('conversation', 'members'));
    }

    public function assign(Request $request, Conversation $conversation)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'in:open,assigned,closed'],
        ]);

        $conversation->update([
            'assigned_to' => $validated['assigned_to'] ?? $conversation->assigned_to,
            'status' => $validated['status'] ?? ($validated['assigned_to'] ? 'assigned' : 'open'),
        ]);

        return back()->with('success', 'Conversation updated successfully.');
    }

    public function sync(Request $request, MetaService $metaService)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $pageToken = $metaService->resolvePageAccessToken();

        if (! $pageToken) {
            return back()->with('error', 'Facebook page access token is missing. Please add it in settings or .env.');
        }

        $integration = SocialIntegration::query()
            ->whereNotNull('access_token')
            ->whereRaw('TRIM(COALESCE(access_token, "")) <> ""')
            ->orderByDesc('id')
            ->first();

        $pageId = $integration?->page_id_or_phone_id
            ?: config('services.facebook.page_id')
            ?: config('services.meta.facebook_page_id')
            ?: env('FACEBOOK_PAGE_ID');

        if (! $pageId) {
            return back()->with('error', 'Facebook page ID is missing. Please add it in settings or .env.');
        }

        $response = Http::acceptJson()->get('https://graph.facebook.com/v18.0/' . $pageId . '/conversations', [
            'fields' => 'messages{message,from,created_time,attachments{mime_type,file_url,type,payload}}',
            'access_token' => $pageToken,
        ]);

        if (! $response->successful()) {
            return back()->with('error', $response->json('error.message', 'Unable to sync Facebook conversations.'));
        }

        $conversations = $response->json('data', []);

        foreach ($conversations as $conversationData) {
            $messages = data_get($conversationData, 'messages.data', []);

            if (empty($messages)) {
                continue;
            }

            $firstMessage = $messages[0] ?? null;
            $senderId = data_get($firstMessage, 'from.id') ?? data_get($conversationData, 'participants.data.0.id');

            if (! $senderId) {
                continue;
            }

            $conversation = Conversation::firstOrCreate(
                [
                    'platform' => 'facebook',
                    'sender_id' => (string) $senderId,
                ],
                [
                    'sender_name' => data_get($firstMessage, 'from.name') ?? 'Facebook User',
                    'status' => 'open',
                    'last_message_at' => now(),
                ]
            );

            foreach ($messages as $messageData) {
                $messageText = data_get($messageData, 'message') ?? data_get($messageData, 'message.text');
                $attachmentUrl = null;
                $attachmentType = null;

                foreach (data_get($messageData, 'attachments.data', []) as $attachment) {
                    if (isset($attachment['payload']['url'])) {
                        $attachmentUrl = $attachment['payload']['url'];
                        $attachmentType = $attachment['type'] ?? 'attachment';
                        break;
                    }
                }

                if (! $messageText && ! $attachmentUrl) {
                    continue;
                }

                $conversation->update([
                    'last_message_at' => isset($messageData['created_time']) ? Carbon::parse($messageData['created_time']) : now(),
                ]);

                $existing = ChannelMessage::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('sender_type', 'customer')
                    ->where('message_text', $messageText)
                    ->where('attachment_url', $attachmentUrl)
                    ->exists();

                if (! $existing) {
                    ChannelMessage::create([
                        'conversation_id' => $conversation->id,
                        'sender_type' => 'customer',
                        'user_id' => null,
                        'message_text' => $messageText,
                        'attachment_url' => $attachmentUrl,
                        'attachment_type' => $attachmentType,
                    ]);
                }
            }
        }

        return back()->with('success', 'Facebook conversations synced successfully.');
    }

    public function reply(Request $request, Conversation $conversation, MetaService $metaService)
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $conversation->assigned_to !== $user->id) {
            abort(403, 'You cannot reply to this conversation.');
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        $messageText = trim((string) ($validated['message'] ?? ''));
        $attachmentUrl = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('social-chat', 'public');
            $attachmentUrl = Storage::disk('public')->url($path);
            $attachmentType = $this->detectAttachmentType($file->getClientOriginalExtension());
        }

        if ($messageText === '' && ! $attachmentUrl) {
            return back()->with('error', 'Please enter a message or attach a file before sending.');
        }

        $sentSuccessfully = false;

        try {
            if ($messageText !== '') {
                $metaService->sendTextReply($conversation, $messageText);
                $sentSuccessfully = true;
            }

            if ($attachmentUrl) {
                $metaService->sendAttachmentReply($conversation, $attachmentUrl, $attachmentType ?? 'document');
                $sentSuccessfully = true;
            }
        } catch (\Throwable $e) {
            Log::error('Social chat reply failed', [
                'conversation_id' => $conversation->id,
                'sender_id' => $conversation->sender_id,
                'message_text' => $messageText,
                'attachment_url' => $attachmentUrl,
                'exception' => $e->getMessage(),
            ]);

            $message = $e->getMessage();
            if (str_contains(strtolower($message), 'outside the allowed window') || str_contains(strtolower($message), 'messaging window')) {
                $message = 'Meta rejected the reply because it was outside the 24-hour messaging window. The app retried with a HUMAN_AGENT tag when allowed.';
            }

            return back()->with('error', $message);
        }

        if (! $sentSuccessfully) {
            return back()->with('error', 'Meta API rejected the reply. Please check the page token and permissions.');
        }

        $storedMessage = ChannelMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'user_id' => $user->id,
            'message_text' => $messageText !== '' ? $messageText : null,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
        ]);

        $conversation->update([
            'status' => 'assigned',
            'last_message_at' => $storedMessage->created_at,
        ]);

        return back()->with('success', 'Reply sent successfully.');
    }

    protected function detectAttachmentType(string $extension): ?string
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'mp3', 'wav', 'm4a', 'aac' => 'audio',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt' => 'document',
            default => null,
        };
    }

    public function isImageAttachment(?string $attachmentUrl, ?string $attachmentType = null): bool
    {
        if (! is_string($attachmentUrl) || trim($attachmentUrl) === '') {
            return false;
        }

        if ($attachmentType === 'image') {
            return true;
        }

        $lower = strtolower($attachmentUrl);

        return preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i', $lower) === 1;
    }
}
