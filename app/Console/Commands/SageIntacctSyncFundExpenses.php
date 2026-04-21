<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncHistory;
use App\Models\SiteSetting;
use App\Services\FiscalYearService;
use App\Services\SageIntacctApiService;
use App\Services\SageIntacctExpenseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SageIntacctSyncFundExpenses extends Command
{
    protected $signature = 'intacct:sync-fund-expenses
                            {--contract-id= : Sync funds referenced by only this contract ID}
                            {--contract-ids= : Comma-separated list of contract IDs to limit to}
                            {--grant-code= : Sync only the given Intacct PROJECTID (e.g. F0412)}
                            {--grant-codes= : Comma-separated list of PROJECTIDs to sync}
                            {--from= : Start date YYYY-MM-DD (default: current fiscal year start)}
                            {--to= : End date YYYY-MM-DD (default: current fiscal year end)}
                            {--dry-run : Query Intacct and report results without persisting}
                            {--force : Skip production confirmation prompt}';

    protected $description = 'Sync posted GL expenses + budgets from Sage Intacct into the fund-keyed utilization tables (intacct_fund_expenses, intacct_fund_budgets).';

    public function handle(): int
    {
        $this->info('Sage Intacct Fund Sync (fund-keyed)');
        $this->info('====================================');
        $this->newLine();

        if (SiteSetting::get('intacct_expense_sync_enabled', '1') !== '1') {
            $this->warn('Intacct fund expense sync is disabled in site settings.');

            return Command::SUCCESS;
        }

        $setting = SageIntacctSetting::getActive();

        if (! $setting || ! $setting->isConfigured()) {
            $this->error('No active, fully-configured Sage Intacct setting found.');

            return Command::FAILURE;
        }

        if (! $setting->isXmlApi()) {
            $this->error('GL queries require XML Web Services auth. Switch the active setting to XML mode.');

            return Command::FAILURE;
        }

        [$from, $to] = $this->resolveDateWindow();
        $this->line('Window: '.$from->toDateString().' through '.$to->toDateString().' (FY '.FiscalYearService::yearFor($to).')');

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE — no data will be persisted.');
        }

        $limitToProjectIds = $this->resolveProjectIdsFilter();
        if ($limitToProjectIds !== null) {
            $this->line('Scoped to '.count($limitToProjectIds).' fund(s): '.implode(', ', array_slice($limitToProjectIds, 0, 10)).(count($limitToProjectIds) > 10 ? '...' : ''));
        } else {
            $this->line('Syncing all funds with activity in the window (contracts ∪ Intacct GL).');
        }

        if (! $dryRun && app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('Proceed with fund sync in PRODUCTION?', false)) {
                $this->line('Aborted.');

                return Command::SUCCESS;
            }
        }

        $history = $dryRun ? null : SageIntacctSyncHistory::create([
            'status' => SageIntacctSyncHistory::STATUS_RUNNING,
            'started_at' => now(),
            'sync_start_date' => $from->toDateString(),
            'sync_end_date' => $to->toDateString(),
            'triggered_by' => 'console:sync-fund-expenses',
            'sync_notes' => 'Fund-keyed sync (GLENTRY + GLBUDGETITEM → intacct_fund_expenses + intacct_fund_budgets)',
        ]);

        $apiService = new SageIntacctApiService($setting);
        $service = new SageIntacctExpenseService($apiService);

        try {
            $result = $service->syncAllFunds($from, $to, $limitToProjectIds, $dryRun);
        } catch (\Throwable $e) {
            Log::error('intacct:sync-fund-expenses fatal', ['error' => $e->getMessage()]);
            $history?->markAsFailed($e->getMessage());
            $this->error('Sync failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        if ($history) {
            $history->update([
                'total_records' => count($result['fund_results']),
                'successful_count' => $result['funds_synced'],
                'failed_count' => $result['funds_failed'],
            ]);
            if ($result['funds_failed'] === 0) {
                $history->markAsSuccess();
            } elseif ($result['funds_synced'] === 0) {
                $history->markAsFailed('All fund syncs failed');
            } else {
                $history->markAsPartial();
            }
        }

        $this->renderResultsTable($result['fund_results']);
        $this->newLine();
        $this->info(sprintf(
            'Funds synced: %d | Failed: %d | Total expense: $%s | Total budget: $%s',
            $result['funds_synced'],
            $result['funds_failed'],
            number_format($result['total_expense'], 2),
            number_format($result['total_budget'], 2)
        ));

        return $result['funds_failed'] > 0 && $result['funds_synced'] === 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    private function resolveDateWindow(): array
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if ($from && $to) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        }

        return FiscalYearService::currentWindow();
    }

    /**
     * Translate command-line filters into an explicit PROJECTID list, or
     * return null to sync everything.
     */
    private function resolveProjectIdsFilter(): ?array
    {
        $ids = [];

        if ($pid = $this->option('grant-code')) {
            $ids[] = trim($pid);
        }

        if ($pids = $this->option('grant-codes')) {
            foreach (explode(',', $pids) as $p) {
                $t = trim($p);
                if ($t !== '') $ids[] = $t;
            }
        }

        $contractIds = [];
        if ($cid = $this->option('contract-id')) {
            $contractIds[] = (int) $cid;
        }
        if ($cids = $this->option('contract-ids')) {
            foreach (explode(',', $cids) as $c) {
                $contractIds[] = (int) trim($c);
            }
        }

        if (! empty($contractIds)) {
            $codes = Contract::whereIn('id', $contractIds)
                ->whereNotNull('grant_code')->where('grant_code', '!=', '')
                ->pluck('grant_code')
                ->map(fn ($g) => trim((string) $g))
                ->all();
            $ids = array_merge($ids, $codes);
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return ! empty($ids) ? $ids : null;
    }

    private function renderResultsTable(array $results): void
    {
        $rows = [];
        foreach ($results as $r) {
            $rows[] = [
                $r['project_id'] ?? '-',
                $r['success'] ? 'ok' : 'fail',
                isset($r['expense_lines']) ? $r['expense_lines'] : 0,
                isset($r['budget_tuples']) ? $r['budget_tuples'] : 0,
                isset($r['total_expense']) ? '$'.number_format($r['total_expense'], 2) : '-',
                isset($r['total_budget']) ? '$'.number_format($r['total_budget'], 2) : '-',
                $r['budget_source'] ?? ($r['error'] ?? '-'),
            ];
        }
        $this->table(
            ['Fund', 'Status', 'Exp Lines', 'Budget Tuples', 'Total Expense', 'Total Budget', 'Budget Src / Error'],
            $rows
        );
    }
}
