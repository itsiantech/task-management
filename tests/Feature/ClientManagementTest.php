<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_view_a_client(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->post('/clients', [
            'name' => 'John Smith',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'status' => 'pending',
        ]);

        $response->assertRedirect('/clients');

        $client = Client::first();
        $this->assertNotNull($client);
        $this->assertSame('John Smith', $client->name);
        $this->assertSame('+1234567890', $client->phone);

        $this->actingAs($admin)
            ->get('/clients')
            ->assertOk()
            ->assertSee('John Smith')
            ->assertSee('Pending');
    }
}
