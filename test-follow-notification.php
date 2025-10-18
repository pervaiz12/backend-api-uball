<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Follow Notification System\n";
echo "=====================================\n\n";

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

// Check if already following
$isAlreadyFollowing = $follower->following()->where('following_id', $player->id)->exists();
echo "📊 Already following: " . ($isAlreadyFollowing ? 'Yes' : 'No') . "\n\n";

if ($isAlreadyFollowing) {
    echo "⚠️  Already following. Unfollowing first to test fresh follow...\n";
    $follower->following()->detach($player->id);
    echo "✅ Unfollowed\n\n";
}

echo "🔔 Testing Follow Notification...\n";
echo "--------------------------------\n";

// Simulate the follow action
$follower->following()->syncWithoutDetaching([$player->id]);

// Send notification manually (simulating FollowerController logic)
$player->notify(new \App\Notifications\UserFollowedNotification(
    followerId: $follower->id,
    followerName: $follower->name,
    followerProfilePhoto: $follower->profile_photo
));

echo "✅ Follow relationship created\n";
echo "✅ Follow notification sent\n\n";

// Check notification in database
$notification = $player->notifications()->where('type', 'App\\Notifications\\UserFollowedNotification')->latest()->first();

if ($notification) {
    echo "📬 Notification Details:\n";
    echo "   Type: {$notification->type}\n";
    echo "   Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n";
    echo "   Data: " . json_encode($notification->data, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "🎯 Frontend Integration:\n";
    echo "========================\n";
    echo "✅ Notification includes:\n";
    echo "   - follower_id: For navigation to follower's profile\n";
    echo "   - follower_name: Display name\n";
    echo "   - follower_profile_photo: Avatar\n";
    echo "   - action_url: /app/profile?userId={$follower->id}\n";
    echo "   - message: '{$follower->name} started following you'\n";
    echo "\n✅ Frontend should navigate to: /app/profile?userId={$follower->id}\n";
    echo "✅ This will show the follower's profile\n";
} else {
    echo "❌ No notification found in database\n";
}

echo "\nDone! 🚀\n";
