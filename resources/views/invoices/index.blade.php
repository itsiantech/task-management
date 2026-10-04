@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Finance</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">Invoice Management</h1>
            </div>
            <a href="{{ route('invoices.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                New Invoice
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Invoice</th>
                            <th class="px-5 py-4 font-semibold">Client</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 font-semibold">Due Date</th>
                            <th class="px-5 py-4 font-semibold">Total</th>
                            <th class="px-5 py-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($invoices as $invoice)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $invoice->invoice_number_label }}</td>
                                <td class="px-5 py-4">
                                    {{ $invoice->client?->name ?? 'Unknown Client' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-700">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-900">
                                    {{ number_format((float) $invoice->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">View</a>
                                        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Print</a>
                                        <a href="{{ route('invoices.pdf', $invoice) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Download</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">No invoices found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($invoices->hasPages())
            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection
