<?php

namespace App\Livewire;

use App\Models\Property;
use App\Models\PropertyDocument;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PropertyShow extends Component
{
    use WithPermissionChecks;

    public $property;
    public $propertyId;

    // Approval modal state
    public $showApprovalModal = false;
    public $approvalDocumentId = null;
    public $rejectionReason = '';

    public function mount($id)
    {
        // Check if user has permission to view properties
        $this->authorizeAction('properties.view', 'You do not have permission to view properties.');
        
        $this->propertyId = $id;
        $this->property = Property::with([
            'participant',
            'household',
            'program',
            'housingJobs.contractor',
            'propertyNotes',
            'propertyDocuments'
        ])->findOrFail($id);
    }

    public function editProperty()
    {
        return redirect()->route('property.management')->with('selectedProperty', $this->propertyId);
    }

    /**
     * Approve a property document
     */
    public function approveDocument($documentId)
    {
        $this->authorizeAction('properties.update');

        try {
            $document = PropertyDocument::findOrFail($documentId);

            if ($document->property_id !== $this->property->id) {
                session()->flash('error', 'Invalid document.');
                return;
            }

            $document->approve(Auth::user());

            // Refresh the property data
            $this->property->load('propertyDocuments');

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
     * Reject a property document with reason
     */
    public function rejectDocument($documentId = null, $reason = null)
    {
        $this->authorizeAction('properties.update');

        $docId = $documentId ?? $this->approvalDocumentId;
        $rejectionReason = $reason ?? $this->rejectionReason;

        $this->validate([
            'rejectionReason' => 'required|string|min:10'
        ]);

        try {
            $document = PropertyDocument::findOrFail($docId);

            if ($document->property_id !== $this->property->id) {
                session()->flash('error', 'Invalid document.');
                return;
            }

            $document->reject(Auth::user(), $rejectionReason);

            // Refresh the property data
            $this->property->load('propertyDocuments');

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
        $this->authorizeAction('properties.update');

        try {
            $document = PropertyDocument::findOrFail($documentId);
            if ($document->property_id !== $this->property->id) {
                session()->flash('error', 'Invalid document.');
                return;
            }
            $document->update(['requires_approval' => !$document->requires_approval]);
            $this->property->load('propertyDocuments');

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
        return view('livewire.property-show')->layout('components.layouts.app');
    }
}