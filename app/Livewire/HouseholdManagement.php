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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectHousehold($householdId)
    {
        $this->selectedHousehold = Household::with(['participants', 'headOfHousehold', 'familyMembers'])->find($householdId);
        $this->showAddMember = false;
        $this->showEditHousehold = false;
        $this->resetMemberForm();
    }

    public function showAddMemberForm()
    {
        $this->showAddMember = true;
        $this->showEditHousehold = false;
        $this->resetMemberForm();
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

    public function render()
    {
        $households = Household::with(['headOfHousehold'])
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
