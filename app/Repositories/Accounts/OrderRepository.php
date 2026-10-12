<?php

declare(strict_types=1);

namespace App\Repositories\Accounts;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    /**
     * Orders are tasks with financial context (order_number, client, budget).
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Task::with(['client', 'assignee'])
            ->withSum('accountPayments as paid_sum', 'amount');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'amount_high' => $query->orderByDesc('total_budget'),
            'amount_low' => $query->orderBy('total_budget'),
            'due_high' => $query->orderByDesc('due_amount'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }
}
