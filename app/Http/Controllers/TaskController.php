<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Task;
use App\Models\User;
use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedStatus = $request->query('status', 'all');
        $validStatuses = ['all', 'not_started', 'in_progress', 'testing', 'awaiting_feedback', 'completed'];

        if (! in_array($selectedStatus, $validStatuses, true)) {
            $selectedStatus = 'all';
        }

        $hasTaskMembersTable = Schema::hasTable('task_user');

        $query = Task::with(array_filter([
            'creator',
            'assignee',
            $hasTaskMembersTable ? 'members' : null,
            'comments.user',
        ]))
            ->when(! $user->isAdmin(), function ($query) use ($user, $hasTaskMembersTable) {
                $query->where(function ($subQuery) use ($user, $hasTaskMembersTable) {
                    $subQuery->where('assigned_to', $user->id)
                        ->when($hasTaskMembersTable, fn ($memberQuery) => $memberQuery->orWhereHas('members', fn ($inner) => $inner->where('users.id', $user->id)));
                });
            });

        $allTasks = $query->orderBy('due_date')->orderBy('created_at', 'desc')->get();

        $tasks = $selectedStatus === 'all'
            ? $allTasks
            : $allTasks->filter(fn ($task) => $task->status === $selectedStatus)->values();

        $taskCounts = [
            'all' => $allTasks->count(),
            'not_started' => $allTasks->where('status', 'not_started')->count(),
            'in_progress' => $allTasks->where('status', 'in_progress')->count(),
            'testing' => $allTasks->where('status', 'testing')->count(),
            'awaiting_feedback' => $allTasks->where('status', 'awaiting_feedback')->count(),
            'completed' => $allTasks->where('status', 'completed')->count(),
        ];

        $approvedMembers = User::where('role', 'member')
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();

        $pendingUsers = $user->isAdmin() ? User::where('is_approved', false)->latest()->get() : collect();

        $budgetSummary = [
            'total_budget' => $allTasks->sum('total_budget'),
            'paid_amount' => $allTasks->sum('paid_amount'),
            'due_amount' => $allTasks->sum('due_amount'),
        ];

        $readAnnouncementIds = AnnouncementRecipient::where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->pluck('announcement_id');

        $dashboardAnnouncements = Announcement::query()
            ->where(function ($query) use ($user) {
                $query->where('target_type', 'all')
                    ->orWhereHas('recipients', fn ($inner) => $inner->where('user_id', $user->id));
            })
            ->with('sender:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($announcement) use ($readAnnouncementIds) {
                $announcement->is_read = $readAnnouncementIds->contains($announcement->id);

                return $announcement;
            });

        return view('tasks.index', compact('tasks', 'approvedMembers', 'pendingUsers', 'budgetSummary', 'hasTaskMembersTable', 'selectedStatus', 'taskCounts', 'dashboardAnnouncements'));
    }

    public function show(Task $task)
    {
        $user = Auth::user();

        $hasTaskMembersTable = Schema::hasTable('task_user');
        $allowed = $user->isAdmin() || $task->assigned_to === $user->id || $task->created_by === $user->id || ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists());

        if (! $allowed) {
            abort(403, 'You do not have access to this task.');
        }

        $task->load(array_filter([
            'creator',
            'assignee',
            $hasTaskMembersTable ? 'members' : null,
            'comments.user',
            'corrections.user',
        ]));

        return view('tasks.show', compact('task'));
    }

    public function updateDescription(Request $request, Task $task)
    {
        $user = Auth::user();
        $hasTaskMembersTable = Schema::hasTable('task_user');

        $allowed = $user->isAdmin() || $task->assigned_to === $user->id || $task->created_by === $user->id || ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists());

        abort_unless($allowed, 403, 'You do not have access to update this task.');

        $validated = $request->validate([
            'description' => ['nullable', 'string'],
        ]);

        $task->update([
            'description' => SafeHtml::clean($validated['description'] ?? ''),
        ]);

        return response()->json(['ok' => true, 'saved_at' => now()->toISOString()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Only admins can create tasks.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sticky_note' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'testing', 'awaiting_feedback', 'completed', 'pending', 'hold', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'tags' => [
                'nullable',
                Rule::anyOf([
                    ['string', 'max:2000'],
                    ['array'],
                ]),
            ],
            'tags.*' => ['string', 'max:50'],
            'assigned_to' => ['nullable', 'exists:users,id', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'member')->where('is_approved', true))],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:users,id', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'member')->where('is_approved', true))],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        $validated['status'] = $this->normalizeStatus($validated['status'] ?? 'not_started');
        $validated['priority'] = $this->normalizePriority($validated['priority'] ?? 'medium');
        $validated['tags'] = $this->normalizeTags($request->input('tags'));

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('task-attachments', 'public');
        }

        $validated['created_by'] = Auth::id();
        $selectedMembers = $request->input('assigned_users', []);

        if (! empty($selectedMembers)) {
            $validated['assigned_to'] = (int) $selectedMembers[0];
        }

        $task = Task::create($validated);
        $task->syncMembers($selectedMembers);

        if (! empty($selectedMembers)) {
            $task->assigned_to = (int) $selectedMembers[0];
            $task->saveQuietly();
        }

        $task->due_amount = max(0, (float) ($task->total_budget ?? 0) - (float) ($task->paid_amount ?? 0));
        $task->payment_status = match (true) {
            (float) ($task->paid_amount ?? 0) <= 0 => 'pending',
            (float) $task->due_amount <= 0 && (float) ($task->total_budget ?? 0) > 0 => 'complete',
            (float) ($task->paid_amount ?? 0) > 0 && (float) $task->due_amount > 0 => 'partial',
            default => $task->payment_status ?? 'pending',
        };
        $task->saveQuietly();

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $user = Auth::user();

        $hasTaskMembersTable = Schema::hasTable('task_user');

        if (! $user->isAdmin() && $task->assigned_to !== $user->id && ! ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists())) {
            abort(403, 'You are not allowed to update this task.');
        }

        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sticky_note' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'testing', 'awaiting_feedback', 'completed', 'pending', 'hold', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['sometimes', 'nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'tags' => [
                'nullable',
                Rule::anyOf([
                    ['string', 'max:2000'],
                    ['array'],
                ]),
            ],
            'tags.*' => ['string', 'max:50'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'member')->where('is_approved', true))],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:users,id', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'member')->where('is_approved', true))],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ];

        if (! $user->isAdmin()) {
            $rules = [
                'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'testing', 'awaiting_feedback', 'completed', 'pending', 'hold', 'cancelled'])],
                'priority' => ['sometimes', 'nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
                'attachment' => ['nullable', 'file', 'max:20480'],
            ];
        }

        $validated = $request->validate($rules);

        if (array_key_exists('status', $validated)) {
            $validated['status'] = $this->normalizeStatus($validated['status']);
        }

        if (array_key_exists('priority', $validated) && $validated['priority'] !== null) {
            $validated['priority'] = $this->normalizePriority($validated['priority']);
        } elseif (! array_key_exists('priority', $validated)) {
            $validated['priority'] = $this->normalizePriority($task->priority ?? 'medium');
        }

        if ($request->has('tags')) {
            $validated['tags'] = $this->normalizeTags($request->input('tags'));
        }

        if ($request->hasFile('attachment')) {
            if ($task->attachment_path && Storage::disk('public')->exists($task->attachment_path)) {
                Storage::disk('public')->delete($task->attachment_path);
            }

            $validated['attachment_path'] = $request->file('attachment')->store('task-attachments', 'public');
        }

        if ($user->isAdmin() && $request->has('assigned_users')) {
            $selectedMembers = $request->input('assigned_users', []);
            $task->syncMembers($selectedMembers);
            if (! empty($selectedMembers)) {
                $task->assigned_to = (int) $selectedMembers[0];
            }
        }

        $task->fill($validated);

        $task->total_budget = $request->filled('total_budget') ? (float) $request->input('total_budget') : ($task->total_budget ?? 0);
        $task->paid_amount = $request->filled('paid_amount') ? (float) $request->input('paid_amount') : ($task->paid_amount ?? 0);
        $task->due_amount = max(0, (float) $task->total_budget - (float) $task->paid_amount);
        $task->payment_status = match (true) {
            (float) $task->paid_amount <= 0 => 'pending',
            (float) $task->due_amount <= 0 && (float) $task->total_budget > 0 => 'complete',
            (float) $task->paid_amount > 0 && (float) $task->due_amount > 0 => 'partial',
            default => $task->payment_status ?? 'pending',
        };

        $task->save();

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtolower((string) ($status ?? 'not_started'));

        return match ($status) {
            'pending' => 'not_started',
            'hold' => 'awaiting_feedback',
            'cancelled' => 'completed',
            default => in_array($status, ['not_started', 'in_progress', 'testing', 'awaiting_feedback', 'completed'], true) ? $status : 'not_started',
        };
    }

    private function normalizePriority(?string $priority): string
    {
        $priority = strtolower((string) ($priority ?? 'medium'));

        return in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'medium';
    }

    private function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,\n]/', $tags) ?: [];
        }

        if (! is_array($tags)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(fn ($tag) => trim((string) $tag), $tags), fn ($tag) => $tag !== '')));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Only admins can delete tasks.');
        }

        if ($task->attachment_path && Storage::disk('public')->exists($task->attachment_path)) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        if (Schema::hasTable('task_user')) {
            $task->members()->detach();
        }

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function downloadAttachment(Task $task)
    {
        $user = Auth::user();

        $hasTaskMembersTable = Schema::hasTable('task_user');

        if (! $user->isAdmin() && $task->assigned_to !== $user->id && ! ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists())) {
            abort(403, 'You do not have access to this attachment.');
        }

        if (! $task->attachment_path || ! Storage::disk('public')->exists($task->attachment_path)) {
            abort(404, 'Attachment not found.');
        }

        return response()->download(Storage::disk('public')->path($task->attachment_path), basename($task->attachment_path));
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        $user = Auth::user();

        $hasTaskMembersTable = Schema::hasTable('task_user');

        if (! $user->isAdmin() && $task->assigned_to !== $user->id && ! ($hasTaskMembersTable && $task->members()->where('users.id', $user->id)->exists())) {
            abort(403, 'You do not have access to upload files for this task.');
        }

        $request->validate([
            'attachment' => ['required', 'file', 'max:20480'],
        ]);

        if ($task->attachment_path && Storage::disk('public')->exists($task->attachment_path)) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        $task->update([
            'attachment_path' => $request->file('attachment')->store('task-attachments', 'public'),
        ]);

        return redirect()->route('tasks.index')->with('success', 'Attachment uploaded successfully.');
    }
}
