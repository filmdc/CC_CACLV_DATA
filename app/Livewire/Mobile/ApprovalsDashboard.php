<?php

namespace App\Livewire\Mobile;

use App\Models\PendingApproval;
use App\Models\ApprovalSubmission;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalsDashboard extends Component
{
    use WithPagination, WithPermissionChecks;

    public $filterStatus = 'all';
    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $isApprover = $user->hasPermission('approvals.view_dashboard');
        $canSubmit = $user->hasPermission('approvals.submit');

        if ($isApprover) {
            $query = PendingApproval::with(['submittedBy', 'approver', 'program', 'approvalType']);

            if ($this->filterStatus !== 'all') {
                $query->where('status', $this->filterStatus);
            }

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('item_name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('submittedBy', function ($q2) {
                            $q2->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            }

            $items = $query->orderByRaw("FIELD(status, 'pending', 'rejected', 'approved')")
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $pendingCount = PendingApproval::pending()->count();
        } else {
            $query = ApprovalSubmission::where('submitted_by', $user->id)
                ->with(['approvalType', 'program', 'approvedBy']);

            if ($this->filterStatus !== 'all') {
                $query->where('status', $this->filterStatus);
            }

            if ($this->search) {
                $query->where('title', 'like', '%'.$this->search.'%');
            }

            $items = $query->orderBy('created_at', 'desc')->paginate(20);
            $pendingCount = 0;
        }

        return view('livewire.mobile.approvals-dashboard', [
            'isApprover' => $isApprover,
            'canSubmit' => $canSubmit,
            'items' => $items,
            'pendingCount' => $pendingCount,
        ])->layout('components.layouts.approvals');
    }
}
