<?php

namespace App\Services\SDXLService;

use App\Contracts\NeuralNetworkServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use App\Models\UserState;
use App\Models\User;
use App\Factories\NeuralNetworkServiceFactory;
use App\Models\UserSetting;
use App\Models\NeuralNetwork;

class SDXLService implements NeuralNetworkServiceInterface
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('API_KEY');
    }

    // Запрос к SDXL
    public function querySDXLApi(string $prompt, int $chatId): array
    {
        $callbackUrl = env('TELEGRAM_URL') . "/dalle-callback/" . $chatId;

        Log::info("Выполняется запрос к gen-api.ru для генерации изображения", ['prompt' => $prompt, 'chatId' => $chatId, 'callbackUrl' => $callbackUrl]);

        try {
            $response = $this->client->post(
                'https://api.gen-api.ru/api/v1/networks/sdxl',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'prompt' => $prompt,
                        'callback_url' => $callbackUrl,
                        'num_outputs' => 1,
                        'translate_input' => true,
                        'width' => 1024,
                        'height' => 1024,
                    ],
                ]
            );

            $body = json_decode((string) $response->getBody(), true);

            if (!isset($body['request_id'])) {
                Log::error('Отсутствует request_id в ответе от gen-api.ru', ['responseBody' => $body]);
                return ['error' => 'Отсутствует request_id в ответе от gen-api.ru'];
            }

            Log::info('body из querySDXLApi', ['body' => $body]);


            return ['request_id' => $body['request_id']];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Ошибка при запросе к gen-api.ru для генерации изображения: ' . $e->getMessage());
            return ['error' => 'Ошибка при запросе к gen-api.ru.', 'детали' => $e->getMessage()];
        }
    }

    public function handleRequest(string $prompt, int $chatId): string
    {
        Log::info('Обрабатываем запрос на генерацию изображения', ['prompt' => $prompt, 'chatId' => $chatId]);

        // Получаем пользователя и его настройки
        $user = User::where('telegram_id', $chatId)->first();
        if (!$user) {
            Log::error('Пользователь не найден', ['chatId' => $chatId]);
            return json_encode(['error' => 'Пользователь не найден.']);
        }

        $userSettings = UserSetting::where('user_id', $user->id)->first();
        if (!$userSettings || is_null($userSettings->neural_network_image_id)) {
            Log::error('Нейросеть для изображений не выбрана для данного пользователя', ['userId' => $user->id]);
            return json_encode(['error' => 'Нейросеть для обработки изображений не выбрана.']);
        }

        // Отправляем запрос к API SDXL и получаем ответ
        $apiResponse = $this->querySDXLApi($prompt, $chatId, $userSettings);

        Log::info('В apiResponse', ['chatId' => $apiResponse]);

        return ('Ваш запрос обрабатывается, пожалуйста, подождите.');
    }


    public function sendToTelegram(int $chatId, string $response): void
    {
    }

    // Метод удаления текста запроса из чата
    public function deleteProcessingMessage(int $chatId, int $messageId)
    {
        Log::info('Удаление сообщения об обработке', ['chatId' => $chatId, 'messageId' => $messageId]);
        TelegramFacade::deleteMessage([
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }
}
