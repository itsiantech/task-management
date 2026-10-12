<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Accounts\Payment;
use App\Models\User;

class PaymentPolicy
{
    private function isManager(?User $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->isAccountant());
    }

    public function view(?User $user, Payment $payment): bool
    {
        return $user !== null;
    }

    public function create(?User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(?User $user, Payment $payment): bool
    {
        return $this->isManager($user);
    }
}
