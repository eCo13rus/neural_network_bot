<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelBotController extends Controller
{
    // Проверка подписки пользователя
    public function checkSubscription(Request $request)
    {
        Log::info('Полученный запрос в checkSubscription', ['data' => $request->all()]);

        Log::warning('TEST LOG checkSubscription');
        $userId = $request->input('userId'); // Использование input метода для извлечения userId
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $channelId = env('TG_KANAL');

        Log::info("Проверка подписки для пользователя: $userId");

        try {
            $response = Http::get("https://api.telegram.org/bot$botToken/getChatMember", [
                'chat_id' => $channelId,
                'user_id' => $userId
            ]);

            $data = $response->json();
            $isSubscribed = in_array($data['result']['status'], ['member', 'administrator', 'creator']);
            Log::info('Ответ от Telegram API', ['response' => $data]);
            Log::info("Статус подписки для пользователя $userId: " . ($isSubscribed ? 'Subscribed' : 'Not Subscribed'));

            $webhookUrl = env('QUIZ_WEB_HUK');
            $response = Http::post($webhookUrl, [
                'userId' => $userId,
                'isSubscribed' => $isSubscribed
            ]);

            Log::info('Отправка данных в квиз-бот', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error("Ошибка при проверке подписки для пользователя $userId: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'isSubscribed' => $isSubscribed]);
    }
}
