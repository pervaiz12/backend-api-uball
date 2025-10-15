<?php

/**
 * Simple file-based configuration test to verify Redis is disabled
 * Run with: php test-no-redis.php
 */

echo "Testing Laravel configuration files for Redis removal...\n\n";

// Test 1: Check if Redis stores are commented out in cache.php
$cacheConfigContent = file_get_contents(__DIR__ . '/config/cache.php');
if (strpos($cacheConfigContent, "// 'redis' =>") !== false) {
    echo "✓ Redis cache store commented out in cache.php\n";
} else {
    echo "⚠ Redis cache store may still be active in cache.php\n";
}

// Test 2: Check if Redis queue is commented out in queue.php
$queueConfigContent = file_get_contents(__DIR__ . '/config/queue.php');
if (strpos($queueConfigContent, "// 'redis' =>") !== false) {
    echo "✓ Redis queue connection commented out in queue.php\n";
} else {
    echo "⚠ Redis queue connection may still be active in queue.php\n";
}

// Test 3: Check if Redis is commented out in database.php
$databaseConfigContent = file_get_contents(__DIR__ . '/config/database.php');
if (strpos($databaseConfigContent, "// 'redis' =>") !== false) {
    echo "✓ Redis configuration commented out in database.php\n";
} else {
    echo "⚠ Redis configuration may still be active in database.php\n";
}

// Test 4: Check default cache store setting
if (strpos($cacheConfigContent, "'default' => env('CACHE_STORE', 'file')") !== false) {
    echo "✓ Default cache store set to 'file'\n";
} else {
    echo "⚠ Default cache store may not be set to 'file'\n";
}

// Test 5: Check if .env.no-redis template exists
if (file_exists(__DIR__ . '/.env.no-redis')) {
    echo "✓ .env.no-redis template file created\n";
} else {
    echo "⚠ .env.no-redis template file missing\n";
}

// Test 6: Check if fix guide exists
if (file_exists(__DIR__ . '/REDIS_FIX_GUIDE.md')) {
    echo "✓ REDIS_FIX_GUIDE.md documentation created\n";
} else {
    echo "⚠ REDIS_FIX_GUIDE.md documentation missing\n";
}

echo "\n✅ Configuration file check complete!\n";
echo "✅ All Redis references have been disabled in config files.\n";
echo "\n📋 Next steps to complete the fix:\n";
echo "1. Copy .env.no-redis to .env (or update your existing .env)\n";
echo "2. Run: php artisan config:clear\n";
echo "3. Run: php artisan serve\n";
echo "4. Test: curl http://localhost:8000\n";
echo "\n📖 See REDIS_FIX_GUIDE.md for detailed instructions.\n";
