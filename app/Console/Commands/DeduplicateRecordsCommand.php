<?php

namespace App\Console\Commands;

use App\Models\Funder;
use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'legacy:deduplicate 
                            {--type=all : Type to deduplicate (all|funders|programs)}
                            {--dry-run : Preview operations without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Remove duplicate funders and programs by merging legacy synced records with existing ones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info("🔧 Starting deduplication process for: {$type}");
        $this->newLine();

        try {
            switch ($type) {
                case 'funders':
                    $this->deduplicateFunders($dryRun);
                    break;

                case 'programs':
                    $this->deduplicatePrograms($dryRun);
                    break;

                case 'all':
                    $this->deduplicateFunders($dryRun);
                    $this->newLine();
                    $this->deduplicatePrograms($dryRun);
                    break;

                default:
                    $this->error("❌ Unknown type: {$type}");
                    return 1;
            }

            $this->newLine();
            $this->info('✅ Deduplication completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Deduplication failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Deduplicate funders
     */
    private function deduplicateFunders(bool $dryRun): void
    {
        $this->info('💰 Analyzing funder duplicates...');

        // Find duplicate funder names
        $duplicateNames = Funder::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        if ($duplicateNames->isEmpty()) {
            $this->info('No duplicate funders found.');
            return;
        }

        $this->table(['Funder Name', 'Count', 'Action'], 
            $duplicateNames->map(function ($name) use ($dryRun) {
                $duplicates = Funder::where('name', $name)->orderBy('id')->get();
                $action = $dryRun ? 'Would merge' : 'Merging';
                return [$name, $duplicates->count(), $action];
            })->toArray()
        );

        if (!$dryRun) {
            foreach ($duplicateNames as $name) {
                $this->mergeDuplicateFunders($name);
            }
        }
    }

    /**
     * Deduplicate programs
     */
    private function deduplicatePrograms(bool $dryRun): void
    {
        $this->info('📂 Analyzing program duplicates...');

        // Find duplicate program names
        $duplicateNames = Program::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        if ($duplicateNames->isEmpty()) {
            $this->info('No duplicate programs found.');
            return;
        }

        $this->table(['Program Name', 'Count', 'Action'], 
            $duplicateNames->map(function ($name) use ($dryRun) {
                $duplicates = Program::where('name', $name)->orderBy('id')->get();
                $action = $dryRun ? 'Would merge' : 'Merging';
                return [$name, $duplicates->count(), $action];
            })->toArray()
        );

        if (!$dryRun) {
            foreach ($duplicateNames as $name) {
                $this->mergeDuplicatePrograms($name);
            }
        }
    }

    /**
     * Merge duplicate funders
     */
    private function mergeDuplicateFunders(string $name): void
    {
        $duplicates = Funder::where('name', $name)->orderBy('id')->get();
        $primary = $duplicates->first(); // Keep the oldest one
        $toDelete = $duplicates->slice(1);

        DB::transaction(function () use ($primary, $toDelete) {
            foreach ($toDelete as $duplicate) {
                // Store legacy_id before clearing it
                $legacyIdToKeep = $duplicate->legacy_id;
                
                // Update contracts that reference the duplicate
                DB::table('contracts')
                    ->where('funder_id', $duplicate->id)
                    ->update(['funder_id' => $primary->id]);

                // Clear legacy_id from duplicate to avoid constraint issues
                $duplicate->update(['legacy_id' => null]);

                // Delete the duplicate
                $duplicate->delete();

                // Update primary with legacy_id if it didn't have one
                if (!$primary->legacy_id && $legacyIdToKeep) {
                    $primary->update(['legacy_id' => $legacyIdToKeep]);
                }
            }
        });

        $this->line("✅ Merged {$toDelete->count()} duplicate funders into: {$name}");
    }

    /**
     * Merge duplicate programs
     */
    private function mergeDuplicatePrograms(string $name): void
    {
        $duplicates = Program::where('name', $name)->orderBy('id')->get();
        $primary = $duplicates->first(); // Keep the oldest one
        $toDelete = $duplicates->slice(1);

        DB::transaction(function () use ($primary, $toDelete) {
            foreach ($toDelete as $duplicate) {
                // Update contracts that reference the duplicate
                DB::table('contracts')
                    ->where('program_id', $duplicate->id)
                    ->update(['program_id' => $primary->id]);

                // Update contract-program pivot relationships
                DB::table('contract_program')
                    ->where('program_id', $duplicate->id)
                    ->update(['program_id' => $primary->id]);

                // Update other relationships (services, work plans, etc.)
                DB::table('services')->where('program_id', $duplicate->id)
                    ->update(['program_id' => $primary->id]);
                DB::table('work_plans')->where('program_id', $duplicate->id)
                    ->update(['program_id' => $primary->id]);
                DB::table('program_enrollments')->where('program_id', $duplicate->id)
                    ->update(['program_id' => $primary->id]);

                // If the primary doesn't have a legacy_id but the duplicate does, transfer it
                $legacyIdToKeep = null;
                if (!$primary->legacy_id && $duplicate->legacy_id) {
                    $legacyIdToKeep = $duplicate->legacy_id;
                }

                // Delete the duplicate first to avoid unique constraint issues
                $duplicate->delete();

                // Update primary with legacy_id after duplicate is removed
                if ($legacyIdToKeep) {
                    $primary->update(['legacy_id' => $legacyIdToKeep]);
                }
            }
        });

        $this->line("✅ Merged {$toDelete->count()} duplicate programs into: {$name}");
    }
}