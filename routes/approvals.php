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
