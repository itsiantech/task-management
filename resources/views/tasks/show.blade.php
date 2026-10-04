@extends('layouts.app')

@section('title', $task->title . ' | Task Details')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('tasks.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to tasks</a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Task Details</p>
                <h1 class="text-3xl font-bold text-slate-900">{{ $task->title }}</h1>
            </div>
        </div>

        @php
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
            $taskTags = is_array($task->tags) ? $task->tags : (json_decode($task->tags ?? '[]', true) ?: []);
        @endphp
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $statusClasses[$task->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabels[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status)) }}</span>
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $priorityClasses[$task->priority] ?? 'bg-slate-100 text-slate-700' }}">{{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}</span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <aside class="space-y-5 lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Overview</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Assigned to</dt>
                        <dd class="font-medium text-slate-800">{{ $task->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Created by</dt>
                        <dd class="font-medium text-slate-800">{{ $task->creator?->name ?? 'Unknown' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Start date</dt>
                        <dd class="font-medium text-slate-800">{{ $task->start_date ? $task->start_date->format('M d, Y') : 'No start date' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Due date</dt>
                        <dd class="font-medium text-slate-800">{{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold text-slate-900">Tags</h2>
                <div class="flex flex-wrap gap-2">
                    @forelse ($taskTags as $tag)
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $tag }}</span>
                    @empty
                        <span class="text-sm text-slate-500">No tags</span>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold text-slate-900">Description</h2>
                <p class="text-sm leading-6 text-slate-600">{{ $task->description ?: 'No description provided yet.' }}</p>
            </div>

            @if ($task->sticky_note)
                <div class="sticky-note rounded-2xl border border-amber-300 bg-gradient-to-br from-yellow-200 via-yellow-100 to-amber-100 p-5 shadow-[0_10px_24px_rgba(217,119,6,0.15)] rotate-[-0.75deg]">
                    <h2 class="mb-2 text-lg font-semibold text-amber-900">Sticky Note</h2>
                    <p class="whitespace-pre-wrap text-sm leading-6 text-amber-900">{{ $task->sticky_note }}</p>
                </div>
            @endif

            @if ($task->attachment_path)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-lg font-semibold text-slate-900">Task Attachment</h2>
                    <a href="{{ route('tasks.download', $task) }}" class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Download attachment</a>
                </div>
            @endif
        </aside>

        <section class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-xl font-semibold text-slate-900">Comments</h2>
                </div>
                <div class="p-5 text-sm text-slate-600">Task feedback and notes can continue from the dashboard conversation flow.</div>
            </div>
        </section>
    </div>
</div>
@endsection
