<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Household extends Model
{
    protected $fillable = [
        'household_code',
        'household_type',
        'household_size',
        'housing_status',
        'monthly_income',
        'annual_income',
        'hhs_poverty_percentage',
        'street_address',
        'city',
        'state',
        'zip_code',
        'notes'
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'annual_income' => 'decimal:2',
        'hhs_poverty_percentage' => 'decimal:2'
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function headOfHousehold(): HasOne
    {
        return $this->hasOne(Participant::class)->where('is_head_of_household', true);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(Participant::class)->where('is_head_of_household', false);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($household) {
            if (empty($household->household_code)) {
                $household->household_code = static::generateHouseholdCode();
            }
        });
    }

    public static function generateHouseholdCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7));
        } while (static::where('household_code', $code)->exists());
        
        return $code;
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function housingJobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function getActualHouseholdSize(): int
    {
        return $this->participants()->count();
    }

    public function updateHouseholdSizeLogic(): void
    {
        $actualCount = $this->getActualHouseholdSize();
        $manualSize = $this->household_size ?? 0;
        
        // If actual count is greater than manual entry, update to actual count
        // Otherwise, keep the manual entry (staff attestation)
        if ($actualCount > $manualSize) {
            $this->update(['household_size' => $actualCount]);
        }
    }

    public function fixInflatedHouseholdSize(): void
    {
        $actualCount = $this->getActualHouseholdSize();
        
        // For fixing existing data, we use the actual participant count
        // This corrects inflated counts from the old increment/decrement logic
        $this->update(['household_size' => $actualCount]);
    }

    public static function fixAllInflatedHouseholdSizes(): int
    {
        $fixed = 0;
        $households = self::with('participants')->get();
        
        foreach ($households as $household) {
            $actualCount = $household->getActualHouseholdSize();
            $recordedSize = $household->household_size ?? 0;
            
            // Only fix if recorded size is greater than actual count (indicating inflation)
            if ($recordedSize > $actualCount) {
                $household->fixInflatedHouseholdSize();
                $fixed++;
            }
        }
        
        return $fixed;
    }
}
