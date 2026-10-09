<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\SocialIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetaService
{
    public function resolvePageAccessToken(): ?string
    {
        $databaseToken = SocialIntegration::query()
            ->whereNotNull('access_token')
            ->whereRaw('TRIM(COALESCE(access_token, "")) <> ""')
            ->orderByDesc('id')
            ->value('access_token');

        if (is_string($databaseToken) && trim($databaseToken) !== '') {
            return trim($databaseToken);
        }

        $configToken = trim((string) (
            config('services.facebook.page_access_token')
            ?: config('services.meta.facebook_page_token')
            ?: env('FACEBOOK_PAGE_ACCESS_TOKEN')
            ?: env('META_FACEBOOK_PAGE_TOKEN')
            ?: ''
        ));

        if ($configToken !== '') {
            return $configToken;
        }

        Log::warning('Facebook page access token missing in social_integrations and .env/config. Check the saved integration row or set FACEBOOK_PAGE_ACCESS_TOKEN.');

        return null;
    }

    public function sendTextReply(Conversation $conversation, string $message): bool
    {
        if ($conversation->platform === 'facebook') {
            return $this->sendFacebookReply([
                'recipient' => ['id' => $conversation->sender_id],
                'messaging_type' => 'RESPONSE',
                'message' => ['text' => $message],
            ]);
        }

        $response = Http::acceptJson()->post(
            'https://graph.facebook.com/v18.0/' . config('services.meta.whatsapp_phone_number_id') . '/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $conversation->sender_id,
                'type' => 'text',
                'text' => ['body' => $message],
            ],
            ['Authorization' => 'Bearer ' . config('services.meta.whatsapp_access_token')]
        );

        return $response->successful();
    }

    public function sendAttachmentReply(Conversation $conversation, string $attachmentUrl, string $attachmentType): bool
    {
        if ($conversation->platform === 'facebook') {
            $payload = [
                'recipient' => ['id' => $conversation->sender_id],
                'messaging_type' => 'RESPONSE',
            ];

            $type = match ($attachmentType) {
                'image' => 'image',
                'audio' => 'audio',
                'video' => 'video',
                default => 'file',
            };

            if ($type === 'file' && ! preg_match('/\.[a-z0-9]+(\?.*)?$/i', parse_url($attachmentUrl, PHP_URL_PATH) ?: '')) {
                $payload['message'] = ['text' => 'Attachment received: ' . $attachmentUrl];
            } else {
                $payload['message'] = ['attachment' => ['type' => $type, 'payload' => ['url' => $attachmentUrl]]];
            }

            return $this->sendFacebookReply($payload);
        }

        $type = match ($attachmentType) {
            'image' => 'image',
            'audio' => 'audio',
            'document' => 'document',
            default => 'text',
        };

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $conversation->sender_id,
            'type' => $type,
        ];

        if ($type === 'text') {
            $payload['text'] = ['body' => 'Attachment: ' . $attachmentUrl];
        } else {
            $payload[$type] = ['link' => $attachmentUrl];
        }

        $response = Http::acceptJson()
            ->withHeaders(['Authorization' => 'Bearer ' . config('services.meta.whatsapp_access_token')])
            ->post('https://graph.facebook.com/v18.0/' . config('services.meta.whatsapp_phone_number_id') . '/messages', $payload);

        return $response->successful();
    }

    protected function sendFacebookReply(array $payload): bool
    {
        $pageToken = $this->resolvePageAccessToken();

        if ($pageToken === null || trim($pageToken) === '') {
            $errorMessage = 'Facebook page access token is not configured.';
            Log::error('Facebook Messenger reply failed: ' . $errorMessage);

            throw new \RuntimeException($errorMessage);
        }

        try {
            $response = Http::acceptJson()->post(
                'https://graph.facebook.com/v18.0/me/messages?access_token=' . urlencode($pageToken),
                $payload
            );

            if (! $response->successful()) {
                $errorBody = $response->json('error', []);
                $errorMessage = data_get($errorBody, 'message', 'Meta API returned a non-200 response.');
                $errorCode = data_get($errorBody, 'code');
                $errorDetails = json_encode($errorBody ?: $response->body(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

                Log::error('Facebook Messenger reply failed', [
                    'payload' => $payload,
                    'meta_error' => $errorDetails,
                    'status' => $response->status(),
                ]);

                if ($this->shouldRetryWithHumanAgentTag($errorCode, $errorMessage)) {
                    $taggedPayload = $this->withHumanAgentTag($payload);

                    Log::warning('Retrying Facebook Messenger reply with MESSAGE_TAG/HUMAN_AGENT', [
                        'original_payload' => $payload,
                        'retry_payload' => $taggedPayload,
                        'meta_error' => $errorDetails,
                    ]);

                    $retryResponse = Http::acceptJson()->post(
                        'https://graph.facebook.com/v18.0/me/messages?access_token=' . urlencode($pageToken),
                        $taggedPayload
                    );

                    if (! $retryResponse->successful()) {
                        $retryErrorBody = $retryResponse->json('error', []);
                        $retryMessage = data_get($retryErrorBody, 'message', 'Meta API returned a non-200 response after tag retry.');
                        Log::error('Facebook Messenger tagged retry failed', [
                            'payload' => $taggedPayload,
                            'meta_error' => json_encode($retryErrorBody ?: $retryResponse->body(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                            'status' => $retryResponse->status(),
                        ]);

                        throw new \RuntimeException('Meta API rejected the reply: ' . $retryMessage);
                    }

                    return true;
                }

                throw new \RuntimeException('Meta API rejected the reply: ' . $errorMessage);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Facebook Messenger reply exception', [
                'payload' => $payload,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function shouldRetryWithHumanAgentTag(?int $errorCode, string $message): bool
    {
        $normalized = strtolower($message);

        return in_array($errorCode, [10, 190], true)
            || str_contains($normalized, 'outside the allowed window')
            || str_contains($normalized, 'not sent because outside the allowed window')
            || str_contains($normalized, 'messaging window');
    }

    protected function withHumanAgentTag(array $payload): array
    {
        $taggedPayload = $payload;
        $taggedPayload['messaging_type'] = 'MESSAGE_TAG';
        $taggedPayload['tag'] = 'HUMAN_AGENT';

        return $taggedPayload;
    }
}
