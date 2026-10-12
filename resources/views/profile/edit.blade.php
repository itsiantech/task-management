@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
@php
    $profileErrors = $errors->getBag('profile');
    $passwordErrors = $errors->getBag('password');
@endphp
<div class="mx-auto max-w-3xl">
    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Account</p>
        <h1 class="mt-1 flex flex-wrap items-center gap-2 text-2xl font-bold text-slate-900">
            My Profile
            @if ($user->member_code)
                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $user->member_code }}</span>
            @endif
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ $user->name }} · {{ $user->email }}</p>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Personal information</h2>
            <p class="text-sm text-slate-500">Update your contact details, addresses and documents (NID / CV). Admin can also see these.</p>
        </div>
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($profileErrors->has('phone'))<p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('phone') }}</p>@endif
                </div>
                <div>
                    <label for="family_phone" class="mb-1 block text-sm font-medium text-slate-700">Family phone</label>
                    <input id="family_phone" name="family_phone" type="text" value="{{ old('family_phone', $user->family_phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($profileErrors->has('family_phone'))<p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('family_phone') }}</p>@endif
                </div>
            </div>
            <div>
                <label for="present_address" class="mb-1 block text-sm font-medium text-slate-700">Present address</label>
                <textarea id="present_address" name="present_address" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">{{ old('present_address', $user->present_address) }}</textarea>
                @if ($profileErrors->has('present_address'))<p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('present_address') }}</p>@endif
            </div>
            <div>
                <label for="permanent_address" class="mb-1 block text-sm font-medium text-slate-700">Permanent address</label>
                <textarea id="permanent_address" name="permanent_address" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">{{ old('permanent_address', $user->permanent_address) }}</textarea>
                @if ($profileErrors->has('permanent_address'))<p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('permanent_address') }}</p>@endif
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="nid" class="mb-1 block text-sm font-medium text-slate-700">NID (jpg, png, pdf, doc — max 2MB)</label>
                    <input id="nid" name="nid" type="file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-sky-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($user->nid_path)
                        <a href="{{ route('members.documents', [$user, 'nid']) }}" class="mt-1 inline-block text-xs font-medium text-sky-600 hover:text-sky-500">Download current NID</a>
                    @endif
                    @if ($profileErrors->has('nid'))<p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('nid') }}</p>@endif
                </div>
                <div>
                    <label for="cv" class="mb-1 block text-sm font-medium text-slate-700">CV (jpg, png, pdf, doc — max 2MB)</label>
                    <input id="cv" name="cv" type="file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-sky-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($user->cv_path)
                        <a href="{{ route('members.documents', [$user, 'cv']) }}" class="mt-1 inline-block text-xs font-medium text-sky-600 hover:text-sky-500">Download current CV</a>
                    @endif
                    @if ($profileErrors->has('cv'))<p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('cv') }}</p>@endif
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">Save Profile</button>
            </div>
        </form>
    </div>

    @if ($user->role === 'member')
        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Monthly Salary</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $salaryStats['monthly_salary'] !== null ? '$' . number_format((float) $salaryStats['monthly_salary'], 2) : '—' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Paid This Month</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">${{ number_format((float) $salaryStats['this_month'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">All-time Received</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">${{ number_format((float) $salaryStats['all_time'], 2) }}</p>
                <a href="{{ route('payments.member') }}" class="mt-1 inline-block text-xs font-medium text-sky-600 hover:text-sky-500">Full payment history →</a>
            </div>
        </div>

        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">My Task History</h2>
                    <p class="text-sm text-slate-500">Every task assigned to you so far ({{ $taskHistory->total() }}).</p>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Task</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($taskHistory as $task)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-slate-900">{{ $task->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $task->order_number_label }}</p>
                                </td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ \App\Models\Task::statusColors()[$task->status] ?? 'bg-slate-100 text-slate-700' }}">{{ \App\Models\Task::statusLabels()[$task->status] ?? ucfirst($task->status) }}</span></td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $task->due_date ? $task->due_date->format('M d, Y') : '—' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('tasks.show', $task) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Open</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No tasks assigned to you yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($taskHistory->hasPages())
                <div class="mt-4">{{ $taskHistory->links() }}</div>
            @endif
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Change password</h2>
        </div>
        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Current password</label>
                <input id="current_password" name="current_password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                @if ($passwordErrors->has('current_password'))<p class="mt-1 text-xs text-rose-600">{{ $passwordErrors->first('current_password') }}</p>@endif
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="new_password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                    <input id="new_password" name="password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($passwordErrors->has('password'))<p class="mt-1 text-xs text-rose-600">{{ $passwordErrors->first('password') }}</p>@endif
                </div>
                <div>
                    <label for="new_password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm new password</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">Change Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
