<?php

namespace App\Notifications;

use App\Models\Clip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when someone who follows you creates a new post
 */
class NewPostByFollower extends Notification
{
    use Queueable;

    public function __construct(public Clip $clip)
    {
        // Load necessary relations for the notification payload
        $this->clip->loadMissing(['user:id,name,profile_photo']);
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
            'type' => 'new_post_by_follower',
            'clip_id' => $this->clip->id,
            'sender' => [
                'id' => $this->clip->user_id,
                'name' => $this->clip->user->name ?? 'Someone',
                'profile_photo' => $this->clip->user->profile_photo ?? null,
            ],
            'post' => [
                'id' => $this->clip->id,
                'media_url' => $this->clip->video_url,
                'thumbnail_url' => $this->clip->thumbnail_url,
                'description' => $this->clip->description,
            ],
            'message' => ($this->clip->user->name ?? 'Someone') . ' (your follower) posted a new clip',
            'created_at' => $this->clip->created_at?->toISOString(),
        ];
    }
}
