<?php

declare(strict_types=1);

namespace App\Repositories\Accounts;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository
{
    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::with(['client', 'task'])->withSum('items as items_total', 'line_total');
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('invoice_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('invoice_date', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('invoice_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function statusCounts(): array
    {
        return Invoice::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);

        return $invoice->fresh();
    }

    public function findWithRelations(int $id): Invoice
    {
        return Invoice::with(['client', 'task', 'items.service', 'payments' => fn ($q) => $q->with('createdBy')->orderByDesc('payment_date'), 'heldBy', 'voidedBy'])
            ->findOrFail($id);
    }

    public function allIdsInStatus(array $statuses): Collection
    {
        return Invoice::whereIn('status', $statuses)->get();
    }
}
