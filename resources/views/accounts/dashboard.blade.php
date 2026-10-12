@extends('layouts.app')

@section('title', 'Accounts Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Accounts Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Income, expenses, dues and balances at a glance (৳ BDT)</p>
        </div>
        <a href="{{ route('accounts.reports.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">View Reports</a>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @php
            $cards = [
                ['label' => "Today's Income", 'value' => $data['today_income'], 'color' => 'text-emerald-600'],
                ['label' => 'This Month Income', 'value' => $data['month_income'], 'color' => 'text-slate-900'],
                ['label' => 'This Month Expense', 'value' => $data['month_expense'], 'color' => 'text-rose-600'],
                ['label' => 'Month Profit/Loss', 'value' => $data['month_profit'], 'color' => $data['month_profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600'],
                ['label' => 'Total Receivable (Due)', 'value' => $data['receivable'], 'color' => 'text-amber-600'],
                ['label' => 'Total Payable', 'value' => $data['payable'], 'color' => 'text-orange-600'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold {{ $card['color'] }}">৳{{ number_format($card['value'], 2) }}</p>
            </div>
        @endforeach
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Pending Orders</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $data['pending_orders'] }}</p>
            <a href="{{ route('accounts.orders.index') }}" class="mt-1 inline-block text-xs font-medium text-violet-600 hover:text-violet-800">View orders →</a>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Invoices</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $data['invoice_summary']->totalInvoices }}</p>
            <a href="{{ route('accounts.invoices.index') }}" class="mt-1 inline-block text-xs font-medium text-violet-600 hover:text-violet-800">View invoices →</a>
        </div>
    </div>

    <!-- Method balances -->
    <h2 class="text-lg font-semibold text-slate-900 mb-3">Account Balances (bKash / Nagad / Bank / Cash)</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach($data['method_balances'] as $method => $b)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-900 capitalize">{{ $method === 'bkash' ? 'bKash' : ucfirst($method) }}</p>
                <div class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Received</span><span class="font-medium text-emerald-600">৳{{ number_format($b['received'], 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Spent</span><span class="font-medium text-rose-600">৳{{ number_format($b['spent'], 2) }}</span></div>
                    <div class="flex justify-between border-t border-slate-100 pt-1"><span class="text-slate-500">Balance</span><span class="font-semibold text-slate-900">৳{{ number_format($b['balance'], 2) }}</span></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <!-- Revenue per service -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Revenue per Service</h2>
            <canvas id="serviceChart" height="220"></canvas>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr><th class="py-2 text-left">Service</th><th class="py-2 text-right">Billed</th><th class="py-2 text-right">Paid</th><th class="py-2 text-right">Due</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($data['service_revenue'] as $row)
                            <tr>
                                <td class="py-2 font-medium text-slate-900">{{ $row['label'] }}</td>
                                <td class="py-2 text-right">৳{{ number_format($row['billed'], 2) }}</td>
                                <td class="py-2 text-right text-emerald-600">৳{{ number_format($row['paid'], 2) }}</td>
                                <td class="py-2 text-right text-amber-600">৳{{ number_format($row['due'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Invoice status summary -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Invoices by Status</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Enums\InvoiceStatus::cases() as $status)
                        @php $count = $status === \App\Enums\InvoiceStatus::DRAFT ? $data['draft_count'] : ($data['invoice_summary']->toArray()[strtolower($status->name) . '_count'] ?? 0); @endphp
                        <a href="{{ route('accounts.invoices.index', ['status' => $status->value]) }}" class="rounded-full px-3 py-1.5 text-xs font-medium {{ $status->color() }}">
                            {{ $status->label() }}: {{ $count }}
                        </a>
                    @endforeach
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Billed</p><p class="font-semibold">৳{{ number_format($data['invoice_summary']->totalBilled, 2) }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Paid</p><p class="font-semibold text-emerald-600">৳{{ number_format($data['invoice_summary']->totalPaid, 2) }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Due</p><p class="font-semibold text-amber-600">৳{{ number_format($data['invoice_summary']->totalDue, 2) }}</p></div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Top Dues Receivable</h2>
                @forelse($data['due_invoices'] as $invoice)
                    <a href="{{ route('accounts.invoices.show', $invoice) }}" class="flex items-center justify-between border-b border-slate-100 py-2 last:border-0 hover:bg-slate-50">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $invoice->invoice_number }}</p>
                            <p class="text-xs text-slate-500">{{ $invoice->client?->name ?? '—' }}</p>
                        </div>
                        <p class="text-sm font-semibold text-amber-600">৳{{ number_format($invoice->total_due, 2) }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No outstanding dues. 🎉</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Recent Payments</h2>
            @forelse($data['recent_payments'] as $payment)
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                    <div><p class="font-medium text-slate-900">{{ $payment->client?->name ?? '—' }}</p><p class="text-xs text-slate-500">{{ $payment->payment_date?->format('d M Y') }} · {{ ucfirst($payment->method) }}</p></div>
                    <p class="font-semibold text-emerald-600">৳{{ number_format($payment->amount, 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No payments yet.</p>
            @endforelse
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Recent Expenses</h2>
            @forelse($data['recent_expenses'] as $expense)
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                    <div><p class="font-medium text-slate-900">{{ $expense->title }}</p><p class="text-xs text-slate-500">{{ $expense->expense_date?->format('d M Y') }} · {{ $expense->category_label }}</p></div>
                    <p class="font-semibold text-rose-600">৳{{ number_format($expense->amount, 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No expenses yet.</p>
            @endforelse
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Recent Activity</h2>
            @forelse($data['recent_activity'] as $log)
                <div class="border-b border-slate-100 py-2 text-sm last:border-0">
                    <p class="text-slate-800">{{ $log->description ?? $log->action }}</p>
                    <p class="text-xs text-slate-500">{{ $log->actor?->name ?? 'System' }} · {{ $log->created_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
(function () {
    const el = document.getElementById('serviceChart');
    if (!el || typeof Chart === 'undefined') return;
    const revenue = @json($data['service_revenue']);
    new Chart(el, {
        type: 'bar',
        data: {
            labels: Object.values(revenue).map(r => r.label),
            datasets: [
                { label: 'Billed (৳)', data: Object.values(revenue).map(r => r.billed), backgroundColor: '#6366f1' },
                { label: 'Paid (৳)', data: Object.values(revenue).map(r => r.paid), backgroundColor: '#10b981' },
            ],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
    });
})();
</script>
@endsection
