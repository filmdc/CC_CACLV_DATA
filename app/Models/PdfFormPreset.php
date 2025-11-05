<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfFormPreset extends Model
{
    protected $fillable = [
        'name',
        'description',
        'template_id',
        'preset_type',
        'mapping_config',
        'is_default',
        'is_shared',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mapping_config' => 'array',
        'is_default' => 'boolean',
        'is_shared' => 'boolean',
    ];

    /**
     * Get the template this preset belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PdfFormTemplate::class, 'template_id');
    }

    /**
     * Get the user who created this preset
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this preset
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get shared presets
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope to get default preset
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by preset type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('preset_type', $type);
    }

    /**
     * Get preset type label
     */
    public function getPresetTypeLabelAttribute(): string
    {
        return match($this->preset_type) {
            'template_specific' => 'Template Specific',
            'global' => 'Global',
            'user_custom' => 'User Custom',
            default => ucfirst($this->preset_type),
        };
    }
}
