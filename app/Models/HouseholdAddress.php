<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class HouseholdAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'address',
        'city',
        'state',
        'zip_code',
        'county',
        'area',
        'primary_phone',
        'is_current',
        'effective_date',
        'end_date',
        'created_by',
        'reason_for_change',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the household that owns the address.
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Get the user who created this address record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only current addresses.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get addresses that were effective on a specific date.
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    });
    }

    /**
     * Mark this address as current and mark all others as not current.
     */
    public function markAsCurrent()
    {
        // Mark all other addresses for this household as not current
        static::where('household_id', $this->household_id)
              ->where('id', '!=', $this->id)
              ->where('is_current', true)
              ->update([
                  'is_current' => false,
                  'end_date' => now()->format('Y-m-d')
              ]);

        // Mark this address as current
        $this->update([
            'is_current' => true,
            'end_date' => null
        ]);
    }

    /**
     * Get full address as a single string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code
        ]);

        if (empty($parts)) {
            return 'No address on file';
        }

        $addressLine = $this->address ?? '';
        $cityStateZip = implode(' ', array_filter([
            $this->city,
            $this->state ? ', ' . $this->state : '',
            $this->zip_code
        ]));

        return trim($addressLine . "\n" . $cityStateZip);
    }

    /**
     * Get city, state, zip as a single line.
     */
    public function getCityStateZipAttribute()
    {
        $parts = array_filter([
            $this->city,
            $this->state,
            $this->zip_code
        ]);

        if (empty($parts)) {
            return '';
        }

        return $this->city . 
               ($this->state ? ', ' . $this->state : '') . 
               ($this->zip_code ? ' ' . $this->zip_code : '');
    }

    /**
     * Boot method to set created_by automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            if (empty($address->created_by)) {
                $address->created_by = Auth::id();
            }
            
            // If this is the first address for the household, ensure it's current
            $existingCount = static::where('household_id', $address->household_id)->count();
            if ($existingCount === 0) {
                $address->is_current = true;
            }
        });
    }
}