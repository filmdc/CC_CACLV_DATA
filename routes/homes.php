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

        Route::get('/settings/fund-codes', App\Livewire\Homes\Settings\FundCodes::class)
            ->middleware('permission:fund_codes.view')
            ->name('homes.settings.fund-codes');

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
