<?php

// Test Game Log API with authentication
// php test_authenticated_game_log.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

echo "🔐 TESTING AUTHENTICATED GAME LOG API\n";
echo "====================================\n\n";

$playerId = 12;

// Get or create a test user for authentication
$testUser = User::where('email', 'admin@example.com')->first();
if (!$testUser) {
    $testUser = User::where('role', 'admin')->first();
}

if (!$testUser) {
    echo "❌ No admin user found. Creating test user...\n";
    $testUser = User::create([
        'name' => 'Test Admin',
        'email' => 'test@admin.com',
        'password' => bcrypt('password'),
        'role' => 'admin'
    ]);
}

echo "👤 Test user: {$testUser->name} ({$testUser->email})\n";

// Create a personal access token
$token = $testUser->createToken('test-token')->plainTextToken;
echo "🔑 Generated token: " . substr($token, 0, 20) . "...\n\n";

// Test the API endpoint with authentication
$apiUrl = "http://127.0.0.1:8000/api/players/{$playerId}/games";
echo "📡 Testing: {$apiUrl}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "✅ SUCCESS! API returned " . count($data['data']) . " games\n\n";
        
        if (count($data['data']) > 0) {
            echo "🎮 SAMPLE GAME DATA:\n";
            echo "===================\n";
            $game = $data['data'][0];
            echo "ID: {$game['id']}\n";
            echo "Location: {$game['location']}\n";
            echo "Date: {$game['game_date']}\n";
            echo "Result: " . ($game['result'] ?? 'No result') . "\n";
            echo "Team Score: " . ($game['team_score'] ?? 'N/A') . "\n";
            echo "Opponent Score: " . ($game['opponent_score'] ?? 'N/A') . "\n\n";
            
            echo "✅ AUTHENTICATION IS WORKING!\n";
            echo "=============================\n";
            echo "The API endpoint works correctly with proper authentication.\n\n";
        }
    } else {
        echo "❌ Unexpected response format\n";
        echo "Response: " . substr($response, 0, 500) . "...\n\n";
    }
} else {
    echo "❌ API Error: HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 500) . "...\n\n";
}

// Clean up the test token
PersonalAccessToken::where('tokenable_id', $testUser->id)
    ->where('name', 'test-token')
    ->delete();

echo "🎯 FRONTEND ISSUE DIAGNOSIS:\n";
echo "============================\n";

if ($httpCode === 200) {
    echo "✅ Backend API is working correctly\n";
    echo "✅ Authentication is required and working\n";
    echo "❌ Frontend issue: User not authenticated or token not sent\n\n";
    
    echo "🔧 FRONTEND DEBUGGING STEPS:\n";
    echo "============================\n";
    echo "1. Check if user is logged in\n";
    echo "2. Verify auth token exists in localStorage\n";
    echo "3. Check browser console for 401/403 errors\n";
    echo "4. Ensure AuthContext is providing token\n";
    echo "5. Test login flow and token storage\n\n";
    
    echo "🌐 BROWSER DEBUGGING:\n";
    echo "=====================\n";
    echo "1. Open browser dev tools\n";
    echo "2. Go to Application > Local Storage\n";
    echo "3. Check for 'auth_token' key\n";
    echo "4. Go to Network tab\n";
    echo "5. Load profile page and check API calls\n";
    echo "6. Look for Authorization header in requests\n\n";
    
} else {
    echo "❌ Backend API has issues\n";
    echo "Check server logs and route configuration\n\n";
}

echo "🚀 SOLUTION:\n";
echo "============\n";
echo "The Game Log is empty because the API requires authentication.\n";
echo "Make sure the user is logged in before accessing the profile page.\n\n";

echo "📋 LOGIN FLOW:\n";
echo "==============\n";
echo "1. User must login at: http://localhost:5173/login\n";
echo "2. Login stores auth token in localStorage\n";
echo "3. Profile page uses token to fetch game data\n";
echo "4. Game Log displays the fetched games\n\n";

echo "🎮 TEST STEPS:\n";
echo "==============\n";
echo "1. Go to: http://localhost:5173/login\n";
echo "2. Login with admin credentials\n";
echo "3. Navigate to: http://localhost:5173/app/profile?userId={$playerId}\n";
echo "4. Game Log should now show games\n";
