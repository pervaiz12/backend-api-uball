<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ClipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\RealtimeController;

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Test broadcasting
Route::middleware('auth:sanctum')->post('/test-broadcast', function () {
    $user = auth()->user();
    \Log::info('Test broadcast triggered for user: ' . $user->id);
    
    try {
        // Test network connectivity first
        $pusherHost = 'api-ap2.pusherapp.com';
        $dnsResult = gethostbyname($pusherHost);
        \Log::info('DNS resolution test', ['host' => $pusherHost, 'resolved_ip' => $dnsResult]);
        
        if ($dnsResult === $pusherHost) {
            return response()->json(['error' => 'Cannot resolve Pusher host: ' . $pusherHost . '. Check internet connection.'], 500);
        }
        
        broadcast(new \App\Events\TestEvent($user->id, 'Broadcasting test from API'));
        \Log::info('Test broadcast sent successfully');
        return response()->json(['message' => 'Test broadcast sent', 'user_id' => $user->id]);
    } catch (\Exception $e) {
        \Log::error('Test broadcast failed: ' . $e->getMessage());
        return response()->json(['error' => 'Broadcast failed: ' . $e->getMessage()], 500);
    }
});

// Realtime fallback routes (for when Pusher is not available)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/realtime/updates', [RealtimeController::class, 'getUpdates']);
    Route::post('/realtime/test', [RealtimeController::class, 'testNotification']);
    Route::get('/realtime/test-notifications', [RealtimeController::class, 'getTestNotifications']);
});

// Auth
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('player-login', [AuthController::class, 'playerLogin']);
Route::post('auth/google', [AuthController::class, 'googleAuth']);
Route::get('auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('auth/facebook', [AuthController::class, 'facebookAuth']);
Route::post('auth/apple', [AuthController::class, 'appleAuth']);

// Password Reset
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

// Protected example route (current user)
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

// Profile
Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::put('profile/password', [ProfileController::class, 'updatePassword']);
});

// Games
Route::get('games', [GameController::class, 'index']);
Route::get('games/{game}', [GameController::class, 'show']);
Route::get('players/{playerId}/games', [GameController::class, 'playerGames']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('games', [GameController::class, 'store']);
    Route::put('games/{game}', [GameController::class, 'update']);
    Route::delete('games/{game}', [GameController::class, 'destroy']);
});

// Clips
Route::middleware('auth:sanctum')->group(function () {
    Route::get('clips', [ClipController::class, 'index']);
    Route::get('clips/tags', [ClipController::class, 'getTags']);
    Route::post('clips', [ClipController::class, 'upload']);
    Route::patch('clips/{clip}', [ClipController::class, 'update']);
    Route::delete('clips/{clip}', [ClipController::class, 'destroy']);
    Route::get('players/{playerId}/clips', [ClipController::class, 'playerClips']);
    Route::get('players/{playerId}/highlights', [ClipController::class, 'playerHighlights']);
});

// Stats
Route::middleware('auth:sanctum')->group(function () {
    Route::get('games/{game}/stats', [StatController::class, 'index']);
    Route::post('games/{game}/stats', [StatController::class, 'store']);
    Route::get('me/stats/last-10', [StatController::class, 'lastTen']);
    Route::get('me/stats/season', [StatController::class, 'season']);
    Route::get('players/{playerId}/stats', [StatController::class, 'playerStats']);
    Route::delete('stats/{stat}', [StatController::class, 'destroy']);
});

// Followers
Route::middleware('auth:sanctum')->group(function () {
    Route::post('follow/{user}', [FollowerController::class, 'follow']);
    Route::post('unfollow/{user}', [FollowerController::class, 'unfollow']);
    Route::get('followers', [FollowerController::class, 'followers']);
    Route::get('following', [FollowerController::class, 'following']);
});

// Messages
Route::middleware('auth:sanctum')->group(function () {
    Route::get('messages', [MessageController::class, 'index']);
    Route::get('messages/conversation/{userId}', [MessageController::class, 'conversation']);
    Route::post('messages', [MessageController::class, 'send']);
    Route::patch('messages/conversation/{userId}/read', [MessageController::class, 'markConversationRead']);
});

