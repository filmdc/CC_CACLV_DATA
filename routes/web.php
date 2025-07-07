<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
    Route::get('funder-management', App\Livewire\FunderManagement::class)->name('funder.management');
    Route::get('work-plan-management', App\Livewire\WorkPlanManagement::class)->name('work-plan.management');
    Route::get('work-plan-goal-management', App\Livewire\WorkPlanGoalManagement::class)->name('work-plan-goal.management');
    Route::get('role-permission-management', App\Livewire\RolePermissionManagement::class)->name('role-permission.management');
    Route::get('user-management', App\Livewire\UserManagement::class)->name('user.management');
    Route::get('poverty-guideline-management', App\Livewire\PovertyGuidelineManagement::class)->name('poverty-guideline.management');
    Route::get('reference-data-management', App\Livewire\ReferenceDataManagement::class)->name('reference-data.management');
    Route::get('reference-data-set-management', App\Livewire\ReferenceDataSetManagement::class)->name('reference-data-set.management');
    Route::get('enrollment-management', App\Livewire\EnrollmentManagement::class)->name('enrollment.management');
    Route::get('participant-import-export', App\Livewire\ParticipantImportExport::class)->name('participant.import-export');
    Route::get('duplicate-resolution', App\Livewire\DuplicateResolutionManagement::class)->name('duplicate.resolution');
    Route::get('admin-panel', App\Livewire\AdminPanel::class)->name('admin.panel');
    Route::get('reports', App\Livewire\ReportingManagement::class)->name('reports');
    
    // Secure download route for import/export files
    Route::get('secure-download/{token}', function ($token) {
        $downloadData = Cache::get('download_' . $token);
        
        if (!$downloadData) {
            abort(404, 'Download link expired or invalid');
        }
        
        // Check if expired
        if (now()->timestamp > $downloadData['expires_at']) {
            Cache::forget('download_' . $token);
            abort(404, 'Download link expired');
        }
        
        try {
            // Get file content
            $content = Storage::disk('local')->get($downloadData['file_path']);
            
            // Clean up
            Cache::forget('download_' . $token);
            Storage::disk('local')->delete($downloadData['file_path']);
            
            return response($content)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $downloadData['filename'] . '"')
                ->header('Cache-Control', 'no-cache, must-revalidate');
                
        } catch (\Exception $e) {
            abort(500, 'Download failed: ' . $e->getMessage());
        }
    })->name('secure-download')->middleware(['auth']);
    
    // Housing Module Routes
    Route::get('property-management', App\Livewire\PropertyManagement::class)->name('property.management');
    Route::get('contractor-management', App\Livewire\ContractorManagement::class)->name('contractor.management');
    Route::get('housing-job-management', App\Livewire\HousingJobManagement::class)->name('housing-job.management');
});

require __DIR__.'/auth.php';
