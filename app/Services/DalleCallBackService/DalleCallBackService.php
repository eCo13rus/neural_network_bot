<?php

namespace App\Services\DalleCallBackService;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Http\Request;

class DalleCallBackService
{
    public function proccessDalleCallback(Request $request, int $chatId)
    {
        Log::info("Получен колбэк от Dalle", ['chatId' => $chatId, 'requestData' => $request->all()]);

        // Предполагаем, что колбэк содержит URL сгенерированного изображения и статус обработки
        $data = $request->json()->all();

        // Проверяем статус запроса и наличие URL изображения
        if (isset($data['status']) && $data['status'] === 'success' && isset($data['result'][0]['url'])) {
            // Извлекаем URL изображения
            $imageUrl = $data['result'][0]['url'];

            try {
                // Формируем данные для отправки фотографии
                $photo = InputFile::create($imageUrl);
                // Отправляем фотографию пользователю

                TelegramFacade::sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => $photo,
                ]);
                Log::info("Изображение успешно отправлено пользователю", ['chatId' => $chatId, 'imageUrl' => $imageUrl]);
            } catch (\Exception $e) {
                Log::error("Ошибка при отправке изображения пользователю", ['chatId' => $chatId, 'error' => $e->getMessage()]);
            }
        } else {
            Log::error("Ошибка или статус обработки не завершён", ['chatId' => $chatId, 'status' => $data['status'] ?? 'unknown', 'data' => $data]);
        }

        return response()->json(['status' => 'success']);
    }
}
