<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'housing_job_id',
        'contract_id',
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

    // Relationships

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'category', 'category')
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

    public function addExpense(float $amount): void
    {
        $this->spent_amount += $amount;
        $this->save();
    }
}
