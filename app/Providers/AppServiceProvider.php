<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogLockout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Models\ContractorDocument;
use App\Models\JobDocumentChecklist;
use App\Models\MilestoneDocument;
use App\Models\PendingApproval;
use App\Models\ProgramEnrollment;
use App\Models\PropertyDocument;
use App\Models\ServiceEnrollment;
use App\Models\User;
use App\Observers\DocumentObserver;
use App\Observers\PendingApprovalObserver;
use App\Observers\ProgramEnrollmentObserver;
use App\Observers\ServiceEnrollmentObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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

        // Register Auth Event Listeners for login audit logging
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Lockout::class, LogLockout::class);

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
