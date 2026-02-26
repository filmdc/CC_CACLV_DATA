<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalHistory;
use App\Models\ApprovalNotification;
use App\Models\PendingApproval;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ApprovalDetail extends Component
{
    use WithPermissionChecks;

    public PendingApproval $approval;
    public $showRejectModal = false;
    public $rejectionReason = '';

    public function mount($id)
    {
        $this->approval = PendingApproval::with([
            'submittedBy', 'approver', 'program', 'approvable', 'approvalType',
        ])->findOrFail($id);
    }

    public function approve()
    {
        if ($this->approval->status !== 'pending') {
            session()->flash('error', 'This item has already been processed.');
            return;
        }

        DB::transaction(function () {
            $previousStep = $this->approval->current_chain_step;
            $this->approval->approve(Auth::user());
            $this->approval->refresh();

            $document = $this->approval->approvable ?? $this->approval;

            ApprovalHistory::create(array_filter([
                'approvable_type' => get_class($document),
                'approvable_id' => $document->id,
                'version' => $document->document_version ?? 1,
                'action' => 'approved',
                'user_id' => Auth::id(),
                'approval_type_id' => $this->approval->approval_type_id,
                'chain_step' => $previousStep,
            ]));

            ApprovalNotification::create([
                'pending_approval_id' => $this->approval->id,
                'user_id' => $this->approval->submitted_by,
                'type' => $this->approval->status === 'approved' ? 'approved' : 'chain_advanced',
                'message' => $this->approval->status === 'approved'
                    ? "Your submission '{$this->approval->item_name}' has been approved."
                    : "Your submission '{$this->approval->item_name}' has advanced to the next approval step.",
            ]);
        });

        session()->flash('message', 'Item approved successfully.');
        return redirect()->route('mobile.review');
    }

    public function openRejectModal()
    {
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject()
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:10',
        ], [
            'rejectionReason.min' => 'Please provide at least 10 characters.',
        ]);

        if ($this->approval->status !== 'pending') {
            session()->flash('error', 'This item has already been processed.');
            $this->showRejectModal = false;
            return;
        }

        DB::transaction(function () {
            $this->approval->reject(Auth::user(), $this->rejectionReason);

            $document = $this->approval->approvable ?? $this->approval;

            ApprovalHistory::create(array_filter([
                'approvable_type' => get_class($document),
                'approvable_id' => $document->id,
                'version' => $document->document_version ?? 1,
                'action' => 'rejected',
                'user_id' => Auth::id(),
                'reason' => $this->rejectionReason,
                'approval_type_id' => $this->approval->approval_type_id,
            ]));

            ApprovalNotification::create([
                'pending_approval_id' => $this->approval->id,
                'user_id' => $this->approval->submitted_by,
                'type' => 'rejected',
                'message' => "Your submission '{$this->approval->item_name}' has been rejected. Reason: {$this->rejectionReason}",
            ]);
        });

        session()->flash('message', 'Item rejected.');
        return redirect()->route('mobile.review');
    }

    public function render()
    {
        $history = ApprovalHistory::where('approvable_type', get_class($this->approval->approvable ?? $this->approval))
            ->where('approvable_id', $this->approval->approvable_id ?? $this->approval->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $attachments = [];
        if ($this->approval->approvable && method_exists($this->approval->approvable, 'getMedia')) {
            $attachments = $this->approval->approvable->getMedia('attachments');
        }

        $approvalType = $this->approval->approvalType;
        $totalSteps = $approvalType ? $approvalType->getTotalSteps() : 1;
        $currentStep = $this->approval->current_chain_step ?? 1;

        return view('livewire.mobile.approval-detail', [
            'history' => $history,
            'attachments' => $attachments,
            'totalSteps' => $totalSteps,
            'currentStep' => $currentStep,
        ])->layout('components.layouts.approvals');
    }
}
