<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\PovertyGuideline;
use App\Models\ReferenceData;
use App\Models\Program;
use App\Models\Service;
use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Carbon\Carbon;

class ParticipantIntake extends Component
{
    public $step = 1;
    public $totalSteps = 5;

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
    public $currently_enrolled_in_education = '';
    public $education_enrollment_type = '';
    public $disability_status = '';
    public $disability_types = [];
    public $assistance_needs = [];
    public $has_chronic_health_conditions = '';
    public $health_insurance_status = '';
    public $health_insurance_sources = [];
    public $ethnicity = '';
    public $race = [];
    public $military_status = '';
    public $work_status = '';
    public $hours_worked_per_week = '';
    public $is_seeking_employment = '';
    public $is_underemployed = '';

    // Financial Information
    public $income_sources = [];
    public $other_income_sources = [];
    public $non_cash_benefits = [];

    // System
    public $notes = '';

    // Step 5: Program Enrollment
    public $program_id = '';
    public $enrollment_date = '';
    public $enrollment_notes = '';
    
    // Optional Service Enrollment
    public $enroll_in_service = false;
    public $service_id = '';
    public $service_enrollment_date = '';
    public $service_notes = '';

    // Created participant for step 5
    public $createdParticipant = null;

    public function mount()
    {
        $this->resetForm();
        $this->enrollment_date = Carbon::today()->format('Y-m-d');
        $this->service_enrollment_date = Carbon::today()->format('Y-m-d');
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
        $this->currently_enrolled_in_education = '';
        $this->education_enrollment_type = '';
        $this->disability_status = '';
        $this->disability_types = [];
        $this->assistance_needs = [];
        $this->has_chronic_health_conditions = '';
        $this->health_insurance_status = '';
        $this->health_insurance_sources = [];
        $this->ethnicity = '';
        $this->race = [];
        $this->military_status = '';
        $this->work_status = '';
        $this->hours_worked_per_week = '';
        $this->is_seeking_employment = '';
        $this->is_underemployed = '';
        $this->income_sources = [];
        $this->other_income_sources = [];
        $this->non_cash_benefits = [];
        $this->notes = '';
        $this->program_id = '';
        $this->enrollment_date = Carbon::today()->format('Y-m-d');
        $this->enrollment_notes = '';
        $this->enroll_in_service = false;
        $this->service_id = '';
        $this->service_enrollment_date = Carbon::today()->format('Y-m-d');
        $this->service_notes = '';
        $this->createdParticipant = null;
    }

    public function nextStep()
    {
        $this->validateCurrentStep();
        
        // If moving from step 4 to 5, create the participant and household first
        if ($this->step == 4 && !$this->createdParticipant) {
            $this->createParticipantAndHousehold();
        }
        
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
            case 5:
                $this->validate([
                    'program_id' => 'required|exists:programs,id',
                    'enrollment_date' => 'required|date',
                ]);
                
                if ($this->enroll_in_service) {
                    $this->validate([
                        'service_id' => 'required|exists:services,id',
                        'service_enrollment_date' => 'required|date',
                    ]);
                }
                break;
        }
    }

    public function createParticipantAndHousehold()
    {
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
            $this->createdParticipant = Participant::create([
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
                'currently_enrolled_in_education' => $this->currently_enrolled_in_education === '1' ? true : ($this->currently_enrolled_in_education === '0' ? false : null),
                'education_enrollment_type' => $this->education_enrollment_type ?: null,
                'disability_status' => $this->disability_status ?: null,
                'disability_types' => $this->disability_types,
                'assistance_needs' => $this->assistance_needs,
                'has_chronic_health_conditions' => $this->has_chronic_health_conditions === '1' ? true : ($this->has_chronic_health_conditions === '0' ? false : null),
                'health_insurance_status' => $this->health_insurance_status ?: null,
                'health_insurance_sources' => $this->health_insurance_sources,
                'ethnicity' => $this->ethnicity ?: null,
                'race' => $this->race,
                'military_status' => $this->military_status ?: null,
                'work_status' => $this->work_status ?: null,
                'hours_worked_per_week' => $this->hours_worked_per_week ?: null,
                'is_seeking_employment' => $this->is_seeking_employment === '1' ? true : ($this->is_seeking_employment === '0' ? false : null),
                'is_underemployed' => $this->is_underemployed === '1' ? true : ($this->is_underemployed === '0' ? false : null),
                'income_sources' => $this->income_sources,
                'other_income_sources' => $this->other_income_sources,
                'non_cash_benefits' => $this->non_cash_benefits,
                'intake_date' => Carbon::today(),
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Participant and household created successfully! Now let\'s enroll them in a program.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred during registration: ' . $e->getMessage());
            throw $e;
        }
    }

    public function submit()
    {
        $this->validateCurrentStep();
        
        try {
            // Ensure participant was created
            if (!$this->createdParticipant) {
                throw new \Exception('Participant must be created before enrollment.');
            }

            // Create program enrollment
            $programEnrollment = ProgramEnrollment::create([
                'participant_id' => $this->createdParticipant->id,
                'program_id' => $this->program_id,
                'enrollment_date' => $this->enrollment_date,
                'status' => 'active',
                'notes' => $this->enrollment_notes,
            ]);

            // Create service enrollment if requested
            if ($this->enroll_in_service && $this->service_id) {
                ServiceEnrollment::create([
                    'participant_id' => $this->createdParticipant->id,
                    'service_id' => $this->service_id,
                    'program_enrollment_id' => $programEnrollment->id,
                    'enrollment_date' => $this->service_enrollment_date,
                    'status' => 'enrolled',
                    'notes' => $this->service_notes,
                ]);
                
                $message = 'Participant successfully enrolled in program and service!';
            } else {
                $message = 'Participant successfully enrolled in program!';
            }

            $household = $this->createdParticipant->household;
            session()->flash('success', $message . ' Household ID: ' . $household->household_code);
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred during enrollment: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $programs = Program::where('active', true)->orderBy('name')->get();
        $services = collect();
        
        // Get services for selected program
        if ($this->program_id) {
            $services = Service::where('program_id', $this->program_id)
                ->where('active', true)
                ->orderBy('name')
                ->get();
        }

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
            'programs' => $programs,
            'services' => $services,
        ])->layout('components.layouts.app');
    }
}
