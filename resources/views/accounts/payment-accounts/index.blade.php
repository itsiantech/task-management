@extends('layouts.app')

@section('title', 'Payment Accounts')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payment Methods & Accounts</h1>
            <p class="text-sm text-slate-500 mt-1">bKash, Nagad, Bank, Cash — received, spent and balance</p>
        </div>
        <button onclick="document.getElementById('addAccountModal').classList.remove('hidden')" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">+ Add Account</button>
    </div>

    <!-- Method balances -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach($methodBalances as $method => $b)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold capitalize">{{ $method === 'bkash' ? 'bKash' : ucfirst($method) }}</p>
                <div class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Received</span><span class="font-medium text-emerald-600">৳{{ number_format($b['received'], 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Spent</span><span class="font-medium text-rose-600">৳{{ number_format($b['spent'], 2) }}</span></div>
                    <div class="flex justify-between border-t border-slate-100 pt-1"><span class="text-slate-500">Balance</span><span class="font-semibold">৳{{ number_format($b['balance'], 2) }}</span></div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Accounts table -->
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3 text-left">Account</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Number</th><th class="px-4 py-3 text-right">Balance</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($accounts as $account)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $account->name }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium">{{ $account->type_label }}</span></td>
                        <td class="px-4 py-3">{{ $account->account_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">৳{{ number_format($account->balance, 2) }}</td>
                        <td class="px-4 py-3">@if($account->is_active)<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Active</span>@else<span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">Inactive</span>@endif</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1 text-xs font-medium">
                                <button onclick="openEditAccount({{ $account->id }})" class="rounded-lg px-2 py-1 text-violet-600 hover:bg-violet-50">Edit</button>
                                <form method="POST" action="{{ route('accounts.payment-accounts.destroy', $account) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="rounded-lg px-2 py-1 text-rose-600 hover:bg-rose-50">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No accounts yet. Add your bKash, Nagad, Bank and Cash accounts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add modal -->
<div id="addAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('addAccountModal').classList.add('hidden')"></div>
    <form method="POST" action="{{ route('accounts.payment-accounts.store') }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        @csrf
        <h3 class="mb-4 text-lg font-semibold">Add Account</h3>
        <div class="space-y-3">
            <div><label class="text-sm font-medium">Name *</label><input type="text" name="name" required placeholder="e.g. Personal bKash" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Type *</label>
                <select name="type" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    @foreach($types as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Account Number</label><input type="text" name="account_number" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Notes</label><input type="text" name="notes" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4"> Active</label>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('addAccountModal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</button>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save</button>
        </div>
    </form>
</div>

<!-- Edit modal -->
<div id="editAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('editAccountModal').classList.add('hidden')"></div>
    <form id="editAccountForm" method="POST" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        @csrf
        @method('PUT')
        <h3 class="mb-4 text-lg font-semibold">Edit Account</h3>
        <div class="space-y-3">
            <div><label class="text-sm font-medium">Name *</label><input type="text" name="name" id="edit_name" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Type *</label>
                <select name="type" id="edit_type" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    @foreach($types as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Account Number</label><input type="text" name="account_number" id="edit_number" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Notes</label><input type="text" name="notes" id="edit_notes" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" id="edit_active" value="1" class="h-4 w-4"> Active</label>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('editAccountModal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</button>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Update</button>
        </div>
    </form>
</div>

@php
$accountsJson = $accounts->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'type' => $a->type, 'account_number' => $a->account_number, 'notes' => $a->notes, 'is_active' => $a->is_active]);
@endphp
<script>
const accountsData = @json($accountsJson);
function openEditAccount(id) {
    const a = accountsData.find(x => x.id === id);
    if (!a) return;
    document.getElementById('editAccountForm').action = '{{ url('admin/accounts/payment-accounts') }}/' + id;
    document.getElementById('edit_name').value = a.name || '';
    document.getElementById('edit_type').value = a.type || 'cash';
    document.getElementById('edit_number').value = a.account_number || '';
    document.getElementById('edit_notes').value = a.notes || '';
    document.getElementById('edit_active').checked = !!a.is_active;
    document.getElementById('editAccountModal').classList.remove('hidden');
}
</script>
@endsection
