<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StickyNoteTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_and_display_a_sticky_note_on_tasks(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->post('/tasks', [
            'title' => 'Client portal setup',
            'description' => 'Complete the portal work',
            'status' => 'pending',
            'sticky_note' => 'DB user: admin / pass: password123',
        ]);

        $response->assertRedirect('/tasks');

        $task = Task::first();

        $this->assertNotNull($task);
        $this->assertSame('DB user: admin / pass: password123', $task->sticky_note);

        $this->actingAs($admin)
            ->get('/tasks')
            ->assertOk()
            ->assertSee('DB user: admin / pass: password123');
    }
}
