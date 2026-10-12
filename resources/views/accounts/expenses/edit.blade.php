@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.expenses.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Expenses</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Edit Expense</h1>
    </div>

    <form method="POST" action="{{ route('accounts.expenses.update', $expense) }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Title *</label><input type="text" name="title" value="{{ old('title', $expense->title) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium">Category *</label>
                <select name="category" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                    @foreach($categories as $category)<option value="{{ $category->value }}" {{ old('category', $expense->category) === $category->value ? 'selected' : '' }}>{{ $category->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="mb-1 block text-sm font-medium">Custom Category</label><input type="text" name="custom_category" value="{{ old('custom_category', $expense->custom_category) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium">Amount (৳) *</label><input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $expense->amount) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium">Date *</label><input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium">Paid Via *</label>
                <select name="paid_via" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                    @foreach($methods as $method)<option value="{{ $method->value }}" {{ old('paid_via', $expense->paid_via) === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="mb-1 block text-sm font-medium">From Account</label>
                <select name="payment_account_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                    <option value="">—</option>
                    @foreach($accounts as $account)<option value="{{ $account->id }}" {{ (string)old('payment_account_id', $expense->payment_account_id ?? '') === (string)$account->id ? 'selected' : '' }}>{{ $account->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="mb-1 block text-sm font-medium">Paid To</label><input type="text" name="paid_to" value="{{ old('paid_to', $expense->paid_to) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium">Reference</label><input type="text" name="reference" value="{{ old('reference', $expense->reference) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium">Payable Amount</label><input type="number" name="payable_amount" step="0.01" min="0" value="{{ old('payable_amount', $expense->payable_amount) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <div>
                <label class="mb-1 block text-sm font-medium">Attachment</label>
                @if($expense->attachment_url)<p class="mb-1 text-sm"><a href="{{ $expense->attachment_url }}" target="_blank" class="text-violet-600 hover:text-violet-800">View current file →</a></p>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remove_attachment" value="1" class="h-4 w-4"> Remove current file</label>
                @endif
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 w-full text-sm">
            </div>
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Note</label><input type="text" name="note" value="{{ old('note', $expense->note) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_paid" value="1" {{ old('is_paid', $expense->is_paid) ? 'checked' : '' }} class="h-4 w-4"> Paid</label>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('accounts.expenses.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</a>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Update Expense</button>
        </div>
    </form>
</div>
@endsection
