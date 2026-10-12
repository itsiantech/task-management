<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Accounts Report {{ $period['from'] }} to {{ $period['to'] }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; padding: 28px; }
    h1 { font-size: 20px; margin-bottom: 2px; }
    .sub { color: #64748b; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th { background: #0f172a; color: #fff; text-align: left; padding: 6px 8px; }
    td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; }
    .num { text-align: right; }
    h2 { font-size: 14px; margin: 14px 0 6px; }
</style>
</head>
<body>
    @php $s = $report['summary']; @endphp
    <h1>Accounts Report — {{ $period['label'] }}</h1>
    <p class="sub">{{ $period['from'] }} → {{ $period['to'] }} · Generated {{ now()->format('d M Y H:i') }}</p>

    <h2>Summary (BDT)</h2>
    <table>
        <tr><th>Metric</th><th class="num">Amount</th></tr>
        <tr><td>Total Income</td><td class="num">{{ number_format($s->income, 2) }}</td></tr>
        <tr><td>Total Expense</td><td class="num">{{ number_format($s->expense, 2) }}</td></tr>
        <tr><td>Net Profit / Loss</td><td class="num">{{ number_format($s->netProfit, 2) }}</td></tr>
        <tr><td>Receivable Due</td><td class="num">{{ number_format($s->receivableDue, 2) }}</td></tr>
        <tr><td>Payable</td><td class="num">{{ number_format($s->payable, 2) }}</td></tr>
    </table>

    <h2>Income by Service</h2>
    <table>
        <tr><th>Service</th><th class="num">Revenue (BDT)</th></tr>
        @foreach($s->incomeByService as $service => $total)
            <tr><td>{{ $service }}</td><td class="num">{{ number_format((float) $total, 2) }}</td></tr>
        @endforeach
    </table>

    <h2>Expense by Category</h2>
    <table>
        <tr><th>Category</th><th class="num">Amount (BDT)</th></tr>
        @foreach($s->expenseByCategory as $category => $total)
            <tr><td>{{ $category }}</td><td class="num">{{ number_format((float) $total, 2) }}</td></tr>
        @endforeach
    </table>

    <h2>Income by Method</h2>
    <table>
        <tr><th>Method</th><th class="num">Amount (BDT)</th></tr>
        @foreach($s->incomeByMethod as $method => $total)
            <tr><td>{{ $method }}</td><td class="num">{{ number_format((float) $total, 2) }}</td></tr>
        @endforeach
    </table>
</body>
</html>
