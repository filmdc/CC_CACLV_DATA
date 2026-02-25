<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalSubmission;
use App\Models\ApprovalType;
use App\Models\PendingApproval;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmitRequest extends Component
{
    use WithFileUploads, WithPermissionChecks;

    public $step = 1;
    public $approvalTypeId = null;
    public $title = '';
    public $description = '';
    public $submitterNotes = '';
    public $programId = null;
    public $attachments = [];

    public function mount()
    {
        $this->authorizeAction('approvals.submit');
    }

    public function getApprovalTypeProperty(): ?ApprovalType
    {
        if (! $this->approvalTypeId) {
            return null;
        }

        return ApprovalType::find($this->approvalTypeId);
    }

    public function selectType($typeId)
    {
        $this->approvalTypeId = $typeId;
        $this->step = 2;
    }

    public function goToStep($step)
    {
        if ($step < $this->step || $this->validateCurrentStep()) {
            $this->step = $step;
        }
    }

    public function nextStep()
    {
        if ($this->validateCurrentStep()) {
            $this->step++;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    protected function validateCurrentStep(): bool
    {
        if ($this->step === 1) {
            return $this->approvalTypeId !== null;
        }

        if ($this->step === 2) {
            $this->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'submitterNotes' => 'nullable|string|max:2000',
                'programId' => 'nullable|exists:programs,id',
            ]);
            return true;
        }

        if ($this->step === 3) {
            $approvalType = $this->approvalType;
            if ($approvalType && $approvalType->requires_document && empty($this->attachments)) {
                $this->addError('attachments', 'This approval type requires at least one attachment.');
                return false;
            }
            return true;
        }

        return true;
    }

    public function removeAttachment($index)
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function submit()
    {
        $approvalType = $this->approvalType;

        $rules = [
            'approvalTypeId' => 'required|exists:approval_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'submitterNotes' => 'nullable|string|max:2000',
            'programId' => 'nullable|exists:programs,id',
        ];

        if ($approvalType && $approvalType->requires_document) {
            $rules['attachments'] = 'required|array|min:1';
            $rules['attachments.*'] = 'file|max:102400';
        } else {
            $rules['attachments'] = 'nullable|array';
            $rules['attachments.*'] = 'file|max:102400';
        }

        $this->validate($rules);

        $submission = ApprovalSubmission::create([
            'approval_type_id' => $this->approvalTypeId,
            'title' => $this->title,
            'description' => $this->description,
            'submitter_notes' => $this->submitterNotes,
            'program_id' => $this->programId,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'status' => 'draft',
        ]);

        foreach ($this->attachments as $attachment) {
            $submission->addMedia($attachment->getRealPath())
                ->usingFileName($attachment->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $submission->submit();

        PendingApproval::create([
            'approvable_type' => get_class($submission),
            'approvable_id' => $submission->id,
            'submitted_by' => Auth::id(),
            'approval_type_id' => $this->approvalTypeId,
            'program_id' => $this->programId,
            'item_type' => 'approval_submission',
            'item_name' => $this->title,
            'item_description' => $this->description,
            'submitter_notes' => $this->submitterNotes,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        session()->flash('message', 'Your request has been submitted for approval.');
        return redirect()->route('mobile.my-submissions');
    }

    public function render()
    {
        $approvalTypes = ApprovalType::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        $programs = Program::orderBy('program_name')->get();

        return view('livewire.mobile.submit-request', [
            'approvalTypes' => $approvalTypes,
            'programs' => $programs,
        ])->layout('components.layouts.approvals');
    }
}
