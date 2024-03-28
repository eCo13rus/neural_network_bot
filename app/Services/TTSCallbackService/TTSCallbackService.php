<?php

namespace App\Services\TTSCallbackService;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Http\Request;

class TTSCallbackService
{
    public function proccessTTSCallback(Request $request, int $chatId)
    {
        Log::info("Получен колбэк от TTS", ['chatId' => $chatId, 'requestData' => $request->all()]);

        // Предполагаем, что колбэк содержит URL сгенерированного аудиофайла и статус обработки
        $data = $request->json()->all();

        // Проверяем статус запроса и наличие URL аудиофайла
        if (isset($data['status']) && $data['status'] === 'success' && isset($data['result'][0])) {
            // Загружаем аудиофайл и отправляем его пользователю
            try {
                $audioData = ['audio' => InputFile::create($data['result'][0]), 'chat_id' => $chatId];
                TelegramFacade::sendAudio($audioData);
                Log::info("Аудиофайл успешно отправлен пользователю", ['chatId' => $chatId, 'audioUrl' => $data['result'][0]]);
            } catch (\Exception $e) {
                Log::error("Ошибка при отправке аудиофайла пользователю", ['chatId' => $chatId, 'error' => $e->getMessage()]);
            }
        } else {
            Log::error("Ошибка или статус обработки не завершён", ['chatId' => $chatId, 'status' => $data['status'] ?? 'unknown', 'data' => $data]);
        }
    }
}
