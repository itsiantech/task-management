@extends('layouts.app')

@section('title', 'Preview ' . $invoice->invoice_number)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('accounts.invoices.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Invoices</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ $invoice->invoice_number }}</h1>
                @php $enum = $invoice->status_enum; @endphp
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $enum?->color() ?? 'bg-slate-100 text-slate-600' }}">{{ strtoupper($enum?->label() ?? $invoice->status) }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $invoice->client?->name ?? '—' }} · ৳{{ number_format($invoice->total_amount, 2) }} · Due ৳{{ number_format($invoice->total_due, 2) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('accounts.invoices.show', $invoice) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Details</a>
            <a href="{{ route('accounts.invoices.print', $invoice) }}" target="_blank" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Print</a>
            <a href="{{ route('accounts.invoices.pdf', $invoice) }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">⬇ Download PDF</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm">
        <iframe src="{{ route('accounts.invoices.print', $invoice) }}" title="Invoice preview" class="h-[75vh] w-full bg-white"></iframe>
    </div>
    <p class="mt-2 text-xs text-slate-500">Preview na dekhale <a href="{{ route('accounts.invoices.pdf', $invoice) }}" class="font-medium text-violet-600 hover:text-violet-800">ekhan theke download</a> korun.</p>
</div>
@endsection
