<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Accounts\InvoiceRequest;
use App\Models\Accounts\Service;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Repositories\Accounts\InvoiceRepository;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly PaymentService $payments,
    ) {}

    public static function lineTotal(int $qty, float $unitPrice, float $discount = 0, float $tax = 0): float
    {
        return round(max(0, $qty * $unitPrice - $discount) + $tax, 2);
    }

    public function createInvoice(InvoiceRequest $request, bool $publish = false): Invoice
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $publish) {
            $invoice = $this->repository->create(array_merge(
                $this->billingAttributes((int) $data['client_id'], $data),
                [
                    'client_id' => $data['client_id'],
                    'task_id' => $data['task_id'] ?? null,
                    'status' => $publish ? InvoiceStatus::UNPAID->value : InvoiceStatus::DRAFT->value,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'advance_paid_credit' => $data['advance_paid_credit'] ?? 0,
                    'payment_method' => $data['payment_method'] ?? 'bank',
                    'notes' => $data['notes'] ?? null,
                    'published_at' => $publish ? now() : null,
                ]
            ));

            $this->syncItems($invoice, $data['items']);
            $invoice->recalculateTotals();

            ActivityLog::record('invoice.created', $invoice, "Invoice {$invoice->invoice_number} created");

            return $invoice->fresh();
        });
    }

    public function updateInvoice(Invoice $invoice, InvoiceRequest $request, bool $force = false): Invoice
    {
        $status = InvoiceStatus::tryFrom((string) $invoice->status);
        if (! $force && ! in_array($status, [InvoiceStatus::DRAFT, InvoiceStatus::UNPAID, InvoiceStatus::PARTIAL, InvoiceStatus::HOLD], true)) {
            throw new \DomainException('Only draft, unpaid, partial or on-hold invoices can be edited.');
        }

        $data = $request->validated();

        return DB::transaction(function () use ($invoice, $data, $force) {
            $this->repository->update($invoice, array_merge(
                $this->billingAttributes((int) $data['client_id'], $data),
                [
                    'client_id' => $data['client_id'],
                    'task_id' => $data['task_id'] ?? null,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'advance_paid_credit' => $data['advance_paid_credit'] ?? 0,
                    'payment_method' => $data['payment_method'] ?? 'bank',
                    'notes' => $data['notes'] ?? null,
                ]
            ));

            $invoice->items()->delete();
            $this->syncItems($invoice, $data['items']);
            $invoice->recalculateTotals();

            ActivityLog::record('invoice.updated', $invoice, "Invoice {$invoice->invoice_number} updated" . ($force ? ' (paid-invoice adjustment by admin)' : ''), [
                'forced' => $force,
            ]);

            return $invoice->fresh();
        });
    }

    public function publish(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->published_at ??= now();
            if ($invoice->status === InvoiceStatus::DRAFT->value) {
                $invoice->status = InvoiceStatus::UNPAID->value;
            }
            $invoice->recalculateTotals();
            ActivityLog::record('invoice.published', $invoice, "Invoice {$invoice->invoice_number} published");

            return $invoice->fresh();
        });
    }

    public function void(Invoice $invoice, string $reason): Invoice
    {
        if ($invoice->status === InvoiceStatus::VOID->value) {
            throw new \DomainException('Invoice is already void.');
        }

        return DB::transaction(function () use ($invoice, $reason) {
            $invoice->update([
                'status' => InvoiceStatus::VOID->value,
                'void_reason' => $reason,
                'voided_at' => now(),
                'voided_by' => auth()->id(),
            ]);

            ActivityLog::record('invoice.voided', $invoice, "Invoice {$invoice->invoice_number} voided: {$reason}", [
                'reason' => $reason,
            ]);

            return $invoice->fresh();
        });
    }

    public function hold(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::VOID->value || $invoice->status === InvoiceStatus::PAID->value) {
            throw new \DomainException('Void or paid invoices cannot be put on hold.');
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => InvoiceStatus::HOLD->value,
                'held_at' => now(),
                'held_by' => auth()->id(),
            ]);

            ActivityLog::record('invoice.held', $invoice, "Invoice {$invoice->invoice_number} put on hold");

            return $invoice->fresh();
        });
    }

    public function resume(Invoice $invoice): Invoice
    {
        if ($invoice->status !== InvoiceStatus::HOLD->value) {
            throw new \DomainException('Only on-hold invoices can be resumed.');
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatus::UNPAID->value, 'held_at' => null, 'held_by' => null]);
            $invoice->recalculateTotals();

            ActivityLog::record('invoice.resumed', $invoice, "Invoice {$invoice->invoice_number} resumed");

            return $invoice->fresh();
        });
    }

    public function addPayment(Invoice $invoice, array $paymentData): \App\Models\Accounts\Payment
    {
        $status = InvoiceStatus::tryFrom((string) $invoice->status);
        if (! $status || ! $status->canAddPayment()) {
            throw new \DomainException('Payments can only be added to unpaid, partial or due invoices.');
        }

        return DB::transaction(function () use ($invoice, $paymentData) {
            $payment = $this->payments->createPayment(array_merge($paymentData, [
                'invoice_id' => $invoice->id,
                'client_id' => $paymentData['client_id'] ?? $invoice->client_id,
                'order_id' => $paymentData['order_id'] ?? $invoice->task_id,
            ]));

            $invoice->recalculateTotals();

            return $payment;
        });
    }

    /**
     * "Invoiced To" snapshot. Blank fields fall back to the client record
     * (country defaults to Bangladesh) so the invoice keeps billing
     * details even if the client changes later.
     */
    private function billingAttributes(int $clientId, array $data): array
    {
        $client = Client::find($clientId);

        $value = fn (string $key, ?string $fallback) => trim((string) ($data[$key] ?? '')) !== ''
            ? trim((string) $data[$key])
            : $fallback;

        return [
            'billing_company' => $value('billing_company', $client?->name),
            'billing_name' => $value('billing_name', $client?->contact_person),
            'billing_address' => $value('billing_address', $client?->address),
            'billing_phone' => $value('billing_phone', $client?->phone),
            'billing_email' => $value('billing_email', $client?->email),
            'billing_country' => $value('billing_country', 'Bangladesh'),
        ];
    }

    private function syncItems(Invoice $invoice, array $items): void
    {        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $tax = (float) ($item['tax'] ?? 0);
            $lineTotal = self::lineTotal($qty, $unitPrice, $discount, $tax);

            // Description is optional: fall back to the service name.
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '' && ! empty($item['service_id'])) {
                $service = Service::find($item['service_id']);
                $description = $service ? $service->name : '';
            }
            if ($description === '') {
                $description = 'Item';
            }

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_id' => $item['service_id'] ?? null,
                'description' => $description,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'tax' => $tax,
                'line_total' => $lineTotal,
                // Legacy column kept in sync so old screens keep working.
                'amount' => $lineTotal,
            ]);
        }
    }
}
