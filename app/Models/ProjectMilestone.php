<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectMilestone extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'housing_job_id',
        'name',
        'description',
        'sequence_order',
        'status',
        'target_date',
        'completed_date',
        'started_at',
        'icon',
        'color',
        'assigned_to_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_date' => 'date',
        'started_at' => 'datetime',
        'sequence_order' => 'integer',
    ];

    protected $appends = [
        'status_color',
        'is_completed',
        'is_overdue',
        'completion_percentage',
    ];

    // Relationships

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MilestoneTask::class, 'milestone_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MilestoneDocument::class, 'milestone_id');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(MilestoneCommunication::class, 'milestone_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Phase 3A / 7.5: milestones this one depends on. A milestone with
     * any incomplete dependency requirement is considered "blocked" by
     * the dashboard precompute, but the state itself is computed, not
     * stored.
     */
    public function dependencies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'milestone_dependencies',
            'milestone_id',
            'depends_on_milestone_id'
        )->withTimestamps();
    }

    /**
     * Inverse: milestones that depend on this one. Used by graph
     * traversal to find downstream blockers.
     */
    public function dependents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'milestone_dependencies',
            'depends_on_milestone_id',
            'milestone_id'
        )->withTimestamps();
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class, 'source_id')
            ->where('source_type', Requirement::SOURCE_MILESTONE);
    }

    /**
     * Milestone revamp: fund codes assigned to this milestone on the
     * parent project (i.e. fund_code_housing_job rows with
     * milestone_id = this). Many fund codes may share a milestone; a
     * fund code can be assigned to at most one milestone per project.
     */
    public function fundCodes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            FundCode::class,
            'fund_code_housing_job',
            'milestone_id',
            'fund_code_id'
        )->withTimestamps();
    }

    /**
     * Milestone revamp: budget rows assigned to this milestone. The
     * requirements tab nests these under the milestone so per-service
     * spend is visible.
     */
    public function projectBudgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class, 'milestone_id');
    }

    public function requirementTasks(): HasMany
    {
        return $this->requirements()->where('type', Requirement::TYPE_TASK);
    }

    public function requirementDocuments(): HasMany
    {
        return $this->requirements()->where('type', Requirement::TYPE_DOCUMENT);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Phase 3A / 7.5: bucket the milestone's target_date distance from
     * today into one of three states. target_date now drives the hard
     * deadline indicator (per David's call: no separate due_date
     * column; the soft-vs-hard distinction was rejected as user-
     * confusion surface). Hardcoded threshold: ≤7 days = approaching,
     * past = overdue.
     *
     * @return 'overdue'|'approaching'|null
     */
    public function deadlineIndicator(): ?string
    {
        if ($this->target_date === null) {
            return null;
        }

        $today = now()->startOfDay();
        $target = $this->target_date->copy()->startOfDay();
        $days = (int) $today->diffInDays($target, false);

        if ($days < 0) {
            return 'overdue';
        }

        if ($days <= 7) {
            return 'approaching';
        }

        return null;
    }

    // Computed Attributes

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'not_started' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            default => 'gray',
        };
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->target_date
            && $this->target_date->isPast()
            && $this->status !== 'completed';
    }

    public function getCompletionPercentageAttribute(): int
    {
        $totalTasks = $this->tasks()->count();
        $totalDocs = $this->documents()->count();
        $total = $totalTasks + $totalDocs;

        if ($total === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();

        // Documents are complete if:
        // 1. Status is 'approved' (for documents requiring approval)
        // 2. Status is 'pending_review' AND doesn't require approval (uploaded files, no approval needed)
        $completedDocs = $this->documents()
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere(function ($q) {
                        $q->where('status', 'pending_review')
                            ->where('requires_approval', false);
                    });
            })
            ->count();

        $completed = $completedTasks + $completedDocs;

        return (int) round(($completed / $total) * 100);
    }

    // Business Logic Methods

    public function canBeCompleted(): bool
    {
        // Check if all tasks are completed
        $incompleteTasks = $this->tasks()
            ->where('status', '!=', 'completed')
            ->count();

        if ($incompleteTasks > 0) {
            return false;
        }

        // Check if all documents are satisfied
        // Documents are incomplete if:
        // 1. Not uploaded
        // 2. Rejected
        // 3. Requires approval but still pending review (not yet approved)
        $incompleteDocuments = $this->documents()
            ->where(function ($query) {
                $query->where('status', 'not_uploaded')
                    ->orWhere('status', 'rejected')
                    ->orWhere(function ($q) {
                        $q->where('status', 'pending_review')
                            ->where('requires_approval', true);
                    });
            })
            ->count();

        return $incompleteDocuments === 0;
    }

    public function markAsCompleted(): bool
    {
        if (! $this->canBeCompleted()) {
            return false;
        }

        $this->status = 'completed';
        $this->completed_date = now();
        $this->save();

        // Auto-start next milestone if exists, routing through
        // markAsInProgress so its started_at is stamped consistently with
        // user-initiated transitions (P-19).
        $nextMilestone = self::where('housing_job_id', $this->housing_job_id)
            ->where('sequence_order', $this->sequence_order + 1)
            ->where('status', 'not_started')
            ->first();

        $nextMilestone?->markAsInProgress();

        return true;
    }

    /**
     * P-19: move this milestone to in_progress and stamp started_at.
     * Once stamped, started_at is preserved across re-transitions
     * (in_progress → not_started → in_progress doesn't overwrite the
     * original timestamp).
     */
    public function markAsInProgress(): bool
    {
        $this->status = 'in_progress';
        if ($this->started_at === null) {
            $this->started_at = now();
        }

        return $this->save();
    }

    public function getPendingTasksCount(): int
    {
        return $this->tasks()
            ->whereIn('status', ['pending', 'in_progress', 'overdue'])
            ->count();
    }

    public function getPendingDocumentsCount(): int
    {
        return $this->documents()
            ->whereIn('status', ['not_uploaded', 'pending_review'])
            ->count();
    }

    public function getNextDeadline(): ?string
    {
        $nextTask = $this->tasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('deadline')
            ->orderBy('deadline', 'asc')
            ->first();

        return $nextTask?->deadline?->format('Y-m-d');
    }
}
