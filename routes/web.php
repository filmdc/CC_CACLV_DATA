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
    Route::get('household-management', App\Livewire\HouseholdManagement::class)->name('household.management');
    Route::get('admin-panel', App\Livewire\AdminPanel::class)->name('admin.panel');
});

require __DIR__.'/auth.php';
