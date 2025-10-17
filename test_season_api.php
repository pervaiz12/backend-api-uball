<?php

// Test the season API functionality
// php test_season_api.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\GameController;

echo "Testing season API functionality...\n\n";

$controller = new GameController();

// Test different seasons
$seasons = [2024, 2023, 2022, 2021, 2020];

foreach ($seasons as $season) {
    echo "=== Testing Season {$season} ===\n";
    
    // Mock the request with season parameter
    $request = new Request(['season' => $season]);
    app()->instance('request', $request);
    
    try {
        $response = $controller->playerGames(12);
        $data = $response->toArray($request);
        
        echo "Games found: " . count($data) . "\n";
        
        foreach ($data as $game) {
            $gameYear = date('Y', strtotime($game['game_date']));
            echo "  - {$game['location']} ({$gameYear}) - Result: {$game['result']} - Score: {$game['team_score']}-{$game['opponent_score']}\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "✅ Season API test complete!\n";
