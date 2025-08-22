<?php

namespace App\Livewire;

use App\Models\Household;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class HouseholdManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function manageHousehold($householdId)
    {
        // Store the current URL as referrer for navigation back
        session(['household_referrer' => request()->fullUrl()]);
        
        // Redirect to the household show page
        return redirect()->route('household.show', $householdId);
    }


    public function viewHousehold($householdId)
    {
        // Redirect to the household show page instead of opening a modal
        return redirect()->route('household.show', $householdId);
    }




    public function render()
    {
        $households = Household::with([
                'headOfHousehold',
                'participants.programEnrollments.program',
                'participants.serviceEnrollments.service',
                'currentAddress'
            ])
            ->when($this->search, function ($query) {
                $query->where('household_code', 'like', '%' . $this->search . '%')
                      ->orWhereHas('headOfHousehold', function ($q) {
                          $q->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.household-management', [
            'households' => $households
        ])->layout('components.layouts.app');
    }
}
