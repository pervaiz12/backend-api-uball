<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Debug Clip Ownership\n";
echo "===================\n";

// Check some clips and their owners
$clips = \App\Models\Clip::with('user')->limit(5)->get();

echo "Recent clips and their owners:\n";
foreach ($clips as $clip) {
    echo "- Clip ID: {$clip->id}\n";
    echo "  Title: {$clip->title}\n";
    echo "  Owner: {$clip->user->name} (ID: {$clip->user_id})\n";
    echo "  Player: " . ($clip->player_id ? "ID {$clip->player_id}" : "None") . "\n";
    echo "\n";
}

// Check if user 60 exists
$user60 = \App\Models\User::find(60);
if ($user60) {
    echo "✅ User 60 exists: {$user60->name}\n";
} else {
    echo "❌ User 60 not found\n";
}

// Check if admin exists
$admin = \App\Models\User::find(1);
if ($admin) {
    echo "✅ Admin exists: {$admin->name}\n";
} else {
    echo "❌ Admin not found\n";
}

echo "\nDone.\n";
