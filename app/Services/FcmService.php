<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $serverKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key', env('FCM_SERVER_KEY', ''));
    }

    /**
     * Send push notification to a single device
     */
    public function sendToDevice(string $token, array $notification, array $data = []): bool
    {
        return $this->sendToDevices([$token], $notification, $data);
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToDevices(array $tokens, array $notification, array $data = []): bool
    {
        if (empty($tokens) || empty($this->serverKey)) {
            Log::warning('FCM: Cannot send notification - missing tokens or server key');
            return false;
        }

        try {
            $payload = [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $notification['title'] ?? 'UBall Notification',
                    'body' => $notification['body'] ?? '',
                    'sound' => 'default',
                    'badge' => 1,
                    'icon' => 'ic_notification',
                    'click_action' => $notification['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge([
                    'type' => 'new_clip',
                    'timestamp' => now()->toIso8601String(),
                ], $data),
                'priority' => 'high',
                'content_available' => true,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('FCM: Notification sent successfully', [
                    'success' => $result['success'] ?? 0,
                    'failure' => $result['failure'] ?? 0,
                ]);
                return true;
            } else {
                Log::error('FCM: Failed to send notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM: Exception while sending notification', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, array $notification, array $data = []): bool
    {
        if (empty($this->serverKey)) {
            Log::warning('FCM: Cannot send notification - missing server key');
            return false;
        }

        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $notification['title'] ?? 'UBall Notification',
                    'body' => $notification['body'] ?? '',
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => array_merge([
                    'type' => 'new_clip',
                    'timestamp' => now()->toIso8601String(),
                ], $data),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM: Exception while sending to topic', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
