<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'import_type',
        'file_name',
        'file_size',
        'reference_data_set_id',
        'records_processed',
        'records_created',
        'records_updated',
        'records_skipped',
        'status',
        'started_at',
        'completed_at',
        'error_summary',
        'ip_address',
        'user_agent',
        'options'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'options' => 'array',
        'records_processed' => 'integer',
        'records_created' => 'integer',
        'records_updated' => 'integer',
        'records_skipped' => 'integer',
        'file_size' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referenceDataSet(): BelongsTo
    {
        return $this->belongsTo(ReferenceDataSet::class);
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        return null;
    }

    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration;
        if (!$duration) {
            return 'N/A';
        }

        if ($duration < 60) {
            return "{$duration} seconds";
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        return "{$minutes} minutes, {$seconds} seconds";
    }

    public static function logImport(array $data): self
    {
        return self::create(array_merge($data, [
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'started_at' => now()
        ]));
    }

    public function markCompleted(array $results): void
    {
        $this->update([
            'status' => $results['success'] ? 'completed' : 'failed',
            'completed_at' => now(),
            'records_created' => $results['processed_participants'] ?? 0,
            'records_updated' => $results['updated_participants'] ?? 0,
            'records_skipped' => $results['skipped_records'] ?? 0,
            'error_summary' => !$results['success'] ? ($results['error'] ?? 'Unknown error') : null
        ]);
    }
}