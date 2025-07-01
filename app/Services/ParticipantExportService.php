<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Household;
use League\Csv\Writer;
use League\Csv\Reader;
use League\Csv\Bom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ParticipantExportService
{
    protected array $csvHeaders = [
        'household_code',
        'household_type',
        'household_size',
        'housing_status',
        'monthly_income',
        'annual_income',
        'hhs_poverty_percentage',
        'street_address',
        'city',
        'state',
        'zip_code',
        'household_notes',
        'participant_type',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'gender',
        'telephone',
        'email',
        'education_level',
        'currently_enrolled_in_education',
        'education_enrollment_type',
        'disability_status',
        'disability_types',
        'assistance_needs',
        'has_chronic_health_conditions',
        'health_insurance_sources',
        'health_insurance_status',
        'ethnicity',
        'race',
        'military_status',
        'work_status',
        'hours_worked_per_week',
        'is_seeking_employment',
        'is_underemployed',
        'income_sources',
        'employment_income',
        'other_income_sources',
        'non_cash_benefits',
        'intake_date',
        'last_service_date',
        'documentation_notes',
        'participant_notes'
    ];

    public function exportParticipantById(int $participantId): string
    {
        $participant = Participant::with('household')->findOrFail($participantId);
        $household = $participant->household;
        
        // Get all participants in the household for complete export
        $participants = $household->participants()->orderBy('is_head_of_household', 'desc')->get();
        
        return $this->generateCsv($participants);
    }

    public function exportHouseholdById(int $householdId): string
    {
        $household = Household::with('participants')->findOrFail($householdId);
        $participants = $household->participants()->orderBy('is_head_of_household', 'desc')->get();
        
        return $this->generateCsv($participants);
    }

    public function exportByDateRange(Carbon $startDate, Carbon $endDate, string $field = 'created_at'): string
    {
        // Validate field parameter
        if (!in_array($field, ['created_at', 'updated_at'])) {
            throw new \InvalidArgumentException('Field must be either created_at or updated_at');
        }

        $participants = Participant::with('household')
            ->whereBetween($field, [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('household_id')
            ->orderBy('is_head_of_household', 'desc')
            ->get();

        return $this->generateCsv($participants);
    }

    public function exportAll(): string
    {
        $participants = Participant::with('household')
            ->orderBy('household_id')
            ->orderBy('is_head_of_household', 'desc')
            ->get();

        return $this->generateCsv($participants);
    }

    public function exportByHouseholdCodes(array $householdCodes): string
    {
        $participants = Participant::with('household')
            ->whereHas('household', function($query) use ($householdCodes) {
                $query->whereIn('household_code', $householdCodes);
            })
            ->orderBy('household_id')
            ->orderBy('is_head_of_household', 'desc')
            ->get();

        return $this->generateCsv($participants);
    }

    protected function generateCsv(Collection $participants): string
    {
        $csv = Writer::createFromString();
        
        // Add UTF-8 BOM for Excel compatibility
        $csv->setOutputBOM(Bom::Utf8);
        
        // Insert headers
        $csv->insertOne($this->csvHeaders);

        $processedHouseholds = [];

        foreach ($participants as $participant) {
            $household = $participant->household;
            $isFirstParticipantInHousehold = !in_array($household->id, $processedHouseholds);
            
            $row = $this->buildRow($participant, $household, $isFirstParticipantInHousehold);
            $csv->insertOne($row);
            
            if ($isFirstParticipantInHousehold) {
                $processedHouseholds[] = $household->id;
            }
        }

        return $csv->toString();
    }

    protected function buildRow(Participant $participant, Household $household, bool $includeHouseholdData): array
    {
        return [
            // Household data (only for first participant in household)
            $includeHouseholdData ? $household->household_code : '',
            $includeHouseholdData ? $household->household_type : '',
            $includeHouseholdData ? $household->household_size : '',
            $includeHouseholdData ? $household->housing_status : '',
            $includeHouseholdData ? $household->monthly_income : '',
            $includeHouseholdData ? $household->annual_income : '',
            $includeHouseholdData ? $household->hhs_poverty_percentage : '',
            $includeHouseholdData ? $household->street_address : '',
            $includeHouseholdData ? $household->city : '',
            $includeHouseholdData ? $household->state : '',
            $includeHouseholdData ? $household->zip_code : '',
            $includeHouseholdData ? $household->notes : '',
            
            // Participant type
            $participant->is_head_of_household ? 'HEAD_OF_HOUSEHOLD' : 'FAMILY_MEMBER',
            
            // Personal information
            $participant->first_name,
            $participant->middle_name,
            $participant->last_name,
            $participant->suffix,
            $participant->date_of_birth?->format('Y-m-d'),
            $participant->gender,
            $participant->telephone,
            $participant->email,
            
            // Demographics
            $participant->education_level,
            $this->formatBoolean($participant->currently_enrolled_in_education),
            $participant->education_enrollment_type,
            $participant->disability_status,
            $this->formatArray($participant->disability_types),
            $this->formatArray($participant->assistance_needs),
            $this->formatBoolean($participant->has_chronic_health_conditions),
            $this->formatArray($participant->health_insurance_sources),
            $participant->health_insurance_status,
            $participant->ethnicity,
            $this->formatArray($participant->race),
            $participant->military_status,
            $participant->work_status,
            $participant->hours_worked_per_week,
            $this->formatBoolean($participant->is_seeking_employment),
            $this->formatBoolean($participant->is_underemployed),
            
            // Financial information
            $this->formatArray($participant->income_sources),
            $participant->employment_income,
            $this->formatArray($participant->other_income_sources),
            $this->formatArray($participant->non_cash_benefits),
            
            // Service dates
            $participant->intake_date?->format('Y-m-d'),
            $participant->last_service_date?->format('Y-m-d'),
            
            // Notes
            $participant->documentation_notes,
            $participant->notes,
        ];
    }

    protected function formatArray($value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? implode('|', $decoded) : $value;
        }
        
        if (is_array($value)) {
            return implode('|', $value);
        }
        
        return (string) $value;
    }

    protected function formatBoolean($value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        return $value ? 'TRUE' : 'FALSE';
    }

    public function generateTemplate(): string
    {
        $csv = Writer::createFromString();
        
        // Add UTF-8 BOM for Excel compatibility
        $csv->setOutputBOM(Bom::Utf8);
        
        // Insert headers
        $csv->insertOne($this->csvHeaders);
        
        // Add example rows
        $exampleRows = [
            [
                // Household data for first participant (head of household)
                'ABC123H', // household_code
                'Two Parent Household', // household_type
                '4', // household_size
                'Own', // housing_status
                '3500.00', // monthly_income
                '42000.00', // annual_income
                '185.50', // hhs_poverty_percentage
                '123 Main Street', // street_address
                'Las Vegas', // city
                'NV', // state
                '89101', // zip_code
                'Sample household notes', // household_notes
                'HEAD_OF_HOUSEHOLD', // participant_type
                'John', // first_name
                'Michael', // middle_name
                'Doe', // last_name
                'Sr.', // suffix
                '1985-06-15', // date_of_birth
                'Male', // gender
                '702-555-0123', // telephone
                'john.doe@email.com', // email
                'High school diploma/GED', // education_level
                'FALSE', // currently_enrolled_in_education
                '', // education_enrollment_type
                'No', // disability_status
                '', // disability_types
                '', // assistance_needs
                'FALSE', // has_chronic_health_conditions
                'Employment Based|Medicaid', // health_insurance_sources
                'Yes', // health_insurance_status
                'Not Hispanic or Latino', // ethnicity
                'White', // race
                'Not applicable', // military_status
                'Employed full-time', // work_status
                '40.00', // hours_worked_per_week
                'FALSE', // is_seeking_employment
                'FALSE', // is_underemployed
                'Employment', // income_sources
                '2800.00', // employment_income
                'Child Support', // other_income_sources
                'SNAP', // non_cash_benefits
                '2024-01-15', // intake_date
                '2024-12-01', // last_service_date
                'All documentation complete', // documentation_notes
                'Head of household - primary earner', // participant_notes
            ],
            [
                // Family member - no household data
                '', '', '', '', '', '', '', '', '', '', '', '', // Empty household fields
                'FAMILY_MEMBER', // participant_type
                'Jane', // first_name
                'Elizabeth', // middle_name
                'Doe', // last_name
                '', // suffix
                '1987-03-22', // date_of_birth
                'Female', // gender
                '702-555-0124', // telephone
                'jane.doe@email.com', // email
                'Some college', // education_level
                'TRUE', // currently_enrolled_in_education
                'College', // education_enrollment_type
                'No', // disability_status
                '', // disability_types
                '', // assistance_needs
                'FALSE', // has_chronic_health_conditions
                'Employment Based', // health_insurance_sources
                'Yes', // health_insurance_status
                'Hispanic or Latino', // ethnicity
                'White|American Indian or Alaska Native', // race (multiple races)
                'Not applicable', // military_status
                'Employed part-time', // work_status
                '20.00', // hours_worked_per_week
                'FALSE', // is_seeking_employment
                'TRUE', // is_underemployed
                'Employment', // income_sources
                '1200.00', // employment_income
                '', // other_income_sources
                'WIC', // non_cash_benefits
                '2024-01-15', // intake_date
                '2024-11-30', // last_service_date
                'Student status verified', // documentation_notes
                'Spouse - part-time student', // participant_notes
            ]
        ];

        foreach ($exampleRows as $row) {
            $csv->insertOne($row);
        }

        return $csv->toString();
    }

    public function getHeaders(): array
    {
        return $this->csvHeaders;
    }
}