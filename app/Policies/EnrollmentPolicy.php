<?php

namespace App\Policies;

use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine if the user can view any enrollments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollments.view');
    }

    /**
     * Determine if the user can view the program enrollment.
     * Requires permission AND program access.
     */
    public function viewProgramEnrollment(User $user, ProgramEnrollment $enrollment): bool
    {
        // Must have view permission
        if (!$user->hasPermission('enrollments.view')) {
            return false;
        }

        // Admins can view all enrollments
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access to the enrollment's program
        return $user->hasAccessToProgram($enrollment->program_id);
    }

    /**
     * Determine if the user can view the service enrollment.
     * Requires permission AND program access.
     */
    public function viewServiceEnrollment(User $user, ServiceEnrollment $enrollment): bool
    {
        // Must have view permission
        if (!$user->hasPermission('enrollments.view')) {
            return false;
        }

        // Admins can view all service enrollments
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access to the service's program
        $programId = $enrollment->service->program_id ?? null;

        if (!$programId) {
            return false;
        }

        return $user->hasAccessToProgram($programId);
    }

    /**
     * Determine if the user can create program enrollments.
     * Can only enroll participants in programs they have access to.
     */
    public function createProgramEnrollment(User $user, $programId = null): bool
    {
        // Must have create permission
        if (!$user->hasPermission('enrollments.create')) {
            return false;
        }

        // Admins can create enrollments in any program
        if ($user->isAdmin()) {
            return true;
        }

        // If program ID is provided, check access
        if ($programId) {
            return $user->hasAccessToProgram($programId);
        }

        // If no program ID, they need at least one program assignment
        return !empty($user->getAccessibleProgramIds());
    }

    /**
     * Determine if the user can create service enrollments.
     */
    public function createServiceEnrollment(User $user, $programId = null): bool
    {
        return $this->createProgramEnrollment($user, $programId);
    }

    /**
     * Determine if the user can update the program enrollment.
     */
    public function updateProgramEnrollment(User $user, ProgramEnrollment $enrollment): bool
    {
        // Must have edit permission
        if (!$user->hasPermission('enrollments.edit')) {
            return false;
        }

        // Admins can edit all enrollments
        if ($user->isAdmin()) {
            return true;
        }

        // Can only edit if user has access to the program
        return $user->hasAccessToProgram($enrollment->program_id);
    }

    /**
     * Determine if the user can update the service enrollment.
     */
    public function updateServiceEnrollment(User $user, ServiceEnrollment $enrollment): bool
    {
        // Must have edit permission
        if (!$user->hasPermission('enrollments.edit')) {
            return false;
        }

        // Admins can edit all service enrollments
        if ($user->isAdmin()) {
            return true;
        }

        // Can only edit if user has access to the service's program
        $programId = $enrollment->service->program_id ?? null;

        if (!$programId) {
            return false;
        }

        return $user->hasAccessToProgram($programId);
    }

    /**
     * Determine if the user can delete the program enrollment.
     */
    public function deleteProgramEnrollment(User $user, ProgramEnrollment $enrollment): bool
    {
        // Must have delete permission
        if (!$user->hasPermission('enrollments.delete')) {
            return false;
        }

        // Admins can delete all enrollments
        if ($user->isAdmin()) {
            return true;
        }

        // Can only delete if user has access to the program
        return $user->hasAccessToProgram($enrollment->program_id);
    }

    /**
     * Determine if the user can delete the service enrollment.
     */
    public function deleteServiceEnrollment(User $user, ServiceEnrollment $enrollment): bool
    {
        // Must have delete permission
        if (!$user->hasPermission('enrollments.delete')) {
            return false;
        }

        // Admins can delete all service enrollments
        if ($user->isAdmin()) {
            return true;
        }

        // Can only delete if user has access to the service's program
        $programId = $enrollment->service->program_id ?? null;

        if (!$programId) {
            return false;
        }

        return $user->hasAccessToProgram($programId);
    }
}
