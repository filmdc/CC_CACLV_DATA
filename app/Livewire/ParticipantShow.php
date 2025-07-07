<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class ParticipantShow extends Component
{
    use WithPermissionChecks;

    public $participant;
    public $participantId;

    public function mount($id)
    {
        // Check if user has permission to view participants
        $this->authorizeAction('participants.view', 'You do not have permission to view participants.');
        
        $this->participantId = $id;
        $this->participant = Participant::with([
            'household.headOfHousehold',
            'programEnrollments.program',
            'serviceEnrollments.service'
        ])->findOrFail($id);
    }

    public function editParticipant()
    {
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

        return view('livewire.participant-show', [
            'referenceData' => $referenceData
        ])->layout('components.layouts.app');
    }
}
