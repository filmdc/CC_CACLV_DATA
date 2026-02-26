<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalNotification;
use App\Models\PendingApproval;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PendingReview extends Component
{
    use WithPagination, WithPermissionChecks;

    public $filterType = 'all';
    public $filterStatus = 'pending';
    public $search = '';
    public $showRejectModal = false;
    public $selectedApprovalId = null;
    public $rejectionReason = '';

    public function mount()
    {
        $this->authorizeAction('approvals.view_dashboard');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function approve($approvalId)
    {
        $approval = PendingApproval::findOrFail($approvalId);

        if ($approval->status !== 'pending') {
            session()->flash('error', 'This item has already been processed.');
            return;
        }

        DB::transaction(function () use ($approval) {
            $approval->approve(Auth::user());

            ApprovalNotification::create([
                'pending_approval_id' => $approval->id,
                'user_id' => $approval->submitted_by,
                'type' => $approval->status === 'approved' ? 'approved' : 'chain_advanced',
                'message' => $approval->status === 'approved'
                    ? "Your submission '{$approval->item_name}' has been approved."
                    : "Your submission '{$approval->item_name}' has advanced to the next approval step.",
            ]);
        });

        session()->flash('message', 'Item approved successfully.');
    }

    public function openRejectModal($approvalId)
    {
        $this->selectedApprovalId = $approvalId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject()
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:10',
        ], [
            'rejectionReason.min' => 'Please provide at least 10 characters explaining the rejection.',
        ]);

        $approval = PendingApproval::findOrFail($this->selectedApprovalId);

        if ($approval->status !== 'pending') {
            session()->flash('error', 'This item has already been processed.');
            $this->showRejectModal = false;
            return;
        }

        DB::transaction(function () use ($approval) {
            $approval->reject(Auth::user(), $this->rejectionReason);

            ApprovalNotification::create([
                'pending_approval_id' => $approval->id,
                'user_id' => $approval->submitted_by,
                'type' => 'rejected',
                'message' => "Your submission '{$approval->item_name}' has been rejected. Reason: {$this->rejectionReason}",
            ]);
        });

        $this->showRejectModal = false;
        $this->rejectionReason = '';
        $this->selectedApprovalId = null;

        session()->flash('message', 'Item rejected.');
    }

    public function render()
    {
        $query = PendingApproval::with(['submittedBy', 'approver', 'program', 'approvable']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType !== 'all') {
            $typeMap = [
                'documents' => ['App\Models\PropertyDocument', 'App\Models\ContractorDocument', 'App\Models\JobDocumentChecklist'],
                'tasks' => ['App\Models\WorkPlanGoal'],
                'submissions' => ['App\Models\ApprovalSubmission'],
            ];
            if (isset($typeMap[$this->filterType])) {
                $query->whereIn('approvable_type', $typeMap[$this->filterType]);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('submittedBy', function ($q2) {
                        $q2->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('program', function ($q2) {
                        $q2->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $approvals = $query->orderBy('created_at', 'desc')->paginate(15);

        $pendingCount = PendingApproval::pending()->count();

        return view('livewire.mobile.pending-review', [
            'approvals' => $approvals,
            'pendingCount' => $pendingCount,
        ])->layout('components.layouts.approvals');
    }
}
