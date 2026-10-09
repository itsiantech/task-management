@extends('layouts.app')

@section('title', $task->title . ' | Task Details')

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
    }
    .ql-editor {
        min-height: 12rem;
    }
    .correction-quill .ql-editor {
        min-height: 7rem;
    }
    .ql-editor.ql-blank::before {
        color: #94a3b8;
    }
</style>
@endpush

@section('content')
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
    $pendingCount = $task->corrections->where('status', 'pending')->count();
    $doneCount = $task->corrections->where('status', 'done')->count();
    $editorData = [
        'description' => $task->description ?? '',
        'description_url' => route('tasks.description.update', $task),
        'store_url' => route('task.corrections.store', $task),
        'corrections' => $task->corrections->map(function ($correction) use ($task) {
            return [
                'id' => $correction->id,
                'date' => $correction->correction_date ? $correction->correction_date->format('Y-m-d') : '',
                'date_label' => $correction->correction_date ? $correction->correction_date->format('M d, Y') : 'No date',
                'author' => $correction->user?->name ?? 'User',
                'content' => $correction->content ?? '',
                'status' => $correction->status,
                'is_owner' => auth()->user()->isAdmin() || $correction->user_id === auth()->id(),
                'created_at' => $correction->created_at ? $correction->created_at->diffForHumans() : '',
                'update_url' => route('task.corrections.update', [$task, $correction]),
                'status_url' => route('task.corrections.status', [$task, $correction]),
                'delete_url' => route('task.corrections.destroy', [$task, $correction]),
            ];
        })->values(),
    ];
