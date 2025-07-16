<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class HouseholdManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $selectedHousehold = null;
    public $selectedHouseholdId = null;
    public $showAddMember = false;
    public $showEditHousehold = false;
    public $showViewModal = false;

    // Add Family Member Form
    public $first_name = '';
    public $middle_name = '';
    public $last_name = '';
    public $suffix = '';
    public $date_of_birth = '';
    public $gender = '';
    public $telephone = '';
    public $email = '';
    public $relationship = '';
    
    // Edit Household Form
    public $editHouseholdType = '';
    public $editHousingStatus = '';
    public $editMonthlyIncome = '';
    public $editPrimaryPhone = '';
    public $editAddress = '';
    public $editCity = '';
    public $editState = '';
    public $editZipCode = '';
    public $editCounty = '';
    public $editArea = '';
    
    public function mount()
    {
        // Check if we're coming from HouseholdShow with an edit request
        if (session()->has('editHouseholdId')) {
            $householdId = session('editHouseholdId');
            session()->forget('editHouseholdId'); // Clean up session
            $this->selectHousehold($householdId);
            if ($this->selectedHousehold) {
                $this->showEditHouseholdForm();
            }
        }
        
        // Check if we're coming from HouseholdShow with an add member request
        if (session()->has('addMemberHouseholdId')) {
            $householdId = session('addMemberHouseholdId');
            session()->forget('addMemberHouseholdId'); // Clean up session
            $this->selectHousehold($householdId);
            if ($this->selectedHousehold) {
                $this->showAddMemberForm();
            }
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectHousehold($householdId)
    {
        try {
            $this->selectedHouseholdId = $householdId;
            $this->selectedHousehold = Household::with(['participants', 'headOfHousehold', 'currentAddress'])->find($householdId);
            
            if (!$this->selectedHousehold) {
                session()->flash('error', 'Household not found.');
                $this->selectedHouseholdId = null;
                return;
            }
            
            $this->showAddMember = false;
            $this->showEditHousehold = false;
            $this->resetMemberForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading household: ' . $e->getMessage());
            $this->selectedHouseholdId = null;
            $this->selectedHousehold = null;
        }
    }

    public function showAddMemberForm()
    {
        $this->showAddMember = true;
        $this->showEditHousehold = false;
        $this->resetMemberForm();
    }
    
    public function showEditHouseholdForm()
    {
        if (!$this->can('households.edit')) {
            session()->flash('error', 'You do not have permission to edit households.');
            return;
        }
        
        $this->showEditHousehold = true;
        $this->showAddMember = false;
        $this->resetMemberForm();
        
        // Load current household data
        if ($this->selectedHousehold) {
            $this->editHouseholdType = $this->selectedHousehold->household_type;
            $this->editHousingStatus = $this->selectedHousehold->housing_status;
            $this->editMonthlyIncome = $this->selectedHousehold->monthly_income;
            
            // Load address data from current address if exists
            if ($this->selectedHousehold->currentAddress) {
                $currentAddress = $this->selectedHousehold->currentAddress;
                $this->editPrimaryPhone = $currentAddress->primary_phone;
                $this->editAddress = $currentAddress->address;
                $this->editCity = $currentAddress->city;
                $this->editState = $currentAddress->state;
                $this->editZipCode = $currentAddress->zip_code;
                $this->editCounty = $currentAddress->county;
                $this->editArea = $currentAddress->area;
            } else {
                // Clear address fields if no current address
                $this->editPrimaryPhone = '';
                $this->editAddress = '';
                $this->editCity = '';
                $this->editState = '';
                $this->editZipCode = '';
                $this->editCounty = '';
                $this->editArea = '';
            }
        }
    }

    public function viewHousehold($householdId)
    {
        // Redirect to the household show page instead of opening a modal
        return redirect()->route('household.show', $householdId);
    }

    public function resetMemberForm()
    {
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->suffix = '';
        $this->date_of_birth = '';
        $this->gender = '';
        $this->telephone = '';
        $this->email = '';
        $this->relationship = '';
        $this->showViewModal = false;
    }
    
    public function resetEditForm()
    {
        $this->editHouseholdType = '';
        $this->editHousingStatus = '';
        $this->editMonthlyIncome = '';
        $this->editPrimaryPhone = '';
        $this->editAddress = '';
        $this->editCity = '';
        $this->editState = '';
        $this->editZipCode = '';
        $this->editCounty = '';
        $this->editArea = '';
        $this->showEditHousehold = false;
    }

    public function addFamilyMember()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string',
        ]);

        if (!$this->selectedHousehold) {
            session()->flash('error', 'No household selected.');
            return;
        }

        try {
            Participant::create([
                'household_id' => $this->selectedHousehold->id,
                'is_head_of_household' => false,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'date_of_birth' => $this->date_of_birth,
                'gender' => $this->gender,
                'telephone' => $this->telephone,
                'email' => $this->email,
            ]);

            // Update household size using smart logic
            $this->selectedHousehold->updateHouseholdSizeLogic();
            $this->selectedHousehold->refresh();

            session()->flash('success', 'Family member added successfully.');
            $this->showAddMember = false;
            $this->resetMemberForm();
            $this->selectHousehold($this->selectedHousehold->id);
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function removeParticipant($participantId)
    {
        try {
            $participant = Participant::find($participantId);
            
            if ($participant && $participant->household_id === $this->selectedHousehold->id) {
                if ($participant->is_head_of_household) {
                    session()->flash('error', 'Cannot remove head of household.');
                    return;
                }

                $participant->delete();
                // After removing a participant, always update to actual count
                $this->selectedHousehold->update(['household_size' => $this->selectedHousehold->getActualHouseholdSize()]);
                $this->selectedHousehold->refresh();
                
                session()->flash('success', 'Family member removed successfully.');
                $this->selectHousehold($this->selectedHousehold->id);
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function updateHousehold()
    {
        if (!$this->can('households.edit')) {
            session()->flash('error', 'You do not have permission to edit households.');
            return;
        }
        
        $this->validate([
            'editHouseholdType' => 'required|string',
            'editHousingStatus' => 'nullable|string',
            'editMonthlyIncome' => 'nullable|numeric|min:0',
            'editPrimaryPhone' => 'nullable|string|max:20',
            'editAddress' => 'required|string|max:255',
            'editCity' => 'required|string|max:100',
            'editState' => 'required|string|size:2',
            'editZipCode' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
            'editCounty' => 'nullable|string|max:100',
            'editArea' => 'nullable|string|max:100',
        ]);
        
        if (!$this->selectedHousehold) {
            session()->flash('error', 'No household selected.');
            return;
        }
        
        try {
            // Update household basic info
            $this->selectedHousehold->update([
                'household_type' => $this->editHouseholdType,
                'housing_status' => $this->editHousingStatus,
                'monthly_income' => $this->editMonthlyIncome,
            ]);
            
            // Check if address has changed
            $currentAddress = $this->selectedHousehold->currentAddress;
            $addressChanged = false;
            
            if ($currentAddress) {
                $addressChanged = 
                    $currentAddress->address !== $this->editAddress ||
                    $currentAddress->city !== $this->editCity ||
                    $currentAddress->state !== $this->editState ||
                    $currentAddress->zip_code !== $this->editZipCode ||
                    $currentAddress->county !== $this->editCounty ||
                    $currentAddress->area !== $this->editArea ||
                    $currentAddress->primary_phone !== $this->editPrimaryPhone;
            } else {
                // No current address, so if any address field is filled, it's changed
                $addressChanged = $this->editAddress || $this->editCity || $this->editState || $this->editZipCode;
            }
            
            // Create new address record if changed
            if ($addressChanged) {
                $this->selectedHousehold->updateAddress([
                    'address' => $this->editAddress,
                    'city' => $this->editCity,
                    'state' => $this->editState,
                    'zip_code' => $this->editZipCode,
                    'county' => $this->editCounty,
                    'area' => $this->editArea,
                    'primary_phone' => $this->editPrimaryPhone,
                ], 'Manual update via household management');
            }
            
            // Recalculate poverty level if income changed
            if ($this->selectedHousehold->monthly_income !== null) {
                $annualIncome = $this->selectedHousehold->monthly_income * 12;
                $povertyGuideline = \App\Models\PovertyGuideline::getCurrentGuideline(
                    $this->selectedHousehold->household_size,
                    $this->selectedHousehold->state
                );
                
                if ($povertyGuideline) {
                    $povertyPercentage = round(($annualIncome / $povertyGuideline->annual_income_threshold) * 100, 2);
                    $this->selectedHousehold->update(['hhs_poverty_percentage' => $povertyPercentage]);
                }
            }
            
            session()->flash('success', 'Household information updated successfully.');
            $this->showEditHousehold = false;
            $this->resetEditForm();
            $this->selectHousehold($this->selectedHousehold->id);
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
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
