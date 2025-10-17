<?php

// Run this script to create real data for Player 12
// php create_real_data_for_player_12.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Game;
use App\Models\PlayerStat;
use App\Models\Clip;
use Illuminate\Support\Facades\DB;

$player = User::find(12);
if (!$player) {
    echo "Player 12 not found!\n";
    exit;
}

echo "Creating real data for Player: {$player->name}\n";

// Create followers for this player
$followerIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]; // Some user IDs to follow this player
foreach ($followerIds as $followerId) {
    if (User::find($followerId)) {
        DB::table('followers')->insertOrIgnore([
            'follower_id' => $followerId,
            'following_id' => $player->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

// Create some games for this player across different years
$games = [];
$years = [2024, 2023, 2022, 2021, 2020];
$courts = ['Lakers Arena', 'Warriors Court', 'Celtics Garden', 'Heat Stadium', 'Bulls Center', 'Nets Arena', 'Spurs Court', 'Knicks Garden', 'Rockets Center'];

for ($i = 1; $i <= 15; $i++) {
    $year = $years[array_rand($years)];
    $month = rand(1, 12);
    $day = rand(1, 28);
    
    $game = Game::create([
        'location' => $courts[array_rand($courts)],
        'game_date' => "{$year}-{$month}-{$day} " . rand(10, 22) . ":" . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ":00",
        'created_by' => $player->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $games[] = $game;
    echo "Created game {$i}: {$game->location} on {$game->game_date}\n";
}

// Create player stats for each game
foreach ($games as $index => $game) {
    $stat = PlayerStat::create([
        'user_id' => $player->id,
        'game_id' => $game->id,
        'points' => rand(18, 35),
        'assists' => rand(5, 15),
        'rebounds' => rand(8, 18),
        'steals' => rand(1, 5),
        'blocks' => rand(0, 4),
        'fg_made' => rand(8, 18),
        'fg_attempts' => rand(15, 30),
        'three_made' => rand(2, 8),
        'three_attempts' => rand(5, 15),
        'minutes_played' => rand(25, 40),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "Created stats for game " . ($index + 1) . ": {$stat->points}PTS {$stat->assists}AST {$stat->rebounds}REB\n";
}

// Create some clips for this player
$clipData = [
    ['description' => 'Amazing Dunk', 'tags' => ['highlight', 'dunk', 'best_play']],
    ['description' => '3PT Shot', 'tags' => ['highlight', '3pt', 'shooting']],
    ['description' => 'Perfect Assist', 'tags' => ['highlight', 'assist', 'teamwork']],
    ['description' => 'Steal and Score', 'tags' => ['highlight', 'steal', 'fast_break']],
    ['description' => 'Block Party', 'tags' => ['highlight', 'block', 'defense']],
    ['description' => 'Crossover Move', 'tags' => ['highlight', 'crossover', 'skills']],
    ['description' => 'Dunk Contest', 'tags' => ['HIGHLIGHT', 'dunk', 'contest']],
    ['description' => 'Game Winner', 'tags' => ['game_highlight', 'clutch', 'winner']],
    ['description' => 'Regular Layup', 'tags' => ['layup', 'scoring']],
    ['description' => 'Free Throw', 'tags' => ['free_throw', 'routine']],
    ['description' => 'Rebound', 'tags' => ['rebound', 'hustle']],
    ['description' => 'Fast Break', 'tags' => ['fast_break', 'speed']],
    ['description' => 'Triple Double', 'tags' => ['BEST_PLAY', 'triple_double', 'milestone']],
    ['description' => 'Buzzer Beater', 'tags' => ['highlight', 'buzzer_beater', 'clutch']],
    ['description' => 'Alley Oop', 'tags' => ['GAME_HIGHLIGHT', 'alley_oop', 'teamwork']]
];

for ($i = 1; $i <= 15; $i++) {
    $data = $clipData[$i - 1];
    $clip = Clip::create([
        'user_id' => 1, // Admin uploaded it
        'player_id' => $player->id, // But it's about this player
        'game_id' => $games[array_rand($games)]->id,
        'title' => "Game Clip #{$i}",
        'description' => $data['description'],
        'tags' => json_encode($data['tags']),
        'video_url' => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
        'thumbnail_url' => "/image-1-12.png",
        'status' => 'approved',
        'likes_count' => rand(50, 500),
        'comments_count' => rand(5, 50),
        'created_at' => now()->subDays(rand(1, 30)),
        'updated_at' => now(),
    ]);
    
    echo "Created clip {$i}: {$data['description']} with tags: " . implode(', ', $data['tags']) . "\n";
}

echo "\n✅ Successfully created real data for Player 12!\n";
echo "- Games: " . count($games) . "\n";
echo "- Stats: " . count($games) . " (one per game)\n";
echo "- Clips: 15\n";
echo "- Followers: " . count($followerIds) . "\n";
echo "\nNow refresh the profile page to see real data!\n";
