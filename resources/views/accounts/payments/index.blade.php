@extends('layouts.app')

@section('title', 'Payments')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payments</h1>
            <p class="text-sm text-slate-500 mt-1">Who paid, how much, via which method, on what date · Total: <span class="font-semibold text-emerald-600">৳{{ number_format($total, 2) }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounts.payments.export', request()->query()) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Export CSV</a>
            @can('create', App\Models\Accounts\Payment::class)
                <button onclick="document.getElementById('addPaymentModal').classList.remove('hidden')" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">+ Record Payment</button>
            @endcan
        </div>
    </div>

    <form method="GET" class="mb-4 grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Txn id, account, client..." class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <select name="method" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All methods</option>
            @foreach($methods as $method)<option value="{{ $method->value }}" {{ ($filters['method'] ?? '') === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>@endforeach
        </select>
        <select name="client_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All clients</option>
            @foreach($clients as $client)<option value="{{ $client->id }}" {{ (string)($filters['client_id'] ?? '') === (string)$client->id ? 'selected' : '' }}>{{ $client->name }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <div class="flex gap-2">
            <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Filter</button>
            <a href="{{ route('accounts.payments.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[920px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Client</th><th class="px-4 py-3 text-left">Invoice</th><th class="px-4 py-3 text-left">Method</th><th class="px-4 py-3 text-left">Account / Txn</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-left">Flags</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $payment->client?->name ?? '—' }}</td>
                        <td class="px-4 py-3">@if($payment->invoice)<a href="{{ route('accounts.invoices.show', $payment->invoice) }}" class="font-medium text-violet-600 hover:text-violet-800">{{ $payment->invoice->invoice_number }}</a>@else—@endif</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium">{{ ucfirst($payment->method) }}</span></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $payment->receiving_account ?? '—' }}<br>{{ $payment->transaction_id ?? '' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-600">৳{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-3">@if($payment->edits_count > 0)<span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Edited</span>@endif</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('accounts.payments.edit', $payment) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">History / Correct</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="mt-4">{{ $payments->links() }}</div>
    @endif
</div>

@can('create', App\Models\Accounts\Payment::class)
<div id="addPaymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('addPaymentModal').classList.add('hidden')"></div>
    <form method="POST" action="{{ route('accounts.payments.store') }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        @csrf
        <h3 class="mb-4 text-lg font-semibold">Record Payment</h3>
        <div class="space-y-3">
            <div><label class="text-sm font-medium">Client *</label>
                <select name="client_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    <option value="">— Select —</option>
                    @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Amount (৳) *</label><input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Date *</label><input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Method *</label>
                <select name="method" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    @foreach($methods as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Transaction ID</label><input type="text" name="transaction_id" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Note</label><input type="text" name="note" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('addPaymentModal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</button>
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Record</button>
        </div>
    </form>
</div>
@endcan
@endsection
