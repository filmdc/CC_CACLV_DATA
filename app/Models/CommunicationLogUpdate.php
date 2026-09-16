<?php

namespace App\Models;

use App\Models\Concerns\TracksSoftDeleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One entry on a call log's "Every touch" timeline.
 *
 * Entries are correctable and removable, but never destroyed: a removal
 * soft-deletes the row so it drops out of the timeline (and out of every
 * count taken off the `updates()` relation) while staying restorable in
 * place. `edited_at` / `edited_by` mark an entry whose wording was
 * changed after the fact, so the timeline never quietly rewrites itself.
 */
class CommunicationLogUpdate extends Model
{
    use SoftDeletes;
    use TracksSoftDeleter;

    /**
     * Entries the system writes about itself — requirement transitions,
     * conversions, merges. Their wording is a statement of record, so the
     * portal will not let anyone rewrite one.
     */
    public const TYPE_SYSTEM = 'status_change';

    /** The kinds a person can choose when correcting an entry. */
    public const AUTHORED_TYPES = [
        'note' => 'Note',
        'follow_up' => 'Follow-up',
        'action' => 'Action',
        'email' => 'Email',
        'outcome' => 'Outcome',
        'other' => 'Other',
    ];

    protected $fillable = [
        'communication_log_id',
        'update_content',
        'update_type',
        'update_date',
        'created_by',
    ];

    protected $casts = [
        'update_date' => 'date',
        'edited_at' => 'datetime',
    ];

    protected $appends = [
        'update_type_display',
        'update_type_color',
    ];

    // Relationships

    public function communicationLog(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    // Authorship

    /**
     * Written by the system rather than typed by a person. These can be
     * removed (by an admin) but never reworded — see TYPE_SYSTEM.
     */
    public function isSystemWritten(): bool
    {
        return $this->update_type === self::TYPE_SYSTEM;
    }

    public function wasEdited(): bool
    {
        return $this->edited_at !== null;
    }

    // Display Attributes

    public function getUpdateTypeDisplayAttribute(): string
    {
        return match ($this->update_type) {
            'note' => 'Note',
            'follow_up' => 'Follow-up',
            'status_change' => 'Status Change',
            'outcome' => 'Outcome',
            'action' => 'Action',
            'email' => 'Email',
            'other' => 'Other',
            default => $this->update_type ?? 'Note',
        };
    }

    public function getUpdateTypeColorAttribute(): string
    {
        return match ($this->update_type) {
            'note' => 'gray',
            'follow_up' => 'amber',
            'status_change' => 'blue',
            'outcome' => 'green',
            'action' => 'amber',
            'email' => 'teal',
            'other' => 'purple',
            default => 'gray',
        };
    }

    // Scopes

    public function scopeByType($query, string $type)
    {
        return $query->where('update_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
