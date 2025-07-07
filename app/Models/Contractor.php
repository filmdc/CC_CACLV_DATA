<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    protected $fillable = [
        'company_name',
        'contact_first_name',
        'contact_last_name',
        'email',
        'phone',
        'fax',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'license_number',
        'license_expiration',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiration',
        'liability_coverage_amount',
        'ein',
        'services_offered',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'license_expiration' => 'date',
        'insurance_expiration' => 'date',
        'liability_coverage_amount' => 'decimal:2',
        'services_offered' => 'array',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractorDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getContactNameAttribute(): ?string
    {
        if ($this->contact_first_name || $this->contact_last_name) {
            return trim($this->contact_first_name . ' ' . $this->contact_last_name);
        }
        return null;
    }

    public function getFullAddressAttribute(): ?string
    {
        if (!$this->address_line_1) {
            return null;
        }
        
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        if ($this->city && $this->state && $this->zip_code) {
            $address .= ', ' . $this->city . ', ' . $this->state . ' ' . $this->zip_code;
        }
        
        return $address;
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expiration && $this->license_expiration->isPast();
    }

    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiration && $this->insurance_expiration->isPast();
    }
}
