<?php

// Test the media gallery functionality
// php test_media_gallery.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ClipController;

echo "Testing media gallery functionality...\n\n";

try {
    $controller = new ClipController();
    $response = $controller->playerClips(12);
    
    echo "Response type: " . get_class($response) . "\n";
    
    // Convert to array to see the data
    $data = $response->toArray(new Request());
    
    echo "Number of clips found: " . count($data) . "\n\n";
    
    foreach ($data as $index => $clip) {
        echo "Clip " . ($index + 1) . ":\n";
        echo "  ID: " . $clip['id'] . "\n";
        echo "  Title: " . ($clip['title'] ?? 'No title') . "\n";
        echo "  Description: " . ($clip['description'] ?? 'No description') . "\n";
        echo "  Video URL: " . $clip['video_url'] . "\n";
        echo "  Thumbnail URL: " . ($clip['thumbnail_url'] ?? 'No thumbnail') . "\n";
        echo "  Views Count: " . ($clip['views_count'] ?? 0) . "\n";
        echo "  Status: " . $clip['status'] . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Media gallery test complete!\n";
echo "\nTo test in frontend:\n";
echo "1. Open http://localhost:5173/app/profile?userId=12\n";
echo "2. Scroll down to the Media Gallery section\n";
echo "3. You should see thumbnails with view counts\n";
echo "4. Videos should have play buttons overlay\n";
