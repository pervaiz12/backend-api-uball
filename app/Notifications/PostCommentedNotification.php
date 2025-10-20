<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class PostCommentedNotification extends Notification
{
    // Synchronous notifications (no queue)

    public function __construct(
        public int $postId,
        public int $commenterId,
        public string $commenterName,
        public string $commentContent,
        public ?string $postContent = null,
        public ?string $commenterProfilePhoto = null,
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
            'type' => 'post_commented',
            'post_id' => $this->postId,
            'commenter_id' => $this->commenterId,
            'commenter_name' => $this->commenterName,
            'commenter_profile_photo' => $this->commenterProfilePhoto,
            'comment_content' => $this->commentContent,
            'post_content' => $this->postContent,
            'message' => "{$this->commenterName} commented on your post",
            'action_url' => "/home?focusPost={$this->postId}",
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
            'title' => '💬 New Comment!',
            'body' => "{$this->commenterName} commented: " . substr($this->commentContent, 0, 50) . (strlen($this->commentContent) > 50 ? '...' : ''),
            'click_action' => 'OPEN_POST',
        ];

        $data = [
            'type' => 'post_commented',
            'post_id' => (string) $this->postId,
            'commenter_id' => (string) $this->commenterId,
            'commenter_name' => $this->commenterName,
            'commenter_profile_photo' => $this->commenterProfilePhoto ?? '',
            'comment_content' => $this->commentContent,
            'post_content' => $this->postContent ?? '',
            'action_url' => "/home?focusPost={$this->postId}",
            'redirect_to' => 'home_focus',
        ];

        return $fcmService->sendToDevice($notifiable->fcm_token, $notification, $data);
    }
}
