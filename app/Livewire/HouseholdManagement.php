<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

class HouseholdManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    
    // Quick edit modal
    public $showQuickEditModal = false;
    public $editingHouseholdId = null;
    
    // Quick edit fields
    #[Validate('required|string')]
    public $quickEditHouseholdType = '';
    
    #[Validate('required|integer|min:1')]
    public $quickEditHouseholdSize = 1;
    
    #[Validate('required|string')]
    public $quickEditHousingStatus = '';
    
    #[Validate('nullable|numeric|min:0')]
    public $quickEditMonthlyIncome = '';
    

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

    public function quickEditHousehold($householdId)
    {
        $this->authorizeAction('households.edit', 'You do not have permission to edit households.');
        
        $household = Household::findOrFail($householdId);
        
        // Populate quick edit fields
        $this->editingHouseholdId = $household->id;
        $this->quickEditHouseholdType = $household->household_type;
        $this->quickEditHouseholdSize = $household->household_size;
        $this->quickEditHousingStatus = $household->housing_status;
        $this->quickEditMonthlyIncome = $household->monthly_income;
        
        $this->showQuickEditModal = true;
    }
    
    public function saveQuickEdit()
    {
        $this->validate([
            'quickEditHouseholdType' => 'required|string',
            'quickEditHouseholdSize' => 'required|integer|min:1',
            'quickEditHousingStatus' => 'required|string',
            'quickEditMonthlyIncome' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $household = Household::findOrFail($this->editingHouseholdId);
            $annual_income = $this->quickEditMonthlyIncome ? $this->quickEditMonthlyIncome * 12 : null;
            
            $household->update([
                'household_type' => $this->quickEditHouseholdType,
                'household_size' => $this->quickEditHouseholdSize,
                'housing_status' => $this->quickEditHousingStatus,
                'monthly_income' => $this->quickEditMonthlyIncome ?: null,
                'annual_income' => $annual_income,
            ]);
            
            $this->showQuickEditModal = false;
            $this->resetQuickEditForm();
            session()->flash('success', 'Household updated successfully.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating household: ' . $e->getMessage());
        }
    }
    
    public function cancelQuickEdit()
    {
        $this->showQuickEditModal = false;
        $this->resetQuickEditForm();
    }
    
    public function resetQuickEditForm()
    {
        $this->editingHouseholdId = null;
        $this->quickEditHouseholdType = '';
        $this->quickEditHouseholdSize = 1;
        $this->quickEditHousingStatus = '';
        $this->quickEditMonthlyIncome = '';
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
            'households' => $households,
            'householdTypes' => ReferenceData::getOptions('household_type'),
            'housingStatuses' => ReferenceData::getOptions('housing_status'),
        ])->layout('components.layouts.app');
    }
}
