<?php

namespace App\Services;

use App\Services\ParticipantExportService;
use App\Services\ParticipantImportService;
use App\Models\ImportAuditLog;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportExportService
{
    protected ParticipantExportService $exportService;
    protected ParticipantImportService $importService;

    public function __construct(
        ParticipantExportService $exportService,
        ParticipantImportService $importService
    ) {
        $this->exportService = $exportService;
        $this->importService = $importService;
    }

    /**
     * Export participants by various criteria
     */
    public function exportParticipants(array $criteria = []): array
    {
        try {
            $exportType = $criteria['type'] ?? 'all';
            $filename = $this->generateExportFilename($exportType, $criteria);
            
            switch ($exportType) {
                case 'participant':
                    if (empty($criteria['participant_id'])) {
                        throw new \InvalidArgumentException('Participant ID is required for participant export');
                    }
                    $csvContent = $this->exportService->exportParticipantById($criteria['participant_id']);
                    break;
                
                case 'household':
                    if (empty($criteria['household_id'])) {
                        throw new \InvalidArgumentException('Household ID is required for household export');
                    }
                    $csvContent = $this->exportService->exportHouseholdById($criteria['household_id']);
                    break;
                
                case 'date_range':
                    if (empty($criteria['start_date']) || empty($criteria['end_date'])) {
                        throw new \InvalidArgumentException('Start date and end date are required for date range export');
                    }
                    $startDate = Carbon::parse($criteria['start_date']);
                    $endDate = Carbon::parse($criteria['end_date']);
                    $field = $criteria['date_field'] ?? 'created_at';
                    $csvContent = $this->exportService->exportByDateRange($startDate, $endDate, $field);
                    break;
                
                case 'household_codes':
                    if (empty($criteria['household_codes'])) {
                        throw new \InvalidArgumentException('Household codes are required for household codes export');
                    }
                    $csvContent = $this->exportService->exportByHouseholdCodes($criteria['household_codes']);
                    break;
                
                case 'all':
                default:
                    $csvContent = $this->exportService->exportAll();
                    break;
            }
            
            // Store the file temporarily
            $filePath = $this->storeTemporaryFile($filename, $csvContent);
            
            Log::info('Export completed', [
                'type' => $exportType,
                'criteria' => $criteria,
                'filename' => $filename,
                'file_size' => strlen($csvContent)
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'file_size' => strlen($csvContent),
                'record_count' => $this->countRecordsInCsv($csvContent)
            ];
            
        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate and download import template
     */
    public function generateImportTemplate(): array
    {
        try {
            $templateContent = $this->exportService->generateTemplate();
            $filename = 'participant_import_template_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $filePath = $this->storeTemporaryFile($filename, $templateContent);
            
            // Count lines for record count (subtract 1 for header)
            $lineCount = substr_count($templateContent, "\n");
            $recordCount = max(0, $lineCount - 1);
            
            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'file_size' => strlen($templateContent),
                'record_count' => $recordCount
            ];
            
        } catch (\Exception $e) {
            Log::error('Template generation failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate uploaded import file
     */
    public function validateImportFile(UploadedFile $file, array $options = []): array
    {
        try {
            // Validate file type - accept various CSV MIME types
            $allowedMimeTypes = [
                'text/csv',
                'text/plain', 
                'application/csv',
                'application/excel',
                'application/vnd.ms-excel',
                'application/vnd.msexcel',
                'text/anytext',
                'text/comma-separated-values',
                'application/octet-stream' // Sometimes CSV files are detected as this
            ];
            
            $clientMimeType = $file->getClientMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Check both MIME type and file extension for CSV validation
            if (!in_array($clientMimeType, $allowedMimeTypes) && !in_array($extension, ['csv', 'txt'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid file type. Please upload a CSV file. Detected type: ' . $clientMimeType . ', Extension: ' . $extension
                ];
            }
            
            // Validate file size (10MB max)
            if ($file->getSize() > 10 * 1024 * 1024) {
                return [
                    'success' => false,
                    'error' => 'File too large. Maximum size is 10MB.'
                ];
            }
            
            // Store file temporarily - use a unique filename in the root private directory
            $uniqueFilename = 'import_' . uniqid() . '_' . time() . '.csv';
            $tempPath = $file->storeAs('', $uniqueFilename, 'local');
            $fullPath = Storage::disk('local')->path($tempPath);
            
            
            // Validate file content with optional reference data set
            $validation = $this->importService->validateFile($fullPath, $options);
            
            // Clean up temp file if validation failed
            if (!$validation['valid']) {
                Storage::disk('local')->delete($tempPath);
                
                return [
                    'success' => false,
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                    'record_count' => $validation['record_count']
                ];
            }
            
            Log::info('Import file validated', [
                'filename' => $file->getClientOriginalName(),
                'record_count' => $validation['record_count'],
                'household_count' => $validation['household_count']
            ]);
            
            return [
                'success' => true,
                'temp_file_path' => $tempPath,
                'validation' => $validation,
                'original_filename' => $file->getClientOriginalName()
            ];
            
        } catch (\Exception $e) {
            Log::error('Import file validation failed', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName()
            ]);
            
            return [
                'success' => false,
                'error' => 'File validation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Preview import data
     */
    public function previewImport(string $tempFilePath, int $limit = 10): array
    {
        try {
            $fullPath = Storage::disk('local')->path($tempFilePath);
            $preview = $this->importService->previewImport($fullPath, $limit);
            
            if (!$preview['success']) {
                return [
                    'success' => false,
                    'error' => $preview['error']
                ];
            }
            
            return [
                'success' => true,
                'preview_data' => $preview['preview'],
                'total_records' => $preview['total_records']
            ];
            
        } catch (\Exception $e) {
            Log::error('Import preview failed', [
                'error' => $e->getMessage(),
                'temp_file' => $tempFilePath
            ]);
            
            return [
                'success' => false,
                'error' => 'Preview generation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process import
     */
    public function processImport(string $tempFilePath, array $options = []): array
    {
        // Create audit log entry
        $auditLog = ImportAuditLog::logImport([
            'import_type' => 'participant',
            'file_name' => basename($tempFilePath),
            'file_size' => Storage::disk('local')->size($tempFilePath),
            'reference_data_set_id' => $options['reference_data_set_id'] ?? null,
            'options' => $options
        ]);
        
        try {
            $fullPath = Storage::disk('local')->path($tempFilePath);
            $results = $this->importService->processImport($fullPath, $options);
            
            // Update audit log with results
            $auditLog->markCompleted($results);
            
            // Clean up temp file
            Storage::disk('local')->delete($tempFilePath);
            
            if ($results['success']) {
                Log::info('Import completed successfully', [
                    'audit_log_id' => $auditLog->id,
                    'processed_households' => $results['processed_households'],
                    'processed_participants' => $results['processed_participants'],
                    'updated_households' => $results['updated_households'],
                    'updated_participants' => $results['updated_participants'],
                    'skipped_records' => $results['skipped_records']
                ]);
            } else {
                Log::error('Import failed', [
                    'audit_log_id' => $auditLog->id,
                    'error' => $results['error']
                ]);
            }
            
            return $results;
            
        } catch (\Exception $e) {
            // Update audit log with failure
            $auditLog->markCompleted([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            
            // Clean up temp file on error
            Storage::disk('local')->delete($tempFilePath);
            
            Log::error('Import processing failed', [
                'audit_log_id' => $auditLog->id,
                'error' => $e->getMessage(),
                'temp_file' => $tempFilePath
            ]);
            
            return [
                'success' => false,
                'error' => 'Import processing failed: ' . $e->getMessage(),
                'processed_households' => 0,
                'processed_participants' => 0,
                'updated_households' => 0,
                'updated_participants' => 0,
                'skipped_records' => 0,
                'errors' => []
            ];
        }
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        return [
            'total_participants' => \App\Models\Participant::count(),
            'total_households' => \App\Models\Household::count(),
            'participants_this_month' => \App\Models\Participant::whereMonth('created_at', now()->month)->count(),
            'households_this_month' => \App\Models\Household::whereMonth('created_at', now()->month)->count(),
            'participants_this_year' => \App\Models\Participant::whereYear('created_at', now()->year)->count(),
            'households_this_year' => \App\Models\Household::whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * Clean up old temporary files
     */
    public function cleanupTemporaryFiles(int $olderThanHours = 24): int
    {
        $cutoffTime = now()->subHours($olderThanHours);
        $cleaned = 0;
        
        // Clean export files
        $exportFiles = Storage::disk('public')->files('exports');
        foreach ($exportFiles as $file) {
            if (Storage::disk('public')->lastModified($file) < $cutoffTime->timestamp) {
                Storage::disk('public')->delete($file);
                $cleaned++;
            }
        }
        
        // Clean import temp files
        $tempFiles = Storage::disk('local')->files('temp-imports');
        foreach ($tempFiles as $file) {
            if (Storage::disk('local')->lastModified($file) < $cutoffTime->timestamp) {
                Storage::disk('local')->delete($file);
                $cleaned++;
            }
        }
        
        Log::info('Cleaned up temporary files', ['files_cleaned' => $cleaned]);
        
        return $cleaned;
    }

    protected function generateExportFilename(string $type, array $criteria): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        switch ($type) {
            case 'participant':
                return "participant_{$criteria['participant_id']}_export_{$timestamp}.csv";
            case 'household':
                return "household_{$criteria['household_id']}_export_{$timestamp}.csv";
            case 'date_range':
                $start = Carbon::parse($criteria['start_date'])->format('Y-m-d');
                $end = Carbon::parse($criteria['end_date'])->format('Y-m-d');
                return "participants_{$start}_to_{$end}_export_{$timestamp}.csv";
            case 'household_codes':
                return "households_by_codes_export_{$timestamp}.csv";
            default:
                return "all_participants_export_{$timestamp}.csv";
        }
    }

    protected function storeTemporaryFile(string $filename, string $content): string
    {
        // Store in local private storage for security
        $filePath = 'exports/' . $filename;
        Storage::disk('local')->put($filePath, $content);
        
        return $filePath;
    }


    protected function countRecordsInCsv(string $csvContent): int
    {
        $lines = explode("\n", $csvContent);
        // Subtract 1 for header row, and filter out empty lines
        return count(array_filter($lines, function($line) {
            return !empty(trim($line));
        })) - 1;
    }

    /**
     * Clean up old export files (older than 1 hour)
     */
    public function cleanupOldExports(): int
    {
        $cleaned = 0;
        $cutoffTime = now()->subHour();
        
        try {
            $files = Storage::disk('local')->files('exports');
            
            foreach ($files as $file) {
                $lastModified = Storage::disk('local')->lastModified($file);
                
                if ($lastModified && Carbon::createFromTimestamp($lastModified)->isBefore($cutoffTime)) {
                    Storage::disk('local')->delete($file);
                    $cleaned++;
                }
            }
            
            Log::info('Export cleanup completed', ['files_cleaned' => $cleaned]);
        } catch (\Exception $e) {
            Log::error('Export cleanup failed', ['error' => $e->getMessage()]);
        }
        
        return $cleaned;
    }
}