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

class PostLiked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $clip;
    public $liker;
    public $clipOwner;

    /**
     * Create a new event instance.
     */
    public function __construct(Clip $clip, User $liker, User $clipOwner)
    {
        $this->clip = $clip;
        $this->liker = $liker;
        $this->clipOwner = $clipOwner;
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
        return 'PostLiked';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'post_liked',
            'title' => '👍 Post Liked!',
            'message' => "{$this->liker->name} liked your post",
            'post_id' => $this->clip->id,
            'liker' => [
                'id' => $this->liker->id,
                'name' => $this->liker->name,
                'profile_photo' => $this->liker->profile_photo,
            ],
            'clip' => [
                'id' => $this->clip->id,
                'title' => $this->clip->title,
                'description' => $this->clip->description,
                'thumbnail_url' => $this->clip->thumbnail_url,
            ],
            'timestamp' => now()->toISOString(),
            'action_url' => "/home?focusPost={$this->clip->id}",
            'redirect_to' => 'home_focus',
        ];
    }
}
