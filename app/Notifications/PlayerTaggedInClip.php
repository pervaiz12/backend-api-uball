<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PlayerTaggedInClip extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $clipId,
        public int $playerId,
        public string $playerName,
        public ?string $description = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'player_tagged_clip',
            'clip_id' => $this->clipId,
            'player_id' => $this->playerId,
            'player_name' => $this->playerName,
            'description' => $this->description,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastOn(): array
    {
        // Notify per-user channel: private-notifications.{userId}
        return [
            new \Illuminate\Broadcasting\PrivateChannel('notifications.' . $notifiable->id),
        ];
    }
}
