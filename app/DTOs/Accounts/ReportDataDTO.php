<?php

declare(strict_types=1);

namespace App\DTOs\Accounts;

readonly class ReportDataDTO
{
    public function __construct(
        public float $income,
        public float $expense,
        public float $netProfit,
        public float $receivableDue,
        public float $payable,
        public int $pendingOrders,
        public array $incomeByService,
        public array $expenseByCategory,
        public array $incomeByMethod,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            income: $data['income'] ?? 0.0,
            expense: $data['expense'] ?? 0.0,
            netProfit: $data['net_profit'] ?? 0.0,
            receivableDue: $data['receivable_due'] ?? 0.0,
            payable: $data['payable'] ?? 0.0,
            pendingOrders: $data['pending_orders'] ?? 0,
            incomeByService: $data['income_by_service'] ?? [],
            expenseByCategory: $data['expense_by_category'] ?? [],
            incomeByMethod: $data['income_by_method'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'income' => $this->income,
            'expense' => $this->expense,
            'net_profit' => $this->netProfit,
            'receivable_due' => $this->receivableDue,
            'payable' => $this->payable,
            'pending_orders' => $this->pendingOrders,
            'income_by_service' => $this->incomeByService,
            'expense_by_category' => $this->expenseByCategory,
            'income_by_method' => $this->incomeByMethod,
        ];
    }
}
