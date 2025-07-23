<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run contract reminders daily at 8:00 AM
        $schedule->command('contract:send-reminders')
            ->dailyAt('08:00')
            ->timezone('America/Los_Angeles')
            ->emailOutputOnFailure('admin@example.com')
            ->description('Send contract report reminder emails');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
