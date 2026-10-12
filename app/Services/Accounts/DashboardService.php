<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Enums\InvoiceStatus;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Payment;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Task;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly PaymentAccountService $accounts,
        private readonly ReportService $reports,
    ) {}

    public function data(): array
    {
        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::today()->startOfMonth()->toDateString();

        $todayIncome = (float) Payment::whereDate('payment_date', $today)->sum('amount');
        $monthIncome = (float) Payment::whereBetween('payment_date', [$monthStart, $today])->sum('amount');
        $monthExpense = (float) Expense::whereBetween('expense_date', [$monthStart, $today])->sum('amount');
        $receivable = (float) Invoice::where('status', '!=', InvoiceStatus::VOID->value)->sum('total_due');
        $payable = (float) Expense::where('is_paid', false)->sum('payable_amount');
        $pendingOrders = Task::whereIn('payment_status', ['pending', 'partial'])->count();

        return [
            'today_income' => $todayIncome,
            'month_income' => $monthIncome,
            'month_expense' => $monthExpense,
            'month_profit' => $monthIncome - $monthExpense,
            'receivable' => $receivable,
            'payable' => $payable,
            'pending_orders' => $pendingOrders,
            'method_balances' => $this->accounts->methodBalances(),
            'service_revenue' => $this->reports->serviceRevenue(),
            'invoice_summary' => $this->reports->invoiceSummary(),
            'draft_count' => Invoice::where('status', InvoiceStatus::DRAFT->value)->count(),
            'due_invoices' => Invoice::with('client')
                ->where('status', '!=', InvoiceStatus::VOID->value)
                ->where('total_due', '>', 0)
                ->orderByDesc('total_due')->limit(8)->get(),
            'recent_payments' => Payment::with(['client', 'invoice'])->orderByDesc('payment_date')->orderByDesc('id')->limit(8)->get(),
            'recent_expenses' => Expense::orderByDesc('expense_date')->orderByDesc('id')->limit(8)->get(),
            'recent_activity' => ActivityLog::with('actor')->orderByDesc('id')->limit(10)->get(),
        ];
    }
}
