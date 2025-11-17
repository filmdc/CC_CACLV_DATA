<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignServiceOverride extends Model
{
    protected $fillable = [
        'campaign_code',
        'intacct_location_code',
        'service_code',
        'project_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get override for a campaign code
     */
    public static function getOverride(string $campaignCode): ?self
    {
        return self::where('campaign_code', $campaignCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active overrides
     */
    public static function getActiveOverrides(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)->get();
    }
}
