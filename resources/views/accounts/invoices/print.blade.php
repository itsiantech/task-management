<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; padding: 32px; }
    .header { display: table; width: 100%; margin-bottom: 24px; border-bottom: 3px solid #0f172a; padding-bottom: 16px; }
    .company, .invoice-meta { display: table-cell; vertical-align: top; }
    .invoice-meta { text-align: right; }
    .company h1 { font-size: 22px; }
    .company p { color: #64748b; font-size: 11px; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; background: #f1f5f9; }
    .parties { display: table; width: 100%; margin-bottom: 20px; }
    .party { display: table-cell; width: 50%; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.items th { background: #0f172a; color: #fff; text-align: left; padding: 8px; font-size: 11px; }
    table.items td { border-bottom: 1px solid #e2e8f0; padding: 8px; }
    .num { text-align: right; }
    .totals { width: 280px; margin-left: auto; }
    .totals div { display: table; width: 100%; padding: 4px 0; }
    .totals span { display: table-cell; }
    .totals span:last-child { text-align: right; }
    .grand { font-weight: bold; font-size: 14px; border-top: 2px solid #0f172a; margin-top: 4px; padding-top: 6px; }
    .footer { margin-top: 32px; color: #64748b; font-size: 10px; text-align: center; }
    @media print { body { padding: 0; } }
</style>
</head>
<body>
    <div class="header">
        <div class="company">
            @if(!empty($company['logo']))<img src="{{ $company['logo'] }}" alt="logo" style="height: 48px; margin-bottom: 8px;">@endif
            <h1>{{ $company['name'] }}</h1>
            <p>{{ $company['address'] }}</p>
            <p>{{ $company['phone'] }} · {{ $company['email'] }}</p>
        </div>
        <div class="invoice-meta">
            <h2>INVOICE</h2>
            <p><strong>{{ $invoice->invoice_number }}</strong></p>
            <p><span class="badge">{{ strtoupper($invoice->status_enum?->label() ?? $invoice->status) }}</span></p>
            <p>Date: {{ $invoice->invoice_date?->format('d M Y') }}</p>
            <p>Due: {{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <strong>Billed To</strong>
            <p>{{ $invoice->billing_company ?? $invoice->client?->name }}</p>
            <p>{{ $invoice->billing_name ?? $invoice->client?->contact_person }}</p>
            <p>{{ $invoice->billing_address ?? $invoice->client?->address }}</p>
            <p>{{ $invoice->billing_phone ?? $invoice->client?->phone }} · {{ $invoice->billing_email ?? $invoice->client?->email }}</p>
            <p>{{ $invoice->billing_country ?? 'Bangladesh' }}</p>
        </div>
        <div class="party" style="text-align: right;">
            <strong>Payment Method</strong>
            <p>{{ $invoice->paymentMethodLabel() }}</p>
            @if($invoice->task)<p>Order: {{ $invoice->task->order_number_label }}</p>@endif
        </div>
    </div>

    <table class="items">
        <thead><tr><th>#</th><th>Description</th><th class="num">Qty</th><th class="num">Unit Price</th><th class="num">Discount</th><th class="num">Tax</th><th class="num">Line Total</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->description }}@if($item->service)<br><small>{{ $item->service->type_label }}: {{ $item->service->name }}</small>@endif</td>
                    <td class="num">{{ $item->qty }}</td>
                    <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">{{ number_format($item->discount, 2) }}</td>
                    <td class="num">{{ number_format($item->tax, 2) }}</td>
                    <td class="num">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal (BDT)</span><span>{{ number_format($invoice->sub_total, 2) }}</span></div>
        <div><span>Advance Credit (BDT)</span><span>{{ number_format($invoice->advance_paid_credit, 2) }}</span></div>
        <div><span>Total (BDT)</span><span>{{ number_format($invoice->total_amount, 2) }}</span></div>
        <div class="grand"><span>Balance Due (BDT)</span><span>{{ number_format($invoice->total_due, 2) }}</span></div>
    </div>

    @if($invoice->notes)<p style="margin-top: 16px;"><strong>Notes:</strong> {{ $invoice->notes }}</p>@endif

    <div class="footer">Thank you for your business · Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
