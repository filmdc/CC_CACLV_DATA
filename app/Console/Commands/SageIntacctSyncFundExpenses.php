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
                            {--contract-id= : Sync only the contract with this ID}
                            {--contract-ids= : Comma-separated list of contract IDs to sync (e.g. 1,2,3)}
                            {--grant-code= : Sync only contracts matching this fund code (grant_code)}
                            {--from= : Start date YYYY-MM-DD (default: current fiscal year start)}
                            {--to= : End date YYYY-MM-DD (default: current fiscal year end)}
                            {--include-archived : Include archived contracts (default: active only)}
                            {--dry-run : Query Intacct and report results without persisting}
                            {--force : Skip production confirmation prompt}';

    protected $description = 'Sync posted GL expense totals from Sage Intacct into local fund utilization data, keyed by contract grant_code + service_code.';

    public function handle(): int
    {
        $this->info('Sage Intacct Fund Expense Sync');
        $this->info('==============================');
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
            $this->error('GLDETAIL queries require XML Web Services auth. Switch the active setting to XML mode.');

            return Command::FAILURE;
        }

        [$from, $to] = $this->resolveDateWindow();
        $this->line('Window: '.$from->toDateString().' through '.$to->toDateString().' (FY '.FiscalYearService::yearFor($to).')');

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE — no data will be persisted.');
        }

        $contracts = $this->resolveContracts();

        if ($contracts->isEmpty()) {
            $this->warn('No contracts matched the filters. Nothing to sync.');

            return Command::SUCCESS;
        }

        $this->info('Contracts to process: '.$contracts->count());
        $this->newLine();

        if (! $dryRun && app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('Proceed with syncing '.$contracts->count().' contract(s) in PRODUCTION?', false)) {
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
            'sync_notes' => 'Fund expense sync (GLENTRY → intacct_fund_expenses)',
        ]);

        $apiService = new SageIntacctApiService($setting);
        $expenseService = new SageIntacctExpenseService($apiService);

        $results = [
            'processed' => 0,
            'synced' => 0,
            'skipped' => 0,
            'failed' => 0,
            'records' => 0,
            'amount' => 0.0,
        ];

        $rows = [];
        $bar = $this->output->createProgressBar($contracts->count());
        $bar->start();

        foreach ($contracts as $contract) {
            $results['processed']++;

            try {
                $result = $expenseService->syncContract($contract, $from, $to, $dryRun);

                if (! empty($result['skipped'])) {
                    $results['skipped']++;
                    $rows[] = [$contract->id, $contract->grant_code ?: '-', 'skipped', $result['reason'] ?? '-', '-'];
                } elseif ($result['success']) {
                    $results['synced']++;
                    $lines = $result['records_synced'] ?? $result['records_found'] ?? 0;
                    $amount = $result['total_amount'] ?? 0.0;
                    $results['records'] += $lines;
                    $results['amount'] += $amount;
                    $rows[] = [$contract->id, $contract->grant_code.($contract->service_code ? ' + '.$contract->service_code : ''), 'ok', $lines.' lines', '$'.number_format($amount, 2)];
                } else {
                    $results['failed']++;
                    $rows[] = [$contract->id, $contract->grant_code ?: '-', 'error', substr($result['error'] ?? 'unknown', 0, 80), '-'];
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                Log::error('intacct:sync-fund-expenses failed for contract', [
                    'contract_id' => $contract->id,
                    'grant_code' => $contract->grant_code,
                    'error' => $e->getMessage(),
                ]);
                $rows[] = [$contract->id, $contract->grant_code ?: '-', 'exception', substr($e->getMessage(), 0, 80), '-'];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($history) {
            $history->update([
                'total_records' => $results['processed'],
                'successful_count' => $results['synced'],
                'failed_count' => $results['failed'],
                'skipped_count' => $results['skipped'],
            ]);

            if ($results['failed'] === 0) {
                $history->markAsSuccess();
            } elseif ($results['synced'] === 0) {
                $history->markAsFailed('All contracts failed to sync');
            } else {
                $history->markAsPartial();
            }
        }

        $this->table(['Contract', 'Coding', 'Status', 'Detail', 'Amount'], $rows);
        $this->newLine();
        $this->info(sprintf(
            'Processed: %d | Synced: %d | Skipped: %d | Failed: %d | Lines: %d | Total: $%s',
            $results['processed'],
            $results['synced'],
            $results['skipped'],
            $results['failed'],
            $results['records'],
            number_format($results['amount'], 2)
        ));

        return $results['failed'] > 0 && $results['synced'] === 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    /**
     * Resolve the [from, to] date window, defaulting to current FY.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
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
     * Resolve the contracts to sync based on command options.
     */
    private function resolveContracts()
    {
        $query = Contract::query();

        if (! $this->option('include-archived')) {
            $query->active();
        }

        $query->whereNotNull('grant_code')->where('grant_code', '!=', '');

        if ($contractId = $this->option('contract-id')) {
            $query->where('id', $contractId);
        }

        if ($contractIds = $this->option('contract-ids')) {
            $ids = array_filter(array_map('intval', explode(',', $contractIds)));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        if ($grantCode = $this->option('grant-code')) {
            $query->where('grant_code', $grantCode);
        }

        return $query->orderBy('id')->get();
    }
}
