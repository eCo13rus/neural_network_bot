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
        $this->apiKey = env('CHATGPT_API_KEY');
    }

    // Запрос к ChatGPT
    public function queryChatGPTApi(string $prompt, int $chatId): array
    {
        Log::info("Выполняется запрос к gen-api.ru", ['question' => $prompt, 'chatId' => $chatId]);

        $user = User::where('telegram_id', $chatId)->first();
        
        $userSettings = UserSetting::where('user_id', $user->id)->first();
        
        $contextLimit = $userSettings ? $userSettings->context_characters_count : 0;

        Log::info('Колличество контекста', ['contextLimit' => $contextLimit]);

        $historyEntries = $this->getUserMessageHistory($user->id);
        
        $messages = $this->formatMessagesForRequest($historyEntries, $prompt, $contextLimit);

        Log::info('Контекст для запроса', ['messages' => $messages]);
        
        return $this->makeRequestToChatGPTApi($messages);
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

        // Проходимся по массиву сообщений с конца, чтобы сохранить последние сообщения
        foreach (array_reverse($messages) as $message) {
            $messageLength = mb_strlen($message['content']);
            if ($totalLength + $messageLength > $limit) break;
            $totalLength += $messageLength;
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
    protected function makeRequestToChatGPTApi(array $messages): array
    {
        try {
            $response = $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/chat-gpt-4-turbo',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'messages' => $messages,
                        'is_sync' => true,
                    ],
                ]
            );

            $body = json_decode((string) $response->getBody(), true);
            Log::info("Успешный ответ от gen-api.ru", ['body' => $body]);

            return $body['output'] ?? $body;
        } catch (RequestException $e) {
            Log::error('Ошибка при запросе к gen-api.ru: ' . $e->getMessage());
            return [
                'error' => 'Ошибка при запросе к ChatGPT.',
                'details' => $e->getMessage()
            ];
        }
    }

    // Обработка запроса от ChatGPT
    public function handleRequest(string $prompt, int $chatId): string
    {
        // Отправляем действие "печатает" перед началом обработки
        TelegramFacade::sendChatAction([
            'chat_id' => $chatId,
            'action' => 'typing',
        ]);

        $response = $this->queryChatGPTApi($prompt, $chatId);

        try {
            $responseText = isset($response['choices'][0]['message']['content'])
                ? $response['choices'][0]['message']['content'] : 'Извините, не удалось получить ответ от ChatGPT.';

            return $responseText;
        } catch (\Exception $e) {
            Log::error('Error handling ChatGPT request', ['exception' => $e->getMessage()]);
            return 'Извините, произошла ошибка при обработке вашего запроса.';
        }
    }
}
