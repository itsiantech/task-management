<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCorrection;
use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TaskCorrectionController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->ensureTaskAccess($task);

        $validated = $request->validate([
            'correction_date' => ['required', 'date'],
            'content' => ['nullable', 'string'],
        ]);

        $correction = $task->corrections()->create([
            'user_id' => Auth::id(),
            'correction_date' => $validated['correction_date'],
            'content' => SafeHtml::clean($validated['content'] ?? ''),
        ]);

        return response()->json(['ok' => true, 'id' => $correction->id]);
    }

    public function update(Request $request, Task $task, TaskCorrection $correction)
    {
        $this->ensureTaskAccess($task);
        abort_unless($this->canManage($correction), 403, 'Only the author or an admin can edit this correction.');

        $validated = $request->validate([
            'correction_date' => ['nullable', 'date'],
            'content' => ['nullable', 'string'],
        ]);

        $correction->update([
            'correction_date' => $validated['correction_date'] ?? $correction->correction_date,
            'content' => SafeHtml::clean($validated['content'] ?? (string) $correction->content),
        ]);

        return response()->json(['ok' => true]);
    }

    public function toggleStatus(Task $task, TaskCorrection $correction)
    {
        $this->ensureTaskAccess($task);
        abort_unless($this->canManage($correction), 403, 'Only the author or an admin can change this correction status.');

        $correction->update(['status' => $correction->status === 'done' ? 'pending' : 'done']);

        return response()->json(['ok' => true, 'status' => $correction->status]);
    }

    public function destroy(Task $task, TaskCorrection $correction)
    {
        $this->ensureTaskAccess($task);
        abort_unless($this->canManage($correction), 403, 'Only the author or an admin can delete this correction.');

        $correction->delete();

        return response()->json(['ok' => true]);
    }

    private function canManage(TaskCorrection $correction): bool
    {
        $user = Auth::user();

        return $user->isAdmin() || $correction->user_id === $user->id;
    }

    private function ensureTaskAccess(Task $task): void
    {
        $user = Auth::user();
        $hasTaskMembersTable = Schema::hasTable('task_user');

        $allowed = $user->isAdmin()
            || $task->assigned_to === $user->id
            || $task->created_by === $user->id
            || ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists());

        abort_unless($allowed, 403, 'You do not have access to this task.');
    }
}
