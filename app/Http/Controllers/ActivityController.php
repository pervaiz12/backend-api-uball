<?php

namespace App\Http\Controllers;

use App\Models\Clip;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        // Require auth
        $user = Auth::user();
        abort_unless($user, 401);

        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 50) : 20;

        // Simple recent activity from clips and games
        $recentClips = Clip::with(['user:id,name'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(function ($clip) {
                return [
                    'id' => 'clip-'.$clip->id,
                    'type' => 'upload',
                    'description' => 'New clip uploaded'.($clip->description ? ': '.$clip->description : ''),
                    'user' => optional($clip->user)->name,
                    'timestamp' => optional($clip->created_at)->toDateTimeString(),
                ];
            });

        $recentGames = Game::orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(function ($game) {
                return [
                    'id' => 'game-'.$game->id,
                    'type' => 'stat',
                    'description' => 'Game scheduled at '.$game->location,
                    'user' => 'System',
                    'timestamp' => optional($game->created_at)->toDateTimeString(),
                ];
            });

        $merged = $recentClips->concat($recentGames)
            ->sortByDesc('timestamp')
            ->values()
            ->take($limit);

        return response()->json($merged);
    }
}
