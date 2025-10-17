<?php

// Test the ratings calculation
// php test_ratings.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\UsersController;

echo "Testing player ratings calculation...\n\n";

try {
    $controller = new UsersController();
    $user = \App\Models\User::find(12);
    
    if (!$user) {
        echo "Player 12 not found!\n";
        exit;
    }
    
    echo "Testing ratings for player: {$user->name}\n";
    
    // Get the public profile which includes ratings
    $response = $controller->showPublic($user);
    $data = $response->toArray(new Request());
    
    echo "Player Data:\n";
    echo "  Overall Rating: " . ($data['overall_rating'] ?? 'Not set') . "\n";
    echo "  Offense Rating: " . ($data['offense_rating'] ?? 'Not set') . "\n";
    echo "  Defense Rating: " . ($data['defense_rating'] ?? 'Not set') . "\n";
    echo "  Games Count: " . ($data['games_count'] ?? 0) . "\n";
    echo "  Clips Count: " . ($data['clips_count'] ?? 0) . "\n";
    echo "  Followers Count: " . ($data['followers_count'] ?? 0) . "\n";
    
    // Check player stats
    $stats = \App\Models\PlayerStat::where('user_id', 12)->get();
    echo "\nPlayer Stats Summary:\n";
    echo "  Total Games: " . $stats->count() . "\n";
    
    if ($stats->count() > 0) {
        echo "  Total Points: " . $stats->sum('points') . "\n";
        echo "  Total Assists: " . $stats->sum('assists') . "\n";
        echo "  Total Rebounds: " . $stats->sum('rebounds') . "\n";
        echo "  Total Steals: " . $stats->sum('steals') . "\n";
        echo "  Total Blocks: " . $stats->sum('blocks') . "\n";
        echo "  FG Made/Attempts: " . $stats->sum('fg_made') . "/" . $stats->sum('fg_attempts') . "\n";
        
        $avgPpg = $stats->sum('points') / $stats->count();
        $avgApg = $stats->sum('assists') / $stats->count();
        $avgRpg = $stats->sum('rebounds') / $stats->count();
        
        echo "  Averages: {$avgPpg} PPG, {$avgApg} APG, {$avgRpg} RPG\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Ratings test complete!\n";
