<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorkflowStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_task_with_new_workflow_statuses(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $member = User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $this->actingAs($admin)
            ->post('/tasks', [
                'title' => 'QA checklist',
                'description' => 'Verify launch checklist',
                'status' => 'testing',
                'start_date' => '2026-10-03',
                'due_date' => '2026-10-10',
                'assigned_users' => [$member->id],
                'tags' => ['Bug', 'UI/UX'],
                'priority' => 'high',
            ])
            ->assertRedirect('/tasks');

        $this->actingAs($admin)
            ->get('/tasks?status=testing')
            ->assertOk()
            ->assertSeeText('Testing')
            ->assertSeeText('QA checklist');
    }
}
