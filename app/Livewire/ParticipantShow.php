<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Livewire\Component;

class ParticipantShow extends Component
{
    use WithPermissionChecks, WithProgramAccess;

    public $participant;
    public $participantId;
    public $canViewSensitiveData = false;

    public function mount($id)
    {
        // Check if user has permission to view participants
        $this->authorizeAction('participants.view', 'You do not have permission to view participants.');

        $this->participantId = $id;
        $this->participant = Participant::with([
            'household.headOfHousehold',
            'household.currentAddress',
            'programEnrollments.program',
            'serviceEnrollments.service'
        ])->findOrFail($id);

        // Check if user can view sensitive data
        $this->canViewSensitiveData = $this->canViewSensitiveData($this->participant);
    }

    public function editParticipant()
    {
        // Check if user can edit this participant
        if (!auth()->user()->can('update', $this->participant)) {
            session()->flash('error', 'You do not have permission to edit this participant.');
            return;
        }

        return redirect()->route('participant.management')->with('selectedParticipant', $this->participantId);
    }

    public function render()
    {
        // Get reference data for display
        $referenceData = [
            'genders' => ReferenceData::getOptions('gender'),
            'ethnicities' => ReferenceData::getOptions('ethnicity'),
            'races' => ReferenceData::getOptions('race'),
            'education_levels' => ReferenceData::getOptions('education_level'),
            'disability_statuses' => ReferenceData::getOptions('disability_status'),
            'health_insurance_statuses' => ReferenceData::getOptions('health_insurance_status'),
            'military_statuses' => ReferenceData::getOptions('military_status'),
            'work_statuses' => ReferenceData::getOptions('work_status'),
        ];

        // Filter enrollments to only show those in user's programs (if not admin)
        $programEnrollments = $this->participant->programEnrollments;
        $serviceEnrollments = $this->participant->serviceEnrollments;

        if (!$this->canViewSensitiveData) {
            // Non-admin users without program access see no enrollments
            $programEnrollments = collect();
            $serviceEnrollments = collect();
        } elseif (!auth()->user()->isAdmin()) {
            // Filter to only user's programs
            $userProgramIds = auth()->user()->getAccessibleProgramIds();

            $programEnrollments = $programEnrollments->filter(function ($enrollment) use ($userProgramIds) {
                return in_array($enrollment->program_id, $userProgramIds);
            });

            $serviceEnrollments = $serviceEnrollments->filter(function ($enrollment) use ($userProgramIds) {
                return in_array($enrollment->service->program_id ?? null, $userProgramIds);
            });
        }

        return view('livewire.participant-show', [
            'referenceData' => $referenceData,
            'programEnrollments' => $programEnrollments,
            'serviceEnrollments' => $serviceEnrollments,
        ])->layout('components.layouts.app');
    }
}
