<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractUtilizationLog;
use App\Models\ContractUtilizationNotification;
use App\Models\SiteSetting;
use App\Notifications\ContractUtilizationThresholdNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ContractUtilizationService
{
    /**
     * Default thresholds (percentage remaining)
     */
    protected static array $defaultThresholds = [75, 50, 25, 10, 5, 0];

    /**
     * Get configured thresholds
     */
    public static function getThresholds(): array
    {
        $thresholds = SiteSetting::get('utilization_thresholds', null);

        if ($thresholds) {
            return json_decode($thresholds, true) ?: self::$defaultThresholds;
        }

        return self::$defaultThresholds;
    }

    /**
     * Master switch for utilization notifications.
     *
     * When this is off the overspend evaluation still runs and still records
     * which thresholds were crossed -- only the email is withheld.
     */
    public static function notificationsEnabled(): bool
    {
        return SiteSetting::get('utilization_notification_enabled', '1') === '1';
    }

    /**
     * Global test mode: every utilization email is redirected to a single
     * address instead of the real recipients.
     */
    public static function testModeEnabled(): bool
    {
        return SiteSetting::get('utilization_test_mode', '0') === '1';
    }

    /**
     * Address that receives redirected mail while test mode is on.
     */
    public static function testEmail(): string
    {
        return trim((string) SiteSetting::get('utilization_test_email', ''));
    }

    /**
     * Check if a contract can accept a new association (budget or expense)
     *
     * @return array{allowed: bool, reason?: string, remaining?: float, requested?: float, contract: Contract}
     */
    public static function canAssociateWithContract(Contract $contract, float $amount = 0): array
    {
        // Archived contracts cannot accept new associations
        if ($contract->archived) {
            return [
                'allowed' => false,
                'reason' => 'Contract is archived and cannot accept new budget or expense associations.',
                'contract' => $contract,
            ];
        }

        // Check if contract has a budget set
        if ($contract->amount <= 0) {
            // Allow if no budget is set (unrestricted)
            return [
                'allowed' => true,
                'reason' => 'Contract has no budget limit set.',
                'contract' => $contract,
            ];
        }

        // Calculate what the new total would be
        $newTotal = $contract->total_spent + $amount;
        $remaining = $contract->getRemainingFunds();

        // Check if this would exceed the contract amount
        if ($amount > 0 && $newTotal > $contract->amount) {
            return [
                'allowed' => false,
                'reason' => sprintf(
                    'This expense of $%s would exceed the contract budget. Only $%s remaining.',
                    number_format($amount, 2),
                    number_format($remaining, 2)
                ),
                'remaining' => $remaining,
                'requested' => $amount,
                'contract' => $contract,
            ];
        }

        // Check if contract is already fully utilized
        if ($contract->isFullyUtilized()) {
            return [
                'allowed' => false,
                'reason' => sprintf(
                    'Contract is fully utilized. Total spent ($%s) meets or exceeds budget ($%s).',
                    number_format($contract->total_spent, 2),
                    number_format($contract->amount, 2)
                ),
                'remaining' => 0,
                'contract' => $contract,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'contract' => $contract,
        ];
    }

    /**
     * Check thresholds and send notifications if needed
     */
    public static function checkAndNotifyThresholds(Contract $contract, string $notificationType = 'realtime'): array
    {
        // Skip if contract has no budget
        if ($contract->amount <= 0) {
            return ['sent' => 0, 'thresholds' => [], 'suppressed' => []];
        }

        $thresholds = self::getThresholds();
        $remainingPercent = $contract->remaining_percentage;

        // Get all thresholds that have been crossed but not yet notified
        $crossedThresholds = ContractUtilizationNotification::getAllCrossedThresholds(
            $contract->id,
            $remainingPercent,
            $thresholds
        );

        if (empty($crossedThresholds)) {
            return ['sent' => 0, 'thresholds' => [], 'suppressed' => []];
        }

        // Mirrors sendThresholdNotification(): a test redirect still delivers
        // while the master switch is off, so it must not be counted as suppressed.
        $delivering = self::notificationsEnabled() || (self::testModeEnabled() && self::testEmail() !== '');
        $notificationsSent = 0;
        $notifiedThresholds = [];
        $suppressedThresholds = [];

        foreach ($crossedThresholds as $threshold) {
            $sent = self::sendThresholdNotification($contract, $threshold, $notificationType);

            // With the master switch off the crossing is still recorded, so
            // report it as suppressed rather than as sent.
            if (! $delivering) {
                $suppressedThresholds[] = $threshold;

                continue;
            }

            if ($sent > 0) {
                $notificationsSent += $sent;
                $notifiedThresholds[] = $threshold;
            }
        }

        return [
            'sent' => $notificationsSent,
            'thresholds' => $notifiedThresholds,
            'suppressed' => $suppressedThresholds,
        ];
    }

    /**
     * Send threshold notification to all recipients
     */
    public static function sendThresholdNotification(Contract $contract, int $threshold, string $notificationType = 'scheduled'): int
    {
        $recentExpenses = $contract->getRecentExpenses(5);
        $testMode = self::testModeEnabled();
        $testEmail = self::testEmail();

        // A test redirect deliberately outranks the master switch: "off for
        // everyone, but still let me see one" is the whole point of pairing
        // the two settings.
        $redirectToTester = $testMode && $testEmail !== '';

        // Master switch off and nowhere to redirect: record the crossing so it
        // is not replayed in a burst the moment notifications are switched back
        // on, and send nothing. The overspend evaluation itself still ran.
        if (! self::notificationsEnabled() && ! $redirectToTester) {
            $contract->markThresholdNotified(
                $threshold,
                $recentExpenses->pluck('id')->toArray(),
                'suppressed'
            );

            Log::info("Utilization notifications are disabled; recorded contract #{$contract->id} crossing {$threshold}% without sending");

            return 0;
        }

        $recipients = $contract->getUtilizationNotificationRecipients();

        if ($recipients->isEmpty()) {
            Log::info("Contract #{$contract->id} has no notification recipients for threshold {$threshold}%");

            // Under a test redirect, leave the threshold outstanding.
            if ($redirectToTester) {
                return 0;
            }

            // Still record the threshold as notified to prevent repeated attempts
            $contract->markThresholdNotified(
                $threshold,
                $recentExpenses->pluck('id')->toArray(),
                $notificationType
            );

            return 0;
        }

        $sent = 0;

        foreach ($recipients as $recipient) {
            try {
                $notification = new ContractUtilizationThresholdNotification(
                    $contract,
                    $threshold,
                    $recentExpenses,
                    $testMode ? $recipient : null
                );

                if ($testMode && $testEmail) {
                    // Route to the test address. An on-the-fly User model is
                    // deliberately NOT used here: this notification is queued,
                    // and an unsaved model cannot survive job serialization.
                    Notification::route('mail', $testEmail)->notify($notification);
                } else {
                    $recipient->notify($notification);
                }

                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to send utilization notification', [
                    'contract_id' => $contract->id,
                    'threshold' => $threshold,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // A test redirect is a rehearsal, not the real alert: recording here
        // would mark the threshold as already handled and the actual recipients
        // would never be told once alerts are switched back on.
        if ($redirectToTester) {
            Log::info("Test send: {$sent} utilization notification(s) for contract #{$contract->id} at {$threshold}% routed to {$testEmail}; threshold left outstanding");

            return $sent;
        }

        // Record that this threshold was notified
        $contract->markThresholdNotified(
            $threshold,
            $recentExpenses->pluck('id')->toArray(),
            $notificationType
        );

        Log::info("Sent {$sent} utilization notifications for contract #{$contract->id} at {$threshold}% threshold");

        return $sent;
    }

    /**
     * Reset thresholds if utilization has dropped
     */
    public static function resetThresholdsIfNeeded(Contract $contract): int
    {
        return $contract->resetThresholdsIfNeeded();
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
        bool $wasOverride = false,
        ?string $overrideReason = null
    ): ContractUtilizationLog {
        // Refresh contract to get current values
        $contract->refresh();

        return ContractUtilizationLog::logChange(
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
    }

    /**
     * Get utilization summary for a contract
     */
    public static function getUtilizationSummary(Contract $contract): array
    {
        return [
            'contract_id' => $contract->id,
            'title' => $contract->title,
            'amount' => $contract->amount,
            'total_spent' => $contract->total_spent,
            'remaining' => $contract->remaining_budget,
            'utilization_percentage' => $contract->utilization_percentage,
            'remaining_percentage' => $contract->remaining_percentage,
            'is_fully_utilized' => $contract->isFullyUtilized(),
            'status' => $contract->utilization_status,
            'can_accept_expenses' => $contract->canAcceptNewExpenses(),
        ];
    }

    /**
     * Get system-wide utilization metrics
     */
    public static function getSystemMetrics(?string $from = null, ?string $to = null): array
    {
        // Budget and spend totals are computed at the FUND level (from
        // intacct_fund_budgets / intacct_fund_expenses) so a shared fund
        // like F8000 — linked by 68 contracts — contributes once, not 68×.
        // Contracts without any Intacct data still contribute their local
        // contract.amount as budget (fallback for manual-only rows).
        //
        // Expense sum is scoped to the posting-date window when provided so
        // the top-of-dashboard metric cards track the period picker.
        // Budget stays unscoped (one current-FY budget snapshot per fund);
        // if the user's window differs from the sync window the total may
        // overstate relative to a period's expected spend.
        $fundBudgetTotal = (float) \App\Models\IntacctFundBudget::sum('amount');
        $fundSpentQuery = \App\Models\IntacctFundExpense::query();
        if ($from && $to) {
            $fundSpentQuery->whereBetween('posting_date', [$from, $to]);
        }
        $fundSpentTotal = (float) $fundSpentQuery->sum('amount');

        // Sum contract.amount ONLY for contracts whose fund has no Intacct
        // budget record — otherwise we'd double-count the fund and the
        // award separately.
        $localOnlyBudget = (float) Contract::active()
            ->where('amount', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('intacct_fund_budgets')
                    ->whereColumn('intacct_fund_budgets.intacct_project_id', 'contracts.grant_code');
            })
            ->sum('amount');

        $contracts = Contract::active()
            ->where(function ($q) {
                $q->where('amount', '>', 0)
                    ->orWhere('intacct_budget_amount', '>', 0);
            })
            ->get();

        $metrics = [
            'total_contracts' => $contracts->count(),
            'total_awarded' => $fundBudgetTotal + $localOnlyBudget,
            'total_spent' => $fundSpentTotal,
            'total_remaining' => 0,
            'critical_count' => 0,
            'warning_count' => 0,
            'depleted_count' => 0,
            'healthy_count' => 0,
        ];

        $metrics['total_remaining'] = $metrics['total_awarded'] - $metrics['total_spent'];

        foreach ($contracts as $contract) {

            $remaining = $contract->remaining_percentage;

            if ($remaining <= 0) {
                $metrics['depleted_count']++;
            } elseif ($remaining <= 10) {
                $metrics['critical_count']++;
            } elseif ($remaining <= 25) {
                $metrics['warning_count']++;
            } else {
                $metrics['healthy_count']++;
            }
        }

        $metrics['at_risk_count'] = $metrics['critical_count'] + $metrics['warning_count'] + $metrics['depleted_count'];
        $metrics['overall_utilization'] = $metrics['total_awarded'] > 0
            ? round(($metrics['total_spent'] / $metrics['total_awarded']) * 100, 1)
            : 0;

        return $metrics;
    }
}
