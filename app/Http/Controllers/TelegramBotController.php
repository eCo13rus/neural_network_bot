<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramBotService;
use App\Services\UserInteractionService;
use App\Models\NeuralNetwork;

class TelegramBotController extends Controller
{
    protected $telegramBotService;
    protected $userInteractionService;

    public function __construct(TelegramBotService $telegramBotService, UserInteractionService $userInteractionService)
    {
        $this->telegramBotService = $telegramBotService;
        $this->userInteractionService = $userInteractionService;
    }

    public function processingWebhook(Request $request)
    {
        Log::info('Обработка запроса', ['request' => $request->all()]);
        $update = json_decode($request->getContent(), true);

        if (isset($update['message'])) {
            $message = $update['message'];
            $text = $message['text'] ?? '';
            $chatId = $message['chat']['id'];
            $telegramUserId = $message['from']['id'];

            switch ($text) {
                case '/start':
                    if ($telegramUserId) {
                        $this->userInteractionService->showMainMenu($chatId, $telegramUserId);
                        Log::info('Показано главное меню', ['telegramUserId' => $telegramUserId]);
                    }
                    break;
                case 'Выбрать нейросеть 🔍':
                    $this->userInteractionService->chooseNeuralNetwork($chatId);
                    Log::info('Показан выбор нейросети', ['telegramUserId' => $telegramUserId]);
                    break;
                case 'Назад ◀️':
                    if ($telegramUserId) {
                        $this->userInteractionService->showMainMenu($chatId, $telegramUserId);
                        Log::info('Возвращение в главное меню', ['telegramUserId' => $telegramUserId]);
                    }
                    break;
                case 'Мой баланс          💰':
                    if ($telegramUserId) {
                        $this->userInteractionService->showUserBalance($chatId, $telegramUserId);
                        Log::info('Показан баланс пользователя', ['telegramUserId' => $telegramUserId]);
                    }
                    break;
                case 'История операций 📋':
                    if ($telegramUserId) {
                        $this->userInteractionService->showUserTransactions($chatId, $telegramUserId);
                        Log::info('Показана история операций пользователя', ['telegram_id' => $telegramUserId]);
                    }
                    break;
                default:
                    $messageId = $message['message_id'];
                    // Если текст не соответствует названию нейросети, обрабатываем его как запрос к выбранной нейросети
                    $this->telegramBotService->handleMessage($chatId, $text,  $messageId);
                    Log::info('Обработка запроса к нейросети', ['chatId' => $chatId, 'text' => $text]);
                    break;
            }
        }
        return response()->json(['status' => 'success']);
    }
}
