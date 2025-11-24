<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasLegacySyncScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funder extends Model
{
    use SoftDeletes, Auditable, HasLegacySyncScopes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'website',
        'active',
        'created_by',
        'updated_by',
        'legacy_id',
        'last_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public static $searchable = [
        'name',
        'code',
        'contact_person',
        'contact_email',
    ];

    /**
     * Get all contracts for this funder
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get active contracts for this funder
     */
    public function activeContracts(): HasMany
    {
        return $this->hasMany(Contract::class)->where('archived', false);
    }

    /**
     * Scope to get only active funders
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the funder's display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }

}
