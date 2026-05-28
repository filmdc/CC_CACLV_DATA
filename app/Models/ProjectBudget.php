<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Project Budget — standalone housing project tracker.
 *
 * Tracks funding sources, allocated amounts, and per-category spent totals
 * for a single housing project. Scoped to the housing project manager's
 * own data entry, scoped to her view of project-level finances.
 *
 * This module is intentionally NOT integrated with Sage Intacct, MIP,
 * DonorPerfect, or any other fiscal system of record. Fiscal teams use
 * their own systems; this exists so the housing project manager can see
 * project-level budget vs. expense without querying Fiscal.
 *
 * Do not add fiscal-system writes, reads, or syncs here. If a future
 * requirement seems to call for that, escalate before building. The
 * tests/Feature/Regression/Budget/BudgetIsStandaloneTest.php tripwire
 * guards this contract via grep-based import scanning.
 */
class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'housing_job_id',
        'contract_id',
        'fund_code_id',
        'milestone_id',
        'category',
        'budgeted_amount',
        'spent_amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
    ];

    protected $appends = ['remaining_amount', 'percentage_spent', 'is_over_budget'];

    /**
     * P-2.5 invariant: when fund_code_id is set, the same fund code must
     * already be in the parent project's fund_code_housing_job pivot.
     * Catches mass-assignment / tinker / console paths that bypass the
     * Livewire form.
     */
    protected static function booted(): void
    {
        // When a budget row is created, sync spent_amount from any
        // existing expenses that share its (housing_job_id, category)
        // tuple. Without this, expenses orphaned by a prior delete of
        // the same-named category stay invisible to the new row's
        // spent total — the over-budget banner would not fire even
        // though the expenses were already there.
        static::created(function (ProjectBudget $budget) {
            $budget->recalculateSpent();
        });

        static::saving(function (ProjectBudget $budget) {
            if (! $budget->isDirty('fund_code_id')) {
                return;
            }
            if ($budget->fund_code_id === null) {
                return;
            }

            $attached = DB::table('fund_code_housing_job')
                ->where('housing_job_id', $budget->housing_job_id)
                ->where('fund_code_id', $budget->fund_code_id)
                ->exists();

            if (! $attached) {
                throw new \InvalidArgumentException(
                    "Cannot set fund_code_id={$budget->fund_code_id} on project_budgets row for housing job {$budget->housing_job_id}: that fund code is not assigned to the project. Attach it via HousingJob::fundCodes()->syncWithoutDetaching([id]) before referencing it on a budget row."
                );
            }
        });
    }

    // Relationships

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function fundCode(): BelongsTo
    {
        return $this->belongsTo(FundCode::class);
    }

    /**
     * Milestone revamp: the milestone this budget item is assigned to,
     * if any. Null = unassigned, shown in the requirements tab's
     * "Unassigned" section.
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'category', 'category')
            ->where('housing_job_id', $this->housing_job_id);
    }

    /**
     * Phase 3B Step 7: append-only audit ledger of budgeted_amount
     * changes on this budget row. Ordered newest-first so the
     * ledger modal shows recent activity at the top.
     */
    public function modifications(): HasMany
    {
        return $this->hasMany(BudgetModification::class, 'project_budget_id')
            ->orderByDesc('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Computed Attributes

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->budgeted_amount - $this->spent_amount);
    }

    public function getPercentageSpentAttribute(): int
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }

        return (int) round(($this->spent_amount / $this->budgeted_amount) * 100);
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->spent_amount > $this->budgeted_amount;
    }

    // Business Logic Methods

    public function recalculateSpent(): void
    {
        $total = ProjectExpense::where('housing_job_id', $this->housing_job_id)
            ->where('category', $this->category)
            ->sum('amount');

        $this->spent_amount = $total;
        $this->save();
    }

    /**
     * @deprecated Phase 3B Step 6: this method does in-memory delta
     * math which can drift from the actual sum of project_expenses
     * rows. The observer-driven recalculateSpent() pattern (fired by
     * ProjectExpense::booted) is the canonical path. No callers exist
     * outside this file; pending removal in a follow-up cleanup PR.
     */
    public function addExpense(float $amount): void
    {
        $this->spent_amount += $amount;
        $this->save();
    }
}
