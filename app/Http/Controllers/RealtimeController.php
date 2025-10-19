<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Models\Message;
use App\Models\User;

class RealtimeController extends Controller
{
    /**
     * Get real-time updates for a user (polling endpoint)
     */
    public function getUpdates(Request $request): JsonResponse
    {
        $user = auth()->user();
        $lastCheck = $request->query('last_check', now()->subMinutes(5)->toISOString());
        
        $updates = [];
        
        // Get new messages since last check
        $newMessages = Message::with(['sender:id,name,profile_photo'])
            ->where('receiver_id', $user->id)
            ->where('created_at', '>', $lastCheck)
            ->orderBy('created_at', 'desc')
            ->get();
            
        foreach ($newMessages as $message) {
            // Create database notification for the message if it doesn't exist
            $existingNotification = $user->notifications()
                ->where('type', 'App\\Notifications\\MessageReceived')
                ->whereJsonContains('data->message_id', $message->id)
                ->first();
                
            if (!$existingNotification) {
                try {
                    \Log::info('Creating fallback message notification', [
                        'message_id' => $message->id,
                        'receiver_id' => $user->id
                    ]);
                    $user->notify(new \App\Notifications\MessageReceived($message));
                } catch (\Exception $e) {
                    \Log::error('Failed to create fallback message notification', ['error' => $e->getMessage()]);
                }
            }
            
            $updates[] = [
                'type' => 'message_received',
                'title' => '💬 New Message',
                'message' => $message->sender->name . ': ' . ($message->body ?: '📎 Attachment'),
                'data' => [
                    'message_id' => $message->id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'profile_photo' => $message->sender->profile_photo,
                    ],
                    'receiver_id' => $message->receiver_id,
                    'body' => $message->body,
                    'created_at' => $message->created_at->toISOString(),
                ],
                'timestamp' => $message->created_at->toISOString(),
            ];
        }
        
        return response()->json([
            'updates' => $updates,
            'last_check' => now()->toISOString(),
            'count' => count($updates),
        ]);
    }
    
    /**
     * Send a test notification (local fallback)
     */
    public function testNotification(): JsonResponse
    {
        $user = auth()->user();
        
        // Store test notification in cache for polling
        $cacheKey = "test_notification_{$user->id}";
        $testNotification = [
            'type' => 'test_event',
            'title' => '🧪 Test Notification (Local)',
            'message' => 'Local polling test - Pusher fallback working!',
            'timestamp' => now()->toISOString(),
        ];
        
        Cache::put($cacheKey, $testNotification, 60); // Store for 1 minute
        
        return response()->json([
            'message' => 'Test notification created (local fallback)',
            'user_id' => $user->id,
            'notification' => $testNotification,
        ]);
    }
    
    /**
     * Get test notifications from cache
     */
    public function getTestNotifications(): JsonResponse
    {
        $user = auth()->user();
        $cacheKey = "test_notification_{$user->id}";
        
        $testNotification = Cache::get($cacheKey);
        
        if ($testNotification) {
            // Clear the notification after retrieving
            Cache::forget($cacheKey);
            
            return response()->json([
                'notifications' => [$testNotification],
                'count' => 1,
            ]);
        }
        
        return response()->json([
            'notifications' => [],
            'count' => 0,
        ]);
    }
}
