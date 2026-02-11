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
 * @property string $gift_id DonorPerfect gift_id
 * @property string $status Values: pending, success, failed, skipped, filtered
 * @property string|null $intacct_record_id
 * @property \Carbon\Carbon|null $sage_deposit_date Deposit date from Sage Intacct
 * @property \Carbon\Carbon|null $deposit_date_synced_at When deposit date was written back to DP
 * @property string|null $deposit_sync_error Error message if deposit date sync failed
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
        'gift_id',
        'status',
        'intacct_record_id',
        'sage_deposit_date',
        'deposit_date_synced_at',
        'deposit_sync_error',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'sage_deposit_date' => 'date',
        'deposit_date_synced_at' => 'datetime',
    ];

    /**
     * Sync item status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_SUCCESS = 'success';

    const STATUS_FAILED = 'failed';

    const STATUS_RESYNC = 'resync';

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
     * @param  string  $intacctRecordId  The Sage Intacct record ID
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
     * @param  string  $errorMessage  The error message
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
     * Retry a failed sync item (clears record ID - creates new in Sage)
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
     * Mark for resync (keeps record ID so old receipt can be deleted first)
     */
    public function markForResync(): bool
    {
        return $this->update([
            'status' => self::STATUS_RESYNC,
            'error_message' => null,
        ]);
    }

    /**
     * Check if this item can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if this item can be resynced (update existing in Sage)
     */
    public function canResync(): bool
    {
        return $this->status === self::STATUS_SUCCESS && ! empty($this->intacct_record_id);
    }

    /**
     * Get the gift data for this sync item
     */
    public function getGiftData(): ?array
    {
        if (! $this->giftCache) {
            return null;
        }

        return $this->giftCache->gift_data;
    }

    /**
     * Check if this item needs deposit date sync
     * (Successfully synced to Sage, has Intacct record ID, but no deposit date synced yet)
     */
    public function needsDepositDateSync(): bool
    {
        return $this->status === self::STATUS_SUCCESS
            && ! empty($this->intacct_record_id)
            && is_null($this->deposit_date_synced_at);
    }

    /**
     * Check if this item has a deposit date from Sage
     */
    public function hasDepositDate(): bool
    {
        return ! is_null($this->sage_deposit_date);
    }

    /**
     * Update the Sage deposit date
     *
     * @param  string|null  $depositDate  The deposit date from Sage (Y-m-d format)
     */
    public function updateSageDepositDate(?string $depositDate): bool
    {
        return $this->update([
            'sage_deposit_date' => $depositDate,
        ]);
    }

    /**
     * Mark the deposit date as synced to DonorPerfect
     */
    public function markDepositDateSynced(): bool
    {
        return $this->update([
            'deposit_date_synced_at' => now(),
            'deposit_sync_error' => null,
        ]);
    }

    /**
     * Mark the deposit date sync as failed
     *
     * @param  string  $errorMessage  The error message
     */
    public function markDepositDateSyncFailed(string $errorMessage): bool
    {
        return $this->update([
            'deposit_sync_error' => $errorMessage,
        ]);
    }

    /**
     * Scope: Items that need deposit date sync
     * (Successfully synced to Sage with an Intacct record ID, but deposit date not yet synced back to DP)
     */
    public function scopeNeedsDepositSync($query)
    {
        return $query->where('status', self::STATUS_SUCCESS)
            ->whereNotNull('intacct_record_id')
            ->whereNull('deposit_date_synced_at');
    }

    /**
     * Scope: Items that have a deposit date from Sage but not synced to DP
     */
    public function scopeHasDepositDatePendingSync($query)
    {
        return $query->where('status', self::STATUS_SUCCESS)
            ->whereNotNull('intacct_record_id')
            ->whereNotNull('sage_deposit_date')
            ->whereNull('deposit_date_synced_at');
    }

    /**
     * Scope: Items with deposit date sync errors
     */
    public function scopeWithDepositSyncErrors($query)
    {
        return $query->whereNotNull('deposit_sync_error');
    }
}
