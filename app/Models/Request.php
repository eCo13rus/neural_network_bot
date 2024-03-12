<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'neural_network_id',
        'status',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function neuralNetwork()
    {
        return $this->belongsTo(NeuralNetwork::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
