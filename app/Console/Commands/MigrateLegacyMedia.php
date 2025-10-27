<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateLegacyMedia extends Command
{
    protected $signature = 'migrate:legacy-media {--dry-run : Run without actually migrating data} {--csv : Generate CSV report} {--type= : Filter by type: contracts, due-dates, reports, ck-media, or all}';
    protected $description = 'Migrate media files from legacy Contract Database system';

    protected $migrationTempPath;
    protected $modelTypeMap = [
        'App\Models\Contract' => Contract::class,
        'App\Models\Report' => ContractReport::class,
        'App\Models\ReportDueDate' => ContractReportDueDate::class,
    ];

    public function handle()
    {
        $this->migrationTempPath = storage_path('app/public/migration_temp');

        $this->info('==============================================');
        $this->info('  Legacy Contract Database - Media Migration');
        $this->info('==============================================');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be modified');
            $this->newLine();
        }

        // Check migration_temp exists
        if (!File::exists($this->migrationTempPath)) {
            $this->error('Migration temp directory not found: ' . $this->migrationTempPath);
            $this->warn('Please ensure files have been transferred from legacy server.');
            return 1;
        }

        $this->info('Connecting to legacy database at 10.55.0.10...');

        // Get type filter
        $type = $this->option('type');
        if ($type && !in_array($type, ['contracts', 'due-dates', 'reports', 'ck-media', 'all'])) {
            $this->error("Invalid type: {$type}");
            $this->warn("Valid types: contracts, due-dates, reports, ck-media, all");
            return 1;
        }

        try {
            $query = DB::connection('legacy_contracts')->table('media');

            // Apply type filter
            if ($type && $type !== 'all') {
                $query = $this->applyTypeFilter($query, $type);
            }

            $legacyMedia = $query->get();

            $typeLabel = $type ? " ({$type})" : " (all types)";
            $this->info("Found {$legacyMedia->count()} media records in legacy system{$typeLabel}");
        } catch (\Exception $e) {
            $this->error('Failed to connect to legacy database: ' . $e->getMessage());
            return 1;
        }

        // Dry-run analysis
        if ($this->option('dry-run')) {
            $this->performDryRunAnalysis($legacyMedia);
        }

        // Generate CSV if requested
        if ($this->option('csv')) {
            $this->info('Generating CSV report...');
            $csvPath = $this->generateCsvReport($legacyMedia);
            $this->info("CSV report saved to: {$csvPath}");
            $this->newLine();
        }

        if ($this->option('dry-run') || $this->option('csv')) {
            return 0;
        }

        $this->newLine();
        $this->info('Starting migration...');
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyMedia->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        $skippedNoModel = 0;
        $skippedNoFiles = 0;
        $skippedDuplicate = 0;

        foreach ($legacyMedia as $legacyMediaItem) {
            try {
                // Check if already migrated
                $exists = Media::where('uuid', $legacyMediaItem->uuid)->first();
                if ($exists) {
                    $skippedDuplicate++;
                    $bar->advance();
                    continue;
                }

                // Map to new model
                $modelResult = $this->findNewModel($legacyMediaItem);

                if (!$modelResult['found']) {
                    $skippedNoModel++;
                    $this->logValidation('media', $legacyMediaItem->id, null, 'skipped', [
                        'reason' => $modelResult['reason'],
                        'model_type' => $legacyMediaItem->model_type,
                        'legacy_model_id' => $legacyMediaItem->model_id,
                    ]);
                    $bar->advance();
                    continue;
                }

                $newModel = $modelResult['model'];

                // Check if files exist
                $sourceDir = $this->migrationTempPath . '/' . $legacyMediaItem->id;
                if (!File::exists($sourceDir) || !File::exists($sourceDir . '/' . $legacyMediaItem->file_name)) {
                    $skippedNoFiles++;
                    $this->logValidation('media', $legacyMediaItem->id, null, 'skipped', [
                        'reason' => 'File not found in migration_temp',
                        'expected_path' => $sourceDir . '/' . $legacyMediaItem->file_name,
                    ]);
                    $bar->advance();
                    continue;
                }

                // Add media to model using Spatie Media Library
                $media = $newModel
                    ->addMedia($sourceDir . '/' . $legacyMediaItem->file_name)
                    ->withCustomProperties(json_decode($legacyMediaItem->custom_properties, true) ?: [])
                    ->preservingOriginal() // Keep files in migration_temp
                    ->usingName($legacyMediaItem->name)
                    ->usingFileName($legacyMediaItem->file_name)
                    ->toMediaCollection($legacyMediaItem->collection_name);

                // Update UUID to match legacy
                $media->uuid = $legacyMediaItem->uuid;
                $media->created_at = $legacyMediaItem->created_at;
                $media->updated_at = $legacyMediaItem->updated_at;
                $media->order_column = $legacyMediaItem->order_column;
                $media->save();

                $this->logValidation('media', $legacyMediaItem->id, $media->id, 'success', [
                    'model_type' => get_class($newModel),
                    'model_id' => $newModel->id,
                ]);

                $migrated++;
            } catch (\Exception $e) {
                $errors++;
                $this->logValidation('media', $legacyMediaItem->id, null, 'error', [
                    'message' => $e->getMessage(),
                    'file_name' => $legacyMediaItem->file_name ?? 'unknown',
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
        $this->info("Total Legacy Media: {$legacyMedia->count()}");
        $this->info("Migrated: {$migrated}");
        $this->warn("Skipped (no model): {$skippedNoModel}");
        $this->warn("Skipped (no files): {$skippedNoFiles}");
        $this->warn("Skipped (duplicate): {$skippedDuplicate}");
        $this->error("Errors: {$errors}");

        if ($errors > 0 || $skippedNoModel > 0 || $skippedNoFiles > 0) {
            $this->newLine();
            $this->warn('Check migration_validation_log table for details');
        }

        $this->newLine();
        $this->info('✓ Media migration completed');

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Apply type filter to query
     */
    protected function applyTypeFilter($query, string $type)
    {
        switch ($type) {
            case 'contracts':
                // Contract documents (excluding ck-media)
                return $query->where('model_type', 'App\Models\Contract')
                    ->where('collection_name', '!=', 'ck-media');

            case 'due-dates':
                // Report Due Date documents (excluding ck-media)
                return $query->where('model_type', 'App\Models\ReportDueDate')
                    ->where('collection_name', '!=', 'ck-media');

            case 'reports':
                // Report submissions
                return $query->where('model_type', 'App\Models\Report');

            case 'ck-media':
                // CKEditor inline media (any model type)
                return $query->where('collection_name', 'ck-media');

            default:
                return $query;
        }
    }

    /**
     * Find the new model corresponding to legacy media
     */
    protected function findNewModel($legacyMediaItem): array
    {
        // Handle orphaned ck-media (model_id = 0)
        if ($legacyMediaItem->model_id == 0) {
            return [
                'found' => false,
                'reason' => 'Orphaned ck-media (model_id = 0)',
            ];
        }

        // Map model type
        $newModelClass = $this->modelTypeMap[$legacyMediaItem->model_type] ?? null;

        if (!$newModelClass) {
            return [
                'found' => false,
                'reason' => 'Unknown model type: ' . $legacyMediaItem->model_type,
            ];
        }

        // Find new model by legacy_id
        $query = $newModelClass::where('legacy_id', $legacyMediaItem->model_id);

        // For ContractReport, check metadata->legacy_report_id instead
        if ($newModelClass === ContractReport::class) {
            $query = $newModelClass::where('metadata->legacy_report_id', $legacyMediaItem->model_id);
        }

        $newModel = $query->first();

        if (!$newModel) {
            return [
                'found' => false,
                'reason' => 'Model not found in new system',
            ];
        }

        return [
            'found' => true,
            'model' => $newModel,
        ];
    }

    /**
     * Perform dry-run analysis
     */
    protected function performDryRunAnalysis($legacyMedia)
    {
        $this->newLine();
        $this->info('==============================================');
        $this->info('  DRY RUN ANALYSIS');
        $this->info('==============================================');

        // Breakdown by model type
        $byType = $legacyMedia->groupBy('model_type');
        $this->info('Media by Type:');
        foreach ($byType as $type => $items) {
            $this->line("  {$type}: {$items->count()}");
        }

        // Breakdown by collection
        $this->newLine();
        $byCollection = $legacyMedia->groupBy('collection_name');
        $this->info('Media by Collection:');
        foreach ($byCollection as $collection => $items) {
            $this->line("  {$collection}: {$items->count()}");
        }

        // Check model availability
        $this->newLine();
        $this->info('Model Availability Check:');

        $withModel = 0;
        $withoutModel = 0;
        $orphaned = 0;

        foreach ($legacyMedia as $item) {
            if ($item->model_id == 0) {
                $orphaned++;
                continue;
            }

            $result = $this->findNewModel($item);
            if ($result['found']) {
                $withModel++;
            } else {
                $withoutModel++;
            }
        }

        $this->info("  With Valid Model: {$withModel}");
        $this->warn("  Without Model (will skip): {$withoutModel}");
        $this->warn("  Orphaned (model_id = 0): {$orphaned}");

        // Check file availability and validation
        $this->newLine();
        $this->info('File Availability and Validation:');

        $filesExist = 0;
        $filesMissing = 0;
        $filesTooLarge = 0;
        $invalidMimeType = 0;
        $maxFileSizeMB = 10; // Spatie Media Library default

        $largeFiles = [];
        $invalidMimeFiles = [];

        foreach ($legacyMedia as $item) {
            $sourceFile = $this->migrationTempPath . '/' . $item->id . '/' . $item->file_name;

            if (!File::exists($sourceFile)) {
                $filesMissing++;
                continue;
            }

            $filesExist++;

            // Check file size
            $fileSizeMB = File::size($sourceFile) / 1024 / 1024;
            if ($fileSizeMB > $maxFileSizeMB) {
                $filesTooLarge++;
                $largeFiles[] = [
                    'name' => $item->file_name,
                    'size' => round($fileSizeMB, 2),
                ];
            }

            // Check MIME type
            $mimeType = File::mimeType($sourceFile);
            $unsupportedMimes = [
                'message/rfc822',           // .eml files
                'application/zip',          // .zip archives
                'image/tiff',               // .tiff images
                'application/vnd.ms-outlook', // .msg Outlook files
                'application/vnd.ms-office', // .msg Office files (alternate)
                'application/x-empty',      // Empty/corrupted files
            ];
            if (in_array($mimeType, $unsupportedMimes)) {
                $invalidMimeType++;
                $invalidMimeFiles[] = [
                    'name' => $item->file_name,
                    'mime' => $mimeType,
                ];
            }
        }

        $this->info("  Files Found: {$filesExist}");
        $this->warn("  Files Missing: {$filesMissing}");
        $this->warn("  Files Too Large (>{$maxFileSizeMB}MB): {$filesTooLarge}");
        $this->warn("  Files with Unsupported MIME Types: {$invalidMimeType}");

        // Show details for problematic files
        if ($filesTooLarge > 0) {
            $this->newLine();
            $this->warn("Large Files (will fail):");
            foreach (array_slice($largeFiles, 0, 5) as $file) {
                $this->line("  - {$file['name']} ({$file['size']} MB)");
            }
            if ($filesTooLarge > 5) {
                $this->line("  ... and " . ($filesTooLarge - 5) . " more");
            }
        }

        if ($invalidMimeType > 0) {
            $this->newLine();
            $this->warn("Files with Unsupported MIME Types (will fail):");
            foreach (array_slice($invalidMimeFiles, 0, 5) as $file) {
                $this->line("  - {$file['name']} ({$file['mime']})");
            }
            if ($invalidMimeType > 5) {
                $this->line("  ... and " . ($invalidMimeType - 5) . " more");
            }
        }

        // Already migrated
        $alreadyMigrated = Media::count();
        $this->newLine();
        $this->warn("Already Migrated in CAP TRACK: {$alreadyMigrated}");

        // Migration prediction
        $this->newLine();
        $this->info('==============================================');
        $this->info('  MIGRATION PREDICTION');
        $this->info('==============================================');
        $expectedSuccess = $filesExist - $filesTooLarge - $invalidMimeType;
        $expectedFail = $filesTooLarge + $invalidMimeType + $filesMissing;
        $this->info("Expected to Migrate: {$expectedSuccess}");
        $this->warn("Expected to Fail: {$expectedFail}");

        if ($expectedFail > 0) {
            $this->newLine();
            $this->warn("⚠ {$expectedFail} files will fail during migration!");
            $this->warn("Use --csv to generate detailed report for review.");
        }

        $this->newLine();
    }

    /**
     * Generate CSV report
     */
    protected function generateCsvReport($legacyMedia)
    {
        $filename = 'legacy_media_' . date('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/' . $filename);

        $handle = fopen($filepath, 'w');

        // Write CSV header
        fputcsv($handle, [
            'Legacy Media ID',
            'Legacy Model Type',
            'Legacy Model ID',
            'New Model Type',
            'New Model ID',
            'Model Found',
            'File Name',
            'Collection',
            'File Exists',
            'File Path',
            'Size (MB)',
            'Size Issue',
            'MIME Type',
            'MIME Issue',
            'Migration Status',
            'Skip Reason',
        ]);

        // Write data rows
        $maxFileSizeMB = 10;
        $unsupportedMimes = [
            'message/rfc822',           // .eml files
            'application/zip',          // .zip archives
            'image/tiff',               // .tiff images
            'application/vnd.ms-outlook', // .msg Outlook files
            'application/vnd.ms-office', // .msg Office files (alternate)
            'application/x-empty',      // Empty/corrupted files
        ];

        foreach ($legacyMedia as $item) {
            $modelResult = $this->findNewModel($item);
            $sourceFile = $this->migrationTempPath . '/' . $item->id . '/' . $item->file_name;
            $fileExists = File::exists($sourceFile);

            // File size and MIME validation
            $fileSizeMB = 0;
            $sizeIssue = 'N/A';
            $mimeType = 'N/A';
            $mimeIssue = 'N/A';

            if ($fileExists) {
                $fileSizeMB = round(File::size($sourceFile) / 1024 / 1024, 2);
                $sizeIssue = $fileSizeMB > $maxFileSizeMB ? "Too large (>{$maxFileSizeMB}MB)" : 'OK';

                $mimeType = File::mimeType($sourceFile);
                $mimeIssue = in_array($mimeType, $unsupportedMimes) ? 'Unsupported' : 'OK';
            }

            $status = 'Will Migrate';
            $skipReason = '';

            // Check for existing migration
            $alreadyMigrated = Media::where('uuid', $item->uuid)->exists();
            if ($alreadyMigrated) {
                $status = 'Skip';
                $skipReason = 'Already migrated';
            } elseif (!$modelResult['found']) {
                $status = 'Skip';
                $skipReason = $modelResult['reason'];
            } elseif (!$fileExists) {
                $status = 'Skip';
                $skipReason = 'File not found';
            } elseif ($sizeIssue !== 'OK') {
                $status = 'Will Fail';
                $skipReason = $sizeIssue;
            } elseif ($mimeIssue !== 'OK') {
                $status = 'Will Fail';
                $skipReason = 'Unsupported MIME type: ' . $mimeType;
            }

            fputcsv($handle, [
                $item->id,
                $item->model_type,
                $item->model_id,
                $modelResult['found'] ? get_class($modelResult['model']) : 'N/A',
                $modelResult['found'] ? $modelResult['model']->id : '',
                $modelResult['found'] ? 'Yes' : 'No',
                $item->file_name,
                $item->collection_name,
                $fileExists ? 'Yes' : 'No',
                $sourceFile,
                $fileSizeMB,
                $sizeIssue,
                $mimeType,
                $mimeIssue,
                $status,
                $skipReason,
            ]);
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
