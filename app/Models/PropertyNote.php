<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyNote extends Model
{
    protected $fillable = [
        'property_id',
        'subject',
        'note',
        'note_type',
        'is_private',
        'created_by',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNoteTypeDisplayAttribute(): string
    {
        return match ($this->note_type) {
            'general' => 'General',
            'inspection' => 'Inspection',
            'maintenance' => 'Maintenance',
            'legal' => 'Legal',
            'financial' => 'Financial',
            'other' => 'Other',
            default => $this->note_type,
        };
    }

    public function getNoteTypeColorAttribute(): string
    {
        return match ($this->note_type) {
            'general' => 'gray',
            'inspection' => 'blue',
            'maintenance' => 'yellow',
            'legal' => 'purple',
            'financial' => 'green',
            'other' => 'indigo',
            default => 'gray',
        };
    }
}
