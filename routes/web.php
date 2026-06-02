<?php

use App\Http\Controllers\ContractPdfExportController;
use App\Http\Controllers\ProgramManagerUploadController;
use App\Services\CheckImageCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
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
    Volt::route('settings/notifications', 'settings.notifications')->name('settings.notifications');

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
    Route::get('contract/{contract}/export-pdf', ContractPdfExportController::class)->name('contract.export-pdf');
    Route::get('contract/{contractId}/document/{mediaId}/preview', [App\Http\Controllers\ContractDocumentController::class, 'preview'])->name('contract.document.preview');
    Route::get('contract/{contractId}/document/{mediaId}/download', [App\Http\Controllers\ContractDocumentController::class, 'download'])->name('contract.document.download');

    // Generic Media Routes (for milestone documents, etc.)
    Route::get('media/{media}/preview', [App\Http\Controllers\MediaController::class, 'preview'])->name('media.preview');
    Route::get('media/{media}/download', [App\Http\Controllers\MediaController::class, 'download'])->name('media.download');
    Route::get('admin/contract-notification-test', App\Livewire\ContractNotificationTest::class)->name('contract.notification-test')->middleware(['permission:contracts.manage']);
    Route::get('admin/notification-recipients', App\Livewire\NotificationRecipientsManagement::class)->name('notification-recipients.management')->middleware(['permission:contracts.view']);
    Route::get('admin/over-budget-recipients', App\Livewire\OverBudgetRecipientsManagement::class)->name('over-budget-recipients.management')->middleware(['permission:over_budget_recipients.manage']);
    Route::get('admin/legacy-contract-import', App\Livewire\LegacyContractImport::class)->name('legacy.contract.import')->middleware(['permission:contracts.import_legacy']);
    Route::get('funder-management', App\Livewire\FunderManagement::class)->name('funder.management');
    Route::get('funder/{id}', App\Livewire\FunderShow::class)->name('funder.show');
    Route::get('contract-report-due-dates', App\Livewire\ContractReportDueDateManagement::class)->name('contract-report-due-dates.management');
    Route::get('contract-utilization', App\Livewire\ContractUtilizationDashboard::class)->name('contract.utilization')->middleware(['permission:contracts.view']);
    Route::get('work-plan-management', App\Livewire\WorkPlanManagement::class)->name('work-plan.management');
    Route::get('work-plan/{id}', App\Livewire\WorkPlanShow::class)->name('work-plan.show');
    Route::get('work-plan-goal-management', App\Livewire\WorkPlanGoalManagement::class)->name('work-plan-goal.management');
    Route::get('work-plan-goal/{id}', App\Livewire\WorkPlanGoalShow::class)->name('work-plan-goal.show');
    Route::get('role-permission-management', App\Livewire\RolePermissionManagement::class)->name('role-permission.management');
    Route::get('role-permission-export-import', App\Livewire\RolePermissionExportImport::class)->name('role-permission.export-import');
    Route::get('role/{id}', App\Livewire\RoleShow::class)->name('role.show');
    Route::get('permission/{id}', App\Livewire\PermissionShow::class)->name('permission.show');
    // User management — split into list / create / edit / bulk pages
    Route::get('users', App\Livewire\UserManagement::class)->name('users.index');
    Route::get('users/create', App\Livewire\UserCreate::class)->name('users.create');
    Route::get('users/bulk', App\Livewire\UserBulkOperations::class)->name('users.bulk');
    Route::get('users/{id}/edit', App\Livewire\UserEdit::class)->name('users.edit');
    // Legacy route names kept for backward compatibility
    Route::get('user-management', fn () => redirect()->route('users.index'))->name('user.management');
    Route::get('user/{id}', fn ($id) => redirect()->route('users.edit', ['id' => $id]))->name('user.show');
    Route::get('poverty-guideline-management', App\Livewire\PovertyGuidelineManagement::class)->name('poverty-guideline.management');
    Route::get('reference-data-management', App\Livewire\ReferenceDataManagement::class)->name('reference-data.management');
    Route::get('reference-data-set-management', App\Livewire\ReferenceDataSetManagement::class)->name('reference-data-set.management');
    Route::get('enrollment-management', App\Livewire\EnrollmentManagement::class)->name('enrollment.management');
    Route::get('enrollment/{type}/{id}', App\Livewire\EnrollmentShow::class)->name('enrollment.show');
    Route::get('participant-import-export', App\Livewire\ParticipantImportExport::class)->name('participant.import-export');
    Route::get('duplicate-resolution', App\Livewire\DuplicateResolutionManagement::class)->name('duplicate.resolution');
    Route::get('admin-panel', App\Livewire\AdminPanel::class)->name('admin.panel');
    Route::get('reports', App\Livewire\ReportingManagement::class)->name('reports');
    Route::get('communications-log', App\Livewire\CommunicationsLogManagement::class)->name('communications-log.management');
    Route::get('fund-codes', App\Livewire\FundCodeManagement::class)->name('fund-codes.management');
    Route::get('requirements-library', App\Livewire\RequirementsLibraryManagement::class)
        ->middleware('permission:requirements_library.view')
        ->name('requirements-library.management');

    // Feedback Routes
    Route::get('admin/feedback', App\Livewire\FeedbackManagement::class)
        ->middleware(['permission:feedback.view_all'])
        ->name('feedback.management');

    // User Login Audit Log Route
    Route::get('admin/login-audit', App\Livewire\UserLoginAuditLog::class)
        ->middleware(['permission:audit.login_view'])
        ->name('audit.login');

    // Activity Audit Log Route (who-changed-what trail across housing + comm logs)
    Route::get('admin/activity-log', App\Livewire\ActivityAuditLog::class)
        ->middleware(['permission:audit.view'])
        ->name('audit.activity');

    // Documentation Routes
    Route::get('documentation', App\Livewire\DocumentationViewer::class)
        ->name('documentation.index');
    Route::get('documentation/bookmarks', App\Livewire\DocumentationViewer::class)
        ->name('documentation.bookmarks');
    Route::get('documentation/{category}', App\Livewire\DocumentationViewer::class)
        ->name('documentation.category');
    Route::get('documentation/{category}/{article}', App\Livewire\DocumentationViewer::class)
        ->name('documentation.article');

    // Admin Documentation Management Routes
    Route::get('admin/documentation/categories', App\Livewire\DocumentationCategoryManagement::class)
        ->middleware(['permission:documentation.manage'])
        ->name('admin.documentation.categories');
    Route::get('admin/documentation/articles', App\Livewire\DocumentationArticleManagement::class)
        ->middleware(['permission:documentation.manage'])
        ->name('admin.documentation.articles');

    // DonorPerfect Routes
    Route::get('donor-gifts', App\Livewire\DonorPerfectGiftsFeed::class)
        ->middleware(['auth', 'permission:donor_gifts.view,donorperfect.sync.view'])
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
        ->middleware(['auth', 'permission:donorperfect.sync.view,donor_gifts.configure'])
        ->name('donor-gifts.configuration');
    Route::get('donor-gifts/audit-log', App\Livewire\CheckImageAuditLog::class)
        ->middleware(['role:Administrator'])
        ->name('donor-gifts.audit-log');

    Route::get('donor-gifts/sync-guide', function () {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.sync-guide-pdf');
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download('DonorPerfect_Sync_Guide.pdf');
    })->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.sync-guide');

    // Batch Sync Routes
    Route::get('donor-gifts/manual-batch-sync', App\Livewire\DonorPerfectManualBatchSync::class)
        ->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.manual-batch-sync');

    Route::get('donor-gifts/batch-sync', App\Livewire\DonorPerfectBatchSync::class)
        ->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.batch-sync');

    Route::get('donor-gifts/batch-sync-download', function () {
        $exportData = session()->get('batch_sync_export');

        if (! $exportData || ! isset($exportData['filepath'])) {
            return redirect()->route('donor-gifts.batch-sync')->with('error', 'Export not found or expired.');
        }

        $filepath = $exportData['filepath'];
        $filename = $exportData['filename'];

        if (! file_exists($filepath)) {
            return redirect()->route('donor-gifts.batch-sync')->with('error', 'Export file not found.');
        }

        // Clear session data
        session()->forget('batch_sync_export');

        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    })->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.batch-sync-download');

    Route::get('donor-gifts/batch-preview-excel-download', function () {
        $exportData = session()->get('batch_preview_excel_export');

        if (! $exportData || ! isset($exportData['filepath'])) {
            return redirect()->route('donor-gifts.batch-sync')->with('error', 'Export not found or expired.');
        }

        $filepath = $exportData['filepath'];
        $filename = $exportData['filename'];

        if (! file_exists($filepath)) {
            return redirect()->route('donor-gifts.batch-sync')->with('error', 'Export file not found.');
        }

        session()->forget('batch_preview_excel_export');

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    })->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.batch-preview-excel-download');

    Route::get('donor-gifts/manual-batch-sync-download', function () {
        $exportData = session()->get('manual_batch_sync_export');

        if (! $exportData || ! isset($exportData['filepath'])) {
            return redirect()->route('donor-gifts.manual-batch-sync')->with('error', 'Export not found or expired.');
        }

        $filepath = $exportData['filepath'];
        $filename = $exportData['filename'];

        if (! file_exists($filepath)) {
            return redirect()->route('donor-gifts.manual-batch-sync')->with('error', 'Export file not found.');
        }

        session()->forget('manual_batch_sync_export');

        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    })->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.manual-batch-sync-download');

    Route::get('donor-gifts/manual-batch-preview-excel-download', function () {
        $exportData = session()->get('manual_batch_preview_excel_export');

        if (! $exportData || ! isset($exportData['filepath'])) {
            return redirect()->route('donor-gifts.manual-batch-sync')->with('error', 'Export not found or expired.');
        }

        $filepath = $exportData['filepath'];
        $filename = $exportData['filename'];

        if (! file_exists($filepath)) {
            return redirect()->route('donor-gifts.manual-batch-sync')->with('error', 'Export file not found.');
        }

        session()->forget('manual_batch_preview_excel_export');

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    })->middleware(['auth', 'permission:donor_gifts.view'])
        ->name('donor-gifts.manual-batch-preview-excel-download');

    // Sage Intacct Routes
    Route::get('/accounting/sage-intacct/configuration', \App\Livewire\SageIntacctConfiguration::class)
        ->name('sage-intacct.configuration')
        ->middleware(['auth', 'permission:sage.sync.view,intacct.configure']);

    Route::get('/accounting/sage-intacct/sync-history/{id}', \App\Livewire\SageIntacctSyncHistoryDetails::class)
        ->name('intacct.sync-history.details')
        ->middleware(['auth', 'permission:sage.sync.view,intacct.configure']);

    Route::get('/accounting/sage-intacct/deposit-date-sync', \App\Livewire\DepositDateSync::class)
        ->name('intacct.deposit-date-sync')
        ->middleware(['auth', 'permission:sage.sync.view,intacct.configure']);

    // Sage Intacct OAuth Routes
    Route::get('/intacct/oauth/authorize', [\App\Http\Controllers\SageIntacctOAuthController::class, 'authorize'])
        ->name('intacct.oauth.authorize')
        ->middleware(['auth', 'permission:intacct.sync']);

    Route::get('/intacct/oauth/callback', [\App\Http\Controllers\SageIntacctOAuthController::class, 'callback'])
        ->name('intacct.oauth.callback')
        ->middleware(['auth']);

    Route::post('/intacct/oauth/disconnect', [\App\Http\Controllers\SageIntacctOAuthController::class, 'disconnect'])
        ->name('intacct.oauth.disconnect')
        ->middleware(['auth', 'permission:intacct.sync']);

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
            $baseFilename = pathinfo($safeFilename, PATHINFO_FILENAME);
            $cachedPngsPath = 'check_images/converted/'.$baseFilename;

            // Check if we already have cached PNG conversions
            $cachedMetadataPath = $cachedPngsPath.'_metadata.json';
            $pngFiles = [];
            $useCache = false;

            if (Storage::disk('local')->exists($cachedMetadataPath)) {
                $cachedMetadata = json_decode(Storage::disk('local')->get($cachedMetadataPath), true);

                // Verify all cached PNG files still exist
                $allPngsExist = true;
                foreach ($cachedMetadata['pages'] as $pageInfo) {
                    $cachedPngPath = 'check_images/converted/'.$pageInfo['filename'];
                    if (! Storage::disk('local')->exists($cachedPngPath.'.enc')) {
                        $allPngsExist = false;
                        break;
                    }
                }

                if ($allPngsExist) {
                    $useCache = true;
                    $pngFiles = $cachedMetadata['pages'];
                    $pageCount = $cachedMetadata['page_count'];

                    // Clean up temp file
                    unlink($tempPath);

                    Log::info('Using cached TIFF conversion', [
                        'tiff_file' => $safeFilename,
                        'pages' => $pageCount,
                    ]);

                    // Return HTML viewer for multi-page check
                    return response()->view('check-image-viewer', [
                        'pages' => $pngFiles,
                        'filename' => $safeFilename,
                        'pageCount' => $pageCount,
                    ]);
                }
            }

            // If no cache available, attempt lightweight conversion with strict timeout
            try {
                // Set PHP max execution time for this conversion attempt
                set_time_limit(90); // 90 seconds max for conversion

                // Try to load the TIFF to check page count quickly
                $imagick = new \Imagick;
                $imagick->pingImage($tempPath); // Ping is faster than full load
                $pageCount = $imagick->getNumberImages();
                $imagick->clear();

                // If file is too complex (more than 10 pages), skip on-demand conversion
                if ($pageCount > 10) {
                    Log::warning('TIFF too complex for on-demand conversion', [
                        'file' => $safeFilename,
                        'pages' => $pageCount,
                    ]);

                    // Clean up temp file
                    unlink($tempPath);

                    // Return a message view instead of trying to convert
                    return response()->view('check-image-processing', [
                        'filename' => $safeFilename,
                        'pageCount' => $pageCount,
                        'message' => 'This check image has '.$pageCount.' pages and is being processed in the background. Please check back in a few minutes.',
                    ]);
                }

                // Attempt quick conversion for simple files (10 pages or less)
                $imagick = new \Imagick($tempPath);
                $imagick->setResourceLimit(\Imagick::RESOURCETYPE_TIME, 60); // 60 second hard limit

                // Convert each page to PNG and cache it
                $pageNum = 0;
                foreach ($imagick as $page) {
                    $pngFilename = $baseFilename.'_page'.$pageNum.'.png';

                    $page->setImageFormat('png');
                    $page->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $page->setImageCompressionQuality(75); // Lower quality for faster conversion

                    $pngBlob = $page->getImageBlob();

                    // Cache the converted PNG (encrypted)
                    $cachedPngPath = 'check_images/converted/'.$pngFilename;

                    // Encrypt and store the PNG
                    $encryptedPng = Crypt::encryptString($pngBlob);
                    Storage::disk('local')->put($cachedPngPath.'.enc', $encryptedPng);

                    // Store metadata
                    $pngMetadataPath = 'check_images/metadata/'.$pngFilename.'.json';
                    Storage::disk('local')->put($pngMetadataPath, json_encode([
                        'mime_type' => 'image/png',
                        'size' => strlen($pngBlob),
                        'checksum' => hash('sha256', $pngBlob),
                        'created_at' => now()->toIso8601String(),
                        'source_tiff' => $safeFilename,
                    ]));

                    $pngFiles[] = [
                        'filename' => $pngFilename,
                        'url' => route('donor-gifts.check-image-png', ['filename' => $pngFilename]),
                        'imageData' => base64_encode($pngBlob),
                    ];

                    $pageNum++;
                }

                $imagick->clear();
                $imagick->destroy();

                // Store conversion metadata for future use
                $conversionMetadata = [
                    'source_tiff' => $safeFilename,
                    'page_count' => $pageCount,
                    'pages' => $pngFiles,
                    'converted_at' => now()->toIso8601String(),
                ];
                Storage::disk('local')->put($cachedMetadataPath, json_encode($conversionMetadata));

                // Clean up temp file
                unlink($tempPath);

                Log::info('Converted and cached multi-page TIFF', [
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

            } catch (\ImagickException $e) {
                // Clean up temp file on error
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }

                // Check if it's a timeout error
                if (str_contains($e->getMessage(), 'time limit exceeded')) {
                    Log::warning('TIFF conversion timeout - file too large', [
                        'file' => $safeFilename,
                        'error' => $e->getMessage(),
                    ]);

                    // Return processing message instead of error
                    return response()->view('check-image-processing', [
                        'filename' => $safeFilename,
                        'pageCount' => null,
                        'message' => 'This check image is very large and requires additional processing time. Our system is working on it. Please try again in a few minutes.',
                    ]);
                }

                // For other errors, log and show error
                Log::error('Failed to convert TIFF', [
                    'file' => $safeFilename,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->view('check-image-processing', [
                    'filename' => $safeFilename,
                    'pageCount' => null,
                    'message' => 'Unable to display this check image at this time. Please contact support if this issue persists.',
                    'error' => true,
                ]);
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

    // Check if TIFF conversion is complete (for auto-refresh polling)
    Route::get('donor-gifts/check-cache-status/{filename}', function ($filename) {
        if (! auth()->check() || ! auth()->user()->hasPermission('donor_gifts.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $safeFilename = basename($filename);
        $baseFilename = pathinfo($safeFilename, PATHINFO_FILENAME);
        $cachedMetadataPath = 'check_images/converted/'.$baseFilename.'_metadata.json';

        // Check if cached conversion metadata exists
        if (Storage::disk('local')->exists($cachedMetadataPath)) {
            $metadata = json_decode(Storage::disk('local')->get($cachedMetadataPath), true);

            // Verify at least one PNG file exists
            if (! empty($metadata['pages'])) {
                $firstPage = $metadata['pages'][0];
                $cachedPngPath = 'check_images/converted/'.$firstPage['filename'];

                if (Storage::disk('local')->exists($cachedPngPath.'.enc')) {
                    return response()->json([
                        'ready' => true,
                        'page_count' => $metadata['page_count'],
                        'converted_at' => $metadata['converted_at'],
                    ]);
                }
            }
        }

        return response()->json(['ready' => false]);
    })->name('donor-gifts.check-cache-status');

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
    Route::get('contractor-management', App\Livewire\ContractorManagement::class)->name('contractor.management');

    // Legacy detail routes. App\Livewire\PropertyShow and
    // App\Livewire\ContractorShow were stubbed in 2025-11 but never finished:
    // each component eager-loads relations that don't exist (PropertyShow's
    // 'program') and renders a view file that doesn't exist
    // (livewire.property-show, livewire.contractor-show). Redirect to the
    // corresponding management page so any cached browser bookmarks land
    // somewhere working. No live UI links to these routes anymore. The
    // unfinished components are dead code; cleanup tracked separately.
    Route::get('property/{id}', fn ($id) => redirect()->route('property.management'))
        ->name('property.show');
    Route::get('contractor/{id}', fn ($id) => redirect()->route('contractor.management'))
        ->name('contractor.show');

    // Project Management Module Routes (Unified Interface)
    Route::get('projects', App\Livewire\ProjectsManagement::class)->name('projects.management');
    Route::get('project/{id}/manage', App\Livewire\ProjectManagement::class)->name('project.management');

    // P-17: housing dashboard. Permission gate via mount() rather than
    // middleware so the redirect in App\Livewire\Dashboard can carry
    // unauthenticated/no-permission users back to the default dashboard
    // without bouncing through a 403.
    Route::get('dashboard/housing', App\Livewire\HousingDashboard::class)->name('dashboard.housing');
    Route::get('project-reporting', App\Livewire\ProjectReporting::class)
        ->middleware(['permission:projects.view'])
        ->name('project.reporting');

    // Approvals Routes
    Route::get('approvals/pending', App\Livewire\PendingApprovalsManagement::class)
        ->middleware(['permission:approvals.view_dashboard'])
        ->name('approvals.pending');

    Route::get('approvals/submit/{type?}', App\Livewire\ApprovalSubmissionCreate::class)
        ->middleware(['permission:approvals.submit'])
        ->name('approvals.submit');

    Route::get('approvals/my-submissions', App\Livewire\MyApprovalSubmissions::class)
        ->name('approvals.my-submissions');

    Route::get('approvals/audit-report', App\Livewire\ApprovalAuditReport::class)
        ->middleware(['permission:approvals.view_dashboard'])
        ->name('approvals.audit-report');

    Route::get('approvals/audit-report-download', function () {
        $filename = request()->query('file');
        if (! $filename || ! file_exists(storage_path("app/exports/{$filename}"))) {
            abort(404, 'Export file not found.');
        }

        return response()->download(
            storage_path("app/exports/{$filename}"),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    })->middleware(['permission:approvals.view_dashboard'])->name('approvals.audit-report-download');

    // Approval Email Actions (with signed URLs)
    Route::get('approvals/{id}/approve', [App\Http\Controllers\ApprovalController::class, 'approveFromEmail'])
        ->middleware(['signed'])
        ->name('approval.approve');
    Route::get('approvals/{id}/reject', [App\Http\Controllers\ApprovalController::class, 'rejectFromEmail'])
        ->middleware(['signed'])
        ->name('approval.reject');
    Route::post('approvals/{id}/reject', [App\Http\Controllers\ApprovalController::class, 'submitRejection'])
        ->middleware(['signed'])
        ->name('approval.reject.submit');

    // PDF Form Templates Routes
    Route::get('admin/pdf-templates', App\Livewire\PdfTemplateManagement::class)
        ->middleware(['permission:pdf_templates.view'])
        ->name('pdf-templates.index');
    Route::get('admin/pdf-templates/{id}/map-fields', App\Livewire\PdfTemplateFieldMapping::class)
        ->middleware(['permission:pdf_templates.edit'])
        ->name('pdf-templates.map-fields');

    // Legacy redirects for old housing job URLs
    Route::redirect('housing-job-management', 'projects');
    Route::redirect('housing-job/{id}', 'projects');
    Route::redirect('project-pipeline', 'projects');

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
        try {
            // Check if user is authenticated
            if (! auth()->check()) {
                Log::warning('Unauthenticated access attempt to storage file', [
                    'path' => $path,
                    'ip' => request()->ip(),
                ]);
                abort(403, 'Unauthorized access to file');
            }

            // Path format is: {media_id}/{filename}
            $fullPath = storage_path('app/public/'.$path);

            Log::info('Storage authenticated file request', [
                'requested_path' => $path,
                'full_path' => $fullPath,
                'user_id' => auth()->id(),
            ]);

            // Security: Prevent directory traversal attacks
            $realPath = realpath($fullPath);
            $storagePath = realpath(storage_path('app/public'));

            if (! $storagePath) {
                Log::error('Storage base path not found', [
                    'attempted_path' => storage_path('app/public'),
                ]);
                abort(500, 'Storage system configuration error');
            }

            if (! $realPath) {
                Log::warning('File path could not be resolved', [
                    'requested_path' => $path,
                    'full_path' => $fullPath,
                ]);
                abort(404, 'File not found - path could not be resolved');
            }

            if (! str_starts_with($realPath, $storagePath)) {
                Log::warning('Directory traversal attempt detected', [
                    'requested_path' => $path,
                    'real_path' => $realPath,
                    'storage_path' => $storagePath,
                    'user_id' => auth()->id(),
                ]);
                abort(403, 'Invalid file path - security violation');
            }

            // Check if file exists
            if (! file_exists($fullPath)) {
                Log::warning('Requested file does not exist', [
                    'path' => $path,
                    'full_path' => $fullPath,
                    'real_path' => $realPath,
                ]);
                abort(404, 'File not found in storage');
            }

            // Get file info with error handling
            $mimeType = @mime_content_type($fullPath);
            if ($mimeType === false) {
                Log::warning('Could not determine MIME type, using default', [
                    'file' => $fullPath,
                ]);
                $mimeType = 'application/octet-stream';
            }

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

            Log::info('Successfully serving file', [
                'file' => $fileName,
                'mime_type' => $mimeType,
                'size' => filesize($fullPath),
                'is_download' => $isDownload,
            ]);

            // Return file response
            return response()->file($fullPath, $headers);

        } catch (\Exception $e) {
            Log::error('Error serving authenticated storage file', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'guest',
            ]);

            // Return user-friendly error instead of generic 500
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e; // Re-throw HTTP exceptions (403, 404, etc.)
            }

            abort(500, 'Unable to serve file. Please contact support if this issue persists.');
        }
    })->where('path', '.*')->name('storage.authenticated');
});

require __DIR__.'/auth.php';
