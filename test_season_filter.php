<?php

// Test season filtering
// php test_season_filter.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;

echo "Testing season filtering...\n\n";

$games = Game::where('created_by', 12)->orderBy('game_date', 'desc')->get();

echo "Total games for player 12: " . $games->count() . "\n\n";

// Group games by year
$gamesByYear = [];
foreach ($games as $game) {
    $year = date('Y', strtotime($game->game_date));
    if (!isset($gamesByYear[$year])) {
        $gamesByYear[$year] = [];
    }
    $gamesByYear[$year][] = $game;
}

// Display games by year
foreach ($gamesByYear as $year => $yearGames) {
    echo "=== {$year} Season ({" . count($yearGames) . "} games) ===\n";
    foreach ($yearGames as $game) {
        echo "  - {$game->location} on " . date('M j, Y', strtotime($game->game_date)) . "\n";
    }
    echo "\n";
}

echo "✅ Season filter test complete!\n";
