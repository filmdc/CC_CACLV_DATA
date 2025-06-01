<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\PovertyGuideline;
use App\Models\ReferenceData;
use Livewire\Component;
use Livewire\Attributes\Validate;

class ParticipantIntake extends Component
{
    public $step = 1;
    public $totalSteps = 4;

    // Household Information
    #[Validate('required|string')]
    public $household_type = '';
    
    #[Validate('required|integer|min:1')]
    public $household_size = 1;
    
    #[Validate('required|string')]
    public $housing_status = '';
    
    #[Validate('nullable|numeric|min:0')]
    public $monthly_income = '';
    
    #[Validate('nullable|string|max:255')]
    public $street_address = '';
    
    #[Validate('nullable|string|max:100')]
    public $city = '';
    
    #[Validate('nullable|string|size:2')]
    public $state = '';
    
    #[Validate('nullable|string|max:10')]
    public $zip_code = '';

    // Head of Household Information
    #[Validate('required|string|max:255')]
    public $first_name = '';
    
    #[Validate('nullable|string|max:255')]
    public $middle_name = '';
    
    #[Validate('required|string|max:255')]
    public $last_name = '';
    
    #[Validate('nullable|string|max:50')]
    public $suffix = '';
    
    #[Validate('required|date|before:today')]
    public $date_of_birth = '';
    
    #[Validate('required|string')]
    public $gender = '';
    
    #[Validate('nullable|string|max:20')]
    public $telephone = '';
    
    #[Validate('nullable|email|max:255')]
    public $email = '';

    // Demographics
    public $education_level = '';
    public $disability_status = '';
    public $health_insurance_status = '';
    public $health_insurance_sources = [];
    public $ethnicity = '';
    public $race = [];
    public $military_status = '';
    public $work_status = '';

    // Financial Information
    public $income_sources = [];
    public $other_income_sources = [];
    public $non_cash_benefits = [];

    // System
    public $notes = '';

    public function mount()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->step = 1;
        $this->household_type = '';
        $this->household_size = 1;
        $this->housing_status = '';
        $this->monthly_income = '';
        $this->street_address = '';
        $this->city = '';
        $this->state = '';
        $this->zip_code = '';
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
        $this->health_insurance_sources = [];
        $this->ethnicity = '';
        $this->race = [];
        $this->military_status = '';
        $this->work_status = '';
        $this->income_sources = [];
        $this->other_income_sources = [];
        $this->non_cash_benefits = [];
        $this->notes = '';
    }

    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function validateCurrentStep()
    {
        switch ($this->step) {
            case 1:
                $this->validate([
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'date_of_birth' => 'required|date|before:today',
                    'gender' => 'required',
                ]);
                break;
            case 2:
                $this->validate([
                    'household_type' => 'required',
                    'household_size' => 'required|integer|min:1',
                    'housing_status' => 'required',
                ]);
                break;
            case 3:
                // Demographics are mostly optional
                break;
            case 4:
                // Financial information is mostly optional
                break;
        }
    }

    public function submit()
    {
        $this->validateCurrentStep();
        
        try {
            // Calculate annual income if monthly is provided
            $annual_income = $this->monthly_income ? $this->monthly_income * 12 : null;
            
            // Create household
            $household = Household::create([
                'household_type' => $this->household_type,
                'household_size' => $this->household_size,
                'housing_status' => $this->housing_status,
                'monthly_income' => $this->monthly_income ?: null,
                'annual_income' => $annual_income,
                'street_address' => $this->street_address,
                'city' => $this->city,
                'state' => $this->state,
                'zip_code' => $this->zip_code,
                'notes' => $this->notes,
            ]);

            // Calculate poverty percentage
            if ($annual_income) {
                $povertyPercentage = PovertyGuideline::calculatePovertyPercentage(
                    $annual_income, 
                    $this->household_size, 
                    'annual'
                );
                if ($povertyPercentage) {
                    $household->update(['hhs_poverty_percentage' => $povertyPercentage]);
                }
            }

            // Get the active reference data set
            $activeDataSet = \App\Models\ReferenceDataSet::getActive();

            // Create head of household participant
            Participant::create([
                'household_id' => $household->id,
                'reference_data_set_id' => $activeDataSet?->id,
                'is_head_of_household' => true,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'date_of_birth' => $this->date_of_birth,
                'gender' => $this->gender,
                'telephone' => $this->telephone,
                'email' => $this->email,
                'education_level' => $this->education_level ?: null,
                'disability_status' => $this->disability_status ?: null,
                'health_insurance_status' => $this->health_insurance_status ?: null,
                'health_insurance_sources' => $this->health_insurance_sources,
                'ethnicity' => $this->ethnicity ?: null,
                'race' => $this->race,
                'military_status' => $this->military_status ?: null,
                'work_status' => $this->work_status ?: null,
                'income_sources' => $this->income_sources,
                'other_income_sources' => $this->other_income_sources,
                'non_cash_benefits' => $this->non_cash_benefits,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Participant and household successfully registered with ID: ' . $household->household_code);
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred during registration: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.participant-intake', [
            'genderOptions' => ReferenceData::getOptions('gender'),
            'educationLevels' => ReferenceData::getOptions('education_level'),
            'ethnicityOptions' => ReferenceData::getOptions('ethnicity'),
            'raceOptions' => ReferenceData::getOptions('race'),
            'disabilityStatuses' => ReferenceData::getOptions('disability_status'),
            'healthInsuranceStatuses' => ReferenceData::getOptions('health_insurance_status'),
            'healthInsuranceSources' => ReferenceData::getOptions('health_insurance_sources'),
            'militaryStatuses' => ReferenceData::getOptions('military_status'),
            'workStatuses' => ReferenceData::getOptions('work_status'),
            'incomeSources' => ReferenceData::getOptions('income_sources'),
            'otherIncomeSources' => ReferenceData::getOptions('other_income_sources'),
            'nonCashBenefits' => ReferenceData::getOptions('non_cash_benefits'),
            'housingStatuses' => ReferenceData::getOptions('housing_status'),
            'housingTypes' => ReferenceData::getOptions('housing_type'),
            'householdTypes' => ReferenceData::getOptions('household_type'),
        ])->layout('components.layouts.app');
    }
}
