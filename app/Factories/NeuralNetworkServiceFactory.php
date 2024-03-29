<?php

namespace App\Factories;

use App\Services\ChatGPTService\ChatGPTService;
use App\Services\SDXLService\SDXLService;
use App\Contracts\NeuralNetworkServiceInterface;
use App\Services\DalleService\DalleService;
use App\Services\TTSService\TTSService;
use Illuminate\Support\Facades\Log;
use App\Services\MidjourneyService\MidjourneyService;

class NeuralNetworkServiceFactory
{
    public static function create(string $networkName): ?NeuralNetworkServiceInterface
    {
        Log::info("Создание сервиса для нейросети", ['networkName' => $networkName]);

        switch ($networkName) {
            case 'GPT-4 Turbo':
                return new ChatGPTService();
            case 'SDXL':
                return new SDXLService();
            case 'TTS-HD':
                return new TTSService();
            case 'DALL-E 3':
                return new DalleService();
            case 'Midjourney':
                return new MidjourneyService();
            default:
                return null;
        }
    }
}
