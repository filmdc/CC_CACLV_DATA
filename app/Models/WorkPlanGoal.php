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
        'actual_number',
        'requires_approval',
        'approved_at',
        'approved_by',
        'approval_status',
        'rejection_reason',
    ];

    protected $casts = [
        'target_number' => 'integer',
        'actual_number' => 'integer',
        'approved_at' => 'datetime',
        'requires_approval' => 'boolean',
    ];

    public function workPlan(): BelongsTo
    {
        return $this->belongsTo(WorkPlan::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who approved this goal.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
            // If no specific service is linked, try to count all services in the work plan's program
            if ($this->workPlan && $this->workPlan->program) {
                $actualCount = $this->getProgramWideServiceCount();
                $this->update(['actual_number' => $actualCount]);
                return true;
            }
            return false;
        }

        // Default to enrollment count, but this could be customized based on goal type
        $actualCount = $this->service->getEnrolledParticipantsCount();
        
        $this->update(['actual_number' => $actualCount]);
        return true;
    }

    /**
     * Get count of all service enrollments across all services in the work plan's program
     */
    public function getProgramWideServiceCount(): int
    {
        if (!$this->workPlan || !$this->workPlan->program) {
            return 0;
        }

        return $this->workPlan->program->services()
            ->with('serviceEnrollments')
            ->get()
            ->sum(function ($service) {
                return $service->getEnrolledParticipantsCount();
            });
    }

    /**
     * Get count of all completed service enrollments across all services in the work plan's program
     */
    public function getProgramWideCompletionCount(): int
    {
        if (!$this->workPlan || !$this->workPlan->program) {
            return 0;
        }

        return $this->workPlan->program->services()
            ->with('serviceEnrollments')
            ->get()
            ->sum(function ($service) {
                return $service->getCompletedEnrollmentsCount();
            });
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

    /**
     * Check if the goal is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if the goal is rejected.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Check if the goal is pending approval.
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending' || ($this->requires_approval && !$this->approval_status);
    }

    /**
     * Check if the goal requires approval.
     */
    public function requiresApproval(): bool
    {
        return (bool) $this->requires_approval;
    }

    /**
     * Approve the goal.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the goal.
     */
    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Reset the approval status.
     */
    public function resetApprovalStatus(): void
    {
        $this->update([
            'approval_status' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->approval_status) {
            'approved' => 'green',
            'rejected' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }
}
