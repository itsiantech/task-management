<?php

use App\Http\Controllers\Accounts\ClientController as AccountsClientController;
use App\Http\Controllers\Accounts\DashboardController;
use App\Http\Controllers\Accounts\ExpenseController;
use App\Http\Controllers\Accounts\InvoiceController;
use App\Http\Controllers\Accounts\OrderController;
use App\Http\Controllers\Accounts\PaymentAccountController;
use App\Http\Controllers\Accounts\PaymentController;
use App\Http\Controllers\Accounts\ReportController;
use App\Http\Controllers\Accounts\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'approved'])->prefix('admin/accounts')->name('accounts.')->group(function () {
    // Dashboard / Summary
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders (account tasks)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/{task}/invoices', [OrderController::class, 'invoices'])->name('orders.invoices');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    // NOTE: create/edit form URLs stay reachable here so they are not shadowed
    // by {invoice}; the controller policy still blocks non-managers (403).
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/pdf/preview', [InvoiceController::class, 'streamPdf'])->name('invoices.pdf.preview');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');

    // Client 360° view + search (for invoice/payment forms)
    Route::get('/clients/search', [AccountsClientController::class, 'search'])->name('clients.search');
    Route::get('/clients/{client}', [AccountsClientController::class, 'show'])->name('clients.show');

    // Services
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');

    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');

    // Payment Methods and Accounts
    Route::get('/payment-accounts', [PaymentAccountController::class, 'index'])->name('payment-accounts.index');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');

    /*
    |--------------------------------------------------------------------------
    | Write operations: Super Admin (admin) + Accountant only
    |--------------------------------------------------------------------------
    */
    Route::middleware(['accounts.manage'])->group(function () {
        // Invoices
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('/invoices/{invoice}/publish', [InvoiceController::class, 'publish'])->name('invoices.publish');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('/invoices/{invoice}/hold', [InvoiceController::class, 'hold'])->name('invoices.hold');
        Route::post('/invoices/{invoice}/resume', [InvoiceController::class, 'resume'])->name('invoices.resume');
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->name('invoices.payments.store');

        // Payments
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');

        // Expenses
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::patch('/expenses/{expense}/toggle-paid', [ExpenseController::class, 'togglePaid'])->name('expenses.toggle-paid');

        // Payment Accounts (Super Admin only via FormRequest)
        Route::post('/payment-accounts', [PaymentAccountController::class, 'store'])->name('payment-accounts.store');
        Route::put('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'update'])->name('payment-accounts.update');
        Route::delete('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'destroy'])->name('payment-accounts.destroy');
    });
});
