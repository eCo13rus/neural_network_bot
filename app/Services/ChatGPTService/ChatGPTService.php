<?php

namespace App\Services\ChatGPTService;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Contracts\NeuralNetworkServiceInterface;
use App\Models\MessageHistory;
use App\Models\User;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;

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

        // Извлечение истории сообщений пользователя
        $user = User::where('telegram_id', $chatId)->firstOrFail();
        $historyEntries = MessageHistory::where('user_id', $user->id)->get();

        $messages = [];

        foreach ($historyEntries as $entry) {
            // Фильтрация сообщений с пустым содержимым
            if (!empty($entry->message_text)) {
                $messages[] = [
                    'role' => $entry->is_from_user ? 'user' : 'assistant',
                    'content' => $entry->message_text
                ];
            }
        }

        // Добавление текущего запроса пользователя в массив сообщений
        if (!empty($prompt)) {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        Log::info("История в queryChatGPTApi", ['messages' => $messages, 'history' => $historyEntries]);

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
