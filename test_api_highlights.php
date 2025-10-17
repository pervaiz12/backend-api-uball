<?php

// Test the API endpoint directly
// php test_api_highlights.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ClipController;

echo "Testing /api/players/12/highlights endpoint...\n\n";

try {
    $controller = new ClipController();
    $response = $controller->playerHighlights(12);
    
    echo "Response type: " . get_class($response) . "\n";
    
    // Convert to array to see the data
    $data = $response->toArray(new Request());
    
    echo "Response structure: " . json_encode(array_keys($data), JSON_PRETTY_PRINT) . "\n";
    
    $highlights = $data['data'] ?? $data;
    echo "Number of highlights found: " . count($highlights) . "\n\n";
    
    foreach ($highlights as $index => $highlight) {
        echo "Highlight " . ($index + 1) . ":\n";
        echo "  ID: " . $highlight['id'] . "\n";
        echo "  Title: " . $highlight['title'] . "\n";
        echo "  Description: " . $highlight['description'] . "\n";
        echo "  Tags: " . (isset($highlight['tags']) ? implode(', ', $highlight['tags']) : 'None') . "\n";
        echo "  Video URL: " . $highlight['video_url'] . "\n";
        echo "  Thumbnail URL: " . ($highlight['thumbnail_url'] ?? 'None') . "\n";
        echo "  Status: " . $highlight['status'] . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ API test complete!\n";
