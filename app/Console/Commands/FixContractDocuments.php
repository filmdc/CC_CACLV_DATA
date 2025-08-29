<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FixContractDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:fix-documents {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix contract document storage and media records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Fixing contract document organization...');
        
        // First, remove orphaned media records (records pointing to non-existent files)
        $this->cleanupOrphanedMedia($dryRun);
        
        // Then, scan storage directories and create proper media records
        $this->syncContractDocuments($dryRun);
        
        return Command::SUCCESS;
    }
    
    private function cleanupOrphanedMedia($dryRun)
    {
        $this->info('Step 1: Cleaning up orphaned media records...');
        
        $orphanedCount = 0;
        $media = Media::where('model_type', 'App\\Models\\Contract')->get();
        
        foreach ($media as $mediaRecord) {
            $expectedPath = $mediaRecord->getPath();
            $contractPath = storage_path("app/public/{$mediaRecord->model_id}/{$mediaRecord->file_name}");
            
            // Check if file exists in either expected location or contract directory
            if (!file_exists($expectedPath) && !file_exists($contractPath)) {
                $orphanedCount++;
                
                if (!$dryRun) {
                    $mediaRecord->delete();
                    $this->line("  ✓ Removed orphaned media record: {$mediaRecord->file_name} (ID: {$mediaRecord->id})");
                } else {
                    $this->line("  [DRY RUN] Would remove orphaned media record: {$mediaRecord->file_name} (ID: {$mediaRecord->id})");
                }
            }
        }
        
        $this->info("Found {$orphanedCount} orphaned media records");
    }
    
    private function syncContractDocuments($dryRun)
    {
        $this->info('Step 2: Syncing contract documents from storage...');
        
        $contracts = Contract::all();
        $syncedCount = 0;
        
        foreach ($contracts as $contract) {
            $this->line("Processing contract {$contract->id}: {$contract->title}");
            
            $storageDir = storage_path("app/public/{$contract->id}");
            
            if (!is_dir($storageDir)) {
                continue;
            }
            
            $files = array_diff(scandir($storageDir), ['.', '..']);
            
            foreach ($files as $fileName) {
                $filePath = $storageDir . '/' . $fileName;
                
                if (!is_file($filePath)) {
                    continue;
                }
                
                // Check if media record already exists for this file
                $existingMedia = $contract->getMedia('documents')
                    ->where('file_name', $fileName)
                    ->first();
                
                if ($existingMedia) {
                    $this->line("  File {$fileName} already has media record");
                    continue;
                }
                
                if (!$dryRun) {
                    // Add file to media library properly
                    try {
                        $mediaRecord = $contract->addMediaFromExisting($filePath)
                            ->usingName(pathinfo($fileName, PATHINFO_FILENAME))
                            ->usingFileName($fileName)
                            ->toMediaCollection('documents');
                        
                        $this->info("  ✓ Added {$fileName} to media library (ID: {$mediaRecord->id})");
                        $syncedCount++;
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed to add {$fileName}: " . $e->getMessage());
                    }
                } else {
                    $this->info("  [DRY RUN] Would add {$fileName} to media library");
                    $syncedCount++;
                }
            }
        }
        
        $this->info("Processed {$syncedCount} files");
    }
}
