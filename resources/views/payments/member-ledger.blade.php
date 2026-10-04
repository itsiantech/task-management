@extends('layouts.app')

@section('title', $selectedUser->name . ' Payment Ledger')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Ledger</p>
            <h1 class="text-3xl font-bold text-slate-900">{{ $selectedUser->name }}'s Payments</h1>
        </div>
    </div>

    <form method="GET" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
            <div>
                <label for="month_year" class="mb-1 block text-sm font-medium text-slate-700">Month</label>
                <input id="month_year" type="month" name="month_year" value="{{ $monthYear }}" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            </div>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                Filter
            </button>
        </div>
    </form>

    <div class="mb-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Balance Due</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">${{ number_format((float) $dueBalance, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Payments This Month</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">${{ number_format((float) $payments->sum('amount'), 2) }}</p>
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
                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700">
                    {{ $summary->month_year }}: ${{ number_format((float) $summary->total_paid, 2) }}
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Task</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $payment->task?->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    {{ ucfirst($payment->payment_status ?? 'paid') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-emerald-700">${{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No payment entries found for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
