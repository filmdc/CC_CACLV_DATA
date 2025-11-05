<?php

namespace App\Observers;

use App\Models\PendingApproval;
use App\Models\User;
use App\Notifications\ApprovalActionedNotification;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\DocumentResubmittedNotification;

class PendingApprovalObserver
{
    /**
     * Handle the PendingApproval "created" event.
     * Send notification to approvers when a new approval request is created.
     */
    public function created(PendingApproval $pendingApproval): void
    {
        // Get the approvers for this item
        $approvers = $this->getApprovers($pendingApproval);

        // Send notification to all approvers
        foreach ($approvers as $approver) {
            $approver->notify(new ApprovalRequestedNotification($pendingApproval));
        }
    }

    /**
     * Handle the PendingApproval "updated" event.
     * Send notification to submitter when approval status changes.
     */
    public function updated(PendingApproval $pendingApproval): void
    {
        // Check if status was changed
        if ($pendingApproval->wasChanged('status')) {
            $oldStatus = $pendingApproval->getOriginal('status');
            $newStatus = $pendingApproval->status;

            // If status changed from pending to approved or rejected
            if ($oldStatus === 'pending' && in_array($newStatus, ['approved', 'rejected'])) {
                $submitter = $pendingApproval->submittedBy;

                if ($submitter) {
                    $action = $newStatus === 'approved' ? 'approved' : 'rejected';
                    $submitter->notify(new ApprovalActionedNotification($pendingApproval, $action));
                }
            }

            // If status changed from rejected back to pending (resubmission)
            if ($oldStatus === 'rejected' && $newStatus === 'pending') {
                $approvers = $this->getApprovers($pendingApproval);

                foreach ($approvers as $approver) {
                    $approver->notify(new DocumentResubmittedNotification($pendingApproval));
                }
            }
        }
    }

    /**
     * Get the list of approvers for a pending approval.
     */
    protected function getApprovers(PendingApproval $pendingApproval): array
    {
        // If a specific approver is assigned, return only that user
        if ($pendingApproval->approver_id) {
            $approver = User::find($pendingApproval->approver_id);
            return $approver ? [$approver] : [];
        }

        // Otherwise, get all users with approval permission
        $approvers = User::whereHas('roles.permissions', function ($query) {
            $query->where('name', 'approvals.approve');
        })->get();

        // If program-specific, filter to users with access to that program
        if ($pendingApproval->program_id) {
            $approvers = $approvers->filter(function ($user) use ($pendingApproval) {
                return $user->hasAccessToProgram($pendingApproval->program_id);
            });
        }

        return $approvers->toArray();
    }
}
