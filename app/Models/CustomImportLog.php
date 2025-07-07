<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomImportLog extends Model
{
    protected $fillable = [
        'template_id',
        'user_id',
        'original_filename',
        'status',
        'total_records',
        'successful_records',
        'failed_records',
        'errors',
        'warnings',
        'notes',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'errors' => 'array',
        'warnings' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CustomImportTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function getSuccessRate(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->successful_records / $this->total_records) * 100, 2);
    }

    public function getDurationInSeconds(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }
}