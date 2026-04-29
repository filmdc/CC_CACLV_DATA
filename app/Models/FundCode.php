<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'contract_id',
        'is_active',
        'starts_at',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];

    // Relationships

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function communicationLogs(): BelongsToMany
    {
        return $this->belongsToMany(CommunicationLog::class, 'communication_log_fund_code')
            ->withTimestamps();
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(FundCodeRequirement::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors

    public function getFullDisplayAttribute(): string
    {
        return "{$this->code} – {$this->description}";
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
