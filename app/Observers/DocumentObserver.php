<?php

namespace App\Observers;

use App\Models\ApprovalHistory;
use App\Models\PendingApproval;
use App\Models\PropertyDocument;
use App\Models\ContractorDocument;
use App\Models\JobDocumentChecklist;
use App\Notifications\DocumentResubmittedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentObserver
{
    /**
     * Handle the "created" event for documents.
     */
    public function created(Model $document): void
    {
        // If document requires approval, create a pending approval record
        if ($this->requiresApproval($document) && $this->hasUser()) {
            $this->createPendingApproval($document);
        }
    }

    /**
     * Handle the "updating" event for documents.
     * This fires BEFORE the update is saved to the database.
     */
    public function updating(Model $document): void
    {
        $original = $document->getOriginal();

        // Check if file was reuploaded
        if ($this->isFileReuploaded($document, $original)) {
            // If previous status was rejected, reset to pending
            if ($original['approval_status'] === 'rejected' && $this->requiresApproval($document)) {
                $document->approval_status = 'pending';
                $document->approved_by = null;
                $document->approved_at = null;
                $document->rejection_reason = null;

                // Increment version
                if (isset($document->document_version)) {
                    $document->document_version = ($original['document_version'] ?? 1) + 1;
                }
            }
        }
    }

    /**
     * Handle the "updated" event for documents.
     * This fires AFTER the update is saved to the database.
     */
    public function updated(Model $document): void
    {
        $changes = $document->getChanges();
        $original = $document->getRawOriginal();

        // Check if file was reuploaded and status was reset to pending
        if ($this->isFileReuploaded($document, $original) &&
            $original['approval_status'] === 'rejected' &&
            $document->approval_status === 'pending') {

            // Log the resubmission
            if ($this->hasUser()) {
                ApprovalHistory::logResubmission(
                    $document,
                    Auth::user(),
                    $this->getHousingJob($document),
                    "Document reuploaded after rejection",
                    ['previous_version' => $original['document_version'] ?? 1]
                );

                // Update or create pending approval
                $this->updatePendingApproval($document, $original);

                // Notify the original approver
                $this->notifyOriginalApprover($document, $original);
            }
        }
    }

    /**
     * Check if the document requires approval.
     */
    protected function requiresApproval(Model $document): bool
    {
        return isset($document->requires_approval) && $document->requires_approval;
    }

    /**
     * Check if a user is authenticated.
     */
    protected function hasUser(): bool
    {
        return Auth::check();
    }

    /**
     * Check if file was reuploaded.
     */
    protected function isFileReuploaded(Model $document, array $original): bool
    {
        return isset($document->file_path) &&
               isset($original['file_path']) &&
               $document->file_path !== $original['file_path'];
    }

    /**
     * Create a pending approval record.
     */
    protected function createPendingApproval(Model $document): void
    {
        $housingJob = $this->getHousingJob($document);

        PendingApproval::create([
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'housing_job_id' => $housingJob?->id,
            'item_type' => $this->getDocumentType($document),
            'item_name' => $document->document_name ?? 'Unnamed Document',
            'item_description' => $document->document_description ?? null,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'status' => 'pending',
        ]);
    }

    /**
     * Update or create pending approval for resubmission.
     */
    protected function updatePendingApproval(Model $document, array $original): void
    {
        $housingJob = $this->getHousingJob($document);

        // Cancel old pending approval if exists
        PendingApproval::where('approvable_type', get_class($document))
            ->where('approvable_id', $document->id)
            ->where('status', 'rejected')
            ->update(['status' => 'cancelled']);

        // Create new pending approval
        PendingApproval::create([
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'housing_job_id' => $housingJob?->id,
            'item_type' => $this->getDocumentType($document),
            'item_name' => $document->document_name ?? 'Unnamed Document',
            'item_description' => "Resubmitted (v{$document->document_version})",
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'approver_id' => $original['approved_by'] ?? null,
            'status' => 'pending',
            'priority' => 1, // Mark as high priority since it's a resubmission
        ]);
    }

    /**
     * Notify the original approver about resubmission.
     */
    protected function notifyOriginalApprover(Model $document, array $original): void
    {
        if (!empty($original['approved_by'])) {
            $approver = \App\Models\User::find($original['approved_by']);

            if ($approver) {
                $approver->notify(new DocumentResubmittedNotification($document, Auth::user()));
            }
        }
    }

    /**
     * Get the housing job associated with this document.
     */
    protected function getHousingJob(Model $document): ?object
    {
        if ($document instanceof PropertyDocument) {
            return $document->property?->housingJobs()->latest()->first();
        }

        if ($document instanceof ContractorDocument) {
            return $document->contractor?->housingJobs()->latest()->first();
        }

        if ($document instanceof JobDocumentChecklist) {
            return $document->housingJob;
        }

        return null;
    }

    /**
     * Get the document type label.
     */
    protected function getDocumentType(Model $document): string
    {
        return match (get_class($document)) {
            PropertyDocument::class => 'property_document',
            ContractorDocument::class => 'contractor_document',
            JobDocumentChecklist::class => 'job_document',
            default => 'document',
        };
    }
}
