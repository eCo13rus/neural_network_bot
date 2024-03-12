<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'neural_network_text_id',
        'neural_network_image_id',
        'neural_network_tts_id',
        'context_characters_count',
        'max_images_batch'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function neuralNetworkText()
    {
        return $this->belongsTo(NeuralNetwork::class, 'neural_network_text_id');
    }

    public function neuralNetworkImage()
    {
        return $this->belongsTo(NeuralNetwork::class, 'neural_network_image_id');
    }

    public function neuralTts()
    {
        return $this->belongsTo(NeuralNetwork::class, 'neural_network_tts_id');
    }
}
