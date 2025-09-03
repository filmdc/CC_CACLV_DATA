<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Carbon\Carbon;

class HouseholdShow extends Component
{
    use WithPermissionChecks;

    public $household;
    public $householdId;
    public $backUrl;
    
    // Edit modals
    public $showEditHouseholdModal = false;
    public $showAddMemberModal = false;
    public $showEditMemberModal = false;
    
    // Household edit fields
    #[Validate('required|string')]
    public $editHouseholdType = '';
    
    #[Validate('required|integer|min:1')]
    public $editHouseholdSize = 1;
    
    #[Validate('required|string')]
    public $editHousingStatus = '';
    
    #[Validate('nullable|numeric|min:0')]
    public $editMonthlyIncome = '';
    
    #[Validate('nullable|string|max:255')]
    public $editStreetAddress = '';
    
    #[Validate('nullable|string|max:100')]
    public $editCity = '';
    
    #[Validate('nullable|string|size:2')]
    public $editState = '';
    
    #[Validate('nullable|string|max:10')]
    public $editZipCode = '';
    
    public $editNotes = '';
    
    // Member edit fields
    public $editingMemberId = null;
    
    #[Validate('required|string|max:255')]
    public $memberFirstName = '';
    
    #[Validate('nullable|string|max:255')]
    public $memberMiddleName = '';
    
    #[Validate('required|string|max:255')]
    public $memberLastName = '';
    
    #[Validate('nullable|string|max:50')]
    public $memberSuffix = '';
    
    #[Validate('required|date|before:today')]
    public $memberDateOfBirth = '';
    
    #[Validate('required|string')]
    public $memberGender = '';
    
    #[Validate('nullable|string|max:20')]
    public $memberTelephone = '';
    
    #[Validate('nullable|email|max:255')]
    public $memberEmail = '';
    
    public $memberRelationship = '';

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
    
    public function goBack()
    {
        return redirect($this->backUrl);
    }

    public function editHousehold()
    {
        $this->authorizeAction('households.edit', 'You do not have permission to edit households.');
        
        // Populate edit fields with current household data
        $this->editHouseholdType = $this->household->household_type;
        $this->editHouseholdSize = $this->household->household_size;
        $this->editHousingStatus = $this->household->housing_status;
        $this->editMonthlyIncome = $this->household->monthly_income;
        $this->editNotes = $this->household->notes;
        
        // Populate address fields from current address
        if ($this->household->currentAddress) {
            $this->editStreetAddress = $this->household->currentAddress->address;
            $this->editCity = $this->household->currentAddress->city;
            $this->editState = $this->household->currentAddress->state;
            $this->editZipCode = $this->household->currentAddress->zip_code;
        }
        
        $this->showEditHouseholdModal = true;
    }
    
    public function saveHouseholdEdit()
    {
        $this->validate([
            'editHouseholdType' => 'required|string',
            'editHouseholdSize' => 'required|integer|min:1',
            'editHousingStatus' => 'required|string',
            'editMonthlyIncome' => 'nullable|numeric|min:0',
            'editStreetAddress' => 'nullable|string|max:255',
            'editCity' => 'nullable|string|max:100',
            'editState' => 'nullable|string|size:2',
            'editZipCode' => 'nullable|string|max:10',
        ]);
        
        try {
            // Update household
            $annual_income = $this->editMonthlyIncome ? $this->editMonthlyIncome * 12 : null;
            
            $this->household->update([
                'household_type' => $this->editHouseholdType,
                'household_size' => $this->editHouseholdSize,
                'housing_status' => $this->editHousingStatus,
                'monthly_income' => $this->editMonthlyIncome ?: null,
                'annual_income' => $annual_income,
                'notes' => $this->editNotes,
            ]);
            
            // Update or create address if any address data provided
            if ($this->editStreetAddress || $this->editCity || $this->editState || $this->editZipCode) {
                if ($this->household->currentAddress) {
                    $this->household->currentAddress->update([
                        'address' => $this->editStreetAddress,
                        'city' => $this->editCity,
                        'state' => $this->editState,
                        'zip_code' => $this->editZipCode,
                    ]);
                } else {
                    $this->household->addresses()->create([
                        'address' => $this->editStreetAddress,
                        'city' => $this->editCity,
                        'state' => $this->editState,
                        'zip_code' => $this->editZipCode,
                        'is_current' => true,
                        'effective_date' => now()->format('Y-m-d'),
                        'reason_for_change' => 'Updated via household edit',
                    ]);
                }
            }
            
            $this->household->refresh();
            $this->showEditHouseholdModal = false;
            session()->flash('success', 'Household updated successfully.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating household: ' . $e->getMessage());
        }
    }
    
    public function cancelHouseholdEdit()
    {
        $this->showEditHouseholdModal = false;
        $this->resetHouseholdEditForm();
    }
    
    public function resetHouseholdEditForm()
    {
        $this->editHouseholdType = '';
        $this->editHouseholdSize = 1;
        $this->editHousingStatus = '';
        $this->editMonthlyIncome = '';
        $this->editStreetAddress = '';
        $this->editCity = '';
        $this->editState = '';
        $this->editZipCode = '';
        $this->editNotes = '';
    }
    
