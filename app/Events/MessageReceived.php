<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use App\Models\User;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        // Ensure relations are available for payload
        $this->message->loadMissing(['sender:id,name,profile_photo', 'receiver:id,name,profile_photo']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to the receiver's private notification channel
        return [
            new PrivateChannel('notifications.' . $this->message->receiver_id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageReceived';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $path = $this->message->attachment_path;
        $attachmentUrl = null;
        if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $attachmentUrl = $path;
            } elseif (str_starts_with($path, 'storage/')) {
                $attachmentUrl = url($path);
            } else {
                $attachmentUrl = url(\Illuminate\Support\Facades\Storage::url($path));
            }
        }

        return [
            'type' => 'message_received',
            'title' => '💬 New Message',
            'message' => $this->message->sender->name . ': ' . ($this->message->body ?: '📎 Attachment'),
            'message_id' => $this->message->id,
            'sender' => [
                'id' => $this->message->sender_id,
                'name' => $this->message->sender->name ?? null,
                'profile_photo' => $this->message->sender->profile_photo ?? null,
            ],
            'receiver_id' => $this->message->receiver_id,
            'body' => $this->message->body,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $this->message->attachment_type,
            'attachment_name' => $this->message->attachment_name,
            'attachment_size' => $this->message->attachment_size,
            'created_at' => $this->message->created_at?->toISOString(),
            'timestamp' => now()->toISOString(),
            'action_url' => "/app/messages?userId={$this->message->sender_id}",
            'redirect_to' => 'messages',
        ];
    }
}
