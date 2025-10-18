<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Services\FcmService;

class UserFollowedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $followerId,
        public string $followerName,
        public ?string $followerProfilePhoto = null
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_followed',
            'follower_id' => $this->followerId,
            'follower_name' => $this->followerName,
            'follower_profile_photo' => $this->followerProfilePhoto,
            'message' => "{$this->followerName} started following you",
            'action_url' => "/app/profile?userId={$this->followerId}",
            'redirect_to' => 'user_profile',
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
            'title' => '👥 New Follower!',
            'body' => "{$this->followerName} started following you",
            'click_action' => 'OPEN_PROFILE',
        ];

        $data = [
            'type' => 'user_followed',
            'follower_id' => (string) $this->followerId,
            'follower_name' => $this->followerName,
            'follower_profile_photo' => $this->followerProfilePhoto ?? '',
            'action_url' => "/app/profile?userId={$this->followerId}",
            'redirect_to' => 'user_profile',
        ];

        return $fcmService->sendToDevice($notifiable->fcm_token, $notification, $data);
    }
}
