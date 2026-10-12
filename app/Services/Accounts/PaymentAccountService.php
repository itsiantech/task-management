<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Enums\PaymentMethod;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Payment;
use App\Models\Accounts\PaymentAccount;
use App\Models\ActivityLog;
use App\Repositories\Accounts\PaymentAccountRepository;
use Illuminate\Support\Facades\DB;

class PaymentAccountService
{
    public function __construct(
        private readonly PaymentAccountRepository $repository,
    ) {}

    public function createAccount(array $data): PaymentAccount
    {
        $data['is_active'] ??= true;

        return DB::transaction(function () use ($data) {
            $account = $this->repository->create($data);
            $this->recalculate($account);

            ActivityLog::record('payment_account.created', $account, "Payment account '{$account->name}' created");

            return $account->fresh();
        });
    }

    public function updateAccount(PaymentAccount $account, array $data): PaymentAccount
    {
        return DB::transaction(function () use ($account, $data) {
            $account = $this->repository->update($account, $data);
            $this->recalculate($account);

            ActivityLog::record('payment_account.updated', $account, "Payment account '{$account->name}' updated");

            return $account;
        });
    }

    public function deleteAccount(PaymentAccount $account): void
    {
        ActivityLog::record('payment_account.deleted', $account, "Payment account '{$account->name}' deleted");
        $this->repository->delete($account);
    }

    public function recalculateAll(): void
    {
        foreach ($this->repository->all() as $account) {
            $this->recalculate($account);
        }
    }

    /**
     * Balance = lifetime received into this account (by name)
     *          minus lifetime expenses paid from this account.
     */
    public function recalculate(PaymentAccount $account): PaymentAccount
    {
        $received = (float) Payment::where('receiving_account', $account->name)->sum('amount');
        $spent = (float) Expense::where('payment_account_id', $account->id)->sum('amount');

        $account->balance = $received - $spent;
        $account->saveQuietly();

        return $account->fresh();
    }

    /**
     * Per-method aggregates: received / spent / balance (lifetime).
     *
     * @return array<string, array{received: float, spent: float, balance: float}>
     */
    public function methodBalances(): array
    {
        $received = Payment::selectRaw('method, SUM(amount) as total')->groupBy('method')->pluck('total', 'method')->all();
        $spent = Expense::selectRaw('paid_via as method, SUM(amount) as total')->groupBy('paid_via')->pluck('total', 'method')->all();

        $balances = [];
        foreach (PaymentMethod::cases() as $method) {
            $r = (float) ($received[$method->value] ?? 0);
            $s = (float) ($spent[$method->value] ?? 0);
            $balances[$method->value] = ['received' => $r, 'spent' => $s, 'balance' => $r - $s];
        }

        return $balances;
    }
}
