<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractUtilizationNotification extends Model
{
    protected $fillable = [
        'contract_id',
        'threshold_percentage',
        'utilization_at_notification',
        'remaining_amount',
        'recent_expenses',
        'notified_at',
        'notification_type',
    ];

    protected $casts = [
        'threshold_percentage' => 'integer',
        'utilization_at_notification' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'recent_expenses' => 'array',
        'notified_at' => 'datetime',
    ];

    /**
     * Get the contract this notification belongs to
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Check if a specific threshold has already been notified for a contract
     */
    public static function hasBeenNotified(int $contractId, int $threshold): bool
    {
        return self::where('contract_id', $contractId)
            ->where('threshold_percentage', $threshold)
            ->exists();
    }

    /**
     * Get the next threshold that needs notification for a contract
     *
     * @param  int  $contractId
     * @param  float  $remainingPercent  Percentage of budget remaining
     * @param  array  $thresholds  Array of thresholds to check (e.g., [75, 50, 25, 10, 5, 0])
     * @return int|null The threshold to notify, or null if none
     */
    public static function getNextThreshold(int $contractId, float $remainingPercent, array $thresholds): ?int
    {
        // Sort thresholds in descending order (75, 50, 25, 10, 5, 0)
        rsort($thresholds);

        foreach ($thresholds as $threshold) {
            // If remaining percent is at or below this threshold and not yet notified
            if ($remainingPercent <= $threshold && ! self::hasBeenNotified($contractId, $threshold)) {
                return $threshold;
            }
        }

        return null;
    }

    /**
     * Get all unnotified thresholds that have been crossed
     */
    public static function getAllCrossedThresholds(int $contractId, float $remainingPercent, array $thresholds): array
    {
        rsort($thresholds);
        $crossedThresholds = [];

        foreach ($thresholds as $threshold) {
            if ($remainingPercent <= $threshold && ! self::hasBeenNotified($contractId, $threshold)) {
                $crossedThresholds[] = $threshold;
            }
        }

        return $crossedThresholds;
    }

    /**
     * Clear notifications above a certain threshold (when utilization drops)
     */
    public static function clearThresholdsAbove(int $contractId, float $remainingPercent): int
    {
        return self::where('contract_id', $contractId)
            ->where('threshold_percentage', '<', $remainingPercent)
            ->delete();
    }
}
