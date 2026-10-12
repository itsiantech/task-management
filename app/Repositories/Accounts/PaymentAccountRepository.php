<?php

declare(strict_types=1);

namespace App\Repositories\Accounts;

use App\Models\Accounts\PaymentAccount;
use Illuminate\Database\Eloquent\Collection;

class PaymentAccountRepository
{
    public function all(): Collection
    {
        return PaymentAccount::orderBy('type')->orderBy('name')->get();
    }

    public function active(): Collection
    {
        return PaymentAccount::active()->orderBy('type')->orderBy('name')->get();
    }

    public function create(array $data): PaymentAccount
    {
        return PaymentAccount::create($data);
    }

    public function update(PaymentAccount $account, array $data): PaymentAccount
    {
        $account->update($data);

        return $account->fresh();
    }

    public function delete(PaymentAccount $account): bool
    {
        return (bool) $account->delete();
    }
}
