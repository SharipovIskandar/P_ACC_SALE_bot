<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramBotController;

// Webhook route
Route::post('/webhook', [TelegramBotController::class, 'handleWebhook']);
