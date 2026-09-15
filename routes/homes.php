<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CACLV Housing Portal Routes
|--------------------------------------------------------------------------
|
| Routes for homes.caclv.org — a task-first, CALV-branded front-end for the
| housing project manager. Shares authentication with the main app via the
| .caclv.org session cookie (see SESSION_DOMAIN). Same database, same models
| as CAP TRACK — this is a separate front door, not a separate application.
|
*/

Route::domain(env('HOMES_DOMAIN', 'homes.caclv.org'))->group(function () {

    // Guest routes (login)
    Route::middleware('guest')->group(function () {
        Route::get('/login', App\Livewire\Homes\Login::class)->name('homes.login');
    });

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Task-first home
        Route::get('/', App\Livewire\Homes\Today::class)->name('homes.today');

        // Calls & Intakes (the communications log, redesigned)
        Route::get('/calls', App\Livewire\Homes\CallsIntakes::class)
            ->middleware('permission:communications_log.view')
            ->name('homes.calls');

        Route::get('/calls/{log}', App\Livewire\Homes\CallDetail::class)
            ->middleware('permission:communications_log.view')
            ->name('homes.call');

        // Projects (active rehab jobs)
        Route::get('/projects', App\Livewire\Homes\Projects::class)
            ->middleware('permission:projects.view')
            ->name('homes.projects');

        Route::get('/projects/{job}', App\Livewire\Homes\ProjectDetail::class)
            ->middleware('permission:projects.view')
            ->name('homes.project');

        // Reports (canned, read-only)
        Route::get('/reports', App\Livewire\Homes\Reports::class)
            ->middleware('permission:housing.reports.view')
            ->name('homes.reports');

        // Settings — hub + the five portal-native admin surfaces
        Route::get('/settings', App\Livewire\Homes\SettingsHub::class)
            ->name('homes.settings');

        // No permission middleware: CheckPermission has no admin bypass, and
        // this surface authorizes admin-OR-permission inside mount().
        Route::get('/settings/dashboard', App\Livewire\Homes\Settings\DashboardWidgets::class)
            ->name('homes.settings.dashboard');

        Route::get('/settings/fund-codes', App\Livewire\Homes\Settings\FundCodes::class)
            ->middleware('permission:fund_codes.view')
            ->name('homes.settings.fund-codes');

        Route::get('/settings/counties', App\Livewire\Homes\Settings\Counties::class)
            ->middleware('permission:fund_codes.view')
            ->name('homes.settings.counties');

        // HUD HOME income limits — the ceiling housing intakes qualify
        // against. Gated on the poverty-guidelines.* permissions the
        // main app's guideline admin already uses (note the hyphen:
        // that is how the permission rows are actually named).
        Route::get('/settings/income-limits', App\Livewire\Homes\Settings\IncomeLimits::class)
            ->middleware('permission:poverty-guidelines.view')
            ->name('homes.settings.income-limits');

        Route::get('/settings/requirements', App\Livewire\Homes\Settings\RequirementsLibrary::class)
            ->middleware('permission:requirements_library.view')
            ->name('homes.settings.requirements');

        Route::get('/settings/contractors', App\Livewire\Homes\Settings\Contractors::class)
            ->middleware('permission:contractors.view')
            ->name('homes.settings.contractors');

        Route::get('/settings/properties', App\Livewire\Homes\Settings\Properties::class)
            ->middleware('permission:properties.view')
            ->name('homes.settings.properties');

        Route::get('/settings/pdf-templates', App\Livewire\Homes\Settings\PdfTemplates::class)
            ->middleware('permission:pdf_templates.view')
            ->name('homes.settings.pdf-templates');
    });
});
