<?php

namespace App\Telegram\Commands;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use App\Models\User;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Стартовая команда, выводит инструкции';

    public function getName(): string
    {
        return $this->name;
    }

    public function handle()
    {        
        $this->replyWithChatAction(['action' => Actions::TYPING]);
    }


    // Вычисляем сколько нужно вывести кнопок с ответами
    // public static function createQuestionKeyboard($question): array
    // {
    //     $keyboard = [];
    //     $answers = $question->answers->toArray();

    //     // Если текущий вопрос имеет ID 36, выстраиваем кнопки вертикально
    //     if ($question->id == 66) {
    //         foreach ($answers as $answer) {
    //             $keyboard[] = [[
    //                 'text' => $answer['text'],
    //                 'callback_data' => "question_{$question->id}_answer_{$answer['id']}"
    //             ]];
    //         }
    //     } else {
    //         // Исходная логика для других вопросов
    //         for ($i = 0; $i < count($answers); $i += 2) {
    //             $row = [];

    //             if (isset($answers[$i])) {
    //                 $row[] = [
    //                     'text' => $answers[$i]['text'],
    //                     'callback_data' => "question_{$question->id}_answer_{$answers[$i]['id']}"
    //                 ];
    //             }

    //             if (isset($answers[$i + 1])) {
    //                 $row[] = [
    //                     'text' => $answers[$i + 1]['text'],
    //                     'callback_data' => "question_{$question->id}_answer_{$answers[$i + 1]['id']}"
    //                 ];
    //             }

    //             if (!empty($row)) {
    //                 $keyboard[] = $row;
    //             }
    //         }
    //     }

    //     return $keyboard;
    // }
}
