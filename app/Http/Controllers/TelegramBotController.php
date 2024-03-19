<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\UserService\UserService;
use App\Services\UserInteractionService;

class TelegramBotController extends Controller
{
    protected $commandHandlers = [
        '/start'                         => 'showMainMenu',
        'Выбрать нейросеть 🔍'           => 'chooseNeuralNetwork',
        'Нейросеть для текста'          => ['showNeuralNetworksByCategory', 'generates text'],
        'Нейросети для изображений'     => ['showNeuralNetworksByCategory', 'generates images'],
        'Нейросеть для озвучки текста'  => ['showNeuralNetworksByCategory', 'text-to-speech'],
        'Назад к категориям ◀️'         => 'chooseNeuralNetwork',
        'Настройки ⚙️'                   => 'showSettingsMenu',
        'Назад ◀️'                      => 'showMainMenu',
        'Мой баланс 💰'                  => 'showUserBalance',
        'История операций 📋'            => 'showUserTransactions',
        'Пополнить баланс 💳'            => 'replenishBalance',
    ];

    protected $userService;
    protected $userInteractionService;

    public function __construct(UserService $userService, UserInteractionService $userInteractionService)
    {
        $this->userService = $userService;
        $this->userInteractionService = $userInteractionService;
    }

    // Обработчик входящих команд от Telegram.
    protected function handleCommand($chatId, $text, $telegramUserId)
    {
        Log::info('Команда', ['$text' => $text]);
        
        if (isset($this->commandHandlers[$text])) {
            $handler = $this->commandHandlers[$text];

            if (is_array($handler)) {
                // Если обработчик требует дополнительные параметры
                $method = $handler[0];
                $this->userInteractionService->$method($chatId, $handler[1]);
            } else {
                // Вызов метода без дополнительных параметров
                $this->userInteractionService->$handler($chatId, $telegramUserId);
            }
        } else {
            // Обрабатываем запрос к выбранной нейросети
            $this->userService->handleMessage($chatId, $text);
        }
    }

    // Обрабатывает входящий вебхук от Telegram, получает данные и направляет на обработку команд.
    public function processingWebhook(Request $request)
    {
        Log::info('Обработка запроса', ['request' => $request->all()]);

        $update = json_decode($request->getContent(), true);

        if (isset($update['message'])) {
            $message = $update['message'];
            $text = $message['text'] ?? '';
            $chatId = $message['chat']['id'];
            $telegramUserId = $message['from']['id'];

            // Использование нового метода для обработки команд
            $this->handleCommand($chatId, $text, $telegramUserId);
        }

        return response()->json(['status' => 'success']);
    }
}
