<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Date;

class TimezoneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set default timezone for all date operations
        date_default_timezone_set(config('app.timezone'));
        
        // Configure Carbon/Date to use the app timezone
        Date::setLocale(config('app.locale'));
    }
}
