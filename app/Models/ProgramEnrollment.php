<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ProgramEnrollment extends Model
{
    protected $fillable = [
        'participant_id',
        'program_id',
        'enrollment_date',
        'exit_date',
        'status',
        'exit_reason',
        'exit_notes',
        'notes'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'exit_date' => 'date'
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ServiceEnrollment::class);
    }

    public function getDaysEnrolledAttribute(): int
    {
        $endDate = $this->exit_date ?? Carbon::now();
        return $this->enrollment_date->diffInDays($endDate);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeForParticipant($query, $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    /**
     * Scope to filter program enrollments by user's program access.
     */
    public function scopeForUserPrograms($query, $user)
    {
        // Admins see all enrollments
        if ($user->isAdmin()) {
            return $query;
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        return $query->whereIn('program_id', $userProgramIds);
    }
}