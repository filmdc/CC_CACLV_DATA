<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MilestoneCommunication extends Model implements HasMedia
{
    use InteractsWithMedia;

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
