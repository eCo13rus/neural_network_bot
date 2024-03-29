<?php

namespace App\Services\MidjourneyCallbackService;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Illuminate\Http\Request;

class MidjourneyCallbackService
{
    public function proccessMidjourneyCallback(Request $request, int $chatId)
    {
        Log::info("Получен колбэк от Midjourney", ['chatId' => $chatId, 'requestData' => $request->all()]);

        $data = $request->json()->all();

        if (isset($data['status']) && $data['status'] === 'success' && is_array($data['result']) && count($data['result']) > 0) {
            $media = collect($data['result'])->map(function ($imageUrl, $key) {
                // Формируем объект для каждого изображения
                return [
                    'type' => 'photo',
                    'media' => $imageUrl,
                ];
            })->toArray();

            Log::info("Фото", ['chatId' => $chatId, 'requestData' => $media]);

            try {
                // Отправляем медиагруппу
                TelegramFacade::sendMediaGroup([
                    'chat_id' => $chatId,
                    'media' => json_encode($media),
                ]);

                Log::info("Изображения успешно отправлены пользователю в группе", ['chatId' => $chatId]);
            } catch (\Exception $e) {
                Log::error("Ошибка при отправке группы изображений пользователю", ['chatId' => $chatId, 'error' => $e->getMessage()]);
            }
        } else {
            Log::error("Ошибка или статус обработки не завершён", ['chatId' => $chatId, 'status' => $data['status'] ?? 'unknown', 'data' => $data]);
        }

        return response()->json(['status' => 'success']);
    }
}
