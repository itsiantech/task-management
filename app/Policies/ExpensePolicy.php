<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Accounts\Expense;
use App\Models\User;

class ExpensePolicy
{
    private function isManager(?User $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->isAccountant());
    }

    public function view(?User $user, Expense $expense): bool
    {
        return $user !== null;
    }

    public function create(?User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(?User $user, Expense $expense): bool
    {
        return $this->isManager($user);
    }

    public function delete(?User $user, Expense $expense): bool
    {
        return $this->isManager($user);
    }
}
