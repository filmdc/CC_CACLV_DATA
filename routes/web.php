<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    
    // CAP TRACK Routes
    Route::get('participant-intake', App\Livewire\ParticipantIntake::class)->name('participant.intake');
    Route::get('participant-management', App\Livewire\ParticipantManagement::class)->name('participant.management');
    Route::get('household-management', App\Livewire\HouseholdManagement::class)->name('household.management');
    Route::get('program-management', App\Livewire\ProgramManagement::class)->name('program.management');
    Route::get('service-management', App\Livewire\ServiceManagement::class)->name('service.management');
    Route::get('contract-management', App\Livewire\ContractManagement::class)->name('contract.management');
    Route::get('work-plan-management', App\Livewire\WorkPlanManagement::class)->name('work-plan.management');
    Route::get('work-plan-goal-management', App\Livewire\WorkPlanGoalManagement::class)->name('work-plan-goal.management');
    Route::get('role-permission-management', App\Livewire\RolePermissionManagement::class)->name('role-permission.management');
    Route::get('poverty-guideline-management', App\Livewire\PovertyGuidelineManagement::class)->name('poverty-guideline.management');
    Route::get('admin-panel', App\Livewire\AdminPanel::class)->name('admin.panel');
});

require __DIR__.'/auth.php';
