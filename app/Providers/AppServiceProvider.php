<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\ServiceEnrollment;
use App\Models\ProgramEnrollment;
use App\Observers\ServiceEnrollmentObserver;
use App\Observers\ProgramEnrollmentObserver;

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

        // Register Model Observers
        ServiceEnrollment::observe(ServiceEnrollmentObserver::class);
        ProgramEnrollment::observe(ProgramEnrollmentObserver::class);
    }
}
