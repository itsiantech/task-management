@extends('layouts.app')

@section('title', 'Orders')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Orders</h1>
            <p class="text-sm text-slate-500 mt-1">Every task/order with paid and due amounts</p>
        </div>
        <a href="{{ route('accounts.orders.export', request()->query()) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Export CSV</a>
    </div>

    <form method="GET" class="mb-4 grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search order, company, phone..." class="rounded-xl border border-slate-200 px-3 py-2 text-sm lg:col-span-2">
        <select name="payment_status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All payment states</option>
            @foreach($paymentStatuses as $value => $label)
                <option value="{{ $value }}" {{ ($filters['payment_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="client_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All clients</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ (string)($filters['client_id'] ?? '') === (string)$client->id ? 'selected' : '' }}>{{ $client->name }}</option>
            @endforeach
        </select>
        <select name="sort" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="latest" {{ ($filters['sort'] ?? '') === 'latest' ? 'selected' : '' }}>Newest first</option>
            <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            <option value="amount_high" {{ ($filters['sort'] ?? '') === 'amount_high' ? 'selected' : '' }}>Amount: high → low</option>
            <option value="amount_low" {{ ($filters['sort'] ?? '') === 'amount_low' ? 'selected' : '' }}>Amount: low → high</option>
            <option value="due_high" {{ ($filters['sort'] ?? '') === 'due_high' ? 'selected' : '' }}>Due: high → low</option>
        </select>
        <div class="flex gap-2">
            <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Filter</button>
            <a href="{{ route('accounts.orders.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Order No</th>
                    <th class="px-4 py-3 text-left">Company</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-right">Due</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $order->order_number_label }}</td>
                        <td class="px-4 py-3">{{ $order->client?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $order->client?->phone ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">৳{{ number_format($order->total_budget, 2) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">৳{{ number_format($order->paid_sum ?? $order->paid_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-amber-600">৳{{ number_format($order->due_amount, 2) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ ucfirst($order->payment_status) }}</span></td>
                        <td class="px-4 py-3 text-slate-500">{{ $order->created_at?->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1 text-xs font-medium">
                                @if($order->client)
                                    <a href="{{ route('accounts.clients.show', $order->client) }}" class="rounded-lg px-2 py-1 text-violet-600 hover:bg-violet-50">Client</a>
                                @endif
                                <a href="{{ route('tasks.show', $order) }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100">Task</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-slate-500">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
