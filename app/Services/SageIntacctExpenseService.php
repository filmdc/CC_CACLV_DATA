<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\IntacctFundExpense;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates syncing posted GL expense totals from Sage Intacct into
 * the local `intacct_fund_expenses` table, keyed by contract.
 *
 * Sync is idempotent per (contract, date window): existing rows in the
 * window are deleted and the current Intacct response is inserted in a
 * single transaction, so reversals and late-posted lines reconcile
 * correctly on rerun.
 */
class SageIntacctExpenseService
{
    public function __construct(
        private SageIntacctApiService $apiService
    ) {}

    /**
     * Sync posted GL expense lines for a single contract within a date window.
     * Defaults to the current fiscal year when dates are not provided.
     *
     * @return array{
     *     success: bool,
     *     skipped?: bool,
     *     reason?: string,
     *     dry_run?: bool,
     *     contract_id?: int,
     *     records_found?: int,
     *     records_synced?: int,
     *     total_amount?: float,
     *     from?: string,
     *     to?: string,
     *     error?: string
     * }
     */
    public function syncContract(
        Contract $contract,
        ?Carbon $from = null,
        ?Carbon $to = null,
        bool $dryRun = false
    ): array {
        if (empty(trim((string) $contract->grant_code))) {
            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'Contract has no grant_code (fund code) configured',
                'contract_id' => $contract->id,
            ];
        }

        // Safeguard: when a fund code is shared across multiple contracts
        // (e.g. F8000 undesignated bucket) and this contract has no
        // service_code set, we cannot uniquely attribute posted GL activity —
        // syncing would double-count the shared activity onto every sibling.
        // Skip with a clear reason so the user knows to set service_code.
        if (empty(trim((string) ($contract->service_code ?? '')))) {
            $siblings = \App\Models\Contract::where('grant_code', $contract->grant_code)
                ->where('id', '!=', $contract->id)
                ->whereNull('service_code')
                ->count();

            if ($siblings > 0) {
                return [
                    'success' => false,
                    'skipped' => true,
                    'reason' => sprintf(
                        'Fund code %s is shared with %d other contract(s) without service_code. Set a unique service_code on this contract to disambiguate which GL activity belongs to it.',
                        $contract->grant_code,
                        $siblings
                    ),
                    'contract_id' => $contract->id,
                ];
            }
        }

        if ($from === null || $to === null) {
            [$from, $to] = FiscalYearService::currentWindow();
        }

        $fiscalYear = FiscalYearService::yearFor($to);

        $glFrom = SiteSetting::get('intacct_expense_gl_account_from', '60000');
        $glTo = SiteSetting::get('intacct_expense_gl_account_to', '89999');

        $queryResult = $this->apiService->queryPostedGLEntriesByDimensions(
            projectId: trim($contract->grant_code),
            departmentId: $contract->service_code ? trim($contract->service_code) : null,
            from: $from,
            to: $to,
            glAccountFrom: $glFrom ?: null,
            glAccountTo: $glTo ?: null,
        );

        if (! $queryResult['success']) {
            return [
                'success' => false,
                'contract_id' => $contract->id,
                'error' => $queryResult['error'] ?? 'Unknown GLDETAIL query error',
            ];
        }

