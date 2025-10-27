<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Funder;
use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyContracts extends Command
{
    protected $signature = 'migrate:legacy-contracts {--dry-run : Run without actually migrating data}';

    protected $description = 'Migrate contracts from legacy Contract Database system';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Legacy Contract Database - Contracts Migration');
        $this->info('==============================================');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be modified');
            $this->newLine();
        }

        $this->info('Connecting to legacy database at 10.55.0.10...');

        try {
            $legacyContracts = DB::connection('legacy_contracts')->table('contracts')->get();
            $this->info("Found {$legacyContracts->count()} contracts in legacy system");
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database: '.$e->getMessage());

            return 1;
        }

        if ($legacyContracts->isEmpty()) {
            $this->warn('No contracts found in legacy system');

            return 0;
        }

        // Dry-run analysis
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('==============================================');
            $this->info('  DRY RUN ANALYSIS');
            $this->info('==============================================');

            // Archive status breakdown
            $archivedCount = $legacyContracts->where('archived', 'yes')->count();
            $activeCount = $legacyContracts->count() - $archivedCount;
            $this->info("Active Contracts: {$activeCount}");
            $this->info("Archived Contracts: {$archivedCount}");

            // Funder relationships
            $withFunder = $legacyContracts->whereNotNull('funder_id')->count();
            $withoutFunder = $legacyContracts->whereNull('funder_id')->count();
            $this->info("Contracts with Funder: {$withFunder}");
            $this->warn("Contracts without Funder: {$withoutFunder}");

            // Program relationships
            $contractsWithPrograms = 0;
            $contractsWithoutPrograms = 0;
            foreach ($legacyContracts as $contract) {
                $hasPrograms = DB::connection('legacy_contracts')
                    ->table('contract_program')
                    ->where('contract_id', $contract->id)
                    ->exists();

                if ($hasPrograms) {
                    $contractsWithPrograms++;
                } else {
                    $contractsWithoutPrograms++;
                }
            }
            $this->info("Contracts with Programs: {$contractsWithPrograms}");
            $this->warn("Contracts without Programs: {$contractsWithoutPrograms}");

            // Date range analysis
            $withDates = $legacyContracts->whereNotNull('start_date')->whereNotNull('end_date')->count();
            $this->info("Contracts with Start/End Dates: {$withDates}");

            // Already migrated check
            $alreadyMigrated = Contract::whereNotNull('legacy_id')->count();
            $this->warn("Already Migrated in CAP TRACK: {$alreadyMigrated}");

            $this->newLine();
        }

        $this->newLine();
        $this->info('Starting migration...');
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyContracts->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($legacyContracts as $legacyContract) {
            try {
                // Check if already migrated
                $exists = Contract::where('legacy_id', $legacyContract->id)->first();

                if ($exists) {
                    $skipped++;
                    $this->logValidation('contracts', $legacyContract->id, $exists->id, 'skipped', ['reason' => 'Already migrated']);
                    $bar->advance();

                    continue;
                }

                // Map funder
                $funderId = null;
                if ($legacyContract->funder_id) {
                    $funder = Funder::where('legacy_id', $legacyContract->funder_id)->first();
                    $funderId = $funder ? $funder->id : null;

                    if (! $funder) {
                        $this->logValidation('contracts', $legacyContract->id, null, 'warning', [
                            'message' => 'Funder not found',
                            'legacy_funder_id' => $legacyContract->funder_id,
                        ]);
                    }
                }

                // Map program (get first from pivot table)
                $programId = null;
                $legacyProgramIds = DB::connection('legacy_contracts')
                    ->table('contract_program')
                    ->where('contract_id', $legacyContract->id)
                    ->pluck('program_id');

                if ($legacyProgramIds->isNotEmpty()) {
                    $firstLegacyProgramId = $legacyProgramIds->first();
                    $program = Program::where('legacy_id', $firstLegacyProgramId)->first();
                    $programId = $program ? $program->id : null;
                }

                // Convert archive status: 'yes' -> true, NULL/other -> false
                $archived = ($legacyContract->archived === 'yes');

                // Convert date_archived from DATE to DATETIME
                $dateArchived = $legacyContract->date_archived
                    ? \Carbon\Carbon::parse($legacyContract->date_archived)->startOfDay()
                    : null;

                if (! $this->option('dry-run')) {
                    $contract = Contract::create([
                        'title' => $legacyContract->title,
                        'contract_number' => $legacyContract->contract_number,
                        'cfda_number' => $legacyContract->cfda_number,
                        'purpose' => $legacyContract->purpose,
                        'program_id' => $programId,
                        'funder_id' => $funderId,
                        'date_of_award' => $legacyContract->date_of_award,
                        'amount' => $legacyContract->amount,
                        'start_date' => $legacyContract->start_date,
                        'end_date' => $legacyContract->end_date,
                        'grant_code' => $legacyContract->grant_code,
                        'notes' => $legacyContract->notes,
                        'archived' => $archived,
                        'date_archived' => $dateArchived,
                        'created_at' => $legacyContract->created_at,
                        'updated_at' => $legacyContract->updated_at,
                        'deleted_at' => $legacyContract->deleted_at,
                        'created_by' => null,
                        'updated_by' => null,
                        'legacy_id' => $legacyContract->id,
                        'last_synced_at' => now(),
                    ]);

                    // Sync many-to-many program relationships
                    if ($legacyProgramIds->isNotEmpty()) {
                        $newProgramIds = Program::whereIn('legacy_id', $legacyProgramIds->toArray())
                            ->pluck('id')
                            ->toArray();

                        if (! empty($newProgramIds)) {
                            $contract->programs()->sync($newProgramIds);
                        }
                    }

                    $this->logValidation('contracts', $legacyContract->id, $contract->id, 'success');
                }

                $migrated++;
            } catch (\Exception $e) {
                $errors++;
                $this->logValidation('contracts', $legacyContract->id, null, 'error', [
                    'message' => $e->getMessage(),
                    'contract_title' => $legacyContract->title ?? 'unknown',
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
        $this->info("Total Legacy Contracts: {$legacyContracts->count()}");
        $this->info("Migrated: {$migrated}");
        $this->info("Skipped (already migrated): {$skipped}");
        $this->error("Errors: {$errors}");

        // Additional stats
        if (! $this->option('dry-run') && $migrated > 0) {
            $archivedCount = Contract::where('archived', true)->where('legacy_id', '!=', null)->count();
            $this->info("Archived contracts: {$archivedCount}");
        }

        if ($errors > 0) {
            $this->newLine();
            $this->warn('Check migration_validation_log table for error details');
        }

        $this->newLine();
        $this->info('✓ Contracts migration completed');

        return $errors > 0 ? 1 : 0;
    }

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
