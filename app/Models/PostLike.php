<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLike extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
    ];

    /**
     * Get the user that liked the post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that was liked
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
