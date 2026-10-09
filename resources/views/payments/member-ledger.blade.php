@extends('layouts.app')

@section('title', $selectedUser->name . ' Payment Ledger')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $paymentMonths = $monthSummary->pluck('month_year')->push(now()->format('Y-m'))->unique()->sortDesc()->values();
@endphp
<div class="mx-auto max-w-6xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Ledger</p>
            <h1 class="text-3xl font-bold text-slate-900">{{ $selectedUser->name }}'s Payments</h1>
            @if ($selectedUser->member_code)
                <p class="mt-1 text-sm text-slate-500">Member ID: <span class="font-semibold text-slate-700">{{ $selectedUser->member_code }}</span></p>
            @endif
        </div>
        @if ($isAdmin)
            <button type="button" data-add-payment data-user-id="{{ $selectedUser->id }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">+ Add Payment</button>
        @endif
    </div>

    <form method="GET" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
            <div>
                <label for="month_year" class="mb-1 block text-sm font-medium text-slate-700">Month</label>
                <select id="month_year" name="month_year" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    @foreach ($paymentMonths as $paymentMonth)
                        <option value="{{ $paymentMonth }}" @selected($monthYear === $paymentMonth)>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $paymentMonth)->format('F Y') }}</option>
                    @endforeach
                    <option value="all" @selected($showAllMonths)>All months</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                Filter
            </button>
        </div>
    </form>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Balance Due</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">${{ number_format((float) $dueBalance, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">{{ $showAllMonths ? 'All Payments' : 'Payments This Month' }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">${{ number_format((float) $payments->sum('amount'), 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Advance Balance</p>
            <p class="mt-2 text-2xl font-bold {{ $advanceBalance > 0 ? 'text-amber-600' : 'text-slate-400' }}">${{ number_format((float) $advanceBalance, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Tasks</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $tasks->count() }}</p>
        </div>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Month-by-month payment summary</h2>
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

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Payment history</h2>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Task</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
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
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    {{ ucfirst($payment->payment_status ?? 'paid') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-emerald-700">${{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No payment entries found{{ $showAllMonths ? '' : ' for this month' }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($isAdmin)
    @include('payments._add-payment-modal', ['paymentMembers' => collect([$selectedUser]), 'paymentPresetId' => $selectedUser->id])
@endif
@endsection
