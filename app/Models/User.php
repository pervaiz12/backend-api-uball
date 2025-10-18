<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo',
        'home_court',
        'city',
        'phone',
        'role',
        'is_official',
        'official_request',
        'google_id',
        'facebook_id',
        'apple_id',
        'fcm_token',
        'fcm_token_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_official' => 'boolean',
            'official_request' => 'string',
            'last_login' => 'datetime',
        ];
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id');
    }

    public function games()
    {
        return $this->hasMany(Game::class, 'created_by');
    }

    public function playerStats()
    {
        return $this->hasMany(PlayerStat::class);
    }

    public function clips()
    {
        return $this->hasMany(Clip::class);
    }

    /**
     * Clips where this user is the tagged player (not the uploader).
     */
    public function taggedClips()
    {
        return $this->hasMany(Clip::class, 'player_id');
    }

    public function messagesSent()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            Like::where('user_id', $user->id)->delete();
            Comment::where('user_id', $user->id)->delete();

            $user->taggedClips()->each(function (Clip $clip) {
                $clip->likes()->delete();
                $clip->comments()->delete();
                $clip->delete();
            });

            $user->clips()->each(function (Clip $clip) {
                $clip->likes()->delete();
                $clip->comments()->delete();
                $clip->delete();
            });
        });
    }
}