// FCM Token Management
Route::middleware('auth:sanctum')->group(function () {
    Route::post('me/fcm-token', function(Request $request) {
        $request->validate(['fcm_token' => 'required|string|max:255']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'FCM token stored successfully']);
    });
    Route::delete('me/fcm-token', function(Request $request) {
        $request->user()->update(['fcm_token' => null]);
        return response()->json(['message' => 'FCM token removed successfully']);
    });
});

// Admin
Route::middleware('auth:sanctum')->group(function () {
    Route::post('users/{user}/approve', [AdminController::class, 'approve']);
    Route::post('users/{user}/reject', [AdminController::class, 'reject']);
    Route::delete('users/{user}', [AdminController::class, 'destroy']);
    Route::get('audit-logs', [AdminController::class, 'logs']);
    Route::get('users', [UsersController::class, 'index']);
    Route::post('users', [UsersController::class, 'store']);
    Route::put('users/{user}', [UsersController::class, 'update']);
    Route::get('metrics', [MetricsController::class, 'index']);
});

// Feed
Route::middleware('auth:sanctum')->group(function () {
    Route::get('feed', [FeedController::class, 'index']);
    Route::post('clips/{clip}/like', [FeedController::class, 'like']);
    Route::post('clips/{clip}/unlike', [FeedController::class, 'unlike']);
    Route::post('clips/{clip}/comment', [FeedController::class, 'comment']);
    Route::get('clips/{clip}/comments', [FeedController::class, 'comments']);
    Route::delete('comments/{comment}', [FeedController::class, 'destroyComment']);
});

// Notifications
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me/notifications', [NotificationController::class, 'index']);
    Route::get('me/notifications/unread', [NotificationController::class, 'unread']);
    Route::get('me/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('me/notifications/{id}', [NotificationController::class, 'markRead']);
    Route::post('me/notifications/read-all', [NotificationController::class, 'markAllRead']);
    
    // Push notification tokens
    Route::post('me/fcm-token', [NotificationController::class, 'registerFcmToken']);
    Route::delete('me/fcm-token', [NotificationController::class, 'removeFcmToken']);
});

// Recent Activity
Route::middleware('auth:sanctum')->group(function () {
    Route::get('activity', [ActivityController::class, 'index']);
});

// Posts API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('posts', [PostsController::class, 'index']);
    Route::get('posts/{post}', [PostsController::class, 'show']);
    Route::post('posts', [PostsController::class, 'store']);
    Route::post('posts/{post}/like', [PostsController::class, 'toggleLike']);
    Route::post('posts/{post}/view', [PostsController::class, 'incrementView']);
    
    // Comments
    Route::get('posts/{post}/comments', [PostsController::class, 'getComments']);
    Route::post('posts/{post}/comments', [PostsController::class, 'addComment']);
    Route::put('comments/{comment}', [PostsController::class, 'updateComment']);
    Route::delete('comments/{comment}', [PostsController::class, 'deleteComment']);
});

// Public Players directory
Route::middleware('auth:sanctum')->group(function () {
    Route::get('players', [UsersController::class, 'publicPlayers']);
    Route::get('/players/top-officials', [UsersController::class, 'topOfficialPlayers']);
    Route::get('/players/suggested', [UsersController::class, 'suggestedPlayers']);
    Route::get('/players/search', [UsersController::class, 'searchPlayers']);
    Route::post('/players/{playerId}/follow', [UsersController::class, 'followPlayer']);
    Route::delete('/players/{playerId}/follow', [UsersController::class, 'unfollowPlayer']);
    Route::get('players/{user}', [UsersController::class, 'showPublic']);
    Route::post('players', [UsersController::class, 'storePlayer']);
    Route::put('players/{user}', [UsersController::class, 'updatePlayer']);
    Route::delete('players/{user}', [UsersController::class, 'destroyPlayer']);
});
