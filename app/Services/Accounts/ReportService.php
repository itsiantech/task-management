<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\DTOs\Accounts\InvoiceSummaryDTO;
use App\DTOs\Accounts\ReportDataDTO;
use App\Enums\ExpenseCategory;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceType;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Payment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Task;
use Carbon\Carbon;

class ReportService
{
    /**
     * Resolve a named period into [from, to] date strings (Y-m-d).
     *
     * @return array{from: string, to: string, label: string}
     */
    public function resolvePeriod(string $preset, ?string $from = null, ?string $to = null): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'daily' => ['from' => $today->toDateString(), 'to' => $today->toDateString(), 'label' => 'Today'],
            'weekly' => ['from' => $today->copy()->subDays(6)->toDateString(), 'to' => $today->toDateString(), 'label' => 'Last 7 days'],
            'yearly' => ['from' => $today->copy()->startOfYear()->toDateString(), 'to' => $today->copy()->endOfYear()->toDateString(), 'label' => $today->format('Y')],
            'custom' => [
                'from' => $from ?: $today->copy()->startOfMonth()->toDateString(),
                'to' => $to ?: $today->toDateString(),
                'label' => 'Custom range',
            ],
            default => ['from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->toDateString(), 'label' => $today->format('F Y')],
        };
    }

    public function invoiceSummary(): InvoiceSummaryDTO
    {
        $counts = Invoice::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        $receivable = Invoice::where('status', '!=', InvoiceStatus::VOID->value);
        $billed = (float) (clone $receivable)->sum('total_amount');
        $due = (float) (clone $receivable)->sum('total_due');

        return InvoiceSummaryDTO::fromArray([
            'total_invoices' => array_sum($counts),
            'total_billed' => $billed,
            'total_paid' => $billed - $due,
            'total_due' => $due,
            'unpaid_count' => $get('unpaid'),
            'partial_count' => $get('partial'),
            'paid_count' => $get('paid'),
            'due_count' => $get('due'),
            'hold_count' => $get('hold'),
            'void_count' => $get('void'),
        ]);
    }

    public function build(string $from, string $to): array
    {
        $income = (float) Payment::whereBetween('payment_date', [$from, $to])->sum('amount');
        $expense = (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
        $receivable = (float) Invoice::where('status', '!=', InvoiceStatus::VOID->value)->sum('total_due');
        $payable = (float) Expense::where('is_paid', false)->sum('payable_amount');
        $pendingOrders = Task::whereIn('payment_status', ['pending', 'partial'])->count();

        $incomeByService = $this->incomeByService($from, $to);
        $expenseByCategory = Expense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category')->all();
        $incomeByMethod = Payment::whereBetween('payment_date', [$from, $to])
            ->selectRaw('method, SUM(amount) as total')->groupBy('method')->pluck('total', 'method')->all();

        $summary = ReportDataDTO::fromArray([
            'income' => $income,
            'expense' => $expense,
            'net_profit' => $income - $expense,
            'receivable_due' => $receivable,
            'payable' => $payable,
            'pending_orders' => $pendingOrders,
            'income_by_service' => array_map('floatval', $incomeByService),
            'expense_by_category' => array_map('floatval', $expenseByCategory),
            'income_by_method' => array_map('floatval', $incomeByMethod),
        ]);

        return [
            'summary' => $summary,
            'per_client' => $this->perClient(),
            'recent_payments' => Payment::with(['client', 'invoice'])->orderByDesc('payment_date')->orderByDesc('id')->limit(10)->get(),
            'recent_expenses' => Expense::orderByDesc('expense_date')->orderByDesc('id')->limit(10)->get(),
            'trend' => $this->trend($from, $to),
        ];
    }

    /**
     * Revenue per service type from invoice items in period (by invoice date).
     *
     * @return array<string, float>
     */
    public function incomeByService(string $from, string $to): array
    {
        $rows = InvoiceItem::selectRaw('invoice_items.line_total, services.type as service_type')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('services', 'services.id', '=', 'invoice_items.service_id')
            ->where('invoices.status', '!=', InvoiceStatus::VOID->value)
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->get();

        $result = [];
        foreach (ServiceType::cases() as $type) {
            $result[$type->value] = 0.0;
        }
        $result['other'] = 0.0;

        foreach ($rows as $row) {
            $key = $row->service_type ?: 'other';
            $result[$key] = ($result[$key] ?? 0) + (float) $row->line_total;
        }

        return $result;
    }

    /**
     * Per-service billed / paid / due. Paid is allocated from invoice
     * payments proportionally across that invoice's items.
     *
     * @return array<string, array{label: string, billed: float, paid: float, due: float}>
     */
    public function serviceRevenue(): array
    {
        $result = [];
        foreach (ServiceType::cases() as $type) {
            $result[$type->value] = ['label' => $type->label(), 'billed' => 0.0, 'paid' => 0.0, 'due' => 0.0];
        }
        $result['other'] = ['label' => 'Other', 'billed' => 0.0, 'paid' => 0.0, 'due' => 0.0];

        $invoices = Invoice::where('status', '!=', InvoiceStatus::VOID->value)
            ->with('items.service')->get();

        foreach ($invoices as $invoice) {
            $itemsTotal = (float) $invoice->items->sum('line_total');
            if ($itemsTotal <= 0) {
                continue;
            }
            $paidTotal = max(0, (float) $invoice->total_amount - (float) $invoice->total_due);

            foreach ($invoice->items as $item) {
                $key = $item->service?->type ?: 'other';
                $weight = (float) $item->line_total / $itemsTotal;
                $result[$key]['billed'] += (float) $item->line_total;
                $result[$key]['paid'] += $paidTotal * $weight;
            }
        }

        foreach ($result as $key => $row) {
            $result[$key]['billed'] = round($row['billed'], 2);
            $result[$key]['paid'] = round($row['paid'], 2);
            $result[$key]['due'] = round($row['billed'] - $row['paid'], 2);
        }

        return $result;
    }

    private function perClient(): \Illuminate\Support\Collection
    {
        return Client::withSum(['invoices as billed' => fn ($q) => $q->where('status', '!=', InvoiceStatus::VOID->value)], 'total_amount')
            ->withSum(['invoices as due' => fn ($q) => $q->where('status', '!=', InvoiceStatus::VOID->value)], 'total_due')
            ->orderByDesc('due')
            ->limit(20)
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'billed' => (float) ($c->billed ?? 0),
                'due' => (float) ($c->due ?? 0),
                'paid' => (float) ($c->billed ?? 0) - (float) ($c->due ?? 0),
            ]);
    }

    /**
     * Daily income vs expense series for charts.
     *
     * @return array{labels: array, income: array, expense: array}
     */
    private function trend(string $from, string $to): array
    {
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $days = min($start->diffInDays($end) + 1, 62);

        $incomeRows = Payment::whereBetween('payment_date', [$from, $to])
            ->selectRaw('payment_date as d, SUM(amount) as total')->groupBy('payment_date')->pluck('total', 'd')->all();
        $expenseRows = Expense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('expense_date as d, SUM(amount) as total')->groupBy('expense_date')->pluck('total', 'd')->all();

        $labels = [];
        $income = [];
        $expense = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $income[] = (float) ($incomeRows[$key] ?? 0);
            $expense[] = (float) ($expenseRows[$key] ?? 0);
        }

        return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
    }

    public function methodOptions(): array
    {
        return array_map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()], PaymentMethod::cases());
    }

    public function categoryOptions(): array
    {
        return array_map(fn (ExpenseCategory $c) => ['value' => $c->value, 'label' => $c->label()], ExpenseCategory::cases());
    }
}
