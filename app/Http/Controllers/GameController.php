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
}
