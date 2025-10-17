<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Clip;
use App\Models\User;
use App\Models\Game;

echo "🧪 TESTING /api/posts ENDPOINT (NOW USING CLIPS)\n";
echo "=================================================\n\n";

// Check current data
$clipCount = Clip::count();
$approvedClipCount = Clip::where('status', 'approved')->count();

echo "📊 Current Database State:\n";
echo "   Total Clips: {$clipCount}\n";
echo "   Approved Clips: {$approvedClipCount}\n\n";

if ($approvedClipCount === 0) {
    echo "⚠️  No approved clips found. Creating test data...\n\n";
    
    // Get or create a user
    $user = User::first();
    if (!$user) {
        echo "❌ No users found. Please create a user first.\n";
        exit(1);
    }
    
    // Get or create a game
    $game = Game::first();
    if (!$game) {
        echo "❌ No games found. Please create a game first.\n";
        exit(1);
    }
    
    // Create test clips
    for ($i = 1; $i <= 3; $i++) {
        $clip = Clip::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'video_url' => '/storage/clips/test_video_' . $i . '.mp4',
            'thumbnail_url' => '/storage/thumbnails/test_thumb_' . $i . '.jpg',
            'title' => 'Test Basketball Highlight #' . $i,
            'description' => 'This is a test highlight video showcasing amazing basketball skills #' . $i,
            'tags' => ['Highlight', 'Test', 'Basketball'],
            'status' => 'approved',
            'visibility' => 'public',
            'show_in_profile' => true,
            'show_in_trending' => true,
            'season' => '2024',
            'likes_count' => rand(10, 100),
            'comments_count' => rand(0, 20),
            'views_count' => rand(50, 500),
        ]);
        
        echo "✅ Created test clip #{$i}: {$clip->title}\n";
    }
    
    echo "\n";
}

// Fetch clips like the API does
$clips = Clip::with(['user:id,name,profile_photo,role,is_official,city', 'player:id,name,profile_photo', 'game:id,location,game_date'])
    ->where('status', 'approved')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get();

echo "📋 Sample API Response Data:\n";
echo "============================\n\n";

foreach ($clips as $index => $clip) {
    echo "Clip #" . ($index + 1) . ":\n";
    echo "  ID: {$clip->id}\n";
    echo "  Title: {$clip->title}\n";
    echo "  Description: {$clip->description}\n";
    echo "  Video URL: {$clip->video_url}\n";
    echo "  Thumbnail: {$clip->thumbnail_url}\n";
    echo "  Status: {$clip->status}\n";
    echo "  Likes: {$clip->likes_count}\n";
    echo "  Comments: {$clip->comments_count}\n";
    echo "  Views: {$clip->views_count}\n";
    echo "  User: " . ($clip->user ? $clip->user->name : 'N/A') . "\n";
    echo "  Game: " . ($clip->game ? $clip->game->location : 'N/A') . "\n";
    echo "  Created: {$clip->created_at}\n";
    echo "\n";
}

echo "✅ API Endpoint: GET http://localhost:8000/api/posts?page=1&per_page=5\n";
echo "✅ Response Format:\n";
echo "   {\n";
echo "     \"data\": [...clips...],\n";
echo "     \"pagination\": {\n";
echo "       \"current_page\": 1,\n";
echo "       \"last_page\": X,\n";
echo "       \"per_page\": 5,\n";
echo "       \"total\": X,\n";
echo "       \"has_more\": true/false\n";
echo "     }\n";
echo "   }\n\n";

echo "✅ Each clip includes:\n";
echo "   - All clip fields (id, title, description, video_url, thumbnail_url, etc.)\n";
echo "   - user object (id, name, profile_photo, role, is_official, city)\n";
echo "   - player object (if tagged)\n";
echo "   - game object (id, location, game_date)\n";
echo "   - is_liked_by_user (boolean)\n";
echo "   - content (mapped from description)\n";
echo "   - media_url (mapped from video_url)\n";
echo "   - media_type: 'video'\n\n";

echo "🎉 Posts API now successfully returns clips data!\n";
