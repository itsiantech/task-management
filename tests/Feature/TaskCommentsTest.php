<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_post_task_comments_with_attachment_path(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $task = Task::create([
            'title' => 'Website redesign',
            'description' => 'Fix the homepage',
            'status' => 'in_progress',
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'priority' => 'high',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'message' => 'I have updated the page copy.',
            'attachment_path' => 'task-comments/hello.txt',
        ]);

        $response = $this->actingAs($admin)->getJson("/tasks/{$task->id}/comments");

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'I have updated the page copy.',
        ]);
        $this->assertSame('task-comments/hello.txt', $comment->fresh()->attachment_path ?? $comment->fresh()->attachment);
    }
}
