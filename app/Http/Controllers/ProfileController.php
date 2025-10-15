<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        return new UserResource(Auth::user());
    }

    /**
     * Update the authenticated user's password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $user = Auth::user();
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => [ 'current_password' => ['The current password is incorrect.'] ]
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated']);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'home_court' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $user = Auth::user();
        $user->update($validated);
        return new UserResource($user->fresh());
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        try {
            $path = $request->file('photo')->store('profiles', 'public');
            // Store relative public path (frontend will prefix base URL)
            $relative = 'storage/' . ltrim($path, '/');

            $user = Auth::user();
            $user->profile_photo = $relative;
            $user->save();

            return response()->json(['profile_photo' => $relative]);
        } catch (\Throwable $e) {
            $maxUpload = ini_get('upload_max_filesize');
            $maxPost = ini_get('post_max_size');
            return response()->json([
                'message' => 'The profile photo failed to upload. Please ensure the image is <= 2MB and server limits are sufficient.',
                'errors' => [
                    'photo' => [
                        'Upload may have exceeded server limits. Current PHP limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost
                    ]
                ]
            ], 422);
        }
    }
}
