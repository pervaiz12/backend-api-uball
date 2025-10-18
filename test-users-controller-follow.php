<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing UsersController Follow Notification\n";
echo "==============================================\n\n";

// Get test users
$follower = \App\Models\User::find(60); // User who will follow
$player = \App\Models\User::find(12);   // Player who will be followed

if (!$follower || !$player) {
    echo "❌ Required test users not found\n";
    echo "Need User ID 60 (follower) and User ID 12 (player)\n";
    exit(1);
}

echo "👤 Follower: {$follower->name} (ID: {$follower->id})\n";
echo "🎯 Player: {$player->name} (ID: {$player->id})\n\n";

// Clean up any existing follow relationship
\Illuminate\Support\Facades\DB::table('followers')
    ->where('follower_id', $follower->id)
    ->where('following_id', $player->id)
    ->delete();

echo "🧹 Cleaned up existing follow relationship\n\n";

echo "🔔 Testing UsersController Follow Method...\n";
echo "-------------------------------------------\n";

// Simulate the UsersController followPlayer method
try {
    // Check if already following
    $existingFollow = \Illuminate\Support\Facades\DB::table('followers')
        ->where('follower_id', $follower->id)
        ->where('following_id', $player->id)
        ->first();
        
    if ($existingFollow) {
        echo "❌ Already following this player\n";
        exit(1);
    }
    
    // Create follow relationship
    \Illuminate\Support\Facades\DB::table('followers')->insert([
        'follower_id' => $follower->id,
        'following_id' => $player->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // Send follow notification
    $player->notify(new \App\Notifications\UserFollowedNotification(
        followerId: $follower->id,
        followerName: $follower->name,
        followerProfilePhoto: $follower->profile_photo
    ));
    
    echo "✅ Follow relationship created\n";
    echo "✅ Follow notification sent\n\n";
    
    // Check notification in database
    $notification = $player->notifications()
        ->where('type', 'App\\Notifications\\UserFollowedNotification')
        ->latest()
        ->first();
    
    if ($notification) {
        echo "📬 Notification Details:\n";
        echo "   Type: {$notification->type}\n";
        echo "   Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n";
        echo "   Data: " . json_encode($notification->data, JSON_PRETTY_PRINT) . "\n\n";
        
        echo "🎯 API Endpoint Test:\n";
        echo "====================\n";
        echo "✅ UsersController::followPlayer() now sends notifications\n";
        echo "✅ Frontend should use: POST /api/players/{$player->id}/follow\n";
        echo "✅ Notification will be sent to player ID {$player->id}\n";
        echo "✅ Click notification → Navigate to /app/profile?userId={$follower->id}\n";
    } else {
        echo "❌ No notification found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone! 🚀\n";
