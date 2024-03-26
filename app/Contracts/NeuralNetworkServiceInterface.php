<?php

namespace App\Contracts;

interface NeuralNetworkServiceInterface
{
    public function handleRequest(string $prompt, int $chatId): string;
    public function sendToTelegram(int $chatId, string $response): void;
}
