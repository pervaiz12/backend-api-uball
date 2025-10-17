<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Clip;
use App\Models\User;

$clip = Clip::with(['user', 'player'])->first();

if ($clip) {
    echo "Clip ID: {$clip->id}\n";
    echo "User ID: {$clip->user_id}\n";
    echo "User Name: " . ($clip->user ? $clip->user->name : 'null') . "\n";
    echo "Player ID: " . ($clip->player_id ?: 'null') . "\n";
    echo "Player Name: " . ($clip->player ? $clip->player->name : 'null') . "\n";
    echo "Thumbnail: {$clip->thumbnail_url}\n";
    echo "Video URL: {$clip->video_url}\n\n";
    
    // List all users
    echo "Available Users:\n";
    $users = User::all();
    foreach ($users as $user) {
        echo "  ID: {$user->id}, Name: {$user->name}, Role: {$user->role}\n";
    }
}
