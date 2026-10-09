@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Notices</p>
            <h1 class="text-3xl font-bold text-slate-900">Announcements</h1>
        </div>
        <a href="{{ route('admin.members') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">← Member Directory</a>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">New announcement</h2>
            <p class="text-sm text-slate-500">Send a notice to every member, or pick specific members for an individual note.</p>
        </div>
        <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="ann_title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                <input id="ann_title" name="title" type="text" required maxlength="255" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="ann_body" class="mb-1 block text-sm font-medium text-slate-700">Message</label>
                <textarea id="ann_body" name="body" rows="4" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Send to</span>
                <div class="flex flex-wrap gap-3">
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-violet-300 has-[:checked]:bg-violet-50 has-[:checked]:text-violet-700">
                        <input type="radio" name="target_type" value="all" class="accent-violet-600" @checked(old('target_type', 'all') === 'all')>
                        All members
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-violet-300 has-[:checked]:bg-violet-50 has-[:checked]:text-violet-700">
                        <input type="radio" name="target_type" value="individual" class="accent-violet-600" @checked(old('target_type') === 'individual')>
                        Specific members
                    </label>
                </div>
                @error('target_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div id="memberPicker" class="{{ old('target_type') === 'individual' ? '' : 'hidden' }} rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($members as $member)
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm text-slate-700 ring-1 ring-slate-200 has-[:checked]:ring-violet-400">
                            <input type="checkbox" name="member_ids[]" value="{{ $member->id }}" class="accent-violet-600" @checked(is_array(old('member_ids')) && in_array($member->id, old('member_ids')))>
                            <span class="truncate">{{ $member->name }}</span>
                            @if ($member->member_code)
                                <span class="text-xs text-slate-400">{{ $member->member_code }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                @error('member_ids')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Send Announcement</button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">History</h2>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Read</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($announcements as $announcement)
                        @php
                            $recipientCount = $announcement->recipients->count();
                            $readCount = $announcement->recipients->whereNotNull('read_at')->count();
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $announcement->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <p class="font-medium text-slate-900">{{ $announcement->title }}</p>
                                <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $announcement->body }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                @if ($announcement->target_type === 'all')
                                    <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">All members</span>
                                @else
                                    <span class="text-xs text-slate-600">{{ $announcement->recipients->pluck('user.name')->filter()->take(3)->implode(', ') }}{{ $recipientCount > 3 ? ' +' . ($recipientCount - 3) . ' more' : '' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $readCount }}/{{ $recipientCount }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Delete this announcement?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-rose-600 hover:text-rose-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No announcements sent yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        const picker = document.getElementById('memberPicker');
        document.querySelectorAll('input[name="target_type"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                picker.classList.toggle('hidden', radio.value !== 'individual' || !radio.checked);
            });
        });
    })();
</script>
@endsection
