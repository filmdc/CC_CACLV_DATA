<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalNotification extends Model
{
    protected $fillable = [
        'pending_approval_id',
        'notification_type',
        'recipient_id',
        'sent_at',
        'opened_at',
        'actioned_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'actioned_at' => 'datetime',
    ];

    public function pendingApproval(): BelongsTo
    {
        return $this->belongsTo(PendingApproval::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Mark the notification as opened.
     */
    public function markAsOpened(): void
    {
        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Mark the notification as actioned.
     */
    public function markAsActioned(): void
    {
        if (!$this->actioned_at) {
            $this->update(['actioned_at' => now()]);
        }
    }

    /**
     * Check if the notification has been opened.
     */
    public function isOpened(): bool
    {
        return $this->opened_at !== null;
    }

    /**
     * Check if the notification has been actioned.
     */
    public function isActioned(): bool
    {
        return $this->actioned_at !== null;
    }

    /**
     * Check if the notification is pending (sent but not opened).
     */
    public function isPending(): bool
    {
        return $this->sent_at !== null && $this->opened_at === null;
    }
}
