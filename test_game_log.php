<?php

// Test the game log functionality
// php test_game_log.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\GameController;

echo "Testing game log functionality...\n\n";

try {
    $controller = new GameController();
    $response = $controller->playerGames(12);
    
    echo "Response type: " . get_class($response) . "\n";
    
    // Convert to array to see the data
    $data = $response->toArray(new Request());
    
    echo "Number of games found: " . count($data) . "\n\n";
    
    foreach ($data as $index => $game) {
        echo "Game " . ($index + 1) . ":\n";
        echo "  ID: " . $game['id'] . "\n";
        echo "  Location: " . $game['location'] . "\n";
        echo "  Date: " . $game['game_date'] . "\n";
        echo "  Result: " . ($game['result'] ?? 'Not set') . "\n";
        echo "  Team Score: " . ($game['team_score'] ?? 'Not set') . "\n";
        echo "  Opponent Score: " . ($game['opponent_score'] ?? 'Not set') . "\n";
        
        if (isset($game['player_stats'])) {
            $stats = $game['player_stats'];
            echo "  Player Stats: {$stats['points']}PTS {$stats['rebounds']}REB {$stats['assists']}AST\n";
        } else {
            echo "  Player Stats: No stats available\n";
        }
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Game log test complete!\n";
