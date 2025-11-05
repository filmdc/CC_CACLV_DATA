<?php

namespace App\Http\Controllers;

use App\Models\PendingApproval;
use App\Notifications\ApprovalActionedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    /**
     * Approve a pending approval from email link.
     */
    public function approveFromEmail(Request $request, $id)
    {
        $pendingApproval = PendingApproval::findOrFail($id);

        // Check if user has permission to approve
        if (!$this->canApprove($pendingApproval)) {
            abort(403, 'You do not have permission to approve this item.');
        }

        // Check if already actioned
        if (!$pendingApproval->isPending()) {
            return redirect()->route('dashboard')
                ->with('error', 'This approval has already been processed.');
        }

        // Approve the item
        $pendingApproval->approve(Auth::user());

        // Send notification to submitter
        $pendingApproval->submittedBy->notify(
            new ApprovalActionedNotification($pendingApproval, 'approved')
        );

        return redirect()->route('dashboard')
            ->with('message', 'Item approved successfully.');
    }

    /**
     * Show rejection form from email link.
     */
    public function rejectFromEmail(Request $request, $id)
    {
        $pendingApproval = PendingApproval::findOrFail($id);

        // Check if user has permission to reject
        if (!$this->canApprove($pendingApproval)) {
            abort(403, 'You do not have permission to reject this item.');
        }

        // Check if already actioned
        if (!$pendingApproval->isPending()) {
            return redirect()->route('dashboard')
                ->with('error', 'This approval has already been processed.');
        }

        return view('approvals.reject', [
            'pendingApproval' => $pendingApproval,
        ]);
    }

    /**
     * Process rejection with reason.
     */
    public function submitRejection(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $pendingApproval = PendingApproval::findOrFail($id);

        // Check if user has permission to reject
        if (!$this->canApprove($pendingApproval)) {
            abort(403, 'You do not have permission to reject this item.');
        }

        // Check if already actioned
        if (!$pendingApproval->isPending()) {
            return redirect()->route('dashboard')
                ->with('error', 'This approval has already been processed.');
        }

        // Reject the item
        $pendingApproval->reject(Auth::user(), $request->rejection_reason);

        // Send notification to submitter
        $pendingApproval->submittedBy->notify(
            new ApprovalActionedNotification($pendingApproval, 'rejected')
        );

        return redirect()->route('dashboard')
            ->with('message', 'Item rejected. The submitter has been notified.');
    }

    /**
     * Check if the current user can approve this item.
     */
    protected function canApprove(PendingApproval $pendingApproval): bool
    {
        $user = Auth::user();

        // Admins can approve anything
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has approval permission
        if (!$user->hasPermission('approvals.approve')) {
            return false;
        }

        // Check program-level access if applicable
        if ($pendingApproval->program_id) {
            return $user->hasAccessToProgram($pendingApproval->program_id);
        }

        return true;
    }
}
