<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Approvals Portal Routes
|--------------------------------------------------------------------------
|
| Routes for approvals.caclv.org — mobile-first approval interface.
| These routes share authentication with the main app via session cookies.
|
*/

Route::domain(env('APPROVALS_DOMAIN', 'approvals.caclv.org'))->group(function () {

    // Guest routes (login)
    Route::middleware('guest')->group(function () {
        Route::get('/login', App\Livewire\Mobile\MobileLogin::class)->name('mobile.login');
    });

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::get('/', App\Livewire\Mobile\ApprovalsDashboard::class)->name('mobile.dashboard');

        // Approver routes
        Route::get('/review', App\Livewire\Mobile\PendingReview::class)
            ->middleware('permission:approvals.view_dashboard')
            ->name('mobile.review');

        Route::get('/review/{id}', App\Livewire\Mobile\ApprovalDetail::class)
            ->middleware('permission:approvals.view_dashboard')
            ->name('mobile.review.detail');

        // Requester routes
        Route::get('/submit', App\Livewire\Mobile\SubmitRequest::class)
            ->middleware('permission:approvals.submit')
            ->name('mobile.submit');

        Route::get('/my-submissions', App\Livewire\Mobile\MySubmissions::class)
            ->name('mobile.my-submissions');

        // Report routes
        Route::get('/reports/timeline', App\Livewire\Mobile\TimelineReport::class)
            ->middleware('permission:approvals.view_audit_log')
            ->name('mobile.timeline-report');

        Route::get('/reports/executive', App\Livewire\Mobile\ExecutiveOverview::class)
            ->middleware('permission:approvals.executive_view')
            ->name('mobile.executive-overview');

        Route::get('/reports/download', function () {
            $filename = request()->query('file');
            if (! $filename || ! file_exists(storage_path("app/exports/{$filename}"))) {
                abort(404, 'Export file not found.');
            }

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv' => 'text/csv',
            ];

            return response()->download(
                storage_path("app/exports/{$filename}"),
                $filename,
                ['Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream']
            )->deleteFileAfterSend(true);
        })->middleware('permission:approvals.view_audit_log')->name('mobile.report.download');

        // Authenticated storage route for private media files
        Route::get('/storage/{path}', function ($path) {
            if (! auth()->check()) {
                abort(403, 'Unauthorized access to file');
            }

            $fullPath = storage_path('app/private/' . $path);

            // Security: Prevent directory traversal
            $realPath = realpath($fullPath);
            $basePath = realpath(storage_path('app/private'));

            if (! $realPath || ! $basePath || ! str_starts_with($realPath, $basePath)) {
                abort(404, 'File not found');
            }

            if (! file_exists($fullPath)) {
                abort(404, 'File not found');
            }

            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
            $fileName = basename($fullPath);

            $isDownload = request()->has('download') && request()->get('download') == '1';

            $headers = [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, max-age=300',
            ];

            if ($isDownload) {
                return response()->download($fullPath, $fileName, $headers);
            }

            return response()->file($fullPath, $headers);
        })->where('path', '.*')->name('mobile.storage');

        // Reuse existing email action routes
        Route::get('/approvals/{id}/approve', [App\Http\Controllers\ApprovalController::class, 'approveFromEmail'])
            ->middleware('signed')
            ->name('mobile.approval.approve');
        Route::get('/approvals/{id}/reject', [App\Http\Controllers\ApprovalController::class, 'rejectFromEmail'])
            ->middleware('signed')
            ->name('mobile.approval.reject');
        Route::post('/approvals/{id}/reject', [App\Http\Controllers\ApprovalController::class, 'submitRejection'])
            ->middleware('signed')
            ->name('mobile.approval.reject.submit');
    });
});
