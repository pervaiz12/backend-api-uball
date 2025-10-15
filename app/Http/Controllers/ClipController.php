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
use Illuminate\Support\Facades\Gate;

class ClipController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 50) : 15;

        $query = Clip::with(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo'])
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
        $clips = $query->paginate($perPage)->appends(request()->query());
        return ClipResource::collection($clips);
    }

    public function upload(ClipUploadRequest $request)
    {
        // Set PHP runtime limits for large file uploads
        ini_set('upload_max_filesize', '512M');
        ini_set('post_max_size', '512M');
        ini_set('max_execution_time', '300');
        ini_set('max_input_time', '300');
        ini_set('memory_limit', '256M');
        
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

        $data = [
            'user_id' => Auth::id(),
            'game_id' => $validated['game_id'],
            'video_url' => Storage::disk('public')->url($path),
            'description' => $validated['description'] ?? null,
            // Auto-approve if uploader is admin; otherwise pending
            'status' => Gate::allows('is-admin') ? 'approved' : 'pending',
        ];
        if (Schema::hasColumn('clips', 'player_id')) {
            $data['player_id'] = $request->input('player_id') ?: null;
        }
        if (Schema::hasColumn('clips', 'duration')) {
            $data['duration'] = $request->input('duration') ? (int) $request->input('duration') : null;
        }
        $clip = Clip::create($data);
        $clip->load(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo']);

        // If a player is selected, create a PlayerStat entry (use zero defaults when stat fields are not provided)
        if ($request->filled('player_id')) {
            PlayerStat::create([
                'game_id' => $validated['game_id'],
                'user_id' => (int) $request->input('player_id'),
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
            $playerId = (int) $request->input('player_id');
            $player = User::find($playerId);
            if ($player) {
                $followerIds = $player->followers()->pluck('users.id');
                if ($followerIds->count() > 0) {
                    $followers = User::whereIn('id', $followerIds)->get();
                    Notification::send($followers, new PlayerTaggedInClip(
                        clipId: $clip->id,
                        playerId: $player->id,
                        playerName: $player->name,
                        description: $clip->description
                    ));
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

        $updates = [];
        if (array_key_exists('status', $validated)) $updates['status'] = $validated['status'];
        if (array_key_exists('description', $validated)) $updates['description'] = $validated['description'];
        if (array_key_exists('player_id', $validated)) $updates['player_id'] = $validated['player_id'];
        if (!empty($updates)) {
            $clip->update($updates);
        }
        $clip->load(['user:id,name,profile_photo', 'game:id,location,game_date', 'player:id,name,profile_photo']);
        return new ClipResource($clip);
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
}
