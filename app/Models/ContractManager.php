<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractManager extends Model
{
    protected $fillable = [
        'contract_id',
        'user_id',
        'receive_notifications',
        'created_by',
    ];

    protected $casts = [
        'receive_notifications' => 'boolean',
    ];

    /**
     * Get the contract this manager is assigned to
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who is the manager
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this assignment
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only managers receiving notifications
     */
    public function scopeReceivingNotifications($query)
    {
        return $query->where('receive_notifications', true);
    }

    /**
     * Scope to get managers for a specific contract
     */
    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }
}
