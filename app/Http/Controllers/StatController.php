<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\PlayerStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PlayerStatResource;

class StatController extends Controller
{
    public function index(Game $game)
    {
        $stats = PlayerStat::where('game_id', $game->id)
            ->with('user:id,name,profile_photo')
            ->orderByDesc('id')
            ->get();
        return PlayerStatResource::collection($stats);
    }

    public function store(Request $request, Game $game)
    {
        $this->authorize('create', Game::class); // staff/admin enforcement

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'points' => ['required', 'integer', 'min:0'],
            'rebounds' => ['required', 'integer', 'min:0'],
            'assists' => ['required', 'integer', 'min:0'],
            'steals' => ['required', 'integer', 'min:0'],
            'blocks' => ['required', 'integer', 'min:0'],
            'fg_made' => ['required', 'integer', 'min:0'],
            'fg_attempts' => ['required', 'integer', 'min:0'],
            'three_made' => ['required', 'integer', 'min:0'],
            'three_attempts' => ['required', 'integer', 'min:0'],
            'minutes_played' => ['required', 'integer', 'min:0'],
        ]);

        $stat = PlayerStat::create(array_merge($validated, ['game_id' => $game->id]));
        return (new PlayerStatResource($stat->load('user:id,name,profile_photo')))
            ->response()
            ->setStatusCode(201);
    }

    public function lastTen()
    {
        $user = Auth::user();
        $stats = PlayerStat::where('user_id', $user->id)
            ->with('user:id,name,profile_photo')
            ->latest()
            ->take(10)
            ->get();
        return PlayerStatResource::collection($stats);
    }

    public function season()
    {
        $user = Auth::user();
        $agg = PlayerStat::where('user_id', $user->id)
            ->selectRaw('SUM(points) as points, SUM(rebounds) as rebounds, SUM(assists) as assists, SUM(steals) as steals, SUM(blocks) as blocks, SUM(fg_made) as fg_made, SUM(fg_attempts) as fg_attempts, SUM(three_made) as three_made, SUM(three_attempts) as three_attempts, SUM(minutes_played) as minutes_played, COUNT(*) as games')
            ->first();
        return $agg ?? [];
    }

    public function destroy(PlayerStat $stat)
    {
        // Staff or Admin can delete any stat line
        $this->authorize('create', Game::class); // reuse same staff/admin gate
        $stat->delete();
        return response()->json(null, 204);
    }
}
