<?php

namespace App\Factories;

use App\Services\ChatGPTService\ChatGPTService;
use App\Services\SDXLService\SDXLService;
use App\Contracts\NeuralNetworkServiceInterface;
use App\Services\TTSService\TTSService;


class NeuralNetworkServiceFactory
{
    public static function create(string $networkName): ?NeuralNetworkServiceInterface
    {
        switch ($networkName) {
            case 'GPT-4 Turbo':
                return new ChatGPTService();
            case 'SDXL':
                return new SDXLService();
            case 'TTS-HD':
                return new TTSService();
            default:
                return null;
        }
    }
}
