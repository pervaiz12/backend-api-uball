<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class ClipUploadedForPlayerNotification extends Notification
{
    // Synchronous notifications (no queue)

    public function __construct(
        public int $clipId,
        public int $uploaderId,
        public string $uploaderName,
        public ?string $clipTitle = null,
        public ?string $thumbnailUrl = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Database: Store notification history
        // FCM: Send push notification to user's device (disabled for now)
        return ['database']; // Temporarily disable FCM to focus on database notifications
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'clip_uploaded_for_you',
            'clip_id' => $this->clipId,
            'uploader_id' => $this->uploaderId,
            'uploader_name' => $this->uploaderName,
            'clip_title' => $this->clipTitle,
            'thumbnail_url' => $this->thumbnailUrl,
            'message' => "{$this->uploaderName} uploaded a new clip of you!",
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

        $notification = [
            'title' => '🎬 New Clip of You!',
            'body' => "{$this->uploaderName} uploaded a new clip featuring you!",
            'click_action' => 'OPEN_CLIP',
        ];

        $data = [
            'type' => 'clip_uploaded_for_you',
            'clip_id' => (string) $this->clipId,
            'uploader_id' => (string) $this->uploaderId,
            'uploader_name' => $this->uploaderName,
            'clip_title' => $this->clipTitle ?? 'New Clip',
            'thumbnail_url' => $this->thumbnailUrl ?? '',
        ];

        return $fcmService->sendToDevice($notifiable->fcm_token, $notification, $data);
    }
}
