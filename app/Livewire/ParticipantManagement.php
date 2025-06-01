<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Household;
use App\Models\ReferenceData;
use Livewire\Component;
use Livewire\WithPagination;

class ParticipantManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedParticipant = null;
    public $showEditForm = false;

    // Participant Form Fields
    public $first_name = '';
    public $middle_name = '';
    public $last_name = '';
    public $suffix = '';
    public $date_of_birth = '';
    public $gender = '';
    public $telephone = '';
    public $email = '';
    public $education_level = '';
    public $disability_status = '';
    public $health_insurance_status = '';
    public $ethnicity = '';
    public $race = [];
    public $military_status = '';
    public $work_status = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showEditParticipant($participantId)
    {
        $this->selectedParticipant = Participant::with('household')->find($participantId);
        if ($this->selectedParticipant) {
            $this->showEditForm = true;
            $this->fillParticipantForm();
        }
    }

    public function fillParticipantForm()
    {
        if ($this->selectedParticipant) {
            $this->first_name = $this->selectedParticipant->first_name;
            $this->middle_name = $this->selectedParticipant->middle_name;
            $this->last_name = $this->selectedParticipant->last_name;
            $this->suffix = $this->selectedParticipant->suffix;
            $this->date_of_birth = $this->selectedParticipant->date_of_birth?->format('Y-m-d') ?? '';
            $this->gender = $this->selectedParticipant->gender;
            $this->telephone = $this->selectedParticipant->telephone;
            $this->email = $this->selectedParticipant->email;
            $this->education_level = $this->selectedParticipant->education_level;
            $this->disability_status = $this->selectedParticipant->disability_status;
            $this->health_insurance_status = $this->selectedParticipant->health_insurance_status;
            $this->ethnicity = $this->selectedParticipant->ethnicity;
            $this->race = is_string($this->selectedParticipant->race) ? json_decode($this->selectedParticipant->race, true) ?? [] : ($this->selectedParticipant->race ?? []);
            $this->military_status = $this->selectedParticipant->military_status;
            $this->work_status = $this->selectedParticipant->work_status;
        }
    }

    public function updateParticipant()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'education_level' => 'nullable|string',
            'disability_status' => 'nullable|string',
            'health_insurance_status' => 'nullable|string',
            'ethnicity' => 'nullable|string',
            'race' => 'nullable|array',
            'military_status' => 'nullable|string',
            'work_status' => 'nullable|string',
        ]);

        try {
            // Get the active reference data set for updated data
            $activeDataSet = \App\Models\ReferenceDataSet::getActive();
            
            $this->selectedParticipant->update([
                'reference_data_set_id' => $activeDataSet?->id,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'date_of_birth' => $this->date_of_birth,
                'gender' => $this->gender,
                'telephone' => $this->telephone,
                'email' => $this->email,
                'education_level' => $this->education_level,
                'disability_status' => $this->disability_status,
                'health_insurance_status' => $this->health_insurance_status,
                'ethnicity' => $this->ethnicity,
                'race' => is_array($this->race) ? json_encode($this->race) : $this->race,
                'military_status' => $this->military_status,
                'work_status' => $this->work_status,
            ]);

            session()->flash('success', 'Participant updated successfully.');
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->suffix = '';
        $this->date_of_birth = '';
        $this->gender = '';
        $this->telephone = '';
        $this->email = '';
        $this->education_level = '';
        $this->disability_status = '';
        $this->health_insurance_status = '';
        $this->ethnicity = '';
        $this->race = [];
        $this->military_status = '';
        $this->work_status = '';
        $this->showEditForm = false;
        $this->selectedParticipant = null;
    }

    public function cancelEdit()
    {
        $this->resetForm();
    }

    public function deleteParticipant($participantId)
    {
        try {
            $participant = Participant::find($participantId);
            if ($participant) {
                if ($participant->is_head_of_household) {
                    session()->flash('error', 'Cannot delete head of household. Transfer headship first.');
                    return;
                }
                
                $participant->delete();
                session()->flash('success', 'Participant deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete participant: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $participants = Participant::with(['household'])
            ->when($this->search, function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhereHas('household', function ($q) {
                          $q->where('household_code', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Use the selected participant's reference data set if editing, otherwise use active set
        $dataSetId = $this->selectedParticipant ? $this->selectedParticipant->reference_data_set_id : null;

        return view('livewire.participant-management', [
            'participants' => $participants,
            'genderOptions' => ReferenceData::getOptions('gender', true, $dataSetId),
            'educationLevels' => ReferenceData::getOptions('education_level', true, $dataSetId),
            'ethnicityOptions' => ReferenceData::getOptions('ethnicity', true, $dataSetId),
            'raceOptions' => ReferenceData::getOptions('race', true, $dataSetId),
            'disabilityStatuses' => ReferenceData::getOptions('disability_status', true, $dataSetId),
            'healthInsuranceStatuses' => ReferenceData::getOptions('health_insurance_status', true, $dataSetId),
            'militaryStatuses' => ReferenceData::getOptions('military_status', true, $dataSetId),
            'workStatuses' => ReferenceData::getOptions('work_status', true, $dataSetId),
        ])->layout('components.layouts.app');
    }
}