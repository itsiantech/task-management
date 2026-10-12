@extends('layouts.app')

@section('title', 'Edit ' . $invoice->invoice_number)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.invoices.show', $invoice) }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Invoice</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Edit {{ $invoice->invoice_number }}</h1>
    </div>

    <form method="POST" action="{{ route('accounts.invoices.update', $invoice) }}">
        @csrf
        @method('PUT')
        @include('accounts.invoices._form', ['invoice' => $invoice])
    </form>
</div>
@endsection
