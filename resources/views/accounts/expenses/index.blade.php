@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Expenses</h1>
            <p class="text-sm text-slate-500 mt-1">Total shown: <span class="font-semibold text-rose-600">৳{{ number_format($total, 2) }}</span> · Still payable: <span class="font-semibold text-orange-600">৳{{ number_format($unpaidTotal, 2) }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounts.expenses.export', request()->query()) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Export CSV</a>
            @can('create', App\Models\Accounts\Expense::class)
                <button onclick="document.getElementById('addExpenseModal').classList.remove('hidden')" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">+ Add Expense</button>
            @endcan
        </div>
    </div>

    <!-- Period presets -->
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach($presets as $key => $label)
            @if($key === 'custom')
                @if($period['preset'] === 'custom')
                    <span class="rounded-full bg-slate-900 px-3.5 py-1.5 text-xs font-medium text-white">Custom: {{ $period['from'] }} → {{ $period['to'] }}</span>
                @endif
            @else
                <a href="{{ route('accounts.expenses.index', array_merge(request()->except(['period', 'from', 'to', 'page']), ['period' => $key])) }}" class="rounded-full px-3.5 py-1.5 text-xs font-medium {{ $period['preset'] === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
            @endif
        @endforeach
    </div>

    <!-- Category-wise breakdown (dashboard style) -->
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Category-wise Expense</h2>
                <p class="text-sm text-slate-500">{{ $period['label'] }} ({{ $period['from'] }} → {{ $period['to'] }}) · Total: <span class="font-semibold text-rose-600">৳{{ number_format($breakdownTotal, 2) }}</span></p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($categories as $category)
                @php $catTotal = $breakdown[$category->value] ?? 0; @endphp
                <a href="{{ route('accounts.expenses.index', array_merge(request()->except(['category', 'page']), ['category' => $category->value])) }}" class="rounded-xl border px-4 py-3 transition {{ ($filters['category'] ?? '') === $category->value ? 'border-violet-400 bg-violet-50' : 'border-slate-100 bg-slate-50/60 hover:border-slate-200 hover:bg-slate-50' }}">
                    <p class="truncate text-xs font-medium text-slate-500">{{ $category->label() }}</p>
                    <p class="mt-0.5 truncate text-lg font-semibold {{ $catTotal > 0 ? 'text-slate-900' : 'text-slate-300' }}">৳{{ number_format($catTotal, 2) }}</p>
                </a>
            @endforeach
        </div>
    </div>

    <form method="GET" class="mb-4 grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        <input type="hidden" name="period" value="{{ $period['preset'] }}">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, vendor, ref..." class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <select name="category" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All categories</option>
            @foreach($categories as $category)<option value="{{ $category->value }}" {{ ($filters['category'] ?? '') === $category->value ? 'selected' : '' }}>{{ $category->label() }}</option>@endforeach
        </select>
        <select name="method" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All methods</option>
            @foreach($methods as $method)<option value="{{ $method->value }}" {{ ($filters['method'] ?? '') === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>@endforeach
        </select>
        <select name="paid_status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">Paid + Due</option>
            <option value="paid" {{ ($filters['paid_status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid only</option>
            <option value="due" {{ ($filters['paid_status'] ?? '') === 'due' ? 'selected' : '' }}>Due only</option>
        </select>
        <input type="date" name="from" value="{{ $period['preset'] === 'custom' ? ($filters['from'] ?? '') : '' }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ $period['preset'] === 'custom' ? ($filters['to'] ?? '') : '' }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <div class="flex gap-2">
            <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Filter</button>
            <a href="{{ route('accounts.expenses.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[960px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Paid To</th><th class="px-4 py-3 text-left">From</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($expenses as $expense)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">{{ $expense->expense_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium">{{ $expense->category_label }}</span></td>
                        <td class="px-4 py-3 font-medium">{{ $expense->title }}</td>
                        <td class="px-4 py-3">{{ $expense->paid_to ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ ucfirst($expense->paid_via) }}@if($expense->paymentAccount)<br><span class="text-slate-500">{{ $expense->paymentAccount->name }}</span>@endif</td>
                        <td class="px-4 py-3 text-right font-semibold text-rose-600">৳{{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-3">@if($expense->is_paid)<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Paid</span>@else<span class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">Due ৳{{ number_format($expense->payable_amount, 2) }}</span>@endif</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1 text-xs font-medium">
                                <form method="POST" action="{{ route('accounts.expenses.toggle-paid', $expense) }}">@csrf @method('PATCH')<button class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100">{{ $expense->is_paid ? 'Mark Due' : 'Mark Paid' }}</button></form>
                                <a href="{{ route('accounts.expenses.edit', $expense) }}" class="rounded-lg px-2 py-1 text-violet-600 hover:bg-violet-50">Edit</a>
                                <form method="POST" action="{{ route('accounts.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="rounded-lg px-2 py-1 text-rose-600 hover:bg-rose-50">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No expenses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($expenses->hasPages())
        <div class="mt-4">{{ $expenses->links() }}</div>
    @endif
</div>

@can('create', App\Models\Accounts\Expense::class)
<div id="addExpenseModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('addExpenseModal').classList.add('hidden')"></div>
    <form method="POST" action="{{ route('accounts.expenses.store') }}" enctype="multipart/form-data" class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
        @csrf
        <h3 class="mb-4 text-lg font-semibold">Add Expense</h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="text-sm font-medium">Title *</label><input type="text" name="title" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Category *</label>
                <select name="category" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    @foreach($categories as $category)<option value="{{ $category->value }}">{{ $category->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Custom Category</label><input type="text" name="custom_category" placeholder="If category = Custom" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Amount (৳) *</label><input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Date *</label><input type="date" name="expense_date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Paid Via *</label>
                <select name="paid_via" required class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    @foreach($methods as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">From Account</label>
                <select name="payment_account_id" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm">
                    <option value="">—</option>
                    @foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">Paid To (vendor/person)</label><input type="text" name="paid_to" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Reference</label><input type="text" name="reference" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Payable (if due)</label><input type="number" name="payable_amount" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <div><label class="text-sm font-medium">Attachment</label><input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 w-full text-sm"></div>
            <div class="sm:col-span-2"><label class="text-sm font-medium">Note</label><input type="text" name="note" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"></div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_paid" value="1" checked class="h-4 w-4"> Already paid (uncheck if still due)</label>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Cancel</button>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save Expense</button>
        </div>
    </form>
</div>
@endcan
@endsection
