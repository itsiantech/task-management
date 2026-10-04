<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialIntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_integration_settings_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->actingAs($admin)
            ->get('/settings/integrations')
            ->assertOk();
    }

    public function test_admin_can_store_social_integration_credentials(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->post('/settings/integrations', [
            'platform' => 'facebook',
            'account_name' => 'Acme Page',
            'page_id_or_phone_id' => '123456789',
            'access_token' => 'demo_token',
            'webhook_verify_token' => 'demo_verify_token',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_integrations', [
            'platform' => 'facebook',
            'account_name' => 'Acme Page',
            'is_active' => true,
        ]);
    }
}
