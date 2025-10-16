<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clip extends Model
{
    /** @use HasFactory<\Database\Factories\ClipFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'player_id',
        'game_id',
        'video_url',
        'external_video_url',
        'thumbnail_url',
        'title',
        'description',
        'tags',
        'team_name',
        'opponent_team',
        'game_result',
        'team_score',
        'opponent_score',
        'fg_percentage',
        'three_pt_percentage',
        'four_pt_percentage',
        'status',
        'visibility',
        'show_in_trending',
        'show_in_profile',
        'feature_on_dashboard',
        'season',
        'duration',
        'likes_count',
        'comments_count',
        'views_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'show_in_trending' => 'boolean',
        'show_in_profile' => 'boolean',
        'feature_on_dashboard' => 'boolean',
        'fg_percentage' => 'decimal:2',
        'three_pt_percentage' => 'decimal:2',
        'four_pt_percentage' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::deleting(function ($clip) {
            // Delete associated player stats when clip is deleted
            if ($clip->player_id && $clip->game_id) {
                PlayerStat::where('game_id', $clip->game_id)
                         ->where('user_id', $clip->player_id)
                         ->delete();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
