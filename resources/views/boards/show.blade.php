@extends('layouts.app')

@section('title', $board->name)

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<style>
    #boardNotesEditor .ql-editor { min-height: 6rem; max-height: 55vh; overflow-y: auto; }
    #editBoardDescriptionEditor .ql-editor { min-height: 6rem; max-height: 35vh; overflow-y: auto; }
</style>
@endpush

@section('content')
@php
    $boardData = [
        'id' => $board->id,
        'canEdit' => $canEdit,
        'columns' => $board->columns->map(fn ($col) => [
            'id' => $col->id,
            'title' => $col->title,
            'cards' => $col->cards->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'has_credentials' => $c->hasCredentials(),
                'has_description' => filled($c->description),
            ])->values(),
        ])->values(),
    ];
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200';
@endphp

<style>
    .kb-ghost { opacity: .35; background: #e0e7ff !important; border: 1px dashed #6366f1 !important; }
    .kb-chosen { box-shadow: 0 10px 25px rgba(15, 23, 42, .18); }
    .kb-drag { transform: rotate(2deg); }
    .kb-card { transition: box-shadow .15s ease, transform .15s ease; }
    .kb-cards { min-height: 2.5rem; }
    .kb-title-input:read-only { cursor: default; }
</style>

<div class="mx-auto max-w-full">
    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <a href="{{ route('boards.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">&larr; All boards</a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="truncate text-2xl font-bold text-slate-900">{{ $board->name }}</h1>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $board->is_private ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $board->is_private ? 'Private' : 'Team' }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ $role }}</span>
            </div>
            @if (trim(strip_tags((string) $board->description)) !== '')
                <p class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $board->description)), 180) }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="mr-2 flex -space-x-2">
                @foreach ($board->memberships->take(5) as $m)
                    <span title="{{ $m->user?->name }} ({{ $m->role }})" class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-slate-700 text-xs font-semibold text-white">{{ strtoupper(substr((string) $m->user?->name, 0, 1)) }}</span>
                @endforeach
                @if ($board->memberships->count() > 5)
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-slate-200 text-xs font-semibold text-slate-600">+{{ $board->memberships->count() - 5 }}</span>
                @endif
            </div>
            @if ($canManage)
                <button type="button" id="openShare" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Share</button>
                <button type="button" id="openEditBoard" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</button>
                <form action="{{ route('boards.destroy', $board) }}" method="POST" onsubmit="return confirm('Delete this board with all its lists and cards?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Board notes: rich text, auto-saved, scrollable --}}
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Board notes</h2>
            <span id="notesSaveStatus" class="text-xs text-slate-400"></span>
        </div>
        <div id="boardNotesEditor"></div>
    </div>

    {{-- Board --}}
    <div class="flex items-start gap-4 overflow-x-auto pb-6">
        <div id="columns" class="flex items-start gap-4"></div>

        @if ($canEdit)
            <form id="addColumnForm" class="w-72 shrink-0 rounded-xl border border-dashed border-slate-300 bg-white/60 p-3">
                <input id="newColumnTitle" type="text" maxlength="255" placeholder="+ Add another list" class="{{ $inputClass }}">
                <button type="submit" class="mt-2 w-full rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add list</button>
            </form>
        @endif
    </div>
</div>

