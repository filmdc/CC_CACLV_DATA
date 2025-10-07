<?php

namespace App\Traits;

use App\Models\Participant;
use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use Illuminate\Database\Eloquent\Builder;

trait WithProgramAccess
{
    /**
     * Get participants that the current user can access.
     * NOTE: All staff can see ALL participants for basic contact/search purposes.
     * Program access only controls viewing SENSITIVE data, not the participant list itself.
     */
    protected function getAccessibleParticipants(): Builder
    {
        // All authenticated users can see all participants (for basic contact info)
        // The sensitive data is controlled at the field level via canViewSensitiveData()
        return Participant::query();
    }

    /**
     * Get program enrollments that the current user can access.
     */
    protected function getAccessibleProgramEnrollments(): Builder
    {
        $user = auth()->user();

        // Admins can see all enrollments
        if ($user->isAdmin()) {
            return ProgramEnrollment::query();
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return ProgramEnrollment::query()->whereRaw('1 = 0');
        }

        return ProgramEnrollment::whereIn('program_id', $userProgramIds);
    }

    /**
     * Get service enrollments that the current user can access.
     */
    protected function getAccessibleServiceEnrollments(): Builder
    {
        $user = auth()->user();

        // Admins can see all service enrollments
        if ($user->isAdmin()) {
            return ServiceEnrollment::query();
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return ServiceEnrollment::query()->whereRaw('1 = 0');
        }

        // Service enrollments are accessible if the service belongs to user's programs
        return ServiceEnrollment::whereHas('service', function ($query) use ($userProgramIds) {
            $query->whereIn('program_id', $userProgramIds);
        });
    }

    /**
     * Check if the current user can view sensitive data for a participant.
     */
    protected function canViewSensitiveData($participant): bool
    {
        $user = auth()->user();

        if (!$participant) {
            return false;
        }

        $participantId = is_object($participant) ? $participant->id : $participant;

        return $user->canAccessParticipantData($participantId);
    }

    /**
     * Get households that the current user can access.
     * NOTE: All staff can see ALL households for basic contact/search purposes.
     * Program access only controls viewing SENSITIVE household data.
     */
    protected function getAccessibleHouseholds(): Builder
    {
        // All authenticated users can see all households (for basic contact info)
        // The sensitive data is controlled at the field level via canViewSensitiveData()
        return \App\Models\Household::query();
    }

    /**
     * Get only the programs that the current user has access to.
     */
    protected function getAccessiblePrograms(): Builder
    {
        $user = auth()->user();

        // Admins can see all programs
        if ($user->isAdmin()) {
            return \App\Models\Program::query();
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return \App\Models\Program::query()->whereRaw('1 = 0');
        }

        return \App\Models\Program::whereIn('id', $userProgramIds);
    }

    /**
     * Restrict a participant query to those accessible by the current user.
     * NOTE: This method is NOT used for participant lists anymore.
     * All staff can see all participants (with public data only).
     * Use canViewSensitiveData() to control field-level access instead.
     */
    protected function applyProgramAccessFilter(Builder $query): Builder
    {
        // No longer apply filtering - all users can see all participants
        // Sensitive data access is controlled at the field/component level
        return $query;
    }
}
