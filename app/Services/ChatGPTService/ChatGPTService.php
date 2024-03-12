<?php

namespace App\Services\ChatGPTService;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Contracts\NeuralNetworkServiceInterface;

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
    public function queryChatGPTApi(string $question, int $chatId): array
    {
        Log::info("Выполняется запрос к gen-api.ru", ['question' => $question, 'chatId' => $chatId]);

        try {
            $response = $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/chat-gpt-4-turbo',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $question
                            ]
                        ],
                        'is_sync' => true,
                    ],
                ]
            );

            $body = json_decode((string) $response->getBody(), true);

            Log::info("Успешный ответ от gen-api.ru", ['body' => $body]);

            return $body['output'] ?? $body;
        } catch (RequestException $e) {
            Log::error('Ошибка при запросе к gen-api.ru: ' . $e->getMessage());

            Log::error('Детали ошибки запроса', [
                'request' => $e->getRequest() ? (string) $e->getRequest()->getBody() : 'пустой боди',
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'пусто ответ',
            ]);

            return ([
                'error' => 'Ошибка при запросе к ChatGPT.',
                'details' => $e->getMessage()
            ]);
        }
    }

    // Обработка запрос от ChatGPT
    public function handleRequest(string $question, int $chatId): string
    {
        $response = $this->queryChatGPTApi($question, $chatId);

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
