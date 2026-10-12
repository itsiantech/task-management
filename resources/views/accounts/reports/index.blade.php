@extends('layouts.app')

@section('title', 'Accounts Reports')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Reports</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $period['label'] }} ({{ $period['from'] }} → {{ $period['to'] }})</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('accounts.reports.export', array_merge(['type' => 'csv'], request()->query())) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-medium text-slate-700 hover:bg-slate-50">CSV</a>
            <a href="{{ route('accounts.reports.export', array_merge(['type' => 'excel'], request()->query())) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-medium text-slate-700 hover:bg-slate-50">Excel</a>
            <a href="{{ route('accounts.reports.export', array_merge(['type' => 'pdf'], request()->query())) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-medium text-slate-700 hover:bg-slate-50">PDF</a>
        </div>
    </div>

    <!-- Period switcher -->
    <form method="GET" class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom'] as $value => $label)
            <a href="{{ route('accounts.reports.index', ['period' => $value]) }}" class="rounded-full px-4 py-1.5 text-sm font-medium {{ $preset === $value ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
        @endforeach
        <span class="mx-1 hidden text-slate-300 sm:inline">|</span>
        <input type="hidden" name="period" value="custom">
        <input type="date" name="from" value="{{ request('from', $period['from']) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm">
        <input type="date" name="to" value="{{ request('to', $period['to']) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm">
        <button class="rounded-xl bg-slate-900 px-4 py-1.5 text-sm font-medium text-white">Apply</button>
    </form>

    @php $s = $report['summary']; @endphp
    <!-- Summary -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Income</p><p class="mt-1 text-xl font-semibold text-emerald-600">৳{{ number_format($s->income, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Expense</p><p class="mt-1 text-xl font-semibold text-rose-600">৳{{ number_format($s->expense, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Net Profit/Loss</p><p class="mt-1 text-xl font-semibold {{ $s->netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">৳{{ number_format($s->netProfit, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Receivable Due</p><p class="mt-1 text-xl font-semibold text-amber-600">৳{{ number_format($s->receivableDue, 2) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Payable</p><p class="mt-1 text-xl font-semibold text-orange-600">৳{{ number_format($s->payable, 2) }}</p></div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Income vs Expense Trend</h2>
            <canvas id="trendChart" height="220"></canvas>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Revenue by Service</h2>
            <canvas id="serviceChart" height="220"></canvas>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Expense by Category</h2>
            <canvas id="expenseChart" height="220"></canvas>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Service Billed / Paid / Due (lifetime)</h2>
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2 text-left">Service</th><th class="py-2 text-right">Billed</th><th class="py-2 text-right">Paid</th><th class="py-2 text-right">Due</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($serviceRevenue as $row)
                        <tr><td class="py-2 font-medium">{{ $row['label'] }}</td><td class="py-2 text-right">৳{{ number_format($row['billed'], 2) }}</td><td class="py-2 text-right text-emerald-600">৳{{ number_format($row['paid'], 2) }}</td><td class="py-2 text-right text-amber-600">৳{{ number_format($row['due'], 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Per Client (Top 20 by Due)</h2>
            <div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2 text-left">Client</th><th class="py-2 text-right">Billed</th><th class="py-2 text-right">Paid</th><th class="py-2 text-right">Due</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['per_client'] as $client)
                        <tr><td class="py-2"><a href="{{ route('accounts.clients.show', $client['id']) }}" class="font-medium text-violet-600 hover:text-violet-800">{{ $client['name'] }}</a><p class="text-xs text-slate-500">{{ $client['phone'] }}</p></td><td class="py-2 text-right">৳{{ number_format($client['billed'], 2) }}</td><td class="py-2 text-right text-emerald-600">৳{{ number_format($client['paid'], 2) }}</td><td class="py-2 text-right text-amber-600">৳{{ number_format($client['due'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-500">No client data.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold">Income by Method</h2>
                @forelse($s->incomeByMethod as $method => $total)
                    <div class="flex justify-between border-b border-slate-100 py-1.5 text-sm last:border-0"><span class="capitalize">{{ $method === 'bkash' ? 'bKash' : $method }}</span><span class="font-medium">৳{{ number_format($total, 2) }}</span></div>
                @empty
                    <p class="text-sm text-slate-500">No income in this period.</p>
                @endforelse
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold">Recent Transactions</h2>
                @foreach($report['recent_payments']->take(5) as $payment)
                    <div class="flex justify-between border-b border-slate-100 py-1.5 text-sm last:border-0"><span>{{ $payment->client?->name ?? '—' }} <span class="text-slate-400">· {{ $payment->payment_date?->format('d M') }}</span></span><span class="font-medium text-emerald-600">+৳{{ number_format($payment->amount, 2) }}</span></div>
                @endforeach
                @foreach($report['recent_expenses']->take(5) as $expense)
                    <div class="flex justify-between border-b border-slate-100 py-1.5 text-sm last:border-0"><span>{{ $expense->title }} <span class="text-slate-400">· {{ $expense->expense_date?->format('d M') }}</span></span><span class="font-medium text-rose-600">-৳{{ number_format($expense->amount, 2) }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (typeof Chart === 'undefined') return;
    const trend = @json($report['trend']);
    const incomeByService = @json($s->incomeByService);
    const expenseByCategory = @json($s->expenseByCategory);

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: { labels: trend.labels, datasets: [
            { label: 'Income', data: trend.income, borderColor: '#10b981', backgroundColor: '#10b98122', fill: true, tension: 0.3 },
            { label: 'Expense', data: trend.expense, borderColor: '#f43f5e', backgroundColor: '#f43f5e22', fill: true, tension: 0.3 },
        ]},
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
    });

    new Chart(document.getElementById('serviceChart'), {
        type: 'bar',
        data: { labels: Object.keys(incomeByService), datasets: [{ label: 'Revenue (৳)', data: Object.values(incomeByService), backgroundColor: '#6366f1' }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });

    new Chart(document.getElementById('expenseChart'), {
        type: 'doughnut',
        data: { labels: Object.keys(expenseByCategory), datasets: [{ data: Object.values(expenseByCategory), backgroundColor: ['#f43f5e', '#f59e0b', '#6366f1', '#10b981', '#0ea5e9', '#8b5cf6', '#ec4899', '#64748b', '#14b8a6', '#f97316'] }] },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
})();
</script>
@endsection
