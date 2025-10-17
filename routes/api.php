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

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Auth
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']); // Admin/Staff login
Route::post('player-login', [AuthController::class, 'playerLogin']); // Player login
Route::post('auth/google', [AuthController::class, 'googleAuth']); // Google OAuth
Route::post('auth/facebook', [AuthController::class, 'facebookAuth']); // Facebook OAuth
Route::post('auth/apple', [AuthController::class, 'appleAuth']); // Apple OAuth
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
// Public player games (for profile viewing)
Route::get('players/{playerId}/games', [GameController::class, 'playerGames']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('games', [GameController::class, 'store']);
    Route::put('games/{game}', [GameController::class, 'update']);
    Route::delete('games/{game}', [GameController::class, 'destroy']);
});

// Clips
use App\Http\Controllers\ClipController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('clips', [ClipController::class, 'index']);
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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me/notifications', [NotificationController::class, 'index']);
    Route::get('me/notifications/unread', [NotificationController::class, 'unread']);
    Route::patch('me/notifications/{id}', [NotificationController::class, 'markRead']);
    Route::post('me/notifications/read-all', [NotificationController::class, 'markAllRead']);
});

// Posts API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('posts', [PostsController::class, 'index']);
    Route::get('posts/{post}', [PostsController::class, 'show']);
    Route::post('posts', [PostsController::class, 'store']);
    Route::post('posts/{post}/like', [PostsController::class, 'toggleLike']);
    
    // Comments
    Route::get('posts/{post}/comments', [PostsController::class, 'getComments']);
    Route::post('posts/{post}/comments', [PostsController::class, 'addComment']);
    Route::put('comments/{comment}', [PostsController::class, 'updateComment']);
    Route::delete('comments/{comment}', [PostsController::class, 'deleteComment']);
});


Route::middleware('auth:sanctum')->group(function () {
});

// Public Players directory (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('players', [UsersController::class, 'publicPlayers']); // Use publicPlayers instead of index
    // Top official players
    Route::get('/players/top-officials', [UsersController::class, 'topOfficialPlayers']);
    
    // Suggested players and following
    Route::get('/players/suggested', [UsersController::class, 'suggestedPlayers']);
    Route::post('/players/{playerId}/follow', [UsersController::class, 'followPlayer']);
    Route::delete('/players/{playerId}/follow', [UsersController::class, 'unfollowPlayer']);
    Route::get('players/{user}', [UsersController::class, 'showPublic']);
    Route::post('players', [UsersController::class, 'storePlayer']); // This requires 'is-staff'
    Route::put('players/{user}', [UsersController::class, 'updatePlayer']);
    Route::delete('players/{user}', [UsersController::class, 'destroyPlayer']);
});
