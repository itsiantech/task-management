<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Enums\PaymentAccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\PaymentAccountRequest;
use App\Models\Accounts\PaymentAccount;
use App\Repositories\Accounts\PaymentAccountRepository;
use App\Services\Accounts\PaymentAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
    public function __construct(
        private readonly PaymentAccountRepository $repository,
        private readonly PaymentAccountService $service,
    ) {}

    public function index(): View
    {
        $this->service->recalculateAll();

        return view('accounts.payment-accounts.index', [
            'accounts' => $this->repository->all(),
            'methodBalances' => $this->service->methodBalances(),
            'types' => PaymentAccountType::cases(),
        ]);
    }

    public function store(PaymentAccountRequest $request): RedirectResponse
    {
        $this->service->createAccount($request->validated());

        return back()->with('success', 'Payment account created.');
    }

    public function update(PaymentAccountRequest $request, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->service->updateAccount($paymentAccount, $request->validated());

        return back()->with('success', 'Payment account updated.');
    }

    public function destroy(PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->service->deleteAccount($paymentAccount);

        return back()->with('success', 'Payment account deleted.');
    }
}
