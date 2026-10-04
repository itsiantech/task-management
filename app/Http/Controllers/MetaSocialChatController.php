<?php

namespace App\Http\Controllers;

use App\Models\ChannelMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MetaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MetaSocialChatController extends Controller
{
    public function index(Request $request)
    {
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
        $selectedConversation = $conversations->first();
        $members = User::where('is_approved', true)->get();

        return view('social-chat.index', compact('conversations', 'selectedConversation', 'members'));
    }

    public function show(Conversation $conversation)
    {
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
            'last_message_at' => $conversation->last_message_at ?? now(),
        ]);

        return back()->with('success', 'Conversation assigned successfully.');
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

        $storedMessage = ChannelMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'user_id' => $user->id,
            'message_text' => $messageText !== '' ? $messageText : null,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
        ]);

        if ($messageText !== '') {
            $metaService->sendTextReply($conversation, $messageText);
        }

        if ($attachmentUrl) {
            $metaService->sendAttachmentReply($conversation, $attachmentUrl, $attachmentType);
        }

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
}
