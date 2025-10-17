<?php

// Test script to check highlights functionality
// php test_highlights.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Clip;

echo "Testing highlights functionality...\n\n";

// Check all clips for player 12
$allClips = Clip::where('player_id', 12)->get();
echo "Total clips for player 12: " . $allClips->count() . "\n";

// Check clips with "highlight" in tags
$highlightClips = Clip::where('player_id', 12)
    ->where('status', 'approved')
    ->where(function($query) {
        $query->whereJsonContains('tags', 'highlight')
              ->orWhereJsonContains('tags', 'HIGHLIGHT')
              ->orWhereJsonContains('tags', 'Highlight')
              ->orWhereJsonContains('tags', 'game_highlight')
              ->orWhereJsonContains('tags', 'GAME_HIGHLIGHT')
              ->orWhereJsonContains('tags', 'best_play')
              ->orWhereJsonContains('tags', 'BEST_PLAY');
    })
    ->get();

echo "Highlight clips found: " . $highlightClips->count() . "\n\n";

foreach ($highlightClips as $clip) {
    echo "Clip ID: {$clip->id}\n";
    echo "Title: {$clip->title}\n";
    echo "Description: {$clip->description}\n";
    $tags = $clip->tags;
    if (is_string($tags)) {
        $tagsArray = json_decode($tags, true);
        echo "Tags: " . ($tagsArray ? implode(', ', $tagsArray) : 'None') . "\n";
    } else if (is_array($tags)) {
        echo "Tags: " . implode(', ', $tags) . "\n";
    } else {
        echo "Tags: None\n";
    }
    echo "Video URL: {$clip->video_url}\n";
    echo "Thumbnail URL: {$clip->thumbnail_url}\n";
    echo "Status: {$clip->status}\n";
    echo "---\n";
}

if ($highlightClips->count() === 0) {
    echo "No highlights found. Creating some test highlights...\n";
    
    // Create a few test highlights
    $testHighlights = [
        ['description' => 'Test Dunk', 'tags' => ['highlight', 'dunk']],
        ['description' => 'Test 3PT', 'tags' => ['HIGHLIGHT', '3pt']],
        ['description' => 'Test Block', 'tags' => ['game_highlight', 'block']],
        ['description' => 'Test Assist', 'tags' => ['BEST_PLAY', 'assist']]
    ];
    
    for ($i = 1; $i <= 4; $i++) {
        $data = $testHighlights[$i - 1];
        $clip = Clip::create([
            'user_id' => 1,
            'player_id' => 12,
            'title' => "Test Highlight #{$i}",
            'description' => $data['description'],
            'tags' => json_encode($data['tags']),
            'video_url' => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
            'thumbnail_url' => "/image-1-12.png",
            'status' => 'approved',
            'likes_count' => rand(10, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "Created test highlight: {$data['description']} with tags: " . implode(', ', $data['tags']) . "\n";
    }
    
    echo "\nTest highlights created! Now check the profile page.\n";
}

echo "\n✅ Highlights test complete!\n";
