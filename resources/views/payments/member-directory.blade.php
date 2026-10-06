@extends('layouts.app')

@section('title', 'Member Directory')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Team</p>
            <h1 class="text-3xl font-bold text-slate-900">Member Directory</h1>
        </div>
        <button type="button" id="openAddMember" class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
            + Add Team Member
        </button>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="GET" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
            <div>
                <label for="month_year" class="mb-1 block text-sm font-medium text-slate-700">Month</label>
                <input id="month_year" type="month" name="month_year" value="{{ $monthYear }}" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
            </div>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                Filter
            </button>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Member</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">This Month</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($members as $member)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $member->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $member->email }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-emerald-700">${{ number_format((float) ($member->payments?->sum('amount') ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('members.payments', $member) }}?month_year={{ $monthYear }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    View ledger
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No approved members available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@php $addErrors = $errors->getBag('addMember'); @endphp
<div id="addMemberModal" class="fixed inset-0 z-50 {{ $addErrors->any() ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Add Team Member</h2>
            <button type="button" id="closeAddMember" class="text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>

        <form action="{{ route('admin.members.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="member_name" class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                <input id="member_name" name="name" type="text" required value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                @if ($addErrors->has('name'))<p class="mt-1 text-xs text-rose-600">{{ $addErrors->first('name') }}</p>@endif
            </div>
            <div>
                <label for="member_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input id="member_email" name="email" type="email" required value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                @if ($addErrors->has('email'))<p class="mt-1 text-xs text-rose-600">{{ $addErrors->first('email') }}</p>@endif
            </div>
            <div>
                <label for="member_role" class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                <select id="member_role" name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    <option value="member" @selected(old('role', 'member') === 'member')>Member</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                </select>
                @if ($addErrors->has('role'))<p class="mt-1 text-xs text-rose-600">{{ $addErrors->first('role') }}</p>@endif
            </div>
            <div>
                <label for="member_password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input id="member_password" name="password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                @if ($addErrors->has('password'))<p class="mt-1 text-xs text-rose-600">{{ $addErrors->first('password') }}</p>@endif
            </div>
            <div>
                <label for="member_password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                <input id="member_password_confirmation" name="password_confirmation" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" id="cancelAddMember" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">Add Member</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('addMemberModal');
        const show = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        const hide = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        document.getElementById('openAddMember').addEventListener('click', show);
        document.getElementById('closeAddMember').addEventListener('click', hide);
        document.getElementById('cancelAddMember').addEventListener('click', hide);
        modal.addEventListener('click', (e) => { if (e.target === modal) hide(); });
    })();
</script>
@endsection
