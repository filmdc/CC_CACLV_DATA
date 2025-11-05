<?php

namespace App\Livewire;

use App\Models\Contractor;
use App\Models\ContractorDocument;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ContractorShow extends Component
{
    use WithPermissionChecks;

    public $contractor;
    public $contractorId;

    // Approval modal state
    public $showApprovalModal = false;
    public $approvalDocumentId = null;
    public $rejectionReason = '';

    public function mount($id)
    {
        // Check if user has permission to view contractors
        $this->authorizeAction('contractors.view', 'You do not have permission to view contractors.');
        
        $this->contractorId = $id;
        $this->contractor = Contractor::with([
            'housingJobs.property',
            'contractorDocuments'
        ])->findOrFail($id);
    }

    public function editContractor()
    {
        return redirect()->route('contractor.management')->with('selectedContractor', $this->contractorId);
    }

    /**
     * Approve a contractor document
     */
    public function approveDocument($documentId)
    {
        $this->authorizeAction('contractors.update');

        try {
            $document = ContractorDocument::findOrFail($documentId);

            if ($document->contractor_id !== $this->contractor->id) {
                session()->flash('error', 'Invalid document.');
                return;
            }

            $document->approve(Auth::user());

            // Refresh the contractor data
            $this->contractor->load('contractorDocuments');

            session()->flash('message', 'Document approved successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error approving document: ' . $e->getMessage());
        }
    }

    /**
     * Show rejection modal for document
     */
    public function showRejectModal($documentId)
    {
        $this->approvalDocumentId = $documentId;
        $this->rejectionReason = '';
        $this->showApprovalModal = true;
    }

    /**
     * Reject a contractor document with reason
     */
    public function rejectDocument($documentId = null, $reason = null)
    {
        $this->authorizeAction('contractors.update');

        $docId = $documentId ?? $this->approvalDocumentId;
        $rejectionReason = $reason ?? $this->rejectionReason;

        $this->validate([
            'rejectionReason' => 'required|string|min:10'
        ]);

        try {
            $document = ContractorDocument::findOrFail($docId);

            if ($document->contractor_id !== $this->contractor->id) {
                session()->flash('error', 'Invalid document.');
                return;
            }

            $document->reject(Auth::user(), $rejectionReason);

            // Refresh the contractor data
            $this->contractor->load('contractorDocuments');

            $this->resetApprovalModal();
            session()->flash('message', 'Document rejected. User will be notified.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error rejecting document: ' . $e->getMessage());
        }
    }

    /**
     * Toggle requires_approval flag for PM
     */
    public function toggleRequiresApproval($documentId)
    {
        $this->authorizeAction('contractors.update');

        try {
            $document = ContractorDocument::findOrFail($documentId);
            if ($document->contractor_id !== $this->contractor->id) {
                session()->flash('error', 'Invalid document.');
                return;
            }
            $document->update(['requires_approval' => !$document->requires_approval]);
            $this->contractor->load('contractorDocuments');

            session()->flash('message', 'Approval requirement updated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating approval requirement: ' . $e->getMessage());
        }
    }

    /**
     * Reset approval modal state
     */
    public function resetApprovalModal()
    {
        $this->showApprovalModal = false;
        $this->approvalDocumentId = null;
        $this->rejectionReason = '';
    }

    public function render()
    {
        return view('livewire.contractor-show')->layout('components.layouts.app');
    }
}