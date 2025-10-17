<?php

// Test the complete season filtering flow
// php test_complete_flow.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing complete season filtering flow...\n\n";

// Simulate frontend API calls for different seasons
$seasons = [2024, 2023, 2022];
$playerId = 12;

foreach ($seasons as $season) {
    echo "=== Frontend Request: GET /api/players/{$playerId}/games?season={$season} ===\n";
    
    // Simulate the API call that would be made from frontend
    $url = "http://127.0.0.1:8000/api/players/{$playerId}/games?season={$season}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]
    ]);
    
    try {
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        
        if ($data && is_array($data)) {
            echo "✅ API Response: " . count($data) . " games found for {$season}\n";
            
            foreach ($data as $game) {
                $gameYear = date('Y', strtotime($game['game_date']));
                echo "  📅 {$game['location']} ({$gameYear}) - {$game['result']} {$game['team_score']}-{$game['opponent_score']}\n";
            }
        } else {
            echo "❌ Invalid response format\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "✅ Complete flow test finished!\n";
echo "\nTo test in frontend:\n";
echo "1. Open http://localhost:5173/app/profile?userId=12\n";
echo "2. Click on the season dropdown (2024 Season)\n";
echo "3. Select different years to see filtered results\n";
echo "4. Each selection should trigger a new API call\n";
