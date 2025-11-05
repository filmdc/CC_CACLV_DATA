<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneTask extends Model
{
    protected $fillable = [
        'milestone_id',
        'name',
        'description',
        'status',
        'assigned_to',
        'deadline',
        'requires_approval',
        'approval_role',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'completed_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'completed_date' => 'date',
        'approved_at' => 'datetime',
        'requires_approval' => 'boolean',
    ];

    protected $appends = ['status_color', 'is_overdue'];

    // Relationships

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
            'pending' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            'overdue' => 'red',
            default => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        return $this->deadline && $this->deadline->isPast();
    }

    // Business Logic Methods

    public function markAsCompleted(): bool
    {
        $this->status = 'completed';
        $this->completed_date = now();
        return $this->save();
    }

    public function canBeCompleted(): bool
    {
        if ($this->requires_approval && !$this->approved_by) {
            return false;
        }

        return true;
    }

    public function approve(User $user): bool
    {
        $this->approved_by = $user->id;
        $this->approved_at = now();
        $this->status = 'completed';
        $this->completed_date = now();
        return $this->save();
    }

    public function reject(User $user, string $reason): bool
    {
        $this->rejection_reason = $reason;
        $this->approved_by = null;
        $this->approved_at = null;
        return $this->save();
    }
}
