<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * SageIntacctFieldMapping Model
 *
 * Maps DonorPerfect fields to Sage Intacct fields for data synchronization.
 * Each mapping belongs to a Sage Intacct configuration.
 *
 * @property int $id
 * @property int $setting_id
 * @property string $donor_perfect_field
 * @property string $intacct_field
 * @property string|null $default_value
 * @property bool $is_required
 * @property int $order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SageIntacctFieldMapping extends Model
{
    protected $table = 'sage_intacct_field_mappings';

    protected $fillable = [
        'setting_id',
        'donor_perfect_field',
        'intacct_field',
        'default_value',
        'is_required',
        'order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the Sage Intacct setting this mapping belongs to
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(SageIntacctSetting::class, 'setting_id');
    }

    /**
     * Scope to order mappings by their order field
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrderByOrder(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Get the value to use for this mapping from gift data
     *
     * @param array $giftData
     * @return mixed
     */
    public function getValueFromGift(array $giftData)
    {
        // Check if the DonorPerfect field exists in the gift data
        if (isset($giftData[$this->donor_perfect_field])) {
            return $giftData[$this->donor_perfect_field];
        }

        // Return default value if field doesn't exist
        return $this->default_value;
    }

    /**
     * Validate that a gift has all required field values
     *
     * @param array $giftData
     * @return bool
     */
    public function hasRequiredValue(array $giftData): bool
    {
        if (!$this->is_required) {
            return true;
        }

        $value = $this->getValueFromGift($giftData);

        return !empty($value) || $value === 0 || $value === '0';
    }
}
