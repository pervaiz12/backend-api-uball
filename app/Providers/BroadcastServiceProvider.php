<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register broadcasting auth endpoint at /api/broadcasting/auth guarded by Sanctum
        Broadcast::routes(['middleware' => ['auth:sanctum'], 'prefix' => 'api']);

        // Load channel authorization callbacks
        require base_path('routes/channels.php');
    }
}
