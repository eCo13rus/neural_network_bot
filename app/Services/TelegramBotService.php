<?php

namespace App\Services;

use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserState;
use App\Models\NeuralNetwork;
use App\Models\UserSetting;
use App\Factories\NeuralNetworkServiceFactory;

class TelegramBotService
{
    // Получаем текущее состояние пользователя
    public function getUserState(int $userId): ?UserState
    {
        $userState = UserState::where('user_id', $userId)->first();
        Log::info('Состояние пользователя получено', ['user_id' => $userId, 'state' => $userState ? $userState->current_state : 'null']);

        return $userState;
    }

    // Обновляем текущее состояние пользователя
    public function updateUserState(int $userId, string $newState): UserState
    {
        $userState = UserState::firstOrNew(['user_id' => $userId]);
        $userState->current_state = $newState;
        $userState->last_update = now();
        $userState->save();

        Log::info('Состояние пользователя обновлено', ['user_id' => $userId, 'new_state' => $newState]);

        return $userState;
    }

    // Выводит описание выбранной нейросети для пользователя
    public function handleNeuralNetworkSelection(int $chatId, string $networkName): void
    {
        $user = User::where('telegram_id', $chatId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        $neuralNetwork = NeuralNetwork::where('name', $networkName)->first();

        if (!$neuralNetwork) {
            Log::error('Нейросеть не найдена', ['name' => $networkName]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Нейросеть не найдена.",
            ]);
            return;
        }

        $messageText = "<strong>Вы выбрали: " . htmlspecialchars($neuralNetwork->name) . "</strong>.\n\n";
        $messageText .= "<em>" . htmlspecialchars($neuralNetwork->description) . "</em>\n\n⬇️Можете сделать запрос⬇️";

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'HTML',
            'text' => $messageText,
        ]);

        // Обновляем настройки, какую нейронку выбрал
        $this->updateUserNeuralNetworkSetting($chatId, $neuralNetwork);

        // Получаем сервис для выбранной нейросети через фабрику
        $neuralNetworkService = NeuralNetworkServiceFactory::create($networkName);
        if ($neuralNetworkService === null) {
            Log::warning('Сервис для обработки запросов к нейросети не найден', ['network_name' => $networkName]);
            // Можно отправить сообщение пользователю, что сервис временно недоступен, если требуется
        } else {
            // Здесь можно выполнить дополнительные действия с сервисом, если необходимо
            Log::info('Сервис для нейросети получен и готов к использованию', ['network_name' => $networkName]);
        }

        Log::info('Нейросеть выбрана пользователем', ['chat_id' => $chatId, 'network_name' => $networkName]);
    }

    // Обновляет и устанавливет настройки нейросети для пользователя, на те что он выбрал
    protected function updateUserNeuralNetworkSetting(int $chatId, $neuralNetwork): void
    {
        $user = User::where('telegram_id', $chatId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        // Существующие настройки пользователя
        $settings = UserSetting::where('user_id', $user->id)->first();

        // Если настройки не найдены, создаем новые
        if ($settings === null) {
            $settings = new UserSetting();
            $settings->user_id = $user->id;
        }

        // Обновляем настройки в зависимости от типа нейросети
        switch ($neuralNetwork->type) {
            case 'generates text':
                $settings->neural_network_text_id = $neuralNetwork->id;
                break;
            case 'generates images':
                $settings->neural_network_image_id = $neuralNetwork->id;
                break;
            case 'text-to-speech':
                $settings->neural_network_tts_id = $neuralNetwork->id;
                break;
        }

        $settings->save(); // Сохраняем изменения в настройках
        Log::info('Настройки нейросети пользователя обновлены', ['user_id' => $user->id, 'neural_network_id' => $neuralNetwork->id]);
    }

    // Метод для обработки запросов пользователя к выбранной нейросети
    protected function handleUserQuery(int $chatId, string $queryText, NeuralNetwork $neuralNetwork): void
    {
        Log::info('Начало обработки запроса пользователя', ['chatId' => $chatId, 'queryText' => $queryText, 'neuralNetwork' => $neuralNetwork->name]);

        $neuralNetworkService = NeuralNetworkServiceFactory::create($neuralNetwork->name);

        if (!$neuralNetworkService) {
            Log::warning('Сервис для обработки запросов к нейросети не найден', ['network_name' => $neuralNetwork->name]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Сервис для выбранной нейросети временно недоступен.",
            ]);
            return;
        }

        $responseText = $neuralNetworkService->handleRequest($queryText, $chatId);

        Log::info('Получен ответ от сервиса нейросети', ['responseText' => $responseText]);

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => $responseText,
        ]);
    }

    // Метод для обработки входящих сообщений от пользователя
    public function handleMessage(int $chatId, string $messageText): void
    {
        Log::info('Начало обработки входящего сообщения', ['chatId' => $chatId, 'messageText' => $messageText]);

        $user = User::where('telegram_id', $chatId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        $settings = UserSetting::where('user_id', $user->id)->first();

        // Проверка на соответствие сообщения имени одной из нейросетей
        $neuralNetworks = NeuralNetwork::pluck('name')->toArray();
        if (in_array($messageText, $neuralNetworks)) {
            // Если сообщение соответствует имени нейросети
            $this->handleNeuralNetworkSelection($chatId, $messageText);
        } else {
            // Если настройки не установлены, просим пользователя выбрать нейросеть
            if (!$settings) {
                TelegramFacade::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Пожалуйста, сначала выберите нейросеть.",
                ]);
                return;
            }

            $neuralNetwork = NeuralNetwork::find($settings->neural_network_text_id);

            if (!$neuralNetwork) {
                Log::error('Нейросеть не найдена', ['neural_network_id' => $settings->neural_network_text_id]);
                TelegramFacade::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Ошибка: выбранная нейросеть не найдена.",
                ]);
                return;
            }

            // Обрабатываем запрос к нейросети
            $this->handleUserQuery($chatId, $messageText, $neuralNetwork);
        }
    }
}
