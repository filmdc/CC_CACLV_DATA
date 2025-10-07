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

    public function addresses(): HasMany
    {
        return $this->hasMany(HouseholdAddress::class)->orderBy('effective_date', 'desc');
    }

    public function currentAddress(): HasOne
    {
        return $this->hasOne(HouseholdAddress::class)->where('is_current', true);
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

    /**
     * Update the household's address, creating a new address history record.
     */
    public function updateAddress(array $addressData, $reason = null)
    {
        // First, mark any current address as not current
        $this->currentAddress?->update([
            'is_current' => false,
            'end_date' => now()->format('Y-m-d')
        ]);

        // Create the new address
        return $this->addresses()->create(array_merge($addressData, [
            'is_current' => true,
            'effective_date' => now()->format('Y-m-d'),
            'reason_for_change' => $reason
        ]));
    }

    /**
     * Scope to filter households by user's program access.
     * User can access household if any member is enrolled in their programs.
     */
    public function scopeForUserPrograms($query, $user)
    {
        // Admins see all households
        if ($user->isAdmin()) {
            return $query;
        }

        $userProgramIds = $user->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Return households where any participant is enrolled in user's programs
        return $query->whereHas('participants.programEnrollments', function ($q) use ($userProgramIds) {
            $q->whereIn('program_id', $userProgramIds);
        });
    }

    /**
     * Define sensitive household fields.
     */
    public static function getSensitiveFields(): array
    {
        return [
            'household_type',
            'housing_status',
            'monthly_income',
            'annual_income',
            'hhs_poverty_percentage',
            'notes'
        ];
    }

    /**
     * Define public household fields.
     */
    public static function getPublicFields(): array
    {
        return [
            'id',
            'household_code',
            'household_size',
            'created_at',
            'updated_at'
        ];
    }
}
