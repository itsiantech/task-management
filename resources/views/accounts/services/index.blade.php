@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Services</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your service catalog</p>
        </div>
        <a href="{{ route('accounts.services.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
            </svg>
            Add Service
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Services</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $statistics['total'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Active</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $statistics['active'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Inactive</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-400">{{ $statistics['inactive'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Domain Services</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-600">{{ $statistics['by_type']['domain'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-3">
        <form method="GET" action="{{ route('accounts.services.index') }}" class="flex flex-1 items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search services..." class="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none">
            <select name="type" class="rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none">
                <option value="">All Types</option>
                <option value="domain" {{ request('type') === 'domain' ? 'selected' : '' }}>Domain</option>
                <option value="hosting" {{ request('type') === 'hosting' ? 'selected' : '' }}>Hosting</option>
                <option value="web_development" {{ request('type') === 'web_development' ? 'selected' : '' }}>Web Development</option>
                <option value="digital_marketing" {{ request('type') === 'digital_marketing' ? 'selected' : '' }}>Digital Marketing</option>
            </select>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Search</button>
            @if (request()->hasAny(['search', 'type']))
                <a href="{{ route('accounts.services.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Clear</a>
            @endif
        </form>
    </div>

    <!-- Services Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Default Price</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($services as $service)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900">{{ $service->name }}</div>
                            @if($service->description)
                                <div class="mt-1 text-xs text-slate-500 line-clamp-1">{{ $service->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                {{ $service->type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $service->formatted_price }}</td>
                        <td class="px-6 py-4">
                            @if($service->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('accounts.services.edit', $service) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke-linecap="round"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                                <form action="{{ route('accounts.services.toggle', $service) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="{{ $service->is_active ? 'Deactivate' : 'Activate' }}">
                                        @if($service->is_active)
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round"/>
                                            </svg>
                                        @else
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M12 6v6l4 2" stroke-linecap="round"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                                <form action="{{ route('accounts.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this service?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-rose-400 hover:bg-rose-50 hover:text-rose-600">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <p class="text-lg font-medium">No services found</p>
                            <p class="mt-1 text-sm">Get started by creating your first service.</p>
                            <a href="{{ route('accounts.services.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
                                </svg>
                                Add Service
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($services->hasPages())
        <div class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-500">
                                Showing {{ $services->firstItem() }} to {{ $services->lastItem() }} of {{ $services->total() }} results
                            </p>
                            {{ $services->links() }}
                        </div>
                    @endif
                </div>
@endsection
