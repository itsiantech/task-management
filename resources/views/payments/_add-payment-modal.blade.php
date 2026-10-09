@php
    $paymentPresetId = $paymentPresetId ?? null;
    $paymentOpen = $errors->has('user_id') || $errors->has('amount') || $errors->has('payment_date') || $errors->has('payment_type');
@endphp
<div id="addPaymentModal" class="fixed inset-0 z-50 {{ $paymentOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Add Payment</h2>
            <button type="button" data-close-payment class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
        </div>

        <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="payment_user" class="mb-1 block text-sm font-medium text-slate-700">Member</label>
                <select id="payment_user" name="user_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    @foreach ($paymentMembers as $paymentMember)
                        <option value="{{ $paymentMember->id }}" @selected((string) old('user_id', $paymentPresetId) === (string) $paymentMember->id)>
                            {{ $paymentMember->name }}{{ $paymentMember->member_code ? ' (' . $paymentMember->member_code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="payment_amount" class="mb-1 block text-sm font-medium text-slate-700">Amount ($)</label>
                    <input id="payment_amount" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    @error('amount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_date" class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                    <input id="payment_date" name="payment_date" type="date" required value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    @error('payment_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="payment_type" class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                <select id="payment_type" name="payment_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="salary" @selected(old('payment_type', 'salary') === 'salary')>Salary</option>
                    <option value="advance" @selected(old('payment_type') === 'advance')>Advance</option>
                    <option value="adjustment" @selected(old('payment_type') === 'adjustment')>Adjustment (advance adjust)</option>
                </select>
                @error('payment_type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="payment_notes" class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
                <textarea id="payment_notes" name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close-payment class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('addPaymentModal');
        if (!modal) return;
        const select = modal.querySelector('select[name="user_id"]');
        const show = (userId) => {
            if (userId && select) select.value = userId;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        const hide = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        document.querySelectorAll('[data-add-payment]').forEach((btn) => {
            btn.addEventListener('click', () => show(btn.getAttribute('data-user-id')));
        });
        modal.querySelectorAll('[data-close-payment]').forEach((btn) => btn.addEventListener('click', hide));
        modal.addEventListener('click', (e) => { if (e.target === modal) hide(); });
    })();
</script>
