<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudVaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cloudvault'))->assertRedirect(route('login'));
    }

    public function test_member_can_open_cloudvault(): void
    {
        $member = User::factory()->create(['role' => 'member', 'is_approved' => true]);

        $response = $this->actingAs($member)->get(route('cloudvault'));

        $response->assertStatus(200);
        // Boot payload carries identity + CSRF for the same-origin API.
        $this->assertStringContainsString('window.__CV__', $response->getContent());
        $this->assertStringContainsString($member->email, $response->getContent());
    }

    public function test_admin_gets_cloudvault_app(): void
    {
        // Security invariant: no static entry point may shadow the route.
        $this->assertFileDoesNotExist(public_path('cloudvault/index.html'));
        $this->assertFileDoesNotExist(public_path('cloudvault'));

        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);

        $response = $this->actingAs($admin)->get(route('cloudvault'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('/cloudvault-assets/', $response->getContent());
        $this->assertStringContainsString('CloudVault Sheets', $response->getContent());
    }

    public function test_accountant_gets_cloudvault_app(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant', 'is_approved' => true]);

        $this->actingAs($accountant)->get(route('cloudvault'))->assertStatus(200);
    }

    public function test_sidebar_links_cloudvault_for_managers(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);

        $html = $this->actingAs($admin)->get(route('tasks.index'))->assertStatus(200)->getContent();

        $this->assertStringContainsString('CloudVault Sheets', $html);
        $this->assertStringContainsString(route('cloudvault'), $html);
    }
}
