<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\ServiceEnrollment;
use App\Models\ProgramEnrollment;
use App\Models\PendingApproval;
use App\Models\PropertyDocument;
use App\Models\ContractorDocument;
use App\Models\JobDocumentChecklist;
use App\Models\MilestoneDocument;
use App\Observers\ServiceEnrollmentObserver;
use App\Observers\ProgramEnrollmentObserver;
use App\Observers\PendingApprovalObserver;
use App\Observers\DocumentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Gates for permission checking
        Gate::before(function (User $user, string $ability) {
            // Check if user has the permission through their roles
            return $user->hasPermission($ability) ? true : null;
        });

        // Register Model Observers
        ServiceEnrollment::observe(ServiceEnrollmentObserver::class);
        ProgramEnrollment::observe(ProgramEnrollmentObserver::class);
        PendingApproval::observe(PendingApprovalObserver::class);

        // Register Document Observer for approval workflow
        PropertyDocument::observe(DocumentObserver::class);
        ContractorDocument::observe(DocumentObserver::class);
        JobDocumentChecklist::observe(DocumentObserver::class);
        MilestoneDocument::observe(DocumentObserver::class);
    }
}
