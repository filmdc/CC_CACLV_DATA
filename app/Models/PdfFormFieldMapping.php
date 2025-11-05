<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfFormFieldMapping extends Model
{
    protected $fillable = [
        'template_id',
        'field_name',
        'field_type',
        'field_label',
        'mapping_type',
        'model_name',
        'model_field',
        'relationship_path',
        'custom_value',
        'computed_method',
        'field_flags',
        'field_options',
        'is_required',
        'validation_rules',
        'position_x',
        'position_y',
        'width',
        'height',
        'page_number',
        'display_order',
        'notes',
    ];

    protected $casts = [
        'field_flags' => 'array',
        'field_options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'page_number' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the template this mapping belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PdfFormTemplate::class, 'template_id');
    }

    /**
     * Scope to get mapped fields only
     */
    public function scopeMapped($query)
    {
        return $query->where('mapping_type', '!=', 'unmapped');
    }

    /**
     * Scope to get unmapped fields
     */
    public function scopeUnmapped($query)
    {
        return $query->where('mapping_type', 'unmapped');
    }

    /**
     * Scope to filter by mapping type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('mapping_type', $type);
    }

    /**
     * Check if this field is mapped
     */
    public function isMapped(): bool
    {
        return $this->mapping_type !== 'unmapped';
    }

    /**
     * Get the full mapping path (model.field or relationship.field)
     */
    public function getFullMappingPathAttribute(): ?string
    {
        if ($this->mapping_type === 'custom_value') {
            return 'Custom: ' . $this->custom_value;
        }

        if ($this->mapping_type === 'computed') {
            return 'Computed: ' . $this->computed_method;
        }

        if ($this->mapping_type === 'unmapped') {
            return null;
        }

        $parts = [];
        if ($this->model_name) {
            $parts[] = $this->model_name;
        }
        if ($this->relationship_path) {
            $parts[] = $this->relationship_path;
        }
        if ($this->model_field) {
            $parts[] = $this->model_field;
        }

        return implode('.', $parts);
    }

    /**
     * Get mapping type label for display
     */
    public function getMappingTypeLabelAttribute(): string
    {
        return match($this->mapping_type) {
            'model_field' => 'Database Field',
            'custom_value' => 'Custom Value',
            'computed' => 'Computed',
            'unmapped' => 'Not Mapped',
            default => ucfirst($this->mapping_type),
        };
    }

    /**
     * Get field type icon
     */
    public function getFieldTypeIconAttribute(): string
    {
        return match($this->field_type) {
            'text' => '📝',
            'checkbox' => '☑️',
            'radio' => '🔘',
            'dropdown' => '📋',
            'signature' => '✍️',
            default => '📄',
        };
    }
}
