<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 3B Step 7 — append-only audit row for any change to
 * ProjectBudget.budgeted_amount.
 *
 * Application code never updates or deletes these rows. The model
 * intentionally has no setter facade beyond create(); rows live and
 * die with their parent budget (cascade) and otherwise stay frozen.
 */
class BudgetModification extends Model
{
    use HasFactory;

    /**
     * Audit rows have no updated_at — they're append-only.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'project_budget_id',
        'modified_by_user_id',
        'old_amount',
        'new_amount',
        'comment',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class, 'project_budget_id');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by_user_id');
    }

    /**
     * Convenience: was this a budget increase or decrease?
     * Returns 'increase' / 'decrease' / 'no_change' (never written
     * normally; defensive for hand-modified rows).
     */
    public function direction(): string
    {
        $delta = (float) $this->new_amount - (float) $this->old_amount;

        if ($delta > 0) {
            return 'increase';
        }
        if ($delta < 0) {
            return 'decrease';
        }

        return 'no_change';
    }
}
