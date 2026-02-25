<?php

namespace App\Livewire;

use App\Models\ApprovalSubmission;
use App\Models\ApprovalType;
use App\Models\PendingApproval;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ApprovalSubmissionCreate extends Component
{
    use WithFileUploads, WithPermissionChecks;

    public $approvalTypeId = null;

    public $title = '';

    public $description = '';

    public $submitterNotes = '';

    public $programId = null;

    public $attachments = [];

    public $preselectedType = null;

    public function mount($type = null)
    {
        $this->authorizeAction('approvals.submit');

        if ($type) {
            $approvalType = ApprovalType::where('name', $type)->where('is_active', true)->first();
            if ($approvalType) {
                $this->preselectedType = $approvalType->id;
                $this->approvalTypeId = $approvalType->id;
            }
        }
    }

    public function getApprovalTypeProperty(): ?ApprovalType
    {
        if (! $this->approvalTypeId) {
            return null;
        }

        return ApprovalType::find($this->approvalTypeId);
    }

    public function save()
    {
        $rules = [
            'approvalTypeId' => 'required|exists:approval_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'submitterNotes' => 'nullable|string|max:2000',
            'programId' => 'nullable|exists:programs,id',
        ];

        // If the selected type requires a document, attachments are required
        if ($this->approvalType && $this->approvalType->requires_document) {
            $rules['attachments'] = 'required|array|min:1';
            $rules['attachments.*'] = 'file|max:102400'; // 100MB max per file
        } else {
            $rules['attachments'] = 'nullable|array';
            $rules['attachments.*'] = 'file|max:102400';
        }

        $this->validate($rules, [
            'approvalTypeId.required' => 'Please select an approval type.',
            'title.required' => 'Please provide a title for your submission.',
            'attachments.required' => 'This approval type requires at least one attachment.',
        ]);

        try {
            $submission = ApprovalSubmission::create([
                'approval_type_id' => $this->approvalTypeId,
                'title' => $this->title,
                'description' => $this->description,
                'submitter_notes' => $this->submitterNotes,
                'program_id' => $this->programId ?: null,
                'status' => 'draft',
                'submitted_by' => Auth::id(),
            ]);

            // Attach uploaded files via Spatie MediaLibrary
            if (! empty($this->attachments)) {
                foreach ($this->attachments as $attachment) {
                    $submission->addMedia($attachment->getRealPath())
                        ->usingFileName($attachment->getClientOriginalName())
                        ->toMediaCollection('attachments');
                }
            }

            // Create PendingApproval record
            $approvalType = ApprovalType::find($this->approvalTypeId);

            PendingApproval::create([
                'approvable_type' => ApprovalSubmission::class,
                'approvable_id' => $submission->id,
                'submitted_by' => Auth::id(),
                'program_id' => $this->programId ?: null,
                'approval_type_id' => $this->approvalTypeId,
                'current_chain_step' => 1,
                'item_type' => $approvalType->name,
                'item_name' => $this->title,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Transition submission from draft to pending
            $submission->submit();

            session()->flash('success', 'Your approval request has been submitted successfully.');

            return redirect()->route('approvals.my-submissions');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while submitting: '.$e->getMessage());
        }
    }

    public function render()
    {
        $approvalTypes = ApprovalType::active()
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get();

        $programs = Program::active()
            ->regularPrograms()
            ->orderBy('name')
            ->get();

        return view('livewire.approval-submission-create', [
            'approvalTypes' => $approvalTypes,
            'programs' => $programs,
        ])->layout('components.layouts.app');
    }
}
