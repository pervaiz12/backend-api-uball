<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class NewClipNotification extends Notification
{
    // Removed ShouldQueue and Queueable to make notifications synchronous

    public function __construct(
        public int $clipId,
        public int $playerId,
        public string $playerName,
        public ?string $clipTitle = null,
        public ?string $thumbnailUrl = null,
        public ?string $playerProfilePhoto = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Database: Store notification history
        // FCM: Send push notification to user's device (disabled for now)
        return ['database']; // Temporarily disable FCM to fix notification issues
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_clip',
            'clip_id' => $this->clipId,
            'player_id' => $this->playerId,
            'player_name' => $this->playerName,
            'player_profile_photo' => $this->playerProfilePhoto,
            'clip_title' => $this->clipTitle,
            'thumbnail_url' => $this->thumbnailUrl,
            'message' => "{$this->playerName} has a new clip!",
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
            'title' => '🏀 New Clip Alert!',
            'body' => "{$this->playerName} just posted a new highlight clip!",
            'click_action' => 'OPEN_CLIP',
        ];

        $data = [
            'type' => 'new_clip',
            'clip_id' => (string) $this->clipId,
            'player_id' => (string) $this->playerId,
            'player_name' => $this->playerName,
            'clip_title' => $this->clipTitle ?? 'New Clip',
            'thumbnail_url' => $this->thumbnailUrl ?? '',
        ];

        return $fcmService->sendToDevice($notifiable->fcm_token, $notification, $data);
    }
}
