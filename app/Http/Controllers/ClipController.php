<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use App\Models\PlayerStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Http\Resources\ClipResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\ClipUploadRequest;
use App\Http\Requests\ClipUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PlayerTaggedInClip;
use App\Notifications\NewClipNotification;
use App\Notifications\ClipUploadedForPlayerNotification;
use Illuminate\Support\Facades\Gate;
use App\Events\NewClipUploaded;
use App\Services\VideoConversionService;

class ClipController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 50) : 15;

        // Select only necessary columns from clips table
        $query = Clip::select([
            'id', 'user_id', 'game_id', 'player_id', 'video_url', 'thumbnail_url',
            'title', 'description', 'tags', 'status', 'views_count', 'likes_count',
            'comments_count', 'created_at', 'updated_at'
        ])
            ->with([
                'user:id,name,profile_photo',
                'game:id,location,game_date',
                'player:id,name,profile_photo'
            ])
            ->where('status', 'approved') // Only show approved clips
            ->orderByDesc('id');
            
        if ($status = request()->query('status')) {
            $query->where('status', $status);
        }
        if ($playerId = request()->query('player_id')) {
            $query->where('player_id', (int) $playerId);
        }
        if ($search = request()->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn('clips', 'title')) {
                    $q->orWhere('title', 'like', "%{$search}%");
                }
                $q->orWhereHas('player', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%");
                });
            });
        }
        // Filter by tag (for category filtering)
        if ($tag = request()->query('tag')) {
            $query->whereJsonContains('tags', $tag);
        }
        
        $clips = $query->paginate($perPage)->appends(request()->query());
        return ClipResource::collection($clips);
    }

    /**
     * Get all unique tags from approved clips
     */
    public function getTags()
    {
        $clips = Clip::where('status', 'approved')
            ->whereNotNull('tags')
            ->get(['tags']);
        
        $allTags = [];
        foreach ($clips as $clip) {
            if (is_array($clip->tags)) {
                $allTags = array_merge($allTags, $clip->tags);
            }
        }
        
        $uniqueTags = array_unique($allTags);
        sort($uniqueTags);
        
        return response()->json(['tags' => array_values($uniqueTags)]);
    }

    public function upload(ClipUploadRequest $request)
    {
        // Set PHP runtime limits for large file uploads
        ini_set('upload_max_filesize', '512M');
        ini_set('post_max_size', '512M');
        ini_set('max_execution_time', '600'); // Increased for video conversion
        ini_set('max_input_time', '600');
        ini_set('memory_limit', '512M'); // Increased for video processing
        
        $this->authorize('create', Clip::class);

        $validated = $request->validated();

        // Check for low-level PHP upload errors and return a helpful message
        if ($request->hasFile('video') && !$request->file('video')->isValid()) {
            $maxUpload = ini_get('upload_max_filesize');
            $maxPost = ini_get('post_max_size');
            return response()->json([
                'message' => 'The video failed to upload. Please ensure the file is <= 500MB and server limits are sufficient.',
                'errors' => [
                    'video' => [
                        'Upload may have exceeded server limits. Current PHP limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost
                    ]
                ]
            ], 422);
        }

        $path = $request->file('video')->store('clips', 'public');

        // Convert video to browser-compatible format
        $videoConversionService = new VideoConversionService();
        $convertedPath = $videoConversionService->convertToBrowserCompatible($path);
        
        // Use converted video if conversion was successful, otherwise use original
        if ($convertedPath) {
            \Log::info("Video converted successfully: {$convertedPath}");
            $finalVideoPath = $convertedPath;
        } else {
            \Log::warning("Video conversion failed, using original: {$path}");
            $finalVideoPath = $path;
        }

        // Handle thumbnail upload or auto-generate from video
        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            // User uploaded custom thumbnail
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
            $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
        } else {
            // Auto-generate thumbnail from video
            $thumbnailUrl = $this->generateVideoThumbnail($path);
        }

        // Calculate percentages if stats are provided
        $fgPercentage = null;
        $threePtPercentage = null;
        if ($request->filled(['fg_made', 'fg_attempts'])) {
            $fgMade = (int) $request->input('fg_made');
            $fgAttempts = (int) $request->input('fg_attempts');
            if ($fgAttempts > 0) {
                $fgPercentage = ($fgMade / $fgAttempts) * 100;
            }
        }
        if ($request->filled(['three_made', 'three_attempts'])) {
            $threeMade = (int) $request->input('three_made');
            $threeAttempts = (int) $request->input('three_attempts');
            if ($threeAttempts > 0) {
                $threePtPercentage = ($threeMade / $threeAttempts) * 100;
            }
        }

        // Generate default title if not provided
        $defaultTitle = $request->input('title') ?: 'Basketball Highlight #' . time();
        
        // Ensure thumbnail URL is set (use default if auto-generation failed)
        if (!$thumbnailUrl) {
            $thumbnailUrl = '/image-1-12.png'; // Default basketball thumbnail
        }

        $data = [
            'user_id' => Auth::id(),
            'game_id' => $request->input('gameId') ?: $request->input('game_id'),
            'video_url' => Storage::disk('public')->url($finalVideoPath),
            'external_video_url' => $request->input('videoUrl') ?: null,
            'thumbnail_url' => $thumbnailUrl,
            'title' => $defaultTitle,
            'description' => $request->input('description') ?: 'Basketball video highlight',
            'tags' => $request->input('tags') ? json_decode($request->input('tags'), true) : ['Highlight'],
            'team_name' => $request->input('teamName') ?: null,
            'opponent_team' => $request->input('opponentTeam') ?: null,
            'game_result' => $request->input('gameResult') ?: null,
            'team_score' => $request->input('teamScore') ? (int) $request->input('teamScore') : null,
            'opponent_score' => $request->input('opponentScore') ? (int) $request->input('opponentScore') : null,
            'fg_percentage' => $fgPercentage,
            'three_pt_percentage' => $threePtPercentage,
            'four_pt_percentage' => 0.0, // Not implemented yet
            'visibility' => $request->input('visibility', 'public'),
            'show_in_trending' => $request->boolean('showInTrending'),
            'show_in_profile' => $request->boolean('showInProfile', true),
            'feature_on_dashboard' => $request->boolean('featureOnDashboard'),
            'season' => $request->input('season', '2024'),
            // Initialize counters
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            // Auto-approve if uploader is admin; otherwise pending
            'status' => Gate::allows('is-admin') ? 'approved' : 'pending',
        ];
        if (Schema::hasColumn('clips', 'player_id')) {
            $playerId = $request->input('playerId') ?: $request->input('player_id');
            // Handle 'none' value from frontend
            $data['player_id'] = ($playerId && $playerId !== 'none') ? (int) $playerId : null;
        }
        if (Schema::hasColumn('clips', 'duration')) {
            $data['duration'] = $request->input('duration') ? (int) $request->input('duration') : null;
        }
        $clip = Clip::create($data);
        $clip->load(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo']);

        // If a player is selected, create a PlayerStat entry (use zero defaults when stat fields are not provided)
        $playerIdForStats = $request->input('playerId') ?: $request->input('player_id');
        if ($playerIdForStats && $playerIdForStats !== 'none') {
            PlayerStat::create([
                'game_id' => $request->input('gameId') ?: $request->input('game_id'),
                'user_id' => (int) $playerIdForStats,
                'points' => (int) ($request->input('points') ?? 0),
                'rebounds' => (int) ($request->input('rebounds') ?? 0),
                'assists' => (int) ($request->input('assists') ?? 0),
                'steals' => (int) ($request->input('steals') ?? 0),
                'blocks' => (int) ($request->input('blocks') ?? 0),
                'fg_made' => (int) ($request->input('fg_made') ?? 0),
                'fg_attempts' => (int) ($request->input('fg_attempts') ?? 0),
                'three_made' => (int) ($request->input('three_made') ?? 0),
                'three_attempts' => (int) ($request->input('three_attempts') ?? 0),
                'minutes_played' => (int) ($request->input('minutes_played') ?? 0),
            ]);

            // Notify followers of the tagged player
            $playerId = (int) $playerIdForStats;
            $player = User::find($playerId);
            if ($player) {
                // First, notify the player that a clip was uploaded for them
                $uploader = User::find(Auth::id());
                if ($uploader && $clip->status === 'approved') {
                    $player->notify(new ClipUploadedForPlayerNotification(
                        clipId: $clip->id,
                        uploaderId: $uploader->id,
                        uploaderName: $uploader->name,
                        clipTitle: $clip->title ?? 'New Basketball Clip',
                        thumbnailUrl: $clip->thumbnail_url
                    ));
                }
                
                // Then notify followers
                $followerIds = $player->followers()->pluck('users.id');
                if ($followerIds->count() > 0) {
                    $followers = User::whereIn('id', $followerIds)->get();
                    
                    // Send tagged notification
                    Notification::send($followers, new PlayerTaggedInClip(
                        clipId: $clip->id,
                        playerId: $player->id,
                        playerName: $player->name,
                        description: $clip->description
                    ));
                    
                    // If clip is auto-approved (admin upload), send push notification and broadcast real-time event
                    if ($clip->status === 'approved') {
                        Notification::send($followers, new NewClipNotification(
                            clipId: $clip->id,
                            playerId: $player->id,
                            playerName: $player->name,
                            clipTitle: $clip->title ?? 'New Basketball Clip',
                            thumbnailUrl: $clip->thumbnail_url,
                            playerProfilePhoto: $player->profile_photo
                        ));
                        
                        // Broadcast real-time socket notification to all followers
                        broadcast(new NewClipUploaded($clip, $player, $followerIds->toArray()));
                    }
                }
            }
        }
        return (new ClipResource($clip))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ClipUpdateRequest $request, Clip $clip)
    {
        $this->authorize('update', $clip);
        $validated = $request->validated();

        $oldStatus = $clip->status;
        $updates = [];
        if (array_key_exists('status', $validated)) $updates['status'] = $validated['status'];
        if (array_key_exists('description', $validated)) $updates['description'] = $validated['description'];
        if (array_key_exists('player_id', $validated)) $updates['player_id'] = $validated['player_id'];
        
        if (!empty($updates)) {
            $clip->update($updates);
        }
        
        // If status changed from pending to approved, notify followers
        if (isset($updates['status']) && $updates['status'] === 'approved' && $oldStatus !== 'approved') {
            if ($clip->player_id) {
                $player = User::find($clip->player_id);
                if ($player) {
                    $followerIds = $player->followers()->pluck('users.id');
                    if ($followerIds->count() > 0) {
                        $followers = User::whereIn('id', $followerIds)->get();
                        
                        // Send push notification to all followers
                        Notification::send($followers, new NewClipNotification(
                            clipId: $clip->id,
                            playerId: $player->id,
                            playerName: $player->name,
                            clipTitle: $clip->title ?? 'New Basketball Clip',
                            thumbnailUrl: $clip->thumbnail_url,
                            playerProfilePhoto: $player->profile_photo
                        ));
                        
                        // Broadcast real-time socket notification to all followers
                        broadcast(new NewClipUploaded($clip, $player, $followerIds->toArray()));
                    }
                }
            }
        }
        
        $clip->load(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo']);
        return new ClipResource($clip);
    }

    /**
     * Generate thumbnail from video file
     */
    private function generateVideoThumbnail(string $videoPath): ?string
    {
        try {
            // Get the full path to the video file
            $fullVideoPath = Storage::disk('public')->path($videoPath);
            
            // Check if video file exists
            if (!file_exists($fullVideoPath)) {
                \Log::warning("Video file not found for thumbnail generation: {$fullVideoPath}");
                return null;
            }

            // Generate unique thumbnail filename
            $thumbnailName = 'thumb_' . time() . '_' . uniqid() . '.jpg';
            $thumbnailPath = 'thumbnails/' . $thumbnailName;
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);

            // Ensure thumbnails directory exists
            $thumbnailDir = dirname($fullThumbnailPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Try different methods for thumbnail generation
            $success = false;

            // Method 1: Try FFmpeg if available
            if ($this->isFFmpegAvailable()) {
                $success = $this->generateThumbnailWithFFmpeg($fullVideoPath, $fullThumbnailPath);
            }

            // Method 2: Try PHP-FFMpeg if available
            if (!$success && class_exists('\FFMpeg\FFMpeg')) {
                $success = $this->generateThumbnailWithPHPFFmpeg($fullVideoPath, $fullThumbnailPath);
            }

            // Method 3: Fallback to ImageMagick if available
            if (!$success && extension_loaded('imagick')) {
                $success = $this->generateThumbnailWithImageMagick($fullVideoPath, $fullThumbnailPath);
            }

            // Method 4: Create a simple placeholder thumbnail if all else fails
            if (!$success) {
                $success = $this->generatePlaceholderThumbnail($fullThumbnailPath);
            }

            if ($success && file_exists($fullThumbnailPath)) {
                return Storage::disk('public')->url($thumbnailPath);
            }

            \Log::warning("Failed to generate thumbnail for video: {$videoPath}");
            return null;

        } catch (\Exception $e) {
            \Log::error("Error generating video thumbnail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if FFmpeg is available
     */
    private function isFFmpegAvailable(): bool
    {
        $output = [];
        $returnVar = 0;
        exec('ffmpeg -version 2>&1', $output, $returnVar);
        return $returnVar === 0;
    }

    /**
     * Generate thumbnail using FFmpeg command line
     */
    private function generateThumbnailWithFFmpeg(string $videoPath, string $thumbnailPath): bool
    {
        try {
            // Extract frame at 2 seconds (or 10% of video duration)
            $command = sprintf(
                'ffmpeg -i %s -ss 00:00:02 -vframes 1 -vf "scale=320:240:force_original_aspect_ratio=decrease,pad=320:240:(ow-iw)/2:(oh-ih)/2" -y %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($thumbnailPath)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            return $returnVar === 0 && file_exists($thumbnailPath);
        } catch (\Exception $e) {
            \Log::error("FFmpeg thumbnail generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate thumbnail using PHP-FFMpeg library
     */
    private function generateThumbnailWithPHPFFmpeg(string $videoPath, string $thumbnailPath): bool
    {
        try {
            $ffmpeg = \FFMpeg\FFMpeg::create();
            $video = $ffmpeg->open($videoPath);
            
            // Extract frame at 2 seconds
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2));
            $frame->save($thumbnailPath);

            return file_exists($thumbnailPath);
        } catch (\Exception $e) {
            \Log::error("PHP-FFMpeg thumbnail generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate thumbnail using ImageMagick (limited video support)
     */
    private function generateThumbnailWithImageMagick(string $videoPath, string $thumbnailPath): bool
    {
        try {
            $imagick = new \Imagick();
            $imagick->readImage($videoPath . '[2]'); // Read frame at 2 seconds
            $imagick->setImageFormat('jpeg');
            $imagick->thumbnailImage(320, 240, true, true);
            $imagick->writeImage($thumbnailPath);
            $imagick->clear();

            return file_exists($thumbnailPath);
        } catch (\Exception $e) {
            \Log::error("ImageMagick thumbnail generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a placeholder thumbnail when video processing fails
     */
    private function generatePlaceholderThumbnail(string $thumbnailPath): bool
    {
        try {
            // Create a simple 320x240 placeholder image
            $image = imagecreate(320, 240);
            
            // Set colors
            $backgroundColor = imagecolorallocate($image, 30, 30, 30); // Dark gray
            $textColor = imagecolorallocate($image, 255, 255, 255); // White
            $accentColor = imagecolorallocate($image, 225, 6, 0); // UBall red
            
            // Fill background
            imagefill($image, 0, 0, $backgroundColor);
            
            // Add basketball-themed design
            // Draw a simple basketball court outline
            imagerectangle($image, 20, 40, 300, 200, $accentColor);
            imagerectangle($image, 140, 40, 180, 80, $accentColor);
            imagerectangle($image, 140, 160, 180, 200, $accentColor);
            
            // Add center circle
            imageellipse($image, 160, 120, 60, 60, $accentColor);
            
            // Add play icon (triangle)
            $triangle = [
                140, 100,  // Top point
                140, 140,  // Bottom left
                170, 120   // Right point
            ];
            imagefilledpolygon($image, $triangle, 3, $textColor);
            
            // Add text
            $font = 3; // Built-in font
            $text = "VIDEO THUMBNAIL";
            $textWidth = imagefontwidth($font) * strlen($text);
            $x = (320 - $textWidth) / 2;
            imagestring($image, $font, $x, 210, $text, $textColor);
            
            // Save as JPEG
            $success = imagejpeg($image, $thumbnailPath, 85);
            imagedestroy($image);
            
            return $success && file_exists($thumbnailPath);
            
        } catch (\Exception $e) {
            \Log::error("Placeholder thumbnail generation failed: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(Clip $clip)
    {
        $this->authorize('delete', $clip);
        // Attempt to delete stored file if within our storage
        if ($clip->video_url) {
            // video_url like /storage/clips/filename; map to disk path
            $publicPath = parse_url($clip->video_url, PHP_URL_PATH);
            if ($publicPath && str_starts_with($publicPath, '/storage/')) {
                $relative = substr($publicPath, strlen('/storage/'));
                Storage::disk('public')->delete($relative);
            }
        }
        $clip->delete();
        return response()->json(null, 204);
    }

    public function playerClips($playerId)
    {
        $clips = Clip::where('player_id', $playerId)
            ->where('status', 'approved')
            ->with(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo'])
            ->orderByDesc('created_at')
            ->get();
        return ClipResource::collection($clips);
    }

    public function playerHighlights($playerId)
    {
        $clips = Clip::where('player_id', $playerId)
            ->where('status', 'approved')
            ->where(function($query) {
                // Check in tags field (JSON array) for highlight variations
                $query->whereJsonContains('tags', 'highlight')
                      ->orWhereJsonContains('tags', 'HIGHLIGHT')
                      ->orWhereJsonContains('tags', 'Highlight')
                      ->orWhereJsonContains('tags', 'game_highlight')
                      ->orWhereJsonContains('tags', 'GAME_HIGHLIGHT')
                      ->orWhereJsonContains('tags', 'best_play')
                      ->orWhereJsonContains('tags', 'BEST_PLAY');
            })
            ->with(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo'])
            ->orderByDesc('id')
            ->take(4)
            ->get();
        return ClipResource::collection($clips);
    }
}
