<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ConditionalCodeMapping extends Model
{
    protected $fillable = [
        'campaign_code',
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
     * Scope: Filter by campaign code
     */
    public function scopeForCampaign($query, string $campaignCode)
    {
        return $query->where('campaign_code', $campaignCode);
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
     * Get the Intacct code for a specific campaign + field type + DP code combination
     * Returns null if no mapping exists
     */
    public static function getMapping(string $campaignCode, string $fieldType, string $dpCode): ?string
    {
        $mapping = self::where('campaign_code', $campaignCode)
            ->where('field_type', $fieldType)
            ->where('dp_code', $dpCode)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();

        if ($mapping) {
            Log::info('Conditional code mapping applied', [
                'campaign' => $campaignCode,
                'field_type' => $fieldType,
                'dp_code' => $dpCode,
                'intacct_code' => $mapping->intacct_code,
            ]);
            return $mapping->intacct_code;
        }

        return null;
    }

    /**
     * Check if a campaign has ANY conditional rules defined for a specific field type
     * Used to determine if validation should be applied
     */
    public static function hasRulesForCampaign(string $campaignCode, string $fieldType): bool
    {
        return self::where('campaign_code', $campaignCode)
            ->where('field_type', $fieldType)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all allowed DP codes for a specific campaign + field type combination
     * Returns empty array if no rules exist (meaning all codes are allowed)
     */
    public static function getAllowedCodes(string $campaignCode, string $fieldType): array
    {
        return self::where('campaign_code', $campaignCode)
            ->where('field_type', $fieldType)
            ->where('is_active', true)
            ->pluck('dp_code')
            ->toArray();
    }

    /**
     * Check if a specific DP code is allowed for a campaign + field type combination
     * If no rules exist for this campaign, all codes are allowed (returns true)
     * If rules exist but the code is not in the list, returns false
     */
    public static function isCodeAllowed(string $campaignCode, string $fieldType, string $dpCode): bool
    {
        // If no rules exist for this campaign + field type, all codes are allowed
        if (!self::hasRulesForCampaign($campaignCode, $fieldType)) {
            return true;
        }

        // Rules exist, so check if this specific code is in the allowed list
        return self::where('campaign_code', $campaignCode)
            ->where('field_type', $fieldType)
            ->where('dp_code', $dpCode)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all active conditional mappings grouped by campaign
     */
    public static function getActiveByCompany(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('campaign_code')
            ->orderBy('field_type')
            ->orderBy('priority', 'desc')
            ->get()
            ->groupBy('campaign_code');
    }
}
