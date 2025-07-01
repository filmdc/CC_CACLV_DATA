<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PovertyGuideline extends Model
{
    protected $fillable = [
        'year',
        'region',
        'family_size',
        'poverty_50',
        'poverty_75',
        'poverty_100',
        'poverty_125',
        'poverty_130',
        'poverty_150',
        'poverty_200',
        'poverty_250',
        'poverty_300',
        'poverty_400',
        'poverty_500',
        'period'
    ];

    protected $casts = [
        'year' => 'integer',
        'family_size' => 'integer',
        'poverty_50' => 'decimal:2',
        'poverty_75' => 'decimal:2',
        'poverty_100' => 'decimal:2',
        'poverty_125' => 'decimal:2',
        'poverty_130' => 'decimal:2',
        'poverty_150' => 'decimal:2',
        'poverty_200' => 'decimal:2',
        'poverty_250' => 'decimal:2',
        'poverty_300' => 'decimal:2',
        'poverty_400' => 'decimal:2',
        'poverty_500' => 'decimal:2'
    ];

    public static function calculatePovertyPercentage($income, $familySize, $period = 'annual', $year = null, $region = '48_contiguous')
    {
        $year = $year ?? date('Y');
        
        $guideline = static::where('year', $year)
            ->where('region', $region)
            ->where('family_size', $familySize)
            ->where('period', $period)
            ->first();

        // If we don't have exact family size, calculate for larger families
        if (!$guideline && $familySize > 4) {
            // Get the guideline for family size 4
            $baseLine = static::where('year', $year)
                ->where('region', $region)
                ->where('family_size', 4)
                ->where('period', $period)
                ->first();
                
            if ($baseLine) {
                // For each additional family member over 4, add standard increment
                // 2025 HHS guidelines: $4,480 annually or $374 monthly per additional person
                $increment = ($period === 'annual') ? 4480 : 374;
                $additionalMembers = $familySize - 4;
                
                // Calculate poverty level for larger family
                $povertyLevel = $baseLine->poverty_100 + ($increment * $additionalMembers);
                return $povertyLevel > 0 ? round(($income / $povertyLevel) * 100, 2) : 0;
            }
        }

        if (!$guideline) {
            return null;
        }

        $povertyLevel = $guideline->poverty_100;
        return $povertyLevel > 0 ? round(($income / $povertyLevel) * 100, 2) : 0;
    }
}
