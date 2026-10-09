@extends('layouts.app')

@section('title', 'Kanban Boards')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<style>
    #boardDescriptionEditor .ql-editor { min-height: 7rem; max-height: 40vh; overflow-y: auto; }
</style>
@endpush

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Workspace</p>
            <h1 class="text-3xl font-bold text-slate-900">Kanban Boards</h1>
            <p class="mt-1 text-sm text-slate-500">Organise work and keep shared credentials in one secure place.</p>
        </div>
        <button type="button" id="openNewBoard" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            + New Board
        </button>
    </div>

    @if ($boards->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-lg font-semibold text-slate-800">No boards yet</p>
            <p class="mt-1 text-sm text-slate-500">Create your first board to start organising cards and credentials.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($boards as $board)
                <a href="{{ route('boards.show', $board) }}" class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="truncate text-lg font-semibold text-slate-900 group-hover:text-indigo-700">{{ $board->name }}</h2>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $board->is_private ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ $board->is_private ? 'Private' : 'Team' }}
                        </span>
                    </div>
                    <p class="mt-2 line-clamp-2 min-h-[2.5rem] text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $board->description)), 150) ?: 'No description' }}</p>
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ $board->columns_count }} lists &middot; {{ $board->cards_count }} cards</span>
                        <span class="capitalize">{{ $board->my_role }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-400">
                        <span>By {{ $board->creator?->name ?? 'Unknown' }}</span>
                        <span>{{ $board->memberships->count() }} member{{ $board->memberships->count() === 1 ? '' : 's' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

{{-- New board modal --}}
<div id="newBoardModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">New Board</h2>
            <button type="button" data-close class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>
        <form action="{{ route('boards.store') }}" method="POST" class="space-y-4" id="newBoardForm">
            @csrf
            <div>
                <label for="board_name" class="mb-1 block text-sm font-medium text-slate-700">Board title</label>
                <input id="board_name" name="name" type="text" required maxlength="255" value="{{ old('name') }}" placeholder="e.g. Client Logins" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Description <span class="font-normal text-slate-400">(rich text, unlimited)</span></label>
                <div id="boardDescriptionEditor"></div>
                <input type="hidden" name="description" id="boardDescriptionInput" value="{{ old('description') }}">
            </div>
            <label class="flex items-start gap-2 text-sm text-slate-600">
                <input type="hidden" name="is_private" value="0">
                <input type="checkbox" name="is_private" value="1" checked class="mt-0.5 rounded border-slate-300">
                <span><span class="font-medium text-slate-800">Private board</span><br>Only you and people you share it with can open it.</span>
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Create Board</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('newBoardModal');
        const open = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.getElementById('board_name').focus(); };
        const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        document.getElementById('openNewBoard').addEventListener('click', open);
        modal.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

        const QUILL_TOOLBAR = [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'code-block', 'link'],
            ['clean'],
        ];
        const descInput = document.getElementById('boardDescriptionInput');
        const quill = new Quill('#boardDescriptionEditor', { theme: 'snow', modules: { toolbar: QUILL_TOOLBAR } });
        if (descInput.value) {
            if (/<[a-z][\s\S]*>/i.test(descInput.value)) quill.clipboard.dangerouslyPasteHTML(descInput.value);
            else quill.setText(descInput.value);
        }
        document.getElementById('newBoardForm').addEventListener('submit', () => {
            descInput.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
        });
    })();
</script>
@endsection
