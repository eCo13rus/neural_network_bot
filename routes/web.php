<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramBotController;
use App\Services\SDXLCallbackService\SDXLCallbackService;
use App\Services\ChatGPTCallbackService\ChatGPTCallbackService;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/telegram-webhook', [TelegramBotController::class, 'processingWebhook']);

Route::post('/telegram-webhook', [TelegramBotController::class, 'processingWebhook']);

Route::get('/dalle-callback/{chat_id}', [SDXLCallbackService::class, 'processSDXLCallback'])->name('dalle.callback');

Route::post('/dalle-callback/{chat_id}', [SDXLCallbackService::class, 'processSDXLCallback'])->name('dalle.callback');

Route::get('/chat-gpt-callback/{chatId}', [ChatGPTCallbackService::class, 'processChatGPTCallback'])->name('chat_gpt.callback');

Route::post('/chat-gpt-callback/{chatId}', [ChatGPTCallbackService::class, 'processChatGPTCallback'])->name('chat_gpt.callback');