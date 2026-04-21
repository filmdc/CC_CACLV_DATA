<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\IntacctFundBudget;
use App\Models\IntacctFundExpense;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync posted GL expenses and expense budgets from Sage Intacct into the
 * local fund utilization tables.
 *
 * Storage is fund-keyed: one row per (intacct_project_id, intacct_department_id,
 * intacct_record_no) in intacct_fund_expenses and one row per
 * (intacct_project_id, intacct_department_id) in intacct_fund_budgets. This
 * lets a fund like F8000 — shared across many contracts — have a single
 * authoritative record, and lets Sage-only funds (no matching local
 * contract) still be tracked.
 *
 * Contracts are matched to funds by (grant_code, service_code) during the
 * backlink step; a missing service_code matches the '' (empty) tuple,
 * which represents the project's unallocated baseline.
 */
class SageIntacctExpenseService
{
    public function __construct(
        private SageIntacctApiService $apiService
    ) {}

    /**
     * Sync every fund with activity in the window. Enumerates unique
     * PROJECTIDs from both local contracts and Intacct's own GL activity,
     * then syncs expenses + budget for each.
     *
     * @param  array|null  $limitToProjectIds  If provided, only sync these project IDs
     * @return array{
     *     success: bool,
     *     funds_synced: int,
     *     funds_failed: int,
     *     total_expense: float,
     *     total_budget: float,
     *     fund_results: list<array>,
     *     from: string,
     *     to: string
     * }
     */
    public function syncAllFunds(
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?array $limitToProjectIds = null,
        bool $dryRun = false
    ): array {
        if ($from === null || $to === null) {
            [$from, $to] = FiscalYearService::currentWindow();
        }

        $projectIds = $this->enumerateFundsToSync($from, $to, $limitToProjectIds);

        $results = [];
        $synced = 0;
        $failed = 0;
        $totalExpense = 0.0;
        $totalBudget = 0.0;

        foreach ($projectIds as $projectId) {
            $r = $this->syncFund($projectId, $from, $to, $dryRun);
            $results[] = $r;
            if ($r['success']) {
                $synced++;
                $totalExpense += (float) ($r['total_expense'] ?? 0);
                $totalBudget += (float) ($r['total_budget'] ?? 0);
            } else {
                $failed++;
            }
        }

        if (! $dryRun) {
            $this->backlinkContractsToFunds();
        }

        return [
            'success' => $failed === 0,
            'funds_synced' => $synced,
            'funds_failed' => $failed,
            'total_expense' => round($totalExpense, 2),
            'total_budget' => round($totalBudget, 2),
            'fund_results' => $results,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /**
     * Sync a single Intacct fund (all department splits at once). Stores
     * expense lines keyed by (project, dept, record_no) and budget rows
     * grouped by (project, dept).
     *
     * @return array{
     *     success: bool,
     *     project_id: string,
     *     expense_lines?: int,
     *     budget_tuples?: int,
     *     total_expense?: float,
     *     total_budget?: float,
     *     error?: string,
     *     tried_budgets?: array
     * }
     */
    public function syncFund(string $projectId, Carbon $from, Carbon $to, bool $dryRun = false): array
    {
        $glFrom = SiteSetting::get('intacct_expense_gl_account_from', '60000');
        $glTo = SiteSetting::get('intacct_expense_gl_account_to', '99999');

        // 1. Pull all expense GL entries for the fund (no dept filter — each
        //    returned line carries its own DEPARTMENT value).
        $expResult = $this->apiService->queryPostedGLEntriesByDimensions(
            projectId: $projectId,
            departmentId: null,
            from: $from,
            to: $to,
            glAccountFrom: $glFrom ?: null,
            glAccountTo: $glTo ?: null,
        );

        if (! $expResult['success']) {
            Log::warning('SageIntacctExpenseService::syncFund expense query failed', [
                'project_id' => $projectId,
                'error' => $expResult['error'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'project_id' => $projectId,
                'error' => $expResult['error'] ?? 'expense query failed',
            ];
        }

        $expenseRecords = $expResult['records'] ?? [];
        $totalExpense = (float) array_sum(array_column($expenseRecords, 'amount'));

        // 2. Pull budget lines for the fund, iterating the configured budget
        //    repositories in priority order.
        $budgetResult = $this->pullFundBudget($projectId, $from, $to);
        $budgetRecords = $budgetResult['records'] ?? [];
        $budgetSource = $budgetResult['budget_id'] ?? null;
        $totalBudget = (float) array_sum(array_column($budgetRecords, 'amount'));

        if ($dryRun) {
            return [
                'success' => true,
                'project_id' => $projectId,
                'expense_lines' => count($expenseRecords),
                'budget_tuples' => $this->countDistinctDepartments($budgetRecords),
                'total_expense' => round($totalExpense, 2),
                'total_budget' => round($totalBudget, 2),
                'dry_run' => true,
            ];
        }

        // 3. Persist expense lines (upsert by project+dept+record_no) and
        //    budget tuples (upsert by project+dept).
        $projectName = $this->pickProjectName($expenseRecords, $budgetRecords);
        $fiscalYear = FiscalYearService::yearFor($to);

        $budgetTuplesCount = 0;

        DB::transaction(function () use (
            $projectId, $from, $to, $fiscalYear, $projectName,
            $expenseRecords, $budgetRecords, $budgetSource, &$budgetTuplesCount
        ) {
            // Wipe existing fund-expense rows for this project within window,
            // then re-insert — idempotent against reversals and late posts.
            IntacctFundExpense::where('intacct_project_id', $projectId)
                ->whereBetween('posting_date', [$from->toDateString(), $to->toDateString()])
                ->delete();

            foreach ($expenseRecords as $rec) {
                if (empty($rec['record_no'])) continue;
                IntacctFundExpense::create([
                    'contract_id' => null, // backlinked later
                    'intacct_record_no' => $rec['record_no'],
                    'intacct_project_id' => $rec['project_id'] ?: $projectId,
                    'intacct_department_id' => $rec['department_id'] ?: '',
                    'intacct_project_name' => $projectName,
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
            }

            // Group budget records by department and upsert per (project, dept).
            $byDept = [];
            foreach ($budgetRecords as $rec) {
                $dept = $rec['department_id'] ?: '';
                if (! isset($byDept[$dept])) {
                    $byDept[$dept] = ['amount' => 0.0, 'lines' => 0];
                }
                $byDept[$dept]['amount'] += (float) $rec['amount'];
                $byDept[$dept]['lines']++;
            }

            // Clean out any prior tuples for this project; about to re-insert
            // exactly what Intacct returned.
            IntacctFundBudget::where('intacct_project_id', $projectId)->delete();

            foreach ($byDept as $dept => $agg) {
                IntacctFundBudget::create([
                    'intacct_project_id' => $projectId,
                    'intacct_department_id' => $dept,
                    'intacct_project_name' => $projectName,
                    'amount' => round($agg['amount'], 2),
                    'budget_source' => $budgetSource,
                    'lines_count' => $agg['lines'],
                    'fiscal_year' => $fiscalYear,
                    'window_start' => $from->toDateString(),
                    'window_end' => $to->toDateString(),
                    'synced_at' => now(),
                ]);
                $budgetTuplesCount++;
            }
        });

        Log::info('SageIntacctExpenseService: synced fund', [
            'project_id' => $projectId,
            'project_name' => $projectName,
            'expense_lines' => count($expenseRecords),
            'total_expense' => round($totalExpense, 2),
            'total_budget' => round($totalBudget, 2),
            'budget_tuples' => $budgetTuplesCount,
            'budget_source' => $budgetSource,
        ]);

        return [
            'success' => true,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'expense_lines' => count($expenseRecords),
            'budget_tuples' => $budgetTuplesCount,
            'total_expense' => round($totalExpense, 2),
            'total_budget' => round($totalBudget, 2),
            'budget_source' => $budgetSource,
            'tried_budgets' => $budgetResult['tried'] ?? [],
        ];
    }

    /**
     * Re-associate expense rows and contract budget denormalization based on
     * current (grant_code, service_code) → (project_id, department_id)
     * mappings. Safe to re-run; only touches rows for funds present in
     * intacct_fund_expenses.
     */
    public function backlinkContractsToFunds(): void
    {
        // 1. Fund expense rows: set contract_id where a unique mapping exists.
        //    Reset all contract_id first so stale links are cleaned up if a
        //    contract's grant_code or service_code changed.
        IntacctFundExpense::whereNotNull('contract_id')->update(['contract_id' => null]);

        $contracts = Contract::whereNotNull('grant_code')->where('grant_code', '!=', '')->get();

        foreach ($contracts as $contract) {
            $projectId = trim((string) $contract->grant_code);
            $deptId = trim((string) ($contract->service_code ?? ''));

            // Skip ambiguous cases: when the contract has no service_code AND
            // another active contract shares the same grant_code, we can't
            // uniquely attribute expense rows — leave contract_id null.
            if ($deptId === '') {
                $siblings = Contract::where('grant_code', $projectId)
                    ->where('id', '!=', $contract->id)
                    ->whereNull('service_code')
                    ->count();
                if ($siblings > 0) {
                    continue;
                }

                // Contract represents the whole project — claim ALL dept splits.
                IntacctFundExpense::where('intacct_project_id', $projectId)
                    ->update(['contract_id' => $contract->id]);
            } else {
                // Contract targets a specific service split — claim only that tuple.
                IntacctFundExpense::where('intacct_project_id', $projectId)
                    ->where('intacct_department_id', $deptId)
                    ->update(['contract_id' => $contract->id]);
            }
        }

        // 2. Denormalize budget onto contracts ONLY when attribution is
        //    unambiguous. For shared grant_codes (F8000 with 68 contracts,
        //    none with service_code), leave intacct_budget_amount null so
        //    the dashboard falls back to the contract's own awarded amount
        //    and system metrics don't multi-count a shared fund's budget
        //    across every sibling contract.
        foreach ($contracts as $contract) {
            $projectId = trim((string) $contract->grant_code);
            $deptId = trim((string) ($contract->service_code ?? ''));

            if ($deptId === '') {
                $siblings = Contract::where('grant_code', $projectId)
                    ->where('id', '!=', $contract->id)
                    ->whereNull('service_code')
                    ->count();

                if ($siblings > 0) {
                    // Ambiguous — clear any stale denormalized budget.
                    $contract->update([
                        'intacct_budget_amount' => null,
                        'intacct_budget_synced_at' => now(),
                        'intacct_budget_source' => null,
                        'intacct_budget_lines_count' => 0,
                        'last_synced_at' => now(),
                    ]);
                    continue;
                }

                // Unique owner of the whole project — sum all department splits.
                $budget = IntacctFundBudget::where('intacct_project_id', $projectId)->sum('amount');
                $bRow = IntacctFundBudget::where('intacct_project_id', $projectId)
                    ->orderByDesc('amount')
                    ->first();
                $source = $bRow->budget_source ?? null;
                $linesCount = IntacctFundBudget::where('intacct_project_id', $projectId)->sum('lines_count');
            } else {
                $bRow = IntacctFundBudget::where('intacct_project_id', $projectId)
                    ->where('intacct_department_id', $deptId)
                    ->first();
                $budget = $bRow?->amount ?? 0;
                $source = $bRow?->budget_source;
                $linesCount = $bRow?->lines_count ?? 0;
            }

            if ((float) $budget > 0) {
                $contract->update([
                    'intacct_budget_amount' => $budget,
                    'intacct_budget_synced_at' => now(),
                    'intacct_budget_source' => $source,
                    'intacct_budget_lines_count' => $linesCount,
                    'last_synced_at' => now(),
                ]);
            } else {
                $contract->update([
                    'intacct_budget_amount' => null,
                    'intacct_budget_synced_at' => now(),
                    'intacct_budget_source' => null,
                    'intacct_budget_lines_count' => 0,
                    'last_synced_at' => now(),
                ]);
            }
        }
    }

    /**
     * Enumerate unique PROJECTIDs to sync, combining:
     * - distinct grant_codes from local contracts
     * - PROJECTIDs with GL activity in Intacct (catches Sage-only funds)
     *
     * @return list<string>
     */
    private function enumerateFundsToSync(Carbon $from, Carbon $to, ?array $limit = null): array
    {
        if (is_array($limit) && ! empty($limit)) {
            return array_values(array_unique(array_filter(array_map('trim', $limit))));
        }

        $fromContracts = Contract::whereNotNull('grant_code')
            ->where('grant_code', '!=', '')
            ->pluck('grant_code')
            ->map(fn ($c) => trim((string) $c))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $intacctResult = $this->apiService->enumerateActiveProjectIdsInWindow($from, $to);
        $fromIntacct = $intacctResult['success'] ? ($intacctResult['project_ids'] ?? []) : [];

        $merged = array_unique(array_merge($fromContracts, $fromIntacct));
        sort($merged);

        return array_values($merged);
    }

    /**
     * Pull budget records for a fund, iterating configured budget-ID priority.
     * Returns the first repository that yields a non-zero result, or an empty
     * array if none have data.
     */
    private function pullFundBudget(string $projectId, Carbon $from, Carbon $to): array
    {
        $glFrom = SiteSetting::get('intacct_expense_gl_account_from', '60000');
        $glTo = SiteSetting::get('intacct_expense_gl_account_to', '99999');

        $budgetIds = $this->resolveBudgetCandidateList();
        $tried = [];

        foreach ($budgetIds as $bid) {
            $result = $this->apiService->queryFundBudgetByDimensions(
                projectId: $projectId,
                departmentId: null,
                from: $from,
                to: $to,
                budgetId: $bid ?: null,
                glAccountFrom: $glFrom ?: null,
                glAccountTo: $glTo ?: null,
            );

            $tried[] = [
                'budget_id' => $bid ?: '(auto)',
                'success' => $result['success'] ?? false,
                'total' => $result['total_expense'] ?? 0,
            ];

            if (! $result['success']) continue;

            $records = $result['records'] ?? [];
            $total = (float) array_sum(array_column($records, 'amount'));

            if ($total > 0) {
                return ['records' => $records, 'budget_id' => $result['budget_id'] ?? null, 'tried' => $tried];
            }
        }

        return ['records' => [], 'budget_id' => null, 'tried' => $tried];
    }

    /**
     * @return list<string>  Ordered list of BUDGETIDs to try ('' = auto-detect).
     */
    private function resolveBudgetCandidateList(): array
    {
        $json = SiteSetting::get('intacct_budget_ids', null);
        if ($json) {
            $list = json_decode($json, true);
            if (is_array($list)) {
                $list = array_values(array_filter(array_map('trim', $list), fn ($v) => $v !== ''));
                if (! empty($list)) return $list;
            }
        }

        $legacy = trim((string) SiteSetting::get('intacct_default_budget_id', ''));
        if ($legacy !== '') return [$legacy];

        return [''];
    }

    private function countDistinctDepartments(array $records): int
    {
        $depts = [];
        foreach ($records as $r) {
            $depts[$r['department_id'] ?: ''] = true;
        }

        return count($depts);
    }

    private function pickProjectName(array $expenseRecords, array $budgetRecords): ?string
    {
        foreach ([$expenseRecords, $budgetRecords] as $set) {
            foreach ($set as $r) {
                if (! empty($r['raw']['PROJECTNAME'])) {
                    return (string) $r['raw']['PROJECTNAME'];
                }
            }
        }

        return null;
    }

    private static function parseIntacctDate(string $date): ?Carbon
    {
        if (empty($date)) return null;
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
