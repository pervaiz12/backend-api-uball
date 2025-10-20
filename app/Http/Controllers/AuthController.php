<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'player',
            'is_official' => false,
        ]);

        // Create token for automatic login
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        
        // Restrict access to admin and staff only
        if ($user->role === 'player') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Access denied. Only admin and staff can access this dashboard.'],
            ]);
        }
        
        // Update last login timestamp
        $user->last_login = now();
        $user->save();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function playerLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        
        // Only allow players to login through this endpoint
        if ($user->role !== 'player') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['This login is for players only.'],
            ]);
        }
        
        // Update last login timestamp
        $user->last_login = now();
        $user->save();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Handle Google OAuth login/signup
     */
    public function googleAuth(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'google_id' => ['required', 'string'],
            'profile_photo' => ['nullable', 'string'],
        ]);

        // Check if user exists with this email
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            // User exists, update google_id if not set
            if (!$user->google_id) {
                $user->google_id = $validated['google_id'];
                $user->save();
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'google_id' => $validated['google_id'],
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role' => 'player',
                'is_official' => false,
                'password' => Hash::make(uniqid()), // Random password for social login users
            ]);
        }

        // Update last login
        $user->last_login = now();
        $user->save();

        // Create token
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'message' => 'Google authentication successful',
        ]);
    }

    /**
     * Handle Facebook OAuth login/signup
     */
    public function facebookAuth(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'facebook_id' => ['required', 'string'],
            'profile_photo' => ['nullable', 'string'],
        ]);

        // Check if user exists with this email
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            // User exists, update facebook_id if not set
            if (!$user->facebook_id) {
                $user->facebook_id = $validated['facebook_id'];
                $user->save();
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'facebook_id' => $validated['facebook_id'],
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role' => 'player',
                'is_official' => false,
                'password' => Hash::make(uniqid()), // Random password for social login users
            ]);
        }

        // Update last login
        $user->last_login = now();
        $user->save();

        // Create token
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'message' => 'Facebook authentication successful',
        ]);
    }

    /**
     * Handle Apple OAuth login/signup
     */
    public function appleAuth(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'apple_id' => ['required', 'string'],
            'profile_photo' => ['nullable', 'string'],
        ]);

        // Check if user exists with this email
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            // User exists, update apple_id if not set
            if (!$user->apple_id) {
                $user->apple_id = $validated['apple_id'];
                $user->save();
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'apple_id' => $validated['apple_id'],
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role' => 'player',
                'is_official' => false,
                'password' => Hash::make(uniqid()), // Random password for social login users
            ]);
        }

        // Update last login
        $user->last_login = now();
        $user->save();

        // Create token
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'message' => 'Apple authentication successful',
        ]);
    }

    /**
<<<<<<< HEAD
     * Send password reset link to email
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check if user exists
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'If an account exists with this email, you will receive a password reset link.',
            ], 200);
        }

        // Delete old tokens for this email
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        // Generate token
        $token = Str::random(64);

        // Store token in database
        DB::table('password_reset_tokens')->insert([
            'email' => $validated['email'],
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Send email with reset link
        $resetUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($validated['email']);

        try {
            Mail::send('emails.password-reset', ['resetUrl' => $resetUrl, 'user' => $user], function ($message) use ($validated) {
                $message->to($validated['email']);
                $message->subject('Reset Your Password - UBALL');
            });
        } catch (\Exception $e) {
            \Log::error('Password reset email failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'If an account exists with this email, you will receive a password reset link.',
        ], 200);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Find token record
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired reset token.'],
            ]);
        }

        // Check if token is valid (not older than 60 minutes)
        if (now()->diffInMinutes($tokenRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            throw ValidationException::withMessages([
                'email' => ['Reset token has expired. Please request a new one.'],
            ]);
        }

        // Verify token
        if (!Hash::check($validated['token'], $tokenRecord->token)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid reset token.'],
            ]);
        }

        // Find user and update password
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now login with your new password.',
        ], 200);
=======
     * Redirect to Google OAuth provider
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user exists with this email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User exists, update google_id if not set
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'profile_photo' => $googleUser->getAvatar(),
                    'role' => 'player',
                    'is_official' => false,
                    'password' => Hash::make(uniqid()), // Random password for social login users
                ]);
            }

            // Update last login
            $user->last_login = now();
            $user->save();

            // Create token
            $token = $user->createToken('api')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => new UserResource($user),
                'message' => 'Google authentication successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage(),
            ], 400);
        }
>>>>>>> 4dd1727967bced996f32697bec111be3dc124dae
    }
}
