@extends('layouts.app')

@section('title', 'Create Service')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.services.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Services
        </a>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">Create Service</h1>
        <p class="text-sm text-slate-500 mt-1">Add a new service to your catalog</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('accounts.services.store') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Service Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none"
                        placeholder="e.g., .com Domain Registration">
                    @error('name')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700">Service Type</label>
                    <select name="type" id="type" required
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none">
                        <option value="">Select type</option>
                        <option value="domain" {{ old('type') === 'domain' ? 'selected' : '' }}>Domain</option>
                        <option value="hosting" {{ old('type') === 'hosting' ? 'selected' : '' }}>Hosting</option>
                        <option value="web_development" {{ old('type') === 'web_development' ? 'selected' : '' }}>Web Development</option>
                        <option value="digital_marketing" {{ old('type') === 'digital_marketing' ? 'selected' : '' }}>Digital Marketing</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="default_price" class="block text-sm font-medium text-slate-700">Default Price (BDT)</label>
                    <div class="relative mt-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">৳</span>
                        <input type="number" name="default_price" id="default_price" value="{{ old('default_price') }}" step="0.01" min="0" required
                            class="block w-full rounded-xl border border-slate-200 pl-8 pr-4 py-2 text-sm focus:border-slate-400 focus:outline-none"
                            placeholder="0.00">
                    </div>
                    @error('default_price')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none"
                        placeholder="Service description...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked
                        class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500">
                    <label for="is_active" class="ml-2 text-sm text-slate-700">Active (available for invoices)</label>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('accounts.services.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Create Service
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
