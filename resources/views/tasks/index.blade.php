@extends('layouts.app')

@section('title', 'Task Dashboard')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Team Workspace</p>
            <h1 class="text-2xl font-bold text-slate-900">{{ auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Member Dashboard' }}</h1>
        </div>

        <div class="flex items-center gap-3">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('lead-management.index') }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Lead Management</a>
                <a href="{{ route('admin.approvals') }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Approvals</a>
                <a href="{{ route('admin.payments') }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Financial Ledger</a>
                <a href="{{ route('admin.members') }}" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100">Members</a>
            @else
                <a href="{{ route('lead-management.index') }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Lead Management</a>
                <a href="{{ route('payments.member') }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">My Payments</a>
            @endif
        </div>
    </div>

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap gap-2">
            @php
                $statusTabs = [
                    'all' => 'All Tasks',
                    'not_started' => 'Not Started',
                    'in_progress' => 'In Progress',
                    'testing' => 'Testing',
                    'awaiting_feedback' => 'Awaiting Feedback',
                ];
            @endphp
            @foreach ($statusTabs as $key => $label)
                <a href="{{ route('tasks.index', ['status' => $key]) }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-medium transition {{ $selectedStatus === $key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full {{ $selectedStatus === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }} px-1.5 text-[10px] font-semibold">
                        {{ $taskCounts[$key] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>

        @if (auth()->user()->isAdmin())
            <button type="button" id="openTaskModal" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">+ New Task</button>
        @endif
    </div>

    <div class="mb-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Tasks</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $taskCounts['all'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">In Progress</p>
            <p class="mt-2 text-3xl font-bold text-sky-600">{{ $taskCounts['in_progress'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Completed</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $taskCounts['completed'] ?? 0 }}</p>
        </div>
    </div>

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
                                'not_started' => 'bg-slate-100 text-slate-700',
                                'in_progress' => 'bg-sky-100 text-sky-700',
                                'testing' => 'bg-violet-100 text-violet-700',
                                'awaiting_feedback' => 'bg-amber-100 text-amber-700',
                                'completed' => 'bg-emerald-100 text-emerald-700',
                            ];
                            $statusLabels = [
                                'not_started' => 'Not Started',
                                'in_progress' => 'In Progress',
                                'testing' => 'Testing',
                                'awaiting_feedback' => 'Awaiting Feedback',
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
                                    <p class="mt-1 max-w-md text-sm text-slate-600">{{ $task->description ?: 'No description provided.' }}</p>
                                    @if ($task->sticky_note)
                                        <div class="mt-3 rounded-xl border border-amber-300 bg-gradient-to-br from-yellow-200 via-yellow-100 to-amber-100 p-2.5 text-xs leading-5 text-amber-900 shadow-[0_8px_18px_rgba(217,119,6,0.12)]">
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
                                        <button type="button" class="edit-task-btn rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100" data-id="{{ $task->id }}" data-title="{{ $task->title }}" data-description="{{ $task->description ?? '' }}" data-sticky-note="{{ $task->sticky_note ?? '' }}" data-status="{{ $task->status }}" data-start-date="{{ $task->start_date ? $task->start_date->format('Y-m-d') : '' }}" data-due-date="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}" data-priority="{{ $task->priority ?? 'medium' }}" data-tags="{{ implode(', ', $taskTags) }}" data-member-ids="{{ ($hasTaskMembersTable ?? false) ? $task->members->pluck('id')->implode(',') : ($task->assigned_to ? $task->assigned_to : '') }}">
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
</div>

<div id="taskChatDrawer" class="fixed inset-y-0 right-0 z-50 hidden w-full max-w-xl flex-col border-l border-slate-200 bg-white shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-violet-600">Task discussion</p>
            <h3 id="taskChatTitle" class="text-lg font-bold text-slate-900">Task chat</h3>
        </div>
        <button type="button" id="closeTaskChat" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-600 hover:bg-slate-100">✕</button>
    </div>

    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <span id="taskChatStatus" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-700">Open</span>
        <span id="taskChatAssignee" class="text-xs font-medium text-slate-500">Unassigned</span>
    </div>

    <div id="taskChatMessages" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4"></div>

    <div class="border-t border-slate-200 bg-white p-4">
        <form id="taskChatForm" class="space-y-3">
            <input id="taskChatAttachment" type="file" class="hidden" />
            <div class="flex items-center gap-2">
                <button type="button" id="taskChatAttachmentBtn" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Attach</button>
                <span id="taskChatAttachmentName" class="text-xs text-slate-500">No file selected</span>
            </div>
            <div class="flex items-end gap-2">
                <textarea id="taskChatInput" rows="2" placeholder="Write a task update..." class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"></textarea>
                <button type="submit" id="taskChatSend" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Send</button>
            </div>
        </form>
    </div>
</div>

<div id="taskModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900">Create Task</h2>
            <button type="button" id="closeTaskModal" class="text-slate-500 hover:text-slate-700">✕</button>
        </div>

        <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="space-y-4">
                <div>
                    <label for="task_title" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="task_title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Task name">
                </div>

                <div>
                    <label for="task_description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="task_description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Task description"></textarea>
                </div>

                <div>
                    <label for="task_sticky_note" class="mb-1 block text-sm font-medium text-slate-700">Sticky note</label>
                    <textarea id="task_sticky_note" name="sticky_note" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Credentials, links, notes"></textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="task_status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select id="task_status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="not_started">Not Started</option>
                            <option value="in_progress">In Progress</option>
                            <option value="testing">Testing</option>
                            <option value="awaiting_feedback">Awaiting Feedback</option>
                            <option value="completed">Completed</option>
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

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" id="cancelTaskModal" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
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
    const taskChatAttachmentName = document.getElementById('taskChatAttachmentName');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let currentTaskChat = {
        id: null,
        sendUrl: null,
    };

    function formatStatusText(status) {
        const labels = {
            not_started: 'Not Started',
            in_progress: 'In Progress',
            testing: 'Testing',
            awaiting_feedback: 'Awaiting Feedback',
            completed: 'Completed',
        };

        return labels[status] || status || 'Open';
    }

    function renderTaskComment(comment) {
        const messageText = comment.message || 'Shared an attachment';
        const isOwner = Boolean(comment.is_owner);
        const author = comment.user?.name || 'User';
        const commentDate = comment.created_at ? new Date(comment.created_at).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Just now';
        const attachmentHtml = comment.attachment_url
            ? '<div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50"><a href="' + comment.attachment_url + '" target="_blank" rel="noopener" class="block text-xs font-medium text-indigo-700 hover:text-indigo-900">Open attachment</a></div>'
            : '';

        const wrapper = document.createElement('div');
        wrapper.className = 'flex ' + (isOwner ? 'justify-end' : 'justify-start');

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[85%] rounded-2xl border p-3 shadow-sm ' + (isOwner ? 'border-violet-200 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-700');
        bubble.innerHTML = '<div class="mb-1 flex items-center justify-between gap-3 text-[10px] font-semibold uppercase tracking-[0.2em] ' + (isOwner ? 'text-violet-100' : 'text-slate-500') + '"><span>' + author + '</span><span>' + commentDate + '</span></div><p class="whitespace-pre-wrap text-sm leading-6">' + messageText.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>' + attachmentHtml;
        wrapper.appendChild(bubble);
        taskChatMessages.appendChild(wrapper);
    }

    function scrollTaskChatToBottom() {
        taskChatMessages.scrollTop = taskChatMessages.scrollHeight;
    }

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

                if (! comments.length) {
                    taskChatMessages.innerHTML = '<div class="flex h-full items-center justify-center text-center text-sm text-slate-500">No comments yet. Start the conversation.</div>';
                    return;
                }

                comments.slice().reverse().forEach(function (comment) {
                    renderTaskComment(comment);
                });
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

        taskChatTitle.textContent = title;
        taskChatStatus.textContent = status;
        taskChatAssignee.textContent = 'Assigned to: ' + assignee;
        taskChatDrawer.classList.remove('hidden');
        taskChatDrawer.classList.add('flex');

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
        taskChatDrawer.classList.add('hidden');
        taskChatDrawer.classList.remove('flex');
    });

    taskChatAttachmentBtn?.addEventListener('click', function () {
        taskChatAttachmentInput.click();
    });

    taskChatAttachmentInput?.addEventListener('change', function () {
        const file = taskChatAttachmentInput.files && taskChatAttachmentInput.files[0];
        taskChatAttachmentName.textContent = file ? file.name : 'No file selected';
    });

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
                    renderTaskComment(comment);
                    scrollTaskChatToBottom();
                }
                taskChatInput.value = '';
                taskChatAttachmentInput.value = '';
                taskChatAttachmentName.textContent = 'No file selected';
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
            const form = document.querySelector('#taskModal form');
            form.action = '/tasks/' + button.dataset.id;
            form.querySelector('input[name="_method"]').value = 'PUT';
            document.querySelector('#taskModal h2').textContent = 'Edit Task';
            form.querySelector('#task_title').value = button.dataset.title || '';
            form.querySelector('#task_description').value = button.dataset.description || '';
            form.querySelector('#task_sticky_note').value = button.dataset.stickyNote || '';
            form.querySelector('#task_status').value = button.dataset.status || 'not_started';
            form.querySelector('#task_start_date').value = button.dataset.startDate || '';
            form.querySelector('#task_due_date').value = button.dataset.dueDate || '';
            form.querySelector('#task_priority').value = button.dataset.priority || 'medium';
            form.querySelector('#task_tags').value = button.dataset.tags || '';

            const selectedMembers = button.dataset.memberIds ? button.dataset.memberIds.split(',').map(Number) : [];
            Array.from(document.querySelectorAll('#assigned_users option')).forEach(function (option) {
                option.selected = selectedMembers.includes(Number(option.value));
            });

            openTaskModal();
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
