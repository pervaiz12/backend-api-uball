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
        ];
    }
}
