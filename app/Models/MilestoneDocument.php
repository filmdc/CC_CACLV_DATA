<?php

namespace App\Models;

use App\Services\MediaValidationService;
use App\Traits\HasStandardMediaCollections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MilestoneDocument extends Model implements HasMedia
{
    use InteractsWithMedia, HasStandardMediaCollections;

    protected $fillable = [
        'milestone_id',
        'pdf_template_id',
        'document_type',
        'description',
        'status',
        'assigned_to',
        'uploaded_date',
        'reviewed_date',
        'requires_approval',
        'approval_role',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'uploaded_date' => 'datetime',
        'reviewed_date' => 'datetime',
        'approved_at' => 'datetime',
        'requires_approval' => 'boolean',
    ];

    protected $appends = ['status_color', 'has_files'];

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

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'document_id');
    }

    public function pdfTemplate(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PdfFormTemplate::class, 'pdf_template_id');
    }

    public function pdfGenerations(): HasMany
    {
        return $this->hasMany(\App\Models\PdfFormGeneration::class, 'milestone_document_id');
    }

    /**
     * Check if this document has a PDF template assigned
     */
    public function hasPdfTemplate(): bool
    {
        return $this->pdf_template_id !== null;
    }

    /**
     * Check if PDF has been generated from template
     */
    public function hasPdfGenerated(): bool
    {
        return $this->pdfGenerations()->where('generation_status', 'success')->exists();
    }

    /**
     * Register media collections
     *
     * UPDATED: Added explicit disk configuration and MIME type validation
     */
    public function registerMediaCollections(): void
    {
        $this->registerStandardDocumentCollection();
    }

    /**
     * Register media conversions for thumbnails
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerStandardConversions($media);
    }

    // Computed Attributes

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'not_uploaded' => 'gray',
            'pending_review' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    public function getHasFilesAttribute(): bool
    {
        return $this->valid_document_media->isNotEmpty();
    }

    // Business Logic Methods

    public function markAsUploaded(): bool
    {
        $this->status = 'pending_review';
        $this->uploaded_date = now();
        return $this->save();
    }

    public function approve(User $user): bool
    {
        $this->status = 'approved';
        $this->approved_by = $user->id;
        $this->approved_at = now();
        $this->reviewed_date = now();
        return $this->save();
    }

    public function reject(User $user, string $reason): bool
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->reviewed_date = now();
        return $this->save();
    }

    public function getValidDocumentMediaAttribute(): Collection
    {
        return $this->filterExistingMedia(
            $this->getMedia('documents')
        )->values();
    }

    public function getMissingDocumentMediaAttribute(): Collection
    {
        return $this->getMedia('documents')
            ->filter(fn (Media $media) => ! $this->mediaFileExists($media))
            ->values();
    }

    protected function filterExistingMedia(Collection $media): Collection
    {
        return $media->filter(fn (Media $mediaItem) => $this->mediaFileExists($mediaItem));
    }

    protected function mediaFileExists(Media $media): bool
    {
        $disk = $media->disk ?? config('media-library.disk_name');
        $path = $media->getPathRelativeToRoot();

        return Storage::disk($disk)->exists($path);
    }
}
