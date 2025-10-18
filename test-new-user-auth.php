<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing New User Authentication\n";
echo "==================================\n\n";

// Find user 60 (the newly registered user)
$user = \App\Models\User::find(60);
if (!$user) {
    echo "❌ User 60 not found\n";
    exit(1);
}

echo "👤 User Found:\n";
echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "ID: {$user->id}\n\n";

// Create a fresh token for testing
echo "🔑 Creating fresh token...\n";
$token = $user->createToken('notification-test')->plainTextToken;
echo "Token: {$token}\n\n";

// Test the token with a simple API call
echo "🧪 Testing token with /api/me endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Token works with /api/me\n";
    $userData = json_decode($response, true);
    echo "User data: {$userData['name']} (ID: {$userData['id']})\n\n";
} else {
    echo "❌ Token failed with /api/me (HTTP {$httpCode})\n";
    echo "Response: {$response}\n\n";
}

// Test broadcasting auth
echo "🔊 Testing broadcasting auth...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/broadcasting/auth');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'socket_id' => 'test.123',
    'channel_name' => 'private-notifications.' . $user->id
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Broadcasting auth successful\n";
    echo "Response: {$response}\n\n";
} else {
    echo "❌ Broadcasting auth failed (HTTP {$httpCode})\n";
    echo "Response: {$response}\n\n";
}

echo "🎯 Instructions for frontend testing:\n";
echo "1. Clear browser localStorage: localStorage.clear()\n";
echo "2. Login with: {$user->email}\n";
echo "3. Check browser console for Pusher connection logs\n";
echo "4. Look for: '✅ Pusher connected successfully'\n\n";

echo "🔧 If still having issues:\n";
echo "1. The token key mismatch has been fixed\n";
echo "2. Make sure to refresh the frontend after the fix\n";
echo "3. Check browser console for any remaining errors\n";
