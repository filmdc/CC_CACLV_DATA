<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
        'module',
        'icon',
        'requires_document',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_document' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function approvalChains(): HasMany
    {
        return $this->hasMany(ApprovalChain::class)->orderBy('step_number');
    }

    public function pendingApprovals(): HasMany
    {
        return $this->hasMany(PendingApproval::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasMultipleChainSteps(): bool
    {
        return $this->approvalChains()->where('is_active', true)->count() > 1;
    }

    public function getChainForStep(int $step): ?ApprovalChain
    {
        return $this->approvalChains()->where('step_number', $step)->where('is_active', true)->first();
    }

    public function getTotalSteps(): int
    {
        return $this->approvalChains()->where('is_active', true)->count();
    }
}
