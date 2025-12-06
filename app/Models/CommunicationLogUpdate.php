<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLogUpdate extends Model
{
    protected $fillable = [
        'communication_log_id',
        'update_content',
        'update_type',
        'created_by',
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

    // Display Attributes

    public function getUpdateTypeDisplayAttribute(): string
    {
        return match ($this->update_type) {
            'note' => 'Note',
            'follow_up' => 'Follow-up',
            'status_change' => 'Status Change',
            'outcome' => 'Outcome',
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
