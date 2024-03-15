<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\UserState;

class TelegramBotService
{
    // Получаем текущее состояние пользователя
    public function getUserState(int $userId): ?UserState
    {
        $userState = UserState::where('user_id', $userId)->first();
        Log::info('Состояние пользователя получено', ['user_id' => $userId, 'state' => $userState ? $userState->current_state : 'null']);

        return $userState;
    }

    // Обновляем текущее состояние пользователя
    public function updateUserState(int $userId, string $newState): UserState
    {
        $userState = UserState::firstOrNew(['user_id' => $userId]);
        $userState->current_state = $newState;
        $userState->last_update = now();
        $userState->save();

        Log::info('Состояние пользователя обновлено', ['user_id' => $userId, 'new_state' => $newState]);

        return $userState;
    }
}
