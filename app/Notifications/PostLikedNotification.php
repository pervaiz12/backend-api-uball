<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class PostLikedNotification extends Notification
{
    // Synchronous notifications (no queue)

    public function __construct(
        public int $postId,
        public int $likerId,
        public string $likerName,
        public ?string $postContent = null,
        public ?string $likerProfilePhoto = null,
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
            'type' => 'post_liked',
            'post_id' => $this->postId,
            'liker_id' => $this->likerId,
            'liker_name' => $this->likerName,
            'liker_profile_photo' => $this->likerProfilePhoto,
            'post_content' => $this->postContent,
            'message' => "{$this->likerName} liked your post",
            'action_url' => "/app/home?focusPost={$this->postId}",
            'redirect_to' => 'home_focus',
            'clickable' => true,
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
            'title' => '👍 Post Liked!',
            'body' => "{$this->likerName} liked your post",
            'click_action' => 'OPEN_POST',
        ];

        $data = [
            'type' => 'post_liked',
            'post_id' => (string) $this->postId,
            'liker_id' => (string) $this->likerId,
            'liker_name' => $this->likerName,
            'liker_profile_photo' => $this->likerProfilePhoto ?? '',
            'post_content' => $this->postContent ?? '',
            'action_url' => "/app/home?focusPost={$this->postId}",
            'redirect_to' => 'home_focus',
        ];

        return $fcmService->sendToDevice($notifiable->fcm_token, $notification, $data);
    }
}
