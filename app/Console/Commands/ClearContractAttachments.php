<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ClearContractAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:clear-attachments {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all contract media records from the database (files remain in storage)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        // Get count before deletion
        $totalMedia = Media::where('model_type', 'App\\Models\\Contract')->count();
        
        if ($totalMedia === 0) {
            $this->info('No contract media records found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalMedia} contract media records to remove");
        
        if (!$dryRun) {
            if (!$this->confirm('This will remove ALL contract attachment records from the database (files will remain in storage). Continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        if (!$dryRun) {
            $deleted = Media::where('model_type', 'App\\Models\\Contract')->delete();
            $this->info("Successfully removed {$deleted} contract media records.");
        } else {
            $this->info("[DRY RUN] Would remove {$totalMedia} contract media records.");
        }
        
        $this->info('Note: Physical files remain in storage for re-syncing.');
        
        return Command::SUCCESS;
    }
}
