<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Services\Accounts\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(): View
    {
        return view('accounts.dashboard', ['data' => $this->dashboard->data()]);
    }
}
