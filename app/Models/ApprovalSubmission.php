<?php

namespace App\Models;

use App\Contracts\HasMediaAuthorization;
use App\Traits\Auditable;
use App\Traits\HasApprovals;
use App\Traits\HasStandardMediaCollections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ApprovalSubmission extends Model implements HasMedia, HasMediaAuthorization
{
    use Auditable, HasApprovals, HasFactory, HasStandardMediaCollections, InteractsWithMedia;

    /**
     * P-39a: submitter sees their own; approvers/admin gated by
     * `approvals.view_dashboard` (see PendingApprovalsManagement:34,
     * MyApprovalSubmissions which filters by submitted_by).
     */
    public function canViewMedia(User $user, Media $media): bool
    {
        if ($this->submitted_by === $user->id) {
            return true;
        }

        return $user->hasPermission('approvals.view_dashboard');
    }

    protected $fillable = [
        'approval_type_id',
        'title',
        'description',
        'submitter_notes',
        'program_id',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->registerStandardDocumentCollection('attachments');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerStandardConversions($media);
    }

    public function approvalType(): BelongsTo
    {
        return $this->belongsTo(ApprovalType::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function requiresApproval(): bool
    {
        return true;
    }

    public function getApprovalTypeLabel(): string
    {
        return $this->approvalType?->display_name ?? 'Approval Submission';
    }

    public function getApprovalCategory(): string
    {
        return $this->approvalType?->category ?? 'General';
    }

    public function getApprovalDetailUrl(): ?string
    {
        return route('approvals.my-submissions');
    }

    public function getApprovalResubmitUrl(): ?string
    {
        return route('approvals.my-submissions');
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
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

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
