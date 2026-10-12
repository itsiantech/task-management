<?php

declare(strict_types=1);

namespace App\Repositories\Accounts;

use App\Models\Accounts\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Payment::with(['invoice', 'client', 'order', 'createdBy'])
            ->withCount('edits');

        if (! empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }
        if (! empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (! empty($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('payment_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('payment_date', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('receiving_account', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('payment_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function sumByMethod(string $from, string $to): array
    {
        return Payment::whereBetween('payment_date', [$from, $to])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->all();
    }

    public function totalBetween(string $from, string $to): float
    {
        return (float) Payment::whereBetween('payment_date', [$from, $to])->sum('amount');
    }
}
