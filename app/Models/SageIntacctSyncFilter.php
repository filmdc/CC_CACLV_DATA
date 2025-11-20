<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SageIntacctSyncFilter extends Model
{
    protected $fillable = [
        'name',
        'field_name',
        'operator',
        'value',
        'action',
        'filter_group',
        'group_logic',
        'is_active',
        'priority',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'filter_group' => 'integer',
    ];

    /**
     * Scope: Only active filters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if a gift matches this filter's criteria
     */
    public function matches(array $giftData): bool
    {
        $fieldValue = $giftData[$this->field_name] ?? null;

        return match ($this->operator) {
            'equals' => $fieldValue == $this->value,
            'not_equals' => $fieldValue != $this->value,
            'contains' => is_string($fieldValue) && str_contains(strtoupper($fieldValue), strtoupper($this->value)),
            'not_contains' => is_string($fieldValue) && !str_contains(strtoupper($fieldValue), strtoupper($this->value)),
            'greater_than' => is_numeric($fieldValue) && floatval($fieldValue) > floatval($this->value),
            'less_than' => is_numeric($fieldValue) && floatval($fieldValue) < floatval($this->value),
            'greater_or_equal' => is_numeric($fieldValue) && floatval($fieldValue) >= floatval($this->value),
            'less_or_equal' => is_numeric($fieldValue) && floatval($fieldValue) <= floatval($this->value),
            'is_empty' => empty($fieldValue),
            'is_not_empty' => !empty($fieldValue),
            default => false,
        };
    }

    /**
     * Apply all active filters to a gift and determine if it should be excluded
     *
     * @param array $giftData Gift data to filter
     * @return array ['should_filter' => bool, 'reason' => string|null, 'matched_filters' => array]
     */
    public static function shouldFilterGift(array $giftData): array
    {
        $filters = self::active()->byPriority()->get();

        if ($filters->isEmpty()) {
            return ['should_filter' => false, 'reason' => null, 'matched_filters' => []];
        }

        $matchedFilters = [];

        foreach ($filters as $filter) {
            if ($filter->matches($giftData)) {
                $matchedFilters[] = [
                    'name' => $filter->name,
                    'rule' => "{$filter->field_name} {$filter->operator} {$filter->value}",
                    'action' => $filter->action,
                ];

                // If this is an exclude filter and it matches, filter out the gift
                if ($filter->action === 'exclude') {
                    Log::info('Sage Intacct: Gift filtered by rule', [
                        'gift_id' => $giftData['gift_id'] ?? 'unknown',
                        'filter_name' => $filter->name,
                        'field' => $filter->field_name,
                        'value' => $giftData[$filter->field_name] ?? null,
                        'operator' => $filter->operator,
                        'filter_value' => $filter->value,
                    ]);

                    return [
                        'should_filter' => true,
                        'reason' => "Excluded by filter: {$filter->name} ({$filter->field_name} {$filter->operator} {$filter->value})",
                        'matched_filters' => $matchedFilters,
                    ];
                }
            }
        }

        return ['should_filter' => false, 'reason' => null, 'matched_filters' => $matchedFilters];
    }

    /**
     * Get all active filters grouped by filter group
     */
    public static function getActiveByGroup(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()->byPriority()->get()->groupBy('filter_group');
    }
}
