<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PdfFormGeneration extends Model
{
    protected $fillable = [
        'template_id',
        'generatable_type',
        'generatable_id',
        'file_path',
        'original_filename',
        'file_size',
        'filled_field_count',
        'total_field_count',
        'generation_status',
        'error_message',
        'field_data',
        'milestone_document_id',
        'is_flattened',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'field_data' => 'array',
        'is_flattened' => 'boolean',
        'generated_at' => 'datetime',
        'filled_field_count' => 'integer',
        'total_field_count' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the template used for this generation
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PdfFormTemplate::class, 'template_id');
    }

    /**
     * Get the entity that triggered this generation (polymorphic)
     */
    public function generatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the milestone document this generation is attached to
     */
    public function milestoneDocument(): BelongsTo
    {
        return $this->belongsTo(MilestoneDocument::class, 'milestone_document_id');
    }

    /**
     * Get the user who generated this PDF
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scope to get successful generations
     */
    public function scopeSuccessful($query)
    {
        return $query->where('generation_status', 'success');
    }

    /**
     * Scope to get failed generations
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('generation_status', ['failed', 'partial']);
    }

    /**
     * Get full file path
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if generated file exists
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
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_field_count == 0) {
            return 0;
        }

        return round(($this->filled_field_count / $this->total_field_count) * 100, 1);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->generation_status) {
            'success' => 'green',
            'failed' => 'red',
            'partial' => 'yellow',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Delete generation and associated file
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($generation) {
            // Delete the physical file
            if ($generation->fileExists()) {
                @unlink($generation->full_path);
            }
        });
    }
}