@endphp
<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('tasks.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to tasks</a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Task Details</p>
                <h1 class="text-3xl font-bold text-slate-900">{{ $task->title }}</h1>
            </div>
        </div>

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

        <section class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Description</h2>
                    <span id="descriptionAutoStatus" class="text-xs font-medium text-slate-400">All changes saved</span>
                </div>
                <div id="descriptionEditor"></div>
                <div class="sticky bottom-4 z-10 mt-3 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/95 px-4 py-3 shadow-md backdrop-blur">
                    <span id="descriptionSaveStatus" class="text-xs font-medium text-slate-500">Tip: typing auto-saves after a short pause.</span>
                    <button type="button" id="descriptionSaveBtn" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">Save</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-slate-900">Corrections</h2>
                        <span id="correctionPendingChip" class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending: {{ $pendingCount }}</span>
                        <span id="correctionDoneChip" class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Done: {{ $doneCount }}</span>
                    </div>
                    <button type="button" id="showCorrectionFormBtn" class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">+ Add correction</button>
                </div>

                <div id="correctionFormWrap" class="mb-5 hidden rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                    <div class="mb-3 flex flex-wrap items-center gap-3">
                        <label class="text-sm font-medium text-slate-700" for="newCorrectionDate">Correction date</label>
                        <input type="date" id="newCorrectionDate" value="{{ now()->format('Y-m-d') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div id="newCorrectionEditor"></div>
                    <div class="mt-3 flex items-center justify-end gap-3">
                        <span id="newCorrectionStatus" class="text-xs font-medium text-slate-500"></span>
                        <button type="button" id="cancelCorrectionBtn" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" id="saveCorrectionBtn" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Submit correction</button>
                    </div>
                </div>

                <div id="correctionsList" class="space-y-4">
                    @forelse ($task->corrections as $correction)
                        <div class="correction-card rounded-xl border border-slate-200 bg-slate-50/60 p-4" data-correction-id="{{ $correction->id }}">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700" data-role="date">{{ $correction->correction_date ? $correction->correction_date->format('M d, Y') : 'No date' }}</span>
                                    <span class="text-xs font-medium text-slate-500">{{ $correction->user?->name ?? 'User' }} &middot; {{ $correction->created_at ? $correction->created_at->diffForHumans() : '' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="correction-status-btn rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $correction->status === 'done' ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' }}" data-role="status">
                                        {{ $correction->status === 'done' ? 'Done' : 'Pending' }}
                                    </button>
                                    <button type="button" class="correction-delete-btn rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100" data-role="delete">Delete</button>
                                </div>
                            </div>
                            <div class="correction-quill"></div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs font-medium text-slate-400" data-role="save-state">Saved</span>
                                <span class="text-[11px] text-slate-400">Auto-saves while typing</span>
                            </div>
                        </div>
                    @empty
                        <p id="noCorrectionsMsg" class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No corrections yet. Members can submit date-wise corrections above.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-xl font-semibold text-slate-900">Comments</h2>
                </div>
                <div class="p-5 text-sm text-slate-600">Task feedback and notes can continue from the dashboard conversation flow.</div>
            </div>
        </section>
    </div>
</div>

<script type="application/json" id="taskEditorData">
{!! json_encode($editorData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}
</script>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
    (function () {
        const editorData = JSON.parse(document.getElementById('taskEditorData').textContent);
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

        function jsonFetch(url, method, payload) {
            return fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload || {}),
            });
        }

        // ---- Description editor (unlimited text + auto-save + sticky save) ----
        const descriptionQuill = new Quill('#descriptionEditor', {
            theme: 'snow',
            placeholder: 'Write the full task description here...',
            modules: { toolbar: QUILL_TOOLBAR },
        });
        setQuillContent(descriptionQuill, editorData.description);

        const descriptionAutoStatus = document.getElementById('descriptionAutoStatus');
        const descriptionSaveStatus = document.getElementById('descriptionSaveStatus');
        const descriptionSaveBtn = document.getElementById('descriptionSaveBtn');
        let descriptionTimer = null;

        function saveDescription() {
            descriptionAutoStatus.textContent = 'Saving...';
            descriptionAutoStatus.className = 'text-xs font-medium text-indigo-500';

            return jsonFetch(editorData.description_url, 'PATCH', {
                description: descriptionQuill.root.innerHTML,
            }).then(function (response) {
                if (! response.ok) {
                    throw new Error('Save failed');
                }
                return response.json();
            }).then(function () {
                const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                descriptionAutoStatus.textContent = 'All changes saved';
                descriptionAutoStatus.className = 'text-xs font-medium text-emerald-500';
                descriptionSaveStatus.textContent = 'Last saved ' + time;
            }).catch(function () {
                descriptionAutoStatus.textContent = 'Save failed';
                descriptionAutoStatus.className = 'text-xs font-medium text-rose-500';
                descriptionSaveStatus.textContent = 'Could not save. Check your connection, then press Save.';
            });
        }

        function queueDescriptionSave() {
            descriptionAutoStatus.textContent = 'Unsaved changes';
            descriptionAutoStatus.className = 'text-xs font-medium text-amber-500';
            clearTimeout(descriptionTimer);
            descriptionTimer = setTimeout(saveDescription, 1200);
        }

        descriptionQuill.on('text-change', queueDescriptionSave);
        descriptionSaveBtn.addEventListener('click', function () {
            clearTimeout(descriptionTimer);
            saveDescription();
        });

        // ---- Corrections ----
        const correctionsById = {};
        editorData.corrections.forEach(function (correction) {
            correctionsById[correction.id] = correction;
        });

        function refreshSummaryCounts() {
            const cards = document.querySelectorAll('.correction-card');
            let pending = 0;
            let done = 0;
            cards.forEach(function (card) {
                const btn = card.querySelector('[data-role="status"]');
                if (btn && btn.textContent.trim() === 'Done') {
                    done += 1;
                } else {
                    pending += 1;
                }
            });
            const pendingChip = document.getElementById('correctionPendingChip');
            const doneChip = document.getElementById('correctionDoneChip');
            if (pendingChip) {
                pendingChip.textContent = 'Pending: ' + pending;
            }
            if (doneChip) {
                doneChip.textContent = 'Done: ' + done;
            }
        }

        function wireCorrectionCard(card, correction) {
            const editorEl = card.querySelector('.correction-quill');
            const quill = new Quill(editorEl, {
                theme: 'snow',
                placeholder: 'Write the correction note here...',
                modules: correction.is_owner ? { toolbar: QUILL_TOOLBAR } : { toolbar: false },
            });
            quill.enable(correction.is_owner);
            setQuillContent(quill, correction.content);

            const saveState = card.querySelector('[data-role="save-state"]');
            const statusBtn = card.querySelector('[data-role="status"]');
            const deleteBtn = card.querySelector('[data-role="delete"]');
            let timer = null;

            function persist() {
                saveState.textContent = 'Saving...';
                saveState.className = 'text-xs font-medium text-indigo-500';
                jsonFetch(correction.update_url, 'PUT', {
                    content: quill.root.innerHTML,
                    correction_date: correction.date,
                }).then(function (response) {
                    if (! response.ok) {
                        throw new Error('Save failed');
                    }
                    saveState.textContent = 'Saved';
                    saveState.className = 'text-xs font-medium text-emerald-500';
                }).catch(function () {
                    saveState.textContent = 'Save failed';
                    saveState.className = 'text-xs font-medium text-rose-500';
                });
            }

            if (correction.is_owner) {
                quill.on('text-change', function () {
                    saveState.textContent = 'Unsaved changes';
                    saveState.className = 'text-xs font-medium text-amber-500';
                    clearTimeout(timer);
                    timer = setTimeout(persist, 1200);
                });
            }

            statusBtn.addEventListener('click', function () {
                statusBtn.disabled = true;
                jsonFetch(correction.status_url, 'PATCH', {}).then(function (response) {
                    return response.json().then(function (data) {
                        if (! response.ok) {
                            throw new Error(data.message || 'Unable to update status.');
                        }
                        const isDone = data.status === 'done';
                        statusBtn.textContent = isDone ? 'Done' : 'Pending';
                        statusBtn.className = 'correction-status-btn rounded-full px-3 py-1.5 text-xs font-semibold transition ' + (isDone ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200');
                        refreshSummaryCounts();
                    });
                }).catch(function (error) {
                    alert(error.message || 'Unable to update status.');
                }).finally(function () {
                    statusBtn.disabled = false;
                });
            });

            deleteBtn.addEventListener('click', function () {
                if (! confirm('Delete this correction?')) {
                    return;
                }

                jsonFetch(correction.delete_url, 'DELETE', {}).then(function (response) {
                    if (! response.ok) {
                        throw new Error('Delete failed');
                    }
                    card.remove();
                    refreshSummaryCounts();
                    if (! document.querySelector('.correction-card')) {
                        const empty = document.createElement('p');
                        empty.id = 'noCorrectionsMsg';
                        empty.className = 'rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500';
                        empty.textContent = 'No corrections yet. Members can submit date-wise corrections above.';
                        document.getElementById('correctionsList').appendChild(empty);
                    }
                }).catch(function () {
                    alert('Unable to delete this correction.');
                });
            });
        }

        document.querySelectorAll('.correction-card').forEach(function (card) {
            const id = Number(card.dataset.correctionId);
            if (correctionsById[id]) {
                wireCorrectionCard(card, correctionsById[id]);
            }
        });

        // ---- New correction form ----
        const correctionFormWrap = document.getElementById('correctionFormWrap');
        const showCorrectionFormBtn = document.getElementById('showCorrectionFormBtn');
        const cancelCorrectionBtn = document.getElementById('cancelCorrectionBtn');
        const saveCorrectionBtn = document.getElementById('saveCorrectionBtn');
        const newCorrectionStatus = document.getElementById('newCorrectionStatus');
        let newCorrectionQuill = null;

        function toggleCorrectionForm(show) {
            correctionFormWrap.classList.toggle('hidden', ! show);
            showCorrectionFormBtn.classList.toggle('hidden', show);

            if (show && ! newCorrectionQuill) {
                newCorrectionQuill = new Quill('#newCorrectionEditor', {
                    theme: 'snow',
                    placeholder: 'Write the correction note here...',
                    modules: { toolbar: QUILL_TOOLBAR },
                });
            }
        }

        showCorrectionFormBtn.addEventListener('click', function () {
            toggleCorrectionForm(true);
        });

        cancelCorrectionBtn.addEventListener('click', function () {
            toggleCorrectionForm(false);
        });

        saveCorrectionBtn.addEventListener('click', function () {
            const date = document.getElementById('newCorrectionDate').value;
            if (! date) {
                newCorrectionStatus.textContent = 'Please pick a correction date.';
                newCorrectionStatus.className = 'text-xs font-medium text-rose-500';
                return;
            }

            const html = newCorrectionQuill ? newCorrectionQuill.root.innerHTML : '';
            const isEmpty = newCorrectionQuill ? newCorrectionQuill.getText().trim() === '' : true;
            if (isEmpty) {
                newCorrectionStatus.textContent = 'Please write the correction note.';
                newCorrectionStatus.className = 'text-xs font-medium text-rose-500';
                return;
            }

            saveCorrectionBtn.disabled = true;
            newCorrectionStatus.textContent = 'Submitting...';
            newCorrectionStatus.className = 'text-xs font-medium text-indigo-500';

            jsonFetch(editorData.store_url, 'POST', {
                correction_date: date,
                content: html,
            }).then(function (response) {
                return response.json().then(function (data) {
                    if (! response.ok) {
                        throw new Error(data.message || 'Unable to submit correction.');
                    }
                    window.location.reload();
                });
            }).catch(function (error) {
                newCorrectionStatus.textContent = error.message || 'Unable to submit correction.';
                newCorrectionStatus.className = 'text-xs font-medium text-rose-500';
                saveCorrectionBtn.disabled = false;
            });
        });

        refreshSummaryCounts();
    })();
</script>
@endsection
