<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;
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

        if ($user->role === 'player') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Access denied. Only admin and staff can access this dashboard.'],
            ]);
        }

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

        if ($user->role !== 'player') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['This login is for players only.'],
            ]);
        }

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

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $validated['google_id'];
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'google_id' => $validated['google_id'],
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role' => 'player',
                'is_official' => false,
                'password' => Hash::make(uniqid()),
            ]);
        }

        $user->last_login = now();
        $user->save();

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

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            if (!$user->facebook_id) {
                $user->facebook_id = $validated['facebook_id'];
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'facebook_id' => $validated['facebook_id'],
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role' => 'player',
                'is_official' => false,
                'password' => Hash::make(uniqid()),
            ]);
        }

        $user->last_login = now();
        $user->save();

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

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            if (!$user->apple_id) {
                $user->apple_id = $validated['apple_id'];
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'apple_id' => $validated['apple_id'],
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role' => 'player',
                'is_official' => false,
                'password' => Hash::make(uniqid()),
            ]);
        }

        $user->last_login = now();
        $user->save();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'message' => 'Apple authentication successful',
        ]);
    }

    /**
     * Send password reset link to email
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'If an account exists with this email, you will receive a password reset link.',
            ], 200);
        }

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $validated['email'],
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

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

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired reset token.'],
            ]);
        }

        if (now()->diffInMinutes($tokenRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            throw ValidationException::withMessages([
                'email' => ['Reset token has expired. Please request a new one.'],
            ]);
        }

        if (!Hash::check($validated['token'], $tokenRecord->token)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid reset token.'],
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now login with your new password.',
        ], 200);
    }

    /**
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

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'profile_photo' => $googleUser->getAvatar(),
                    'role' => 'player',
                    'is_official' => false,
                    'password' => Hash::make(uniqid()),
                ]);
            }

            $user->last_login = now();
            $user->save();

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
    }
}
