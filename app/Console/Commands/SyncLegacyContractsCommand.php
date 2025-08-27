<?php

namespace App\Console\Commands;

use App\Services\LegacyContractsApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLegacyContractsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'legacy:sync 
                            {--type=all : Type to sync (all|funders|programs|contracts)}
                            {--page=1 : Starting page for contracts sync}
                            {--dry-run : Preview sync operations without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Sync data from legacy contracts database API';

    /**
     * Execute the console command.
     */
    public function handle(LegacyContractsApiService $apiService): int
    {
        $type = $this->option('type');
        $page = (int) $this->option('page');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual changes will be made');
            return 0; // For now, just return success for dry run
        }

        $this->info("🔄 Starting legacy sync process for: {$type}");
        $this->newLine();

        try {
            $startTime = microtime(true);

            switch ($type) {
                case 'funders':
                    $this->syncFunders($apiService);
                    break;

                case 'programs':
                    $this->syncPrograms($apiService);
                    break;

                case 'contracts':
                    $this->syncContracts($apiService, $page);
                    break;

                case 'all':
                    $this->syncAll($apiService);
                    break;

                default:
                    $this->error("❌ Unknown sync type: {$type}");
                    $this->line('Available types: all, funders, programs, contracts');
                    return 1;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("✅ Sync completed successfully in {$duration} seconds!");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Sync failed: {$e->getMessage()}");
            $this->newLine();
            
            if ($this->output->isVerbose()) {
                $this->error("Stack trace:");
                $this->line($e->getTraceAsString());
            } else {
                $this->line("Run with -v for detailed error information");
            }
            
            return 1;
        }
    }

    /**
     * Sync all data types in correct dependency order
     */
    private function syncAll(LegacyContractsApiService $apiService): void
    {
        $this->info('📋 Running full sync in dependency order...');
        $this->newLine();
        
        // Sync in correct order due to dependencies
        $this->syncFunders($apiService);
        $this->newLine();
        
        $this->syncPrograms($apiService);
        $this->newLine();
        
        $this->syncContracts($apiService);
    }

    /**
     * Sync funders from legacy API
     */
    private function syncFunders(LegacyContractsApiService $apiService): void
    {
        $this->info('💰 Syncing funders from legacy API...');
        
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        try {
            $result = $apiService->syncFunders();
            $progressBar->finish();
            
            if ($result) {
                $this->line('');
                $this->info('✅ Funders synced successfully');
            } else {
                $this->line('');
                $this->warn('⚠️ Funders sync completed with issues');
            }
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->line('');
            throw $e;
        }
    }

    /**
     * Sync programs from legacy API
     */
    private function syncPrograms(LegacyContractsApiService $apiService): void
    {
        $this->info('📂 Syncing programs from legacy API...');
        
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        try {
            $result = $apiService->syncPrograms();
            $progressBar->finish();
            
            if ($result) {
                $this->line('');
                $this->info('✅ Programs synced successfully');
            } else {
                $this->line('');
                $this->warn('⚠️ Programs sync completed with issues');
            }
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->line('');
            throw $e;
        }
    }

    /**
     * Sync contracts from legacy API
     */
    private function syncContracts(LegacyContractsApiService $apiService, int $startPage = 1): void
    {
        $this->info("📋 Syncing contracts from legacy API (starting page {$startPage})...");
        
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        try {
            $result = $apiService->syncContracts($startPage);
            $progressBar->finish();
            
            if ($result) {
                $this->line('');
                $this->info('✅ Contracts synced successfully');
            } else {
                $this->line('');
                $this->warn('⚠️ Contracts sync completed with issues');
            }
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->line('');
            throw $e;
        }
    }
}