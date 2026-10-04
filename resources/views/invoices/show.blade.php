@extends('layouts.app')

@section('title', 'Invoice '.$invoice->invoice_number_label)

@section('content')
    @php
        $statusColors = [
            'Draft' => 'bg-slate-100 text-slate-700 border-slate-200',
            'Paid' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'Unpaid' => 'bg-amber-100 text-amber-700 border-amber-200',
            'Due' => 'bg-orange-100 text-orange-700 border-orange-200',
            'Overdue' => 'bg-red-100 text-red-700 border-red-200',
            'Cancelled' => 'bg-slate-200 text-slate-700 border-slate-300',
            'Pending Payment' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'Refunded' => 'bg-violet-100 text-violet-700 border-violet-200',
        ];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Invoice Overview</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $invoice->invoice_number_label }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex rounded-full border px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] {{ $statusColors[$invoice->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ strtoupper($invoice->status) }}
                </span>
                <a href="{{ route('invoices.edit', $invoice) }}" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Edit</a>
                <a href="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
            </div>
        </div>

        <div class="invoice-card rounded-[28px] border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 md:p-8">
            <div class="flex flex-col gap-5 border-b border-slate-200 pb-6 md:flex-row md:items-start md:justify-between">
                <div class="logo-box">
                    <img src="{{ asset('images/itsian-logo.svg') }}" alt="ITSIAN logo" class="h-16 w-auto" />
                </div>
                <div class="text-left md:text-right">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Status</div>
                    <div class="mt-2 inline-flex rounded-full border px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] {{ $statusColors[$invoice->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ strtoupper($invoice->status) }}
                    </div>
                    <div class="mt-4 text-sm text-slate-600">
                        <span class="font-semibold text-slate-700">Due Date:</span>
                        {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Not set' }}
                    </div>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Invoice</div>
                    <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $invoice->invoice_number_label }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2 no-print">
                    <a href="{{ route('invoices.edit', $invoice) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit Invoice</a>
                    <form action="{{ route('invoices.publish', $invoice) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Publish</button>
                    </form>
                    <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print</a>
                    <a href="{{ route('invoices.pdf', $invoice) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download PDF</a>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Invoiced To</div>
                    <div class="mt-3 space-y-1 text-sm text-slate-700">
                        <p class="text-lg font-semibold text-slate-900">{{ $invoice->client?->name ?? 'Client Name' }}</p>
                        <p>{{ $invoice->client?->name ?? 'Company Name' }}</p>
                        <p>{{ $invoice->client?->address ?? 'Address not provided' }}</p>
                        <p>{{ $invoice->client?->phone ?? 'Phone not provided' }}</p>
                        <p>{{ $invoice->client?->email ?? 'Email not provided' }}</p>
                        <p>Country: Bangladesh</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Pay To (ITSIAN)</div>
                    <div class="mt-3 space-y-1 text-sm text-slate-700">
                        <p class="text-lg font-semibold text-slate-900">ITSIAN</p>
                        <p>House-09, 4th floor, Road 1/b,</p>
                        <p>Block-L Banani, Chairmanbari, Dhaka</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Invoice Date</div>
                    <div class="mt-2 text-lg font-semibold text-slate-900">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '—' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Payment Method</div>
                    <div class="mt-2 text-lg font-semibold text-slate-900">{{ $invoice->paymentMethodLabel() }}</div>
                </div>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-4 font-semibold text-slate-700">Description</th>
                            <th class="px-5 py-4 text-right font-semibold text-slate-700">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td class="px-5 py-4 text-slate-700">{{ $item->description }}</td>
                                <td class="px-5 py-4 text-right font-medium text-slate-900">{{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-5 py-6 text-center text-slate-500">No items added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-end">
                <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between py-2 text-sm text-slate-700">
                        <span>Sub Total</span>
                        <span>{{ number_format((float) $invoice->sub_total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm text-slate-700">
                        <span>Credit</span>
                        <span>{{ number_format((float) $invoice->advance_paid_credit, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200 py-3 text-base font-bold text-slate-900">
                        <span>Total</span>
                        <span>{{ number_format((float) $invoice->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-4 font-semibold text-slate-700">Transaction Date</th>
                            <th class="px-5 py-4 font-semibold text-slate-700">Gateway</th>
                            <th class="px-5 py-4 font-semibold text-slate-700">Transaction ID</th>
                            <th class="px-5 py-4 text-right font-semibold text-slate-700">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($invoice->transactions as $transaction)
                            <tr>
                                <td class="px-5 py-4">{{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '—' }}</td>
                                <td class="px-5 py-4">{{ ucfirst($transaction->gateway) }}</td>
                                <td class="px-5 py-4">{{ $transaction->transaction_id ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-right font-medium text-slate-900">{{ number_format((float) $transaction->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-slate-500">No payment transactions recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-end">
                <div class="rounded-2xl border border-slate-200 bg-slate-900 px-5 py-4 text-white">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Balance Due</div>
                    <div class="mt-2 text-2xl font-bold">{{ number_format((float) $invoice->total_due, 2) }}</div>
                </div>
            </div>

            @if ($invoice->notes)
                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Notes</div>
                    <p class="mt-2 text-sm text-slate-700">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
