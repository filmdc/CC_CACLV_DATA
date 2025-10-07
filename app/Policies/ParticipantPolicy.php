<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    /**
     * Determine if the user can view any participants.
     * This controls access to the participant list (public data only).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('participants.view');
    }

    /**
     * Determine if the user can view the participant (public data).
     */
    public function view(User $user, Participant $participant): bool
    {
        return $user->hasPermission('participants.view');
    }

    /**
     * Determine if the user can view sensitive participant data.
     * Requires both permission AND program access.
     */
    public function viewSensitiveData(User $user, Participant $participant): bool
    {
        // Must have base view permission
        if (!$user->hasPermission('participants.view')) {
            return false;
        }

        // Admins can view all sensitive data
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access to any program this participant is enrolled in
        return $user->canAccessParticipantData($participant->id);
    }

    /**
     * Determine if the user can create participants.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('participants.create');
    }

    /**
     * Determine if the user can update the participant.
     * Can update if they have edit permission AND can access participant data.
     */
    public function update(User $user, Participant $participant): bool
    {
        // Must have edit permission
        if (!$user->hasPermission('participants.edit')) {
            return false;
        }

        // Admins can edit all participants
        if ($user->isAdmin()) {
            return true;
        }

        // For public fields, anyone with edit permission can update
        // For sensitive fields, need program access
        return $user->canAccessParticipantData($participant->id);
    }

    /**
     * Determine if the user can delete the participant.
     */
    public function delete(User $user, Participant $participant): bool
    {
        // Must have delete permission
        if (!$user->hasPermission('participants.delete')) {
            return false;
        }

        // Admins can delete all participants
        if ($user->isAdmin()) {
            return true;
        }

        // Can only delete if user has access to participant data
        return $user->canAccessParticipantData($participant->id);
    }

    /**
     * Determine if the user can restore a deleted participant.
     */
    public function restore(User $user, Participant $participant): bool
    {
        return $user->hasPermission('participants.delete') && $user->isAdmin();
    }

    /**
     * Determine if the user can permanently delete the participant.
     */
    public function forceDelete(User $user, Participant $participant): bool
    {
        return $user->hasPermission('participants.delete') && $user->isAdmin();
    }
}
