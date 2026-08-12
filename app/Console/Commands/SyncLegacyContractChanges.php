<?php

namespace App\Console\Commands;

use App\Models\LegacyContractSyncRun;
use App\Services\LegacyContractSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SyncLegacyContractChanges extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'contracts:sync-legacy
                            {--dry-run : Report the drift without changing anything}
                            {--new-only : Import new contracts, leave existing ones alone}
                            {--updated-only : Refresh changed contracts, import nothing new}
                            {--limit= : Cap how many contracts are touched this run}
                            {--no-media : Skip downloading attachments}
                            {--media-only : Only backfill missing attachments on already-synced contracts}
                            {--no-notify : Do not send the run summary email}
                            {--trigger=command : Where this run came from (scheduled|manual|command)}';

    /**
     * The console command description.
     */
    protected $description = 'Check the legacy contract database for new and changed contracts and sync them into CAP TRACK';

    public function handle(LegacyContractSyncService $service): int
    {
        if (! config('legacy-import.auto_sync.enabled', true)) {
            $this->warn('Automatic legacy contract sync is disabled (legacy-import.auto_sync.enabled).');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $newOnly = (bool) $this->option('new-only');
        $updatedOnly = (bool) $this->option('updated-only');

        if ($newOnly && $updatedOnly) {
            $this->error('--new-only and --updated-only are mutually exclusive.');

            return self::FAILURE;
        }

        // Confirm the legacy box is actually reachable before opening a run
        // record — a dead VPN link should not look like a failed sync.
        $connection = $service->testConnection();

        if (! $connection['success']) {
            $this->error('Cannot reach the legacy contract database: '.$connection['message']);

            LegacyContractSyncRun::create([
                'status' => 'failed',
                'triggered_by' => $this->option('trigger'),
                'dry_run' => $dryRun,
                'started_at' => now(),
                'completed_at' => now(),
                'error_message' => 'Connection failed: '.$connection['message'],
            ]);

            return self::FAILURE;
        }

        if ($this->option('media-only')) {
            return $this->backfillMedia($service);
        }

        $run = LegacyContractSyncRun::create([
            'status' => 'running',
            'triggered_by' => $this->option('trigger'),
            'dry_run' => $dryRun,
            'started_at' => now(),
        ]);

        $started = microtime(true);

        $this->info($dryRun
            ? '🔍 Checking the legacy contract database for drift (dry run)...'
            : '🔄 Syncing contracts from the legacy database...');

        try {
            $result = $service->autoSync([
                'import_new' => ! $updatedOnly,
                'update_existing' => ! $newOnly,
                'sync_media' => ! $this->option('no-media'),
                'limit' => $this->option('limit') !== null ? (int) $this->option('limit') : null,
                'dry_run' => $dryRun,
            ]);

            $this->recordResult($run, $result, $started);
            $this->report($result, $dryRun);

            if (! $dryRun && ! $this->option('no-notify')) {
                $this->notify($run->refresh());
            }

            return $run->status === 'failed' ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'error_message' => $e->getMessage(),
            ]);

            $this->error('❌ Sync failed: '.$e->getMessage());

            Log::error('Legacy contract auto-sync failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Fill in attachments missing from contracts that are otherwise in step.
     */
    protected function backfillMedia(LegacyContractSyncService $service): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->info('📎 Backfilling missing attachments on already-synced contracts...');
        $this->newLine();

        $result = $service->backfillMedia($limit);

        $this->info(sprintf(
            'Checked %d contract(s); downloaded %d attachment(s).',
            $result['contracts_checked'],
            $result['stats']['media_synced'] ?? 0
        ));

        foreach (($result['warnings'] ?? []) as $warning) {
            $this->line('<fg=yellow>⚠</> '.$warning);
        }

        foreach (($result['errors'] ?? []) as $error) {
            $this->line('<fg=red>✖</> '.$error);
        }

        $this->newLine();
        $this->info('✅ Done.');

        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Persist the outcome of a run.
     */
    protected function recordResult(LegacyContractSyncRun $run, array $result, float $started): void
    {
        $stats = $result['stats'] ?? [];
        $detected = $result['detected'] ?? [];
        $errors = $result['errors'] ?? [];

        $applied = ($stats['created'] ?? 0) + ($stats['updated'] ?? 0);

        $status = match (true) {
            ! ($result['success'] ?? false) => 'failed',
            ! empty($errors) && $applied > 0 => 'partial',
            ! empty($errors) => 'failed',
            default => 'success',
        };

        $run->update([
            'status' => $status,
            'completed_at' => now(),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'detected_new' => $detected['new'] ?? 0,
            'detected_updated' => $detected['updated'] ?? 0,
            'detected_deleted' => $detected['deleted_in_legacy'] ?? 0,
            'unchanged_count' => $detected['unchanged'] ?? 0,
            'created_count' => $stats['created'] ?? 0,
            'updated_count' => $stats['updated'] ?? 0,
            'skipped_count' => $stats['skipped'] ?? 0,
            'due_dates_created' => $stats['due_dates_created'] ?? 0,
            'due_dates_updated' => $stats['due_dates_updated'] ?? 0,
            'media_synced' => $stats['media_synced'] ?? 0,
            'funders_created' => $stats['funders_created'] ?? 0,
            'deferred_count' => $result['deferred'] ?? 0,
            'errors' => $errors ?: null,
            'warnings' => ($result['warnings'] ?? []) ?: null,
            'deleted_legacy_ids' => array_column($result['changes']['deleted_in_legacy'] ?? [], 'legacy_id') ?: null,
            'error_message' => $result['error'] ?? null,
        ]);
    }

    /**
     * Print a human-readable summary to the console.
     */
    protected function report(array $result, bool $dryRun): void
    {
        $detected = $result['detected'] ?? [];
        $stats = $result['stats'] ?? [];

        $this->newLine();
        $this->line('<options=bold>Legacy comparison</>');
        $this->table(
            ['Bucket', 'Count'],
            [
                ['New in legacy (not yet imported)', $detected['new'] ?? 0],
                ['Changed since our last sync', $detected['updated'] ?? 0],
                ['Deleted in legacy (still here)', $detected['deleted_in_legacy'] ?? 0],
                ['Unchanged', $detected['unchanged'] ?? 0],
            ]
        );

        if ($dryRun) {
            $would = $result['would_sync'] ?? [];
            $this->newLine();
            $this->info('Dry run — nothing was changed.');

            if ($would) {
                $this->line('Would sync '.count($would).' contract(s): legacy id '.
                    implode(', ', array_slice($would, 0, 40)).(count($would) > 40 ? ', …' : ''));
            } else {
                $this->line('Nothing to sync.');
            }
        } else {
            $this->newLine();
            $this->line('<options=bold>Applied</>');
            $this->table(
                ['Action', 'Count'],
                [
                    ['Contracts imported', $stats['created'] ?? 0],
                    ['Contracts updated', $stats['updated'] ?? 0],
                    ['Contracts skipped', $stats['skipped'] ?? 0],
                    ['Due dates created', $stats['due_dates_created'] ?? 0],
                    ['Due dates updated', $stats['due_dates_updated'] ?? 0],
                    ['Attachments downloaded', $stats['media_synced'] ?? 0],
                    ['Funders created', $stats['funders_created'] ?? 0],
                ]
            );
        }

        $deleted = $result['changes']['deleted_in_legacy'] ?? [];
        if ($deleted) {
            $strategy = $result['deleted_handling']['strategy'] ?? 'report';
            $this->newLine();
            $this->warn(count($deleted).' contract(s) exist here but are deleted in the legacy system'.
                ($strategy === 'archive' ? ' (archived locally):' : ' — review manually:'));

            foreach (array_slice($deleted, 0, 15) as $row) {
                $this->line(sprintf('  • legacy #%s → contract #%s: %s',
                    $row['legacy_id'], $row['contract_id'], \Illuminate\Support\Str::limit($row['title'] ?? '-', 60)));
            }

            if (count($deleted) > 15) {
                $this->line('  … and '.(count($deleted) - 15).' more');
            }
        }

        foreach (($result['warnings'] ?? []) as $warning) {
            $this->line('<fg=yellow>⚠</> '.$warning);
        }

        foreach (($result['errors'] ?? []) as $error) {
            $this->line('<fg=red>✖</> '.$error);
        }

        $this->newLine();
        $this->info('✅ Done.');
    }

    /**
     * Mail the run summary to the configured recipients.
     */
    protected function notify(LegacyContractSyncRun $run): void
    {
        $recipients = config('legacy-import.auto_sync.notify.recipients', []);

        if (empty($recipients)) {
            return;
        }

        // Quiet nights stay quiet unless the operator asked for every run.
        if (config('legacy-import.auto_sync.notify.only_on_change', true)
            && ! $run->madeChanges()
            && ! $run->hasProblems()) {
            return;
        }

        try {
            Mail::send(
                'emails.legacy-contract-sync-summary',
                ['run' => $run],
                function ($message) use ($recipients, $run) {
                    $message->to($recipients)
                        ->subject('Legacy contract sync — '.$run->summaryLine());
                }
            );
        } catch (\Throwable $e) {
            // A failed notification must not turn a good sync into a bad run.
            Log::warning('Could not send legacy contract sync summary', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
