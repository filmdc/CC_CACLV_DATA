<?php

namespace App\Livewire;

use App\Traits\WithPermissionChecks;
use Livewire\Component;

class ParticipantHouseholdHub extends Component
{
    use WithPermissionChecks;

    public function mount()
    {
        // Check if user has any relevant permissions
        $this->authorizeAny([
            'participants.view',
            'participants.create',
            'households.view',
            'enrollments.view'
        ]);
    }

    public function render()
    {
        return view('livewire.participant-household-hub');
    }

    public function canViewIntake()
    {
        return $this->can('participants.create');
    }

    public function canViewDuplicates()
    {
        return $this->can('participants.view') || $this->can('participants.create');
    }

    public function canViewSearch()
    {
        return $this->can('participants.view') || $this->can('households.view');
    }

    public function canViewEnrollments()
    {
        return $this->can('enrollments.view');
    }
}