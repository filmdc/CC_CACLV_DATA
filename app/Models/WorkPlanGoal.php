<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPlanGoal extends Model
{
    protected $fillable = [
        'work_plan_id',
        'service_id',
        'goal_name',
        'description',
        'target_number',
        'actual_number'
    ];

    protected $casts = [
        'target_number' => 'integer',
        'actual_number' => 'integer'
    ];

    public function workPlan(): BelongsTo
    {
        return $this->belongsTo(WorkPlan::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        return $this->target_number > 0 ? 
            round(($this->actual_number / $this->target_number) * 100, 2) : 0;
    }

    /**
     * Get the actual service enrollment count for this goal
     */
    public function getServiceEnrollmentCountAttribute(): int
    {
        return $this->service ? $this->service->getEnrolledParticipantsCount() : 0;
    }

    /**
     * Get the actual service completion count for this goal  
     */
    public function getServiceCompletionCountAttribute(): int
    {
        return $this->service ? $this->service->getCompletedEnrollmentsCount() : 0;
    }

    /**
     * Get total service activity (enrollments + completions) for this goal
     */
    public function getTotalServiceActivityAttribute(): int
    {
        return $this->service_enrollment_count + $this->service_completion_count;
    }

    /**
     * Update actual number based on service data
     * This method can be called to sync the goal with actual service delivery
     */
    public function updateActualFromServiceData(): bool
    {
        if (!$this->service) {
            return false;
        }

        // Default to enrollment count, but this could be customized based on goal type
        $actualCount = $this->service->getEnrolledParticipantsCount();
        
        $this->update(['actual_number' => $actualCount]);
        return true;
    }

    /**
     * Check if this goal is on track based on time of year and progress
     */
    public function getIsOnTrackAttribute(): bool
    {
        $currentMonth = now()->month;
        $expectedProgress = ($currentMonth / 12) * 100;
        return $this->progress_percentage >= ($expectedProgress * 0.8); // 80% of expected progress
    }

    /**
     * Get status description for the goal
     */
    public function getStatusAttribute(): string
    {
        if ($this->progress_percentage >= 100) {
            return 'Target Met';
        } elseif ($this->progress_percentage >= 75) {
            return 'Close to Target';
        } elseif ($this->is_on_track) {
            return 'On Track';
        } else {
            return 'Needs Attention';
        }
    }
}
