<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LegacyContractSyncRun Model
 *
 * One row per execution of the automatic legacy contract sync
 * (`contracts:sync-legacy`). Captures both what the comparison against the
 * legacy database found and what was actually applied, so a scheduled run is
 * auditable without trawling the log file.
 *
 * @property int $id
 * @property string $status Values: running, success, partial, failed, skipped
 * @property string $triggered_by Values: scheduled, manual, command
 * @property bool $dry_run
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int $detected_new
 * @property int $detected_updated
 * @property int $detected_deleted
 * @property int $unchanged_count
 * @property int $created_count
 * @property int $updated_count
 * @property array|null $errors
 * @property array|null $warnings
 * @property array|null $deleted_legacy_ids
 */
class LegacyContractSyncRun extends Model
{
    protected $fillable = [
        'status',
        'triggered_by',
        'dry_run',
        'started_at',
        'completed_at',
        'detected_new',
        'detected_updated',
        'detected_deleted',
        'unchanged_count',
        'created_count',
        'updated_count',
        'skipped_count',
        'due_dates_created',
        'due_dates_updated',
        'media_synced',
        'funders_created',
        'deferred_count',
        'errors',
        'warnings',
        'deleted_legacy_ids',
        'error_message',
        'duration_ms',
        'executed_by_user_id',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'errors' => 'array',
        'warnings' => 'array',
        'deleted_legacy_ids' => 'array',
        'duration_ms' => 'integer',
    ];

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    /**
     * The most recent completed run, used by the import screen's status panel.
     */
    public static function latestRun(): ?self
    {
        return static::whereNot('status', 'running')
            ->where('dry_run', false)
            ->latest('started_at')
            ->first();
    }

    /**
     * Did this run change anything in CAP TRACK?
     */
    public function madeChanges(): bool
    {
        return ($this->created_count + $this->updated_count) > 0;
    }

    public function hasProblems(): bool
    {
        return in_array($this->status, ['failed', 'partial'], true) || ! empty($this->errors);
    }

    /**
     * Human-readable one-line outcome for the UI and notification mail.
     */
    public function summaryLine(): string
    {
        if ($this->status === 'failed') {
            return 'Sync failed: '.($this->error_message ?: 'unknown error');
        }

        if (! $this->madeChanges()) {
            return 'No changes — legacy system is in step.';
        }

        $parts = [];
        if ($this->created_count) {
            $parts[] = $this->created_count.' imported';
        }
        if ($this->updated_count) {
            $parts[] = $this->updated_count.' updated';
        }
        if ($this->media_synced) {
            $parts[] = $this->media_synced.' attachment'.($this->media_synced === 1 ? '' : 's');
        }

        return implode(', ', $parts);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'partial' => 'yellow',
            'failed' => 'red',
            'skipped' => 'gray',
            default => 'blue',
        };
    }
}
