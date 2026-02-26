<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            // Load approvals routes FIRST so domain-scoped routes take priority
            Route::middleware('web')
                ->group(base_path('routes/approvals.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Redirect unauthenticated users to the correct login page based on domain
        $middleware->redirectGuestsTo(function ($request) {
            $approvalsDomain = env('APPROVALS_DOMAIN', 'approvals.caclv.org');
            if ($request->getHost() === $approvalsDomain) {
                return route('mobile.login');
            }
            return route('login');
        });
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

        // Sync deposit dates from Sage Intacct back to DonorPerfect every 4 hours
        $schedule->command('intacct:sync-deposit-dates')
            ->everyFourHours()
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
