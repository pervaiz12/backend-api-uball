<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

// Register auth endpoint for private channels at /api/broadcasting/auth
Broadcast::routes(['middleware' => ['auth:sanctum'], 'prefix' => 'api']);

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
