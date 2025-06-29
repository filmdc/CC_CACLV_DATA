<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
