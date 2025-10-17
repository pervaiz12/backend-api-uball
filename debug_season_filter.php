<?php

// Debug season filter for Game Log API
// php debug_season_filter.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;
use App\Models\Clip;
use App\Models\PlayerStat;

echo "🔍 DEBUGGING SEASON FILTER\n";
echo "==========================\n\n";

$playerId = 54;
$season = 2024;

echo "👤 Player ID: {$playerId}\n";
echo "📅 Season: {$season}\n\n";

// Check what games exist for this player
echo "📊 DATA ANALYSIS:\n";
echo "=================\n";

$allPlayerGames = Game::whereHas('clips', function ($q) use ($playerId) {
    $q->where('player_id', $playerId)->where('status', 'approved');
})->orWhereHas('playerStats', function ($q) use ($playerId) {
    $q->where('user_id', $playerId);
})->orderByDesc('game_date')->get();

echo "🎮 Total games for player {$playerId}: " . $allPlayerGames->count() . "\n\n";

if ($allPlayerGames->count() > 0) {
    echo "📅 GAME DATES:\n";
    echo "==============\n";
    
    $yearCounts = [];
    foreach ($allPlayerGames as $game) {
        $year = date('Y', strtotime($game->game_date));
        $yearCounts[$year] = ($yearCounts[$year] ?? 0) + 1;
        
        echo "🎮 Game #{$game->id}: {$game->location} - {$game->game_date} (Year: {$year})\n";
    }
    
    echo "\n📊 GAMES BY YEAR:\n";
    echo "=================\n";
    foreach ($yearCounts as $year => $count) {
        echo "📅 {$year}: {$count} games\n";
    }
    echo "\n";
}

// Test the season filter query
echo "🔍 TESTING SEASON FILTER QUERY:\n";
echo "===============================\n";

$filteredQuery = Game::whereHas('clips', function ($q) use ($playerId) {
    $q->where('player_id', $playerId)->where('status', 'approved');
})->orWhereHas('playerStats', function ($q) use ($playerId) {
    $q->where('user_id', $playerId);
})->whereYear('game_date', $season)
->orderByDesc('game_date');

echo "🔍 SQL Query: " . $filteredQuery->toSql() . "\n";
echo "🔍 Bindings: " . json_encode($filteredQuery->getBindings()) . "\n\n";

$filteredGames = $filteredQuery->get();
echo "📊 Games for season {$season}: " . $filteredGames->count() . "\n\n";

if ($filteredGames->count() > 0) {
    echo "🎮 FILTERED GAMES:\n";
    echo "==================\n";
    foreach ($filteredGames as $game) {
        echo "🎮 Game #{$game->id}: {$game->location} - {$game->game_date}\n";
    }
} else {
    echo "❌ No games found for season {$season}\n";
    echo "This could mean:\n";
    echo "1. No games exist for this year\n";
    echo "2. Season filter is not working correctly\n";
    echo "3. Player has no clips/stats for games in this year\n";
}

echo "\n🌐 TESTING API ENDPOINT:\n";
echo "========================\n";

// Test the actual API endpoint
$apiUrl = "http://127.0.0.1:8000/api/players/{$playerId}/games?season={$season}";
echo "📡 URL: {$apiUrl}\n";

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
    $apiGameCount = isset($data['data']) ? count($data['data']) : count($data);
    echo "✅ API Response: {$apiGameCount} games returned\n";
    
    if ($apiGameCount > 0) {
        echo "\n🎮 API GAMES:\n";
        echo "=============\n";
        $games = isset($data['data']) ? $data['data'] : $data;
        foreach (array_slice($games, 0, 3) as $game) {
            echo "🎮 Game #{$game['id']}: {$game['location']} - {$game['game_date']}\n";
        }
    }
} else {
    echo "❌ API Error: HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n🔧 DIAGNOSIS:\n";
echo "=============\n";

if ($allPlayerGames->count() === 0) {
    echo "❌ No games found for player {$playerId}\n";
    echo "Solution: Upload clips with this player selected\n";
} else if ($filteredGames->count() === 0) {
    echo "❌ Season filter is not working or no games in {$season}\n";
    echo "Available years: " . implode(', ', array_keys($yearCounts)) . "\n";
    echo "Solution: Check if games exist in {$season} or fix filter logic\n";
} else if ($httpCode !== 200) {
    echo "❌ API endpoint has issues\n";
    echo "Solution: Check server logs and route configuration\n";
} else {
    echo "✅ Season filter is working correctly\n";
    echo "Games found: {$apiGameCount} for season {$season}\n";
}

echo "\n🎯 RECOMMENDATIONS:\n";
echo "===================\n";
echo "1. Check if player {$playerId} has clips/stats in {$season}\n";
echo "2. Verify game dates are in the correct year format\n";
echo "3. Test with different season values\n";
echo "4. Check SQL query execution and bindings\n";
