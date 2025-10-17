<?php

// Test the Game Log API endpoint directly
// php test_game_log_api_direct.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\GameController;
use App\Models\Game;
use App\Models\Clip;
use App\Models\PlayerStat;

echo "🌐 TESTING GAME LOG API ENDPOINT DIRECTLY\n";
echo "=========================================\n\n";

$playerId = 12;

echo "📡 Testing: GET /api/players/{$playerId}/games\n";
echo "==============================================\n\n";

try {
    // Create a mock request
    $request = new Request();
    $request->setMethod('GET');
    
    // Test the controller method directly
    $controller = new GameController();
    $response = $controller->playerGames($playerId);
    
    // Get the response data
    $responseData = $response->toArray($request);
    
    echo "✅ API Response Status: Success\n";
    echo "📊 Games Count: " . count($responseData) . "\n\n";
    
    if (count($responseData) > 0) {
        echo "🎮 FIRST 3 GAMES FROM API:\n";
        echo "==========================\n";
        
        foreach (array_slice($responseData, 0, 3) as $index => $game) {
            echo "🏀 Game #" . ($index + 1) . ":\n";
            echo "   ID: {$game['id']}\n";
            echo "   Location: {$game['location']}\n";
            echo "   Date: {$game['game_date']}\n";
            echo "   Result: " . ($game['result'] ?? 'No result') . "\n";
            echo "   Team Score: " . ($game['team_score'] ?? 'N/A') . "\n";
            echo "   Opponent Score: " . ($game['opponent_score'] ?? 'N/A') . "\n";
            
            if (isset($game['player_stats'])) {
                $stats = $game['player_stats'];
                echo "   Player Stats: {$stats['points']}PTS {$stats['rebounds']}REB {$stats['assists']}AST\n";
            }
            
            echo "   Clips Count: " . (isset($game['clips']) ? count($game['clips']) : 0) . "\n\n";
        }
        
        echo "✅ GAME LOG API IS WORKING!\n";
        echo "===========================\n";
        echo "🎯 The backend is returning game data correctly\n";
        echo "📱 Frontend should receive this data via /api/players/{$playerId}/games\n\n";
        
    } else {
        echo "❌ NO GAMES RETURNED\n";
        echo "====================\n";
        echo "This means the query is not finding games where:\n";
        echo "- Player has approved clips, OR\n";
        echo "- Player has stats recorded\n\n";
        
        // Debug the query conditions
        echo "🔍 DEBUGGING QUERY CONDITIONS:\n";
        echo "==============================\n";
        
        // Check clips condition
        $gamesWithClips = Game::whereHas('clips', function ($q) use ($playerId) {
            $q->where('player_id', $playerId)->where('status', 'approved');
        })->count();
        
        echo "🎬 Games with approved clips for player {$playerId}: {$gamesWithClips}\n";
        
        // Check stats condition  
        $gamesWithStats = Game::whereHas('playerStats', function ($q) use ($playerId) {
            $q->where('user_id', $playerId);
        })->count();
        
        echo "📊 Games with stats for player {$playerId}: {$gamesWithStats}\n";
        
        // Check total clips for player
        $totalClips = Clip::where('player_id', $playerId)->where('status', 'approved')->count();
        echo "🎥 Total approved clips for player: {$totalClips}\n";
        
        // Check total stats for player
        $totalStats = PlayerStat::where('user_id', $playerId)->count();
        echo "📈 Total stats for player: {$totalStats}\n";
    }
    
} catch (Exception $e) {
    echo "❌ API ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 FRONTEND INTEGRATION:\n";
echo "========================\n";
echo "The frontend calls: gamesApi.getPlayerGames({$playerId})\n";
echo "Which hits: GET /api/players/{$playerId}/games\n";
echo "Backend method: GameController@playerGames\n\n";

echo "🔧 IF GAME LOG IS STILL EMPTY:\n";
echo "==============================\n";
echo "1. Check browser console for API errors\n";
echo "2. Verify frontend is calling correct endpoint\n";
echo "3. Check if clips are approved and have player_id set\n";
echo "4. Ensure games exist and are properly linked\n";
echo "5. Test API directly: curl http://localhost:8000/api/players/{$playerId}/games\n";
