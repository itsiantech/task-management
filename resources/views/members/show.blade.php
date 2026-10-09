@extends('layouts.app')

@section('title', $user->name . ' Profile')

@section('content')
@php
    $editErrors = $errors->getBag('editMember');
    $resetErrors = $errors->getBag('resetPassword');
    $allTimePaid = (float) $monthSummary->sum('total_paid');
@endphp
<div class="mx-auto max-w-6xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.members') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Back to members">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Member Profile</p>
                <h1 class="flex flex-wrap items-center gap-2 text-2xl font-bold text-slate-900">
                    {{ $user->name }}
                    @if ($user->member_code)
                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $user->member_code }}</span>
                    @endif
                </h1>
                <p class="text-sm text-slate-500">{{ $user->email }} · {{ ucfirst($user->role) }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-open-edit class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</button>
            <button type="button" data-open-reset class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset Password</button>
            <button type="button" data-add-payment data-user-id="{{ $user->id }}" class="rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">+ Add Payment</button>
            @if ($user->role === 'member' && $user->id !== auth()->id())
                <form action="{{ route('admin.members.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this member? Their payments and records will also be removed. This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mb-8 grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Profile Info</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="font-medium text-slate-800">{{ $user->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Family Phone</dt>
                    <dd class="font-medium text-slate-800">{{ $user->family_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Present Address</dt>
                    <dd class="font-medium text-slate-800">{{ $user->present_address ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Permanent Address</dt>
                    <dd class="font-medium text-slate-800">{{ $user->permanent_address ?: '—' }}</dd>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <dt class="text-slate-500">NID</dt>
                    <dd class="font-medium">
                        @if ($user->nid_path)
                            <a href="{{ route('members.documents', [$user, 'nid']) }}" class="text-sky-600 hover:text-sky-500">Download NID</a>
                        @else
                            <span class="text-slate-400">Not uploaded</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">CV</dt>
                    <dd class="font-medium">
                        @if ($user->cv_path)
                            <a href="{{ route('members.documents', [$user, 'cv']) }}" class="text-sky-600 hover:text-sky-500">Download CV</a>
                        @else
                            <span class="text-slate-400">Not uploaded</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">This Month</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">${{ number_format((float) $thisMonthTotal, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">All-time Paid</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($allTimePaid, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Advance Balance</p>
                <p class="mt-2 text-2xl font-bold {{ $advanceBalance > 0 ? 'text-amber-600' : 'text-slate-400' }}">${{ number_format((float) $advanceBalance, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Monthly Salary</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $user->monthly_salary !== null ? '$' . number_format((float) $user->monthly_salary, 2) : '—' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Month-by-month summary</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            @forelse ($monthSummary as $summary)
                <span class="inline-flex flex-col rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700">
                    <span>{{ $summary->month_year }}: ${{ number_format((float) $summary->total_paid, 2) }}</span>
                    <span class="mt-0.5 text-xs font-normal text-emerald-600/80">
                        Salary ${{ number_format((float) $summary->salary_total, 2) }}
                        @if ((float) $summary->advance_total > 0)
                            · Advance ${{ number_format((float) $summary->advance_total, 2) }}
                        @endif
                        @if ((float) $summary->adjustment_total > 0)
                            · Adjusted ${{ number_format((float) $summary->adjustment_total, 2) }}
                        @endif
                    </span>
                </span>
            @empty
                <span class="text-sm text-slate-500">No payment history recorded yet.</span>
            @endforelse
        </div>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Recent payments</h2>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Task</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($payment->payment_type === 'advance')
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Advance</span>
                                @elseif ($payment->payment_type === 'adjustment')
                                    <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Adjustment</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Salary</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $payment->task?->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ $payment->notes ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-emerald-700">${{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Send note to {{ strtok($user->name, ' ') }}</h2>
            <p class="text-sm text-slate-500">This note will appear on their dashboard only.</p>
        </div>
        <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="target_type" value="individual">
            <input type="hidden" name="member_ids[]" value="{{ $user->id }}">
            <div>
                <label for="note_title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                <input id="note_title" name="title" type="text" required maxlength="255" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="note_body" class="mb-1 block text-sm font-medium text-slate-700">Message</label>
                <textarea id="note_body" name="body" rows="3" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Send Note</button>
            </div>
        </form>
    </div>
</div>

@php $editOpen = $editErrors->any() ? 'flex' : 'hidden'; @endphp
<div id="editMemberModal" class="fixed inset-0 z-50 {{ $editOpen }} items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Edit Member</h2>
            <button type="button" data-close-edit class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>

        <form action="{{ route('admin.members.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_name" class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                    <input id="edit_name" name="name" type="text" required value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('name'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('name') }}</p>@endif
                </div>
                <div>
                    <label for="edit_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="edit_email" name="email" type="email" required value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('email'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('email') }}</p>@endif
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_role" class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                    <select id="edit_role" name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        <option value="member" @selected(old('role', $user->role) === 'member')>Member</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                    @if ($editErrors->has('role'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('role') }}</p>@endif
                </div>
                <div>
                    <label for="edit_salary" class="mb-1 block text-sm font-medium text-slate-700">Monthly salary</label>
                    <input id="edit_salary" name="monthly_salary" type="number" step="0.01" min="0" value="{{ old('monthly_salary', $user->monthly_salary) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('monthly_salary'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('monthly_salary') }}</p>@endif
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input id="edit_phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('phone'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('phone') }}</p>@endif
                </div>
                <div>
                    <label for="edit_family_phone" class="mb-1 block text-sm font-medium text-slate-700">Family phone</label>
                    <input id="edit_family_phone" name="family_phone" type="text" value="{{ old('family_phone', $user->family_phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('family_phone'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('family_phone') }}</p>@endif
                </div>
            </div>
            <div>
                <label for="edit_present_address" class="mb-1 block text-sm font-medium text-slate-700">Present address</label>
                <textarea id="edit_present_address" name="present_address" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">{{ old('present_address', $user->present_address) }}</textarea>
                @if ($editErrors->has('present_address'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('present_address') }}</p>@endif
            </div>
            <div>
                <label for="edit_permanent_address" class="mb-1 block text-sm font-medium text-slate-700">Permanent address</label>
                <textarea id="edit_permanent_address" name="permanent_address" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">{{ old('permanent_address', $user->permanent_address) }}</textarea>
                @if ($editErrors->has('permanent_address'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('permanent_address') }}</p>@endif
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_nid" class="mb-1 block text-sm font-medium text-slate-700">NID (jpg, png, pdf, doc — max 2MB)</label>
                    <input id="edit_nid" name="nid" type="file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-sky-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('nid'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('nid') }}</p>@endif
                </div>
                <div>
                    <label for="edit_cv" class="mb-1 block text-sm font-medium text-slate-700">CV (jpg, png, pdf, doc — max 2MB)</label>
                    <input id="edit_cv" name="cv" type="file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-sky-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                    @if ($editErrors->has('cv'))<p class="mt-1 text-xs text-rose-600">{{ $editErrors->first('cv') }}</p>@endif
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close-edit class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@php $resetOpen = $resetErrors->any() ? 'flex' : 'hidden'; @endphp
<div id="resetPasswordModal" class="fixed inset-0 z-50 {{ $resetOpen }} items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Reset Password — {{ strtok($user->name, ' ') }}</h2>
            <button type="button" data-close-reset class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>
        <form action="{{ route('admin.members.password', $user) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="reset_password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                <input id="reset_password" name="password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                @if ($resetErrors->has('password'))<p class="mt-1 text-xs text-rose-600">{{ $resetErrors->first('password') }}</p>@endif
            </div>
            <div>
                <label for="reset_password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                <input id="reset_password_confirmation" name="password_confirmation" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close-reset class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const bindModal = (openSelector, modalId, closeSelector) => {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const show = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
            const hide = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
            document.querySelectorAll(openSelector).forEach((btn) => btn.addEventListener('click', show));
            modal.querySelectorAll(closeSelector).forEach((btn) => btn.addEventListener('click', hide));
            modal.addEventListener('click', (e) => { if (e.target === modal) hide(); });
        };
        bindModal('[data-open-edit]', 'editMemberModal', '[data-close-edit]');
        bindModal('[data-open-reset]', 'resetPasswordModal', '[data-close-reset]');
    })();
</script>

@include('payments._add-payment-modal', ['paymentMembers' => collect([$user]), 'paymentPresetId' => $user->id])
@endsection
