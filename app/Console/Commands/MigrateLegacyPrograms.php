<?php

namespace App\Console\Commands;

use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyPrograms extends Command
{
    protected $signature = 'migrate:legacy-programs {--dry-run : Run without actually updating data} {--create-missing : Create programs that don\'t exist}';

    protected $description = 'Map legacy programs to CAP TRACK programs by name and set legacy_id';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Legacy Contract Database - Programs Mapping');
        $this->info('==============================================');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be modified');
            $this->newLine();
        }

        $this->info('Connecting to legacy database at 10.55.0.10...');

        try {
            $legacyPrograms = DB::connection('legacy_contracts')->table('programs')->get();
            $this->info("Found {$legacyPrograms->count()} programs in legacy system");
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database: '.$e->getMessage());

            return 1;
        }

        $currentPrograms = Program::all();
        $this->info("Current CAP TRACK programs: {$currentPrograms->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyPrograms->count());
        $bar->start();

        $mapped = 0;
        $created = 0;
        $notFound = 0;
        $mappedPrograms = [];
        $missingPrograms = [];
        $toBeCreatedPrograms = [];

        foreach ($legacyPrograms as $legacyProgram) {
            $existingProgram = Program::whereRaw('LOWER(name) = ?', [strtolower($legacyProgram->program_name)])->first();

            if ($existingProgram) {
                if (! $this->option('dry-run')) {
                    $existingProgram->update(['legacy_id' => $legacyProgram->id]);
                }
                $mapped++;
                $mappedPrograms[] = [
                    'legacy_id' => $legacyProgram->id,
                    'legacy_name' => $legacyProgram->program_name,
                    'current_id' => $existingProgram->id,
                    'current_name' => $existingProgram->name,
                ];
            } elseif ($this->option('create-missing')) {
                if (! $this->option('dry-run')) {
                    Program::create([
                        'name' => $legacyProgram->program_name,
                        'active' => true,
                        'legacy_id' => $legacyProgram->id,
                    ]);
                }
                $created++;
                $toBeCreatedPrograms[] = [
                    'legacy_id' => $legacyProgram->id,
                    'name' => $legacyProgram->program_name,
                ];
            } else {
                $notFound++;
                $missingPrograms[] = [
                    'legacy_id' => $legacyProgram->id,
                    'name' => $legacyProgram->program_name,
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Show detailed report in dry-run mode
        if ($this->option('dry-run')) {
            if (! empty($mappedPrograms)) {
                $this->info('==============================================');
                $this->info('  PROGRAMS THAT WILL BE MAPPED');
                $this->info('==============================================');
                foreach ($mappedPrograms as $program) {
                    $this->line("Legacy ID: {$program['legacy_id']} | Legacy Name: {$program['legacy_name']}");
                    $this->line("  → Maps to Current ID: {$program['current_id']} | Current Name: {$program['current_name']}");
                }
                $this->newLine();
            }

            if (! empty($toBeCreatedPrograms)) {
                $this->info('==============================================');
                $this->info('  PROGRAMS THAT WILL BE CREATED');
                $this->info('==============================================');
                foreach ($toBeCreatedPrograms as $program) {
                    $this->line("Legacy ID: {$program['legacy_id']} | Name: {$program['name']}");
                }
                $this->newLine();
            }

            if (! empty($missingPrograms)) {
                $this->info('==============================================');
                $this->info('  PROGRAMS NOT FOUND (WILL NOT BE MIGRATED)');
                $this->info('==============================================');
                foreach ($missingPrograms as $program) {
                    $this->line("Legacy ID: {$program['legacy_id']} | Name: {$program['name']}");
                }
                $this->newLine();
            }
        }

        $this->info('==============================================');
        $this->info("Total: {$legacyPrograms->count()} | Mapped: {$mapped} | Created: {$created} | Not Found: {$notFound}");
        $this->info('==============================================');

        if ($notFound > 0) {
            $this->warn("Use --create-missing to create {$notFound} missing programs");
        }

        return 0;
    }
}
