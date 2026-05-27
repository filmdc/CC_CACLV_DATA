<?php

namespace App\Models;

use App\Contracts\HasMediaAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MilestoneCommunication extends Model implements HasMedia, HasMediaAuthorization
{
    use InteractsWithMedia;

    /**
     * P-39a: rides on the ProjectManagement page-level gate.
     */
    public function canViewMedia(User $user, Media $media): bool
    {
        return $user->hasPermission('projects.view');
    }

    protected $fillable = [
        'milestone_id',
        'subject',
        'content',
        'created_by',
    ];

    protected $appends = ['has_attachments'];

    // Relationships

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Computed Attributes

    public function getHasAttachmentsAttribute(): bool
    {
        return $this->getMedia()->count() > 0;
    }

    // Business Logic Methods

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M d, Y g:i A');
    }
}
