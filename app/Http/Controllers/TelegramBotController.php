<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;

class TelegramBotController extends Controller
{
    public function processingWebhook(Request $request)
    {
        $data = $request->all(); // Получаем данные запроса

        TelegramFacade::commandsHandler(true);

        // Проверяем, является ли обновление сообщением от пользователя в чате
        if (isset($data['message'])) {
            $chatId = $data['message']['chat']['id']; // Извлекаем идентификатор чата из сообщения
            Log::info("Сообщение из чат-бота: Идентификатор чата: {$chatId}", $data); // Добавляем в лог информацию о том, что это сообщение из чат-бота
        }
        // Проверяем, является ли обновление сообщением из канала
        elseif (isset($data['channel_post'])) {
            $chatId = $data['channel_post']['chat']['id']; // Извлекаем идентификатор канала из сообщения канала
            Log::info("Сообщение из ТГ канала: Идентификатор канала: {$chatId}", $data); // Добавляем в лог информацию о том, что это сообщение из ТГ канала
        } else {
            Log::warning('Полученные данные не содержат идентификатора чата.', $data);
        }

        return response()->json([
            'status' => 'success',
        ]);
    }
}
