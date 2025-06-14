<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'program_manager_id',
        'active',
        'housing_module_enabled',
        'start_date',
        'end_date',
        'notes'
    ];

    protected $casts = [
        'active' => 'boolean',
        'housing_module_enabled' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function workPlans(): HasMany
    {
        return $this->hasMany(WorkPlan::class);
    }

    public function programEnrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->programEnrollments()->active()->with('participant');
    }

    public function getEnrolledParticipantsCount(): int
    {
        return $this->activeEnrollments()->count();
    }

    public function getTotalEnrollmentsCount(): int
    {
        return $this->programEnrollments()->count();
    }

    public function housingJobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function hasHousingModule(): bool
    {
        return $this->housing_module_enabled;
    }
}
