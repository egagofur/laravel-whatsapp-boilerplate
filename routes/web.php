<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\N8nController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Livewire\N8nWebhookSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Deep health check (public) — used by Docker / reverse proxy
Route::get('/health', HealthController::class)->middleware('throttle:health');

/*
|--------------------------------------------------------------------------
| App-owned admin UI (integrations & settings)
|--------------------------------------------------------------------------
| Protected by Basic Auth and rate limits.
*/
Route::middleware(['whatsapp.auth', 'throttle:api'])->group(function () {
    Route::get('/integrations/n8n', N8nWebhookSettings::class)->name('integrations.n8n');
});

/*
|--------------------------------------------------------------------------
| WhatsApp Core API (Basic Auth + Rate Limiting)
|--------------------------------------------------------------------------
*/
Route::middleware(['whatsapp.auth', 'throttle:api'])->group(function () {
    Route::get('/send-test', [WhatsAppController::class, 'sendTest'])->middleware('throttle:send');
    Route::post('/send-message', [WhatsAppController::class, 'send'])->middleware('throttle:send');
    Route::get('/whatsapp-status', [WhatsAppController::class, 'status']);
});

/*
|--------------------------------------------------------------------------
| n8n Integration API (Dedicated Basic Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['n8n.auth', 'throttle:n8n'])->prefix('api/n8n')->group(function () {
    Route::post('/send', [N8nController::class, 'send'])->middleware('throttle:send');
    Route::get('/status', [N8nController::class, 'status']);
    Route::get('/webhook-settings', [N8nController::class, 'webhookSettings']);
    Route::post('/webhook-settings', [N8nController::class, 'updateWebhookSettings']);
    Route::post('/webhook-test', [N8nController::class, 'testWebhook']);
});
