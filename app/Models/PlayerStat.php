<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerStat extends Model
{
    /** @use HasFactory<\Database\Factories\PlayerStatFactory> */
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id',
        'points',
        'rebounds',
        'assists',
        'steals',
        'blocks',
        'fg_made',
        'fg_attempts',
        'three_made',
        'three_attempts',
        'minutes_played',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
