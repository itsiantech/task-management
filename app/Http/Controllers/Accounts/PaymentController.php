<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\PaymentRequest;
use App\Http\Requests\Accounts\PaymentUpdateRequest;
use App\Models\Accounts\Payment;
use App\Models\Accounts\PaymentAccount;
use App\Models\Client;
use App\Models\Invoice;
use App\Repositories\Accounts\PaymentRepository;
use App\Services\Accounts\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentRepository $repository,
        private readonly PaymentService $service,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['method', 'client_id', 'invoice_id', 'from', 'to', 'search']);

        return view('accounts.payments.index', [
            'payments' => $this->repository->paginate($filters),
            'filters' => $filters,
            'methods' => PaymentMethod::cases(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'total' => $this->repository->paginate($filters, 100000)->sum('amount'),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);
        $payment = $this->service->createPayment($request->validated());

        if ($payment->invoice_id) {
            return redirect()->route('accounts.invoices.show', $payment->invoice_id)
                ->with('success', 'Payment recorded successfully.');
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);
        $payment->load(['invoice', 'client', 'order', 'edits.editedBy']);

        return view('accounts.payments.edit', [
            'payment' => $payment,
            'methods' => PaymentMethod::cases(),
            'accounts' => PaymentAccount::active()->get(),
            'invoices' => Invoice::orderByDesc('id')->limit(200)->get(['id', 'invoice_number', 'client_id']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(PaymentUpdateRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);
        $data = $request->validated();

        $this->service->updatePayment($payment, $data, $data['edit_reason']);

        return redirect()->route('accounts.payments.index')
            ->with('success', 'Payment corrected. The edit history is preserved below the payment.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['method', 'client_id', 'invoice_id', 'from', 'to', 'search']);
        $payments = $this->repository->paginate($filters, 5000);

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Client', 'Invoice', 'Method', 'Account', 'Transaction ID', 'Amount (BDT)', 'Edited', 'Note']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->payment_date?->format('Y-m-d'),
                    $payment->client?->name ?? '—',
                    $payment->invoice?->invoice_number ?? '—',
                    $payment->method,
                    $payment->receiving_account ?? '—',
                    $payment->transaction_id ?? '—',
                    number_format((float) $payment->amount, 2, '.', ''),
                    $payment->edits_count > 0 ? 'Yes' : 'No',
                    $payment->note ?? '',
                ]);
            }

            fclose($handle);
        }, 'payments-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
