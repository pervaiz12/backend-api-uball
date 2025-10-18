<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debug Authentication Issues\n";
echo "==============================\n\n";

// Check if we can get the request headers
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    echo "Authorization Header: " . $authHeader . "\n";
    
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        echo "Token: " . $token . "\n";
        
        // Try to find the token in the database
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if ($personalAccessToken) {
            $user = $personalAccessToken->tokenable;
            echo "✅ Token is valid\n";
            echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
            echo "Token created: " . $personalAccessToken->created_at . "\n";
            echo "Last used: " . ($personalAccessToken->last_used_at ?? 'Never') . "\n";
        } else {
            echo "❌ Token not found or invalid\n";
        }
    } else {
        echo "❌ Invalid authorization header format\n";
    }
} else {
    echo "❌ No authorization header found\n";
}

echo "\n📋 Recent Active Tokens:\n";
$recentTokens = \Laravel\Sanctum\PersonalAccessToken::with('tokenable')
    ->where('created_at', '>', now()->subDays(7))
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recentTokens as $token) {
    echo "- User: {$token->tokenable->name} (ID: {$token->tokenable->id})\n";
    echo "  Token: {$token->token}\n";
    echo "  Created: {$token->created_at}\n";
    echo "  Last used: " . ($token->last_used_at ?? 'Never') . "\n\n";
}
