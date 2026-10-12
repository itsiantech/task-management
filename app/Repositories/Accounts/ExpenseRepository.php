<?php

declare(strict_types=1);

namespace App\Repositories\Accounts;

use App\Models\Accounts\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseRepository
{
    private function applyFilters($query, array $filters, bool $ignoreCategory = false): void
    {
        if (! empty($filters['category']) && ! $ignoreCategory) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['method'])) {
            $query->where('paid_via', $filters['method']);
        }
        if (! empty($filters['paid_status'])) {
            $query->where('is_paid', $filters['paid_status'] === 'paid');
        }
        if (! empty($filters['from'])) {
            $query->whereDate('expense_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('expense_date', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('paid_to', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%"));
        }
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Expense::query();
        $this->applyFilters($query, $filters);

        return $query->orderByDesc('expense_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Category-wise totals for the filtered period (ignores the
     * category filter itself so every category is shown).
     *
     * @return array<string, float>
     */
    public function categoryTotals(array $filters): array
    {
        $query = Expense::query();
        $this->applyFilters($query, $filters, true);

        return $query->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    public function total(array $filters): float
    {
        $query = Expense::query();
        $this->applyFilters($query, $filters);

        return (float) $query->sum('amount');
    }

    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense->fresh();
    }

    public function sumByCategory(string $from, string $to): array
    {
        return Expense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->all();
    }

    public function totalBetween(string $from, string $to): float
    {
        return (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
    }
}
