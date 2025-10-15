<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ClipResource;
use Illuminate\Support\Facades\Gate;

class FeedController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        $followingIds = $auth->following()->pluck('users.id');
        $perPage = (int) request()->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 50) : 15;

        $clips = Clip::with([
                'user:id,name,profile_photo',
                'game:id,location,game_date',
                'player:id,name,profile_photo',
            ])
            ->withCount(['likes', 'comments'])
            ->select('clips.*')
            ->selectRaw('EXISTS(SELECT 1 FROM likes WHERE likes.clip_id = clips.id AND likes.user_id = ?) as liked_by_me', [$auth->id])
            ->where(function ($q) use ($followingIds, $auth) {
                $q->whereIn('user_id', $followingIds)
                  ->orWhere('user_id', $auth->id);
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends(request()->query());

        return ClipResource::collection($clips);
    }

    public function like(Clip $clip)
    {
        $auth = Auth::user();
        Like::firstOrCreate([
            'user_id' => $auth->id,
            'clip_id' => $clip->id,
        ]);
        return response()->json(['message' => 'Liked']);
    }

    public function unlike(Clip $clip)
    {
        $auth = Auth::user();
        Like::where('user_id', $auth->id)->where('clip_id', $clip->id)->delete();
        return response()->json(['message' => 'Unliked']);
    }

    public function comment(Request $request, Clip $clip)
    {
        $auth = Auth::user();
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $comment = Comment::create([
            'user_id' => $auth->id,
            'clip_id' => $clip->id,
            'body' => $validated['body'],
        ]);

        return response()->json($comment->load('user:id,name,profile_photo'), 201);
    }

    public function comments(Clip $clip)
    {
        $list = Comment::where('clip_id', $clip->id)
            ->with('user:id,name,profile_photo')
            ->orderBy('id', 'desc')
            ->paginate((int) request()->query('per_page', 20));
        return response()->json([
            'data' => $list->items(),
            'meta' => [
                'current_page' => $list->currentPage(),
                'last_page' => $list->lastPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
            ],
        ]);
    }

    public function destroyComment(Comment $comment)
    {
        Gate::authorize('is-staff'); // staff or admin
        $comment->delete();
        return response()->json(null, 204);
    }
}
