<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use App\Models\Clip;
use Illuminate\Support\Facades\Gate;

class MetricsController extends Controller
{
    public function index()
    {
        Gate::authorize('is-staff');

        $totalUsers = User::count();
        $games = Game::count();
        $clips = Clip::count();
        $pendingClips = Clip::where('status', 'pending')->count();

        return response()->json([
            'total_users' => $totalUsers,
            'games' => $games,
            'clips' => $clips,
            'pending_clips' => $pendingClips,
        ]);
    }
}
