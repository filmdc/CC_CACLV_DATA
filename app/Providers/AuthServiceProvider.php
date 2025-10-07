<?php

namespace App\Providers;

use App\Models\Participant;
use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use App\Policies\ParticipantPolicy;
use App\Policies\EnrollmentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Participant::class => ParticipantPolicy::class,
        // Note: EnrollmentPolicy handles both ProgramEnrollment and ServiceEnrollment
        // but we register it under the generic class name for manual policy resolution
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
