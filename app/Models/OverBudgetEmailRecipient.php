<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Phase 3B Step 7 — admin-managed list of users who receive emails
 * when a ProjectBudget.budgeted_amount changes (increase or decrease).
 *
 * Eligibility comes from membership in this list. Per-user delivery
 * is then gated on the over_budget_email category opt-out stored on
 * users.notification_preferences (Phase 3B Step 2).
 */
class OverBudgetEmailRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Return the active User instances configured to receive
     * over-budget modification emails. Used by
     * BudgetModifiedNotification::dispatch.
     *
     * Per-user opt-out (over_budget_email category) is handled inside
     * the notification's via() — this method just returns the
     * eligibility list.
     *
     * @return Collection<int, User>
     */
    public static function activeRecipientUsers(): Collection
    {
        return self::query()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn (?User $u) => $u !== null && $u->active)
            ->values();
    }
}