{{-- Card details modal --}}
<div id="cardModal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
    <div class="my-8 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="flex-1">
                <label for="cardTitle" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Title</label>
                <input id="cardTitle" type="text" maxlength="255" class="{{ $inputClass }} text-base font-semibold">
            </div>
            <button type="button" data-close-card class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>

        <div class="mb-5">
            <label for="cardDescription" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Description / notes <span class="font-normal normal-case text-slate-400">(plain text or Markdown)</span></label>
            <textarea id="cardDescription" rows="6" maxlength="20000" class="{{ $inputClass }} font-mono"></textarea>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
            <div class="mb-3 flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>
                <h3 class="text-sm font-semibold text-amber-900">Secret Credentials</h3>
                <span class="text-xs text-amber-700">Encrypted at rest &middot; visible to board members</span>
            </div>

            <div class="space-y-3">
                <div>
                    <label for="credUsername" class="mb-1 block text-xs font-medium text-slate-600">Username / Email</label>
                    <div class="flex gap-2">
                        <input id="credUsername" type="text" autocomplete="off" maxlength="500" class="{{ $inputClass }} bg-white">
                        <button type="button" data-copy="credUsername" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">Copy Username</button>
                    </div>
                </div>

                <div>
                    <label for="credPassword" class="mb-1 block text-xs font-medium text-slate-600">Password</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input id="credPassword" type="password" autocomplete="new-password" maxlength="1000" class="{{ $inputClass }} bg-white pr-10">
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-800" aria-label="Show or hide password" title="Show / hide">
                                <svg id="eyeOpen" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeClosed" viewBox="0 0 24 24" class="hidden h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18M10.6 6.1A9.8 9.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.2 3.9M6.6 6.7C3.8 8.4 2 12 2 12s3.5 7 10 7c1.7 0 3.2-.4 4.5-1" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <button type="button" data-copy="credPassword" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">Copy Password</button>
                    </div>
                </div>

                <div>
                    <label for="credUrl" class="mb-1 block text-xs font-medium text-slate-600">Login URL</label>
                    <div class="flex gap-2">
                        <input id="credUrl" type="text" autocomplete="off" maxlength="2048" placeholder="https://" class="{{ $inputClass }} bg-white">
                        <button type="button" id="openLink" class="shrink-0 rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Open Link</button>
                    </div>
                </div>
            </div>
        </div>

        <p id="cardError" class="mt-3 hidden text-sm text-rose-600"></p>

        <div class="mt-6 flex items-center justify-between">
            <div>
                @if ($canEdit)
                    <button type="button" id="deleteCard" class="rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50">Delete card</button>
                @endif
            </div>
            <div class="flex gap-2">
                <button type="button" data-close-card class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ $canEdit ? 'Cancel' : 'Close' }}</button>
                @if ($canEdit)
                    <button type="button" id="saveCard" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($canManage)
    {{-- Share modal --}}
    <div id="shareModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Share board</h2>
                <button type="button" data-close-modal class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
            </div>
            <p class="mb-4 text-sm text-slate-500">Select team members and choose what they can do. Editors can change lists and cards; viewers can only look and copy credentials.</p>

            <form action="{{ route('boards.share', $board) }}" method="POST">
                @csrf
                <input type="search" id="shareSearch" placeholder="Search team members..." class="{{ $inputClass }} mb-3">
                <div id="shareList" class="max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200">
                    @forelse ($teamMembers as $member)
                        @php $current = $sharedRoles[$member->id] ?? null; @endphp
                        <label data-name="{{ strtolower($member->name.' '.$member->email) }}" class="flex cursor-pointer items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
                            <input type="checkbox" name="user_ids[]" value="{{ $member->id }}" class="share-check rounded border-slate-300" @checked($current !== null)>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-slate-800">{{ $member->name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $member->email }}</span>
                            </span>
                            <select name="roles[{{ $member->id }}]" class="share-role rounded-lg border border-slate-300 px-2 py-1 text-xs" @disabled($current === null)>
                                <option value="viewer" @selected(($current ?? 'viewer') === 'viewer')>Viewer</option>
                                <option value="editor" @selected($current === 'editor')>Editor</option>
                            </select>
                        </label>
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-slate-500">No other approved team members available.</p>
                    @endforelse
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save sharing</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit board modal --}}
    <div id="editBoardModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Edit board</h2>
                <button type="button" data-close-modal class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
            </div>
            <form action="{{ route('boards.update', $board) }}" method="POST" class="space-y-4" id="editBoardForm">
                @csrf @method('PUT')
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Board title</label>
                    <input name="name" type="text" required maxlength="255" value="{{ $board->name }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Description <span class="font-normal text-slate-400">(rich text, unlimited)</span></label>
                    <div id="editBoardDescriptionEditor"></div>
                    <input type="hidden" name="description" id="editBoardDescriptionInput" value="{{ $board->description }}">
                </div>
                <label class="flex items-start gap-2 text-sm text-slate-600">
                    <input type="hidden" name="is_private" value="0">
                    <input type="checkbox" name="is_private" value="1" class="mt-0.5 rounded border-slate-300" @checked($board->is_private)>
                    <span><span class="font-medium text-slate-800">Private board</span><br>Uncheck to let every approved team member open it as a viewer.</span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div id="toast" class="pointer-events-none fixed bottom-6 right-6 z-[60] hidden rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"></div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const BOARD = @json($boardData);
    const BASE = @json(url('/boards/'.$board->id));
    const POSITION_URL = @json(route('kanban.update-position'));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const canEdit = BOARD.canEdit;

    const $ = (id) => document.getElementById(id);
    const columnsEl = $('columns');

    /* ---------- helpers ---------- */
    async function api(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        let data = {};
        try { data = await res.json(); } catch (e) {}
        if (!res.ok) {
            const msg = (data.errors && Object.values(data.errors).flat()[0]) || data.message || ('Request failed (' + res.status + ')');
            throw new Error(msg);
        }
        return data;
    }

    let toastTimer;
    function toast(msg) {
        const t = $('toast');
        t.textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 1800);
    }

    function el(tag, className, text) {
        const e = document.createElement(tag);
        if (className) e.className = className;
        if (text !== undefined) e.textContent = text;
        return e;
    }

    /* ---------- rendering ---------- */
    function renderCard(card) {
        const div = el('div', 'kb-card cursor-pointer rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-sm hover:border-indigo-300 hover:shadow');
        div.dataset.id = card.id;
        div.appendChild(el('p', 'kb-card-title break-words font-medium text-slate-800', card.title));
        const badges = el('div', 'kb-card-badges mt-2 flex items-center gap-2 text-slate-400 empty:hidden');
        div.appendChild(badges);
        paintBadges(div, card);
        div.addEventListener('click', () => openCard(card.id));
        return div;
    }

    function paintBadges(div, card) {
        const badges = div.querySelector('.kb-card-badges');
        badges.innerHTML = '';
        if (card.has_description) {
            const b = el('span', 'inline-flex items-center gap-1 text-xs');
            b.title = 'Has description';
            b.innerHTML = '<svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/></svg>';
            badges.appendChild(b);
        }
        if (card.has_credentials) {
            const b = el('span', 'inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700');
            b.title = 'Has credentials';
            b.innerHTML = '<svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>Secret';
            badges.appendChild(b);
        }
    }

    function renderColumn(col) {
        const wrap = el('div', 'kb-column w-72 shrink-0 rounded-xl border border-slate-200 bg-slate-100/80 p-3');
        wrap.dataset.id = col.id;

        const head = el('div', 'mb-3 flex items-center gap-2');
        if (canEdit) {
            const handle = el('span', 'kb-col-handle cursor-grab select-none text-slate-400 hover:text-slate-600');
            handle.title = 'Drag to reorder list';
            handle.innerHTML = '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>';
            head.appendChild(handle);
        }
        const title = el('input', 'kb-title-input min-w-0 flex-1 rounded bg-transparent px-1 py-0.5 text-sm font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200');
        title.value = col.title;
        title.maxLength = 255;
        title.readOnly = !canEdit;
        let original = col.title;
        title.addEventListener('keydown', (e) => { if (e.key === 'Enter') title.blur(); if (e.key === 'Escape') { title.value = original; title.blur(); } });
        title.addEventListener('blur', async () => {
            const val = title.value.trim();
            if (!val) { title.value = original; return; }
            if (val === original) return;
            try { await api('PUT', `${BASE}/columns/${col.id}`, { title: val }); original = val; toast('List renamed'); }
            catch (err) { title.value = original; toast(err.message); }
        });
        head.appendChild(title);

        const count = el('span', 'kb-count rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-600', '0');
        head.appendChild(count);

        if (canEdit) {
            const del = el('button', 'text-slate-400 hover:text-rose-600', '\u00d7');
            del.type = 'button';
            del.title = 'Delete list';
            del.className += ' text-lg leading-none';
            del.addEventListener('click', async () => {
                if (!confirm('Delete this list and all its cards?')) return;
                try { await api('DELETE', `${BASE}/columns/${col.id}`); wrap.remove(); toast('List deleted'); }
                catch (err) { toast(err.message); }
            });
            head.appendChild(del);
        }
        wrap.appendChild(head);

        const list = el('div', 'kb-cards space-y-2');
        list.dataset.columnId = col.id;
        (col.cards || []).forEach((c) => list.appendChild(renderCard(c)));
        wrap.appendChild(list);

        if (canEdit) {
            const form = el('form', 'mt-3');
            const input = el('input', 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200');
            input.placeholder = '+ Add a card';
            input.maxLength = 255;
            form.appendChild(input);
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const val = input.value.trim();
                if (!val) return;
                input.disabled = true;
                try {
                    const { card } = await api('POST', `${BASE}/columns/${col.id}/cards`, { title: val });
                    list.appendChild(renderCard(card));
                    input.value = '';
                    updateCounts();
                } catch (err) { toast(err.message); }
                input.disabled = false;
                input.focus();
            });
            wrap.appendChild(form);
        }

        if (canEdit) initCardSortable(list);
        return wrap;
    }

    function updateCounts() {
        columnsEl.querySelectorAll('.kb-column').forEach((c) => {
            c.querySelector('.kb-count').textContent = c.querySelectorAll('.kb-cards > .kb-card').length;
        });
    }

    /* ---------- drag & drop ---------- */
    let saveChain = Promise.resolve();
    function savePositions(payload) {
        // Serialise requests so rapid drags are stored in the order they happened.
        saveChain = saveChain
            .then(() => api('POST', POSITION_URL, Object.assign({ board_id: BOARD.id }, payload)))
            .catch((err) => { toast('Could not save: ' + err.message); setTimeout(() => location.reload(), 1200); });
    }

    function columnPayload(columnEl) {
        return {
            id: Number(columnEl.dataset.id),
            cards: Array.from(columnEl.querySelectorAll('.kb-cards > .kb-card')).map((c) => Number(c.dataset.id)),
        };
    }

    function initCardSortable(list) {
        Sortable.create(list, {
            group: 'cards',
            animation: 180,
            easing: 'cubic-bezier(.2, .8, .2, 1)',
            ghostClass: 'kb-ghost',
            chosenClass: 'kb-chosen',
            dragClass: 'kb-drag',
            delay: 120,
            delayOnTouchOnly: true,
            touchStartThreshold: 4,
            emptyInsertThreshold: 24,
            onEnd(evt) {
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;
                const affected = [evt.to.closest('.kb-column')];
                if (evt.from !== evt.to) affected.push(evt.from.closest('.kb-column'));
                updateCounts();
                savePositions({ columns: affected.map(columnPayload) });
            },
        });
    }

    if (canEdit) {
        Sortable.create(columnsEl, {
            animation: 180,
            handle: '.kb-col-handle',
            draggable: '.kb-column',
            ghostClass: 'kb-ghost',
            chosenClass: 'kb-chosen',
            onEnd(evt) {
                if (evt.oldIndex === evt.newIndex) return;
                savePositions({
                    column_order: Array.from(columnsEl.querySelectorAll('.kb-column')).map((c) => Number(c.dataset.id)),
                });
            },
        });
    }

    BOARD.columns.forEach((col) => columnsEl.appendChild(renderColumn(col)));
    updateCounts();

    $('addColumnForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = $('newColumnTitle');
        const val = input.value.trim();
        if (!val) return;
        try {
            const { column } = await api('POST', `${BASE}/columns`, { title: val });
            columnsEl.appendChild(renderColumn({ id: column.id, title: column.title, cards: [] }));
            input.value = '';
            updateCounts();
        } catch (err) { toast(err.message); }
    });

    /* ---------- board notes rich editor (auto-save) ---------- */
    const QUILL_TOOLBAR = [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'code-block', 'link'],
        ['clean'],
    ];

    function setQuillContent(quill, content) {
        const value = (content || '').toString();
        if (/<[a-z][\s\S]*>/i.test(value)) quill.clipboard.dangerouslyPasteHTML(value);
        else quill.setText(value);
    }

    const NOTES_TOOLBAR = canEdit ? QUILL_TOOLBAR : false;
    const notesQuill = new Quill('#boardNotesEditor', {
        theme: 'snow',
        readOnly: !canEdit,
        modules: { toolbar: NOTES_TOOLBAR },
    });
    setQuillContent(notesQuill, @json((string) $board->description));

    const notesStatus = $('notesSaveStatus');
    function setNotesStatus(text) { notesStatus.textContent = text; }

    if (canEdit) {
        let notesTimer = null;
        notesQuill.on('text-change', () => {
            setNotesStatus('Typing...');
            clearTimeout(notesTimer);
            notesTimer = setTimeout(saveNotes, 1200);
        });
    }

    async function saveNotes() {
        setNotesStatus('Saving...');
        const html = notesQuill.getText().trim() === '' ? '' : notesQuill.root.innerHTML;
        try {
            await api('PATCH', `${BASE}/description`, { description: html });
            setNotesStatus('All changes saved');
        } catch (err) {
            setNotesStatus('Save failed \u2014 ' + err.message);
        }
    }

    /* ---------- edit board modal (rich description, owner only) ---------- */
    if ($('editBoardDescriptionEditor')) {
        const editDescQuill = new Quill('#editBoardDescriptionEditor', { theme: 'snow', modules: { toolbar: QUILL_TOOLBAR } });
        const editDescInput = $('editBoardDescriptionInput');
        setQuillContent(editDescQuill, editDescInput.value);
        $('editBoardForm').addEventListener('submit', () => {
            editDescInput.value = editDescQuill.getText().trim() === '' ? '' : editDescQuill.root.innerHTML;
        });
    }

    /* ---------- card modal ---------- */
    const cardModal = $('cardModal');
    const fields = { title: $('cardTitle'), description: $('cardDescription'), username: $('credUsername'), password: $('credPassword'), url: $('credUrl') };
    let currentCardId = null;

    function setPasswordVisible(visible) {
        fields.password.type = visible ? 'text' : 'password';
        $('eyeOpen').classList.toggle('hidden', visible);
        $('eyeClosed').classList.toggle('hidden', !visible);
    }

    function showCardError(msg) {
        const e = $('cardError');
        e.textContent = msg || '';
        e.classList.toggle('hidden', !msg);
    }

    async function openCard(id) {
        try {
            const { card } = await api('GET', `${BASE}/cards/${id}`);
            currentCardId = id;
            fields.title.value = card.title;
            fields.description.value = card.description;
            fields.username.value = card.credentials.username;
            fields.password.value = card.credentials.password;
            fields.url.value = card.credentials.url;
            Object.values(fields).forEach((f) => { f.readOnly = !canEdit; });
            setPasswordVisible(false);
            showCardError('');
            cardModal.classList.remove('hidden');
            cardModal.classList.add('flex');
        } catch (err) { toast(err.message); }
    }

    function closeCard() {
        cardModal.classList.add('hidden');
        cardModal.classList.remove('flex');
        fields.password.value = '';
        currentCardId = null;
    }

    async function copyText(text) {
        if (!text) { toast('Nothing to copy'); return; }
        try {
            await navigator.clipboard.writeText(text);
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            document.execCommand('copy');
            ta.remove();
        }
        toast('Copied to clipboard');
    }

    cardModal.querySelectorAll('[data-close-card]').forEach((b) => b.addEventListener('click', closeCard));
    cardModal.addEventListener('mousedown', (e) => { if (e.target === cardModal) closeCard(); });
    cardModal.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => copyText($(b.dataset.copy).value)));
    $('togglePassword').addEventListener('click', () => setPasswordVisible(fields.password.type === 'password'));

    $('openLink').addEventListener('click', () => {
        let url = fields.url.value.trim();
        if (!url) { toast('No link saved'); return; }
        if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
        window.open(url, '_blank', 'noopener,noreferrer');
    });

    $('saveCard')?.addEventListener('click', async () => {
        const title = fields.title.value.trim();
        if (!title) { showCardError('Title is required.'); return; }
        const btn = $('saveCard');
        btn.disabled = true;
        try {
            const { card } = await api('PUT', `${BASE}/cards/${currentCardId}`, {
                title,
                description: fields.description.value,
                credentials: { username: fields.username.value, password: fields.password.value, url: fields.url.value },
            });
            const node = columnsEl.querySelector(`.kb-card[data-id="${card.id}"]`);
            if (node) {
                node.querySelector('.kb-card-title').textContent = card.title;
                paintBadges(node, card);
            }
            closeCard();
            toast('Card saved');
        } catch (err) { showCardError(err.message); }
        btn.disabled = false;
    });

    $('deleteCard')?.addEventListener('click', async () => {
        if (!confirm('Delete this card?')) return;
        try {
            await api('DELETE', `${BASE}/cards/${currentCardId}`);
            columnsEl.querySelector(`.kb-card[data-id="${currentCardId}"]`)?.remove();
            closeCard();
            updateCounts();
            toast('Card deleted');
        } catch (err) { showCardError(err.message); }
    });

    /* ---------- share / edit board modals ---------- */
    function bindModal(modalId, openId) {
        const modal = $(modalId);
        if (!modal) return;
        const open = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        $(openId)?.addEventListener('click', open);
        modal.querySelectorAll('[data-close-modal]').forEach((b) => b.addEventListener('click', close));
        modal.addEventListener('mousedown', (e) => { if (e.target === modal) close(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    }
    bindModal('shareModal', 'openShare');
    bindModal('editBoardModal', 'openEditBoard');
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && currentCardId) closeCard(); });

    document.querySelectorAll('.share-check').forEach((cb) => {
        cb.addEventListener('change', () => {
            cb.closest('label').querySelector('.share-role').disabled = !cb.checked;
        });
    });
    $('shareSearch')?.addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        document.querySelectorAll('#shareList label').forEach((l) => {
            l.classList.toggle('hidden', q !== '' && !l.dataset.name.includes(q));
        });
    });
})();
</script>
@endsection
