<?php

namespace App\Observers;

use App\Models\ApprovalChain;
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
        $approvers = $this->getApprovers($pendingApproval);

        foreach ($approvers as $approver) {
            $approver->notify(new ApprovalRequestedNotification($pendingApproval));
        }
    }

    /**
     * Handle the PendingApproval "updated" event.
     * Send notification to submitter when approval status changes,
     * or notify next-step approvers when chain advances.
     */
    public function updated(PendingApproval $pendingApproval): void
    {
        // Chain step advancement: still pending but step changed
        if ($pendingApproval->wasChanged('current_chain_step') && $pendingApproval->status === 'pending') {
            $approvers = $this->getApprovers($pendingApproval);

            foreach ($approvers as $approver) {
                $approver->notify(new ApprovalRequestedNotification($pendingApproval));
            }

            return;
        }

        // Status change notifications
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
     * Checks chain-based approvers first, then falls back to permission-based lookup.
     */
    protected function getApprovers(PendingApproval $pendingApproval): array
    {
        // If a specific approver is assigned, return only that user
        if ($pendingApproval->approver_id) {
            $approver = User::find($pendingApproval->approver_id);

            return $approver ? [$approver] : [];
        }

        // Check for chain-based approvers via approval_type_id
        if ($pendingApproval->approval_type_id) {
            $approvalType = $pendingApproval->approvalType;
            $currentStep = $pendingApproval->current_chain_step ?? 1;

            if ($approvalType) {
                $chain = $approvalType->getChainForStep($currentStep);

                if ($chain) {
                    $chainApprovers = $chain->getQualifiedApprovers();

                    if ($chainApprovers->isNotEmpty()) {
                        return $chainApprovers->all();
                    }
                }
            }
        }

        // Fallback: get all users with approval permission
        $approvers = User::whereHas('roles.permissions', function ($query) {
            $query->where('name', 'approvals.approve_reject');
        })->get();

        // If program-specific, filter to users with access to that program
        if ($pendingApproval->program_id) {
            $approvers = $approvers->filter(function ($user) use ($pendingApproval) {
                return $user->hasAccessToProgram($pendingApproval->program_id);
            });
        }

        return $approvers->all();
    }
}
