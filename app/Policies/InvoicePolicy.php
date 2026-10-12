<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private function isManager(?User $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->isAccountant());
    }

    public function view(?User $user, Invoice $invoice): bool
    {
        return $user !== null;
    }

    public function create(?User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(?User $user, Invoice $invoice): bool
    {
        if (! $this->isManager($user)) {
            return false;
        }

        $status = InvoiceStatus::tryFrom((string) $invoice->status);

        // Paid invoices are locked: only Super Admin may adjust (audit-logged).
        if ($status === InvoiceStatus::PAID) {
            return $user->isAdmin();
        }

        return $status !== null && $status->isEditable();
    }

    public function void(?User $user, Invoice $invoice): bool
    {
        return $this->isManager($user) && $invoice->status !== InvoiceStatus::VOID->value;
    }

    public function hold(?User $user, Invoice $invoice): bool
    {
        if (! $this->isManager($user)) {
            return false;
        }

        return ! in_array($invoice->status, [InvoiceStatus::VOID->value, InvoiceStatus::PAID->value], true);
    }

    public function resume(?User $user, Invoice $invoice): bool
    {
        return $this->isManager($user) && $invoice->status === InvoiceStatus::HOLD->value;
    }

    public function addPayment(?User $user, Invoice $invoice): bool
    {
        if (! $this->isManager($user)) {
            return false;
        }

        $status = InvoiceStatus::tryFrom((string) $invoice->status);

        return $status !== null && $status->canAddPayment();
    }
}
