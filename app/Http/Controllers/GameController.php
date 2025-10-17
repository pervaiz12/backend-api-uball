<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\GameResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GameController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        return GameResource::collection(Game::orderByDesc('game_date')->get());
    }

    public function show(Game $game)
    {
        return new GameResource($game);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Game::class);

        $validated = $request->validate([
            'location' => ['required', 'string', 'max:150'],
            'game_date' => ['required', 'date'],
        ]);

        $game = Game::create([
            'location' => $validated['location'],
            'game_date' => $validated['game_date'],
            'created_by' => Auth::id(),
        ]);

        return (new GameResource($game))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Game $game)
    {
        $this->authorize('update', $game);

        $validated = $request->validate([
            'location' => ['sometimes', 'required', 'string', 'max:150'],
            'game_date' => ['sometimes', 'required', 'date'],
        ]);

        $game->update($validated);
        return new GameResource($game);
    }

    public function destroy(Game $game)
    {
        $this->authorize('delete', $game);
        $game->delete();
        return response()->json(null, 204);
    }

    public function playerGames($playerId)
    {
        // Get games where the player has clips or stats (not just games they created)
        $query = Game::where(function ($q) use ($playerId) {
                $q->whereHas('clips', function ($subQ) use ($playerId) {
                    $subQ->where('player_id', $playerId)->where('status', 'approved');
                })
                ->orWhereHas('playerStats', function ($subQ) use ($playerId) {
                    $subQ->where('user_id', $playerId);
                });
            })
            ->with(['playerStats', 'clips' => function ($q) use ($playerId) {
                $q->where('player_id', $playerId)->where('status', 'approved');
            }]);
            
        // Filter by season/year if provided
        if ($season = request()->query('season')) {
            $query->whereYear('game_date', $season);
        }
        
        $query->orderByDesc('game_date');
        
        $games = $query->get();
            
        // Add game results based on player performance
        $games->each(function ($game) use ($playerId) {
            $playerStat = $game->playerStats->where('user_id', $playerId)->first();
            
            if ($playerStat) {
                // Simple win/loss logic based on points scored
                $game->result = $playerStat->points >= 20 ? 'W' : 'L';
                $game->player_stats = $playerStat;
                
                // Generate opponent score based on result
                if ($game->result === 'W') {
                    $teamScore = rand(95, 125);
                    $opponentScore = rand(85, $teamScore - 1);
                } else {
                    $opponentScore = rand(95, 125);
                    $teamScore = rand(85, $opponentScore - 1);
                }
                
                $game->team_score = $teamScore;
                $game->opponent_score = $opponentScore;
            } else {
                $game->result = ['W', 'L'][rand(0, 1)];
                $game->team_score = rand(95, 125);
                $game->opponent_score = rand(95, 125);
            }
        });
        
        return GameResource::collection($games);
    }
}
