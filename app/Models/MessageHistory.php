<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageHistory extends Model
{
    use HasFactory;

    protected $table = 'message_history';

    protected $fillable = [
        'user_id',
        'message_text',
        'is_from_user',
        'neural_history_network_id',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function neuralNetwork()
    {
        return $this->belongsTo(NeuralNetwork::class, 'neural_history_network_id');
    }
}
