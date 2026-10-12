@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.invoices.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Invoices</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Create Invoice</h1>
        <p class="text-sm text-slate-500 mt-1">Invoice number is generated automatically (e.g. INV-2026-0001)</p>
    </div>

    <form method="POST" action="{{ route('accounts.invoices.store') }}">
        @csrf
        @include('accounts.invoices._form')
    </form>
</div>
@endsection
