<?php

namespace App\Services\ChatGPTService;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Contracts\NeuralNetworkServiceInterface;
use App\Models\MessageHistory;
use App\Models\User;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Illuminate\Support\Collection;
use App\Models\UserSetting;

class ChatGPTService implements NeuralNetworkServiceInterface
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('API_KEY');
    }

    // Запрос к ChatGPT уже с тоговыми настройками юзера
    public function queryChatGPTApi(string $prompt, int $chatId): array
    {
        Log::info("Выполняется запрос к gen-api.ru", ['question' => $prompt, 'chatId' => $chatId]);

        $user = User::where('telegram_id', $chatId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['chatId' => $chatId]);
            return ['error' => 'Пользователь не найден.'];
        }

        $userSettings = UserSetting::where('user_id', $user->id)->first();

        $contextLimit = $userSettings ? $userSettings->context_characters_count : 0;

        Log::info('Количество контекста', ['contextLimit' => $contextLimit]);

        $historyEntries = $this->getUserMessageHistory($user->id);

        $messages = $this->formatMessagesForRequest($historyEntries, $prompt, $contextLimit);

        Log::info('Контекст для запроса', ['messages' => $messages]);

        // Инициация асинхронного запроса
        $this->makeRequestToChatGPTApi($messages, $chatId);

        // Возврат информации о статусе инициации запроса
        return ['status' => 'processing', 'message' => 'Запрос обрабатывается, ожидайте ответ.'];
    }


    // Извлекает историю сообщений пользователя.
    protected function getUserMessageHistory(int $userId): Collection
    {
        return MessageHistory::where('user_id', $userId)->get();
    }

    // Обрезает массив сообщений до заданного лимита символов, сохраняя последние сообщения.
    protected function limitMessagesByCharacters(array $messages, int $limit): array
    {
        $totalLength = 0;
        $limitedMessages = [];

        foreach (array_reverse($messages) as $message) {
            if (is_string($message['content'])) {
                $contentLength = mb_strlen($message['content']);
            } elseif (is_array($message['content'])) {
                // Преобразуем массив в строку, чтобы проверить его длину
                $contentLength = mb_strlen(implode(" ", $message['content']));
            } elseif (is_object($message['content']) && property_exists($message['content'], 'request_id')) {
                // Если content является объектом с request_id, обрабатываем его как строку
                $contentLength = mb_strlen((string) $message['content']->request_id);
            } else {
                // Если тип content не поддерживается, пропускаем его
                continue;
            }

            if ($totalLength + $contentLength > $limit) break;
            $totalLength += $contentLength;
            array_unshift($limitedMessages, $message); // Добавляем сообщение в начало массива
        }

        return $limitedMessages;
    }

    //Формирует массив сообщений для запроса, включая текущий запрос пользователя.
    protected function formatMessagesForRequest(Collection $historyEntries, string $prompt, int $contextLimit): array
    {
        $messages = [];

        foreach ($historyEntries as $entry) {
            if (!empty($entry->message_text)) {
                $messages[] = [
                    'role' => $entry->is_from_user ? 'user' : 'assistant',
                    'content' => $entry->message_text
                ];
            }
        }

        if ($contextLimit > 0) {
            $messages = $this->limitMessagesByCharacters($messages, $contextLimit);
        } elseif ($contextLimit === 0) {
            $messages = [];
        }

        if (!empty($prompt)) {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        return $messages;
    }

    // Отправляет запрос к API ChatGPT и возвращает ответ.
    protected function makeRequestToChatGPTApi(array $messages, int $chatId): void
    {
        $callbackUrl = env('TELEGRAM_URL') . "/chat-gpt-callback/" . $chatId;

        try {
            $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/chat-gpt-4-turbo',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'messages' => $messages,
                        'callback_url' => $callbackUrl,
                        'is_sync' => false, // Убедись, что запрос отправляется асинхронно
                    ],
                ]
            );

            Log::info("Запрос на обработку отправлен", ['chatId' => $chatId]);
        } catch (RequestException $e) {
            Log::error('Ошибка при запросе к gen-api.ru: ' . $e->getMessage(), ['chatId' => $chatId]);
            // Здесь можно отправить сообщение об ошибке через Telegram, если это необходимо
        }
    }

    public function sendToTelegram(int $chatId, string $response): void
    {
    }

    // Обработка ответа от ChatGPT
    public function handleRequest(string $prompt, int $chatId): string
    {
        // Отправляем действие "печатает" перед началом обработки
        TelegramFacade::sendChatAction([
            'chat_id' => $chatId,
            'action' => 'typing',
        ]);

        $response = $this->queryChatGPTApi($prompt, $chatId);
        Log::info("Ответ в response в handleRequest ", ['chatId' => $response]);

        return "Ваш запрос обрабатывается, ожидайте ответ.";
    }
}
