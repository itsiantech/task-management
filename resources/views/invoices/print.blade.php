<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice {{ $invoice->invoice_number_label }}</title>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                background: #f8fafc;
                color: #0f172a;
                margin: 0;
                padding: 30px;
            }
            .invoice-shell {
                max-width: 960px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                padding: 32px;
                box-shadow: 0 18px 35px rgba(15, 23, 42, 0.08);
            }
            .header, .meta-row, .info-grid, .totals {
                display: flex;
                justify-content: space-between;
                gap: 20px;
            }
            .header {
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 20px;
                align-items: flex-start;
            }
            .logo img {
                height: 54px;
                width: auto;
            }
            .badge {
                display: inline-block;
                padding: 8px 12px;
                border-radius: 999px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.18em;
                border: 1px solid #e2e8f0;
                background: #f1f5f9;
                color: #334155;
            }
            .badge.paid { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
            .badge.unpaid { background: #fef3c7; color: #92400e; border-color: #fde68a; }
            .badge.due { background: #ffedd5; color: #9a5b00; border-color: #fdba74; }
            .badge.refunded { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
            .badge.cancelled { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
            .meta-row {
                margin-top: 28px;
            }
            .invoice-number {
                font-size: 36px;
                font-weight: 700;
                margin: 8px 0 0;
            }
            .info-grid {
                margin-top: 28px;
                gap: 18px;
            }
            .info-box {
                flex: 1;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 18px;
            }
            .label {
                color: #64748b;
                font-size: 11px;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                margin-bottom: 14px;
                font-weight: 700;
            }
            .info-box p {
                margin: 4px 0;
                color: #334155;
                font-size: 14px;
            }
            .table-wrap {
                margin-top: 32px;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                overflow: hidden;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            thead { background: #f8fafc; }
            th, td { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; text-align: left; }
            th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: #475569; }
            td { font-size: 14px; color: #0f172a; }
            .amount-cell { text-align: right; }
            .totals {
                justify-content: flex-end;
                margin-top: 24px;
            }
            .summary-box {
                width: 320px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                border-radius: 18px;
                padding: 18px;
            }
            .summary-row {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                font-size: 14px;
                color: #334155;
            }
            .summary-row.total {
                border-top: 1px solid #e2e8f0;
                margin-top: 8px;
                padding-top: 16px;
                font-size: 20px;
                font-weight: 700;
                color: #0f172a;
            }
            @media print {
                body {
                    background: #fff;
                    padding: 0;
                }
                .invoice-shell {
                    box-shadow: none;
                    border: none;
                    border-radius: 0;
                    padding: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="invoice-shell">
            <div class="header">
                <div class="logo">
                    <img src="{{ asset('images/itsian-logo.svg') }}" alt="ITSIAN" />
                </div>
                <div style="text-align: right;">
                    <div class="badge {{ strtolower(str_replace(' ', '-', $invoice->status)) }}">{{ strtoupper($invoice->status) }}</div>
                    <div style="margin-top: 12px; font-size: 14px; color: #475569;">Due Date: <strong>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Not set' }}</strong></div>
                </div>
            </div>

            <div class="meta-row">
                <div>
                    <div class="label">Invoice</div>
                    <div class="invoice-number">{{ $invoice->invoice_number_label }}</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-box">
                    <div class="label">Invoiced To</div>
                    <p><strong>{{ $invoice->client?->name ?? 'Client Name' }}</strong></p>
                    <p>{{ $invoice->client?->name ?? 'Company Name' }}</p>
                    <p>{{ $invoice->client?->address ?? 'Address not provided' }}</p>
                    <p>{{ $invoice->client?->phone ?? 'Phone not provided' }}</p>
                    <p>{{ $invoice->client?->email ?? 'Email not provided' }}</p>
                    <p>Country: Bangladesh</p>
                </div>
                <div class="info-box">
                    <div class="label">Pay To (ITSIAN)</div>
                    <p><strong>ITSIAN</strong></p>
                    <p>House-09, 4th floor, Road 1/b,</p>
                    <p>Block-L Banani, Chairmanbari, Dhaka</p>
                </div>
            </div>

            <div class="info-grid" style="margin-top: 22px;">
                <div class="info-box">
                    <div class="label">Invoice Date</div>
                    <p>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '—' }}</p>
                </div>
                <div class="info-box">
                    <div class="label">Payment Method</div>
                    <p>{{ $invoice->paymentMethodLabel() }}</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="amount-cell">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="amount-cell">{{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="totals">
                <div class="summary-box">
                    <div class="summary-row"><span>Sub Total</span><span>{{ number_format((float) $invoice->sub_total, 2) }}</span></div>
                    <div class="summary-row"><span>Credit</span><span>{{ number_format((float) $invoice->advance_paid_credit, 2) }}</span></div>
                    <div class="summary-row total"><span>Total</span><span>{{ number_format((float) $invoice->total_amount, 2) }}</span></div>
                </div>
            </div>

            <div class="table-wrap" style="margin-top: 32px;">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Gateway</th>
                            <th>Transaction ID</th>
                            <th class="amount-cell">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '—' }}</td>
                                <td>{{ ucfirst($transaction->gateway) }}</td>
                                <td>{{ $transaction->transaction_id ?? 'N/A' }}</td>
                                <td class="amount-cell">{{ number_format((float) $transaction->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="totals" style="margin-top: 24px;">
                <div class="summary-box" style="background: #0f172a; color: #fff; border-color: #0f172a;">
                    <div class="summary-row" style="color: #cbd5e1;"><span>Balance Due</span><span>{{ number_format((float) $invoice->total_due, 2) }}</span></div>
                </div>
            </div>
        </div>
    </body>
</html>
