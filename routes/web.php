<?php

use App\Http\Controllers\ProgramManagerUploadController;
use App\Services\CheckImageCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('welcome');
})->name('home');

Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/program-manager-upload', [ProgramManagerUploadController::class, 'create'])->name('program-manager-upload.create');
Route::post('/program-manager-upload', [ProgramManagerUploadController::class, 'store'])->name('program-manager-upload.store');
Route::get('/program-manager-upload/template/{type}', [ProgramManagerUploadController::class, 'downloadTemplate'])
    ->whereIn('type', ['general', 'hmis', 'rx_office'])
    ->name('program-manager-upload.template');

Route::get('dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('admin/program-manager-uploads', App\Livewire\ProgramManagerUploadReview::class)
        ->middleware(['permission:program-manager-uploads.manage'])
        ->name('program-manager-uploads.review');

    Route::get('admin/program-manager-upload/{upload}/download', [ProgramManagerUploadController::class, 'download'])
        ->middleware(['permission:program-manager-uploads.manage'])
        ->name('program-manager-upload.download');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // CAP TRACK Routes

    // Hub Routes - New consolidated pages
    Route::get('participants-households', App\Livewire\ParticipantHouseholdHub::class)->name('participant.household.hub');
    Route::get('programs-services', App\Livewire\ProgramServiceHub::class)->name('program.service.hub');
    Route::get('contracts-hub', App\Livewire\ContractsHub::class)->name('contracts.hub');

    // Participant Routes
    Route::get('participant-intake', App\Livewire\ParticipantIntake::class)->name('participant.intake');
    Route::get('participant-management', App\Livewire\ParticipantManagement::class)->name('participant.management');
    Route::get('participant/{id}', App\Livewire\ParticipantShow::class)->name('participant.show');
    Route::get('household-management', App\Livewire\HouseholdManagement::class)->name('household.management');
    Route::get('household/{id}', App\Livewire\HouseholdShow::class)->name('household.show');
    Route::get('program-management', App\Livewire\ProgramManagement::class)->name('program.management');
    Route::get('program/{id}', App\Livewire\ProgramShow::class)->name('program.show');
    Route::get('service-management', App\Livewire\ServiceManagement::class)->name('service.management');
    Route::get('service/{id}', App\Livewire\ServiceShow::class)->name('service.show');
    Route::get('contract-management', App\Livewire\ContractManagement::class)->name('contract.management');
    Route::get('contract/{id}', App\Livewire\ContractShow::class)->name('contract.show');
    Route::get('contract/{contractId}/document/{mediaId}/preview', [App\Http\Controllers\ContractDocumentController::class, 'preview'])->name('contract.document.preview');
    Route::get('contract/{contractId}/document/{mediaId}/download', [App\Http\Controllers\ContractDocumentController::class, 'download'])->name('contract.document.download');
    Route::get('admin/contract-notification-test', App\Livewire\ContractNotificationTest::class)->name('contract.notification-test')->middleware(['permission:contracts.manage']);
    Route::get('funder-management', App\Livewire\FunderManagement::class)->name('funder.management');
    Route::get('funder/{id}', App\Livewire\FunderShow::class)->name('funder.show');
    Route::get('contract-report-due-dates', App\Livewire\ContractReportDueDateManagement::class)->name('contract-report-due-dates.management');
    Route::get('work-plan-management', App\Livewire\WorkPlanManagement::class)->name('work-plan.management');
    Route::get('work-plan/{id}', App\Livewire\WorkPlanShow::class)->name('work-plan.show');
    Route::get('work-plan-goal-management', App\Livewire\WorkPlanGoalManagement::class)->name('work-plan-goal.management');
    Route::get('work-plan-goal/{id}', App\Livewire\WorkPlanGoalShow::class)->name('work-plan-goal.show');
    Route::get('role-permission-management', App\Livewire\RolePermissionManagement::class)->name('role-permission.management');
    Route::get('role/{id}', App\Livewire\RoleShow::class)->name('role.show');
    Route::get('permission/{id}', App\Livewire\PermissionShow::class)->name('permission.show');
    Route::get('user-management', App\Livewire\UserManagement::class)->name('user.management');
    Route::get('user/{id}', App\Livewire\UserShow::class)->name('user.show');
    Route::get('poverty-guideline-management', App\Livewire\PovertyGuidelineManagement::class)->name('poverty-guideline.management');
    Route::get('reference-data-management', App\Livewire\ReferenceDataManagement::class)->name('reference-data.management');
    Route::get('reference-data-set-management', App\Livewire\ReferenceDataSetManagement::class)->name('reference-data-set.management');
    Route::get('enrollment-management', App\Livewire\EnrollmentManagement::class)->name('enrollment.management');
    Route::get('enrollment/{type}/{id}', App\Livewire\EnrollmentShow::class)->name('enrollment.show');
    Route::get('participant-import-export', App\Livewire\ParticipantImportExport::class)->name('participant.import-export');
    Route::get('duplicate-resolution', App\Livewire\DuplicateResolutionManagement::class)->name('duplicate.resolution');
    Route::get('admin-panel', App\Livewire\AdminPanel::class)->name('admin.panel');
    Route::get('reports', App\Livewire\ReportingManagement::class)->name('reports');

    // DonorPerfect Routes
    Route::get('donor-gifts', App\Livewire\DonorPerfectGiftsFeed::class)
        ->middleware(['permission:donor_gifts.view'])
        ->name('donor-gifts.feed');
    Route::get('donor-gifts/download-export/{type}', function ($type) {
        $exportData = session()->get('export_downloads');

        if (! $exportData || ! isset($exportData['success']) || ! $exportData['success']) {
            return redirect()->route('donor-gifts.feed')->with('error', 'Export data not found or expired.');
        }

        if ($type === 'data') {
            $filePath = $exportData['data_file_path'] ?? null;
            $filename = $exportData['data_filename'] ?? 'export.csv';
        } elseif ($type === 'images') {
            $filePath = $exportData['check_images_path'] ?? null;
            $filename = $exportData['check_images_filename'] ?? 'check_images.zip';
        } else {
            return redirect()->route('donor-gifts.feed')->with('error', 'Invalid download type.');
        }

        if (! $filePath || ! file_exists($filePath)) {
            return redirect()->route('donor-gifts.feed')->with('error', 'File not found.');
        }

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    })
        ->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.download-export');
    Route::get('donor-gifts/configuration', App\Livewire\DonorPerfectConfiguration::class)
        ->middleware(['permission:donor_gifts.configure'])
        ->name('donor-gifts.configuration');
    Route::get('donor-gifts/audit-log', App\Livewire\CheckImageAuditLog::class)
        ->middleware(['role:Administrator'])
        ->name('donor-gifts.audit-log');

    // Sage Intacct Routes
    Route::get('/accounting/sage-intacct/configuration', \App\Livewire\SageIntacctConfiguration::class)
        ->name('sage-intacct.configuration')
        ->middleware(['auth']);

    // Serve cached check images (authenticated, encrypted, auto-convert TIFF to PNG)
    Route::get('donor-gifts/check-image/{filename}', function ($filename) {
        // Verify user is authenticated and has permission
        if (! auth()->check() || ! auth()->user()->hasPermission('donor_gifts.view')) {
            abort(403, 'Unauthorized');
        }

        // Security: prevent directory traversal
        $safeFilename = basename($filename);
        $filePath = 'check_images/'.$safeFilename;

        // Log audit event for file access
        \App\Models\CheckImageAuditLog::logAccess('accessed', $safeFilename, null, [
            'file_type' => pathinfo($safeFilename, PATHINFO_EXTENSION),
        ]);

        // Check if encrypted file exists
        if (! Storage::disk('local')->exists($filePath.'.enc')) {
            abort(404, 'Image not found');
        }

        // Decrypt the file
        $imageCacheService = new CheckImageCacheService;
        $decryptResult = $imageCacheService->decryptFile($filePath);

        if (! $decryptResult['success']) {
            Log::error('Failed to decrypt check image', [
                'filename' => $safeFilename,
                'error' => $decryptResult['error'],
            ]);
            abort(500, 'Failed to decrypt image');
        }

        $fileContents = $decryptResult['contents'];

        // Get metadata for mime type
        $metadataPath = 'check_images/metadata/'.$safeFilename.'.json';
        $metadata = Storage::disk('local')->exists($metadataPath)
            ? json_decode(Storage::disk('local')->get($metadataPath), true)
            : [];

        $mimeType = $metadata['mime_type'] ?? 'application/octet-stream';

        // Create temporary file for processing (needed for TIFF conversion)
        $tempPath = tempnam(sys_get_temp_dir(), 'check_img_');
        file_put_contents($tempPath, $fileContents);

        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        // Check if it's a TIFF file that needs conversion
        if (in_array($extension, ['tif', 'tiff'])) {
            try {
                $imagick = new \Imagick($tempPath);
                $pageCount = $imagick->getNumberImages();

                $baseFilename = pathinfo($safeFilename, PATHINFO_FILENAME);
                $pngFiles = [];

                // Convert each page to PNG
                $imagick = new \Imagick($tempPath);
                $pageNum = 0;
                foreach ($imagick as $page) {
                    $pngFilename = $baseFilename.'_page'.$pageNum.'.png';

                    $page->setImageFormat('png');
                    $page->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $page->setImageCompressionQuality(85);

                    $pngFiles[] = [
                        'filename' => $pngFilename,
                        'url' => route('donor-gifts.check-image-png', ['filename' => $pngFilename]),
                        'imageData' => base64_encode($page->getImageBlob()),
                    ];

                    $pageNum++;
                }

                $imagick->clear();
                $imagick->destroy();

                // Clean up temp file
                unlink($tempPath);

                Log::info('Converted multi-page TIFF', [
                    'tiff_file' => $safeFilename,
                    'pages' => $pageCount,
                    'png_files' => count($pngFiles),
                ]);

                // Return HTML viewer for multi-page check
                return response()->view('check-image-viewer', [
                    'pages' => $pngFiles,
                    'filename' => $safeFilename,
                    'pageCount' => $pageCount,
                ]);

            } catch (\Exception $e) {
                // Clean up temp file on error
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }

                Log::error('Failed to convert TIFF', [
                    'file' => $safeFilename,
                    'error' => $e->getMessage(),
                ]);
                abort(500, 'Failed to convert image for viewing');
            }
        }

        // For non-TIFF files, serve decrypted contents directly
        $response = response($fileContents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);

        // Clean up temp file
        unlink($tempPath);

        return $response;
    })->name('donor-gifts.check-image');

    // Serve standalone PNG check images (encrypted)
    // Note: TIFF-to-PNG conversions are now handled inline with base64 encoding
    Route::get('donor-gifts/check-image-png/{filename}', function ($filename) {
        if (! auth()->check() || ! auth()->user()->hasPermission('donor_gifts.view')) {
            abort(403, 'Unauthorized');
        }

        $safeFilename = basename($filename);
        $filePath = 'check_images/'.$safeFilename;

        // Log audit event for PNG page access
        \App\Models\CheckImageAuditLog::logAccess('accessed', $safeFilename, null, [
            'file_type' => 'png',
        ]);

        // Check if encrypted file exists
        if (! Storage::disk('local')->exists($filePath.'.enc')) {
            abort(404, 'PNG not found');
        }

        // Decrypt the file
        $imageCacheService = new CheckImageCacheService;
        $decryptResult = $imageCacheService->decryptFile($filePath);

        if (! $decryptResult['success']) {
            Log::error('Failed to decrypt PNG check image', [
                'filename' => $safeFilename,
                'error' => $decryptResult['error'],
            ]);
            abort(500, 'Failed to decrypt image');
        }

        $fileContents = $decryptResult['contents'];

        return response($fileContents, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    })->name('donor-gifts.check-image-png');

    // Secure download route for import/export files
    Route::get('secure-download/{token}', function ($token) {
        $downloadData = Cache::get('download_'.$token);

        if (! $downloadData) {
            abort(404, 'Download link expired or invalid');
        }

        // Check if expired
        if (now()->timestamp > $downloadData['expires_at']) {
            Cache::forget('download_'.$token);
            abort(404, 'Download link expired');
        }

        try {
            // Get file content
            $content = Storage::disk('local')->get($downloadData['file_path']);

            // Clean up
            Cache::forget('download_'.$token);
            Storage::disk('local')->delete($downloadData['file_path']);

            return response($content)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="'.$downloadData['filename'].'"')
                ->header('Cache-Control', 'no-cache, must-revalidate');

        } catch (\Exception $e) {
            abort(500, 'Download failed: '.$e->getMessage());
        }
    })->name('secure-download')->middleware(['auth']);

    // Housing Module Routes
    Route::get('property-management', App\Livewire\PropertyManagement::class)->name('property.management');
    Route::get('property/{id}', App\Livewire\PropertyShow::class)->name('property.show');
    Route::get('contractor-management', App\Livewire\ContractorManagement::class)->name('contractor.management');
    Route::get('contractor/{id}', App\Livewire\ContractorShow::class)->name('contractor.show');
    Route::get('housing-job-management', App\Livewire\HousingJobManagement::class)->name('housing-job.management');
    Route::get('housing-job/{id}', App\Livewire\HousingJobShow::class)->name('housing-job.show');

    // Custom storage route for authenticated users (only for specific protected folders)
    Route::get('storage/protected/{path}', function ($path) {
        // Check if user is authenticated
        if (! auth()->check()) {
            abort(403, 'Unauthorized access to file');
        }

        $fullPath = storage_path('app/public/protected/'.$path);

        // Check if file exists
        if (! file_exists($fullPath)) {
            abort(404, 'File not found');
        }

        // Get file info
        $mimeType = mime_content_type($fullPath);
        $fileName = basename($fullPath);

        // Check if download is requested
        $isDownload = request()->has('download') && request()->get('download') == '1';

        // Set appropriate headers based on file type and download preference
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=300', // 5 minutes cache
        ];

        if ($isDownload) {
            $headers['Content-Disposition'] = 'attachment; filename="'.$fileName.'"';
        } else {
            // For inline viewing, set appropriate content disposition based on file type
            if (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'image')) {
                $headers['Content-Disposition'] = 'inline; filename="'.$fileName.'"';
            } else {
                // For other file types, force download for security
                $headers['Content-Disposition'] = 'attachment; filename="'.$fileName.'"';
            }
        }

        // Return file response
        return response()->file($fullPath, $headers);
    })->where('path', '.*')->name('storage.protected');

    // Authenticated storage route for Spatie Media Library files
    // Handles files stored in storage/app/public/{id}/{filename}
    Route::get('storage/authenticated/{path}', function ($path) {
        // Check if user is authenticated
        if (! auth()->check()) {
            abort(403, 'Unauthorized access to file');
        }

        // Path format is: {media_id}/{filename}
        $fullPath = storage_path('app/public/'.$path);

        // Security: Prevent directory traversal attacks
        $realPath = realpath($fullPath);
        $storagePath = realpath(storage_path('app/public'));

        if (! $realPath || ! str_starts_with($realPath, $storagePath)) {
            abort(403, 'Invalid file path');
        }

        // Check if file exists
        if (! file_exists($fullPath)) {
            abort(404, 'File not found');
        }

        // Get file info
        $mimeType = mime_content_type($fullPath);
        $fileName = basename($fullPath);

        // Check if download is requested
        $isDownload = request()->has('download') && request()->get('download') == '1';

        // Set appropriate headers based on file type and download preference
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=300', // 5 minutes cache
        ];

        if ($isDownload) {
            $headers['Content-Disposition'] = 'attachment; filename="'.$fileName.'"';
        } else {
            // For inline viewing, set appropriate content disposition based on file type
            if (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'image')) {
                $headers['Content-Disposition'] = 'inline; filename="'.$fileName.'"';
            } else {
                // For other file types, force download for security
                $headers['Content-Disposition'] = 'attachment; filename="'.$fileName.'"';
            }
        }

        // Return file response
        return response()->file($fullPath, $headers);
    })->where('path', '.*')->name('storage.authenticated');
});

require __DIR__.'/auth.php';
