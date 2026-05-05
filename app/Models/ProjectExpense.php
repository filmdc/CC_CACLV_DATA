<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Project Expense — standalone housing project tracker.
 *
 * Records expense entries against a project's budget categories and
 * updates ProjectBudget.spent_amount via a booted observer.
 *
 * Like ProjectBudget, this model is intentionally NOT integrated with
 * Sage Intacct, MIP, DonorPerfect, or any other fiscal system of record.
 * The housing project manager enters expenses here for project-level
 * visibility; Fiscal's own systems remain authoritative for accounting.
 *
 * Do not add fiscal-system writes, reads, or syncs here. If a future
 * requirement seems to call for that, escalate before building. See
 * tests/Feature/Regression/Budget/BudgetIsStandaloneTest.php.
 */
class ProjectExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'housing_job_id',
        'contract_id',
        'category',
        'amount',
        'expense_date',
        'description',
        'document_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected $appends = ['is_credit', 'formatted_amount'];

    /**
     * Phase 3B (P-23 Step 6) math-hardening: budget rows are keyed on
     * the (housing_job_id, category) tuple. When an expense's tuple
     * changes (most commonly a category edit, occasionally a project
     * move), the OLD budget tuple is now stale and needs a recompute
     * too — not just the NEW one. Pre-3B the observer only touched
     * the current-value tuple, leaving the abandoned tuple's
     * spent_amount permanently inflated.
     *
     * The fix: capture original values via getOriginal() at update time
     * and recompute both sides if the grouping key changed. created
     * and deleted only have one tuple to recompute.
     */
    protected static function booted()
    {
        static::created(function (ProjectExpense $expense) {
            $expense->recomputeBudgetForCurrentTuple();
        });

        static::updated(function (ProjectExpense $expense) {
            $originalCategory = $expense->getOriginal('category');
            $originalHousingJobId = $expense->getOriginal('housing_job_id');

            $tupleChanged = $originalCategory !== $expense->category
                || $originalHousingJobId !== $expense->housing_job_id;

            if ($tupleChanged) {
                $expense->recomputeBudgetForTuple($originalHousingJobId, $originalCategory);
            }

            $expense->recomputeBudgetForCurrentTuple();
        });

        static::deleted(function (ProjectExpense $expense) {
            // Eloquent retains attribute values on the in-memory model
            // after delete, so the (housing_job_id, category) tuple is
            // still readable here and recalculateSpent will see the row
            // is gone from the DB.
            $expense->recomputeBudgetForCurrentTuple();
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

    public function document(): BelongsTo
    {
        return $this->belongsTo(MilestoneDocument::class, 'document_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class, 'category', 'category')
            ->where('housing_job_id', $this->housing_job_id);
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

    public function getIsCreditAttribute(): bool
    {
        return $this->amount < 0;
    }

    public function getFormattedAmountAttribute(): string
    {
        return ($this->amount < 0 ? '-$' : '$').number_format(abs($this->amount), 2);
    }

    // Business Logic Methods

    /**
     * Phase 3B (P-23 Step 6): recompute spent_amount on the budget row
     * matching this expense's current (housing_job_id, category)
     * tuple. No-op when no matching budget exists — uncategorized
     * expenses don't track against a budget, by design.
     */
    protected function recomputeBudgetForCurrentTuple(): void
    {
        $this->recomputeBudgetForTuple($this->housing_job_id, $this->category);
    }

    /**
     * Phase 3B (P-23 Step 6): recompute spent_amount for an explicit
     * (housing_job_id, category) tuple. Used by the updated event to
     * refresh both old and new tuples when an expense's grouping key
     * changes.
     */
    protected function recomputeBudgetForTuple(?int $housingJobId, ?string $category): void
    {
        if ($housingJobId === null || $category === null) {
            return;
        }

        $budget = ProjectBudget::where('housing_job_id', $housingJobId)
            ->where('category', $category)
            ->first();

        if ($budget) {
            $budget->recalculateSpent();
        }
    }
}
