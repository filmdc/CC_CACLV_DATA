<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'household_id',
        'property_type_id',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'county',
        'latitude',
        'longitude',
        'year_built',
        'bedrooms',
        'bathrooms',
        'square_footage',
        'property_description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year_built' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'decimal:1',
        'square_footage' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(ReferenceData::class, 'property_type_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PropertyNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function propertyDocuments(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function housingJobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', '.$this->address_line_2;
        }
        $address .= ', '.$this->city.', '.$this->state.' '.$this->zip_code;

        return $address;
    }
}
