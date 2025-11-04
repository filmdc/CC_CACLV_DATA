<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContact extends Model
{
    protected $fillable = [
        'housing_job_id',
        'contact_type',
        'name',
        'phone',
        'email',
        'relationship',
        'notes',
        'is_primary',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the housing job this contact belongs to
     */
    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    /**
     * Get the user who created this contact
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this contact
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get primary contacts
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to filter by contact type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('contact_type', $type);
    }

    /**
     * Get formatted contact info for display
     */
    public function getFormattedContactAttribute(): string
    {
        $parts = [$this->name];

        if ($this->phone) {
            $parts[] = $this->phone;
        }

        if ($this->email) {
            $parts[] = $this->email;
        }

        return implode(' • ', $parts);
    }

    /**
     * Get contact type label
     */
    public function getContactTypeLabelAttribute(): string
    {
        return match($this->contact_type) {
            'proxy' => 'Proxy',
            'emergency' => 'Emergency Contact',
            'contractor_liaison' => 'Contractor Liaison',
            'other' => 'Other Contact',
            default => ucfirst($this->contact_type),
        };
    }
}
