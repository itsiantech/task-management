<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Task;
use App\Repositories\Accounts\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'payment_status', 'status', 'client_id', 'from', 'to', 'sort']);

        return view('accounts.orders.index', [
            'orders' => $this->repository->paginate($filters),
            'filters' => $filters,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'paymentStatuses' => ['pending' => 'Pending', 'partial' => 'Partial', 'complete' => 'Complete', 'hold' => 'Hold', 'cancelled' => 'Cancelled'],
        ]);
    }

    /**
     * All invoices of one specific task/order, with totals.
     */
    public function invoices(Task $task): View
    {
        $task->load(['client', 'invoices' => fn ($q) => $q->with('items')->orderByDesc('invoice_date')->orderByDesc('id')]);

        $valid = $task->invoices->where('status', '!=', 'void');

        return view('accounts.orders.invoices', [
            'task' => $task,
            'invoices' => $task->invoices,
            'totalBilled' => (float) $valid->sum('total_amount'),
            'totalPaid' => (float) $valid->sum('total_amount') - (float) $valid->sum('total_due'),
            'totalDue' => (float) $valid->sum('total_due'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'payment_status', 'status', 'client_id', 'from', 'to', 'sort']);
        $orders = $this->repository->paginate($filters, 5000);

        $fileName = 'orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order No', 'Company', 'Phone', 'Title', 'Total (BDT)', 'Paid (BDT)', 'Due (BDT)', 'Payment Status', 'Date']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number_label,
                    $order->client?->name ?? '—',
                    $order->client?->phone ?? '—',
                    $order->title,
                    number_format((float) $order->total_budget, 2, '.', ''),
                    number_format((float) ($order->paid_sum ?? $order->paid_amount), 2, '.', ''),
                    number_format((float) $order->due_amount, 2, '.', ''),
                    ucfirst((string) $order->payment_status),
                    $order->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
