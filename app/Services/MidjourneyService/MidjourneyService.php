<?php

namespace App\Services\MidjourneyService;

use Illuminate\Support\Facades\Log;
use App\Contracts\NeuralNetworkServiceInterface;
use GuzzleHttp\Client;
use App\Models\User;
use App\Models\UserSetting;

class MidjourneyService implements NeuralNetworkServiceInterface
{
    protected $apiKey;
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('API_KEY');
    }

    // Запрос к Mijourney
    public function queryMidjourneyApi(string $prompt, int $chatId)
    {
        $callbackUrl = env('TELEGRAM_URL') . "/midjourney-callback/" . $chatId;

        Log::info("Выполняется запрос к gen-api.ru для генерации изображения", ['prompt' => $prompt, 'chatId' => $chatId, 'callbackUrl' => $callbackUrl]);

        try {
            $response = $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/midjourney',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        "model" => "6.0",
                        "mode" => "relax",
                        "prompt" => $prompt,
                        "callback_url" => $callbackUrl,
                        "aspectRatio" => "1:1",
                        "chaos" => 0,
                        "quality" => "1",
                        "stop" => 100,
                        "stylize" => 100,
                        "tile" => false,
                        "weird" => 0,
                        "translate_input" => true,
                        "upgrade_prompt" => false
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
        $apiResponse = $this->queryMidjourneyApi($prompt, $chatId, $userSettings);

        return ('Ваш запрос обрабатывается, пожалуйста, подождите.');
    }
}
