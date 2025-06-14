<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Gates for permission checking
        Gate::before(function (User $user, string $ability) {
            // Check if user has the permission through their roles
            return $user->hasPermission($ability) ? true : null;
        });
    }
}
