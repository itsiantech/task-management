@extends('layouts.app')

@section('title', $client->name)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Clients</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $client->name }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $client->contact_person }} · {{ $client->phone }} · {{ $client->email ?? '—' }}</p>
    </div>

    <!-- Totals -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Billed</p><p class="mt-1 text-2xl font-semibold">৳{{ number_format($totalBilled, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Paid</p><p class="mt-1 text-2xl font-semibold text-emerald-600">৳{{ number_format($totalPaid, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Due</p><p class="mt-1 text-2xl font-semibold text-amber-600">৳{{ number_format($totalDue, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Pending Invoices</p><p class="mt-1 text-2xl font-semibold">{{ $pendingInvoices->count() }}</p></div>
    </div>

    @if($client->address || $client->notes)
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if($client->address)<p class="text-sm"><span class="text-slate-500">Address:</span> {{ $client->address }}</p>@endif
            @if($client->notes)<p class="mt-1 text-sm"><span class="text-slate-500">Notes:</span> {{ $client->notes }}</p>@endif
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Orders -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Orders ({{ $client->tasks->count() }})</h2>
            @forelse($client->tasks as $task)
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                    <div><p class="font-medium">{{ $task->order_number_label }} — {{ $task->title }}</p><p class="text-xs text-slate-500">{{ ucfirst($task->payment_status) }}</p></div>
                    <div class="text-right"><p class="font-medium">৳{{ number_format($task->total_budget, 2) }}</p><p class="text-xs text-amber-600">Due ৳{{ number_format($task->due_amount, 2) }}</p></div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No orders yet.</p>
            @endforelse
        </div>

        <!-- Invoices -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Invoices ({{ $client->invoices->count() }})</h2>
            @forelse($client->invoices as $invoice)
                <a href="{{ route('accounts.invoices.show', $invoice) }}" class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0 hover:bg-slate-50">
                    <div><p class="font-medium">{{ $invoice->invoice_number }}</p><p class="text-xs text-slate-500">{{ $invoice->invoice_date?->format('d M Y') }} · {{ $invoice->status_enum?->label() ?? ucfirst($invoice->status) }}</p></div>
                    <div class="text-right"><p class="font-medium">৳{{ number_format($invoice->total_amount, 2) }}</p><p class="text-xs text-amber-600">Due ৳{{ number_format($invoice->total_due, 2) }}</p></div>
                </a>
            @empty
                <p class="text-sm text-slate-500">No invoices yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Payments -->
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Payments ({{ $client->accountPayments->count() }})</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-2 text-left">Date</th><th class="px-4 py-2 text-left">Invoice</th><th class="px-4 py-2 text-left">Method</th><th class="px-4 py-2 text-left">Txn ID</th><th class="px-4 py-2 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($client->accountPayments as $payment)
                        <tr>
                            <td class="px-4 py-2">{{ $payment->payment_date?->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                            <td class="px-4 py-2">{{ ucfirst($payment->method) }}</td>
                            <td class="px-4 py-2">{{ $payment->transaction_id ?? '—' }}</td>
                            <td class="px-4 py-2 text-right font-medium text-emerald-600">৳{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
