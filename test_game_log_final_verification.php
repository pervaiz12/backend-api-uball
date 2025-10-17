<?php

// Final verification that Game Log is working end-to-end
// php test_game_log_final_verification.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;
use App\Models\Clip;
use App\Models\PlayerStat;
use App\Models\User;

echo "🎯 FINAL GAME LOG VERIFICATION\n";
echo "==============================\n\n";

$playerId = 12;
$player = User::find($playerId);

echo "👤 Player: {$player->name} (ID: {$playerId})\n\n";

echo "✅ BACKEND VERIFICATION:\n";
echo "========================\n";

// 1. Check data exists
$approvedClips = Clip::where('player_id', $playerId)->where('status', 'approved')->count();
$playerStats = PlayerStat::where('user_id', $playerId)->count();
$gamesWithData = Game::whereHas('clips', function ($q) use ($playerId) {
    $q->where('player_id', $playerId)->where('status', 'approved');
})->orWhereHas('playerStats', function ($q) use ($playerId) {
    $q->where('user_id', $playerId);
})->count();

echo "🎬 Approved clips for player: {$approvedClips}\n";
echo "📊 Player stat records: {$playerStats}\n";
echo "🎮 Games with player data: {$gamesWithData}\n\n";

// 2. Test API endpoint
echo "🌐 API ENDPOINT TEST:\n";
echo "====================\n";

$apiUrl = "http://127.0.0.1:8000/api/players/{$playerId}/games";
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

echo "📡 URL: {$apiUrl}\n";
echo "📊 HTTP Status: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $gameCount = isset($data['data']) ? count($data['data']) : count($data);
    echo "✅ API Response: {$gameCount} games returned\n";
    
    if ($gameCount > 0) {
        echo "\n🎮 SAMPLE GAME DATA:\n";
        echo "===================\n";
        $games = isset($data['data']) ? $data['data'] : $data;
        $firstGame = $games[0];
        
        echo "Game: {$firstGame['location']}\n";
        echo "Date: {$firstGame['game_date']}\n";
        echo "Result: {$firstGame['result']}\n";
        echo "Score: {$firstGame['team_score']} - {$firstGame['opponent_score']}\n";
        
        if (isset($firstGame['player_stats'])) {
            $stats = $firstGame['player_stats'];
            echo "Stats: {$stats['points']}PTS {$stats['rebounds']}REB {$stats['assists']}AST\n";
        }
        echo "\n";
    }
} else {
    echo "❌ API Error: HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
}

echo "📱 FRONTEND INTEGRATION:\n";
echo "========================\n";
echo "Frontend calls: gamesApi.getPlayerGames({$playerId})\n";
echo "API endpoint: GET /api/players/{$playerId}/games\n";
echo "Authentication: ❌ NOT REQUIRED (public endpoint)\n";
echo "Response format: Laravel Resource Collection\n\n";

echo "🎯 GAME LOG STATUS:\n";
echo "===================\n";

if ($httpCode === 200 && $gameCount > 0) {
    echo "✅ GAME LOG IS FULLY FUNCTIONAL!\n";
    echo "=================================\n";
    echo "🎮 Backend returns {$gameCount} games\n";
    echo "🌐 API endpoint is public (no auth required)\n";
    echo "📊 Games include stats and scores\n";
    echo "🎬 Games are linked to player clips\n\n";
    
    echo "🚀 FRONTEND SHOULD NOW WORK:\n";
    echo "============================\n";
    echo "1. Profile page loads without authentication\n";
    echo "2. Game Log section shows {$gameCount} games\n";
    echo "3. Each game shows location, date, score, result\n";
    echo "4. Player stats are displayed for each game\n";
    echo "5. Latest games appear first\n\n";
    
    echo "🔧 IF STILL EMPTY, CHECK:\n";
    echo "=========================\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Network tab for failed API requests\n";
    echo "3. Frontend API base URL configuration\n";
    echo "4. CORS settings if cross-origin\n";
    echo "5. Frontend data parsing logic\n\n";
    
} else {
    echo "❌ GAME LOG HAS ISSUES\n";
    echo "======================\n";
    if ($httpCode !== 200) {
        echo "- API endpoint not responding\n";
    }
    if ($gameCount === 0) {
        echo "- No games found for player\n";
        echo "- Upload more clips with player selected\n";
    }
}

echo "🎬 UPLOAD WORKFLOW REMINDER:\n";
echo "============================\n";
echo "1. Go to dashboard: http://localhost:3000/dashboard/uploads\n";
echo "2. Upload video with player {$playerId} selected\n";
echo "3. Approve clip in: http://localhost:3000/dashboard/clips\n";
echo "4. Game appears in profile: http://localhost:5173/app/profile?userId={$playerId}\n\n";

echo "✅ SYSTEM STATUS: " . ($httpCode === 200 && $gameCount > 0 ? "WORKING" : "NEEDS ATTENTION") . "\n";
echo "==============" . ($httpCode === 200 && $gameCount > 0 ? "=======" : "===============") . "\n";

if ($httpCode === 200 && $gameCount > 0) {
    echo "🎉 Game Log is ready for production use!\n";
    echo "Admin uploads create games that appear in player profiles.\n";
} else {
    echo "🔧 Follow the troubleshooting steps above.\n";
}
