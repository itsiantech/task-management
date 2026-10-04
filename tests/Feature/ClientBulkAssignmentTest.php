<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientBulkAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_assign_clients_to_team_members(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $memberA = User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $memberB = User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $clientA = Client::create([
            'name' => 'Alpha Client',
            'phone' => '+1234567890',
            'email' => 'alpha@example.com',
            'address' => 'Alpha Address',
            'status' => 'pending',
        ]);

        $clientB = Client::create([
            'name' => 'Beta Client',
            'phone' => '+1987654321',
            'email' => 'beta@example.com',
            'address' => 'Beta Address',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('clients.bulkAssign'), [
            'client_ids' => [$clientA->id, $clientB->id],
            'member_ids' => [$memberA->id, $memberB->id],
        ]);

        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success', 'Clients assigned successfully.');

        $this->assertDatabaseHas('client_user', [
            'client_id' => $clientA->id,
            'user_id' => $memberA->id,
        ]);

        $this->assertDatabaseHas('client_user', [
            'client_id' => $clientA->id,
            'user_id' => $memberB->id,
        ]);

        $this->assertDatabaseHas('client_user', [
            'client_id' => $clientB->id,
            'user_id' => $memberA->id,
        ]);

        $this->assertDatabaseHas('client_user', [
            'client_id' => $clientB->id,
            'user_id' => $memberB->id,
        ]);
    }
}
