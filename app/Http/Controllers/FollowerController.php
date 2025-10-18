<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserSmallResource;
use App\Notifications\UserFollowedNotification;

class FollowerController extends Controller
{
    public function follow(User $user)
    {
        $auth = Auth::user();
        if ($auth->id === $user->id) {
            return response()->json(['message' => 'Cannot follow yourself'], 422);
        }
        
        // Check if already following to avoid duplicate notifications
        $wasAlreadyFollowing = $auth->following()->where('following_id', $user->id)->exists();
        
        $auth->following()->syncWithoutDetaching([$user->id]);
        
        // Send notification only if this is a new follow (not already following)
        if (!$wasAlreadyFollowing) {
            $user->notify(new UserFollowedNotification(
                followerId: $auth->id,
                followerName: $auth->name,
                followerProfilePhoto: $auth->profile_photo
            ));
            
            // Debug logging
            \Log::info('Follow notification sent', [
                'follower_id' => $auth->id,
                'follower_name' => $auth->name,
                'followed_user_id' => $user->id,
                'followed_user_name' => $user->name
            ]);
        }
        
        return response()->json(['message' => 'Followed']);
    }

    public function unfollow(User $user)
    {
        $auth = Auth::user();
        $auth->following()->detach($user->id);
        return response()->json(['message' => 'Unfollowed']);
    }

    public function followers()
    {
        $auth = Auth::user();
        $list = $auth->followers()->select('users.id', 'users.name', 'users.profile_photo')->get();
        return UserSmallResource::collection($list);
    }

    public function following()
    {
        $auth = Auth::user();
        $list = $auth->following()->select('users.id', 'users.name', 'users.profile_photo')->get();
        return UserSmallResource::collection($list);
    }
}
