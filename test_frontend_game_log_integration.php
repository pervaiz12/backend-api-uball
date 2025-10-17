<?php

// Test frontend Game Log integration
// php test_frontend_game_log_integration.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;
use App\Models\Clip;
use App\Models\PlayerStat;
use App\Models\User;

echo "🎮 FRONTEND GAME LOG INTEGRATION TEST\n";
echo "====================================\n\n";

$playerId = 12;
$player = User::find($playerId);

if (!$player) {
    echo "❌ Player {$playerId} not found\n";
    exit(1);
}

echo "👤 Testing for: {$player->name} (ID: {$playerId})\n\n";

echo "🔍 COMPREHENSIVE DATA ANALYSIS:\n";
echo "===============================\n";

// 1. Check clips uploaded by admin for this player
$adminUploadedClips = Clip::where('player_id', $playerId)
                          ->where('status', 'approved')
                          ->with(['user', 'game'])
                          ->get();

echo "🎬 Admin uploaded clips for player: " . $adminUploadedClips->count() . "\n";

if ($adminUploadedClips->count() > 0) {
    echo "\n📋 RECENT ADMIN CLIPS:\n";
    echo "=====================\n";
    
    foreach ($adminUploadedClips->take(5) as $clip) {
        echo "🎥 Clip #{$clip->id}: {$clip->title}\n";
        echo "   👤 Uploaded by: " . ($clip->user ? $clip->user->name : 'Unknown') . "\n";
        echo "   🎮 Game: " . ($clip->game ? $clip->game->location : 'No game') . " (ID: {$clip->game_id})\n";
        echo "   📅 Date: {$clip->created_at}\n";
        echo "   ✅ Status: {$clip->status}\n\n";
    }
}

// 2. Check games that should appear in Game Log
echo "🎯 GAMES THAT SHOULD APPEAR IN GAME LOG:\n";
echo "=======================================\n";

$gamesWithClips = Game::whereHas('clips', function ($q) use ($playerId) {
    $q->where('player_id', $playerId)->where('status', 'approved');
})->with(['clips' => function ($q) use ($playerId) {
    $q->where('player_id', $playerId)->where('status', 'approved');
}, 'playerStats' => function ($q) use ($playerId) {
    $q->where('user_id', $playerId);
}])->orderByDesc('game_date')->get();

echo "🏀 Games with player clips: " . $gamesWithClips->count() . "\n";

if ($gamesWithClips->count() > 0) {
    echo "\n🎮 FIRST 3 GAMES WITH CLIPS:\n";
    echo "===========================\n";
    
    foreach ($gamesWithClips->take(3) as $game) {
        $playerStat = $game->playerStats->where('user_id', $playerId)->first();
        
        echo "🏀 Game #{$game->id}: {$game->location}\n";
        echo "   📅 Date: {$game->game_date}\n";
        echo "   🎬 Player clips: " . $game->clips->count() . "\n";
        
        if ($playerStat) {
            echo "   📊 Stats: {$playerStat->points}PTS {$playerStat->rebounds}REB {$playerStat->assists}AST\n";
        } else {
            echo "   📊 Stats: No stats recorded\n";
        }
        echo "\n";
    }
}

// 3. Test the exact API call that frontend makes
echo "🌐 TESTING FRONTEND API CALL:\n";
echo "=============================\n";

// Simulate the exact HTTP request
$apiUrl = "http://127.0.0.1:8000/api/players/{$playerId}/games";
echo "📡 API URL: {$apiUrl}\n";

// Test with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "✅ API Response: " . count($data['data']) . " games returned\n";
        echo "📋 Response format: Laravel Resource Collection\n\n";
        
        if (count($data['data']) > 0) {
            echo "🎮 SAMPLE API RESPONSE:\n";
            echo "======================\n";
            $firstGame = $data['data'][0];
            echo "Game ID: {$firstGame['id']}\n";
            echo "Location: {$firstGame['location']}\n";
            echo "Date: {$firstGame['game_date']}\n";
            echo "Result: " . ($firstGame['result'] ?? 'No result') . "\n";
            echo "Team Score: " . ($firstGame['team_score'] ?? 'N/A') . "\n";
            echo "Opponent Score: " . ($firstGame['opponent_score'] ?? 'N/A') . "\n\n";
        }
        
    } else if (is_array($data)) {
        echo "✅ API Response: " . count($data) . " games returned\n";
        echo "📋 Response format: Direct array\n\n";
    } else {
        echo "❌ Unexpected response format\n";
        echo "Response: " . substr($response, 0, 200) . "...\n\n";
    }
} else {
    echo "❌ API Error: HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
}

// 4. Check frontend expectations
echo "📱 FRONTEND EXPECTATIONS:\n";
echo "========================\n";
echo "Frontend expects:\n";
echo "✅ HTTP 200 status\n";
echo "✅ JSON response with game data\n";
echo "✅ Each game should have: id, location, game_date, result, team_score, opponent_score\n";
echo "✅ Optional: player_stats, clips arrays\n\n";

echo "🎯 TROUBLESHOOTING CHECKLIST:\n";
echo "=============================\n";
echo "1. ✅ Backend API working: " . ($httpCode === 200 ? "YES" : "NO") . "\n";
echo "2. ✅ Games exist for player: " . ($gamesWithClips->count() > 0 ? "YES" : "NO") . "\n";
echo "3. ✅ Clips are approved: " . ($adminUploadedClips->count() > 0 ? "YES" : "NO") . "\n";
echo "4. ✅ Player ID is correct: YES (using {$playerId})\n\n";

if ($httpCode === 200 && $gamesWithClips->count() > 0) {
    echo "✅ BACKEND IS WORKING CORRECTLY!\n";
    echo "================================\n";
    echo "The issue is likely in the frontend:\n";
    echo "1. Check browser console for errors\n";
    echo "2. Verify API base URL in frontend\n";
    echo "3. Check authentication headers\n";
    echo "4. Ensure CORS is properly configured\n";
    echo "5. Test frontend API call manually\n\n";
    
    echo "🔧 FRONTEND DEBUGGING:\n";
    echo "======================\n";
    echo "1. Open browser dev tools\n";
    echo "2. Go to Network tab\n";
    echo "3. Load profile page: http://localhost:5173/app/profile?userId={$playerId}\n";
    echo "4. Look for API call to: /api/players/{$playerId}/games\n";
    echo "5. Check if request succeeds and returns data\n";
    
} else {
    echo "❌ BACKEND ISSUES FOUND\n";
    echo "=======================\n";
    if ($httpCode !== 200) {
        echo "- API endpoint not responding correctly\n";
    }
    if ($gamesWithClips->count() === 0) {
        echo "- No games found with player clips\n";
        echo "- Upload more clips with player selected\n";
    }
}

echo "\n🚀 NEXT STEPS:\n";
echo "==============\n";
echo "1. Upload clips via dashboard with player {$playerId} selected\n";
echo "2. Approve clips in dashboard\n";
echo "3. Check Game Log in profile: http://localhost:5173/app/profile?userId={$playerId}\n";
echo "4. Games should now appear in the Game Log section\n";
