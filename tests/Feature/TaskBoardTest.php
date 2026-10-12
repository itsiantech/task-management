<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_approved' => true]);
    }

    public function test_board_groups_tasks_into_pending_hold_complete(): void
    {
        $admin = $this->admin();
        Task::factory()->create(['title' => 'Pending job', 'status' => 'pending', 'created_by' => $admin->id]);
        Task::factory()->create(['title' => 'Held job', 'status' => 'hold', 'created_by' => $admin->id]);
        Task::factory()->create(['title' => 'Done job', 'status' => 'completed', 'created_by' => $admin->id]);

        $html = $this->actingAs($admin)->get(route('tasks.index', ['view' => 'board']))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('Pending job', $html);
        $this->assertStringContainsString('Held job', $html);
        $this->assertStringContainsString('Done job', $html);
    }

    public function test_quick_status_change_moves_card(): void
    {
        $admin = $this->admin();
        $task = Task::factory()->create(['title' => 'Move me', 'status' => 'pending', 'created_by' => $admin->id]);

        $this->actingAs($admin)->patch(route('tasks.status', $task), ['status' => 'completed'])
            ->assertRedirect();
        $this->assertEquals('completed', $task->fresh()->status);

        // Invalid status rejected.
        $this->actingAs($admin)->patch(route('tasks.status', $task), ['status' => 'archived'])
            ->assertSessionHasErrors('status');
        $this->assertEquals('completed', $task->fresh()->status);
    }

    public function test_status_tabs_include_pending_hold_completed(): void
    {
        $admin = $this->admin();
        Task::factory()->create(['title' => 'Pending Alpha Task ZZ', 'status' => 'pending', 'created_by' => $admin->id]);
        Task::factory()->create(['title' => 'Held Beta Task ZZ', 'status' => 'hold', 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('tasks.index', ['status' => 'pending']))
            ->assertStatus(200)->assertSeeText('Pending Alpha Task ZZ')->assertDontSee('Held Beta Task ZZ');

        $this->actingAs($admin)->get(route('tasks.index', ['status' => 'hold']))
            ->assertStatus(200)->assertSeeText('Held Beta Task ZZ');
    }

    public function test_first_page_shows_running_work_with_in_progress_default(): void
    {
        $admin = $this->admin();
        Task::factory()->create(['title' => 'Running Work ZZ', 'status' => 'in_progress', 'created_by' => $admin->id]);
        Task::factory()->create(['title' => 'Done Work ZZ', 'status' => 'completed', 'created_by' => $admin->id]);
        Task::factory()->create(['title' => 'Waiting Work ZZ', 'status' => 'pending', 'created_by' => $admin->id]);

        // Clicking Tasks (no filter) lands on In Progress: running work only.
        $html = $this->actingAs($admin)->get(route('tasks.index'))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('Running Work ZZ', $html);
        $this->assertStringNotContainsString('Done Work ZZ', $html);
        $this->assertStringNotContainsString('Waiting Work ZZ', $html);
    }

    public function test_assigned_member_can_change_status_but_outsider_cannot(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create(['role' => 'member', 'is_approved' => true]);
        $outsider = User::factory()->create(['role' => 'member', 'is_approved' => true]);
        $task = Task::factory()->create(['status' => 'pending', 'created_by' => $admin->id, 'assigned_to' => $member->id]);

        $this->actingAs($member)->patch(route('tasks.status', $task), ['status' => 'hold'])->assertRedirect();
        $this->assertEquals('hold', $task->fresh()->status);

        $this->actingAs($outsider)->patch(route('tasks.status', $task), ['status' => 'completed'])->assertStatus(403);
    }
}
