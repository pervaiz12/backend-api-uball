<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Clip;

class PostCommented implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $clip;
    public $commenter;
    public $clipOwner;
    public $commentContent;

    /**
     * Create a new event instance.
     */
    public function __construct(Clip $clip, User $commenter, User $clipOwner, string $commentContent)
    {
        $this->clip = $clip;
        $this->commenter = $commenter;
        $this->clipOwner = $clipOwner;
        $this->commentContent = $commentContent;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to the clip owner's private notification channel
        return [
            new PrivateChannel('notifications.' . $this->clipOwner->id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'PostCommented';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'post_commented',
            'title' => '💬 New Comment',
            'message' => "{$this->commenter->name} commented on your post",
            'post_id' => $this->clip->id,
            'commenter' => [
                'id' => $this->commenter->id,
                'name' => $this->commenter->name,
                'profile_photo' => $this->commenter->profile_photo,
            ],
            'clip' => [
                'id' => $this->clip->id,
                'title' => $this->clip->title,
                'description' => $this->clip->description,
                'thumbnail_url' => $this->clip->thumbnail_url,
            ],
            'comment_content' => $this->commentContent,
            'timestamp' => now()->toISOString(),
            'action_url' => "/home?focusPost={$this->clip->id}",
            'redirect_to' => 'home_focus',
        ];
    }
}
