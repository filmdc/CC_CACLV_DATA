<?php

namespace App\Console\Commands;

use App\Models\Funder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyFunders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:legacy-funders {--dry-run : Run without actually migrating data} {--csv : Generate CSV report of funder-contract relationships}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate funders from legacy Contract Database system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Legacy Contract Database - Funders Migration');
        $this->info('==============================================');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be modified');
            $this->newLine();
        }

        // Connect to legacy database
        $this->info('Connecting to legacy database at 10.55.0.10...');

        try {
            $allLegacyFunders = DB::connection('legacy_contracts')->table('funders')->get();
            $this->info("Found {$allLegacyFunders->count()} funders in legacy system");

            // Get funder IDs that have contracts (archived or unarchived)
            $funderIdsWithContracts = DB::connection('legacy_contracts')
                ->table('contracts')
                ->whereNotNull('funder_id')
                ->distinct()
                ->pluck('funder_id')
                ->toArray();

            // Filter to only funders with contracts
            $legacyFunders = $allLegacyFunders->whereIn('id', $funderIdsWithContracts);
            $unusedFunders = $allLegacyFunders->whereNotIn('id', $funderIdsWithContracts);
            $unusedFundersCount = $unusedFunders->count();

            $this->info("Funders with contracts: {$legacyFunders->count()}");
            $this->warn("Funders with no contracts (will skip): {$unusedFundersCount}");

            // In dry-run mode, show detailed lists
            if ($this->option('dry-run')) {
                $this->newLine();
                $this->info('==============================================');
                $this->info('  FUNDERS WITH CONTRACTS');
                $this->info('==============================================');

                foreach ($legacyFunders as $funder) {
                    // Get contracts for this funder
                    $contracts = DB::connection('legacy_contracts')
                        ->table('contracts')
                        ->where('funder_id', $funder->id)
                        ->select('id', 'title')
                        ->get();

                    $contractIds = $contracts->pluck('id')->implode(', ');
                    $this->line("ID: {$funder->id} | Name: {$funder->funder_name}");
                    $this->line("  → Contracts ({$contracts->count()}): {$contractIds}");
                }

                $this->newLine();
                $this->info('==============================================');
                $this->info('  FUNDERS WITHOUT CONTRACTS (WILL SKIP)');
                $this->info('==============================================');

                foreach ($unusedFunders as $funder) {
                    $this->line("ID: {$funder->id} | Name: {$funder->funder_name}");
                }

                $this->newLine();
            }

            // Generate CSV if requested
            if ($this->option('csv')) {
                $this->info('Generating CSV report...');
                $csvPath = $this->generateCsvReport($legacyFunders);
                $this->info("CSV report saved to: {$csvPath}");
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database: '.$e->getMessage());

            return 1;
        }

        if ($legacyFunders->isEmpty()) {
            $this->warn('No funders with contracts found in legacy system');

            return 0;
        }

        $this->newLine();
        $this->info('Starting migration...');
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyFunders->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        $noContracts = 0;

        foreach ($legacyFunders as $legacyFunder) {
            try {
                // Check if already migrated
                $exists = Funder::where('legacy_id', $legacyFunder->id)->first();

                if ($exists) {
                    $skipped++;
                    $this->logValidation('funders', $legacyFunder->id, $exists->id, 'skipped', ['reason' => 'Already migrated']);
                    $bar->advance();

                    continue;
                }

                if (! $this->option('dry-run')) {
                    $funder = Funder::create([
                        'name' => $legacyFunder->funder_name,
                        'code' => null, // Legacy has no code field
                        'description' => null,
                        'contact_person' => null,
                        'contact_email' => null,
                        'contact_phone' => null,
                        'address' => null,
                        'website' => null,
                        'active' => true,
                        'created_at' => $legacyFunder->created_at,
                        'updated_at' => $legacyFunder->updated_at,
                        'deleted_at' => $legacyFunder->deleted_at,
                        'created_by' => null,
                        'updated_by' => null,
                        'legacy_id' => $legacyFunder->id,
                        'last_synced_at' => now(),
                    ]);

                    $this->logValidation('funders', $legacyFunder->id, $funder->id, 'success');
                }

                $migrated++;
            } catch (\Exception $e) {
                $errors++;
                $this->logValidation('funders', $legacyFunder->id, null, 'error', [
                    'message' => $e->getMessage(),
                    'funder_name' => $legacyFunder->funder_name ?? 'unknown',
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('==============================================');
        $this->info('  Migration Summary');
        $this->info('==============================================');
        $this->info("Total Legacy Funders: {$allLegacyFunders->count()}");
        $this->info("Funders with contracts: {$legacyFunders->count()}");
        $this->warn("Funders with no contracts (skipped): {$unusedFundersCount}");
        $this->info("Migrated: {$migrated}");
        $this->info("Skipped (already migrated): {$skipped}");
        $this->error("Errors: {$errors}");

        if ($errors > 0) {
            $this->newLine();
            $this->warn('Check migration_validation_log table for error details');
        }

        $this->newLine();
        $this->info('✓ Funders migration completed');

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Generate CSV report of funder-contract relationships
     */
    protected function generateCsvReport($funders)
    {
        $filename = 'funder_contracts_'.date('Y-m-d_His').'.csv';
        $filepath = storage_path('app/'.$filename);

        $handle = fopen($filepath, 'w');

        // Write CSV header
        fputcsv($handle, ['Funder ID', 'Funder Name', 'Contract ID', 'Contract Title']);

        // Write data rows
        foreach ($funders as $funder) {
            $contracts = DB::connection('legacy_contracts')
                ->table('contracts')
                ->where('funder_id', $funder->id)
                ->select('id', 'title')
                ->get();

            if ($contracts->isEmpty()) {
                // If somehow a funder has no contracts, still add a row
                fputcsv($handle, [
                    $funder->id,
                    $funder->funder_name,
                    '',
                    'No contracts',
                ]);
            } else {
                foreach ($contracts as $contract) {
                    fputcsv($handle, [
                        $funder->id,
                        $funder->funder_name,
                        $contract->id,
                        $contract->title,
                    ]);
                }
            }
        }

        fclose($handle);

        return $filepath;
    }

    /**
     * Log validation entry
     */
    protected function logValidation(string $entityType, ?int $legacyId, ?int $newId, string $status, array $errors = [])
    {
        if ($this->option('dry-run')) {
            return;
        }

        DB::table('migration_validation_log')->insert([
            'entity_type' => $entityType,
            'legacy_id' => $legacyId,
            'new_id' => $newId,
            'status' => $status,
            'errors' => $errors ? json_encode($errors) : null,
            'created_at' => now(),
        ]);
    }
}
