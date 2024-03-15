<?php

namespace App\Services\NeuralServiceSetting;

use Telegram\Bot\Laravel\Facades\Telegram as TelegramFacade;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\NeuralNetwork;
use App\Models\UserSetting;
use App\Factories\NeuralNetworkServiceFactory;

class NeuralServiceSetting
{
    // Выводит описание выбранной нейросети для пользователя
    public function handleNeuralNetworkSelection(int $chatId, string $networkName): void
    {
        $user = User::where('telegram_id', $chatId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        $neuralNetwork = NeuralNetwork::where('name', $networkName)->first();

        $messageText = "<strong>Вы выбрали: " . htmlspecialchars($neuralNetwork->name) . "</strong>.\n\n";
        $messageText .= "<em>" . htmlspecialchars($neuralNetwork->description) . "</em>\n\n⬇️Можете сделать запрос⬇️";

        TelegramFacade::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'HTML',
            'text' => $messageText,
        ]);

        // Обновляем настройки, какую нейронку выбрал пользователь
        $this->updateUserNeuralNetworkSetting($chatId, $neuralNetwork);

        // Получаем сервис для выбранной нейросети через фабрику
        $neuralNetworkService = NeuralNetworkServiceFactory::create($networkName);
        if ($neuralNetworkService === null) {
            Log::warning('Сервис для обработки запросов к нейросети не найден', ['network_name' => $networkName]);
            // Можно отправить сообщение пользователю, что сервис временно недоступен, если требуется
        } else {
            // Здесь можно выполнить дополнительные действия с сервисом, если необходимо
            Log::info('Сервис для нейросети получен и готов к использованию', ['network_name' => $networkName]);
        }

        Log::info('Нейросеть выбрана пользователем', ['chat_id' => $chatId, 'network_name' => $networkName]);
    }

    // Обновляет и устанавливет настройки нейросети для пользователя, на те что он выбрал
    protected function updateUserNeuralNetworkSetting(int $chatId, $neuralNetwork): void
    {
        $user = User::where('telegram_id', $chatId)->first();

        if (!$user) {
            Log::error('Пользователь не найден', ['chat_id' => $chatId]);
            TelegramFacade::sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: пользователь не найден.",
            ]);
            return;
        }

        // Существующие настройки пользователя
        $settings = UserSetting::where('user_id', $user->id)->first();

        // Если настройки не найдены, создаем новые
        if ($settings === null) {
            $settings = new UserSetting();
            $settings->user_id = $user->id;
        }

        // Обновляем настройки в зависимости от типа нейросети
        switch ($neuralNetwork->type) {
            case 'generates text':
                $settings->neural_network_text_id = $neuralNetwork->id;
                break;
            case 'generates images':
                $settings->neural_network_image_id = $neuralNetwork->id;
                break;
            case 'text-to-speech':
                $settings->neural_network_tts_id = $neuralNetwork->id;
                break;
        }

        $settings->save(); // Сохраняем изменения в настройках
        Log::info('Настройки нейросети пользователя обновлены', ['user_id' => $user->id, 'neural_network_id' => $neuralNetwork->id]);
    }
}
