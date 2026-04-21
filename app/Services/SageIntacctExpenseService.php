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

        Log::info('SageIntacctExpenseService: synced contract', [
            'contract_id' => $contract->id,
            'grant_code' => $contract->grant_code,
            'service_code' => $contract->service_code,
            'records_synced' => $inserted,
            'total_amount' => round($totalAmount, 2),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        return [
            'success' => true,
            'contract_id' => $contract->id,
            'records_synced' => $inserted,
            'total_amount' => round($totalAmount, 2),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
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
