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
            // Load domain-scoped portal routes FIRST so host matches win over
            // the catch-all web routes.
            Route::middleware('web')
                ->group(base_path('routes/approvals.php'));

            Route::middleware('web')
                ->group(base_path('routes/homes.php'));

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

            $homesDomain = env('HOMES_DOMAIN', 'homes.caclv.org');
            if ($request->getHost() === $homesDomain) {
                return route('homes.login');
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

        // Sync deposit dates from Sage Intacct back to DonorPerfect every 2 hours
        $schedule->command('intacct:sync-deposit-dates')
            ->everyTwoHours()
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

        // Sync posted Intacct GL expenses to fund utilization daily at 1:00 AM PST
        $schedule->command('intacct:sync-fund-expenses --force')
            ->dailyAt('01:00')
            ->timezone('America/Los_Angeles')
            ->when(function () {
                if (\App\Models\SiteSetting::get('intacct_expense_sync_enabled', '1') !== '1') {
                    return false;
                }
                $setting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

                return $setting && $setting->isConfigured() && $setting->isXmlApi();
            })
            ->description('Pull posted GL expense totals from Sage Intacct into fund utilization');

        // Pull new and changed contracts from the legacy contract database
        // (contracts.caclv.org) nightly at 4:00 AM ET, before staff are working.
        $schedule->command('contracts:sync-legacy --trigger=scheduled')
            ->dailyAt('04:00')
            ->timezone('America/New_York')
            ->withoutOverlapping(30)
            ->when(fn () => config('legacy-import.auto_sync.enabled', true))
            ->description('Import new and changed contracts from the legacy contract database');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
