<?php

use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\IntegrationSettingsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadManagementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('/tasks/{task}/download', [TaskController::class, 'downloadAttachment'])->name('tasks.download');
    Route::post('/tasks/{task}/upload', [TaskController::class, 'uploadAttachment'])->name('tasks.upload');

    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index'])->name('task.comments.index');
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('task.comments.store');
    Route::put('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('task.comments.update');
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('task.comments.destroy');

    Route::get('/payments', [PaymentController::class, 'memberLedger'])->name('payments.member');
    Route::get('/members/{user}/payments', [PaymentController::class, 'memberLedger'])->name('members.payments');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/publish', [InvoiceController::class, 'publish'])->name('invoices.publish');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    Route::get('/social-chat', [\App\Http\Controllers\SocialChatController::class, 'index'])->name('social-chat.index');
    Route::post('/social-chat/sync', [\App\Http\Controllers\SocialChatController::class, 'sync'])->name('social-chat.sync');
    Route::get('/social-chat/{conversation}', [\App\Http\Controllers\SocialChatController::class, 'show'])->name('social-chat.show');
    Route::post('/social-chat/{conversation}/assign', [\App\Http\Controllers\SocialChatController::class, 'assign'])->name('social-chat.assign');
    Route::post('/social-chat/{conversation}/reply', [\App\Http\Controllers\SocialChatController::class, 'reply'])->name('social-chat.reply');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/approvals', [AdminApprovalController::class, 'index'])->name('admin.approvals');
    Route::post('/admin/approvals/{user}', [AdminApprovalController::class, 'update'])->name('admin.approvals.update');
    Route::get('/admin/payments', [PaymentController::class, 'adminDashboard'])->name('admin.payments');
    Route::get('/admin/members', [PaymentController::class, 'memberDirectory'])->name('admin.members');

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

    Route::get('/settings/integrations', [IntegrationSettingsController::class, 'index'])->name('settings.integrations');
    Route::post('/settings/integrations', [IntegrationSettingsController::class, 'store'])->name('settings.integrations.store');
    Route::post('/settings/integrations/test', [IntegrationSettingsController::class, 'testConnection'])->name('settings.integrations.test');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
