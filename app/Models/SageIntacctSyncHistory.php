<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SageIntacctSyncHistory Model
 *
 * Tracks synchronization jobs between DonorPerfect and Sage Intacct.
 * Records the overall status and metrics for each sync operation.
 *
 * @property int $id
 * @property int|null $setting_id
 * @property string $status Values: running, success, failed, partial
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int $total_records
 * @property int $successful_count
 * @property int $failed_count
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SageIntacctSyncHistory extends Model
{
    protected $table = 'sage_intacct_sync_history';

    protected $fillable = [
        'setting_id',
        'status',
        'started_at',
        'completed_at',
        'reset_at',
        'reset_by_user_id',
        'reset_count',
        'sync_start_date',
        'sync_end_date',
        'executed_by_user_id',
        'environment_info',
        'configuration_snapshot',
        'total_records',
        'successful_count',
        'failed_count',
        'skipped_count',
        'error_message',
        'sync_notes',
        'triggered_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reset_at' => 'datetime',
        'sync_start_date' => 'date',
        'sync_end_date' => 'date',
        'environment_info' => 'array',
        'configuration_snapshot' => 'array',
        'total_records' => 'integer',
        'successful_count' => 'integer',
        'failed_count' => 'integer',
        'skipped_count' => 'integer',
        'reset_count' => 'integer',
    ];

    /**
     * Sync status constants
     */
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';

    /**
     * Get the Sage Intacct setting used for this sync
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(SageIntacctSetting::class, 'setting_id');
    }

    /**
     * Get the user who executed this sync
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'executed_by_user_id');
    }

    /**
     * Get the user who reset this sync
     */
    public function resetBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reset_by_user_id');
    }

    /**
     * Get the individual sync items for this sync job
     */
    public function syncItems(): HasMany
    {
        return $this->hasMany(SageIntacctSyncItem::class, 'sync_history_id');
    }

    /**
     * Mark the sync as currently running
     *
     * @return bool
     */
    public function markAsRunning(): bool
    {
        return $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the sync as successfully completed
     *
     * @return bool
     */
    public function markAsSuccess(): bool
    {
        return $this->update([
            'status' => self::STATUS_SUCCESS,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the sync as failed
     *
     * @param string|null $errorMessage
     * @return bool
     */
    public function markAsFailed(?string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark the sync as partially successful
     *
     * @return bool
     */
    public function markAsPartial(): bool
    {
        return $this->update([
            'status' => self::STATUS_PARTIAL,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the success rate as a percentage
     *
     * @return float
     */
    public function getSuccessRate(): float
    {
        if ($this->total_records === 0) {
            return 0.0;
        }

        return round(($this->successful_count / $this->total_records) * 100, 2);
    }

    /**
     * Get the duration of the sync in seconds
     *
     * @return int|null
     */
    public function getDurationSeconds(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Update counters from sync items
     *
     * @return bool
     */
    public function updateCounters(): bool
    {
        $total = $this->syncItems()->count();
        $successful = $this->syncItems()->where('status', SageIntacctSyncItem::STATUS_SUCCESS)->count();
        $failed = $this->syncItems()->where('status', SageIntacctSyncItem::STATUS_FAILED)->count();

        return $this->update([
            'total_records' => $total,
            'successful_count' => $successful,
            'failed_count' => $failed,
        ]);
    }

    /**
     * Finalize the sync based on results
     *
     * @return bool
     */
    public function finalize(): bool
    {
        $this->updateCounters();

        if ($this->failed_count === 0) {
            return $this->markAsSuccess();
        } elseif ($this->successful_count === 0) {
            return $this->markAsFailed('All records failed to sync');
        } else {
            return $this->markAsPartial();
        }
    }
}
