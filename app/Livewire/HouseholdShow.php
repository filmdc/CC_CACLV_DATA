<?php

namespace App\Livewire;

use App\Models\Household;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class HouseholdShow extends Component
{
    use WithPermissionChecks;

    public $household;
    public $householdId;

    public function mount($id)
    {
        // Check if user has permission to view households
        $this->authorizeAction('households.view', 'You do not have permission to view households.');
        
        $this->householdId = $id;
        $this->household = Household::with([
            'participants',
            'headOfHousehold'
        ])->findOrFail($id);
    }

    public function editHousehold()
    {
        return redirect()->route('household.management')->with('selectedHousehold', $this->householdId);
    }

    public function render()
    {
        return view('livewire.household-show')->layout('components.layouts.app');
    }
}