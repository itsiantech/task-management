@extends('layouts.app')

@section('title', 'Correct Payment #' . $payment->id)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('accounts.payments.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Payments</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Correct Payment #{{ $payment->id }}</h1>
        <p class="text-sm text-slate-500 mt-1">A correction note is mandatory. Edit history can never be deleted and stays visible to admin.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('accounts.payments.update', $payment) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Amount (৳) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $payment->amount) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                    @error('amount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Payment Date *</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Method *</label>
                    <select name="method" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                        @foreach($methods as $method)<option value="{{ $method->value }}" {{ old('method', $payment->method) === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Receiver Account</label>
                    <select name="receiving_account" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                        <option value="">—</option>
                        @foreach($accounts as $account)<option value="{{ $account->name }}" {{ old('receiving_account', $payment->receiving_account) === $account->name ? 'selected' : '' }}>{{ $account->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Transaction ID</label>
                    <input type="text" name="transaction_id" value="{{ old('transaction_id', $payment->transaction_id) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Note</label>
                    <input type="text" name="note" value="{{ old('note', $payment->note) }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <label class="mb-1 block text-sm font-medium text-amber-800">Correction Note (mandatory) *</label>
                    <textarea name="edit_reason" rows="2" required minlength="5" placeholder='e.g. "Payment of ৳5,000 received on 12 Oct 2026, entered as ৳500 by mistake"' class="w-full rounded-xl border border-amber-200 px-4 py-2.5 text-sm">{{ old('edit_reason') }}</textarea>
                    @error('edit_reason')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">Save Correction</button>
            </div>
        </form>

        <!-- Immutable edit history -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Edit History @if($payment->edits->count() > 0)<span class="ml-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Edited</span>@endif</h2>
            @forelse($payment->edits->sortByDesc('edited_at') as $edit)
                <div class="mb-3 rounded-xl bg-slate-50 p-4 text-sm">
                    <p class="font-medium text-slate-900">{{ $edit->editedBy?->name ?? '—' }} <span class="font-normal text-slate-500">· {{ $edit->edited_at?->format('d M Y H:i') }}</span></p>
                    <p class="mt-1 text-slate-700"><span class="font-medium">Reason:</span> {{ $edit->reason }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg bg-white p-2"><p class="font-semibold text-slate-500">OLD</p>
                            @foreach((array) $edit->old_values as $key => $value)<p><span class="text-slate-500">{{ $key }}:</span> {{ is_scalar($value) ? $value : json_encode($value) }}</p>@endforeach
                        </div>
                        <div class="rounded-lg bg-white p-2"><p class="font-semibold text-emerald-600">NEW</p>
                            @foreach((array) $edit->new_values as $key => $value)<p><span class="text-slate-500">{{ $key }}:</span> {{ is_scalar($value) ? $value : json_encode($value) }}</p>@endforeach
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No edits yet. This payment has never been corrected.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
