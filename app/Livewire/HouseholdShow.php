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
    public $backUrl;

    public function mount($id)
    {
        // Check if user has permission to view households
        $this->authorizeAction('households.view', 'You do not have permission to view households.');
        
        $this->householdId = $id;
        $this->household = Household::with([
            'participants',
            'headOfHousehold',
            'currentAddress',
            'addresses' => function($query) {
                $query->orderBy('effective_date', 'desc')->limit(5);
            }
        ])->findOrFail($id);
        
        // Get the referrer URL from session, or default to household management
        $this->backUrl = session('household_referrer', route('household.management'));
    }

    public function editHousehold()
    {
        // Use session (not flash) to persist across redirect
        session(['editHouseholdId' => $this->householdId]);
        return redirect()->route('household.management');
    }
    
    public function addMember()
    {
        // Use session (not flash) to persist across redirect
        session(['addMemberHouseholdId' => $this->householdId]);
        return redirect()->route('household.management');
    }

    public function render()
    {
        return view('livewire.household-show')->layout('components.layouts.app');
    }
}