    public function addMember()
    {
        $this->authorizeAction('participants.create', 'You do not have permission to add household members.');
        $this->resetMemberForm();
        $this->showAddMemberModal = true;
    }
    
    public function saveMember()
    {
        $this->validate([
            'memberFirstName' => 'required|string|max:255',
            'memberLastName' => 'required|string|max:255',
            'memberDateOfBirth' => 'required|date|before:today',
            'memberGender' => 'required|string',
            'memberRelationship' => 'required|string',
        ]);
        
        try {
            // Get the active reference data set
            $activeDataSet = \App\Models\ReferenceDataSet::getActive();
            
            // Create new household member
            Participant::create([
                'household_id' => $this->household->id,
                'reference_data_set_id' => $activeDataSet?->id,
                'is_head_of_household' => false,
                'first_name' => $this->memberFirstName,
                'middle_name' => $this->memberMiddleName,
                'last_name' => $this->memberLastName,
                'suffix' => $this->memberSuffix,
                'date_of_birth' => $this->memberDateOfBirth,
                'gender' => $this->memberGender,
                'telephone' => $this->memberTelephone,
                'email' => $this->memberEmail,
                'relationship_to_head' => $this->memberRelationship,
                'intake_date' => Carbon::today(),
            ]);
            
            // Update household size
            $this->household->updateHouseholdSizeLogic();
            $this->household->refresh();
            
            $this->showAddMemberModal = false;
            $this->resetMemberForm();
            session()->flash('success', 'Household member added successfully.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding household member: ' . $e->getMessage());
        }
    }
    
    public function editMember($memberId)
    {
        $this->authorizeAction('participants.edit', 'You do not have permission to edit participants.');
        
        $member = Participant::findOrFail($memberId);
        
        // Verify member belongs to this household
        if ($member->household_id !== $this->household->id) {
            session()->flash('error', 'Member does not belong to this household.');
            return;
        }
        
        // Populate edit form
        $this->editingMemberId = $member->id;
        $this->memberFirstName = $member->first_name;
        $this->memberMiddleName = $member->middle_name;
        $this->memberLastName = $member->last_name;
        $this->memberSuffix = $member->suffix;
        $this->memberDateOfBirth = $member->date_of_birth->format('Y-m-d');
        $this->memberGender = $member->gender;
        $this->memberTelephone = $member->telephone;
        $this->memberEmail = $member->email;
        $this->memberRelationship = $member->relationship_to_head;
        
        $this->showEditMemberModal = true;
    }
    
    public function updateMember()
    {
        $this->validate([
            'memberFirstName' => 'required|string|max:255',
            'memberLastName' => 'required|string|max:255',
            'memberDateOfBirth' => 'required|date|before:today',
            'memberGender' => 'required|string',
            'memberRelationship' => 'required|string',
        ]);
        
        try {
            $member = Participant::findOrFail($this->editingMemberId);
            
            // Verify member belongs to this household
            if ($member->household_id !== $this->household->id) {
                session()->flash('error', 'Member does not belong to this household.');
                return;
            }
            
            $member->update([
                'first_name' => $this->memberFirstName,
                'middle_name' => $this->memberMiddleName,
                'last_name' => $this->memberLastName,
                'suffix' => $this->memberSuffix,
                'date_of_birth' => $this->memberDateOfBirth,
                'gender' => $this->memberGender,
                'telephone' => $this->memberTelephone,
                'email' => $this->memberEmail,
                'relationship_to_head' => $this->memberRelationship,
            ]);
            
            $this->household->refresh();
            $this->showEditMemberModal = false;
            $this->resetMemberForm();
            session()->flash('success', 'Household member updated successfully.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating household member: ' . $e->getMessage());
        }
    }
    
    public function cancelMemberEdit()
    {
        $this->showAddMemberModal = false;
        $this->showEditMemberModal = false;
        $this->resetMemberForm();
    }
    
    public function resetMemberForm()
    {
        $this->editingMemberId = null;
        $this->memberFirstName = '';
        $this->memberMiddleName = '';
        $this->memberLastName = '';
        $this->memberSuffix = '';
        $this->memberDateOfBirth = '';
        $this->memberGender = '';
        $this->memberTelephone = '';
        $this->memberEmail = '';
        $this->memberRelationship = '';
    }
    
    public function removeMember($memberId)
    {
        $this->authorizeAction('participants.delete', 'You do not have permission to remove household members.');
        
        try {
            $member = Participant::findOrFail($memberId);
            
            // Verify member belongs to this household
            if ($member->household_id !== $this->household->id) {
                session()->flash('error', 'Member does not belong to this household.');
                return;
            }
            
            // Prevent removal of head of household
            if ($member->is_head_of_household) {
                session()->flash('error', 'Cannot remove the head of household.');
                return;
            }
            
            $memberName = $member->first_name . ' ' . $member->last_name;
            $member->delete();
            
            // Update household size
            $this->household->updateHouseholdSizeLogic();
            $this->household->refresh();
            
            session()->flash('success', 'Household member "' . $memberName . '" removed successfully.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error removing household member: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.household-show', [
            'genderOptions' => ReferenceData::getOptions('gender'),
            'householdTypes' => ReferenceData::getOptions('household_type'),
            'housingStatuses' => ReferenceData::getOptions('housing_status'),
        ])->layout('components.layouts.app');
    }
}