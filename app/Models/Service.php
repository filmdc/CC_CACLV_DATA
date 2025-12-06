<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'program_id',
        'service_type',
        'start_date',
        'end_date',
        'active',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the contracts that fund this service
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_service')
            ->withPivot(['allocation_percentage', 'allocated_amount', 'notes', 'created_by', 'updated_by'])
            ->withTimestamps();
    }

    /**
     * Get active contracts that fund this service
     */
    public function activeContracts(): BelongsToMany
    {
        return $this->contracts()->where('archived', false);
    }

    public function workPlanGoals(): HasMany
    {
        return $this->hasMany(WorkPlanGoal::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ServiceEnrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->serviceEnrollments()->active()->with('participant');
    }

    public function getEnrolledParticipantsCount(): int
    {
        return $this->activeEnrollments()->count();
    }

    public function getCompletedEnrollmentsCount(): int
    {
        return $this->serviceEnrollments()->completed()->count();
    }
}
