<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeuralNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
    ];

    public function requests()
    {
        return $this->hasMany(Request::class, 'neural_network_id');
    }

    public function userSettingText()
    {
        return $this->hasMany(UserSetting::class, 'neural_network_text_id');
    }

    public function userSettingImage()
    {
        return $this->hasMany(UserSetting::class, 'neural_network_image_id');
    }

    public function messageHistories()
    {
        return $this->hasMany(MessageHistory::class, 'neural_history_network_id');
    }
}
