<?php

namespace App\Contracts;

interface NeuralNetworkServiceInterface
{
    public function handleRequest(string $prompt, int $chatId): string;
}
