<?php

use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\IntegrationSettingsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\KanbanBoardController;
use App\Http\Controllers\LeadManagementController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SocialChatController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check() ? redirect()->route('tasks.index') : redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.store');

Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->name('auth.google.callback');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated + approved users
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved'])->group(function () {
    // Dashboard / tasks
    Route::get('/dashboard', [TaskController::class, 'index'])->name('dashboard');
    Route::get('/home', [TaskController::class, 'index'])->name('home');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('/tasks/{task}/download', [TaskController::class, 'downloadAttachment'])->name('tasks.download');
    Route::post('/tasks/{task}/upload', [TaskController::class, 'uploadAttachment'])->name('tasks.upload');

    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index'])->name('task.comments.index');
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('task.comments.store');
    Route::put('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('task.comments.update');
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('task.comments.destroy');

    // Payments (member view)
    Route::get('/payments', [PaymentController::class, 'memberLedger'])->name('payments.member');
    Route::get('/members/{user}/payments', [PaymentController::class, 'memberLedger'])->name('members.payments');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/publish', [InvoiceController::class, 'publish'])->name('invoices.publish');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // Social chat
    Route::get('/social-chat', [SocialChatController::class, 'index'])->name('social-chat.index');
    Route::post('/social-chat/sync', [SocialChatController::class, 'sync'])->name('social-chat.sync');
    Route::get('/social-chat/{conversation}', [SocialChatController::class, 'show'])->name('social-chat.show');
    Route::post('/social-chat/{conversation}/assign', [SocialChatController::class, 'assign'])->name('social-chat.assign');
    Route::post('/social-chat/{conversation}/reply', [SocialChatController::class, 'reply'])->name('social-chat.reply');

    // Kanban boards (access is enforced per board by BoardPolicy)
    Route::get('/boards', [KanbanBoardController::class, 'index'])->name('boards.index');
    Route::post('/boards', [KanbanBoardController::class, 'store'])->name('boards.store');
    Route::post('/kanban/update-position', [KanbanBoardController::class, 'updatePosition'])->name('kanban.update-position');

    Route::scopeBindings()->group(function () {
        Route::get('/boards/{board}', [KanbanBoardController::class, 'show'])->name('boards.show');
        Route::put('/boards/{board}', [KanbanBoardController::class, 'update'])->name('boards.update');
        Route::delete('/boards/{board}', [KanbanBoardController::class, 'destroy'])->name('boards.destroy');
        Route::post('/boards/{board}/share', [KanbanBoardController::class, 'share'])->name('boards.share');

        Route::post('/boards/{board}/columns', [KanbanBoardController::class, 'storeColumn'])->name('boards.columns.store');
        Route::put('/boards/{board}/columns/{column}', [KanbanBoardController::class, 'updateColumn'])->name('boards.columns.update');
        Route::delete('/boards/{board}/columns/{column}', [KanbanBoardController::class, 'destroyColumn'])->name('boards.columns.destroy');
        Route::post('/boards/{board}/columns/{column}/cards', [KanbanBoardController::class, 'storeCard'])->name('boards.cards.store');

        Route::get('/boards/{board}/cards/{card}', [KanbanBoardController::class, 'showCard'])->name('boards.cards.show');
        Route::put('/boards/{board}/cards/{card}', [KanbanBoardController::class, 'updateCard'])->name('boards.cards.update');
        Route::delete('/boards/{board}/cards/{card}', [KanbanBoardController::class, 'destroyCard'])->name('boards.cards.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Admin only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved', 'admin'])->group(function () {
    // Approvals / financial / members
    Route::get('/admin/approvals', [AdminApprovalController::class, 'index'])->name('admin.approvals');
    Route::post('/admin/approvals/{user}', [AdminApprovalController::class, 'update'])->name('admin.approvals.update');
    Route::get('/admin/payments', [PaymentController::class, 'adminDashboard'])->name('admin.payments');
    Route::get('/admin/members', [PaymentController::class, 'memberDirectory'])->name('admin.members');
    Route::post('/admin/members', [MemberController::class, 'store'])->name('admin.members.store');

    // Clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::post('/clients/bulk-assign', [ClientController::class, 'bulkAssign'])->name('clients.bulkAssign');
    Route::get('/clients/template', [ClientController::class, 'downloadTemplate'])->name('clients.template');
    Route::post('/clients/import', [ClientController::class, 'import'])->name('clients.import');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::post('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.status');
    Route::post('/clients/{client}/notes', [ClientController::class, 'storeNote'])->name('clients.notes.store');
    Route::put('/clients/{client}/notes/{note}', [ClientController::class, 'updateNote'])->name('clients.notes.update');
    Route::delete('/clients/{client}/notes/{note}', [ClientController::class, 'destroyNote'])->name('clients.notes.destroy');

    // Lead management
    Route::get('/lead-management', [LeadManagementController::class, 'index'])->name('lead-management.index');
    Route::post('/lead-management/appointments', [LeadManagementController::class, 'storeAppointment'])->name('lead-management.appointments.store');
    Route::put('/lead-management/appointments/{appointment}', [LeadManagementController::class, 'updateAppointment'])->name('lead-management.appointments.update');
    Route::delete('/lead-management/appointments/{appointment}', [LeadManagementController::class, 'destroyAppointment'])->name('lead-management.appointments.destroy');

    Route::post('/lead-management/meetings', [LeadManagementController::class, 'storeMeeting'])->name('lead-management.meetings.store');
    Route::put('/lead-management/meetings/{meeting}', [LeadManagementController::class, 'updateMeeting'])->name('lead-management.meetings.update');
    Route::delete('/lead-management/meetings/{meeting}', [LeadManagementController::class, 'destroyMeeting'])->name('lead-management.meetings.destroy');

    Route::post('/lead-management/assigned-leads', [LeadManagementController::class, 'storeAssignedLead'])->name('lead-management.assigned-leads.store');
    Route::get('/lead-management/assigned-leads/{assignedLead}', [LeadManagementController::class, 'showLead'])->name('lead-management.assigned-leads.show');
    Route::post('/lead-management/assigned-leads/{assignedLead}/assign', [LeadManagementController::class, 'assignLeadMembers'])->name('lead-management.assigned-leads.assign');
    Route::post('/lead-management/assigned-leads/{assignedLead}/notes', [LeadManagementController::class, 'storeLeadNote'])->name('lead-management.assigned-leads.notes.store');
    Route::put('/lead-management/assigned-leads/{assignedLead}', [LeadManagementController::class, 'updateAssignedLead'])->name('lead-management.assigned-leads.update');
    Route::delete('/lead-management/assigned-leads/{assignedLead}', [LeadManagementController::class, 'destroyAssignedLead'])->name('lead-management.assigned-leads.destroy');

    // Settings & integrations
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/logo', [SettingController::class, 'updateGeneral'])->name('settings.logo');
    Route::post('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.password');
    Route::get('/settings/integrations', [IntegrationSettingsController::class, 'index'])->name('settings.integrations');
    Route::post('/settings/integrations', [IntegrationSettingsController::class, 'store'])->name('settings.integrations.store');
    Route::post('/settings/integrations/test', [IntegrationSettingsController::class, 'testConnection'])->name('settings.integrations.test');
    Route::get('/integrations', [IntegrationSettingsController::class, 'index'])->name('integrations.index');
});
