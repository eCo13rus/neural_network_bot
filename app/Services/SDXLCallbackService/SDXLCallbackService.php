<?php

namespace App\Services\SDXLCallbackService;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Http\Request;
use App\Services\Telegram\QuizService\QuizService;
use App\Models\User;
use App\Services\Telegram\ServiceCheckSubscription\ServiceCheckSubscription;
use App\Models\UserState;
use App\Services\SDXLService\SDXLService;

class SDXLCallbackService
{
    protected $sdxlService;

    public function __construct(SDXLService $sdxlService)
    {
        $this->sdxlService = $sdxlService;
    }

    // Обрабатывает колбэк от SDXL API
    public function processSDXLCallback(Request $request, int $chatId)
    {
        Log::info("Получен колбэк от SDXL", ['chatId' => $chatId, 'requestData' => $request->all()]);

        $data = $request->json()->all();

        if (!isset($data['request_id'])) {
            Log::error("Отсутствует request_id в данных колбэка", ['data' => $data]);
            return response()->json(['error' => 'Отсутствует request_id'], 400);
        }

        switch ($data['status']) {
            case 'success':
                $this->handleSuccessStatus($data, $chatId);
                break;
            default:
                Log::error("Некорректный статус в данных колбэка", ['data' => $data]);
                TelegramFacade::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Извините, произошла неизвестная ошибка.'
                ]);
        }

        return response()->json(['status' => 'success']);
    }

    // Обрабатывает статус 'processing' колбэка от SDXL API
    protected function handleProcessingStatus($data, $chatId)
    {
        Log::info("Запрос успешно обработан", ['data' => $data]);
        $imageUrl = $data['result'][0] ?? null;

        if ($imageUrl) {
            $this->sendImageToTelegram($imageUrl, $chatId);
        } else {
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Ваше изображение все еще обрабатывается. Пожалуйста, подождите.'
            ]);
        }
    }

    // Обрабатывает статус 'success' колбэка от SDXL API
    protected function handleSuccessStatus($data, $chatId, $processingMessageId = null)
    {
        Log::info("Запрос успешно обработан", ['data' => $data]);

        // Проверяем наличие результатов в ответе
        if (!isset($data['result']) || empty($data['result'])) {
            Log::error("Отсутствуют результаты в данных успешного колбэка", ['data' => $data]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Извините, не удалось получить результат.'
            ]);
            return;
        }

        $imageUrl = $data['result'][0];

        // Отправляем изображение
        $this->sendImageToTelegram($imageUrl, $chatId, $processingMessageId);
    }

    // Отправляет сгенерированное изображение в чат Telegram и удаляет сообщение об обработке запроса
    protected function sendImageToTelegram($imageUrl, $chatId, $processingMessageId = null)
    {
        Log::info("Отправляем изображение в Telegram", ['imageUrl' => $imageUrl]);

        // Создаем экземпляр InputFile из URL изображения
        $photo = InputFile::create($imageUrl);
        TelegramFacade::sendPhoto([
            'chat_id' => $chatId,
            'photo' => $photo,
        ]);
    }
}
