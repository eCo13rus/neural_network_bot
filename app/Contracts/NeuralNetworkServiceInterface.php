<?php

namespace App\Contracts;

interface NeuralNetworkServiceInterface
{
    public function handleRequest(string $query, int $chatId): string;
}
