<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserSmallResource;

class FollowerController extends Controller
{
    public function follow(User $user)
    {
        $auth = Auth::user();
        if ($auth->id === $user->id) {
            return response()->json(['message' => 'Cannot follow yourself'], 422);
        }
        $auth->following()->syncWithoutDetaching([$user->id]);
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
