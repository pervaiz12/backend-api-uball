<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Simple Notification Test\n";
echo "========================\n";

// Check if admin user exists
$admin = \App\Models\User::find(1);
if (!$admin) {
    echo "❌ Admin user not found\n";
    exit(1);
}

echo "✅ Admin user found: {$admin->name}\n";

// Check notifications table
$notificationsBefore = \Illuminate\Support\Facades\DB::table('notifications')
    ->where('notifiable_id', 1)
    ->count();
echo "✅ Notifications before: {$notificationsBefore}\n";

// Try to create a simple notification manually
try {
    $notificationId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('notifications')->insert([
        'id' => $notificationId,
        'type' => 'App\\Notifications\\PostLikedNotification',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => 1,
        'data' => json_encode([
            'type' => 'post_liked',
            'post_id' => 123,
            'liker_id' => 60,
            'liker_name' => 'Test User',
            'message' => 'Test User liked your post',
        ]),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✅ Manual notification inserted\n";
} catch (Exception $e) {
    echo "❌ Failed to insert notification: {$e->getMessage()}\n";
}

// Check notifications after
$notificationsAfter = \Illuminate\Support\Facades\DB::table('notifications')
    ->where('notifiable_id', 1)
    ->count();
echo "✅ Notifications after: {$notificationsAfter}\n";

if ($notificationsAfter > $notificationsBefore) {
    echo "🎉 SUCCESS: Notification system is working!\n";
    echo "The issue might be in the PostsController logic.\n";
} else {
    echo "❌ FAILED: Database notification insertion failed\n";
}

echo "\nDone.\n";
