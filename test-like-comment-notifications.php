<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Like and Comment Notifications\n";
echo "=========================================\n\n";

// Get test users
$user60 = \App\Models\User::find(60); // User who will like/comment  
$admin = \App\Models\User::find(1);   // Admin who owns clips

if (!$user60 || !$admin) {
    echo "❌ Test users not found\n";
    exit(1);
}

echo "Liker/Commenter: {$user60->name} (ID: 60)\n";
echo "Clip Owner: {$admin->name} (ID: 1)\n";

// Find a clip owned by admin
$adminClip = \App\Models\Clip::where('user_id', 1)->first();
if (!$adminClip) {
    echo "❌ No clips found for admin\n";
    exit(1);
}

echo "Testing with clip: {$adminClip->title} (ID: {$adminClip->id})\n\n";

// Check admin's notification count before
$beforeCount = \Illuminate\Support\Facades\DB::table('notifications')
    ->where('notifiable_id', 1)
    ->count();
echo "Admin notifications before: {$beforeCount}\n\n";

// Test 1: Like Notification
echo "👍 Testing Like Notification...\n";
echo "-------------------------------\n";

try {
    // Remove existing like first
    $existingLike = \App\Models\Like::where('user_id', 60)
        ->where('clip_id', $adminClip->id)
        ->first();
    if ($existingLike) {
        $existingLike->delete();
        $adminClip->decrement('likes_count');
        echo "Removed existing like\n";
    }

    // Create like (simulating PostsController toggleLike)
    \App\Models\Like::create([
        'user_id' => 60,
        'clip_id' => $adminClip->id,
    ]);
    $adminClip->increment('likes_count');
    echo "✅ Like created\n";

    // Send notification (exact code from PostsController)
    $liker = \App\Models\User::find(60);
    $clipOwner = \App\Models\User::find(1);

    if ($clipOwner && $liker) {
        $clipOwner->notify(new \App\Notifications\PostLikedNotification(
            postId: $adminClip->id,
            likerId: $liker->id,
            likerName: $liker->name,
            postContent: $adminClip->title ?? $adminClip->description,
            likerProfilePhoto: $liker->profile_photo
        ));
        echo "✅ Like notification sent\n";
    } else {
        echo "❌ Failed to send like notification\n";
    }
} catch (Exception $e) {
    echo "❌ Error in like test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Comment Notification  
echo "💬 Testing Comment Notification...\n";
echo "----------------------------------\n";

try {
    // Create comment (simulating PostsController addComment)
    $comment = \App\Models\Comment::create([
        'user_id' => 60,
        'clip_id' => $adminClip->id,
        'body' => 'Great clip! Amazing basketball skills! 🏀',
    ]);
    $adminClip->increment('comments_count');
    echo "✅ Comment created\n";

    // Send notification (exact code from PostsController)
    $commenter = \App\Models\User::find(60);
    $clipOwner = \App\Models\User::find(1);

    if ($clipOwner && $commenter) {
        $clipOwner->notify(new \App\Notifications\PostCommentedNotification(
            postId: $adminClip->id,
            commenterId: $commenter->id,
            commenterName: $commenter->name,
            commentContent: 'Great clip! Amazing basketball skills! 🏀',
            postContent: $adminClip->title ?? $adminClip->description,
            commenterProfilePhoto: $commenter->profile_photo
        ));
        echo "✅ Comment notification sent\n";
    } else {
        echo "❌ Failed to send comment notification\n";
    }
} catch (Exception $e) {
    echo "❌ Error in comment test: " . $e->getMessage() . "\n";
}

echo "\n";

// Check results
echo "📊 Results:\n";
echo "===========\n";

$afterCount = \Illuminate\Support\Facades\DB::table('notifications')
    ->where('notifiable_id', 1)
    ->count();
echo "Admin notifications after: {$afterCount}\n";
echo "New notifications: " . ($afterCount - $beforeCount) . "\n\n";

// Show recent notifications
$recentNotifications = \Illuminate\Support\Facades\DB::table('notifications')
    ->where('notifiable_id', 1)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Recent notifications for admin:\n";
foreach ($recentNotifications as $notification) {
    $data = json_decode($notification->data, true);
    $type = $data['type'] ?? 'unknown';
    $message = $data['message'] ?? 'No message';
    echo "- {$type}: {$message}\n";
}

echo "\n🎯 Summary:\n";
if ($afterCount > $beforeCount) {
    echo "✅ Notifications are working!\n";
} else {
    echo "❌ Notifications are NOT working!\n";
    echo "💡 Check:\n";
    echo "   1. Notification classes exist\n";
    echo "   2. Database notifications table\n";
    echo "   3. User model has Notifiable trait\n";
}
