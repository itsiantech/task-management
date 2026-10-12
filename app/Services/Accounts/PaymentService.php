<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Models\Accounts\Payment;
use App\Models\ActivityLog;
use App\Repositories\Accounts\PaymentRepository;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $repository,
    ) {}

    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $payment = $this->repository->create([
                'invoice_id' => $data['invoice_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'transaction_date' => $data['transaction_date'] ?? $data['payment_date'],
                'method' => $data['method'],
                'gateway' => $data['gateway'] ?? $data['method'],
                'receiving_account' => $data['receiving_account'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            if ($payment->invoice_id) {
                $payment->invoice?->recalculateTotals();
            }

            ActivityLog::record('payment.created', $payment, "Payment of ৳" . number_format((float) $payment->amount, 2) . " recorded", [
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
            ]);

            return $payment;
        });
    }

    public function updatePayment(Payment $payment, array $data, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $data, $reason) {
            $payment->editReason = $reason;
            $payment->editActor = auth()->id();

            $payment->fill([
                'invoice_id' => $data['invoice_id'] ?? $payment->invoice_id,
                'client_id' => $data['client_id'] ?? $payment->client_id,
                'order_id' => $data['order_id'] ?? $payment->order_id,
                'amount' => $data['amount'] ?? $payment->amount,
                'payment_date' => $data['payment_date'] ?? $payment->payment_date,
                'method' => $data['method'] ?? $payment->method,
                'gateway' => $data['gateway'] ?? $data['method'] ?? $payment->gateway,
                'receiving_account' => $data['receiving_account'] ?? $payment->receiving_account,
                'transaction_id' => $data['transaction_id'] ?? $payment->transaction_id,
                'note' => $data['note'] ?? $payment->note,
            ]);

            if ($payment->isDirty()) {
                $payment->save();
            }

            ActivityLog::record('payment.updated', $payment, "Payment #{$payment->id} corrected: {$reason}", [
                'reason' => $reason,
            ]);

            return $payment->fresh();
        });
    }
}
