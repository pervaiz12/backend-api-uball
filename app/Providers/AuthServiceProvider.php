<?php

namespace App\Providers;

use App\Models\Clip;
use App\Models\Game;
use App\Policies\ClipPolicy;
use App\Policies\GamePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Game::class => GamePolicy::class,
        Clip::class => ClipPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('is-admin', fn($user) => $user->role === 'admin');
        Gate::define('is-staff', fn($user) => in_array($user->role, ['staff', 'admin']));
    }
}
