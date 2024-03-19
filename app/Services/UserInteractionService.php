<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserState;
use App\Models\Transaction;
use App\Models\NeuralNetwork;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;

class UserInteractionService
{
    public function showMainMenu(int $chatId, int $telegramUserId): void
    {
        // Сохраняем пользователя
        $user = User::firstOrCreate(['telegram_id' => $telegramUserId]);
        Log::info('Пользователь найден или сохранен', ['user_id' => $user->id, 'telegram_id' => $telegramUserId]);

        // Устанавливаем состояние пользователя в 'start'
        UserState::updateOrCreate(
            ['user_id' => $user->id],
            ['current_state' => 'start'],
        );
        Log::info('Состояние пользователя start', ['telegram_id' => $telegramUserId]);

        $keyboard = [
            ['Выбрать нейросеть 🔍', 'Мой баланс 💰'],
            ['Пополнить баланс 💳', 'История операций 📋'],
        ];

        $replyKeyboardMarkup = json_encode([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false, // Клавиатура останется открытой после использования
        ]);

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => "Выбери действие из меню:",
            'reply_markup' => $replyKeyboardMarkup,
        ]);
    }

    // Для вывода баланса юзера
    public function showUserBalance(int $chatId, $telegramUserId): void
    {
        $user = User::where('telegram_id', $telegramUserId)->first();

        if (!$user) {
            // Логирование и отправка сообщения об ошибке, если пользователь не найден
            Log::error('Пользователь не найден', ['telegramUserId' => $telegramUserId]);
            return;
        }

        $balance = $user->balance ?? 0; // Если баланс не установлен, считать его равным нулю

        $message = "Ваш текущий баланс: {$balance} рублей";
        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        Log::info('Баланс пользователя показан', ['telegramUserId' => $telegramUserId, 'balance' => $balance]);
    }

    // Пополнение баланса
    public function replenishBalance(int $chatId): void
    {
        $message = "В разработке.";

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        Log::info('Пользователь попытался пополнить баланс', ['chat_id' => $chatId]);
    }

    // Для вывода транзацкий
    public function showUserTransactions(int $chatId, $telegramUserId): void
    {
        $user = User::where('telegram_id', $telegramUserId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['telegramUserId' => $telegramUserId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        $transactions = Transaction::where('user_id', $user->id)->get();

        // Проверяем, существуют ли транзакции и не пустой ли массив транзакций
        if ($transactions === null || $transactions->isEmpty()) {
            $message = "У вас еще нет не каких операций.";
        } else {
            $message = "История ваших транзакций:\n";
            foreach ($user->transactions as $transaction) {
                $message .=
                    "Тип: {$transaction->type}, 
                Сумма: {$transaction->amount}, 
                Дата: " . $transaction->created_at->format('Y-m-d H:i:s') . "\n";
            }
        }

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);
        Log::info('Транзацкии пользователя показаны', ['telegramUserId' => $telegramUserId, 'balance' => $transactions]);
    }

    // Выводит список нейросетей
    public function chooseNeuralNetwork(int $chatId): void
    {
        // Кнопки категорий нейросетей
        $keyboard = [
            ['Нейросеть для текста'],
            ['Нейросети для изображений'],
            ['Нейросеть для озвучки текста'],
            ['Назад ◀️']
        ];

        $replyKeyboardMarkup = json_encode([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => "Выбери категорию нейросетей:",
            'reply_markup' => $replyKeyboardMarkup,
        ]);
        Log::info('Показан список категорий нейросетей');
    }

    // Отображает нейросети по категориям
    public function showNeuralNetworksByCategory(int $chatId, string $categoryType): void
    {
        $neuralNetworks = NeuralNetwork::where('type', $categoryType)->get();

        $buttons = $neuralNetworks->map(function ($network) {
            return ['text' => $network->name];
        })->toArray();

        // Добавляем кнопки "Настройки" и "Назад к категориям" в конец списка
        $buttons = array_merge($buttons, [[
            'text' => 'Настройки ⚙️'
        ], [
            'text' => 'Назад к категориям ◀️'
        ]]);

        $keyboard = array_chunk($buttons, 2);

        $replyKeyboardMarkup = json_encode([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ]);

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => "Выбери нейросеть:",
            'reply_markup' => $replyKeyboardMarkup,
        ]);

        Log::info('Показан список нейросетей для категории', ['category' => $categoryType]);
    }

    // Отображает меню настроек для пользователя
    public function showSettingsMenu(int $chatId, int $telegramUserId): void
    {
        // Поиск пользователя и его настроек
        $user = User::where('telegram_id', $telegramUserId)->first();

        if (!$user) {
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Произошла ошибка. Пользователь не найден.',
            ]);
            return;
        }

        $userSettings = $user->userSetting()->first();

        if (!$userSettings || (!$userSettings->neural_network_text_id && !$userSettings->neural_network_image_id && !$userSettings->neural_network_tts_id)) {
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Пожалуйста, сначала выберите нейросеть.",
            ]);
            return;
        }

        // Устанавливаем состояние пользователя
        $this->setUserState($user->id, 'entering_characters');

        $currentCount = $userSettings ? $userSettings->context_characters_count : 'не установлено';

        $message = "Текущее количество символов контекста: <strong>{$currentCount}</strong>.\n\n" .
            "В настройках вы можете определить, сколько последних символов из ваших сообщений бот будет учитывать для контекста ответа.\n\n" .
            "Введите нужное количество символов для контекста или оставьте текущее (0, чтобы не использовать контекст):";

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'text' => "<em>{$message}</em>",
            'parse_mode' => 'HTML',
        ]);
    }

    // Устанавливает текущее состояние пользователя.
    public function setUserState(int $userId, string $state): void
    {
        // Используем user_id из найденного пользователя
        UserState::updateOrCreate(
            ['user_id' => $userId],
            ['current_state' => $state]
        );
    }

    //Получает текущее состояние пользователя.
    public function getUserState(int $userId): string
    {
        $userState = UserState::where('user_id', $userId)->first();

        return $userState ? $userState->current_state : 'default';
    }
}
