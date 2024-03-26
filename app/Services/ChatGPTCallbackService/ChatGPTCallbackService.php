<?php

namespace App\Services\ChatGPTCallbackService;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Http\Request;
use App\Services\UserService\UserService;
use App\Models\User;
use App\Models\UserSetting;

class ChatGPTCallbackService
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function processChatGPTCallback(Request $request, int $chatId)
    {
        Log::info("Получен колбэк от GPT", ['chatId' => $chatId, 'requestData' => $request->all()]);

        $data = $request->json()->all();
        Log::info("В data в processChatGPTCallback", ['requestData' => $data]);

        $user = User::where('telegram_id', $chatId)->first();

        $neuralNetworkId = $this->getUserTextNeuralNetworkId($chatId);

        // Попытка найти и отправить сообщение из ответа
        if (isset($data['result'][0]['message']['content'])) {
            $responseContent = $data['result'][0]['message']['content'];
            // Отправка сообщения пользователю
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => $responseContent,
            ]);
            Log::info('Из processChatGPTCallback сохранилось и отправилось сообщение из responseContent', ['response' => $responseContent]);

            // Сохранение ответа от нейросети в базе данных
            $this->userService->saveMessage($user->id, $responseContent, false, $neuralNetworkId);
        } else {
            // Если в ответе нет содержимого, отправляем сообщение об ошибке
            $errorMessage = 'Извините, не удалось получить ответ от ChatGPT.';
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => $errorMessage,
            ]);
            // Сохранение сообщения об ошибке
            $this->userService->saveMessage($user->id, $errorMessage, false);
        }
    }

    public function getUserTextNeuralNetworkId(int $telegramId): ?int
    {
        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            Log::warning("Пользователь с telegram_id не найден", ['telegramId' => $telegramId]);
            return null;
        }

        $userSetting = UserSetting::where('user_id', $user->id)->first();
        if ($userSetting) {
            Log::info("Найден UserSetting", ['userSetting' => $userSetting]);
            return $userSetting->neural_network_text_id;
        } else {
            Log::warning("UserSetting не найден для пользователя", ['userId' => $user->id]);
            return null;
        }
    }
}
