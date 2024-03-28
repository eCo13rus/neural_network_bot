<?php

namespace App\Services\TTSService;

use App\Contracts\NeuralNetworkServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserSetting;

class TTSService implements NeuralNetworkServiceInterface
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('API_KEY');
    }

    // Запрос к TTS
    public function queryTTSApi(string $prompt, int $chatId)
    {
        $callbackUrl = env('TELEGRAM_URL') . "/tts-callback/" . $chatId;

        Log::info("Выполняется запрос к gen-api.ru для генерации звука", ['prompt' => $prompt, 'chatId' => $chatId, 'callbackUrl' => $callbackUrl]);

        try {
            $response = $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/tts-hd',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'input' => $prompt,
                        'callback_url' => $callbackUrl,
                        'is_sync' => false,
                        'voice' => 'echo',
                        'response_format' => 'mp3',
                        'speed' => 1,
                    ]
                ],
            );

            $body = json_decode((string) $response->getBody(), true);

            if (!isset($body['request_id'])) {
                Log::error('Отсутствует request_id в ответе от gen-api.ru', ['responseBody' => $body]);
                return ['error' => 'Отсутствует request_id в ответе от gen-api.ru'];
            }

            Log::info('body из queryTTSApi', ['body' => $body]);

            return ['request_id' => $body['request_id']];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Ошибка при запросе к gen-api.ru для генерации звука: ' . $e->getMessage());
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
        if (!$userSettings || is_null($userSettings->neural_network_tts_id)) {
            Log::error('Нейросеть для звука не выбрана для данного пользователя', ['userId' => $user->id]);
            return json_encode(['error' => 'Нейросеть для обработки звука не выбрана.']);
        }

        // Отправляем запрос к API TTS и получаем ответ
        $apiResponse = $this->queryTTSApi($prompt, $chatId, $userSettings);

        return ('Ваш запрос обрабатывается, пожалуйста, подождите.');
    }
}
