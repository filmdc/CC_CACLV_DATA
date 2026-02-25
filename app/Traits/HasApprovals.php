<?php

namespace App\Traits;

use App\Models\ApprovalHistory;
use App\Models\PendingApproval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovals
{
    public function pendingApprovals(): MorphMany
    {
        return $this->morphMany(PendingApproval::class, 'approvable');
    }

    public function approvalHistory(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable');
    }

    public function getApprovalTypeLabel(): string
    {
        return class_basename($this);
    }

    public function getApprovalCategory(): string
    {
        return 'General';
    }

    public function getApprovalDetailUrl(): ?string
    {
        return null;
    }

    public function getApprovalResubmitUrl(): ?string
    {
        return $this->getApprovalDetailUrl();
    }

    public function requiresApproval(): bool
    {
        return (bool) ($this->requires_approval ?? false);
    }

    public function isApproved(): bool
    {
        if (isset($this->approval_status)) {
            return $this->approval_status === 'approved';
        }

        if (isset($this->status)) {
            return in_array($this->status, ['approved', 'verified']);
        }

        return false;
    }

    public function isRejected(): bool
    {
        if (isset($this->approval_status)) {
            return $this->approval_status === 'rejected';
        }

        if (isset($this->status)) {
            return $this->status === 'rejected';
        }

        return false;
    }

    public function isPendingApproval(): bool
    {
        return ! $this->isApproved() && ! $this->isRejected() && $this->requiresApproval();
    }

    public function getActivePendingApproval(): ?PendingApproval
    {
        return $this->pendingApprovals()
            ->where('status', 'pending')
            ->latest()
            ->first();
    }
}
