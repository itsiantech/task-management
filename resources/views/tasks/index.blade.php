@extends('layouts.app')

@section('title', 'Task Dashboard')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow {
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        border-color: #cbd5e1;
        background: #f8fafc;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
        border-color: #cbd5e1;
        font-size: 0.875rem;
        background: #ffffff;
    }
    #task_description_editor .ql-editor {
        min-height: 8rem;
    }
    .ql-editor.ql-blank::before {
        color: #94a3b8;
    }
</style>
@endpush

@section('content')
@php
    $tasksPayload = $tasks->map(function ($task) use ($hasTaskMembersTable) {
        $taskTags = is_array($task->tags) ? $task->tags : (json_decode($task->tags ?? '[]', true) ?: []);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description ?? '',
            'sticky_note' => $task->sticky_note ?? '',
            'status' => $task->status,
            'priority' => $task->priority ?? 'medium',
            'start_date' => $task->start_date ? $task->start_date->format('Y-m-d') : '',
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : '',
            'tags' => $taskTags,
            'member_ids' => ($hasTaskMembersTable ?? false)
                ? $task->members->pluck('id')->values()->all()
                : ($task->assigned_to ? [$task->assigned_to] : []),
        ];
    })->values();
@endphp
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Team Workspace</p>
            <h1 class="text-2xl font-bold text-slate-900">{{ auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Team Member Dashboard' }}</h1>
        </div>

        <div class="flex items-center gap-3">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('lead-management.index') }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Lead Management</a>
                <a href="{{ route('admin.approvals') }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Approvals</a>
                <a href="{{ route('admin.payments') }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Financial Ledger</a>
                <a href="{{ route('admin.members') }}" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100">Team Members</a>
            @else
                <a href="{{ route('lead-management.index') }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Lead Management</a>
                <a href="{{ route('payments.member') }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">My Payments</a>
            @endif
        </div>
    </div>

    @if ($dashboardAnnouncements->isNotEmpty())
        <div class="mb-6 rounded-2xl border border-violet-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 11v3a1 1 0 0 0 1 1h2l3 4h1V5H9l-3 4H4a1 1 0 0 0-1 1v2Z" stroke-linejoin="round"/>
                    <path d="M15.5 9.5a3.5 3.5 0 0 1 0 5M18 7a7 7 0 0 1 0 10" stroke-linecap="round"/>
                </svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-violet-700">Notice Board</h2>
            </div>
            <div class="space-y-2">
                @foreach ($dashboardAnnouncements as $announcement)
                    <div class="flex items-start gap-3 rounded-xl border {{ $announcement->is_read ? 'border-slate-100 bg-slate-50/60' : 'border-violet-100 bg-violet-50/60' }} px-3 py-2.5">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $announcement->title }}</p>
                                @unless ($announcement->is_read)
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700">New</span>
                                @endunless
                            </div>
                            <p class="mt-0.5 text-sm text-slate-600">{{ $announcement->body }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $announcement->sender->name ?? 'Admin' }} · {{ $announcement->created_at->diffForHumans() }}</p>
                        </div>
                        @unless ($announcement->is_read)
                            <form action="{{ route('announcements.read', $announcement) }}" method="POST">
                                @csrf
                                <button type="submit" class="mt-0.5 rounded-lg border border-violet-200 bg-white px-2.5 py-1 text-xs font-medium text-violet-700 hover:bg-violet-50">Mark read</button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap gap-2">
            @php
                $statusTabs = [
                    'in_progress' => 'In Progress',
                    'pending' => 'Pending',
                    'hold' => 'Hold',
                    'all' => 'All Tasks',
                    'not_started' => 'Not Started',
                    'testing' => 'Testing',
                    'awaiting_feedback' => 'Awaiting Feedback',
                    'completed' => 'Completed',
                ];
            @endphp
            @foreach ($statusTabs as $key => $label)
                <a href="{{ route('tasks.index', ['status' => $key, 'view' => $view ?? 'table']) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-medium transition {{ $selectedStatus === $key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full {{ $selectedStatus === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }} px-1.5 text-[10px] font-semibold">
                        {{ $taskCounts[$key] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <div class="flex rounded-lg border border-slate-200 bg-white p-1 text-sm font-medium">
                <a href="{{ route('tasks.index', ['status' => $selectedStatus, 'view' => 'table']) }}" class="rounded-md px-3 py-1.5 {{ ($view ?? 'table') === 'table' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Table</a>
                <a href="{{ route('tasks.index', ['status' => $selectedStatus, 'view' => 'board']) }}" class="rounded-md px-3 py-1.5 {{ ($view ?? 'table') === 'board' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Board</a>
            </div>

            @if (auth()->user()->isAdmin())
                <button type="button" id="openTaskModal" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">+ New Task</button>
            @endif
        </div>
    </div>

    @php
        $pendingBucket = ($taskCounts['pending'] ?? 0) + ($taskCounts['not_started'] ?? 0);
        $holdBucket = ($taskCounts['hold'] ?? 0) + ($taskCounts['awaiting_feedback'] ?? 0);
    @endphp
    <div class="mb-8 grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Tasks</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $taskCounts['all'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
            <p class="text-sm text-amber-700">Pending</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $pendingBucket }}</p>
        </div>
        <div class="rounded-2xl border border-orange-200 bg-orange-50/60 p-5 shadow-sm">
            <p class="text-sm text-orange-700">Hold</p>
            <p class="mt-2 text-3xl font-bold text-orange-600">{{ $holdBucket }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Completed</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $taskCounts['completed'] ?? 0 }}</p>
        </div>
    </div>

    @if (($view ?? 'table') === 'board')
        @php
            $boardColumns = [
                'pending' => ['label' => 'Pending', 'statuses' => ['pending', 'not_started', 'in_progress'], 'dot' => 'bg-amber-500', 'header' => 'border-amber-200 bg-amber-50/60', 'moves' => ['hold' => '⏸ Hold', 'completed' => '✓ Complete']],
                'hold' => ['label' => 'Hold', 'statuses' => ['hold', 'testing', 'awaiting_feedback'], 'dot' => 'bg-orange-500', 'header' => 'border-orange-200 bg-orange-50/60', 'moves' => ['pending' => '↩ Pending', 'completed' => '✓ Complete']],
                'completed' => ['label' => 'Complete', 'statuses' => ['completed'], 'dot' => 'bg-emerald-500', 'header' => 'border-emerald-200 bg-emerald-50/60', 'moves' => ['pending' => '↩ Reopen', 'hold' => '⏸ Hold']],
            ];
            $boardStatusLabels = ['pending' => 'Pending', 'not_started' => 'Not Started', 'in_progress' => 'In Progress', 'testing' => 'Testing', 'awaiting_feedback' => 'Awaiting Feedback', 'hold' => 'Hold', 'completed' => 'Completed'];
        @endphp
        <div class="grid items-start gap-4 lg:grid-cols-3">
            @foreach ($boardColumns as $column)
                @php $columnTasks = $tasks->whereIn('status', $column['statuses'])->values(); @endphp
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3 shadow-sm">
                    <div class="mb-3 flex items-center justify-between rounded-xl border px-3 py-2 {{ $column['header'] }}">
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <span class="h-2.5 w-2.5 rounded-full {{ $column['dot'] }}"></span>
                            {{ $column['label'] }}
                        </span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600 shadow-sm">{{ $columnTasks->count() }}</span>
                    </div>
                    <div class="space-y-2">
                        @forelse ($columnTasks as $task)
                            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $task->order_number_label }}</p>
                                <a href="{{ route('tasks.show', $task) }}" class="mt-0.5 block text-sm font-semibold text-slate-900 hover:text-indigo-700">{{ $task->title }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ $task->assignee?->name ?? 'Unassigned' }} · {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $boardStatusLabels[$task->status] ?? ucfirst($task->status) }}</span>
                                    @if ($task->priority)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ ucfirst($task->priority) }}</span>@endif
                                </div>
                                <div class="mt-2.5 flex flex-wrap gap-1.5 border-t border-slate-100 pt-2">
                                    @foreach ($column['moves'] as $moveStatus => $moveLabel)
                                        <form action="{{ route('tasks.status', $task) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $moveStatus }}">
                                            <button type="submit" class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-100">{{ $moveLabel }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-300 px-3 py-8 text-center text-xs text-slate-400">No tasks</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Start Date</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Due Date</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned to</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Tags</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($tasks as $task)
                        @php
                            $taskTags = is_array($task->tags) ? $task->tags : (json_decode($task->tags ?? '[]', true) ?: []);
                            $statusClasses = [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'not_started' => 'bg-slate-100 text-slate-700',
                                'in_progress' => 'bg-sky-100 text-sky-700',
                                'testing' => 'bg-violet-100 text-violet-700',
                                'awaiting_feedback' => 'bg-amber-100 text-amber-700',
                                'hold' => 'bg-orange-100 text-orange-700',
                                'completed' => 'bg-emerald-100 text-emerald-700',
                            ];
                            $statusLabels = [
                                'pending' => 'Pending',
                                'not_started' => 'Not Started',
                                'in_progress' => 'In Progress',
                                'testing' => 'Testing',
                                'awaiting_feedback' => 'Awaiting Feedback',
                                'hold' => 'Hold',
                                'completed' => 'Completed',
                            ];
                            $priorityClasses = [
                                'low' => 'bg-emerald-100 text-emerald-700',
                                'medium' => 'bg-blue-100 text-blue-700',
                                'high' => 'bg-amber-100 text-amber-700',
                                'urgent' => 'bg-rose-100 text-rose-700',
                            ];
                            $priorityLabels = [
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ];
                            $taskAssigned = ($hasTaskMembersTable ?? false) && $task->members->count() ? $task->members->pluck('name')->join(', ') : ($task->assignee?->name ?? 'Unassigned');
                        @endphp
                        <tr>
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $task->title }}</p>
                                    @if ($task->sticky_note)
                                        <div class="mt-3 max-w-md rounded-xl border border-amber-300 bg-gradient-to-br from-yellow-200 via-yellow-100 to-amber-100 p-2.5 text-xs leading-5 text-amber-900 shadow-[0_8px_18px_rgba(217,119,6,0.12)]">
                                            <span class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.2em] text-amber-700">Sticky note</span>
                                            <p class="whitespace-pre-wrap">{{ $task->sticky_note }}</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$task->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabels[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $task->start_date ? $task->start_date->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $task->due_date ? $task->due_date->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $taskAssigned }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($taskTags as $tag)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-700">{{ $tag }}</span>
                                    @empty
                                        <span class="text-sm text-slate-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClasses[$task->priority] ?? 'bg-slate-100 text-slate-700' }}">{{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}</span></td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('tasks.show', $task) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Open</a>
                                    <button
                                        type="button"
                                        class="open-task-chat-btn rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-medium text-violet-700 hover:bg-violet-100"
                                        data-task-id="{{ $task->id }}"
                                        data-task-title="{{ $task->title }}"
                                        data-task-status="{{ $task->status }}"
                                        data-task-assignee="{{ $taskAssigned }}"
                                        data-comments-url="{{ route('task.comments.index', $task) }}"
                                        data-send-url="{{ route('task.comments.store', $task) }}"
                                    >
                                        <span class="inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8-1.815 0-3.505-.46-4.947-1.264L3 20l1.08-4.08A7.94 7.94 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            Chat
                                        </span>
                                        @if ($task->comments->count() > 0)
                                            <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-violet-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $task->comments->count() }}</span>
                                        @endif
                                    </button>
                                    @if (auth()->user()->isAdmin())
                                        <button type="button" class="edit-task-btn rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100" data-id="{{ $task->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">No entries found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<div id="taskChatDrawer" class="fixed inset-y-0 right-0 z-50 hidden w-full max-w-xl flex-col bg-white shadow-2xl">
    <style>
        .chat-scroll { scrollbar-width: thin; scrollbar-color: #c4b5fd transparent; scroll-behavior: smooth; }
        .chat-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .chat-scroll::-webkit-scrollbar-track { background: transparent; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 999px; }
        .chat-scroll::-webkit-scrollbar-thumb:hover { background: #a78bfa; }
        .chat-tab-active { border-color: #7c3aed !important; color: #6d28d9 !important; }
    </style>

    <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <div id="taskChatAvatar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-600 to-indigo-500 text-sm font-semibold text-white">T</div>
            <div class="min-w-0">
                <h3 id="taskChatTitle" class="truncate text-base font-bold text-slate-900">Task chat</h3>
                <p class="truncate text-xs text-slate-500"><span id="taskChatStatus" class="font-medium text-emerald-600">Open</span> &middot; <span id="taskChatAssignee">Unassigned</span></p>
            </div>
        </div>
        <button type="button" id="closeTaskChat" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-600 hover:bg-slate-100">✕</button>
    </div>

    <div class="flex border-b border-slate-200 bg-slate-50 px-4">
        <button type="button" data-chat-tab="chat" class="chat-tab -mb-px border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
            Chat
        </button>
        <button type="button" data-chat-tab="media" class="chat-tab -mb-px flex items-center gap-1.5 border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke-linejoin="round"/></svg>
            Media
            <span id="chatMediaCount" class="hidden rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold text-violet-700">0</span>
        </button>
    </div>

    <div id="taskChatMessages" class="chat-scroll flex-1 space-y-4 overflow-y-auto bg-slate-50/80 p-4"></div>

    <button id="taskChatNewBadge" type="button" class="absolute bottom-24 left-1/2 z-10 hidden -translate-x-1/2 items-center gap-1 rounded-full bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white shadow-lg hover:bg-slate-700">
        ↓ New messages
    </button>

    <div id="taskChatMediaPanel" class="chat-scroll hidden flex-1 overflow-y-auto bg-slate-50/80 p-4">
        <div class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800">
            <svg viewBox="0 0 24 24" class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke-linejoin="round"/></svg>
            Media
            <span class="text-xs font-normal text-slate-400">all images from this chat</span>
        </div>
        <div id="taskChatMediaGrid" class="grid grid-cols-3 gap-2"></div>
        <p id="taskChatMediaEmpty" class="hidden py-16 text-center text-sm text-slate-500">No media yet. Send an image in the chat and it will show up here.</p>
    </div>

    <div id="taskChatComposer" class="border-t border-slate-200 bg-white p-3">
        <div id="chatAttachmentPreview" class="mb-2 hidden items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 p-2">
            <img id="chatAttachmentThumb" class="hidden h-12 w-12 rounded-lg object-cover" alt="Selected attachment">
            <svg id="chatAttachmentIcon" viewBox="0 0 24 24" class="h-8 w-8 text-violet-400" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 5h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="M8 9h8M8 13h5" stroke-linecap="round"/></svg>
            <span id="chatAttachmentLabel" class="min-w-0 flex-1 truncate text-xs font-medium text-violet-700">file</span>
            <button type="button" id="chatAttachmentRemove" class="rounded-lg px-2 py-1 text-sm text-slate-500 hover:bg-violet-100 hover:text-rose-600">✕</button>
        </div>
        <form id="taskChatForm">
            <input id="taskChatAttachment" type="file" class="hidden" accept="image/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z,.mp4,.mov,.avi,.mkv" />
            <div class="flex items-end gap-2">
                <button type="button" id="taskChatAttachmentBtn" title="Attach image or file" class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-slate-600 hover:border-violet-300 hover:text-violet-600">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m21.4 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <textarea id="taskChatInput" rows="1" placeholder="Write a message..." class="max-h-32 w-full resize-none rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"></textarea>
                <button type="submit" id="taskChatSend" title="Send" class="shrink-0 rounded-xl bg-violet-600 p-2.5 text-white shadow-sm transition hover:bg-violet-500 active:scale-95">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="taskModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900">Create Task</h2>
            <button type="button" id="closeTaskModal" class="text-slate-500 hover:text-slate-700">✕</button>
        </div>

        <div id="taskModalErrors" class="mb-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

        <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto pr-1">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="space-y-4">
                <div>
                    <label for="task_title" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="task_title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Task name">
                </div>

                <div>
                    <label for="task_description_editor" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <div id="task_description_editor"></div>
                    <input type="hidden" name="description" id="task_description">
                </div>

                <div>
                    <label for="task_sticky_note" class="mb-1 block text-sm font-medium text-slate-700">Sticky note</label>
                    <textarea id="task_sticky_note" name="sticky_note" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Credentials, links, notes"></textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="task_status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select id="task_status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="pending">Pending</option>
                            <option value="hold">Hold</option>
                            <option value="completed">Completed</option>
                            <option value="not_started">Not Started</option>
                            <option value="in_progress">In Progress</option>
                            <option value="testing">Testing</option>
                            <option value="awaiting_feedback">Awaiting Feedback</option>
                        </select>
                    </div>
                    <div>
                        <label for="task_priority" class="mb-1 block text-sm font-medium text-slate-700">Priority</label>
                        <select id="task_priority" name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="task_start_date" class="mb-1 block text-sm font-medium text-slate-700">Start Date</label>
                        <input id="task_start_date" name="start_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div>
                        <label for="task_due_date" class="mb-1 block text-sm font-medium text-slate-700">Due Date</label>
                        <input id="task_due_date" name="due_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                </div>

                <div>
                    <label for="task_tags" class="mb-1 block text-sm font-medium text-slate-700">Tags</label>
                    <input id="task_tags" name="tags" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Bug, UI/UX, Backend, Urgent">
                </div>

                <div>
                    <label for="assigned_users" class="mb-1 block text-sm font-medium text-slate-700">Assigned users</label>
                    <select id="assigned_users" name="assigned_users[]" multiple class="h-32 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        @foreach ($approvedMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="task_budget" class="mb-1 block text-sm font-medium text-slate-700">Total budget</label>
                        <input id="task_budget" name="total_budget" type="number" step="0.01" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="0.00">
                    </div>
                    <div>
                        <label for="task_paid_amount" class="mb-1 block text-sm font-medium text-slate-700">Paid amount</label>
                        <input id="task_paid_amount" name="paid_amount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label for="task_attachment" class="mb-1 block text-sm font-medium text-slate-700">Attachment</label>
                    <input id="task_attachment" name="attachment" type="file" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700">
                </div>
            </div>

            <div class="sticky bottom-0 mt-6 flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-white py-4">
                <button type="button" id="cancelTaskModal" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="taskModalSubmit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="tasksPayload">
{!! json_encode($tasksPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}
</script>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
    const tasksData = JSON.parse(document.getElementById('tasksPayload').textContent);
    const taskModal = document.getElementById('taskModal');
    const openTaskModalBtn = document.getElementById('openTaskModal');
    const closeTaskModalBtn = document.getElementById('closeTaskModal');
    const cancelTaskModalBtn = document.getElementById('cancelTaskModal');
    const taskChatDrawer = document.getElementById('taskChatDrawer');
    const taskChatMessages = document.getElementById('taskChatMessages');
    const taskChatTitle = document.getElementById('taskChatTitle');
    const taskChatStatus = document.getElementById('taskChatStatus');
    const taskChatAssignee = document.getElementById('taskChatAssignee');
    const taskChatInput = document.getElementById('taskChatInput');
    const taskChatForm = document.getElementById('taskChatForm');
    const taskChatAttachmentInput = document.getElementById('taskChatAttachment');
    const taskChatAttachmentBtn = document.getElementById('taskChatAttachmentBtn');
    const taskChatAttachmentName = document.getElementById('chatAttachmentLabel');
    const taskChatAvatar = document.getElementById('taskChatAvatar');
    const taskChatMediaPanel = document.getElementById('taskChatMediaPanel');
    const taskChatMediaGrid = document.getElementById('taskChatMediaGrid');
    const taskChatMediaEmpty = document.getElementById('taskChatMediaEmpty');
    const chatMediaCount = document.getElementById('chatMediaCount');
    const taskChatNewBadge = document.getElementById('taskChatNewBadge');
    const CHAT_POLL_MS = 4000;
    let chatPollTimer = null;
    const taskChatComposer = document.getElementById('taskChatComposer');
    const chatAttachmentPreview = document.getElementById('chatAttachmentPreview');
    const chatAttachmentThumb = document.getElementById('chatAttachmentThumb');
    const chatAttachmentIcon = document.getElementById('chatAttachmentIcon');
    const chatAttachmentRemove = document.getElementById('chatAttachmentRemove');
    const taskModalErrors = document.getElementById('taskModalErrors');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const QUILL_TOOLBAR = [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'code-block', 'link'],
        ['clean'],
    ];

    function looksLikeHtml(value) {
        return /<\/?[a-z][\s\S]*>/i.test(value || '');
    }

    function setQuillContent(quill, value) {
        if (! value) {
            quill.setText('');
            return;
        }

        if (looksLikeHtml(value)) {
            quill.clipboard.dangerouslyPasteHTML(value);
        } else {
            quill.setText(value);
        }
    }

    const descriptionQuill = new Quill('#task_description_editor', {
        theme: 'snow',
        placeholder: 'Task description',
        modules: { toolbar: QUILL_TOOLBAR },
    });
    const taskDescriptionInput = document.getElementById('task_description');

    let currentTaskChat = {
        id: null,
        sendUrl: null,
    };

    function formatStatusText(status) {
        const labels = {
            pending: 'Pending',
            not_started: 'Not Started',
            in_progress: 'In Progress',
            testing: 'Testing',
            awaiting_feedback: 'Awaiting Feedback',
            hold: 'Hold',
            completed: 'Completed',
        };

        return labels[status] || status || 'Open';
    }

    function renderTaskComment(comment) {
        const messageText = (comment.message || '').trim();
        // Role-based sides (same for every viewer): admin right, team member left.
        const isAdminMsg = Boolean(comment.is_admin);
        const isMine = Boolean(comment.is_mine);
        const isSeen = Boolean(comment.read_at);
        const author = comment.user?.name || 'User';
        const initial = author.trim().charAt(0).toUpperCase() || 'U';
        const commentDate = comment.created_at ? new Date(comment.created_at).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Just now';
        const attachmentUrl = comment.attachment_url || null;
        const attachmentType = comment.attachment_type || 'document';
        const safeUrl = attachmentUrl ? attachmentUrl.replace(/"/g, '&quot;') : '';
        const tickColor = isSeen
            ? (isAdminMsg ? 'text-sky-300' : 'text-sky-500')
            : (isAdminMsg ? 'text-violet-200' : 'text-slate-400');
        const ticks = isMine
            ? '<svg viewBox="0 0 24 24" class="h-3.5 w-3.5 ' + tickColor + '" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M2.5 12.5 7 17l9.5-10M11 15.5l1.5 1.5L22 7" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : '';

        let attachmentHtml = '';
        if (attachmentUrl && attachmentType === 'image') {
            attachmentHtml = '<a href="' + safeUrl + '" target="_blank" rel="noopener" class="mt-2 block overflow-hidden rounded-xl border border-black/10">' +
                '<img src="' + safeUrl + '" alt="Shared image" loading="lazy" class="max-h-64 w-full object-cover transition hover:opacity-90">' +
                '</a>';
        } else if (attachmentUrl && attachmentType === 'audio') {
            attachmentHtml = '<audio controls preload="none" src="' + safeUrl + '" class="mt-2 w-56 max-w-full"></audio>';
        } else if (attachmentUrl) {
            attachmentHtml = '<a href="' + safeUrl + '" target="_blank" rel="noopener" class="mt-2 flex items-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-white">' +
                '<svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="M8 9h8M8 13h5" stroke-linecap="round"/></svg>' +
                '<span class="truncate">Open attachment</span></a>';
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-end gap-2 ' + (isAdminMsg ? 'justify-end' : 'justify-start');
        wrapper.dataset.commentId = comment.id;

        if (! isAdminMsg) {
            const avatar = document.createElement('div');
            avatar.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-600 to-slate-400 text-xs font-semibold text-white';
            avatar.textContent = initial;
            wrapper.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[80%] rounded-2xl border px-3.5 py-2.5 shadow-sm ' + (isAdminMsg
            ? 'rounded-br-md border-violet-200 bg-gradient-to-br from-violet-600 to-indigo-600 text-white'
            : 'rounded-bl-md border-slate-200 bg-white text-slate-700');

        const meta = document.createElement('div');
        meta.className = 'mb-1 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider ' + (isAdminMsg ? 'text-violet-100' : 'text-slate-400');
        meta.innerHTML = '<span>' + author.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span><span class="normal-case tracking-normal opacity-80">' + commentDate + '</span>' + ticks;
        bubble.appendChild(meta);

        if (messageText) {
            const p = document.createElement('p');
            p.className = 'whitespace-pre-wrap break-words text-sm leading-6';
            p.textContent = messageText;
            bubble.appendChild(p);
        }
        if (attachmentHtml) {
            const att = document.createElement('div');
            att.innerHTML = attachmentHtml;
            bubble.appendChild(att);
        }
        if (! messageText && ! attachmentHtml) {
            const p = document.createElement('p');
            p.className = 'text-sm italic opacity-70';
            p.textContent = 'Shared an attachment';
            bubble.appendChild(p);
        }

        const delRow = document.createElement('div');
        delRow.className = 'mt-1 flex ' + (isAdminMsg ? 'justify-end' : 'justify-start');
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.title = 'Delete message';
        delBtn.className = 'rounded px-1 text-[11px] font-medium text-slate-400 hover:text-rose-600';
        delBtn.textContent = 'Delete';
        delBtn.addEventListener('click', function () {
            deleteTaskComment(comment.id, wrapper);
        });
        delRow.appendChild(delBtn);
        bubble.appendChild(delRow);

        wrapper.appendChild(bubble);
        taskChatMessages.appendChild(wrapper);
    }

    function deleteTaskComment(commentId, wrapper) {
        if (! confirm('Delete this message? Both sides will no longer see it.')) {
            return;
        }

        const base = (currentTaskChat.sendUrl || '').replace(/\/$/, '');
        if (! base || ! commentId) {
            return;
        }

        fetch(base + '/' + commentId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('Unable to delete message.');
                }
                return response.json();
            })
            .then(function () {
                currentTaskChat.comments = (currentTaskChat.comments || []).filter(function (c) {
                    return String(c.id) !== String(commentId);
                });
                wrapper.remove();
                rebuildMediaGrid(currentTaskChat.comments);
            })
            .catch(function (error) {
                alert(error.message || 'Unable to delete message.');
            });
    }

    function scrollTaskChatToBottom() {
        taskChatMessages.scrollTop = taskChatMessages.scrollHeight;
        hideNewBadge();
    }

    function isNearBottom() {
        return taskChatMessages.scrollHeight - taskChatMessages.scrollTop - taskChatMessages.clientHeight < 140;
    }

    function hideNewBadge() {
        taskChatNewBadge?.classList.add('hidden');
        taskChatNewBadge?.classList.remove('flex');
    }

    function showNewBadge() {
        taskChatNewBadge?.classList.remove('hidden');
        taskChatNewBadge?.classList.add('flex');
    }

    taskChatNewBadge?.addEventListener('click', scrollTaskChatToBottom);
    taskChatMessages?.addEventListener('scroll', function () {
        if (isNearBottom()) hideNewBadge();
    });

    function chatSignature(comments) {
        return (comments || []).map(function (c) {
            return c.id + ':' + (c.read_at ? '1' : '0');
        }).join(',');
    }

    /* ---------- live sync (no reload): new messages, seen ticks, deletes ---------- */
    function stopChatPolling() {
        if (chatPollTimer) {
            clearInterval(chatPollTimer);
            chatPollTimer = null;
        }
    }

    function startChatPolling() {
        stopChatPolling();
        chatPollTimer = setInterval(function () {
            if (! currentTaskChat.id || ! currentTaskChat.commentsUrl) return;
            if (taskChatDrawer.classList.contains('hidden')) return;
            if (document.visibilityState !== 'visible') return;
            syncTaskChat();
        }, CHAT_POLL_MS);
    }

    function renderChatList(comments) {
        taskChatMessages.innerHTML = '';
        comments.slice().reverse().forEach(function (comment) {
            renderTaskComment(comment);
        });
    }

    function syncTaskChat() {
        fetch(currentTaskChat.commentsUrl, {
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('sync failed');
                }
                return response.json();
            })
            .then(function (data) {
                const fresh = data.comments || [];
                const signature = chatSignature(fresh);

                currentTaskChat.comments = fresh;

                if (signature === currentTaskChat.signature) {
                    return;
                }

                currentTaskChat.signature = signature;
                const stick = isNearBottom();
                const prevHeight = taskChatMessages.scrollHeight;
                const prevTop = taskChatMessages.scrollTop;

                renderChatList(fresh);
                rebuildMediaGrid(fresh);

                if (stick) {
                    scrollTaskChatToBottom();
                } else {
                    taskChatMessages.scrollTop = prevTop + (taskChatMessages.scrollHeight - prevHeight);
                    showNewBadge();
                }
            })
            .catch(function () {
                /* keep old messages on transient failure */
            });
    }

    /* ---------- media folder (images from this chat) ---------- */
    function rebuildMediaGrid(comments) {
        const images = (comments || []).filter(function (c) {
            return c.attachment_type === 'image' && c.attachment_url;
        });

        taskChatMediaGrid.innerHTML = '';
        images.forEach(function (comment) {
            const link = document.createElement('a');
            link.href = comment.attachment_url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'group block overflow-hidden rounded-xl border border-slate-200 bg-slate-100';
            link.title = (comment.user?.name || 'User') + ' · ' + (comment.created_at || '');
            const img = document.createElement('img');
            img.src = comment.attachment_url;
            img.alt = 'Chat media';
            img.loading = 'lazy';
            img.className = 'h-24 w-full object-cover transition group-hover:scale-105';
            link.appendChild(img);
            taskChatMediaGrid.appendChild(link);
        });

        taskChatMediaEmpty.classList.toggle('hidden', images.length > 0);
        chatMediaCount.textContent = images.length;
        chatMediaCount.classList.toggle('hidden', images.length === 0);
    }

    function switchChatTab(tab) {
        document.querySelectorAll('.chat-tab').forEach(function (btn) {
            btn.classList.toggle('chat-tab-active', btn.dataset.chatTab === tab);
        });
        const isMedia = tab === 'media';
        taskChatMessages.classList.toggle('hidden', isMedia);
        taskChatMediaPanel.classList.toggle('hidden', ! isMedia);
        taskChatComposer.classList.toggle('hidden', isMedia);
        if (! isMedia) {
            scrollTaskChatToBottom();
        }
    }

    document.querySelectorAll('.chat-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchChatTab(btn.dataset.chatTab);
        });
    });

    function loadTaskComments(url) {
        taskChatMessages.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-slate-500">Loading discussion...</div>';

        fetch(url, {
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('Unable to load discussion');
                }
                return response.json();
            })
            .then(function (data) {
                taskChatMessages.innerHTML = '';
                const comments = data.comments || [];
                currentTaskChat.comments = comments;
                currentTaskChat.signature = chatSignature(comments);
                hideNewBadge();

                if (! comments.length) {
                    taskChatMessages.innerHTML = '<div class="flex h-full items-center justify-center text-center text-sm text-slate-500">No messages yet.<br>Say hello and start the conversation.</div>';
                    rebuildMediaGrid([]);
                    return;
                }

                comments.slice().reverse().forEach(function (comment) {
                    renderTaskComment(comment);
                });
                rebuildMediaGrid(comments);
                scrollTaskChatToBottom();
            })
            .catch(function () {
                taskChatMessages.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-rose-600">Unable to load the discussion.</div>';
            });
    }

    function openTaskChat(button) {
        const title = button.dataset.taskTitle || 'Task chat';
        const status = formatStatusText(button.dataset.taskStatus);
        const assignee = button.dataset.taskAssignee || 'Unassigned';
        const commentsUrl = button.dataset.commentsUrl;
        const sendUrl = button.dataset.sendUrl;

        currentTaskChat.id = button.dataset.taskId;
        currentTaskChat.sendUrl = sendUrl;
        currentTaskChat.commentsUrl = commentsUrl || null;
        currentTaskChat.comments = [];
        currentTaskChat.signature = '';
        hideNewBadge();

        taskChatTitle.textContent = title;
        taskChatStatus.textContent = status;
        taskChatAssignee.textContent = 'Assigned to: ' + assignee;
        taskChatAvatar.textContent = title.trim().charAt(0).toUpperCase() || 'T';
        taskChatDrawer.classList.remove('hidden');
        taskChatDrawer.classList.add('flex');
        switchChatTab('chat');
        startChatPolling();

        if (commentsUrl) {
            loadTaskComments(commentsUrl);
        }
    }

    document.querySelectorAll('.open-task-chat-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            openTaskChat(button);
        });
    });

    document.getElementById('closeTaskChat')?.addEventListener('click', function () {
        stopChatPolling();
        taskChatDrawer.classList.add('hidden');
        taskChatDrawer.classList.remove('flex');
    });

    taskChatAttachmentBtn?.addEventListener('click', function () {
        taskChatAttachmentInput.click();
    });

    taskChatAttachmentInput?.addEventListener('change', function () {
        const file = taskChatAttachmentInput.files && taskChatAttachmentInput.files[0];
        if (! file) {
            chatAttachmentPreview.classList.add('hidden');
            chatAttachmentPreview.classList.remove('flex');
            taskChatAttachmentName.textContent = 'No file selected';
            return;
        }
        taskChatAttachmentName.textContent = file.name;
        if (file.type && file.type.indexOf('image/') === 0) {
            chatAttachmentThumb.src = URL.createObjectURL(file);
            chatAttachmentThumb.classList.remove('hidden');
            chatAttachmentIcon.classList.add('hidden');
        } else {
            chatAttachmentThumb.classList.add('hidden');
            chatAttachmentThumb.removeAttribute('src');
            chatAttachmentIcon.classList.remove('hidden');
        }
        chatAttachmentPreview.classList.remove('hidden');
        chatAttachmentPreview.classList.add('flex');
    });

    function clearChatAttachment() {
        taskChatAttachmentInput.value = '';
        chatAttachmentPreview.classList.add('hidden');
        chatAttachmentPreview.classList.remove('flex');
        chatAttachmentThumb.classList.add('hidden');
        chatAttachmentThumb.removeAttribute('src');
        chatAttachmentIcon.classList.remove('hidden');
        taskChatAttachmentName.textContent = 'No file selected';
    }

    chatAttachmentRemove?.addEventListener('click', clearChatAttachment);

    taskChatInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && ! event.shiftKey) {
            event.preventDefault();
            taskChatForm.requestSubmit();
        }
    });

    taskChatForm?.addEventListener('submit', function (event) {
        event.preventDefault();

        if (! currentTaskChat.sendUrl) {
            return;
        }

        const message = taskChatInput.value.trim();
        const file = taskChatAttachmentInput.files && taskChatAttachmentInput.files[0];

        if (! message && ! file) {
            return;
        }

        const formData = new FormData();
        formData.append('message', message);
        if (file) {
            formData.append('attachment', file);
        }

        fetch(currentTaskChat.sendUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                Accept: 'application/json',
            },
            body: formData,
        })
            .then(function (response) {
                if (! response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.message || 'Unable to send message.');
                    });
                }
                return response.json();
            })
            .then(function (data) {
                const comment = data.comment;
                if (comment) {
                    currentTaskChat.comments = [comment].concat(currentTaskChat.comments || []);
                    currentTaskChat.signature = chatSignature(currentTaskChat.comments);
                    renderTaskComment(comment);
                    rebuildMediaGrid(currentTaskChat.comments);
                    scrollTaskChatToBottom();
                }
                taskChatInput.value = '';
                clearChatAttachment();
            })
            .catch(function (error) {
                alert(error.message || 'Unable to send chat message.');
            });
    });

    function resetTaskModal() {
        const form = document.querySelector('#taskModal form');
        form.reset();
        form.querySelector('input[name="_method"]').value = 'POST';
        form.action = '{{ route('tasks.store') }}';
        document.querySelector('#taskModal h2').textContent = 'Create Task';
        document.querySelector('#task_status').value = 'not_started';
        document.querySelector('#task_priority').value = 'medium';
        const select = document.querySelector('#assigned_users');
        Array.from(select.options).forEach(option => option.selected = false);
        descriptionQuill.setText('');
        taskDescriptionInput.value = '';
        taskModalErrors.classList.add('hidden');
        taskModalErrors.innerHTML = '';
    }

    function openTaskModal() {
        taskModal.classList.remove('hidden');
        taskModal.classList.add('flex');
    }

    function closeTaskModal() {
        taskModal.classList.add('hidden');
        taskModal.classList.remove('flex');
        resetTaskModal();
    }

    openTaskModalBtn?.addEventListener('click', openTaskModal);
    closeTaskModalBtn?.addEventListener('click', closeTaskModal);
    cancelTaskModalBtn?.addEventListener('click', closeTaskModal);
    taskModal?.addEventListener('click', function (event) {
        if (event.target === taskModal) {
            closeTaskModal();
        }
    });

    document.querySelectorAll('.edit-task-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const task = tasksData.find(function (item) {
                return String(item.id) === String(button.dataset.id);
            });

            if (! task) {
                return;
            }

            const form = document.querySelector('#taskModal form');
            form.action = '/tasks/' + task.id;
            form.querySelector('input[name="_method"]').value = 'PUT';
            document.querySelector('#taskModal h2').textContent = 'Edit Task';
            form.querySelector('#task_title').value = task.title || '';
            setQuillContent(descriptionQuill, task.description);
            taskDescriptionInput.value = task.description || '';
            form.querySelector('#task_sticky_note').value = task.sticky_note || '';
            form.querySelector('#task_status').value = task.status || 'not_started';
            form.querySelector('#task_start_date').value = task.start_date || '';
            form.querySelector('#task_due_date').value = task.due_date || '';
            form.querySelector('#task_priority').value = task.priority || 'medium';
            form.querySelector('#task_tags').value = (task.tags || []).join(', ');

            const selectedMembers = task.member_ids || [];
            Array.from(document.querySelectorAll('#assigned_users option')).forEach(function (option) {
                option.selected = selectedMembers.includes(Number(option.value));
            });

            openTaskModal();
        });
    });

    document.querySelector('#taskModal form')?.addEventListener('submit', function (event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = document.getElementById('taskModalSubmit');
        taskDescriptionInput.value = descriptionQuill.root.innerHTML;
        taskModalErrors.classList.add('hidden');
        taskModalErrors.innerHTML = '';
        submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: new FormData(form),
        })
            .then(function (response) {
                if (response.status === 422) {
                    return response.json().then(function (data) {
                        const errors = data.errors || {};
                        const messages = Object.keys(errors).length
                            ? Object.values(errors).flat()
                            : [data.message || 'Please check the form and try again.'];

                        taskModalErrors.innerHTML = '<ul class="list-disc pl-5">' + messages.map(function (message) {
                            return '<li>' + String(message).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</li>';
                        }).join('') + '</ul>';
                        taskModalErrors.classList.remove('hidden');
                    });
                }

                if (! response.ok) {
                    throw new Error('Unable to save the task. Please try again.');
                }

                window.location.reload();
            })
            .catch(function (error) {
                alert(error.message || 'Unable to save the task. Please try again.');
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
    });

    document.querySelectorAll('.copy-sticky-note').forEach(function (button) {
        button.addEventListener('click', async function () {
            const text = button.closest('[data-sticky-card]')?.querySelector('[data-sticky-text]')?.textContent?.trim() || '';
            if (! text) return;

            try {
                await navigator.clipboard.writeText(text);
                const oldText = button.textContent;
                button.textContent = 'Copied!';
                setTimeout(() => button.textContent = oldText, 1200);
            } catch (error) {
                button.textContent = 'Failed';
                setTimeout(() => button.textContent = 'Copy credentials', 1200);
            }
        });
    });
</script>
@endsection
