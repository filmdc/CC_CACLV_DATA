<?php

namespace App\Http\Controllers;

use App\Models\PendingApproval;
use App\Notifications\ApprovalActionedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * Approve a pending approval from email link.
     */
    public function approveFromEmail(Request $request, int $id): RedirectResponse
    {
        $pendingApproval = $this->findApprovalOrAbort($id);
        if ($redirect = $this->redirectIfProcessed($pendingApproval)) {
            return $redirect;
        }

        $pendingApproval->approve(Auth::user());
        $this->notifySubmitter($pendingApproval, 'approved');

        return $this->successRedirect('Item approved successfully.');
    }

    /**
     * Show rejection form from email link.
     */
    public function rejectFromEmail(Request $request, int $id): View|RedirectResponse
    {
        $pendingApproval = $this->findApprovalOrAbort($id);
        if ($redirect = $this->redirectIfProcessed($pendingApproval)) {
            return $redirect;
        }

        return view('approvals.reject', [
            'pendingApproval' => $pendingApproval,
        ]);
    }

    /**
     * Process rejection with reason.
     */
    public function submitRejection(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $pendingApproval = $this->findApprovalOrAbort($id);
        if ($redirect = $this->redirectIfProcessed($pendingApproval)) {
            return $redirect;
        }

        $pendingApproval->reject(Auth::user(), $request->rejection_reason);
        $this->notifySubmitter($pendingApproval, 'rejected');

        return $this->successRedirect('Item rejected. The submitter has been notified.');
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

    /**
     * Retrieve the approval record and ensure the current user can manage it.
     */
    private function findApprovalOrAbort(int $id): PendingApproval
    {
        $pendingApproval = PendingApproval::with('submittedBy')->findOrFail($id);

        abort_if(
            !$this->canApprove($pendingApproval),
            403,
            'You do not have permission to manage this approval item.'
        );

        return $pendingApproval;
    }

    /**
     * Provide a consistent redirect when the approval has already been processed.
     */
    private function redirectIfProcessed(PendingApproval $pendingApproval): ?RedirectResponse
    {
        if ($pendingApproval->isPending()) {
            return null;
        }

        return redirect()->route('dashboard')
            ->with('error', 'This approval has already been processed.');
    }

    private function successRedirect(string $message): RedirectResponse
    {
        return redirect()->route('dashboard')->with('message', $message);
    }

    private function notifySubmitter(PendingApproval $pendingApproval, string $action): void
    {
        $pendingApproval->submittedBy->notify(
            new ApprovalActionedNotification($pendingApproval, $action)
        );
    }
}
