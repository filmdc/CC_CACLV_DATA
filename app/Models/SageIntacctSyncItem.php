<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SageIntacctSyncItem Model
 *
 * Tracks individual gift records synchronized from DonorPerfect to Sage Intacct.
 * Each item represents one gift's sync attempt within a sync job.
 *
 * @property int $id
 * @property int $sync_history_id
 * @property int|null $gift_cache_id
 * @property string $status Values: pending, success, failed
 * @property string|null $intacct_record_id
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $synced_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SageIntacctSyncItem extends Model
{
    protected $table = 'sage_intacct_sync_items';

    protected $fillable = [
        'sync_history_id',
        'gift_cache_id',
        'status',
        'intacct_record_id',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    /**
     * Sync item status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Get the sync history this item belongs to
     */
    public function syncHistory(): BelongsTo
    {
        return $this->belongsTo(SageIntacctSyncHistory::class, 'sync_history_id');
    }

    /**
     * Get the DonorPerfect gift cache record
     */
    public function giftCache(): BelongsTo
    {
        return $this->belongsTo(DonorPerfectGiftCache::class, 'gift_cache_id');
    }

    /**
     * Mark this sync item as successful
     *
     * @param string $intacctRecordId The Sage Intacct record ID
     * @return bool
     */
    public function markAsSuccess(string $intacctRecordId): bool
    {
        $updated = $this->update([
            'status' => self::STATUS_SUCCESS,
            'intacct_record_id' => $intacctRecordId,
            'synced_at' => now(),
            'error_message' => null,
        ]);

        // Also mark the gift cache as synced
        if ($updated && $this->giftCache) {
            $this->giftCache->markAsSynced();
        }

        return $updated;
    }

    /**
     * Mark this sync item as failed
     *
     * @param string $errorMessage The error message
     * @return bool
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'synced_at' => now(),
        ]);
    }

    /**
     * Retry a failed sync item
     *
     * @return bool
     */
    public function retry(): bool
    {
        return $this->update([
            'status' => self::STATUS_PENDING,
            'error_message' => null,
            'intacct_record_id' => null,
        ]);
    }

    /**
     * Check if this item can be retried
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get the gift data for this sync item
     *
     * @return array|null
     */
    public function getGiftData(): ?array
    {
        if (!$this->giftCache) {
            return null;
        }

        return $this->giftCache->gift_data;
    }
}
