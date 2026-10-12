@extends('layouts.app')

@section('title', $service->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.services.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Services
        </a>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $service->name }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $service->type_label }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Service Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Service Information</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Type</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $service->type_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Default Price</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $service->formatted_price }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Status</dt>
                        <dd>
                            @if($service->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    @if($service->description)
                        <div>
                            <dt class="text-sm text-slate-500 mb-1">Description</dt>
                            <dd class="text-sm text-slate-900">{{ $service->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Usage Statistics -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Usage Statistics</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Total Invoices</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $service->invoiceItems()->distinct('invoice_id')->count('invoice_id') }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Total Billed</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">৳{{ number_format($service->invoiceItems()->sum('line_total'), 2) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Total Quantity</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $service->invoiceItems()->sum('qty') }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Avg. Price</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">৳{{ number_format($service->invoiceItems()->avg('unit_price') ?: 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            @if($service->invoiceItems()->count() > 0)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Recent Invoices</h2>
                    </div>
                    <div class="divide-y divide-slate-200">
                        @foreach($service->invoiceItems()->with('invoice.client')->latest()->limit(5)->get() as $item)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $item->invoice->invoice_number }}</p>
                                        <p class="text-sm text-slate-500">{{ $item->invoice->client->name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-slate-900">{{ $item->formatted_line_total }}</p>
                                        <p class="text-sm text-slate-500">{{ $item->invoice->invoice_date->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('accounts.services.edit', $service) }}" class="flex w-full items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke-linecap="round"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Edit Service
                    </a>
                    <form action="{{ route('accounts.services.toggle', $service) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            @if($service->is_active)
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round"/>
                                </svg>
                                Deactivate
                            @else
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2" stroke-linecap="round"/>
                                </svg>
                                Activate
                            @endif
                        </button>
                    </form>
                    @if(!$service->invoiceItems()->exists())
                        <form action="{{ route('accounts.services.destroy', $service) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex w-full items-center gap-2 rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke-linecap="round"/>
                                </svg>
                                Delete Service
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
