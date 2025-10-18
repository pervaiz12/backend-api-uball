<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when someone comments on a user's post/clip
 */
class PostCommented extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment)
    {
        // Load necessary relations for the notification payload
        $this->comment->loadMissing([
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
        $commentPreview = mb_substr($this->comment->body, 0, 80);
        
        return [
            'type' => 'post_commented',
            'comment_id' => $this->comment->id,
            'sender' => [
                'id' => $this->comment->user_id,
                'name' => $this->comment->user->name ?? 'Someone',
                'profile_photo' => $this->comment->user->profile_photo ?? null,
            ],
            'comment' => [
                'body' => $this->comment->body,
                'preview' => $commentPreview,
            ],
            'post' => [
                'id' => $this->comment->clip_id,
                'media_url' => $this->comment->clip->video_url ?? null,
                'thumbnail_url' => $this->comment->clip->thumbnail_url ?? null,
                'description' => $this->comment->clip->description ?? null,
            ],
            'message' => ($this->comment->user->name ?? 'Someone') . ' commented: "' . $commentPreview . '"',
            'created_at' => $this->comment->created_at?->toISOString(),
        ];
    }
}
