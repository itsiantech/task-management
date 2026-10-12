<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Models\Accounts\Expense;
use App\Models\ActivityLog;
use App\Repositories\Accounts\ExpenseRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepository $repository,
    ) {}

    public function createExpense(array $data, ?UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($data, $attachment) {
            if ($attachment) {
                $data['attachment'] = $attachment->store('expense-attachments', 'public');
            }
            $data['payable_amount'] ??= $data['amount'];
            $data['is_paid'] ??= true;

            $expense = $this->repository->create($data);

            ActivityLog::record('expense.created', $expense, "Expense '{$expense->title}' (৳" . number_format((float) $expense->amount, 2) . ") recorded");

            return $expense;
        });
    }

    public function updateExpense(Expense $expense, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Expense
    {
        return DB::transaction(function () use ($expense, $data, $attachment, $removeAttachment) {
            if ($attachment) {
                $this->deleteAttachment($expense);
                $data['attachment'] = $attachment->store('expense-attachments', 'public');
            } elseif ($removeAttachment) {
                $this->deleteAttachment($expense);
                $data['attachment'] = null;
            }

            $expense = $this->repository->update($expense, $data);

            ActivityLog::record('expense.updated', $expense, "Expense '{$expense->title}' updated");

            return $expense;
        });
    }

    public function deleteExpense(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            ActivityLog::record('expense.deleted', $expense, "Expense '{$expense->title}' deleted");
            $expense->delete();
        });
    }

    public function markPaid(Expense $expense, bool $paid): Expense
    {
        return DB::transaction(function () use ($expense, $paid) {
            $expense->update(['is_paid' => $paid]);

            ActivityLog::record($paid ? 'expense.paid' : 'expense.marked_due', $expense, "Expense '{$expense->title}' marked as " . ($paid ? 'paid' : 'due'));

            return $expense->fresh();
        });
    }

    private function deleteAttachment(Expense $expense): void
    {
        if ($expense->attachment && Storage::disk('public')->exists($expense->attachment)) {
            Storage::disk('public')->delete($expense->attachment);
        }
    }
}
