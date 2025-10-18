<?php

namespace App\Notifications;

use App\Models\Like;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when someone likes a user's post/clip
 */
class PostLiked extends Notification
{
    use Queueable;

    public function __construct(public Like $like)
    {
        // Load necessary relations for the notification payload
        $this->like->loadMissing([
            'user:id,name,profile_photo',
            'clip:id,video_url,thumbnail_url,description,user_id'
        ]);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_liked',
            'like_id' => $this->like->id,
            'sender' => [
                'id' => $this->like->user_id,
                'name' => $this->like->user->name ?? 'Someone',
                'profile_photo' => $this->like->user->profile_photo ?? null,
            ],
            'post' => [
                'id' => $this->like->clip_id,
                'media_url' => $this->like->clip->video_url ?? null,
                'thumbnail_url' => $this->like->clip->thumbnail_url ?? null,
                'description' => $this->like->clip->description ?? null,
            ],
            'message' => ($this->like->user->name ?? 'Someone') . ' liked your post',
            'created_at' => $this->like->created_at?->toISOString(),
        ];
    }
}
