<?php

namespace App\Services\DalleService;

use App\Contracts\NeuralNetworkServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserSetting;

class DalleService implements NeuralNetworkServiceInterface
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('API_KEY');
    }

    // Запрос к Dalle
    public function queryDalleApi(string $prompt, int $chatId)
    {
        $callbackUrl = env('TELEGRAM_URL') . "/dalle-callback/" . $chatId;

        Log::info("Выполняется запрос к gen-api.ru для генерации изображения", ['prompt' => $prompt, 'chatId' => $chatId, 'callbackUrl' => $callbackUrl]);

        try {
            $response = $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/dalle-3',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'prompt' => $prompt,
                        'callback_url' => $callbackUrl,
                        'is_sync' => false,
                        'n' => 1,
                        'quality' => 'hd',
                        'response_format' => 'url',
                        'size' => '1024x1024',
                        'style' => 'vivid',
                    ]
                ],
            );

            $body = json_decode((string) $response->getBody(), true);

            if (!isset($body['request_id'])) {
                Log::error('Отсутствует request_id в ответе от gen-api.ru', ['responseBody' => $body]);
                return ['error' => 'Отсутствует request_id в ответе от gen-api.ru'];
            }

            return ['request_id' => $body['request_id']];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Ошибка при запросе к gen-api.ru для генерации изображения: ' . $e->getMessage());
            return ['error' => 'Ошибка при запросе к gen-api.ru.', 'детали' => $e->getMessage()];
        }
    }

    public function handleRequest(string $prompt, int $chatId): string
    {
        // Получаем пользователя и его настройки
        $user = User::where('telegram_id', $chatId)->first();
        if (!$user) {
            Log::error('Пользователь не найден', ['chatId' => $chatId]);
            return json_encode(['error' => 'Пользователь не найден.']);
        }

        $userSettings = UserSetting::where('user_id', $user->id)->first();
        if (!$userSettings || is_null($userSettings->neural_network_image_id)) {
            Log::error('Нейросеть для изображения не выбрана для данного пользователя', ['userId' => $user->id]);
            return json_encode(['error' => 'Нейросеть для обработки изображения не выбрана.']);
        }

        // Отправляем запрос к API TTS и получаем ответ
        $apiResponse = $this->queryDalleApi($prompt, $chatId, $userSettings);

        return ('Ваш запрос обрабатывается, пожалуйста, подождите.');
    }
}
