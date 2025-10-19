<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

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
        // Database notifications only - real-time notifications handled by Pusher events
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

    /**
     * Send FCM push notification
     */
    public function toFcm(object $notifiable): bool
    {
        // Check if user has FCM token
        if (empty($notifiable->fcm_token)) {
            Log::info('FCM: User has no token', ['user_id' => $notifiable->id]);
            return false;
        }

        $fcmService = app(FcmService::class);

        $senderName = $this->message->sender->name ?? 'Someone';
        $messageBody = $this->message->body;
        
        // Truncate long messages for notification
        $previewText = $messageBody;
        if (strlen($messageBody) > 50) {
            $previewText = substr($messageBody, 0, 47) . '...';
        }
        
        // If there's an attachment but no text
        if (empty($messageBody) && $this->message->attachment_name) {
            $previewText = '📎 ' . $this->message->attachment_name;
        }

        $notification = [
            'title' => '💬 New Message',
            'body' => "{$senderName}: {$previewText}",
            'click_action' => 'OPEN_MESSAGE',
        ];

        $data = [
            'type' => 'message_received',
            'message_id' => (string) $this->message->id,
            'sender_id' => (string) $this->message->sender_id,
            'sender_name' => $senderName,
            'sender_photo' => $this->message->sender->profile_photo ?? '',
            'body' => $messageBody,
            'has_attachment' => !empty($this->message->attachment_name) ? 'true' : 'false',
            'attachment_type' => $this->message->attachment_type ?? '',
        ];

        return $fcmService->sendToDevice($notifiable->fcm_token, $notification, $data);
    }
}
