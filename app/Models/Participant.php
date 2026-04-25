<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'reference_data_set_id',
        'is_head_of_household',
        'relationship_to_head',
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
        'is_disconnected_youth',
        'csbg_age_group',
        'csbg_income_classification',
        'documentation_notes',
        'notes'
    ];

    protected $casts = [
        'is_head_of_household' => 'boolean',
        'date_of_birth' => 'date',
        'currently_enrolled_in_education' => 'boolean',
        'has_chronic_health_conditions' => 'boolean',
        'is_seeking_employment' => 'boolean',
        'is_underemployed' => 'boolean',
        'is_disconnected_youth' => 'boolean',
        'intake_date' => 'date',
        'last_service_date' => 'date',
        'employment_income' => 'decimal:2',
        'hours_worked_per_week' => 'decimal:2',
        'health_insurance_sources' => 'array',
        'race' => 'array',
        'income_sources' => 'array',
        'other_income_sources' => 'array',
        'non_cash_benefits' => 'array',
        'disability_types' => 'array',
        'assistance_needs' => 'array'
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function referenceDataSet(): BelongsTo
    {
        return $this->belongsTo(ReferenceDataSet::class);
    }

    public function getFullNameAttribute(): string
    {
        $name = trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
        return $this->suffix ? $name . ' ' . $this->suffix : $name;
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? (int) $this->date_of_birth->diffInYears(Carbon::now()) : 0;
    }

    public function programEnrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ServiceEnrollment::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function housingJobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function activeProgramEnrollments()
    {
        return $this->programEnrollments()->active()->with('program');
    }

    public function activeServiceEnrollments()
    {
        return $this->serviceEnrollments()->active()->with('service');
    }

    public function getActivePrograms()
    {
        return $this->activeProgramEnrollments()->get()->pluck('program');
    }

    public function getActiveServices()
    {
        return $this->activeServiceEnrollments()->get()->pluck('service');
    }

    // CSBG Computed Fields

    public function getCSBGAgeGroupAttribute(): string
    {
        $age = $this->age;
        
        if ($age >= 0 && $age <= 5) return '0-5';
        if ($age >= 6 && $age <= 13) return '6-13';
        if ($age >= 14 && $age <= 17) return '14-17';
        if ($age >= 18 && $age <= 24) return '18-24';
        if ($age >= 25 && $age <= 44) return '25-44';
        if ($age >= 45 && $age <= 54) return '45-54';
        if ($age >= 55 && $age <= 59) return '55-59';
        if ($age >= 60 && $age <= 64) return '60-64';
        if ($age >= 65 && $age <= 74) return '65-74';
        if ($age >= 75) return '75+';
        
        return 'Unknown/not reported';
    }

    public function getIsDisconnectedYouthAttribute(): bool
    {
        $age = $this->age;
        
        // Youth ages 14-24 who are neither working nor in school
        if ($age >= 14 && $age <= 24) {
            $isWorking = in_array($this->work_status, ['Employed Full-Time', 'Employed Part-Time']);
            $isInSchool = $this->currently_enrolled_in_education;
            
            return !$isWorking && !$isInSchool;
        }
        
        return false;
    }

    public function getCSBGIncomeClassificationAttribute(): string
    {
        $household = $this->household;
        if (!$household) return 'Unknown/not reported';
        
        $householdParticipants = $household->participants;
        
        // Helper function to safely get array from JSON field
        $getArrayField = function($field) {
            if (is_array($field)) return $field;
            if (is_string($field)) return json_decode($field, true) ?? [];
            return [];
        };
        
        $hasEmployment = $householdParticipants->some(function($p) use ($getArrayField) {
            return in_array('Employment', $getArrayField($p->income_sources));
        });
        
        $hasOtherIncome = $householdParticipants->some(function($p) use ($getArrayField) {
            return !empty($getArrayField($p->other_income_sources));
        });
        
        $hasNonCashBenefits = $householdParticipants->some(function($p) use ($getArrayField) {
            return !empty($getArrayField($p->non_cash_benefits));
        });
        
        $hasAnyIncome = $hasEmployment || $hasOtherIncome;
        
        if (!$hasAnyIncome && !$hasNonCashBenefits) {
            return 'No Income';
        }
        
        if (!$hasAnyIncome && $hasNonCashBenefits) {
            return 'Non-Cash Benefits Only';
        }
        
        if ($hasEmployment && !$hasOtherIncome && !$hasNonCashBenefits) {
            return 'Income from Employment Only';
        }
        
        if ($hasEmployment && $hasOtherIncome && $hasNonCashBenefits) {
            return 'Income from Employment, Other Income Source, and Non-Cash Benefits';
        }
        
        if ($hasEmployment && $hasNonCashBenefits && !$hasOtherIncome) {
            return 'Income from Employment and Non-Cash Benefits';
        }
        
        if ($hasEmployment && $hasOtherIncome && !$hasNonCashBenefits) {
            return 'Income from Employment and Other Income Source';
        }
        
        if (!$hasEmployment && $hasOtherIncome && $hasNonCashBenefits) {
            return 'Other Income Source and Non-Cash Benefits';
        }
        
        if (!$hasEmployment && $hasOtherIncome && !$hasNonCashBenefits) {
            return 'Other Income Source Only';
        }
        
        return 'Unknown/not reported';
    }

    public function updateCSBGComputedFields(): void
    {
        $this->update([
            'csbg_age_group' => $this->csbg_age_group,
            'is_disconnected_youth' => $this->is_disconnected_youth,
            'csbg_income_classification' => $this->csbg_income_classification,
        ]);
    }

    // Observer hook to auto-update computed fields
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($participant) {
            // Auto-update computed fields when relevant data changes
            $participant->csbg_age_group = $participant->csbg_age_group;
            $participant->is_disconnected_youth = $participant->is_disconnected_youth;
            $participant->csbg_income_classification = $participant->csbg_income_classification;
        });
    }

    /**
     * Scope to filter participants by user's program access.
     */
    public function scopeForUserPrograms($query, $user)
    {
        // Admins see all participants
        if ($user->isAdmin()) {
            return $query;
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Return participants enrolled in user's programs
        return $query->whereHas('programEnrollments', function ($q) use ($userProgramIds) {
            $q->whereIn('program_id', $userProgramIds);
        });
    }

    /**
     * Define sensitive fields that require program access to view.
     */
    public static function getSensitiveFields(): array
    {
        return [
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
            'is_disconnected_youth',
            'csbg_age_group',
            'csbg_income_classification',
            'documentation_notes',
            'notes'
        ];
    }

    /**
     * Define public fields that are always visible.
     */
    public static function getPublicFields(): array
    {
        return [
            'id',
            'household_id',
            'is_head_of_household',
            'relationship_to_head',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'date_of_birth',
            'telephone',
            'email',
            'created_at',
            'updated_at'
        ];
    }
}
