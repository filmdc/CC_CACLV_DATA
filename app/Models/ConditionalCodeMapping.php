<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ConditionalCodeMapping extends Model
{
    protected $fillable = [
        'intacct_location_code',
        'field_type',
        'dp_code',
        'intacct_code',
        'is_active',
        'priority',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Boot method to register model events
     */
    protected static function booted(): void
    {
        // When a conditional mapping is created, updated, or deleted,
        // invalidate the gift cache so unsynced gifts will be revalidated
        static::created(function ($mapping) {
            self::invalidateGiftCache($mapping);
        });

        static::updated(function ($mapping) {
            self::invalidateGiftCache($mapping);
        });

        static::deleted(function ($mapping) {
            self::invalidateGiftCache($mapping);
        });
    }

    /**
     * Invalidate gift cache for affected location
     * This ensures unsynced gifts will be revalidated against new rules
     */
    private static function invalidateGiftCache($mapping): void
    {
        $clearedCount = \App\Models\DonorPerfectGiftCache::where(function ($query) {
            $query->where('synced_to_intacct', false)
                  ->orWhereNull('synced_to_intacct');
        })->delete();

        Log::info('Conditional mapping changed - gift cache invalidated', [
            'intacct_location_code' => $mapping->intacct_location_code,
            'field_type' => $mapping->field_type,
            'dp_code' => $mapping->dp_code,
            'cache_entries_cleared' => $clearedCount,
            'reason' => 'Unsynced gifts will be re-fetched and revalidated on next sync',
        ]);
    }

    /**
     * Scope: Filter by Sage Intacct location code
     */
    public function scopeForLocation($query, string $intacctLocationCode)
    {
        return $query->where('intacct_location_code', $intacctLocationCode);
    }

    /**
     * Scope: Filter by field type
     */
    public function scopeForFieldType($query, string $fieldType)
    {
        return $query->where('field_type', $fieldType);
    }

    /**
     * Scope: Only active mappings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the Intacct code for a specific Intacct location + field type + DP code combination
     * Returns null if no mapping exists
     */
    public static function getMapping(string $intacctLocationCode, string $fieldType, string $dpCode): ?string
    {
        $mapping = self::where('intacct_location_code', $intacctLocationCode)
            ->where('field_type', $fieldType)
            ->where('dp_code', $dpCode)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();

        if ($mapping) {
            Log::info('Conditional code mapping applied', [
                'intacct_location' => $intacctLocationCode,
                'field_type' => $fieldType,
                'dp_code' => $dpCode,
                'intacct_code' => $mapping->intacct_code,
            ]);
            return $mapping->intacct_code;
        }

        return null;
    }

    /**
     * Check if an Intacct location has ANY conditional rules defined for a specific field type
     * Used to determine if validation should be applied
     */
    public static function hasRulesForLocation(string $intacctLocationCode, string $fieldType): bool
    {
        return self::where('intacct_location_code', $intacctLocationCode)
            ->where('field_type', $fieldType)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all allowed DP codes for a specific Intacct location + field type combination
     * Returns empty array if no rules exist (meaning all codes are allowed)
     */
    public static function getAllowedCodes(string $intacctLocationCode, string $fieldType): array
    {
        return self::where('intacct_location_code', $intacctLocationCode)
            ->where('field_type', $fieldType)
            ->where('is_active', true)
            ->pluck('dp_code')
            ->toArray();
    }

    /**
     * Check if a specific DP code is allowed for an Intacct location + field type combination
     * If no rules exist for this location, all codes are allowed (returns true)
     * If rules exist but the code is not in the list, returns false
     */
    public static function isCodeAllowed(string $intacctLocationCode, string $fieldType, string $dpCode): bool
    {
        // If no rules exist for this location + field type, all codes are allowed
        if (!self::hasRulesForLocation($intacctLocationCode, $fieldType)) {
            return true;
        }

        // Rules exist, so check if this specific code is in the allowed list
        return self::where('intacct_location_code', $intacctLocationCode)
            ->where('field_type', $fieldType)
            ->where('dp_code', $dpCode)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all active conditional mappings grouped by Intacct location
     */
    public static function getActiveByLocation(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('intacct_location_code')
            ->orderBy('field_type')
            ->orderBy('priority', 'desc')
            ->get()
            ->groupBy('intacct_location_code');
    }
}
