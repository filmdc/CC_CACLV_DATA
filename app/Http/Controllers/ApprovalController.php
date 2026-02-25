<?php

namespace App\Http\Controllers;

use App\Models\ApprovalHistory;
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

        $previousStep = $pendingApproval->current_chain_step;
        $pendingApproval->approve(Auth::user());
        $pendingApproval->refresh();

        // Log the approval with signature info
        $this->logAuditEntry($pendingApproval, $request, 'approved', $previousStep);

        // Only notify submitter if fully approved (not just chain advancement)
        if ($pendingApproval->isApproved()) {
            $this->notifySubmitter($pendingApproval, 'approved');

            return $this->successRedirect('Item approved successfully.');
        }

        return $this->successRedirect("Step {$previousStep} approved. Advanced to step {$pendingApproval->current_chain_step}.");
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

        // Log the rejection with signature info
        $this->logAuditEntry($pendingApproval, $request, 'rejected');

        $this->notifySubmitter($pendingApproval, 'rejected');

        return $this->successRedirect('Item rejected. The submitter has been notified.');
    }

    /**
     * Check if the current user can approve this item.
     */
    protected function canApprove(PendingApproval $pendingApproval): bool
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermission('approvals.approve_reject')) {
            return false;
        }

        if ($pendingApproval->program_id) {
            return $user->hasAccessToProgram($pendingApproval->program_id);
        }

        return true;
    }

    private function findApprovalOrAbort(int $id): PendingApproval
    {
        $pendingApproval = PendingApproval::with('submittedBy')->findOrFail($id);

        abort_if(
            ! $this->canApprove($pendingApproval),
            403,
            'You do not have permission to manage this approval item.'
        );

        return $pendingApproval;
    }

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
        if ($pendingApproval->submittedBy) {
            $pendingApproval->submittedBy->notify(
                new ApprovalActionedNotification($pendingApproval, $action)
            );
        }
    }

    /**
     * Log an audit entry with request signature info (IP, user agent).
     */
    private function logAuditEntry(PendingApproval $pendingApproval, Request $request, string $action, ?int $chainStep = null): void
    {
        $document = $pendingApproval->approvable ?? $pendingApproval;

        $data = [
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'version' => $document->document_version ?? 1,
            'action' => $action,
            'user_id' => Auth::id(),
            'signature_ip' => $request->ip(),
            'signature_user_agent' => $request->userAgent(),
        ];

        if ($pendingApproval->approval_type_id) {
            $data['approval_type_id'] = $pendingApproval->approval_type_id;
        }

        if ($chainStep) {
            $data['chain_step'] = $chainStep;
        }

        if ($action === 'rejected') {
            $data['reason'] = $pendingApproval->rejection_reason;
        }

        ApprovalHistory::create($data);
    }
}
