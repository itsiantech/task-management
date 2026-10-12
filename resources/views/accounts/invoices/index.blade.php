@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Invoices</h1>
            <p class="text-sm text-slate-500 mt-1">Unpaid, partial, paid, due, hold and void</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounts.invoices.export', request()->query()) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Export CSV</a>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('accounts.invoices.create') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">+ New Invoice</a>
            @endcan
        </div>
    </div>

    <!-- Status tabs -->
    <div class="mb-4 flex flex-wrap gap-2">
        @php $active = $filters['status'] ?? ''; @endphp
        <a href="{{ route('accounts.invoices.index', array_merge(request()->except('status', 'page'))) }}" class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ $active === '' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">All ({{ array_sum($counts) }})</a>
        @foreach($statuses as $status)
            <a href="{{ route('accounts.invoices.index', array_merge(request()->except('status', 'page'), ['status' => $status->value])) }}" class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ $active === $status->value ? 'bg-slate-900 text-white' : $status->color() }}">{{ $status->label() }} ({{ $counts[$status->value] ?? 0 }})</a>
        @endforeach
    </div>

    <form method="GET" class="mb-4 grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        @if(!empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Invoice no, client, phone..." class="rounded-xl border border-slate-200 px-3 py-2 text-sm lg:col-span-2">
        <select name="client_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All clients</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ (string)($filters['client_id'] ?? '') === (string)$client->id ? 'selected' : '' }}>{{ $client->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <div class="flex gap-2">
            <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Filter</button>
            <a href="{{ route('accounts.invoices.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[860px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Due</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                    @php $enum = $invoice->status_enum; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3">{{ $invoice->client?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $enum?->color() ?? 'bg-slate-100 text-slate-600' }}">{{ $enum?->label() ?? ucfirst($invoice->status) }}</span></td>
                        <td class="px-4 py-3 text-slate-500">{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">৳{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $invoice->total_due > 0 ? 'text-amber-600 font-medium' : 'text-slate-400' }}">৳{{ number_format($invoice->total_due, 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1 text-xs font-medium">
                                <a href="{{ route('accounts.invoices.show', $invoice) }}" class="rounded-lg px-2 py-1 text-violet-600 hover:bg-violet-50">View</a>
                                <a href="{{ route('accounts.invoices.print', $invoice) }}" target="_blank" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100">Print</a>
                                <a href="{{ route('accounts.invoices.preview', $invoice) }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div class="mt-4">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
