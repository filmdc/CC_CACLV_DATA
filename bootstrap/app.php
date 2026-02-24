<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            ->timezone('America/New_York')
            ->emailOutputOnFailure('admin@example.com')
            ->description('Send contract report reminder emails');

        // Check contract budget utilization daily at 9:00 AM
        $schedule->command('contract:check-utilization')
            ->dailyAt('09:00')
            ->timezone('America/New_York')
            ->description('Check contract budget utilization and send threshold notifications');

        // Process scheduled test reminders every minute
        $schedule->command('contract:process-scheduled-tests')
            ->everyMinute()
            ->description('Process scheduled test reminder emails');

        // Sync unsynced gifts to Sage Intacct daily at 2:00 AM PST
        $schedule->command('intacct:sync-gifts --unsynced-only')
            ->daily()
            ->at('02:00')
            ->timezone('America/Los_Angeles')
            ->when(function () {
                $setting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

                return $setting && $setting->isConfigured();
            });

        // Sync deposit dates from Sage Intacct back to DonorPerfect daily at 4:00 AM PST
        $schedule->command('intacct:sync-deposit-dates')
            ->daily()
            ->at('04:00')
            ->timezone('America/Los_Angeles')
            ->when(function () {
                $setting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

                return $setting && $setting->isConfigured();
            })
            ->description('Sync deposit dates from Sage Intacct back to DonorPerfect');

        // Discover DonorPerfect codes daily at 3:00 AM PST (for code mapping)
        $schedule->command('dp:discover-codes --days=90')
            ->daily()
            ->at('03:00')
            ->timezone('America/Los_Angeles')
            ->when(function () {
                $setting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

                return $setting && $setting->code_mapping_enabled;
            });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
