<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PendingApproval extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'submitted_by',
        'approver_id',
        'program_id',
        'housing_job_id',
        'item_type',
        'item_name',
        'item_description',
        'status',
        'rejection_reason',
        'submitted_at',
        'actioned_at',
        'approved_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'actioned_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the housing job associated with this approval.
     */
    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    /**
     * Get the submitter of this approval request.
     * Alias for submittedBy() for consistency.
     */
    public function submitter(): BelongsTo
    {
        return $this->submittedBy();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for filtering approvals for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('approver_id', $user->id);
    }

    /**
     * Scope for filtering approvals for a specific program.
     */
    public function scopeForProgram($query, Program $program)
    {
        return $query->where('program_id', $program->id);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approver_id' => $approver->id,
            'approved_at' => now(),
            'actioned_at' => now(),
        ]);

        // Also approve the underlying approvable model if it exists
        if ($this->approvable && method_exists($this->approvable, 'approve')) {
            $this->approvable->approve($approver);
        }
    }

    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approver_id' => $approver->id,
            'rejection_reason' => $reason,
            'actioned_at' => now(),
        ]);

        // Also reject the underlying approvable model if it exists
        if ($this->approvable && method_exists($this->approvable, 'reject')) {
            $this->approvable->reject($approver, $reason);
        }
    }

    /**
     * Get a human-readable label for the approval type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->item_type) {
            'property_document' => 'Property Document',
            'contractor_document' => 'Contractor Document',
            'job_document' => 'Job Document',
            'work_plan_goal' => 'Work Plan Goal',
            default => ucwords(str_replace('_', ' ', $this->item_type ?? 'Unknown')),
        };
    }

    /**
     * Get a display name for the approval item.
     */
    public function getDisplayName(): string
    {
        return $this->item_name ?? $this->getTypeLabel();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
