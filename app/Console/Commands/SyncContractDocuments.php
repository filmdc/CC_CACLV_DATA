<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SyncContractDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:sync-documents {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orphaned contract documents from storage to media library';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Scanning for contract documents...');
        
        $contracts = Contract::all();
        $syncedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($contracts as $contract) {
            $this->info("Processing contract {$contract->id}: {$contract->title}");
            
            $storageDir = storage_path("app/public/{$contract->id}");
            
            if (!is_dir($storageDir)) {
                $this->line("  No storage directory found");
                $skippedCount++;
                continue;
            }

            $files = array_diff(scandir($storageDir), ['.', '..']);
            
            if (empty($files)) {
                $this->line("  Storage directory is empty");
                $skippedCount++;
                continue;
            }

            foreach ($files as $fileName) {
                $filePath = $storageDir . '/' . $fileName;
                
                if (!is_file($filePath)) {
                    continue;
                }

                // Check if this file is already in media library
                $existingMedia = $contract->getMedia('documents')
                    ->where('file_name', $fileName)
                    ->first();

                if ($existingMedia) {
                    $this->line("  File {$fileName} already exists in media library");
                    continue;
                }

                try {
                    if (!$dryRun) {
                        // Get file info
                        $fileSize = filesize($filePath);
                        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
                        
                        // Create media record manually
                        $media = new Media([
                            'model_type' => Contract::class,
                            'model_id' => $contract->id,
                            'collection_name' => 'documents',
                            'name' => pathinfo($fileName, PATHINFO_FILENAME),
                            'file_name' => $fileName,
                            'mime_type' => $mimeType,
                            'disk' => 'public',
                            'size' => $fileSize,
                            'manipulations' => [],
                            'custom_properties' => [],
                            'generated_conversions' => [],
                            'responsive_images' => [],
                        ]);
                        
                        $media->save();
                        
                        $this->info("  ✓ Added {$fileName} to media library");
                        $syncedCount++;
                    } else {
                        $this->info("  [DRY RUN] Would add {$fileName} to media library");
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to add {$fileName}: " . $e->getMessage());
                    $errorCount++;
                }
            }
        }

        $this->newLine();
        $this->info("Sync completed!");
        $this->table(['Status', 'Count'], [
            ['Synced', $syncedCount],
            ['Skipped', $skippedCount], 
            ['Errors', $errorCount],
        ]);

        if ($dryRun) {
            $this->info('This was a DRY RUN - no actual changes were made');
            $this->info('Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
