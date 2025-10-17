<?php

// Test Game Log functionality after admin uploads clips
// php test_game_log_fix.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;
use App\Models\Clip;
use App\Models\PlayerStat;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\GameController;

echo "🎮 TESTING GAME LOG FUNCTIONALITY\n";
echo "=================================\n\n";

$playerId = 12; // Test player
$player = User::find($playerId);

if (!$player) {
    echo "❌ Player {$playerId} not found\n";
    exit(1);
}

echo "👤 Testing Game Log for: {$player->name} (ID: {$playerId})\n\n";

// Check what games exist for this player
echo "📊 CURRENT DATA ANALYSIS:\n";
echo "=========================\n";

// 1. Games created by this player
$gamesCreatedByPlayer = Game::where('created_by', $playerId)->count();
echo "🎮 Games created by player: {$gamesCreatedByPlayer}\n";

// 2. Games where player has clips
$gamesWithClips = Game::whereHas('clips', function ($q) use ($playerId) {
    $q->where('player_id', $playerId)->where('status', 'approved');
})->count();
echo "🎬 Games where player has approved clips: {$gamesWithClips}\n";

// 3. Games where player has stats
$gamesWithStats = Game::whereHas('playerStats', function ($q) use ($playerId) {
    $q->where('user_id', $playerId);
})->count();
echo "📊 Games where player has stats: {$gamesWithStats}\n";

// 4. Total clips for this player
$playerClips = Clip::where('player_id', $playerId)->where('status', 'approved')->count();
echo "🎥 Total approved clips for player: {$playerClips}\n";

// 5. Total stats for this player
$playerStats = PlayerStat::where('user_id', $playerId)->count();
echo "📈 Total stat records for player: {$playerStats}\n\n";

// Test the API endpoint
echo "🌐 TESTING GAME LOG API:\n";
echo "========================\n";

try {
    $controller = new GameController();
    $response = $controller->playerGames($playerId);
    $gameLogData = $response->toArray(new Request());
    
    echo "✅ API Response: " . count($gameLogData) . " games returned\n\n";
    
    if (count($gameLogData) > 0) {
        echo "🎮 GAME LOG ENTRIES:\n";
        echo "===================\n";
        
        foreach ($gameLogData as $index => $game) {
            echo "🏀 Game #" . ($index + 1) . ":\n";
            echo "   📍 Location: {$game['location']}\n";
            echo "   📅 Date: {$game['game_date']}\n";
            echo "   🏆 Result: " . ($game['result'] ?? 'No result') . "\n";
            echo "   📊 Team Score: " . ($game['team_score'] ?? 'N/A') . "\n";
            echo "   📊 Opponent Score: " . ($game['opponent_score'] ?? 'N/A') . "\n";
            
            if (isset($game['player_stats'])) {
                $stats = $game['player_stats'];
                echo "   📈 Player Stats: {$stats['points']}PTS {$stats['rebounds']}REB {$stats['assists']}AST\n";
            }
            
            echo "   🎬 Clips: " . (isset($game['clips']) ? count($game['clips']) : 0) . "\n\n";
        }
    } else {
        echo "❌ NO GAMES FOUND IN GAME LOG\n";
        echo "=============================\n";
        
        // Diagnose the issue
        echo "🔍 DIAGNOSIS:\n";
        echo "=============\n";
        
        if ($playerClips === 0) {
            echo "❌ Player has no approved clips\n";
            echo "   Solution: Upload clips and approve them\n";
        } else {
            echo "✅ Player has {$playerClips} approved clips\n";
            
            // Check if clips are associated with games
            $clipsWithGames = Clip::where('player_id', $playerId)
                                  ->where('status', 'approved')
                                  ->whereNotNull('game_id')
                                  ->count();
            
            if ($clipsWithGames === 0) {
                echo "❌ Clips are not associated with games\n";
                echo "   Solution: Ensure game_id is set when uploading clips\n";
            } else {
                echo "✅ {$clipsWithGames} clips are associated with games\n";
                
                // Check if games exist
                $gameIds = Clip::where('player_id', $playerId)
                              ->where('status', 'approved')
                              ->whereNotNull('game_id')
                              ->pluck('game_id')
                              ->unique();
                
                $existingGames = Game::whereIn('id', $gameIds)->count();
                echo "🎮 Games referenced by clips: {$existingGames}\n";
                
                if ($existingGames === 0) {
                    echo "❌ Referenced games don't exist in database\n";
                    echo "   Solution: Create games or fix game_id references\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ API Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🔧 TROUBLESHOOTING STEPS:\n";
echo "=========================\n";
echo "1. ✅ Upload clips via dashboard with player selected\n";
echo "2. ✅ Ensure game is selected when uploading\n";
echo "3. ✅ Approve clips in dashboard\n";
echo "4. ✅ Check Game Log API: /api/players/{$playerId}/games\n";
echo "5. ✅ Verify frontend calls correct endpoint\n\n";

echo "📋 GAME LOG REQUIREMENTS:\n";
echo "=========================\n";
echo "For games to appear in Game Log:\n";
echo "✅ Player must have approved clips in the game, OR\n";
echo "✅ Player must have stats recorded for the game\n";
echo "✅ Game must exist in games table\n";
echo "✅ Clips must have game_id set\n\n";

echo "🎯 EXPECTED WORKFLOW:\n";
echo "=====================\n";
echo "1. Admin uploads clip with player and game selected\n";
echo "2. Clip gets approved (auto for admin)\n";
echo "3. Game appears in player's Game Log\n";
echo "4. Game shows player stats and clips\n";
echo "5. Frontend displays in profile Game Log section\n";
