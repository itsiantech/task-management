<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\ExpenseRequest;
use App\Models\Accounts\Expense;
use App\Models\Accounts\PaymentAccount;
use App\Repositories\Accounts\ExpenseRepository;
use App\Services\Accounts\ExpenseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    public const PERIODS = [
        'month' => 'Running Month',
        '7days' => 'Last 7 Days',
        '15days' => 'Last 15 Days',
        '30days' => 'Last 30 Days',
        '3months' => 'Last 3 Months',
        '6months' => 'Last 6 Months',
        '1year' => 'Last 1 Year',
        'custom' => 'Custom Range',
    ];

    public function __construct(
        private readonly ExpenseRepository $repository,
        private readonly ExpenseService $service,
    ) {}

    /**
     * Resolve a period preset (or manual dates) into a from/to range.
     *
     * @return array{preset: string, label: string, from: string, to: string}
     */
    private function resolveRange(?string $preset, ?string $from, ?string $to): array
    {
        $today = Carbon::today();

        if ($from || $to) {
            return [
                'preset' => 'custom',
                'label' => self::PERIODS['custom'],
                'from' => $from ?: $today->copy()->startOfMonth()->toDateString(),
                'to' => $to ?: $today->toDateString(),
            ];
        }

        if (! array_key_exists((string) $preset, self::PERIODS) || $preset === 'custom') {
            $preset = 'month';
        }

        $start = match ($preset) {
            '7days' => $today->copy()->subDays(6),
            '15days' => $today->copy()->subDays(14),
            '30days' => $today->copy()->subDays(29),
            '3months' => $today->copy()->subDays(89),
            '6months' => $today->copy()->subDays(179),
            '1year' => $today->copy()->subDays(364),
            default => $today->copy()->startOfMonth(),
        };

        return [
            'preset' => $preset,
            'label' => self::PERIODS[$preset],
            'from' => $start->toDateString(),
            'to' => $today->toDateString(),
        ];
    }

    public function index(Request $request): View
    {
        $range = $this->resolveRange($request->input('period'), $request->input('from'), $request->input('to'));
        $base = $request->only(['category', 'method', 'paid_status', 'search']);
        $filters = array_merge($base, ['from' => $range['from'], 'to' => $range['to']]);
        $expenses = $this->repository->paginate($filters);
        $breakdown = $this->repository->categoryTotals($filters);

        return view('accounts.expenses.index', [
            'expenses' => $expenses,
            'filters' => array_merge($base, ['period' => $range['preset'], 'from' => $range['from'], 'to' => $range['to']]),
            'period' => $range,
            'presets' => self::PERIODS,
            'breakdown' => $breakdown,
            'breakdownTotal' => array_sum($breakdown),
            'categories' => ExpenseCategory::cases(),
            'methods' => PaymentMethod::cases(),
            'accounts' => PaymentAccount::active()->get(),
            'total' => $this->repository->total($filters),
            'unpaidTotal' => Expense::where('is_paid', false)->sum('payable_amount'),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);
        $data = $request->validated();
        $attachment = $request->file('attachment');
        unset($data['attachment']);

        $this->service->createExpense($data, $attachment);

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('accounts.expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::cases(),
            'methods' => PaymentMethod::cases(),
            'accounts' => PaymentAccount::active()->get(),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);
        $data = $request->validated();
        $attachment = $request->file('attachment');
        unset($data['attachment']);

        $this->service->updateExpense($expense, $data, $attachment, $request->boolean('remove_attachment'));

        return redirect()->route('accounts.expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);
        $this->service->deleteExpense($expense);

        return back()->with('success', 'Expense deleted.');
    }

    public function togglePaid(Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);
        $expense = $this->service->markPaid($expense, ! $expense->is_paid);

        return back()->with('success', $expense->is_paid ? 'Marked as paid.' : 'Marked as due.');
    }

    public function export(Request $request): StreamedResponse
    {
        $range = $this->resolveRange($request->input('period'), $request->input('from'), $request->input('to'));
        $filters = array_merge(
            $request->only(['category', 'method', 'paid_status', 'search']),
            ['from' => $range['from'], 'to' => $range['to']]
        );
        $expenses = $this->repository->paginate($filters, 5000);

        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Category', 'Title', 'Paid To', 'Paid Via', 'Account', 'Reference', 'Amount (BDT)', 'Status']);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->expense_date?->format('Y-m-d'),
                    $expense->category_label,
                    $expense->title,
                    $expense->paid_to ?? '—',
                    $expense->paid_via,
                    $expense->paymentAccount?->name ?? '—',
                    $expense->reference ?? '—',
                    number_format((float) $expense->amount, 2, '.', ''),
                    $expense->is_paid ? 'Paid' : 'Due',
                ]);
            }

            fclose($handle);
        }, 'expenses-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
