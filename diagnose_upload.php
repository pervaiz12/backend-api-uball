<?php

echo "🔍 UPLOAD DIAGNOSTICS\n";
echo "=====================\n\n";

echo "📊 PHP CONFIGURATION:\n";
echo "====================\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";

echo "\n📁 PHP.INI LOCATIONS:\n";
echo "====================\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
echo "Additional .ini files: " . (php_ini_scanned_files() ?: 'None') . "\n";

echo "\n📂 STORAGE DIRECTORIES:\n";
echo "======================\n";

$storagePath = __DIR__ . '/storage/app/public';
$clipsPath = __DIR__ . '/storage/app/public/clips';
$thumbnailsPath = __DIR__ . '/storage/app/public/thumbnails';

echo "Storage path: {$storagePath}\n";
echo "  Exists: " . (is_dir($storagePath) ? '✅' : '❌') . "\n";
echo "  Writable: " . (is_writable($storagePath) ? '✅' : '❌') . "\n";

echo "\nClips path: {$clipsPath}\n";
echo "  Exists: " . (is_dir($clipsPath) ? '✅' : '❌') . "\n";
echo "  Writable: " . (is_writable($clipsPath) ? '✅' : '❌') . "\n";

echo "\nThumbnails path: {$thumbnailsPath}\n";
echo "  Exists: " . (is_dir($thumbnailsPath) ? '✅' : '❌') . "\n";
echo "  Writable: " . (is_writable($thumbnailsPath) ? '✅' : '❌') . "\n";

echo "\n🔗 STORAGE LINK:\n";
echo "================\n";
$publicStorage = __DIR__ . '/public/storage';
echo "Public storage link: {$publicStorage}\n";
echo "  Exists: " . (file_exists($publicStorage) ? '✅' : '❌') . "\n";
echo "  Is symlink: " . (is_link($publicStorage) ? '✅' : '❌') . "\n";
if (is_link($publicStorage)) {
    echo "  Points to: " . readlink($publicStorage) . "\n";
}

echo "\n🎬 VIDEO SUPPORT:\n";
echo "=================\n";
echo "FFmpeg available: ";
exec('which ffmpeg 2>&1', $ffmpegOutput, $ffmpegReturn);
echo ($ffmpegReturn === 0 ? '✅ ' . $ffmpegOutput[0] : '❌ Not found') . "\n";

echo "ImageMagick extension: " . (extension_loaded('imagick') ? '✅ Loaded' : '❌ Not loaded') . "\n";
echo "GD extension: " . (extension_loaded('gd') ? '✅ Loaded' : '❌ Not loaded') . "\n";

echo "\n⚠️  ISSUES DETECTED:\n";
echo "===================\n";

$issues = [];

if (ini_get('upload_max_filesize') !== '512M') {
    $issues[] = "❌ upload_max_filesize is " . ini_get('upload_max_filesize') . " (should be 512M)";
}
if (ini_get('post_max_size') !== '512M') {
    $issues[] = "❌ post_max_size is " . ini_get('post_max_size') . " (should be 512M)";
}
if (!is_dir($clipsPath)) {
    $issues[] = "❌ Clips directory doesn't exist";
}
if (is_dir($clipsPath) && !is_writable($clipsPath)) {
    $issues[] = "❌ Clips directory is not writable";
}
if (!is_dir($thumbnailsPath)) {
    $issues[] = "❌ Thumbnails directory doesn't exist";
}
if (!file_exists($publicStorage)) {
    $issues[] = "❌ Storage symlink doesn't exist (run: php artisan storage:link)";
}

if (empty($issues)) {
    echo "✅ No issues detected!\n";
} else {
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
}

echo "\n🔧 RECOMMENDED FIXES:\n";
echo "====================\n";

if (ini_get('upload_max_filesize') !== '512M' || ini_get('post_max_size') !== '512M') {
    echo "1. Update PHP configuration:\n";
    echo "   - Edit your php.ini file: " . (php_ini_loaded_file() ?: '/etc/php/8.x/fpm/php.ini') . "\n";
    echo "   - Set: upload_max_filesize = 512M\n";
    echo "   - Set: post_max_size = 512M\n";
    echo "   - Restart PHP-FPM: sudo systemctl restart php8.2-fpm\n";
    echo "   - Or restart Apache: sudo systemctl restart apache2\n\n";
}

if (!file_exists($publicStorage)) {
    echo "2. Create storage symlink:\n";
    echo "   php artisan storage:link\n\n";
}

if (!is_dir($clipsPath) || !is_dir($thumbnailsPath)) {
    echo "3. Create missing directories:\n";
    echo "   mkdir -p storage/app/public/clips\n";
    echo "   mkdir -p storage/app/public/thumbnails\n";
    echo "   chmod -R 775 storage/app/public\n\n";
}

echo "✅ Done!\n";
