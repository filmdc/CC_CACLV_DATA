<?php

namespace App\Observers;

use App\Exceptions\ContractFullyUtilizedException;
use App\Models\Contract;
use App\Models\ContractUtilizationLog;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Services\ContractUtilizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ContractUtilizationObserver
{
    /**
     * Handle the "creating" event for expenses (validation)
     */
    public function creating(Model $model): void
    {
        // Only validate expenses, not budgets
        if (! $model instanceof ProjectExpense) {
            return;
        }

        if (! $model->contract_id) {
            return;
        }

        $contract = Contract::find($model->contract_id);
        if (! $contract) {
            return;
        }

        // Check for admin override
        if ($this->hasOverride($model->contract_id)) {
            return;
        }

        // Validate the association
        $result = ContractUtilizationService::canAssociateWithContract(
            $contract,
            (float) $model->amount
        );

        if (! $result['allowed']) {
            throw new ContractFullyUtilizedException(
                $result['reason'],
                $contract,
                $result['remaining'] ?? 0,
                (float) $model->amount
            );
        }
    }

    /**
     * Handle the "created" event
     */
    public function created(Model $model): void
    {
        if (! $model->contract_id) {
            return;
        }

        $contract = Contract::find($model->contract_id);
        if (! $contract) {
            return;
        }

        // Check for override info
        $wasOverride = $this->hasOverride($model->contract_id);
        $overrideReason = $this->getOverrideReason($model->contract_id);
        $this->clearOverride($model->contract_id);

        // Get amount for logging
        $amount = $model instanceof ProjectExpense
            ? (float) $model->amount
            : (float) $model->budgeted_amount;

        // Determine action type
        $action = $model instanceof ProjectExpense
            ? ContractUtilizationLog::ACTION_EXPENSE_CREATED
            : ContractUtilizationLog::ACTION_BUDGET_CREATED;

        // Log the change (contract values are stale, so calculate before values)
        $utilizationBefore = $contract->utilization_percentage;
        $remainingBefore = $contract->remaining_budget;

        // Refresh contract to get updated totals
        $contract->refresh();

        ContractUtilizationLog::logChange(
            $contract,
            $action,
            $model,
            null,
            $amount,
            $utilizationBefore,
            $remainingBefore,
            $contract->utilization_percentage,
            $contract->remaining_budget,
            $wasOverride,
            $overrideReason
        );

        // Check and send threshold notifications for expenses
        if ($model instanceof ProjectExpense) {
            ContractUtilizationService::checkAndNotifyThresholds($contract, 'realtime');
        }
    }

    /**
     * Handle the "updating" event for expenses (validation when contract changes)
     */
    public function updating(Model $model): void
    {
        // Only validate expenses
        if (! $model instanceof ProjectExpense) {
            return;
        }

        // Check if contract_id is being changed to a new contract
        if (! $model->isDirty('contract_id')) {
            return;
        }

        $newContractId = $model->contract_id;
        if (! $newContractId) {
            return;
        }

        // Check for admin override
        if ($this->hasOverride($newContractId)) {
            return;
        }

        $contract = Contract::find($newContractId);
        if (! $contract) {
            return;
        }

        // Validate the new association
        $result = ContractUtilizationService::canAssociateWithContract(
            $contract,
            (float) $model->amount
        );

        if (! $result['allowed']) {
            throw new ContractFullyUtilizedException(
                $result['reason'],
                $contract,
                $result['remaining'] ?? 0,
                (float) $model->amount
            );
        }
    }

    /**
     * Handle the "updated" event
     */
    public function updated(Model $model): void
    {
        // Handle old contract if contract_id changed
        if ($model->isDirty('contract_id') && $model->getOriginal('contract_id')) {
            $oldContract = Contract::find($model->getOriginal('contract_id'));
            if ($oldContract) {
                $oldContract->refresh();
                ContractUtilizationService::resetThresholdsIfNeeded($oldContract);
            }
        }

        // Handle current contract
        if (! $model->contract_id) {
            return;
        }

        $contract = Contract::find($model->contract_id);
        if (! $contract) {
            return;
        }

        // Check for override info
        $wasOverride = $this->hasOverride($model->contract_id);
        $overrideReason = $this->getOverrideReason($model->contract_id);
        $this->clearOverride($model->contract_id);

        // Get amounts for logging
        $oldAmount = $model instanceof ProjectExpense
            ? (float) $model->getOriginal('amount')
            : (float) $model->getOriginal('budgeted_amount');

        $newAmount = $model instanceof ProjectExpense
            ? (float) $model->amount
            : (float) $model->budgeted_amount;

        // Determine action type
        $action = $model instanceof ProjectExpense
            ? ContractUtilizationLog::ACTION_EXPENSE_UPDATED
            : ContractUtilizationLog::ACTION_BUDGET_UPDATED;

        // Calculate before values (these will be approximate)
        $utilizationBefore = $contract->utilization_percentage;
        $remainingBefore = $contract->remaining_budget;

        // Refresh contract to get updated totals
        $contract->refresh();

        ContractUtilizationLog::logChange(
            $contract,
            $action,
            $model,
            $oldAmount,
            $newAmount,
            $utilizationBefore,
            $remainingBefore,
            $contract->utilization_percentage,
            $contract->remaining_budget,
            $wasOverride,
            $overrideReason
        );

        // Check thresholds for expenses
        if ($model instanceof ProjectExpense) {
            ContractUtilizationService::checkAndNotifyThresholds($contract, 'realtime');
        }
    }

    /**
     * Handle the "deleted" event
     */
    public function deleted(Model $model): void
    {
        if (! $model->contract_id) {
            return;
        }

        $contract = Contract::find($model->contract_id);
        if (! $contract) {
            return;
        }

        // Get amount for logging
        $amount = $model instanceof ProjectExpense
            ? (float) $model->amount
            : (float) $model->budgeted_amount;

        // Determine action type
        $action = $model instanceof ProjectExpense
            ? ContractUtilizationLog::ACTION_EXPENSE_DELETED
            : ContractUtilizationLog::ACTION_BUDGET_DELETED;

        // Calculate before values
        $utilizationBefore = $contract->utilization_percentage;
        $remainingBefore = $contract->remaining_budget;

        // Refresh contract to get updated totals
        $contract->refresh();

        ContractUtilizationLog::logChange(
            $contract,
            $action,
            $model,
            $amount,
            null,
            $utilizationBefore,
            $remainingBefore,
            $contract->utilization_percentage,
            $contract->remaining_budget,
            false,
            null
        );

        // Reset thresholds if utilization dropped
        ContractUtilizationService::resetThresholdsIfNeeded($contract);
    }

    /**
     * Check if an admin override is in effect
     */
    protected function hasOverride(int $contractId): bool
    {
        return session()->has("contract_override_{$contractId}");
    }

    /**
     * Get the override reason
     */
    protected function getOverrideReason(int $contractId): ?string
    {
        return session()->get("contract_override_reason_{$contractId}");
    }

    /**
     * Clear the override session data
     */
    protected function clearOverride(int $contractId): void
    {
        session()->forget("contract_override_{$contractId}");
        session()->forget("contract_override_reason_{$contractId}");
    }
}
