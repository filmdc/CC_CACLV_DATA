<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PendingApproval extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'submitted_by',
        'approver_id',
        'program_id',
        'context_type',
        'context_id',
        'approval_type_id',
        'current_chain_step',
        'submitter_notes',
        'priority',
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
        'current_chain_step' => 'integer',
        'priority' => 'integer',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvalType(): BelongsTo
    {
        return $this->belongsTo(ApprovalType::class);
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

    public function scopeForUser($query, User $user)
    {
        return $query->where('approver_id', $user->id);
    }

    public function scopeForProgram($query, Program $program)
    {
        return $query->where('program_id', $program->id);
    }

    /**
     * Scope for filtering approvals by context (generalized replacement for housing_job_id).
     */
    public function scopeForContext($query, string $contextType, int $contextId)
    {
        return $query->where('context_type', $contextType)
                     ->where('context_id', $contextId);
    }

    /**
     * Scope for filtering approvals for a specific housing job via context morph.
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('context_type', HousingJob::class)
                     ->where('context_id', $jobId);
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

    /**
     * Approve this approval request.
     * Supports multi-step chain advancement: if an approval type with multiple
     * chain steps is configured, advances to the next step instead of fully approving.
     */
    public function approve(User $approver): void
    {
        // Check if this has a multi-step approval chain
        if ($this->approval_type_id) {
            $approvalType = $this->approvalType;

            if ($approvalType && $approvalType->getTotalSteps() > 1) {
                $currentStep = $this->current_chain_step ?? 1;
                $totalSteps = $approvalType->getTotalSteps();

                if ($currentStep < $totalSteps) {
                    // Advance to next step, stay pending
                    $this->update([
                        'current_chain_step' => $currentStep + 1,
                        'approver_id' => $approver->id,
                    ]);

                    // Log the step approval
                    ApprovalHistory::logApproval(
                        $this->approvable ?? $this,
                        $approver,
                        null,
                        "Chain step {$currentStep} approved",
                        ['chain_step' => $currentStep, 'total_steps' => $totalSteps]
                    );

                    return;
                }
            }
        }

        // Final approval (single-step or last step of chain)
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
     * Delegates to the approvable model first, then falls back to approval_type, then legacy match.
     */
    public function getTypeLabel(): string
    {
        // Try delegating to approvable model
        if ($this->approvable && method_exists($this->approvable, 'getApprovalTypeLabel')) {
            return $this->approvable->getApprovalTypeLabel();
        }

        // Try the approval type display name
        if ($this->approval_type_id && $this->approvalType) {
            return $this->approvalType->display_name;
        }

        // Legacy fallback
        return match ($this->item_type) {
            'property_document' => 'Property Document',
            'contractor_document' => 'Contractor Document',
            'job_document' => 'Job Document',
            'work_plan_goal' => 'Work Plan Goal',
            default => ucwords(str_replace('_', ' ', $this->item_type ?? 'Unknown')),
        };
    }

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
