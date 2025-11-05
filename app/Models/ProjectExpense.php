<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    protected $fillable = [
        'housing_job_id',
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

    protected static function booted()
    {
        // Update budget spent_amount when expense is created/updated/deleted
        static::created(function ($expense) {
            $expense->updateBudget();
        });

        static::updated(function ($expense) {
            $expense->updateBudget();
        });

        static::deleted(function ($expense) {
            $expense->updateBudget();
        });
    }

    // Relationships

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
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
        return ($this->amount < 0 ? '-$' : '$') . number_format(abs($this->amount), 2);
    }

    // Business Logic Methods

    protected function updateBudget(): void
    {
        $budget = ProjectBudget::where('housing_job_id', $this->housing_job_id)
            ->where('category', $this->category)
            ->first();

        if ($budget) {
            $budget->recalculateSpent();
        }
    }
}
