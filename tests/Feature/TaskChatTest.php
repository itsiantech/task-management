<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskChatTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_approved' => true]);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'member', 'is_approved' => true]);
    }

    private function taskWithMember(User $admin, User $member): Task
    {
        return Task::factory()->create([
            'status' => 'in_progress',
            'created_by' => $admin->id,
            'assigned_to' => $member->id,
        ]);
    }

    public function test_sides_are_role_based_for_every_viewer(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $task = $this->taskWithMember($admin, $member);

        TaskComment::create(['task_id' => $task->id, 'user_id' => $admin->id, 'message' => 'Admin msg']);
        TaskComment::create(['task_id' => $task->id, 'user_id' => $member->id, 'message' => 'Member msg']);

        // Admin viewer: admin right, member left.
        $adminView = $this->actingAs($admin)->getJson(route('task.comments.index', $task))
            ->assertStatus(200)->json('comments');
        $this->assertTrue(collect($adminView)->firstWhere('message', 'Admin msg')['is_admin']);
        $this->assertFalse(collect($adminView)->firstWhere('message', 'Member msg')['is_admin']);

        // Member viewer: same sides.
        $memberView = $this->actingAs($member)->getJson(route('task.comments.index', $task))
            ->assertStatus(200)->json('comments');
        $this->assertTrue(collect($memberView)->firstWhere('message', 'Admin msg')['is_admin']);
        $this->assertFalse(collect($memberView)->firstWhere('message', 'Member msg')['is_admin']);
    }

    public function test_opening_chat_marks_other_side_seen(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $task = $this->taskWithMember($admin, $member);

        $adminMsg = TaskComment::create(['task_id' => $task->id, 'user_id' => $admin->id, 'message' => 'a']);
        $memberMsg = TaskComment::create(['task_id' => $task->id, 'user_id' => $member->id, 'message' => 'm']);
        $this->assertNull($adminMsg->fresh()->read_at);

        // Member opens: admin's message becomes seen.
        $seen = $this->actingAs($member)->getJson(route('task.comments.index', $task))
            ->assertStatus(200)->json('comments');
        $this->assertNotNull(collect($seen)->firstWhere('message', 'a')['read_at']);
        $this->assertNull(collect($seen)->firstWhere('message', 'm')['read_at']);

        // Admin opens: member's message becomes seen.
        $seen = $this->actingAs($admin)->getJson(route('task.comments.index', $task))
            ->assertStatus(200)->json('comments');
        $this->assertNotNull(collect($seen)->firstWhere('message', 'm')['read_at']);
    }

    public function test_both_sides_can_delete_any_message(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $task = $this->taskWithMember($admin, $member);

        $adminMsg = TaskComment::create(['task_id' => $task->id, 'user_id' => $admin->id, 'message' => 'a']);

        // Member deletes admin's message.
        $this->actingAs($member)->deleteJson(route('task.comments.destroy', [$task, $adminMsg]))
            ->assertStatus(200);
        $this->assertDatabaseMissing('task_comments', ['id' => $adminMsg->id]);

        // Admin deletes member's message.
        $memberMsg = TaskComment::create(['task_id' => $task->id, 'user_id' => $member->id, 'message' => 'm']);
        $this->actingAs($admin)->deleteJson(route('task.comments.destroy', [$task, $memberMsg]))
            ->assertStatus(200);
        $this->assertDatabaseMissing('task_comments', ['id' => $memberMsg->id]);
    }

    public function test_outsider_cannot_read_or_delete_chat(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $outsider = $this->member();
        $task = $this->taskWithMember($admin, $member);
        $msg = TaskComment::create(['task_id' => $task->id, 'user_id' => $admin->id, 'message' => 'a']);

        $this->actingAs($outsider)->getJson(route('task.comments.index', $task))->assertStatus(403);
        $this->actingAs($outsider)->deleteJson(route('task.comments.destroy', [$task, $msg]))->assertStatus(403);
    }

    public function test_chat_drawer_has_live_sync_wiring(): void
    {
        $html = $this->actingAs($this->admin())->get(route('tasks.index'))
            ->assertStatus(200)->getContent();

        // Polling engine, new-message badge and delete wiring must exist
        // so both sides see messages without reloading.
        $this->assertStringContainsString('startChatPolling', $html);
        $this->assertStringContainsString('syncTaskChat', $html);
        $this->assertStringContainsString('taskChatNewBadge', $html);
        $this->assertStringContainsString('deleteTaskComment', $html);
    }
}
