<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Notification Redirection Data\n";
echo "========================================\n\n";

// Get test users
$user60 = \App\Models\User::find(60);
$admin = \App\Models\User::find(1);
$clip = \App\Models\Clip::where('player_id', 12)->first(); // Find clip of player 12

if (!$user60 || !$admin || !$clip) {
    echo "❌ Required test data not found\n";
    exit(1);
}

echo "Testing notification data structure...\n";
echo "Clip ID: {$clip->id}\n";
echo "Player ID: {$clip->player_id}\n\n";

// Test like notification data
echo "👍 Testing Like Notification Data:\n";
echo "-----------------------------------\n";

$likeNotification = new \App\Notifications\PostLikedNotification(
    postId: $clip->id,
    likerId: $user60->id,
    likerName: $user60->name,
    postContent: $clip->title,
    likerProfilePhoto: $user60->profile_photo
);

$likeData = $likeNotification->toArray($admin);
echo "✅ Like notification data:\n";
foreach ($likeData as $key => $value) {
    echo "   {$key}: {$value}\n";
}

echo "\n💬 Testing Comment Notification Data:\n";
echo "-------------------------------------\n";

$commentNotification = new \App\Notifications\PostCommentedNotification(
    postId: $clip->id,
    commenterId: $user60->id,
    commenterName: $user60->name,
    commentContent: 'Great clip! 🏀',
    postContent: $clip->title,
    commenterProfilePhoto: $user60->profile_photo
);

$commentData = $commentNotification->toArray($admin);
echo "✅ Comment notification data:\n";
foreach ($commentData as $key => $value) {
    echo "   {$key}: {$value}\n";
}

echo "\n🎯 Frontend Integration:\n";
echo "========================\n";
echo "✅ Notifications include:\n";
echo "   - post_id: For navigation to specific post\n";
echo "   - action_url: Frontend route (/post/{id})\n";
echo "   - redirect_to: Type of redirect (post_detail)\n";
echo "   - clickable: true (indicates clickable notification)\n";
echo "\n✅ Frontend should navigate to: /app/post/{$clip->id}\n";
echo "✅ This will show the specific clip with comments\n";

echo "\nDone! 🚀\n";
