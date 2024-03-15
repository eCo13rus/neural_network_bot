<?php

namespace App\Services\UserService;

use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\NeuralNetwork;
use App\Models\UserSetting;
use App\Factories\NeuralNetworkServiceFactory;
use App\Models\MessageHistory;
use App\Services\NeuralServiceSetting\NeuralServiceSetting;

class UserService
{
    protected $neuralServiceSetting;
    
    public function __construct(NeuralServiceSetting $neuralServiceSetting)
    {
        $this->neuralServiceSetting = $neuralServiceSetting;
    }

    // Метод для обработки запросов пользователя к выбранной нейросети
    protected function handleUserQuery(int $chatId, string $prompt, NeuralNetwork $neuralNetwork): void
    {
        Log::info('Обработка запроса пользователя', ['chatId' => $chatId, 'queryText' => $prompt, 'neuralNetwork' => $neuralNetwork->name]);

        $neuralNetworkService = NeuralNetworkServiceFactory::create($neuralNetwork->name);

        if (!$neuralNetworkService) {
            Log::warning('Сервис для обработки запросов к нейросети не найден', ['network_name' => $neuralNetwork->name]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Сервис для выбранной нейросети временно недоступен.",
            ]);
            return;
        }

        // Запрос к нейросети
        $responseText = $neuralNetworkService->handleRequest($prompt, $chatId);

        $user = User::where('telegram_id', $chatId)->firstOrFail();

        // Сохраняем входящее сообщение пользователя и ответ нейросети
        $this->saveMessage($user->id, $prompt, true);
        $this->saveMessage($user->id, $responseText, false);

        Log::info('Получен ответ от сервиса нейросети', ['responseText' => $responseText]);

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => $responseText,
        ]);
    }

    // Метод для обработки входящих сообщений от пользователя
    public function handleMessage(int $chatId, string $messageText): void
    {
        Log::info('Обработка входящего сообщения от пользователя', ['chatId' => $chatId, 'messageText' => $messageText]);

        // Поиск пользователя
        $user = User::where('telegram_id', $chatId)->firstOrFail();

        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        $userSettings = UserSetting::where('user_id', $user->id)->first();

        // Проверка на соответствие сообщения имени одной из нейросетей
        $neuralNetworks = NeuralNetwork::pluck('name')->toArray();

        if (in_array($messageText, $neuralNetworks)) {
            // Если сообщение соответствует имени нейросети
            $this->neuralServiceSetting->handleNeuralNetworkSelection($chatId, $messageText);
        } else {
            // Если настройки не установлены, просим пользователя выбрать нейросеть
            if (!$userSettings) {
                TelegramFacade::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Пожалуйста, сначала выберите нейросеть.",
                ]);
                return;
            }

            $neuralNetwork = NeuralNetwork::find($userSettings->neural_network_text_id);

            // Обрабатываем запрос к нейросети
            $this->handleUserQuery($chatId, $messageText, $neuralNetwork);
        }
    }

    // Сохраняет сообщение пользователя и ответ нейросети в историю.
    protected function saveMessage(int $userId, string $message, bool $isFromUser): void
    {
        if (!empty($message)) {
            MessageHistory::create([
                'user_id' => $userId,
                'message_text' => $message,
                'is_from_user' => $isFromUser ? 1 : 0,
            ]);
        }
    }
}