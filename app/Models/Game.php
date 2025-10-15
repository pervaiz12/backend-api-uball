<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    protected $fillable = [
        'location',
        'game_date',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function playerStats()
    {
        return $this->hasMany(PlayerStat::class);
    }

    public function clips()
    {
        return $this->hasMany(Clip::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Game $game) {
            // Delete all clips related to this game
            $game->clips()->each(function (Clip $clip) {
                // Delete likes and comments for each clip
                $clip->likes()->delete();
                $clip->comments()->delete();
                $clip->delete();
            });

            // Delete all player stats for this game
            $game->playerStats()->delete();
        });
    }
}
