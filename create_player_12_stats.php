<?php

// Run this script to create stats for Player 12
// php create_player_12_stats.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Game;
use App\Models\PlayerStat;

$player = User::find(12);
if (!$player) {
    echo "Player 12 not found!\n";
    exit;
}

echo "Creating stats for Player: {$player->name}\n";

// Create some games for this player
for ($i = 1; $i <= 5; $i++) {
    $game = Game::create([
        'location' => "Court {$i} - Basketball Arena",
        'game_date' => now()->subDays(rand(1, 30)),
        'created_by' => $player->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create player stats for each game
    PlayerStat::create([
        'player_id' => $player->id,
        'game_id' => $game->id,
        'points' => rand(15, 35),
        'assists' => rand(3, 12),
        'rebounds' => rand(5, 15),
        'steals' => rand(0, 5),
        'blocks' => rand(0, 3),
        'fg_made' => rand(6, 15),
        'fg_attempts' => rand(12, 25),
        'three_made' => rand(1, 6),
        'three_attempts' => rand(3, 10),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "Created game {$i} with stats\n";
}

echo "✅ Successfully created 5 games and stats for Player 12!\n";
echo "Now refresh the profile page.\n";
