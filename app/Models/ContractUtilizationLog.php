<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ContractUtilizationLog extends Model
{
    protected $fillable = [
        'contract_id',
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_amount',
        'new_amount',
        'contract_utilization_before',
        'contract_utilization_after',
        'contract_remaining_before',
        'contract_remaining_after',
        'was_override',
        'override_reason',
        'ip_address',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'contract_utilization_before' => 'decimal:2',
        'contract_utilization_after' => 'decimal:2',
        'contract_remaining_before' => 'decimal:2',
        'contract_remaining_after' => 'decimal:2',
        'was_override' => 'boolean',
    ];

    /**
     * Action constants
     */
    public const ACTION_BUDGET_CREATED = 'budget_created';

    public const ACTION_BUDGET_UPDATED = 'budget_updated';

    public const ACTION_BUDGET_DELETED = 'budget_deleted';

    public const ACTION_EXPENSE_CREATED = 'expense_created';

    public const ACTION_EXPENSE_UPDATED = 'expense_updated';

    public const ACTION_EXPENSE_DELETED = 'expense_deleted';

    public const ACTION_ADMIN_OVERRIDE = 'admin_override';

    /**
     * Get the contract this log belongs to
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who made the change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic-like retrieval)
     */
    public function getRelatedModel(): ?Model
    {
        if (! $this->model_type || ! $this->model_id) {
            return null;
        }

        return $this->model_type::find($this->model_id);
    }

    /**
     * Log a utilization change
     */
    public static function logChange(
        Contract $contract,
        string $action,
        Model $model,
        ?float $oldAmount,
        ?float $newAmount,
        float $utilizationBefore,
        float $remainingBefore,
        float $utilizationAfter,
        float $remainingAfter,
        bool $wasOverride = false,
        ?string $overrideReason = null
    ): self {
        return self::create([
            'contract_id' => $contract->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'contract_utilization_before' => $utilizationBefore,
            'contract_utilization_after' => $utilizationAfter,
            'contract_remaining_before' => $remainingBefore,
            'contract_remaining_after' => $remainingAfter,
            'was_override' => $wasOverride,
            'override_reason' => $overrideReason,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Scope to get logs for a specific contract
     */
    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Scope to get override logs only
     */
    public function scopeOverridesOnly($query)
    {
        return $query->where('was_override', true);
    }

    /**
     * Scope to get logs by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get human-readable action label
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_BUDGET_CREATED => 'Budget Created',
            self::ACTION_BUDGET_UPDATED => 'Budget Updated',
            self::ACTION_BUDGET_DELETED => 'Budget Deleted',
            self::ACTION_EXPENSE_CREATED => 'Expense Created',
            self::ACTION_EXPENSE_UPDATED => 'Expense Updated',
            self::ACTION_EXPENSE_DELETED => 'Expense Deleted',
            self::ACTION_ADMIN_OVERRIDE => 'Admin Override',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get the change description
     */
    public function getChangeDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->old_amount !== null && $this->new_amount !== null) {
            $parts[] = sprintf(
                'Amount changed from $%s to $%s',
                number_format($this->old_amount, 2),
                number_format($this->new_amount, 2)
            );
        } elseif ($this->new_amount !== null) {
            $parts[] = sprintf('Amount: $%s', number_format($this->new_amount, 2));
        } elseif ($this->old_amount !== null) {
            $parts[] = sprintf('Previous amount: $%s', number_format($this->old_amount, 2));
        }

        $parts[] = sprintf(
            'Utilization: %.1f%% → %.1f%%',
            $this->contract_utilization_before,
            $this->contract_utilization_after
        );

        if ($this->was_override) {
            $parts[] = '[ADMIN OVERRIDE]';
        }

        return implode(' | ', $parts);
    }
}
