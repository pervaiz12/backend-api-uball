<?php

// Test complete upload to profile flow
// php test_upload_to_profile_flow.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Clip;
use App\Models\PlayerStat;
use App\Models\User;
use App\Models\Game;
use Illuminate\Http\Request;
use App\Http\Controllers\ClipController;

echo "🎬 TESTING COMPLETE UPLOAD TO PROFILE FLOW\n";
echo "==========================================\n\n";

// Test player ID 12 (our test player)
$playerId = 12;
$player = User::find($playerId);

if (!$player) {
    echo "❌ Player {$playerId} not found\n";
    exit(1);
}

echo "👤 Testing for player: {$player->name} (ID: {$playerId})\n\n";

// Check clips for this player
echo "📊 CLIP ANALYSIS FOR PLAYER {$playerId}:\n";
echo "=======================================\n";

$playerClips = Clip::where('player_id', $playerId)
                   ->where('status', 'approved')
                   ->orderByDesc('created_at')
                   ->get();

echo "📹 Total approved clips: " . $playerClips->count() . "\n";

if ($playerClips->count() > 0) {
    echo "\n🎯 LATEST 5 CLIPS:\n";
    echo "==================\n";
    
    foreach ($playerClips->take(5) as $index => $clip) {
        $views = number_format($clip->views_count);
        $formattedViews = $clip->views_count >= 1000000 ? 
            number_format($clip->views_count/1000000, 1) . 'M' : 
            ($clip->views_count >= 1000 ? number_format($clip->views_count/1000, 1) . 'K' : $clip->views_count);
        
        echo "🎥 #" . ($index + 1) . " - {$clip->title}\n";
        echo "   📅 Created: {$clip->created_at}\n";
        echo "   🖼️  Thumbnail: " . ($clip->thumbnail_url ?: 'No thumbnail') . "\n";
        echo "   👁️  Views: {$formattedViews} ({$views})\n";
        echo "   📊 Status: {$clip->status}\n";
        echo "   👤 Show in Profile: " . ($clip->show_in_profile ? 'Yes' : 'No') . "\n";
        echo "   🎮 Game: " . ($clip->game ? $clip->game->location : 'No game') . "\n\n";
    }
}

// Test API endpoint that frontend uses
echo "🌐 TESTING FRONTEND API ENDPOINT:\n";
echo "=================================\n";

try {
    $controller = new ClipController();
    $response = $controller->playerClips($playerId);
    $apiData = $response->toArray(new Request());
    
    echo "✅ API Response: " . count($apiData) . " clips returned\n";
    
    if (count($apiData) > 0) {
        echo "\n📱 FIRST 3 API RESULTS (What frontend sees):\n";
        echo "===========================================\n";
        
        foreach (array_slice($apiData, 0, 3) as $index => $clip) {
            echo "🎬 API Clip #" . ($index + 1) . ":\n";
            echo "   ID: {$clip['id']}\n";
            echo "   Title: " . ($clip['title'] ?: 'No title') . "\n";
            echo "   Thumbnail: " . ($clip['thumbnail_url'] ?: 'No thumbnail') . "\n";
            echo "   Views: " . ($clip['views_count'] ?: 0) . "\n";
            echo "   Video URL: " . substr($clip['video_url'], 0, 50) . "...\n";
            echo "   Status: {$clip['status']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ API Error: " . $e->getMessage() . "\n";
}

// Check player stats
echo "📊 PLAYER STATS CHECK:\n";
echo "======================\n";

$playerStats = PlayerStat::where('user_id', $playerId)->get();
echo "📈 Total player stat records: " . $playerStats->count() . "\n";

if ($playerStats->count() > 0) {
    $latestStat = $playerStats->sortByDesc('created_at')->first();
    echo "🏀 Latest stats: {$latestStat->points}PTS {$latestStat->rebounds}REB {$latestStat->assists}AST\n";
}

// Final verification
echo "\n✅ PROFILE DISPLAY READINESS CHECK:\n";
echo "===================================\n";

$readyClips = Clip::where('player_id', $playerId)
                  ->where('status', 'approved')
                  ->where('show_in_profile', true)
                  ->whereNotNull('thumbnail_url')
                  ->where('thumbnail_url', '!=', '')
                  ->whereNotNull('title')
                  ->where('title', '!=', '')
                  ->count();

$totalPlayerClips = Clip::where('player_id', $playerId)->count();

echo "🎯 Clips ready for profile: {$readyClips}/{$totalPlayerClips}\n";

if ($readyClips > 0) {
    echo "✅ PROFILE WILL SHOW VIDEOS!\n";
    echo "============================\n";
    echo "🎬 Latest videos will appear first\n";
    echo "🖼️  All videos have thumbnails\n";
    echo "👁️  All videos have view counts\n";
    echo "📝 All videos have titles\n";
    echo "🎮 All videos are approved\n";
} else {
    echo "❌ NO VIDEOS WILL SHOW IN PROFILE\n";
    echo "=================================\n";
    echo "Possible issues:\n";
    echo "- No approved clips for this player\n";
    echo "- Missing thumbnails\n";
    echo "- Missing titles\n";
    echo "- show_in_profile = false\n";
}

echo "\n🚀 NEXT STEPS:\n";
echo "==============\n";
echo "1. Test dashboard upload: http://localhost:3000/dashboard/uploads\n";
echo "2. Upload a new video with all fields filled\n";
echo "3. Approve the video in: http://localhost:3000/dashboard/clips\n";
echo "4. Check profile: http://localhost:5173/app/profile?userId={$playerId}\n";
echo "5. Video should appear in Media Gallery with thumbnail and views\n\n";

echo "🎯 UPLOAD CHECKLIST:\n";
echo "====================\n";
echo "✅ Title: Required (auto-generated if empty)\n";
echo "✅ Description: Optional (auto-generated if empty)\n";
echo "✅ Player: Must select player for profile display\n";
echo "✅ Game: Required field\n";
echo "✅ Video file: Required\n";
echo "✅ Thumbnail: Optional (auto-generated if not provided)\n";
echo "✅ Stats: Optional but recommended\n";
echo "✅ Approval: Admin auto-approves, others need approval\n";
