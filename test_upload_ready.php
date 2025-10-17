<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎬 UPLOAD SYSTEM READY CHECK\n";
echo "============================\n\n";

echo "✅ PHP Configuration:\n";
echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "\n\n";

echo "✅ Storage Directories:\n";
$clipsPath = storage_path('app/public/clips');
$thumbnailsPath = storage_path('app/public/thumbnails');
echo "   Clips: " . ($clipsPath) . " - " . (is_writable($clipsPath) ? '✅ Writable' : '❌ Not writable') . "\n";
echo "   Thumbnails: " . ($thumbnailsPath) . " - " . (is_writable($thumbnailsPath) ? '✅ Writable' : '❌ Not writable') . "\n\n";

echo "✅ Database:\n";
use App\Models\User;
use App\Models\Game;
use App\Models\Clip;

$userCount = User::count();
$gameCount = Game::count();
$clipCount = Clip::count();

echo "   Users: {$userCount}\n";
echo "   Games: {$gameCount}\n";
echo "   Clips: {$clipCount}\n\n";

if ($gameCount === 0) {
    echo "⚠️  WARNING: No games found in database.\n";
    echo "   You need to create a game before uploading clips.\n";
    echo "   Clips require a valid game_id.\n\n";
}

if ($userCount === 0) {
    echo "⚠️  WARNING: No users found in database.\n";
    echo "   You need to register/login before uploading clips.\n\n";
}

echo "✅ Video Processing:\n";
exec('which ffmpeg 2>&1', $ffmpegOutput, $ffmpegReturn);
echo "   FFmpeg: " . ($ffmpegReturn === 0 ? '✅ Available' : '❌ Not found (thumbnails will use fallback)') . "\n";
echo "   GD Library: " . (extension_loaded('gd') ? '✅ Available' : '❌ Not available') . "\n\n";

echo "🚀 SYSTEM STATUS: ";
if (ini_get('upload_max_filesize') === '512M' && 
    ini_get('post_max_size') === '512M' && 
    is_writable($clipsPath) && 
    $gameCount > 0 && 
    $userCount > 0) {
    echo "✅ READY FOR UPLOADS!\n\n";
    echo "📝 Next Steps:\n";
    echo "   1. Login to your application\n";
    echo "   2. Navigate to upload page\n";
    echo "   3. Select a game from the dropdown\n";
    echo "   4. Upload your video file (up to 500MB)\n";
    echo "   5. Fill in the details and submit\n";
} else {
    echo "⚠️  NEEDS ATTENTION\n\n";
    if ($gameCount === 0) {
        echo "   - Create at least one game first\n";
    }
    if ($userCount === 0) {
        echo "   - Register/login a user first\n";
    }
}

echo "\n✅ Upload endpoint: POST http://localhost:8000/api/clips\n";
echo "✅ Authentication: Bearer token required (Laravel Sanctum)\n";
