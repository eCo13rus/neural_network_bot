<?php

namespace App\Factories;

use App\Contracts\NeuralNetworkServiceInterface;
use App\Services\ChatGPTService\ChatGPTService;

class NeuralNetworkServiceFactory
{
    public static function create(string $networkName): ?NeuralNetworkServiceInterface
    {
        switch ($networkName) {
            case 'GPT-4 Turbo':
                return new ChatGPTService();
            default:
                return null;
        }
    }
}
