<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageReceived extends Notification
{
    use Queueable;

    public function __construct(public Message $message)
    {
        // Ensure relations are available for payload
        $this->message->loadMissing(['sender:id,name,profile_photo', 'receiver:id,name,profile_photo']);
    }

    public function via(object $notifiable): array
    {
        // Database notifications (read by NotificationController)
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'message_received',
            'message_id' => $this->message->id,
            'sender' => [
                'id' => $this->message->sender_id,
                'name' => $this->message->sender->name ?? null,
                'profile_photo' => $this->message->sender->profile_photo ?? null,
            ],
            'receiver_id' => $this->message->receiver_id,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }
}
