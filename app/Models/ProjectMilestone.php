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
        'icon',
        'color',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_date' => 'date',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere(function($q) {
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
            ->where(function($query) {
                $query->where('status', 'not_uploaded')
                      ->orWhere('status', 'rejected')
                      ->orWhere(function($q) {
                          $q->where('status', 'pending_review')
                            ->where('requires_approval', true);
                      });
            })
            ->count();

        return $incompleteDocuments === 0;
    }

    public function markAsCompleted(): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->status = 'completed';
        $this->completed_date = now();
        $this->save();

        // Auto-start next milestone if exists
        $nextMilestone = self::where('housing_job_id', $this->housing_job_id)
            ->where('sequence_order', $this->sequence_order + 1)
            ->where('status', 'not_started')
            ->first();

        if ($nextMilestone) {
            $nextMilestone->status = 'in_progress';
            $nextMilestone->save();
        }

        return true;
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
