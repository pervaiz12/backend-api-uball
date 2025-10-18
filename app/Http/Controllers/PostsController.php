<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\Clip;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostsController extends Controller
{
    /**
     * Get paginated posts for the feed (using clips data)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 50) : 10;
        $currentUserId = auth()->id();

        // Fetch clips instead of posts, only approved clips
        $clips = Clip::with(['user:id,name,profile_photo,role,is_official,city', 'player:id,name,profile_photo,role,is_official', 'game:id,location,game_date'])
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // Transform clips to match post structure and add is_liked_by_user flag
        $clips->getCollection()->transform(function ($clip) use ($currentUserId) {
            // Check if current user liked this clip
            $isLiked = Like::where('user_id', $currentUserId)
                ->where('clip_id', $clip->id)
                ->exists();
            
            $clip->is_liked_by_user = $isLiked;
            
            // Map clip fields to post-like structure for frontend compatibility
            $clip->content = $clip->description;
            $clip->media_url = $clip->video_url;
            $clip->media_type = 'video';
            
            // Keep both user and player - frontend will use player if available
            // No need to replace, just ensure both are loaded
            
            return $clip;
        });

        return response()->json([
            'data' => $clips->items(),
            'pagination' => [
                'current_page' => $clips->currentPage(),
                'last_page' => $clips->lastPage(),
                'per_page' => $clips->perPage(),
                'total' => $clips->total(),
                'has_more' => $clips->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get a single post
     */
    public function show(Post $post): JsonResponse
    {
        $post->load(['user:id,name,profile_photo,role,is_official,city']);
        
        return response()->json($post);
    }

    /**
     * Create a new post (authenticated users only)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'nullable|string|max:2000',
            'media_url' => 'nullable|string|max:500',
            'media_type' => 'nullable|in:video,image,text',
            'badge' => 'nullable|string|max:50',
            'is_highlight' => 'boolean',
        ]);

        $post = Post::create([
            'user_id' => auth()->id(),
            ...$validated
        ]);

        $post->load(['user:id,name,profile_photo,role,is_official,city']);

        return response()->json($post, 201);
    }

    /**
     * Like/unlike a post (toggle) - now works with clips
     */
    public function toggleLike($id): JsonResponse
    {
        $userId = auth()->id();
        
        // Find the clip with user relationship
        $clip = Clip::with('user')->findOrFail($id);
        
        // Check if user already liked this clip
        $existingLike = Like::where('user_id', $userId)
            ->where('clip_id', $clip->id)
            ->first();
            
        if ($existingLike) {
            // Unlike: Remove the like
            $existingLike->delete();
            $clip->decrement('likes_count');
            $message = 'Clip unliked';
            $isLiked = false;
        } else {
            // Like: Add the like
            $like = Like::create([
                'user_id' => $userId,
                'clip_id' => $clip->id,
            ]);
            $clip->increment('likes_count');
            
            // Send notification to clip owner (if not liking own clip)
            if ($clip->user_id !== $userId && $clip->user) {
                try {
                    $clip->user->notify(new \App\Notifications\PostLiked($like));
                } catch (\Exception $e) {
                    \Log::error('Failed to send like notification: ' . $e->getMessage());
                }
            }
            
            $message = 'Clip liked';
            $isLiked = true;
        }
        
        return response()->json([
            'message' => $message,
            'likes_count' => $clip->fresh()->likes_count,
            'is_liked' => $isLiked
        ]);
    }

    /**
     * Get comments for a post (now works with clips)
     */
    public function getComments($id): JsonResponse
    {
        // Find the clip
        $clip = Clip::findOrFail($id);
        
        $comments = \App\Models\Comment::with(['user:id,name,profile_photo'])
            ->where('clip_id', $clip->id)
            ->orderByDesc('created_at')
            ->get();
            
        return response()->json($comments);
    }

    /**
     * Add a comment to a post (now works with clips)
     */
    public function addComment(Request $request, $id): JsonResponse
    {
        // Find the clip with user relationship
        $clip = Clip::with('user')->findOrFail($id);
        
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = \App\Models\Comment::create([
            'user_id' => auth()->id(),
            'clip_id' => $clip->id,
            'body' => $validated['content'],
        ]);

        // Increment comment count
        $clip->increment('comments_count');

        // Send notification to clip owner (if not commenting on own clip)
        if ($clip->user_id !== auth()->id() && $clip->user) {
            try {
                $clip->user->notify(new \App\Notifications\PostCommented($comment));
            } catch (\Exception $e) {
                \Log::error('Failed to send comment notification: ' . $e->getMessage());
            }
        }

        // Load user relationship
        $comment->load(['user:id,name,profile_photo']);

        return response()->json($comment, 201);
    }

    /**
     * Update a comment (now works with clip comments)
     */
    public function updateComment(Request $request, $commentId): JsonResponse
    {
        $comment = \App\Models\Comment::findOrFail($commentId);
        
        // Check if user owns the comment
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update($validated);
        $comment->load(['user:id,name,profile_photo']);

        return response()->json($comment);
    }

    /**
     * Delete a comment (now works with clip comments)
     */
    public function deleteComment($commentId): JsonResponse
    {
        $comment = \App\Models\Comment::findOrFail($commentId);
        $currentUser = auth()->user();
        
        // Check if user owns the comment or is an admin
        if ($comment->user_id !== $currentUser->id && $currentUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. You can only delete your own comments.'], 403);
        }

        $clip = $comment->clip;
        $comment->delete();
        
        // Decrement comment count
        if ($clip) {
            $clip->decrement('comments_count');
        }

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    /**
     * Increment view count for a post/clip
     */
    public function incrementView($id): JsonResponse
    {
        // Find the clip
        $clip = Clip::findOrFail($id);
        
        // Increment views_count
        $clip->increment('views_count');
        
        return response()->json([
            'message' => 'View counted',
            'views_count' => $clip->fresh()->views_count
        ]);
    }
}
