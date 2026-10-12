@extends('layouts.app')

@section('title', 'Invoices of ' . $task->order_number_label)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('tasks.show', $task) }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Task</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Invoices — {{ $task->order_number_label }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $task->title }} · {{ $task->client?->name ?? 'No client' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Billed</p><p class="mt-1 text-2xl font-semibold">৳{{ number_format($totalBilled, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Paid</p><p class="mt-1 text-2xl font-semibold text-emerald-600">৳{{ number_format($totalPaid, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total Due</p><p class="mt-1 text-2xl font-semibold text-amber-600">৳{{ number_format($totalDue, 2) }}</p></div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3 text-left">Invoice</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Items</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Due</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                    @php $enum = $invoice->status_enum; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $enum?->color() ?? 'bg-slate-100 text-slate-600' }}">{{ $enum?->label() ?? ucfirst($invoice->status) }}</span></td>
                        <td class="px-4 py-3 text-slate-500">{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $invoice->items->count() }} item(s)</td>
                        <td class="px-4 py-3 text-right">৳{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-amber-600">৳{{ number_format($invoice->total_due, 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1 text-xs font-medium">
                                <a href="{{ route('accounts.invoices.show', $invoice) }}" class="rounded-lg px-2 py-1 text-violet-600 hover:bg-violet-50">View</a>
                                <a href="{{ route('accounts.invoices.pdf', $invoice) }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No invoices for this task yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
