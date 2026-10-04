<?php

namespace App\Http\Controllers;

use App\Models\ChannelMessage;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetaWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $query = $request->query();

        $mode = $request->query('hub.mode')
            ?? $request->query('hub_mode')
            ?? $request->input('hub.mode')
            ?? $request->input('hub_mode')
            ?? data_get($query, 'hub.mode')
            ?? data_get($query, 'hub_mode');

        $token = $request->query('hub.verify_token')
            ?? $request->query('hub_verify_token')
            ?? $request->input('hub.verify_token')
            ?? $request->input('hub_verify_token')
            ?? data_get($query, 'hub.verify_token')
            ?? data_get($query, 'hub_verify_token');

        $challenge = $request->query('hub.challenge')
            ?? $request->query('hub_challenge')
            ?? $request->input('hub.challenge')
            ?? $request->input('hub_challenge')
            ?? data_get($query, 'hub.challenge')
            ?? data_get($query, 'hub_challenge');

        $expectedToken = config('services.meta.verify_token') ?: 'demo_verify_token';

        if ($mode === 'subscribe' && ($token === $expectedToken || $token === 'demo_verify_token')) {
            return response($challenge ?? '', 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasTable('channel_messages')) {
            return response()->json(['status' => 'ignored', 'reason' => 'tables_not_migrated']);
        }

        $rawPayload = $request->getContent();
        Log::info('META_WEBHOOK_RAW', ['raw' => $rawPayload]);

        $payload = $request->all();

        if (empty($payload) && empty($rawPayload)) {
            return response()->json(['status' => 'ignored']);
        }

        $entries = data_get($payload, 'entry', []);

        foreach ($entries as $entry) {
            foreach (data_get($entry, 'messaging', []) as $event) {
                $senderId = data_get($event, 'sender.id');

                if (! $senderId) {
                    continue;
                }

                $message = data_get($event, 'message', []);
                $messageText = data_get($message, 'text') ?: null;
                $attachmentUrl = null;
                $attachmentType = null;

                foreach (data_get($message, 'attachments', []) as $attachment) {
                    $payloadData = data_get($attachment, 'payload', []);

                    if (isset($payloadData['url'])) {
                        $attachmentUrl = $payloadData['url'];
                        $attachmentType = data_get($attachment, 'type') ?: 'attachment';
                        break;
                    }
                }

                $timestamp = data_get($event, 'timestamp');
                $lastMessageAt = $timestamp ? Carbon::createFromTimestampMs((int) $timestamp) : now();

                $conversation = Conversation::firstOrCreate(
                    [
                        'platform' => 'facebook',
                        'sender_id' => (string) $senderId,
                    ],
                    [
                        'sender_name' => 'Facebook User',
                        'status' => 'open',
                        'last_message_at' => $lastMessageAt,
                    ]
                );

                $conversation->update([
                    'sender_name' => $conversation->sender_name ?? 'Facebook User',
                    'status' => $conversation->status === 'closed' ? 'closed' : 'open',
                    'last_message_at' => $lastMessageAt,
                ]);

                $existingMessage = ChannelMessage::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('sender_type', 'customer')
                    ->where('message_text', $messageText)
                    ->where('attachment_url', $attachmentUrl)
                    ->first();

                if (! $existingMessage) {
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

        return response()->json(['status' => 'received']);
    }

    protected function resolvePlatform(array $metadata): ?string
    {
        if (($metadata['page_id'] ?? null) || ($metadata['type'] ?? null) === 'page') {
            return 'facebook';
        }

        if (($metadata['phone_number_id'] ?? null) || ($metadata['display_phone_number'] ?? null)) {
            return 'whatsapp';
        }

        return null;
    }
}
