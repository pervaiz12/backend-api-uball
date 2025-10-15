<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostsController extends Controller
{
    /**
     * Get paginated posts for the feed
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 50) : 10;
        $currentUserId = auth()->id();

        $posts = Post::with(['user:id,name,profile_photo,role,is_official,city'])
            ->withCount(['likes', 'comments'])
            ->recent()
            ->paginate($perPage);

        // Add is_liked_by_user flag for each post
        $posts->getCollection()->transform(function ($post) use ($currentUserId) {
            $post->is_liked_by_user = $post->isLikedBy($currentUserId);
            // Update counts from relationships
            $post->likes_count = $post->likes_count;
            $post->comments_count = $post->comments_count;
            return $post;
        });

        return response()->json([
            'data' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'has_more' => $posts->hasMorePages(),
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
     * Like/unlike a post (toggle)
     */
    public function toggleLike(Post $post): JsonResponse
    {
        $userId = auth()->id();
        
        // Check if user already liked this post
        $existingLike = PostLike::where('user_id', $userId)
            ->where('post_id', $post->id)
            ->first();
            
        if ($existingLike) {
            // Unlike: Remove the like
            $existingLike->delete();
            $post->decrement('likes_count');
            $message = 'Post unliked';
            $isLiked = false;
        } else {
            // Like: Add the like
            PostLike::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');
            $message = 'Post liked';
            $isLiked = true;
        }
        
        return response()->json([
            'message' => $message,
            'likes_count' => $post->fresh()->likes_count,
            'is_liked' => $isLiked
        ]);
    }

    /**
     * Get comments for a post
     */
    public function getComments(Post $post): JsonResponse
    {
        $comments = PostComment::with(['user:id,name,profile_photo'])
            ->where('post_id', $post->id)
            ->recent()
            ->get();
            
        return response()->json($comments);
    }

    /**
     * Add a comment to a post
     */
    public function addComment(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = PostComment::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
            'content' => $validated['content'],
        ]);

        // Increment comment count
        $post->increment('comments_count');

        // Load user relationship
        $comment->load(['user:id,name,profile_photo']);

        return response()->json($comment, 201);
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, PostComment $comment): JsonResponse
    {
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
     * Delete a comment
     */
    public function deleteComment(PostComment $comment): JsonResponse
    {
        $currentUser = auth()->user();
        
        // Check if user owns the comment or is an admin
        if ($comment->user_id !== $currentUser->id && $currentUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. You can only delete your own comments.'], 403);
        }

        $post = $comment->post;
        $comment->delete();
        
        // Decrement comment count
        $post->decrement('comments_count');

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
