<?php

namespace App\Livewire;

use App\Models\ApprovalHistory;
use App\Models\PendingApproval;
use App\Models\ApprovalNotification;
use App\Models\Program;
use App\Services\MediaValidationService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PendingApprovalsManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $filterType = 'all';
    public $filterStatus = 'pending';
    public $filterProgram = null;
    public $search = '';
    public $selectedApproval = null;
    public $showRejectModal = false;
    public $showDetailModal = false;
    public $detailApproval = null;
    public $detailAttachments = [];
    public $detailHistory = [];
    public $rejectionReason = '';
    public $bulkSelected = [];

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

    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

    public function getPendingApprovalsProperty()
    {
        $query = PendingApproval::with([
            'submittedBy',
            'approver',
            'program',
            'approvable'
        ]);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType !== 'all') {
            $typeMap = [
                'documents' => [
                    'App\Models\PropertyDocument',
                    'App\Models\ContractorDocument',
                    'App\Models\JobDocumentChecklist'
                ],
                'tasks' => [
                    'App\Models\WorkPlanGoal'
                ],
                'submissions' => [
                    'App\Models\ApprovalSubmission'
                ],
            ];

            if (isset($typeMap[$this->filterType])) {
                $query->whereIn('approvable_type', $typeMap[$this->filterType]);
            }
        }

        if ($this->filterProgram) {
            $query->where('program_id', $this->filterProgram);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('submittedBy', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('program', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('item_name', 'like', '%' . $this->search . '%')
                ->orWhere('item_description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('submitted_at', 'desc')->paginate(15);
    }

    public function viewDetails($approvalId)
    {
        $approval = PendingApproval::with([
            'approvable',
            'approvalType',
            'submittedBy',
            'approver',
            'program',
        ])->findOrFail($approvalId);

        $this->detailApproval = $approval;

        // Load attachments if the approvable is an ApprovalSubmission with media
        $this->detailAttachments = [];
        if ($approval->approvable && $approval->approvable instanceof \App\Models\ApprovalSubmission) {
            $this->detailAttachments = $approval->approvable->getMedia('attachments')->map(function ($media) {
                return [
                    'name' => MediaValidationService::displayName($media),
                    'size' => $media->human_readable_size,
                    'url' => $media->getUrl(),
                    'mime' => $media->mime_type,
                ];
            })->toArray();
        }

        // Load approval history for this item
        $this->detailHistory = ApprovalHistory::where('approvable_type', $approval->approvable_type)
            ->where('approvable_id', $approval->approvable_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'action' => $entry->action,
                    'user' => $entry->user->name ?? 'System',
                    'reason' => $entry->reason,
                    'notes' => $entry->notes,
                    'chain_step' => $entry->chain_step,
                    'created_at' => $entry->created_at->format('M d, Y g:i A'),
                ];
            })->toArray();

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailApproval = null;
        $this->detailAttachments = [];
        $this->detailHistory = [];
    }

    public function approveFromDetail($approvalId)
    {
        $this->closeDetailModal();
        $this->approve($approvalId);
    }

    public function rejectFromDetail($approvalId)
    {
        $this->closeDetailModal();
        $this->showRejectModal($approvalId);
    }

    public function showRejectModal($approvalId)
    {
        $this->authorizeAction('approvals.approve_reject');
        $this->selectedApproval = PendingApproval::findOrFail($approvalId);
        $this->showRejectModal = true;
        $this->rejectionReason = '';
    }

    public function approve($approvalId)
    {
        $this->authorizeAction('approvals.approve_reject');

        try {
            DB::beginTransaction();

            $approval = PendingApproval::findOrFail($approvalId);

            if ($approval->status !== 'pending') {
                session()->flash('error', 'This approval has already been processed.');
                DB::rollBack();
                return;
            }

            $previousStep = $approval->current_chain_step;
            $approval->approve(Auth::user());
            $approval->refresh();

            // Check if chain advanced (still pending) vs fully approved
            if ($approval->status === 'pending' && $approval->current_chain_step > $previousStep) {
                session()->flash('message', "Step {$previousStep} approved. Advanced to step {$approval->current_chain_step}.");
            } else {
                // Also update the underlying approvable's approval fields if applicable
                if ($approval->approvable && isset($approval->approvable->approval_status)) {
                    $approval->approvable->update([
                        'approval_status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                }

                ApprovalNotification::create([
                    'pending_approval_id' => $approval->id,
                    'notification_type' => 'approved',
                    'recipient_id' => $approval->submitted_by,
                    'sent_at' => now(),
                ]);

                session()->flash('message', 'Item approved successfully.');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error approving item: ' . $e->getMessage());
        }
    }

    public function reject()
    {
        $this->authorizeAction('approvals.approve_reject');
        $this->validate([
            'rejectionReason' => 'required|string|min:10',
        ]);

        try {
            DB::beginTransaction();

            if ($this->selectedApproval->status !== 'pending') {
                session()->flash('error', 'This approval has already been processed.');
                DB::rollBack();
                $this->showRejectModal = false;
                return;
            }

            $this->selectedApproval->reject(Auth::user(), $this->rejectionReason);

            if ($this->selectedApproval->approvable && isset($this->selectedApproval->approvable->approval_status)) {
                $this->selectedApproval->approvable->update([
                    'approval_status' => 'rejected',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);
            }

            ApprovalNotification::create([
                'pending_approval_id' => $this->selectedApproval->id,
                'notification_type' => 'rejected',
                'recipient_id' => $this->selectedApproval->submitted_by,
                'sent_at' => now(),
            ]);

            DB::commit();
            session()->flash('message', 'Item rejected successfully.');
            $this->showRejectModal = false;
            $this->rejectionReason = '';
            $this->selectedApproval = null;
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error rejecting item: ' . $e->getMessage());
        }
    }

    public function bulkApprove()
    {
        $this->authorizeAction('approvals.approve_reject');

        if (empty($this->bulkSelected)) {
            session()->flash('error', 'Please select items to approve.');
            return;
        }

        try {
            DB::beginTransaction();

            $approvedCount = 0;
            foreach ($this->bulkSelected as $approvalId) {
                $approval = PendingApproval::find($approvalId);

                if (!$approval || $approval->status !== 'pending') {
                    continue;
                }

                $approval->update([
                    'status' => 'approved',
                    'approver_id' => Auth::id(),
                    'approved_at' => now(),
                ]);

                if ($approval->approvable) {
                    $approval->approvable->update([
                        'approval_status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                }

                ApprovalNotification::create([
                    'pending_approval_id' => $approval->id,
                    'notification_type' => 'approved',
                    'recipient_id' => $approval->submitted_by,
                    'sent_at' => now(),
                ]);

                $approvedCount++;
            }

            DB::commit();
            session()->flash('message', "Successfully approved {$approvedCount} item(s).");
            $this->bulkSelected = [];
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error approving items: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->filterType = 'all';
        $this->filterStatus = 'pending';
        $this->filterProgram = null;
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $programs = Program::where('active', true)->orderBy('name')->get();

        $pendingCount = PendingApproval::where('status', 'pending')->count();

        return view('livewire.pending-approvals-management', [
            'approvals' => $this->pendingApprovals,
            'programs' => $programs,
            'pendingCount' => $pendingCount,
        ])->layout('components.layouts.app');
    }
}
