<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Participant extends Model
{
    protected $fillable = [
        'household_id',
        'reference_data_set_id',
        'is_head_of_household',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'gender',
        'telephone',
        'email',
        'education_level',
        'disability_status',
        'health_insurance_sources',
        'health_insurance_status',
        'ethnicity',
        'race',
        'military_status',
        'work_status',
        'income_sources',
        'employment_income',
        'other_income_sources',
        'non_cash_benefits',
        'documentation_notes',
        'notes'
    ];

    protected $casts = [
        'is_head_of_household' => 'boolean',
        'date_of_birth' => 'date',
        'employment_income' => 'decimal:2',
        'health_insurance_sources' => 'array',
        'race' => 'array',
        'income_sources' => 'array',
        'other_income_sources' => 'array',
        'non_cash_benefits' => 'array'
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
        return $this->date_of_birth ? $this->date_of_birth->diffInYears(Carbon::now()) : 0;
    }

    public function programEnrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ServiceEnrollment::class);
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
}
