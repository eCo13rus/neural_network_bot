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
use App\Services\UserInteractionService;

class UserService
{
    protected $neuralServiceSetting;
    protected $userInteractionService;

    public function __construct(NeuralServiceSetting $neuralServiceSetting, UserInteractionService $userInteractionService)
    {
        $this->neuralServiceSetting = $neuralServiceSetting;
        $this->userInteractionService = $userInteractionService;
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

        $user = User::where('telegram_id', $chatId)->first();

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

        $user = $this->findUserByTelegramId($chatId);
        if (!$user) return;

        $currentState = $this->userInteractionService->getUserState($user->id);
        $this->processUserState($currentState, $chatId, $user->id, $messageText);
    }

    // Если пользователь не найден, отправляет сообщение об ошибке
    protected function findUserByTelegramId(int $chatId): ?User
    {
        $user = User::where('telegram_id', $chatId)->first();
        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage(['chat_id' => $chatId, 'text' => "Ошибка: пользователь не найден."]);
        }
        return $user;
    }

    //Обрабатывает состояние пользователя и выводит сообщения в зависимости от состояния.
    protected function processUserState(string $currentState, int $chatId, int $userId, string $messageText): void
    {
        if ($currentState === 'entering_characters') {
            $this->updateContextCharactersCount($chatId, $userId, $messageText);
        } else {
            $this->processRegularMessage($chatId, $userId, $messageText);
        }
    }

    // Обрабатывает обычные сообщения от пользователя, включая выбор нейросети.
    protected function processRegularMessage(int $chatId, int $userId, string $messageText): void
    {
        $neuralNetworks = NeuralNetwork::pluck('name')->toArray();

        if (in_array($messageText, $neuralNetworks)) {
            $this->neuralServiceSetting->handleNeuralNetworkSelection($chatId, $messageText);
        } else {
            $this->handleNeuralNetworkRequest($chatId, $userId, $messageText);
        }
    }

    // Обрабатывает запросы к нейросети, проверяет наличие настроек юзера.
    protected function handleNeuralNetworkRequest(int $chatId, int $userId, string $messageText): void
    {
        $userSettings = UserSetting::where('user_id', $userId)->first();

        if (!$userSettings) {
            TelegramFacade::sendMessage(['chat_id' => $chatId, 'text' => "Пожалуйста, сначала выберите нейросеть."]);
            return;
        }

        $neuralNetwork = NeuralNetwork::find($userSettings->neural_network_text_id);
        $this->handleUserQuery($chatId, $messageText, $neuralNetwork);
    }

    // Обновляет кол-во символов введенным юзером
    public function updateContextCharactersCount(int $chatId, int $userId, string $text): void
    {
        if (!is_numeric($text)) {
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Пожалуйста, введите число",
            ]);
            return;
        }

        $count = intval($text);

        if ($count < 0) {
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Количество символов не может быть отрицательным. Пожалуйста, введите положительное значение.',
            ]);
            return;
        }

        // Обновление или создание записи в таблице user_settings
        UserSetting::updateOrCreate(['user_id' => $userId], ['context_characters_count' => $count]);

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => "Настройки обновлены. Текущее количество символов контекста: {$count}.",
        ]);

        $this->userInteractionService->setUserState($userId, 'default');
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
