<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Clip;
use App\Events\NewClipUploaded;
use App\Notifications\NewClipNotification;
use Illuminate\Support\Facades\Notification;

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing UBall Notification System\n";
echo "=====================================\n\n";

// Check broadcasting configuration
echo "📡 Broadcasting Configuration:\n";
echo "Driver: " . config('broadcasting.default') . "\n";
echo "Pusher Key: " . config('broadcasting.connections.pusher.key') . "\n";
echo "Pusher Cluster: " . config('broadcasting.connections.pusher.options.cluster') . "\n\n";

// Find test users
$admin = User::where('role', 'admin')->first();
$player = User::where('is_official', true)->first();

if (!$admin) {
    echo "❌ No admin user found. Please create an admin user first.\n";
    exit(1);
}

if (!$player) {
    echo "❌ No official player found. Please run the OfficialPlayersSeeder.\n";
    exit(1);
}

echo "👤 Test Users Found:\n";
echo "Admin: {$admin->name} (ID: {$admin->id})\n";
echo "Player: {$player->name} (ID: {$player->id})\n\n";

// Get player's followers
$followers = $player->followers()->get();
echo "👥 Player Followers: " . $followers->count() . "\n";

if ($followers->count() === 0) {
    echo "⚠️  Player has no followers. Notifications won't be sent.\n";
    echo "💡 Create some followers for the player to test notifications.\n\n";
} else {
    foreach ($followers as $follower) {
        echo "  - {$follower->name} (ID: {$follower->id})\n";
    }
    echo "\n";
}

// Get a test game
$testGame = \App\Models\Game::first();
if (!$testGame) {
    echo "❌ No games found. Please create a game first.\n";
    exit(1);
}

// Create a test clip
echo "🎬 Creating Test Clip...\n";
echo "Using Game: {$testGame->location} (ID: {$testGame->id})\n";
$clip = Clip::create([
    'user_id' => $admin->id,
    'player_id' => $player->id,
    'game_id' => $testGame->id, // Use existing game ID
    'title' => 'Test Notification Clip - ' . now()->format('H:i:s'),
    'description' => 'This is a test clip to verify notifications work',
    'video_url' => '/test-video.mp4',
    'thumbnail_url' => '/test-thumbnail.jpg',
    'tags' => ['TEST', 'NOTIFICATION'],
    'status' => 'approved',
    'visibility' => 'public',
    'views_count' => 0,
    'likes_count' => 0,
    'comments_count' => 0,
]);

echo "✅ Test clip created (ID: {$clip->id})\n\n";

if ($followers->count() > 0) {
    echo "📬 Sending Notifications...\n";
    
    try {
        // Send database notifications
        Notification::send($followers, new NewClipNotification(
            clipId: $clip->id,
            playerId: $player->id,
            playerName: $player->name,
            clipTitle: $clip->title,
            thumbnailUrl: $clip->thumbnail_url
        ));
        
        echo "✅ Database notifications sent\n";
        
        // Broadcast real-time event
        $followerIds = $followers->pluck('id')->toArray();
        broadcast(new NewClipUploaded($clip, $player, $followerIds));
        
        echo "✅ Real-time broadcast event sent\n";
        echo "🎯 Broadcast channels: ";
        foreach ($followerIds as $followerId) {
            echo "notifications.{$followerId} ";
        }
        echo "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error sending notifications: " . $e->getMessage() . "\n\n";
    }
}

// Check database notifications
echo "🗃️  Database Notifications Check:\n";
$dbNotifications = \DB::table('notifications')
    ->where('type', 'App\\Notifications\\NewClipNotification')
    ->latest()
    ->limit(5)
    ->get();

echo "Recent notifications count: " . $dbNotifications->count() . "\n";
foreach ($dbNotifications as $notification) {
    $data = json_decode($notification->data, true);
    echo "  - User {$notification->notifiable_id}: {$data['message']}\n";
}

echo "\n🎉 Test completed!\n";
echo "👀 Check your browser console for real-time notifications.\n";
echo "📱 Check the notifications page in the frontend app.\n";
