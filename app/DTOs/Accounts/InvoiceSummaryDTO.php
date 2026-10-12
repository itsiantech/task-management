<?php

declare(strict_types=1);

namespace App\DTOs\Accounts;

readonly class InvoiceSummaryDTO
{
    public function __construct(
        public int $totalInvoices,
        public float $totalBilled,
        public float $totalPaid,
        public float $totalDue,
        public int $unpaidCount,
        public int $partialCount,
        public int $paidCount,
        public int $dueCount,
        public int $holdCount,
        public int $voidCount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            totalInvoices: $data['total_invoices'] ?? 0,
            totalBilled: $data['total_billed'] ?? 0.0,
            totalPaid: $data['total_paid'] ?? 0.0,
            totalDue: $data['total_due'] ?? 0.0,
            unpaidCount: $data['unpaid_count'] ?? 0,
            partialCount: $data['partial_count'] ?? 0,
            paidCount: $data['paid_count'] ?? 0,
            dueCount: $data['due_count'] ?? 0,
            holdCount: $data['hold_count'] ?? 0,
            voidCount: $data['void_count'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'total_invoices' => $this->totalInvoices,
            'total_billed' => $this->totalBilled,
            'total_paid' => $this->totalPaid,
            'total_due' => $this->totalDue,
            'unpaid_count' => $this->unpaidCount,
            'partial_count' => $this->partialCount,
            'paid_count' => $this->paidCount,
            'due_count' => $this->dueCount,
            'hold_count' => $this->holdCount,
            'void_count' => $this->voidCount,
        ];
    }
}
