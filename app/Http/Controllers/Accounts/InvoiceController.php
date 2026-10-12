<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\InvoiceRequest;
use App\Http\Requests\Accounts\InvoicePaymentRequest;
use App\Http\Requests\Accounts\VoidInvoiceRequest;
use App\Models\Accounts\PaymentAccount;
use App\Models\Accounts\Service;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Task;
use App\Repositories\Accounts\InvoiceRepository;
use App\Services\Accounts\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly InvoiceService $service,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'client_id', 'from', 'to', 'search']);

        return view('accounts.invoices.index', [
            'invoices' => $this->repository->paginate($filters),
            'counts' => $this->repository->statusCounts(),
            'filters' => $filters,
            'statuses' => InvoiceStatus::cases(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        return view('accounts.invoices.create', $this->formData());
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->service->createInvoice($request, $request->boolean('publish'));

        return redirect()->route('accounts.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    public function show(Invoice $invoice): View
    {
        $invoice = $this->repository->findWithRelations($invoice->id);

        return view('accounts.invoices.show', [
            'invoice' => $invoice,
            'methods' => PaymentMethod::cases(),
            'accounts' => PaymentAccount::active()->get(),
            'canAddPayment' => auth()->user()?->can('addPayment', $invoice) ?? false,
            'canUpdate' => auth()->user()?->can('update', $invoice) ?? false,
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);
        $invoice->load(['items.service', 'client']);

        return view('accounts.invoices.edit', array_merge(['invoice' => $invoice], $this->formData($invoice)));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        try {
            $forced = $invoice->status === InvoiceStatus::PAID->value;
            $invoice = $this->service->updateInvoice($invoice, $request, $forced);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('accounts.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} updated successfully.");
    }

    public function publish(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        $invoice = $this->service->publish($invoice);

        return back()->with('success', "Invoice {$invoice->invoice_number} published.");
    }

    public function void(VoidInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('void', $invoice);

        try {
            $invoice = $this->service->void($invoice, $request->validated()['void_reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('accounts.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} moved to Void.");
    }

    public function hold(Invoice $invoice): RedirectResponse
    {
        $this->authorize('hold', $invoice);

        try {
            $this->service->hold($invoice);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice put on hold.');
    }

    public function resume(Invoice $invoice): RedirectResponse
    {
        $this->authorize('resume', $invoice);

        try {
            $this->service->resume($invoice);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice resumed.');
    }

    public function addPayment(InvoicePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('addPayment', $invoice);

        try {
            $this->service->addPayment($invoice, $request->validated());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function print(Invoice $invoice): View
    {
        $invoice = $this->repository->findWithRelations($invoice->id);

        return view('accounts.invoices.print', [
            'invoice' => $invoice,
            'company' => $this->companyDetails(false),
        ]);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice = $this->repository->findWithRelations($invoice->id);

        $pdf = Pdf::loadView('accounts.invoices.print', [
            'invoice' => $invoice,
            'company' => $this->companyDetails(true),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Preview page: the PDF is shown first, download happens from here.
     */
    public function preview(Invoice $invoice): View
    {
        $invoice = $this->repository->findWithRelations($invoice->id);

        return view('accounts.invoices.preview', compact('invoice'));
    }

    /**
     * The PDF streamed inline (for the preview iframe).
     */
    public function streamPdf(Invoice $invoice)
    {
        $invoice = $this->repository->findWithRelations($invoice->id);

        $pdf = Pdf::loadView('accounts.invoices.print', [
            'invoice' => $invoice,
            'company' => $this->companyDetails(true),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['status', 'client_id', 'from', 'to', 'search']);
        $invoices = $this->repository->paginate($filters, 5000);

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice No', 'Client', 'Date', 'Due Date', 'Status', 'Total (BDT)', 'Due (BDT)']);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->client?->name ?? '—',
                    $invoice->invoice_date?->format('Y-m-d'),
                    $invoice->due_date?->format('Y-m-d'),
                    $invoice->status,
                    number_format((float) $invoice->total_amount, 2, '.', ''),
                    number_format((float) $invoice->total_due, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'invoices-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function formData(?Invoice $invoice = null): array
    {
        $clients = Client::orderBy('name')->get();

        return [
            'clients' => $clients,
            'clientsMap' => $clients->mapWithKeys(fn (Client $client) => [(string) $client->id => [
                'id' => $client->id,
                'text' => $client->name . ($client->contact_person ? " ({$client->contact_person})" : '') . " - {$client->phone}",
                'name' => $client->name,
                'contact_person' => $client->contact_person,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
            ]]),
            'tasks' => Task::orderByDesc('id')->limit(200)->get(['id', 'title', 'client_id']),
            'services' => Service::active()->orderBy('type')->orderBy('name')->get(),
            'methods' => PaymentMethod::cases(),
        ];
    }

    private function companyDetails(bool $forPdf = false): array
    {
        // DomPDF cannot load remote URLs by default, so the PDF gets a
        // local filesystem path while the browser gets the public URL.
        $logoPath = Setting::get('admin_logo');
        $logo = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logo = $forPdf ? Storage::disk('public')->path($logoPath) : Setting::logoUrl();
        }

        return [
            'name' => Setting::get('company_name', Setting::get('site_title', 'ITsian CRM')),
            'address' => Setting::get('company_address', ''),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'logo' => $logo,
        ];
    }
}
