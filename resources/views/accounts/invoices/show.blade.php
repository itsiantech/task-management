@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('accounts.invoices.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Invoices</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ $invoice->invoice_number }}</h1>
                @php $enum = $invoice->status_enum; @endphp
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $enum?->color() ?? 'bg-slate-100 text-slate-600' }}">{{ strtoupper($enum?->label() ?? $invoice->status) }}</span>
                @if($invoice->status === 'paid')<span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-medium text-white">🔒 Locked</span>@endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('accounts.invoices.print', $invoice) }}" target="_blank" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Print</a>
            <a href="{{ route('accounts.invoices.preview', $invoice) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">PDF Preview</a>
            @if($canUpdate && $invoice->status === 'draft')
                <form method="POST" action="{{ route('accounts.invoices.publish', $invoice) }}">@csrf<button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Publish</button></form>
            @endif
            @if($canUpdate)
                <a href="{{ route('accounts.invoices.edit', $invoice) }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Edit</a>
            @endif
        </div>
    </div>

    @if($invoice->status === 'void' && $invoice->void_reason)
        <div class="mb-4 rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">Void reason:</span> {{ $invoice->void_reason }}
            <span class="text-slate-500">— by {{ $invoice->voidedBy?->name ?? '—' }}, {{ $invoice->voided_at?->format('d M Y H:i') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <!-- Items -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr><th class="px-4 py-3 text-left">Service / Description</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Unit</th><th class="px-4 py-3 text-right">Disc.</th><th class="px-4 py-3 text-right">Tax</th><th class="px-4 py-3 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-3"><p class="font-medium text-slate-900">{{ $item->description }}</p>@if($item->service)<p class="text-xs text-slate-500">{{ $item->service->type_label }}: {{ $item->service->name }}</p>@endif</td>
                                <td class="px-4 py-3 text-right">{{ $item->qty }}</td>
                                <td class="px-4 py-3 text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right">৳{{ number_format($item->discount, 2) }}</td>
                                <td class="px-4 py-3 text-right">৳{{ number_format($item->tax, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium">৳{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="space-y-1 border-t border-slate-200 px-4 py-3 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>৳{{ number_format($invoice->sub_total, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Advance credit</span><span>৳{{ number_format($invoice->advance_paid_credit, 2) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Total</span><span>৳{{ number_format($invoice->total_amount, 2) }}</span></div>
                    <div class="flex justify-between font-semibold {{ $invoice->total_due > 0 ? 'text-amber-600' : 'text-emerald-600' }}"><span>Balance Due</span><span>৳{{ number_format($invoice->total_due, 2) }}</span></div>
                </div>
            </div>

            <!-- Payments -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Payments ({{ $invoice->payments->count() }})</h2>
                    @if($canAddPayment)
                        <button onclick="document.getElementById('addPaymentModal').classList.remove('hidden')" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Add Payment</button>
                    @endif
                </div>
                @forelse($invoice->payments as $payment)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 py-3 last:border-0">
                        <div>
                            <p class="font-medium text-slate-900">৳{{ number_format($payment->amount, 2) }}
                                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($payment->method) }}</span>
                                @if($payment->edits_count ?? $payment->edits()->count() > 0)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Edited</span>@endif
                            </p>
                            <p class="text-xs text-slate-500">{{ $payment->payment_date?->format('d M Y') }} · {{ $payment->receiving_account ?? '—' }} · {{ $payment->transaction_id ?? 'no txn id' }} · by {{ $payment->createdBy?->name ?? '—' }}</p>
                            @if($payment->note)<p class="text-xs text-slate-500">{{ $payment->note }}</p>@endif
                        </div>
                        <a href="{{ route('accounts.payments.edit', $payment) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">History / Correct →</a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments recorded yet.</p>
                @endforelse
            </div>

            @if($invoice->notes)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-2 text-lg font-semibold text-slate-900">Notes</h2>
                    <p class="text-sm text-slate-700">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <!-- Client + meta -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold text-slate-900">Invoiced To</h2>
                <p class="font-medium text-slate-900">{{ $invoice->billing_company ?? $invoice->client?->name }}</p>
                @if($invoice->billing_name ?? $invoice->client?->contact_person)<p class="text-sm text-slate-700">{{ $invoice->billing_name ?? $invoice->client?->contact_person }}</p>@endif
                @if($invoice->billing_address ?? $invoice->client?->address)<p class="text-sm text-slate-500">{{ $invoice->billing_address ?? $invoice->client?->address }}</p>@endif
                <p class="text-sm text-slate-500">{{ $invoice->billing_phone ?? $invoice->client?->phone }}@if($invoice->billing_email ?? $invoice->client?->email) · {{ $invoice->billing_email ?? $invoice->client?->email }}@endif</p>
                @if($invoice->billing_country)<p class="text-sm text-slate-500">{{ $invoice->billing_country }}</p>@endif
                @if($invoice->client)
                    <a href="{{ route('accounts.clients.show', $invoice->client) }}" class="mt-2 inline-block text-sm font-medium text-violet-600 hover:text-violet-800">View Client Details →</a>
                @endif
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Invoice Date</dt><dd>{{ $invoice->invoice_date?->format('d M Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Due Date</dt><dd>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Method</dt><dd>{{ $invoice->paymentMethodLabel() }}</dd></div>
                    @if($invoice->task)<div class="flex justify-between"><dt class="text-slate-500">Order</dt><dd>{{ $invoice->task->order_number_label }}</dd></div>@endif
                </dl>
            </div>

            <!-- Danger zone -->
            @can('void', $invoice)
                <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">Status Actions</h2>
                    <div class="flex flex-wrap gap-2">
                        @if($invoice->status === 'hold')
                            <form method="POST" action="{{ route('accounts.invoices.resume', $invoice) }}">@csrf<button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Resume</button></form>
                        @elseif(in_array($invoice->status, ['draft', 'unpaid', 'partial', 'due']))
                            <form method="POST" action="{{ route('accounts.invoices.hold', $invoice) }}">@csrf<button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Put on Hold</button></form>
                        @endif
                        @if($invoice->status !== 'void')
                            <button onclick="document.getElementById('voidModal').classList.remove('hidden')" class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Cancel / Void…</button>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Invoices can never be deleted. Cancelling moves the invoice to Void with a mandatory reason.</p>
                </div>
            @endcan
        </div>
    </div>
</div>

<!-- Add Payment modal -->
@if($canAddPayment)
<div id="addPaymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('addPaymentModal').classList.add('hidden')"></div>
    <form method="POST" action="{{ route('accounts.invoices.payments.store', $invoice) }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        @csrf
        <h3 class="text-lg font-semibold">Add Payment — {{ $invoice->invoice_number }}</h3>
        <p class="mb-4 text-sm text-slate-500">Due: <span class="font-semibold text-amber-600">৳{{ number_format($invoice->total_due, 2) }}</span></p>
        <div class="space-y-3">
            <div><label class="text-sm font-medium">Amount (৳) *</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->total_due }}" value="{{ $invoice->total_due }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Date *</label><input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Method *</label>
                <select name="method" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    @foreach($methods as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Receiver Account</label>
                <select name="receiving_account" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    <option value="">—</option>
                    @foreach($accounts as $account)<option value="{{ $account->name }}">{{ $account->name }} ({{ $account->type_label }})</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Transaction ID / Reference</label><input type="text" name="transaction_id" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Note</label><input type="text" name="note" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('addPaymentModal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</button>
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Record Payment</button>
        </div>
    </form>
</div>
@endif

<!-- Void modal -->
@can('void', $invoice)
@if($invoice->status !== 'void')
<div id="voidModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('voidModal').classList.add('hidden')"></div>
    <form method="POST" action="{{ route('accounts.invoices.void', $invoice) }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        @csrf
        <h3 class="text-lg font-semibold text-rose-600">Cancel / Void Invoice?</h3>
        <p class="mb-4 text-sm text-slate-500">The invoice will be moved to Void. A reason is mandatory and will be audit-logged.</p>
        <textarea name="void_reason" rows="3" required minlength="3" placeholder="Reason for voiding..." class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></textarea>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</button>
            <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-medium text-white">Move to Void</button>
        </div>
    </form>
</div>
@endif
@endcan
@endsection
