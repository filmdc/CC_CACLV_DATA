<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('welcome');
})->name('home');

Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

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
    Route::get('participant/{id}', App\Livewire\ParticipantShow::class)->name('participant.show');
    Route::get('household-management', App\Livewire\HouseholdManagement::class)->name('household.management');
    Route::get('program-management', App\Livewire\ProgramManagement::class)->name('program.management');
    Route::get('service-management', App\Livewire\ServiceManagement::class)->name('service.management');
    Route::get('contract-management', App\Livewire\ContractManagement::class)->name('contract.management');
    Route::get('work-plan-management', App\Livewire\WorkPlanManagement::class)->name('work-plan.management');
    Route::get('work-plan-goal-management', App\Livewire\WorkPlanGoalManagement::class)->name('work-plan-goal.management');
    Route::get('role-permission-management', App\Livewire\RolePermissionManagement::class)->name('role-permission.management');
    Route::get('user-management', App\Livewire\UserManagement::class)->name('user.management');
    Route::get('poverty-guideline-management', App\Livewire\PovertyGuidelineManagement::class)->name('poverty-guideline.management');
    Route::get('reference-data-management', App\Livewire\ReferenceDataManagement::class)->name('reference-data.management');
    Route::get('reference-data-set-management', App\Livewire\ReferenceDataSetManagement::class)->name('reference-data-set.management');
    Route::get('enrollment-management', App\Livewire\EnrollmentManagement::class)->name('enrollment.management');
    Route::get('participant-import-export', App\Livewire\ParticipantImportExport::class)->name('participant.import-export');
    Route::get('download/export/{file}', function($file) {
        try {
            $filePath = base64_decode($file);
            
            // Security checks
            if (!$filePath || !str_starts_with($filePath, 'exports/')) {
                abort(403, 'Access denied');
            }
            
            // Check if user has permission
            if (!auth()->user() || !auth()->user()->hasAnyPermission(['participants.export', 'participants.import'])) {
                abort(403, 'Access denied');
            }
            
            // Additional security: ensure no directory traversal
            $filePath = str_replace(['../', '..\\'], '', $filePath);
            
            if (!Storage::disk('local')->exists($filePath)) {
                abort(404, 'File not found');
            }
            
            $filename = basename($filePath);
            $content = Storage::disk('local')->get($filePath);
            
            // Clean up the file after download
            Storage::disk('local')->delete($filePath);
            
            return response($content)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, must-revalidate')
                ->header('X-Content-Type-Options', 'nosniff');
                
        } catch (\Exception $e) {
            \Log::error('Download failed', ['error' => $e->getMessage(), 'file' => $file]);
            abort(500, 'Download failed');
        }
    })->name('download.export');
    Route::get('admin-panel', App\Livewire\AdminPanel::class)->name('admin.panel');
    
    // Housing Module Routes
    Route::get('property-management', App\Livewire\PropertyManagement::class)->name('property.management');
    Route::get('contractor-management', App\Livewire\ContractorManagement::class)->name('contractor.management');
    Route::get('housing-job-management', App\Livewire\HousingJobManagement::class)->name('housing-job.management');
});

require __DIR__.'/auth.php';
