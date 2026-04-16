<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LocationDefaultCode extends Model
{
    protected $fillable = [
        'intacct_location_code',
        'field_type',
        'default_intacct_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Only active defaults
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by location
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
     * Get the default Intacct code for a location + field type when the DP code is empty.
     * Returns null if no default is configured.
     */
    public static function getDefault(string $intacctLocationCode, string $fieldType): ?string
    {
        $default = self::where('intacct_location_code', $intacctLocationCode)
            ->where('field_type', $fieldType)
            ->where('is_active', true)
            ->first();

        if ($default) {
            Log::info('Location default code applied', [
                'intacct_location' => $intacctLocationCode,
                'field_type' => $fieldType,
                'default_intacct_code' => $default->default_intacct_code,
            ]);

            return $default->default_intacct_code;
        }

        return null;
    }

    /**
     * Check if a default exists for a location + field type.
     */
    public static function hasDefault(string $intacctLocationCode, string $fieldType): bool
    {
        return self::where('intacct_location_code', $intacctLocationCode)
            ->where('field_type', $fieldType)
            ->where('is_active', true)
            ->exists();
    }
}
