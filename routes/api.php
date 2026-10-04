<?php

use App\Http\Controllers\MetaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/meta/webhook', [MetaWebhookController::class, 'verify'])->name('api.meta.webhook.verify');
Route::post('/meta/webhook', [MetaWebhookController::class, 'handle'])->name('api.meta.webhook.handle');
