<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalHistory;
use App\Models\ApprovalSubmission;
use App\Models\PendingApproval;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ApprovalsDashboard extends Component
{
    use WithPermissionChecks;

    public function render()
    {
        $user = Auth::user();
        $isApprover = $user->hasPermission('approvals.view_dashboard');
        $canSubmit = $user->hasPermission('approvals.submit');

        $pendingReviewCount = $isApprover
            ? PendingApproval::pending()->count()
            : 0;

        $myPendingCount = ApprovalSubmission::where('submitted_by', $user->id)
            ->where('status', 'pending')
            ->count();

        $myApprovedCount = ApprovalSubmission::where('submitted_by', $user->id)
            ->where('status', 'approved')
            ->count();

        $myRejectedCount = ApprovalSubmission::where('submitted_by', $user->id)
            ->where('status', 'rejected')
            ->count();

        $recentActivity = ApprovalHistory::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('approvable', function ($q2) use ($user) {
                    $q2->where('submitted_by', $user->id);
                });
        })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('livewire.mobile.approvals-dashboard', [
            'isApprover' => $isApprover,
            'canSubmit' => $canSubmit,
            'pendingReviewCount' => $pendingReviewCount,
            'myPendingCount' => $myPendingCount,
            'myApprovedCount' => $myApprovedCount,
            'myRejectedCount' => $myRejectedCount,
            'recentActivity' => $recentActivity,
        ])->layout('components.layouts.approvals');
    }
}