        $records = $queryResult['records'] ?? [];
        $totalAmount = array_sum(array_column($records, 'amount'));

        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'contract_id' => $contract->id,
                'records_found' => count($records),
                'total_amount' => round($totalAmount, 2),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ];
        }

        $inserted = 0;

        DB::transaction(function () use ($contract, $records, $from, $to, $fiscalYear, &$inserted) {
            IntacctFundExpense::where('contract_id', $contract->id)
                ->whereBetween('posting_date', [$from->toDateString(), $to->toDateString()])
                ->delete();

            foreach ($records as $rec) {
                if (empty($rec['record_no'])) {
                    continue;
                }

                IntacctFundExpense::create([
                    'contract_id' => $contract->id,
                    'intacct_record_no' => $rec['record_no'],
                    'intacct_project_id' => $rec['project_id'] ?: trim($contract->grant_code),
                    'intacct_department_id' => $rec['department_id'] ?: null,
                    'posting_date' => self::parseIntacctDate($rec['posting_date']),
                    'amount' => $rec['amount'],
                    'gl_account' => $rec['account_no'] ?: '',
                    'gl_account_title' => $rec['account_title'] ?: null,
                    'document_no' => $rec['document_no'] ?: null,
                    'description' => $rec['description'] ?: null,
                    'fiscal_year' => $fiscalYear,
                    'raw_data' => $rec['raw'] ?? null,
                    'last_synced_at' => now(),
                ]);

                $inserted++;
            }

            $contract->update(['last_synced_at' => now()]);
        });

        // Also pull the Intacct budget for this fund in the same window so
        // utilization denominator reflects the real budget (not stale
        // contract.amount). Non-fatal: budget sync failures are logged but
        // don't block the expense sync.
        $budgetResult = $this->syncBudgetForContract($contract, $from, $to);

        Log::info('SageIntacctExpenseService: synced contract', [
            'contract_id' => $contract->id,
            'grant_code' => $contract->grant_code,
            'service_code' => $contract->service_code,
            'records_synced' => $inserted,
            'total_amount' => round($totalAmount, 2),
            'budget_synced' => $budgetResult['success'] ?? false,
            'budget_amount' => $budgetResult['budget_amount'] ?? null,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        return [
            'success' => true,
            'contract_id' => $contract->id,
            'records_synced' => $inserted,
            'total_amount' => round($totalAmount, 2),
            'budget_synced' => $budgetResult['success'] ?? false,
            'budget_amount' => $budgetResult['budget_amount'] ?? 0.0,
            'budget_lines' => $budgetResult['lines_count'] ?? 0,
            'budget_source' => $budgetResult['budget_id'] ?? null,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /**
     * Pull the Intacct expense budget for a contract's fund and store it on
     * the contract. Called from syncContract() so a single click refreshes
     * both actuals and budget.
     *
     * Priority for selecting the budget repository:
     *   1. contract.preferred_intacct_budget_id (explicit per-contract override)
     *   2. Iterate the site-wide ordered list (intacct_budget_ids JSON) and
     *      take the FIRST repository that returns a non-zero expense budget
     *      for this fund.
     *   3. Auto-detect the DEFAULT_BUDGET flag on GLBUDGETHEADER (fallback
     *      when no list is configured at all).
     *
     * @return array{success: bool, budget_amount?: float, lines_count?: int, budget_id?: ?string, tried?: array, error?: string}
     */
    public function syncBudgetForContract(Contract $contract, Carbon $from, Carbon $to): array
    {
        if (empty(trim((string) $contract->grant_code))) {
            return ['success' => false, 'error' => 'No grant_code'];
        }

        $glFrom = SiteSetting::get('intacct_expense_gl_account_from', '60000');
        $glTo = SiteSetting::get('intacct_expense_gl_account_to', '99999');

        $tried = [];

        // Build the ordered list of budget IDs to try for this contract.
        $candidateIds = $this->resolveBudgetCandidateList($contract);

        $lastError = null;

        foreach ($candidateIds as $candidate) {
            $result = $this->apiService->queryFundBudgetByDimensions(
                projectId: trim($contract->grant_code),
                departmentId: $contract->service_code ? trim($contract->service_code) : null,
                from: $from,
                to: $to,
                budgetId: $candidate ?: null, // '' means auto-detect
                glAccountFrom: $glFrom ?: null,
                glAccountTo: $glTo ?: null,
            );

            $budgetTried = $result['budget_id'] ?? ($candidate ?: '(auto)');
            $totalBudget = (float) ($result['total_expense'] ?? 0);
            $tried[] = [
                'budget_id' => $budgetTried,
                'success' => $result['success'] ?? false,
                'total' => $totalBudget,
                'error' => $result['success'] ? null : ($result['error'] ?? null),
            ];

            if (! $result['success']) {
                $lastError = $result['error'] ?? 'unknown';
                continue; // try next repository
            }

            // If a per-contract preference is set we honor it even if total=0,
            // otherwise we continue the fallback chain to find one with data.
            $isExplicitPreference = ! empty($contract->preferred_intacct_budget_id);
            if ($totalBudget <= 0 && ! $isExplicitPreference) {
                continue;
            }

            // Found a usable budget — persist and return.
            $contract->update([
                'intacct_budget_amount' => $totalBudget,
                'intacct_budget_synced_at' => now(),
                'intacct_budget_source' => $result['budget_id'] ?? null,
                'intacct_budget_lines_count' => count($result['records'] ?? []),
            ]);

            return [
                'success' => true,
                'budget_amount' => $totalBudget,
                'lines_count' => count($result['records'] ?? []),
                'budget_id' => $result['budget_id'] ?? null,
                'tried' => $tried,
            ];
        }

        // No repository returned a non-zero budget. Clear any stale budget
        // data on this contract so the dashboard falls back to contract.amount.
        $contract->update([
            'intacct_budget_amount' => null,
            'intacct_budget_synced_at' => now(),
            'intacct_budget_source' => null,
            'intacct_budget_lines_count' => 0,
        ]);

        Log::info('SageIntacctExpenseService: no budget found in any repository', [
            'contract_id' => $contract->id,
            'grant_code' => $contract->grant_code,
            'tried' => $tried,
        ]);

        return [
            'success' => false,
            'budget_amount' => 0.0,
            'lines_count' => 0,
            'budget_id' => null,
            'tried' => $tried,
            'error' => $lastError ?? 'No budget found in any configured repository',
        ];
    }

    /**
     * Resolve the ordered list of budget-IDs to try for a contract. Handles
     * per-contract overrides, the site-wide list, and back-compat with the
     * legacy single-value setting.
     *
     * @return list<string>  Ordered candidate IDs; empty string entry means "auto-detect default".
     */
    private function resolveBudgetCandidateList(Contract $contract): array
    {
        // 1. Explicit per-contract override wins.
        if (! empty($contract->preferred_intacct_budget_id)) {
            return [trim($contract->preferred_intacct_budget_id)];
        }

        // 2. Site-wide ordered list (new canonical setting).
        $json = SiteSetting::get('intacct_budget_ids', null);
        if ($json) {
            $list = json_decode($json, true);
            if (is_array($list)) {
                $list = array_values(array_filter(array_map('trim', $list), fn ($v) => $v !== ''));
                if (! empty($list)) {
                    return $list;
                }
            }
        }

        // 3. Legacy single-value setting.
        $legacy = trim((string) SiteSetting::get('intacct_default_budget_id', ''));
        if ($legacy !== '') {
            return [$legacy];
        }

        // 4. No configuration → auto-detect the DEFAULT_BUDGET header.
        return [''];
    }

    /**
     * Parse an Intacct readByQuery date string (MM/DD/YYYY) into a Carbon date.
     */
    private static function parseIntacctDate(string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('m/d/Y', $date)->startOfDay();
        } catch (\Exception $e) {
            try {
                return Carbon::parse($date)->startOfDay();
            } catch (\Exception $e2) {
                return null;
            }
        }
    }
}
