<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup-duplicates {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate media records for contracts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Finding duplicate contract media records...');
        
        // Find duplicates by grouping on model_type, model_id, collection_name, and file_name
        $duplicates = DB::select("
            SELECT model_type, model_id, collection_name, file_name, COUNT(*) as count
            FROM media 
            WHERE model_type = 'App\\\\Models\\\\Contract'
            GROUP BY model_type, model_id, collection_name, file_name
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ");

        if (empty($duplicates)) {
            $this->info('No duplicate media records found.');
            return Command::SUCCESS;
        }

        $this->info('Found ' . count($duplicates) . ' sets of duplicate files');
        
        $totalDuplicates = 0;
        $deletedCount = 0;

        foreach ($duplicates as $duplicate) {
            $this->line("Processing: {$duplicate->file_name} ({$duplicate->count} copies)");
            
            // Get all media records for this duplicate group
            $mediaRecords = Media::where('model_type', $duplicate->model_type)
                ->where('model_id', $duplicate->model_id)
                ->where('collection_name', $duplicate->collection_name)
                ->where('file_name', $duplicate->file_name)
                ->orderBy('created_at', 'asc') // Keep the oldest record
                ->get();

            if ($mediaRecords->count() <= 1) {
                continue; // Skip if somehow no duplicates
            }

            // Keep the first (oldest) record, delete the rest
            $toKeep = $mediaRecords->first();
            $toDelete = $mediaRecords->skip(1);

            $totalDuplicates += $toDelete->count();

            foreach ($toDelete as $media) {
                if (!$dryRun) {
                    try {
                        $media->delete();
                        $deletedCount++;
                        $this->line("  ✓ Deleted duplicate record ID {$media->id} (created {$media->created_at})");
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed to delete record ID {$media->id}: " . $e->getMessage());
                    }
                } else {
                    $this->line("  [DRY RUN] Would delete record ID {$media->id} (created {$media->created_at})");
                }
            }
            
            $this->line("  → Kept record ID {$toKeep->id} (created {$toKeep->created_at})");
        }

        $this->newLine();
        $this->info("Cleanup completed!");
        $this->table(['Status', 'Count'], [
            ['Total duplicate records found', $totalDuplicates],
            ['Records deleted', $deletedCount],
        ]);

        if ($dryRun) {
            $this->info('This was a DRY RUN - no actual changes were made');
            $this->info('Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
