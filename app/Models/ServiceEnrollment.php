<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ServiceEnrollment extends Model
{
    protected $fillable = [
        'participant_id',
        'service_id',
        'program_enrollment_id',
        'enrollment_date',
        'completion_date',
        'status',
        'outcomes',
        'hours_received',
        'sessions_attended',
        'completion_notes',
        'notes'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'outcomes' => 'array',
        'hours_received' => 'decimal:2',
        'sessions_attended' => 'integer'
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function programEnrollment(): BelongsTo
    {
        return $this->belongsTo(ProgramEnrollment::class);
    }

    public function getDaysInServiceAttribute(): int
    {
        $endDate = $this->completion_date ?? Carbon::now();
        return $this->enrollment_date->diffInDays($endDate);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['enrolled', 'in_progress']);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['enrolled', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeForParticipant($query, $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    public function scopeForProgramEnrollment($query, $programEnrollmentId)
    {
        return $query->where('program_enrollment_id', $programEnrollmentId);
    }

    /**
     * Scope to filter service enrollments by user's program access.
     */
    public function scopeForUserPrograms($query, $user)
    {
        // Admins see all service enrollments
        if ($user->isAdmin()) {
            return $query;
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Service enrollments are accessible if the service belongs to user's programs
        return $query->whereHas('service', function ($q) use ($userProgramIds) {
            $q->whereIn('program_id', $userProgramIds);
        });
    }
}