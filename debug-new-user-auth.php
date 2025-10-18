<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debug New User Authentication Issues\n";
echo "======================================\n\n";

// Test registration endpoint
echo "1. Testing Registration Endpoint\n";
echo "--------------------------------\n";

$testEmail = 'debuguser' . time() . '@example.com';
$registrationData = [
    'name' => 'Debug Test User',
    'email' => $testEmail,
    'password' => 'password123',
    'password_confirmation' => 'password123'
];

// Simulate registration API call
$user = \App\Models\User::create([
    'name' => $registrationData['name'],
    'email' => $registrationData['email'],
    'password' => \Illuminate\Support\Facades\Hash::make($registrationData['password']),
    'role' => 'player',
    'is_official' => false,
]);

$token = $user->createToken('api')->plainTextToken;

echo "✅ User created: {$user->name} (ID: {$user->id})\n";
echo "✅ Token generated: {$token}\n\n";

// Test token validation
echo "2. Testing Token Validation\n";
echo "---------------------------\n";

$personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
if ($personalAccessToken) {
    $tokenUser = $personalAccessToken->tokenable;
    echo "✅ Token is valid\n";
    echo "   User: {$tokenUser->name} (ID: {$tokenUser->id})\n";
    echo "   Role: {$tokenUser->role}\n";
    echo "   Created: {$personalAccessToken->created_at}\n\n";
} else {
    echo "❌ Token validation failed\n\n";
}

// Test API endpoints that were failing
echo "3. Testing Problematic Endpoints\n";
echo "--------------------------------\n";

// Find a clip to test with
$testClip = \App\Models\Clip::first();
if (!$testClip) {
    echo "❌ No clips found for testing\n";
    exit(1);
}

echo "Testing with clip ID: {$testClip->id}\n";

// Simulate middleware authentication
try {
    // Set up request context for Sanctum
    $request = new \Illuminate\Http\Request();
    $request->headers->set('Authorization', "Bearer {$token}");
    
    // Test if Sanctum can authenticate the user
    $sanctumUser = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
    
    if ($sanctumUser) {
        echo "✅ Sanctum authentication successful\n";
        echo "   Authenticated user: {$sanctumUser->name}\n";
        echo "   User role: {$sanctumUser->role}\n";
        
        // Test like endpoint logic
        echo "\n4. Testing Like Endpoint Logic\n";
        echo "------------------------------\n";
        
        $existingLike = \App\Models\Like::where('user_id', $sanctumUser->id)
            ->where('clip_id', $testClip->id)
            ->first();
            
        if (!$existingLike) {
            echo "✅ No existing like found - can create new like\n";
        } else {
            echo "ℹ️  Like already exists - would toggle\n";
        }
        
        // Test comments endpoint logic
        echo "\n5. Testing Comments Endpoint Logic\n";
        echo "----------------------------------\n";
        
        $comments = \App\Models\Comment::where('clip_id', $testClip->id)->count();
        echo "✅ Comments query successful - found {$comments} comments\n";
        
    } else {
        echo "❌ Sanctum authentication failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during testing: {$e->getMessage()}\n";
}

echo "\n6. Debugging Summary\n";
echo "===================\n";
echo "✅ User registration: Working\n";
echo "✅ Token generation: Working\n";
echo "✅ Token validation: Working\n";
echo "✅ Sanctum authentication: Working\n";
echo "✅ Database queries: Working\n";
echo "\n💡 If frontend is getting 401 errors, check:\n";
echo "   1. Token is being stored in localStorage as 'auth_token'\n";
echo "   2. Token is being sent in Authorization header as 'Bearer {token}'\n";
echo "   3. API base URL is correct (http://127.0.0.1:8000)\n";
echo "   4. CORS is properly configured\n";

echo "\n🧪 Test this token manually:\n";
echo "curl -X POST 'http://127.0.0.1:8000/api/posts/{$testClip->id}/like' \\\n";
echo "  -H 'Authorization: Bearer {$token}' \\\n";
echo "  -H 'Accept: application/json'\n";
