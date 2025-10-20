<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class DonorPerfectGiftCache extends Model
{
    protected $table = 'donor_perfect_gift_cache';

    protected $fillable = [
        'cache_key',
        'start_date',
        'end_date',
        'fields_hash',
        'gift_data',
        'record_count',
        'expires_at',
        'synced_to_intacct',
        'last_synced_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'gift_data' => 'array',
        'record_count' => 'integer',
        'expires_at' => 'datetime',
        'synced_to_intacct' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Generate a cache key from parameters
     */
    public static function generateCacheKey(string $startDate, string $endDate, array $fields): string
    {
        $fieldsHash = md5(implode(',', $fields));
        return md5($startDate . $endDate . $fieldsHash);
    }

    /**
     * Get cached data if valid
     */
    public static function getCached(string $startDate, string $endDate, array $fields): ?self
    {
        $cacheKey = self::generateCacheKey($startDate, $endDate, $fields);

        return self::where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Store gift data in cache
     */
    public static function storeCache(string $startDate, string $endDate, array $fields, array $giftData, int $cacheDurationMinutes = 60): self
    {
        $cacheKey = self::generateCacheKey($startDate, $endDate, $fields);
        $fieldsHash = md5(implode(',', $fields));

        // Delete existing cache entry for this key
        self::where('cache_key', $cacheKey)->delete();

        return self::create([
            'cache_key' => $cacheKey,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fields_hash' => $fieldsHash,
            'gift_data' => $giftData,
            'record_count' => count($giftData),
            'expires_at' => now()->addMinutes($cacheDurationMinutes),
        ]);
    }

    /**
     * Check if cache is still valid
     */
    public function isValid(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Clear expired cache entries
     */
    public static function clearExpired(): int
    {
        return self::where('expires_at', '<=', now())->delete();
    }

    /**
     * Clear all cache entries
     */
    public static function clearAll(): int
    {
        return self::query()->delete();
    }

    /**
     * Clear cache for specific date range
     */
    public static function clearForDateRange(string $startDate, string $endDate): int
    {
        return self::where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->delete();
    }

    /**
     * Get cache statistics
     */
    public static function getStatistics(): array
    {
        $total = self::count();
        $valid = self::where('expires_at', '>', now())->count();
        $expired = $total - $valid;

        return [
            'total_entries' => $total,
            'valid_entries' => $valid,
            'expired_entries' => $expired,
            'oldest_entry' => self::orderBy('created_at')->first()?->created_at,
            'newest_entry' => self::orderBy('created_at', 'desc')->first()?->created_at,
        ];
    }

    /**
     * Mark this gift cache entry as synced to Sage Intacct
     *
     * @return bool
     */
    public function markAsSynced(): bool
    {
        return $this->update([
            'synced_to_intacct' => true,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Scope to get unsynced gift cache entries
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnsynced(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('synced_to_intacct', false)
              ->orWhereNull('synced_to_intacct');
        });
    }
}
