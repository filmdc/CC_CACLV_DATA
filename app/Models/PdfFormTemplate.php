<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PdfFormTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'field_count',
        'last_field_scan',
        'is_active',
        'usage_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_field_scan' => 'datetime',
        'field_count' => 'integer',
        'usage_count' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the field mappings for this template
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(PdfFormFieldMapping::class, 'template_id')->orderBy('display_order');
    }

    /**
     * Get the generations from this template
     */
    public function generations(): HasMany
    {
        return $this->hasMany(PdfFormGeneration::class, 'template_id');
    }

    /**
     * Get the presets for this template
     */
    public function presets(): HasMany
    {
        return $this->hasMany(PdfFormPreset::class, 'template_id');
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get full file path for storage
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if template file exists
     */
    public function fileExists(): bool
    {
        return file_exists($this->full_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the number of mapped fields
     */
    public function getMappedFieldCountAttribute(): int
    {
        return $this->fieldMappings()->where('mapping_type', '!=', 'unmapped')->count();
    }

    /**
     * Get mapping completion percentage
     */
    public function getMappingCompletionAttribute(): float
    {
        if ($this->field_count == 0) {
            return 0;
        }

        return round(($this->mapped_field_count / $this->field_count) * 100, 1);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Delete template and associated file
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($template) {
            // Delete the physical file
            if ($template->fileExists()) {
                @unlink($template->full_path);
            }

            // Delete all field mappings (cascade)
            $template->fieldMappings()->delete();

            // Delete all presets
            $template->presets()->delete();
        });
    }
}
