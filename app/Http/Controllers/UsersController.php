<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Notifications\UserFollowedNotification;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('is-admin');

        $perPage = (int) $request->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 50) : 15;

        $query = User::query()
            ->withCount(['playerStats as games_count' => function ($q) {
                $q->select(DB::raw('COUNT(DISTINCT game_id)'));
            }]);
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }
        if ($role = $request->query('role')) {
            // Support comma-separated roles, e.g., admin,staff
            $roles = array_filter(array_map('trim', explode(',', $role)));
            if (count($roles) > 1) {
                $query->whereIn('role', $roles);
            } else {
                $query->where('role', $roles[0]);
            }
        }

        $users = $query->orderByDesc('id')->paginate($perPage)->appends($request->query());
        return UserResource::collection($users);
    }

    /**
     * Public players directory for authenticated users (non-admin).
     */
    public function publicPlayers(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 50) : 15;

        $query = User::query()
            ->where('role', 'player')
            ->select(['id', 'name', 'email', 'profile_photo', 'city', 'home_court', 'role', 'is_official'])
            ->selectSub(function ($q) {
                $q->from('clips')
                  ->selectRaw('COUNT(DISTINCT game_id)')
                  ->whereColumn('clips.player_id', 'users.id')
                  ->whereNotNull('game_id');
            }, 'games_count');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $players = $query->orderByDesc('id')->paginate($perPage)->appends($request->query());
        return UserResource::collection($players);
    }

    /**
     * Get top official players for public display
     */
    public function topOfficialPlayers(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $limit = $limit > 0 ? min($limit, 20) : 10;

        $topPlayers = User::query()
            ->where('role', 'player')
            ->where('is_official', true)
            ->select(['id', 'name', 'email', 'profile_photo', 'city', 'home_court', 'role', 'is_official'])
            ->selectSub(function ($q) {
                $q->from('clips')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('clips.user_id', 'users.id');
            }, 'clips_count')
            ->selectSub(function ($q) {
                $q->from('clips')
                  ->selectRaw('COUNT(DISTINCT game_id)')
                  ->whereColumn('clips.user_id', 'users.id')
                  ->whereNotNull('game_id');
            }, 'games_count')
            ->selectSub(function ($q) {
                $q->from('player_stats')
                  ->selectRaw('AVG(points)')
                  ->whereColumn('player_stats.user_id', 'users.id');
            }, 'avg_points')
            ->orderByDesc('clips_count')
            ->orderByDesc('games_count')
            ->limit($limit)
            ->get();

        return UserResource::collection($topPlayers);
    }

    /**
     * Get suggested players for user to follow
     */
    public function suggestedPlayers(Request $request)
    {
        $limit = (int) $request->query('limit', 5);
        $limit = $limit > 0 ? min($limit, 10) : 5;

        $currentUserId = auth()->id();

        // Get players that current user is not following
        $suggestedPlayers = User::query()
            ->where('role', 'player')
            ->where('id', '!=', $currentUserId)
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                      ->from('followers')
                      ->whereColumn('followers.following_id', 'users.id')
                      ->where('followers.follower_id', $currentUserId);
            })
            ->select(['id', 'name', 'email', 'profile_photo', 'city', 'home_court', 'role', 'is_official'])
            ->selectSub(function ($q) {
                $q->from('clips')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('clips.user_id', 'users.id');
            }, 'clips_count')
            ->selectSub(function ($q) {
                $q->from('followers')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('followers.following_id', 'users.id');
            }, 'followers_count')
            ->orderByDesc('clips_count')
            ->orderByDesc('followers_count')
            ->limit($limit)
            ->get();

        return UserResource::collection($suggestedPlayers);
    }

    /**
     * Follow a player
     */
    public function followPlayer(Request $request, $playerId)
    {
        $currentUserId = auth()->id();
        
        // Check if player exists
        $player = User::where('role', 'player')->findOrFail($playerId);
        
        // Check if already following
        $existingFollow = DB::table('followers')
            ->where('follower_id', $currentUserId)
            ->where('following_id', $playerId)
            ->first();
            
        if ($existingFollow) {
            return response()->json(['message' => 'Already following this player'], 400);
        }
        
        // Create follow relationship
        DB::table('followers')->insert([
            'follower_id' => $currentUserId,
            'following_id' => $playerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Send follow notification
        $follower = auth()->user();
        $player->notify(new UserFollowedNotification(
            followerId: $follower->id,
            followerName: $follower->name,
            followerProfilePhoto: $follower->profile_photo
        ));
        
        // Debug logging
        \Log::info('Follow notification sent (UsersController)', [
            'follower_id' => $follower->id,
            'follower_name' => $follower->name,
            'followed_player_id' => $player->id,
            'followed_player_name' => $player->name
        ]);
        
        return response()->json(['message' => 'Successfully followed player']);
    }

    /**
     * Unfollow a player
     */
    public function unfollowPlayer(Request $request, $playerId)
    {
        $currentUserId = auth()->id();
        
        $deleted = DB::table('followers')
            ->where('follower_id', $currentUserId)
            ->where('following_id', $playerId)
            ->delete();
            
        if (!$deleted) {
            return response()->json(['message' => 'Not following this player'], 400);
        }
        
        return response()->json(['message' => 'Successfully unfollowed player']);
    }

    /**
     * Show a single player's public profile (authenticated).
     */
    public function showPublic(User $user)
    {
        if ($user->role !== 'player') {
            abort(404);
        }
        
        // Load all relationship counts
        $user->loadCount([
            'followers',
            'following', 
            'clips',
            'taggedClips',
            'games'
        ]);
        
        // Check if current user is following this player
        $currentUserId = auth()->id();
        $isFollowing = false;
        if ($currentUserId) {
            $isFollowing = DB::table('followers')
                ->where('follower_id', $currentUserId)
                ->where('following_id', $user->id)
                ->exists();
        }
        
        // Compute games_count from distinct game_id in clips where this user is tagged as player
        $gamesCount = DB::table('clips')
            ->where('player_id', $user->id)
            ->whereNotNull('game_id')
            ->distinct('game_id')
            ->count('game_id');
            
        // Use the higher count between actual games created and games from clips
        $totalGamesCount = max($user->games_count, $gamesCount);
        
        // Use tagged clips count for videos (clips where this player is featured)
        $videosCount = $user->tagged_clips_count;
        
        // Calculate player ratings based on stats
        $ratings = $this->calculatePlayerRatings($user->id);
        
        // Dynamically attach computed counts and ratings
        $user->setAttribute('games_count', (int) $totalGamesCount);
        $user->setAttribute('clips_count', (int) $videosCount);
        $user->setAttribute('overall_rating', $ratings['overall']);
        $user->setAttribute('offense_rating', $ratings['offense']);
        $user->setAttribute('defense_rating', $ratings['defense']);
        $user->setAttribute('is_following', $isFollowing);
        
        return new UserResource($user);
    }

    private function calculatePlayerRatings($userId)
    {
        $stats = \App\Models\PlayerStat::where('user_id', $userId)->get();
        
        if ($stats->isEmpty()) {
            // Default ratings for players without stats
            return [
                'overall' => 75,
                'offense' => 70,
                'defense' => 65
            ];
        }

        // Calculate totals
        $totalGames = $stats->count();
        $totalPoints = $stats->sum('points');
        $totalAssists = $stats->sum('assists');
        $totalRebounds = $stats->sum('rebounds');
        $totalSteals = $stats->sum('steals');
        $totalBlocks = $stats->sum('blocks');
        $totalFgMade = $stats->sum('fg_made');
        $totalFgAttempts = $stats->sum('fg_attempts');
        $totalThreeMade = $stats->sum('three_made');
        $totalThreeAttempts = $stats->sum('three_attempts');

        // Calculate averages
        $ppg = $totalPoints / $totalGames;
        $apg = $totalAssists / $totalGames;
        $rpg = $totalRebounds / $totalGames;
        $spg = $totalSteals / $totalGames;
        $bpg = $totalBlocks / $totalGames;
        $fgPct = $totalFgAttempts > 0 ? ($totalFgMade / $totalFgAttempts) * 100 : 0;
        $threePct = $totalThreeAttempts > 0 ? ($totalThreeMade / $totalThreeAttempts) * 100 : 0;

        // Calculate offense rating (based on scoring and shooting)
        $offenseRating = min(99, max(50, 
            ($ppg * 2.5) + 
            ($apg * 3) + 
            ($fgPct * 0.8) + 
            ($threePct * 0.5)
        ));

        // Calculate defense rating (based on defensive stats)
        $defenseRating = min(99, max(50,
            ($rpg * 4) + 
            ($spg * 8) + 
            ($bpg * 10) + 
            50 // Base defensive rating
        ));

        // Calculate overall rating (weighted average)
        $overallRating = min(99, max(50,
            ($offenseRating * 0.6) + ($defenseRating * 0.4)
        ));

        return [
            'overall' => round($overallRating),
            'offense' => round($offenseRating),
            'defense' => round($defenseRating)
        ];
    }

    public function store(Request $request)
    {
        Gate::authorize('is-admin');

        // Normalize potential alternate field name from frontend
        if ($request->hasFile('photo') && !$request->hasFile('profile_photo')) {
            $request->files->set('profile_photo', $request->file('photo'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'city' => ['nullable', 'string', 'max:255'],
            'home_court' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'role' => ['sometimes', 'in:admin,staff,player'],
        ]);

        $photoUrl = null;
        if ($request->hasFile('profile_photo')) {
            try {
                $path = $request->file('profile_photo')->store('avatars', 'public');
                // Store relative public path (frontend will prefix base URL)
                $photoUrl = 'storage/' . ltrim($path, '/');
            } catch (\Throwable $e) {
                $maxUpload = ini_get('upload_max_filesize');
                $maxPost = ini_get('post_max_size');
                return response()->json([
                    'message' => 'The profile photo failed to upload. Please ensure the image is <= 5MB and server limits are sufficient.',
                    'errors' => [
                        'profile_photo' => [
                            'Upload may have exceeded server limits. Current PHP limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost
                        ]
                    ]
                ], 422);
            }
        }

        $role = $validated['role'] ?? 'player';
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'city' => $validated['city'] ?? null,
            'home_court' => $validated['home_court'] ?? null,
            'profile_photo' => $photoUrl,
            'role' => $role,
        ];
        if ($role === 'player') {
            $data['is_official'] = true;
            $data['official_request'] = 'approved';
        }
        $user = User::create($data);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Allow staff/admin to create player accounts (role forced to player).
     */
    public function storePlayer(Request $request)
    {
        Gate::authorize('is-staff');

        // Normalize potential alternate field name from frontend
        if ($request->hasFile('photo') && !$request->hasFile('profile_photo')) {
            $request->files->set('profile_photo', $request->file('photo'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'city' => ['nullable', 'string', 'max:255'],
            'home_court' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photoUrl = null;
        if ($request->hasFile('profile_photo')) {
            try {
                $path = $request->file('profile_photo')->store('avatars', 'public');
                $photoUrl = 'storage/' . ltrim($path, '/');
            } catch (\Throwable $e) {
                $maxUpload = ini_get('upload_max_filesize');
                $maxPost = ini_get('post_max_size');
                return response()->json([
                    'message' => 'The profile photo failed to upload. Please ensure the image is <= 5MB and server limits are sufficient.',
                    'errors' => [
                        'profile_photo' => [
                            'Upload may have exceeded server limits. Current PHP limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost
                        ]
                    ]
                ], 422);
            }
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'city' => $validated['city'] ?? null,
            'home_court' => $validated['home_court'] ?? null,
            'profile_photo' => $photoUrl,
            'role' => 'player',
            'is_official' => true,
            'official_request' => 'approved',
        ];

        $user = User::create($data);
        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('is-admin');

        // Normalize potential alternate field name from frontend
        if ($request->hasFile('photo') && !$request->hasFile('profile_photo')) {
            $request->files->set('profile_photo', $request->file('photo'));
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'city' => ['nullable', 'string', 'max:255'],
            'home_court' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_official' => ['sometimes', 'boolean'],
        ]);

        // Only provided fields will be updated
        if (array_key_exists('name', $validated)) $user->name = $validated['name'];
        if (array_key_exists('email', $validated)) $user->email = $validated['email'];
        if (!empty($validated['password'])) $user->password = Hash::make($validated['password']);
        if (array_key_exists('city', $validated)) $user->city = $validated['city'];
        if (array_key_exists('home_court', $validated)) $user->home_court = $validated['home_court'];
        if ($request->hasFile('profile_photo')) {
            try {
                $path = $request->file('profile_photo')->store('avatars', 'public');
                // Store relative public path (frontend will prefix base URL)
                $user->profile_photo = 'storage/' . ltrim($path, '/');
            } catch (\Throwable $e) {
                $maxUpload = ini_get('upload_max_filesize');
                $maxPost = ini_get('post_max_size');
                return response()->json([
                    'message' => 'The profile photo failed to upload. Please ensure the image is <= 5MB and server limits are sufficient.',
                    'errors' => [
                        'profile_photo' => [
                            'Upload may have exceeded server limits. Current PHP limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost
                        ]
                    ]
                ], 422);
            }
        }
        if (array_key_exists('is_official', $validated)) $user->is_official = (bool)$validated['is_official'];
        $user->save();

        return new UserResource($user);
    }

    /**
     * Allow staff/admin to delete player accounts only.
     */
    public function destroyPlayer(User $user)
    {
        Gate::authorize('is-staff');
        if ($user->role !== 'player') {
            return response()->json(['message' => 'Only player accounts can be deleted by staff.'], 422);
        }
        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * Allow staff/admin to update player accounts only.
     */
    public function updatePlayer(Request $request, User $user)
    {
        Gate::authorize('is-staff');
        if ($user->role !== 'player') {
            return response()->json(['message' => 'Only player accounts can be updated by staff.'], 422);
        }

        if ($request->hasFile('photo') && !$request->hasFile('profile_photo')) {
            $request->files->set('profile_photo', $request->file('photo'));
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'city' => ['nullable', 'string', 'max:255'],
            'home_court' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (array_key_exists('name', $validated)) $user->name = $validated['name'];
        if (array_key_exists('email', $validated)) $user->email = $validated['email'];
        if (!empty($validated['password'])) $user->password = Hash::make($validated['password']);
        if (array_key_exists('city', $validated)) $user->city = $validated['city'];
        if (array_key_exists('home_court', $validated)) $user->home_court = $validated['home_court'];
        if ($request->hasFile('profile_photo')) {
            try {
                $path = $request->file('profile_photo')->store('avatars', 'public');
                $user->profile_photo = 'storage/' . ltrim($path, '/');
            } catch (\Throwable $e) {
                $maxUpload = ini_get('upload_max_filesize');
                $maxPost = ini_get('post_max_size');
                return response()->json([
                    'message' => 'The profile photo failed to upload. Please ensure the image is <= 5MB and server limits are sufficient.',
                    'errors' => [
                        'profile_photo' => [
                            'Upload may have exceeded server limits. Current PHP limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost
                        ]
                    ]
                ], 422);
            }
        }

        $user->save();
        return new UserResource($user);
    }

    /**
     * Search for players
     */
    public function searchPlayers(Request $request)
    {
        $query = $request->query('q', '');
        $limit = min((int) $request->query('limit', 20), 50);
        
        if (empty(trim($query))) {
            return response()->json([
                'data' => [],
                'message' => 'Search query is required'
            ], 400);
        }
        
        $players = User::where('role', 'player')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('city', 'LIKE', "%{$query}%")
                  ->orWhere('home_court', 'LIKE', "%{$query}%");
            })
            ->select(['id', 'name', 'profile_photo', 'city', 'home_court', 'role', 'is_official'])
            ->selectSub(function ($q) {
                $q->from('followers')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('followers.following_id', 'users.id');
            }, 'followers_count')
            ->selectSub(function ($q) {
                $q->from('clips')
                  ->selectRaw('COUNT(DISTINCT game_id)')
                  ->whereColumn('clips.player_id', 'users.id')
                  ->whereNotNull('game_id');
            }, 'games_count')
            ->selectSub(function ($q) {
                $q->from('clips')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('clips.user_id', 'users.id');
            }, 'clips_count')
            ->orderBy('is_official', 'desc')
            ->orderBy('followers_count', 'desc')
            ->limit($limit)
            ->get();
            
        // Add is_following flag for each player
        $currentUserId = auth()->id();
        $players->each(function ($player) use ($currentUserId) {
            $player->is_following = DB::table('followers')
                ->where('follower_id', $currentUserId)
                ->where('following_id', $player->id)
                ->exists();
        });
        
        return response()->json([
            'data' => $players,
            'query' => $query,
            'count' => $players->count()
        ]);
    }
}
