<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Clip;
use App\Models\User;

class NewClipUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $clip;
    public $player;
    public $followerIds;

    /**
     * Create a new event instance.
     */
    public function __construct(Clip $clip, User $player, array $followerIds)
    {
        $this->clip = $clip;
        $this->player = $player;
        $this->followerIds = $followerIds;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Broadcast to each follower's private notification channel
        foreach ($this->followerIds as $followerId) {
            $channels[] = new PrivateChannel('notifications.' . $followerId);
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'NewClipUploaded';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'new_clip_uploaded',
            'message' => "New clip uploaded for {$this->player->name}!",
            'clip' => [
                'id' => $this->clip->id,
                'title' => $this->clip->title,
                'description' => $this->clip->description,
                'thumbnail_url' => $this->clip->thumbnail_url,
                'video_url' => $this->clip->video_url,
                'tags' => $this->clip->tags,
                'created_at' => $this->clip->created_at->toISOString(),
            ],
            'player' => [
                'id' => $this->player->id,
                'name' => $this->player->name,
                'profile_photo' => $this->player->profile_photo,
                'is_official' => $this->player->is_official,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
