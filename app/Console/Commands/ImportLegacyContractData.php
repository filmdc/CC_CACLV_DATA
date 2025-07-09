<?php

namespace App\Console\Commands;

use App\Services\ContractDataMappingService;
use App\Services\LegacyContractImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportLegacyContractData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'contract:import-legacy-data 
                          {--dry-run : Run without making any database changes}
                          {--entity= : Import specific entity only (users,funders,contracts,report_due_dates,due_date_assignments)}
                          {--validate-mappings : Only validate program mappings without importing}
                          {--show-suggestions : Show program mapping suggestions}
                          {--config-check : Check configuration and files}';

    /**
     * The console command description.
     */
    protected $description = 'Import data from legacy contract database system into CAP TRACK';

    protected LegacyContractImportService $importService;
    protected ContractDataMappingService $mappingService;

    public function __construct(
        LegacyContractImportService $importService,
        ContractDataMappingService $mappingService
    ) {
        parent::__construct();
        $this->importService = $importService;
        $this->mappingService = $mappingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Legacy Contract Database Import Tool');
        $this->newLine();

        try {
            // Check configuration first
            if ($this->option('config-check')) {
                return $this->checkConfiguration();
            }

            // Validate mappings only
            if ($this->option('validate-mappings')) {
                return $this->validateMappings();
            }

            // Show mapping suggestions
            if ($this->option('show-suggestions')) {
                return $this->showMappingSuggestions();
            }

            // Set dry run mode
            $dryRun = $this->option('dry-run');
            $this->importService->setDryRun($dryRun);

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made to the database');
                $this->newLine();
            }

            // Confirm before proceeding
            if (!$dryRun && !$this->confirm('This will import data into your database. Continue?')) {
                $this->info('Import cancelled.');
                return 0;
            }

            // Run the import
            $this->info('Starting import process...');
            $this->newLine();

            $result = $this->importService->import();

            if ($result['success']) {
                $this->displayImportResults($result);
                return 0;
            } else {
                $this->error('Import failed: ' . $result['error']);
                $this->displayImportResults($result);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check configuration and file availability
     */
    protected function checkConfiguration(): int
    {
        $this->info('Checking import configuration...');
        $this->newLine();

        $config = config('legacy-import');
        
        // Check file availability
        $this->info('📁 Checking import files:');
        $missingFiles = [];
        $foundFiles = [];

        foreach ($config['import_files'] as $entity => $filename) {
            $jsonPath = "import/{$filename}";
            $csvPath = "import/{$config['csv_files'][$entity]}";
            
            if (Storage::exists($jsonPath)) {
                $this->line("  ✅ {$filename} (JSON)");
                $foundFiles[] = $entity;
            } elseif (Storage::exists($csvPath)) {
                $this->line("  ✅ {$config['csv_files'][$entity]} (CSV)");
                $foundFiles[] = $entity;
            } else {
                $this->line("  ❌ {$filename} (missing)");
                $missingFiles[] = $entity;
            }
        }

        $this->newLine();

        // Check program mappings
        $this->info('🗂️  Program mapping configuration:');
        $programMappings = $config['program_mapping'] ?? [];
        
        if (empty($programMappings)) {
            $this->warn('  ⚠️  No program mappings configured');
        } else {
            $mappingCount = count($programMappings);
            $this->line("  📊 {$mappingCount} program mappings configured");
        }

        if ($config['default_program_id']) {
            $this->line("  🎯 Default program ID: {$config['default_program_id']}");
        } else {
            $this->warn('  ⚠️  No default program ID configured');
        }

        $this->newLine();

        // Check available programs
        $availablePrograms = $this->mappingService->getAvailablePrograms();
        $this->info("🏢 Available programs in system: " . count($availablePrograms));
        
        foreach ($availablePrograms as $program) {
            $contractOnly = $program['contract_only'] ? ' (Contract Only)' : '';
            $this->line("  • {$program['name']} (ID: {$program['id']}){$contractOnly}");
        }

        $this->newLine();

        if (!empty($missingFiles)) {
            $this->error('❌ Missing files: ' . implode(', ', $missingFiles));
            $this->info('💡 Use the provided mysql-export-script.sql to generate these files');
            return 1;
        }

        $this->info('✅ Configuration check completed successfully');
        return 0;
    }

    /**
     * Validate program mappings
     */
    protected function validateMappings(): int
    {
        $this->info('Validating program mappings...');
        $this->newLine();

        try {
            // Load contract-program relationships
            $importData = $this->loadContractProgramData();
            $oldProgramNames = collect($importData)->pluck('program_name')->unique()->values()->toArray();

            if (empty($oldProgramNames)) {
                $this->warn('No program data found to validate');
                return 0;
            }

            $validation = $this->mappingService->validateProgramMappings($oldProgramNames);
            $stats = $this->mappingService->getMappingStatistics($oldProgramNames);

            $this->info("📊 Mapping Statistics:");
            $this->line("  Total programs: {$stats['total_programs']}");
            $this->line("  Mapped: {$stats['mapped_count']}");
            $this->line("  Using default: {$stats['default_count']}");
            $this->line("  Unmapped: {$stats['unmapped_count']}");
            $this->line("  Success rate: {$stats['mapping_percentage']}%");
            $this->newLine();

            if (!empty($validation['valid'])) {
                $this->info('✅ Successfully mapped programs:');
                foreach ($validation['valid'] as $programName) {
                    $mappedId = $this->mappingService->mapProgram($programName);
                    $this->line("  • {$programName} → Program ID {$mappedId}");
                }
                $this->newLine();
            }

            if (!empty($validation['using_default'])) {
                $this->warn('⚠️  Programs using default mapping:');
                foreach ($validation['using_default'] as $programName) {
                    $this->line("  • {$programName}");
                }
                $this->newLine();
            }

            if (!empty($validation['invalid'])) {
                $this->error('❌ Unmapped programs:');
                foreach ($validation['invalid'] as $programName) {
                    $this->line("  • {$programName}");
                }
                $this->newLine();
                $this->info('💡 Update config/legacy-import.php program_mapping to resolve these');
                return 1;
            }

            $this->info('✅ All programs are properly mapped');
            return 0;

        } catch (\Exception $e) {
            $this->error('Validation failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show program mapping suggestions
     */
    protected function showMappingSuggestions(): int
    {
        $this->info('Generating program mapping suggestions...');
        $this->newLine();

        try {
            $importData = $this->loadContractProgramData();
            $oldProgramNames = collect($importData)->pluck('program_name')->unique()->values()->toArray();

            if (empty($oldProgramNames)) {
                $this->warn('No program data found');
                return 0;
            }

            $suggestions = $this->mappingService->generateProgramMappingSuggestions($oldProgramNames);

            if (empty($suggestions)) {
                $this->info('No suggestions needed - all programs are mapped');
                return 0;
            }

            $this->info('💡 Program mapping suggestions:');
            $this->newLine();

            foreach ($suggestions as $oldProgramName => $matches) {
                $this->line("Old program: <comment>{$oldProgramName}</comment>");
                
                if (empty($matches)) {
                    $this->line("  No similar programs found");
                } else {
                    $this->line("  Suggestions:");
                    foreach ($matches as $match) {
                        $similarity = round($match['similarity'], 1);
                        $program = $match['program'];
                        $contractOnly = $program['contract_only'] ? ' (Contract Only)' : '';
                        $this->line("    • {$program['name']} (ID: {$program['id']}) - {$similarity}% similarity{$contractOnly}");
                    }
                }
                
                $this->newLine();
            }

            $this->info('Add these mappings to config/legacy-import.php:');
            $this->newLine();
            $this->line("'program_mapping' => [");
            
            foreach ($suggestions as $oldProgramName => $matches) {
                if (!empty($matches)) {
                    $bestMatch = $matches[0]['program'];
                    $this->line("    '{$oldProgramName}' => {$bestMatch['id']}, // {$bestMatch['name']}");
                } else {
                    $this->line("    '{$oldProgramName}' => null, // No good match found");
                }
            }
            
            $this->line("],");

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to generate suggestions: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Load contract-program relationship data
     */
    protected function loadContractProgramData(): array
    {
        $config = config('legacy-import');
        $filename = $config['import_files']['contract_programs'];
        $csvFilename = $config['csv_files']['contract_programs'];
        
        $jsonPath = "import/{$filename}";
        $csvPath = "import/{$csvFilename}";

        if (Storage::exists($jsonPath)) {
            $content = Storage::get($jsonPath);
            return json_decode($content, true) ?: [];
        } elseif (Storage::exists($csvPath)) {
            return $this->loadCsvData($csvPath);
        }

        throw new \Exception("Contract-program relationship file not found");
    }

    /**
     * Load CSV data
     */
    protected function loadCsvData(string $path): array
    {
        $content = Storage::get($path);
        $lines = explode("\n", trim($content));
        
        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $data = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $data[] = array_combine($headers, $values);
            }
        }

        return $data;
    }

    /**
     * Display import results
     */
    protected function displayImportResults(array $result): void
    {
        $this->newLine();
        
        if ($result['success']) {
            $this->info('✅ Import completed successfully!');
        } else {
            $this->error('❌ Import failed');
        }
        
        $this->newLine();
        $this->info('📊 Import Statistics:');
        
        foreach ($result['stats'] as $entity => $stats) {
            $this->line("  {$entity}:");
            $this->line("    Processed: {$stats['processed']}");
            $this->line("    Created: {$stats['created']}");
            $this->line("    Updated: {$stats['updated']}");
            $this->line("    Skipped: {$stats['skipped']}");
            $this->line("    Failed: {$stats['failed']}");
        }

        if (!empty($result['warnings'])) {
            $this->newLine();
            $warningCount = count($result['warnings']);
            $this->warn("⚠️  Warnings ({$warningCount}):");
            foreach ($result['warnings'] as $warning) {
                $this->line("  • {$warning}");
            }
        }

        if (!empty($result['errors'])) {
            $this->newLine();
            $errorCount = count($result['errors']);
            $this->error("❌ Errors ({$errorCount}):");
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->line("  • {$error}");
            }
            
            if (count($result['errors']) > 10) {
                $this->line("  ... and " . (count($result['errors']) - 10) . " more errors");
                $this->line("  Check storage/logs/legacy-import.log for full details");
            }
        }

        $this->newLine();
    }
}