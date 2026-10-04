<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_webhook_verification_returns_challenge(): void
    {
        $response = $this->get('/api/meta/webhook?hub.mode=subscribe&hub.verify_token=demo_verify_token&hub.challenge=challenge_123');

        $response->assertOk();
        $response->assertSeeText('challenge_123');
    }

    public function test_admin_can_access_social_chat_inbox(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->actingAs($admin)
            ->get('/social-chat')
            ->assertOk();
    }

    public function test_facebook_webhook_ingests_nested_messenger_payload(): void
    {
        $response = $this->postJson('/api/meta/webhook', [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => '444555'],
                    'recipient' => ['id' => '123456789'],
                    'timestamp' => 1728000000000,
                    'message' => [
                        'text' => 'hello from messenger',
                        'mid' => 'mid.123',
                    ],
                ]],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'platform' => 'facebook',
            'sender_id' => '444555',
        ]);
        $this->assertDatabaseHas('channel_messages', [
            'message_text' => 'hello from messenger',
            'sender_type' => 'customer',
        ]);
    }

    public function test_meta_service_posts_facebook_reply_to_correct_endpoint(): void
    {
        \App\Models\SocialIntegration::query()->delete();
        putenv('FACEBOOK_PAGE_ACCESS_TOKEN=page-token-123');
        config()->set('services.facebook.page_access_token', 'page-token-123');

        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/v18.0/me/messages*' => \Illuminate\Support\Facades\Http::response(['message_id' => 'mid.123'], 200),
        ]);

        $conversation = \App\Models\Conversation::create([
            'platform' => 'facebook',
            'sender_id' => '444555',
            'sender_name' => 'Messenger User',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $service = new \App\Services\MetaService();
        $result = $service->sendTextReply($conversation, 'Thanks for reaching out');

        $this->assertTrue($result);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $url = (string) $request->url();

            return str_contains($url, 'https://graph.facebook.com/v18.0/me/messages')
                && str_contains($url, 'access_token=page-token-123')
                && $request['recipient']['id'] === '444555'
                && $request['messaging_type'] === 'RESPONSE'
                && $request['message']['text'] === 'Thanks for reaching out';
        });
    }

    public function test_meta_service_falls_back_to_configured_facebook_page_token(): void
    {
        \App\Models\SocialIntegration::query()->delete();
        putenv('FACEBOOK_PAGE_ACCESS_TOKEN=config-fallback-token');
        config()->set('services.facebook.page_access_token', 'config-fallback-token');

        $service = new \App\Services\MetaService();

        $this->assertSame('config-fallback-token', $service->resolvePageAccessToken());
    }

    public function test_meta_service_retries_with_human_agent_tag_when_message_window_is_closed(): void
    {
        \App\Models\SocialIntegration::query()->delete();
        putenv('FACEBOOK_PAGE_ACCESS_TOKEN=page-token-123');
        config()->set('services.facebook.page_access_token', 'page-token-123');

        \Illuminate\Support\Facades\Http::fake(function () {
            static $callCount = 0;
            $callCount++;

            if ($callCount === 1) {
                return \Illuminate\Support\Facades\Http::response([
                    'error' => ['code' => 10, 'message' => 'Message was not sent because outside the allowed window.'],
                ], 400);
            }

            return \Illuminate\Support\Facades\Http::response(['message_id' => 'mid.tagged'], 200);
        });

        $conversation = \App\Models\Conversation::create([
            'platform' => 'facebook',
            'sender_id' => '444555',
            'sender_name' => 'Messenger User',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $service = new \App\Services\MetaService();

        $this->assertTrue($service->sendTextReply($conversation, 'Follow up after window close'));

        \Illuminate\Support\Facades\Http::assertSentCount(2);
        \Illuminate\Support\Facades\Http::assertSent(function ($request, $options) {
            $url = (string) $request->url();

            return str_contains($url, 'https://graph.facebook.com/v18.0/me/messages')
                && $request['messaging_type'] === 'MESSAGE_TAG'
                && $request['tag'] === 'HUMAN_AGENT'
                && $request['message']['text'] === 'Follow up after window close';
        });
    }
}
