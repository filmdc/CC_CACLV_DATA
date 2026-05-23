<?php

namespace App\Models;

use App\Contracts\HasMediaAuthorization;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractReport extends Model implements HasMedia, HasMediaAuthorization
{
    use Auditable, InteractsWithMedia, SoftDeletes;

    /**
     * P-39a: matches ContractManagement page-level gate (contract_reports.view).
     */
    public function canViewMedia(User $user, Media $media): bool
    {
        return $user->hasPermission('contract_reports.view');
    }

    public const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'revision_required' => 'Revision Required',
    ];

    protected $fillable = [
        'contract_id',
        'contract_report_due_date_id',
        'title',
        'description',
        'submitted_date',
        'status',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'submitted_date' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = [
        'submissions',
        'documents', // Legacy support
    ];

    public static $searchable = [
        'title',
        'description',
        'status',
    ];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('submissions')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.oasis.opendocument.spreadsheet',
                'text/plain',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/jpg',
            ])
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 150, 150);
        $this->addMediaConversion('preview')->fit('crop', 300, 300);
    }

    /**
     * Get the contract this report belongs to
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the due date this report is for
     */
    public function contractReportDueDate(): BelongsTo
    {
        return $this->belongsTo(ContractReportDueDate::class);
    }

    /**
     * Get the user who created this report
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get submissions attached to this report
     */
    public function getSubmissionsAttribute()
    {
        return $this->getMedia('submissions');
    }

    /**
     * Get documents attached to this report (legacy accessor)
     *
     * @deprecated Use getSubmissionsAttribute() instead
     */
    public function getDocumentsAttribute()
    {
        return $this->getMedia('submissions');
    }

    /**
     * Scope to get reports by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get submitted reports
     */
    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review', 'approved', 'rejected', 'revision_required']);
    }

    /**
     * Scope to get draft reports
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Check if report is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'revision_required']);
    }

    /**
     * Check if report can be submitted
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && ! empty($this->title) && ! empty($this->description);
    }

    /**
     * Submit the report
     */
    public function submit(): bool
    {
        if (! $this->canBeSubmitted()) {
            return false;
        }

        $this->update([
            'status' => 'submitted',
            'submitted_date' => now()->toDateString(),
        ]);

        // Update due date status if this report fulfills it
        if ($this->contractReportDueDate) {
            $this->contractReportDueDate->update(['status' => 'completed']);
        }

        return true;
    }

    /**
     * Approve the report
     */
    public function approve(): bool
    {
        if (! in_array($this->status, ['submitted', 'under_review'])) {
            return false;
        }

        $this->update(['status' => 'approved']);

        return true;
    }

    /**
     * Reject the report
     */
    public function reject(?string $reason = null): bool
    {
        if (! in_array($this->status, ['submitted', 'under_review'])) {
            return false;
        }

        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['rejection_reason'] = $reason;
            $metadata['rejection_date'] = now()->toISOString();
        }

        $this->update([
            'status' => 'rejected',
            'metadata' => $metadata,
        ]);

        return true;
    }

    /**
     * Request revision for the report
     */
    public function requestRevision(?string $feedback = null): bool
    {
        if (! in_array($this->status, ['submitted', 'under_review'])) {
            return false;
        }

        $metadata = $this->metadata ?? [];
        if ($feedback) {
            $metadata['revision_feedback'] = $feedback;
            $metadata['revision_requested_date'] = now()->toISOString();
        }

        $this->update([
            'status' => 'revision_required',
            'metadata' => $metadata,
        ]);

        return true;
    }
}
