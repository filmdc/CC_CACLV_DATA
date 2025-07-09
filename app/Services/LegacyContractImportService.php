<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\Funder;
use App\Models\Program;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LegacyContractImportService
{
    protected ContractDataMappingService $mappingService;
    protected array $config;
    protected array $importStats = [];
    protected bool $dryRun = false;
    protected array $errors = [];
    protected array $warnings = [];
    protected array $dryRunFunderCache = [];
    protected array $dryRunContractCache = [];
    protected array $dryRunUserCache = [];
    protected array $dryRunReportDueDateCache = [];

    public function __construct(ContractDataMappingService $mappingService)
    {
        $this->mappingService = $mappingService;
        $this->config = config('legacy-import');
        $this->initializeStats();
    }

    /**
     * Set dry run mode
     */
    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Main import method
     */
    public function import(): array
    {
        try {
            Log::info('Starting legacy contract database import', [
                'dry_run' => $this->dryRun,
                'timestamp' => now()->toISOString(),
            ]);

            // Validate import files exist
            $this->validateImportFiles();

            // Load and validate data
            $importData = $this->loadImportData();

            // Validate program mappings
            $this->validateProgramMappings($importData);

            // Start database transaction for non-dry-run
            if (!$this->dryRun) {
                DB::beginTransaction();
            }

            // Import entities in proper order
            foreach ($this->config['import_order'] as $entity) {
                if ($entity === 'programs') {
                    continue; // We don't import programs, just validate mappings
                }
                
                $this->importEntity($entity, $importData[$entity] ?? []);
            }

            // Commit transaction if not dry run
            if (!$this->dryRun) {
                DB::commit();
            }

            $this->logImportSummary();

            return [
                'success' => true,
                'stats' => $this->importStats,
                'warnings' => $this->warnings,
                'errors' => $this->errors,
            ];

        } catch (Exception $e) {
            if (!$this->dryRun) {
                DB::rollBack();
            }

            Log::error('Legacy import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->importStats,
                'warnings' => $this->warnings,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Initialize import statistics
     */
    protected function initializeStats(): void
    {
        $this->importStats = [
            'users' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'funders' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'contracts' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'report_due_dates' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'due_date_assignments' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'missing_users' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
            'media' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
        ];
    }

    /**
     * Validate that all required import files exist
     */
    protected function validateImportFiles(): void
    {
        $missingFiles = [];
        
        foreach ($this->config['import_files'] as $entity => $filename) {
            $path = "import/{$filename}";
            
            if (!Storage::exists($path)) {
                // Try CSV alternative
                $csvPath = "import/{$this->config['csv_files'][$entity]}";
                if (!Storage::exists($csvPath)) {
                    $missingFiles[] = $filename;
                }
            }
        }

        if (!empty($missingFiles)) {
            throw new Exception("Missing import files: " . implode(', ', $missingFiles));
        }
    }

    /**
     * Load all import data from files
     */
    protected function loadImportData(): array
    {
        $importData = [];

        foreach ($this->config['import_files'] as $entity => $filename) {
            $data = $this->loadEntityData($entity, $filename);
            $importData[$entity] = $data;
            
            Log::info("Loaded {$entity} data", ['count' => count($data)]);
        }

        return $importData;
    }

    /**
     * Load data for a specific entity
     */
    protected function loadEntityData(string $entity, string $filename): array
    {
        $jsonPath = "import/{$filename}";
        $csvPath = "import/{$this->config['csv_files'][$entity]}";

        // Try JSON first
        if (Storage::exists($jsonPath)) {
            $content = Storage::get($jsonPath);
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in {$filename}: " . json_last_error_msg());
            }
            
            return $data ?: [];
        }

        // Fall back to CSV
        if (Storage::exists($csvPath)) {
            return $this->loadCsvData($csvPath, $entity);
        }

        throw new Exception("Could not find import file for {$entity}");
    }

    /**
     * Load CSV data and convert to array
     */
    protected function loadCsvData(string $path, string $entity): array
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
     * Validate program mappings exist for all contracts
     */
    protected function validateProgramMappings(array $importData): void
    {
        $contractPrograms = $importData['contract_programs'] ?? [];
        $unmappedPrograms = [];

        foreach ($contractPrograms as $relationship) {
            $programName = $relationship['program_name'];
            $programId = $this->mappingService->mapProgram($programName);
            
            if (!$programId) {
                $unmappedPrograms[] = $programName;
            }
        }

        if (!empty($unmappedPrograms) && $this->config['validation']['require_program_mapping']) {
            throw new Exception("Unmapped programs found: " . implode(', ', array_unique($unmappedPrograms)) . 
                              ". Please update config/legacy-import.php program_mapping.");
        }

        if (!empty($unmappedPrograms)) {
            $this->warnings[] = "Unmapped programs will use default program: " . implode(', ', array_unique($unmappedPrograms));
        }
    }

    /**
     * Import entities based on type
     */
    protected function importEntity(string $entity, array $data): void
    {
        if (empty($data)) {
            Log::info("No data to import for {$entity}");
            return;
        }

        Log::info("Importing {$entity}", ['count' => count($data)]);

        switch ($entity) {
            case 'users':
                $this->importUsers($data);
                break;
            case 'funders':
                $this->importFunders($data);
                break;
            case 'contracts':
                $this->importContracts($data);
                break;
            case 'report_due_dates':
                $this->importReportDueDates($data);
                break;
            case 'due_date_email_assignments':
                $this->importDueDateEmailAssignments($data);
                break;
            case 'media':
                $this->importMedia($data);
                break;
            default:
                Log::warning("Unknown entity type: {$entity}");
        }
    }

    /**
     * Import users
     */
    protected function importUsers(array $users): void
    {
        foreach ($users as $userData) {
            $this->importStats['users']['processed']++;

            try {
                // Validate required fields
                if (empty($userData['email'])) {
                    $this->importStats['users']['failed']++;
                    $this->errors[] = "User missing email: " . json_encode($userData);
                    continue;
                }

                // Check for existing user
                $existingUser = User::where('email', $userData['email'])->first();
                
                if ($existingUser) {
                    if ($this->config['import_mode']['update_existing_users']) {
                        if (!$this->dryRun) {
                            $existingUser->update([
                                'name' => $userData['name'] ?? $existingUser->name,
                                'active' => $userData['approved'] ?? true,
                                'email_verified_at' => $userData['approved'] ? now() : null,
                            ]);
                        }
                        $this->importStats['users']['updated']++;
                    } else {
                        $this->importStats['users']['skipped']++;
                    }
                    continue;
                }

                // Create new user
                if (!$this->dryRun) {
                    User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'active' => $userData['approved'] ?? true,
                        'email_verified_at' => $userData['approved'] ? now() : null,
                        'password' => bcrypt('temp_password_' . time()), // Temporary password, users will need to reset
                        'created_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $userData['created_at'] 
                            : now(),
                        'updated_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $userData['updated_at'] 
                            : now(),
                    ]);
                }

                $this->importStats['users']['created']++;

            } catch (Exception $e) {
                $this->importStats['users']['failed']++;
                $this->errors[] = "Failed to import user {$userData['email']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Import funders
     */
    protected function importFunders(array $funders): void
    {
        foreach ($funders as $funderData) {
            $this->importStats['funders']['processed']++;

            try {
                $funderName = $funderData['funder_name'];
                
                // Apply name mapping if configured
                $mappedName = $this->mappingService->mapFunderName($funderName);
                
                // Check for existing funder
                $existingFunder = Funder::where('name', $mappedName)->first();
                
                if ($existingFunder) {
                    if ($this->config['import_mode']['update_existing_funders']) {
                        if (!$this->dryRun) {
                            $existingFunder->update([
                                'description' => $funderData['description'] ?? $existingFunder->description,
                                'updated_at' => $this->config['import_mode']['preserve_timestamps'] 
                                    ? $funderData['updated_at'] 
                                    : now(),
                            ]);
                        } else {
                            // Cache existing funder for dry run
                            $this->dryRunFunderCache[$mappedName] = $existingFunder;
                        }
                        $this->importStats['funders']['updated']++;
                    } else {
                        // Cache existing funder for dry run
                        if ($this->dryRun) {
                            $this->dryRunFunderCache[$mappedName] = $existingFunder;
                        }
                        $this->importStats['funders']['skipped']++;
                    }
                    continue;
                }

                // Create new funder
                if (!$this->dryRun) {
                    Funder::create([
                        'name' => $mappedName,
                        'description' => "Imported from legacy system",
                        'active' => true,
                        'created_by' => 1, // System user
                        'created_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $funderData['created_at'] 
                            : now(),
                        'updated_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $funderData['updated_at'] 
                            : now(),
                    ]);
                } else {
                    // In dry run mode, cache the funder for contract lookup
                    $this->dryRunFunderCache[$mappedName] = (object) [
                        'id' => count($this->dryRunFunderCache) + 1000, // Fake ID for dry run
                        'name' => $mappedName,
                    ];
                }

                $this->importStats['funders']['created']++;

            } catch (Exception $e) {
                $this->importStats['funders']['failed']++;
                $this->errors[] = "Failed to import funder {$funderData['funder_name']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Import contracts
     */
    protected function importContracts(array $contracts): void
    {
        foreach ($contracts as $contractData) {
            $this->importStats['contracts']['processed']++;

            try {
                // Find the primary program for this contract
                $programId = $this->findContractProgramId($contractData['id']);
                
                if (!$programId) {
                    $programId = $this->config['default_program_id'];
                    $this->warnings[] = "Contract {$contractData['title']} assigned to default program";
                }

                // Find funder
                $mappedFunderName = $this->mappingService->mapFunderName($contractData['funder_name']);
                
                if ($this->dryRun) {
                    // Use cache in dry run mode
                    $funder = $this->dryRunFunderCache[$mappedFunderName] ?? null;
                } else {
                    // Normal database lookup
                    $funder = Funder::where('name', $mappedFunderName)->first();
                }
                
                if (!$funder) {
                    if ($this->config['validation']['require_funder_match']) {
                        throw new Exception("Funder not found: {$contractData['funder_name']}");
                    }
                    
                    // Create or use "Unknown Funder" for contracts without funders
                    $unknownFunderName = 'Unknown Funder';
                    
                    if ($this->dryRun) {
                        // Use cached unknown funder or create it
                        if (!isset($this->dryRunFunderCache[$unknownFunderName])) {
                            $this->dryRunFunderCache[$unknownFunderName] = (object) [
                                'id' => 9999, // Special ID for unknown funder
                                'name' => $unknownFunderName,
                            ];
                        }
                        $funder = $this->dryRunFunderCache[$unknownFunderName];
                    } else {
                        // Get or create unknown funder in database
                        $funder = Funder::firstOrCreate(
                            ['name' => $unknownFunderName],
                            [
                                'description' => 'Default funder for contracts without specified funders',
                                'active' => true,
                                'created_by' => 1,
                            ]
                        );
                    }
                    
                    $this->warnings[] = "Contract {$contractData['title']} assigned to 'Unknown Funder' (original: {$contractData['funder_name']})";
                }

                // Check for existing contract
                $existingContract = Contract::where('title', $contractData['title'])
                    ->where('contract_number', $contractData['contract_number'])
                    ->first();

                if ($existingContract) {
                    if ($this->config['import_mode']['update_existing_contracts']) {
                        // Update logic here
                        $this->importStats['contracts']['updated']++;
                    } else {
                        $this->importStats['contracts']['skipped']++;
                    }
                    continue;
                }

                // Handle null dates - provide defaults if missing
                $startDate = $contractData['start_date'] ?? $contractData['date_of_award'] ?? '2023-01-01';
                $endDate = $contractData['end_date'] ?? null;
                
                // If no end date, calculate based on start date (assume 1 year contract)
                if (!$endDate && $startDate) {
                    $endDate = date('Y-m-d', strtotime($startDate . ' +1 year'));
                } elseif (!$endDate) {
                    $endDate = '2024-12-31'; // Default end date
                }
                
                // Log if we had to use defaults
                if (!$contractData['start_date'] || !$contractData['end_date']) {
                    $this->warnings[] = "Contract {$contractData['title']} - used default dates (start: {$startDate}, end: {$endDate})";
                }

                // Create new contract
                if (!$this->dryRun) {
                    Contract::create([
                        'title' => $contractData['title'],
                        'contract_number' => $contractData['contract_number'],
                        'cfda_number' => $contractData['cfda_number'],
                        'purpose' => $contractData['purpose'],
                        'program_id' => $programId,
                        'funder_id' => $funder->id,
                        'date_of_award' => $contractData['date_of_award'],
                        'amount' => $contractData['amount'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'grant_code' => $contractData['grant_code'],
                        'notes' => $contractData['notes'],
                        'archived' => $contractData['archived'] ?? false,
                        'date_archived' => $contractData['date_archived'],
                        'created_by' => 1, // System user
                        'created_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $contractData['created_at'] 
                            : now(),
                        'updated_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $contractData['updated_at'] 
                            : now(),
                    ]);
                } else {
                    // Cache contract for dry run mode
                    $this->dryRunContractCache[$contractData['title']] = (object) [
                        'id' => count($this->dryRunContractCache) + 2000, // Fake ID for dry run
                        'title' => $contractData['title'],
                        'contract_number' => $contractData['contract_number'],
                    ];
                }

                $this->importStats['contracts']['created']++;

            } catch (Exception $e) {
                $this->importStats['contracts']['failed']++;
                $this->errors[] = "Failed to import contract {$contractData['title']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Find program ID for a contract
     */
    protected function findContractProgramId(int $contractId): ?int
    {
        // Load contract-program relationships if not already loaded
        static $contractPrograms = null;
        
        if ($contractPrograms === null) {
            $data = $this->loadEntityData('contract_programs', $this->config['import_files']['contract_programs']);
            $contractPrograms = collect($data)->groupBy('contract_id');
        }

        $relationships = $contractPrograms->get($contractId, collect());
        
        // Take the first program relationship (contracts had many-to-many in old system, 
        // but our new system uses single program per contract)
        $firstRelationship = $relationships->first();
        
        if (!$firstRelationship) {
            return null;
        }

        return $this->mappingService->mapProgram($firstRelationship['program_name']);
    }

    /**
     * Find contract by title with fallback lookups
     */
    protected function findContractByTitle(string $title): ?Contract
    {
        // First try exact match
        $contract = Contract::where('title', $title)->first();
        if ($contract) {
            return $contract;
        }
        
        // Try case-insensitive search
        $contract = Contract::whereRaw('LOWER(title) = LOWER(?)', [$title])->first();
        if ($contract) {
            return $contract;
        }
        
        // Try partial match (contract title might have variations)
        $contract = Contract::where('title', 'like', '%' . $title . '%')->first();
        if ($contract) {
            return $contract;
        }
        
        return null;
    }

    /**
     * Import report due dates
     */
    protected function importReportDueDates(array $reportDueDates): void
    {
        foreach ($reportDueDates as $dueDateData) {
            $this->importStats['report_due_dates']['processed']++;

            try {
                // Find the contract in our new system
                if ($this->dryRun) {
                    $contract = $this->dryRunContractCache[$dueDateData['contract_title']] ?? null;
                } else {
                    $contract = $this->findContractByTitle($dueDateData['contract_title']);
                }
                
                if (!$contract) {
                    $this->warnings[] = "Contract not found for report due date: {$dueDateData['contract_title']}";
                    $this->importStats['report_due_dates']['skipped']++;
                    continue;
                }

                // Map status
                $status = $this->config['status_mapping']['report_due_dates'][$dueDateData['status']] ?? $dueDateData['status'];

                // Create report due date
                if (!$this->dryRun) {
                    ContractReportDueDate::create([
                        'contract_id' => $contract->id,
                        'report_name' => $dueDateData['description'] ?? 'Imported Report',
                        'due_date' => $dueDateData['due_date'],
                        'description' => $dueDateData['description'],
                        'status' => $status,
                        'active' => true,
                        'reminder_intervals' => $this->config['default_reminder_intervals'],
                        'created_by' => 1, // System user
                        'created_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $dueDateData['created_at'] 
                            : now(),
                        'updated_at' => $this->config['import_mode']['preserve_timestamps'] 
                            ? $dueDateData['updated_at'] 
                            : now(),
                    ]);
                } else {
                    // Cache report due date for dry run
                    $cacheKey = $contract->id . '|' . $dueDateData['due_date'];
                    $this->dryRunReportDueDateCache[$cacheKey] = (object) [
                        'id' => 4000 + count($this->dryRunReportDueDateCache),
                        'contract_id' => $contract->id,
                        'due_date' => $dueDateData['due_date'],
                        'description' => $dueDateData['description'],
                    ];
                }

                $this->importStats['report_due_dates']['created']++;

            } catch (Exception $e) {
                $this->importStats['report_due_dates']['failed']++;
                $this->errors[] = "Failed to import report due date: " . $e->getMessage();
            }
        }
    }

    /**
     * Import due date email assignments  
     */
    protected function importDueDateEmailAssignments(array $assignments): void
    {
        foreach ($assignments as $assignmentData) {
            $this->importStats['due_date_assignments']['processed']++;

            try {
                // Find the user by email (case-insensitive)
                $email = strtolower($assignmentData['email']);
                
                if ($this->dryRun) {
                    // Check cache first
                    $user = $this->dryRunUserCache[$email] ?? null;
                    if (!$user) {
                        // Check database
                        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                        if ($user) {
                            $this->dryRunUserCache[$email] = $user;
                        }
                    }
                } else {
                    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                }
                
                if (!$user) {
                    // Create missing user
                    $userName = $this->generateUserNameFromEmail($assignmentData['email']);
                    
                    if ($this->dryRun) {
                        // Create fake user for dry run
                        $user = (object) [
                            'id' => 3000 + count($this->dryRunUserCache),
                            'name' => $userName,
                            'email' => $assignmentData['email'],
                            'active' => false, // Mark as inactive since they're historical
                        ];
                        $this->dryRunUserCache[$email] = $user;
                    } else {
                        // Create user in database
                        $user = User::create([
                            'name' => $userName,
                            'email' => $assignmentData['email'],
                            'active' => false, // Mark as inactive since they're historical
                            'email_verified_at' => null,
                            'password' => bcrypt('temp_password_' . time()), // Will need to reset password if they return
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    
                    $this->importStats['missing_users']['processed']++;
                    $this->importStats['missing_users']['created']++;
                    $this->warnings[] = "Created missing user for email assignment: {$assignmentData['email']} (marked as inactive)";
                }

                // Find the report due date
                if ($this->dryRun) {
                    $contract = $this->dryRunContractCache[$assignmentData['contract_title']] ?? null;
                } else {
                    $contract = $this->findContractByTitle($assignmentData['contract_title']);
                }
                
                if (!$contract) {
                    $this->warnings[] = "Contract not found for assignment: {$assignmentData['contract_title']}";
                    $this->importStats['due_date_assignments']['skipped']++;
                    continue;
                }

                if ($this->dryRun) {
                    // Look up report due date in cache
                    $cacheKey = $contract->id . '|' . $assignmentData['due_date'];
                    $reportDueDate = $this->dryRunReportDueDateCache[$cacheKey] ?? null;
                } else {
                    $reportDueDate = ContractReportDueDate::where('contract_id', $contract->id)
                        ->where('due_date', $assignmentData['due_date'])
                        ->first();
                }

                if (!$reportDueDate) {
                    $this->warnings[] = "Report due date not found for assignment";
                    $this->importStats['due_date_assignments']['skipped']++;
                    continue;
                }

                // Create or find the DueDateEmail record
                if (!$this->dryRun) {
                    // Find or create the DueDateEmail record
                    $dueDateEmail = \App\Models\DueDateEmail::firstOrCreate(
                        ['email' => $assignmentData['email']],
                        [
                            'name' => $user->name ?? 'Imported User',
                            'active' => true,
                            'created_by' => 1,
                            'preferences' => [
                                'reminder_intervals' => [30, 15, 7, 3, 1],
                                'receive_overdue_notifications' => true,
                                'receive_completion_notifications' => false,
                            ],
                        ]
                    );
                    
                    // Create the many-to-many relationship (avoid duplicates)
                    if (!$reportDueDate->emails()->where('due_date_email_id', $dueDateEmail->id)->exists()) {
                        $reportDueDate->emails()->attach($dueDateEmail->id, [
                            'reminder_intervals' => json_encode([30, 15, 7, 3, 1]),
                            'active' => true,
                        ]);
                    }
                }

                $this->importStats['due_date_assignments']['created']++;

            } catch (Exception $e) {
                $this->importStats['due_date_assignments']['failed']++;
                $this->errors[] = "Failed to import assignment: " . $e->getMessage();
            }
        }
    }

    /**
     * Import media files (contract documents)
     */
    protected function importMedia(array $mediaRecords): void
    {
        $mediaConfig = $this->config['media_migration'];
        
        foreach ($mediaRecords as $mediaData) {
            $this->importStats['media']['processed']++;

            try {
                // Find the contract in our new system
                $originalContractId = $mediaData['model_id'];
                $contractTitle = $mediaData['contract_title'] ?? '';
                
                if ($this->dryRun) {
                    $contract = $this->dryRunContractCache[$contractTitle] ?? null;
                } else {
                    $contract = $this->findContractByTitle($contractTitle);
                }
                
                if (!$contract) {
                    $this->warnings[] = "Contract not found for media file: {$contractTitle} (original ID: {$originalContractId})";
                    $this->importStats['media']['skipped']++;
                    continue;
                }

                // Validate file extension
                $fileName = $mediaData['file_name'];
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (!in_array($extension, $mediaConfig['allowed_extensions'])) {
                    $this->warnings[] = "File extension not allowed: {$fileName}";
                    $this->importStats['media']['skipped']++;
                    continue;
                }

                // Check file size
                $fileSize = $mediaData['size'] ?? 0;
                if ($fileSize > $mediaConfig['max_file_size']) {
                    $this->warnings[] = "File too large: {$fileName} ({$fileSize} bytes)";
                    $this->importStats['media']['skipped']++;
                    continue;
                }

                // Try to find the source file
                $sourcePath = $this->findSourceFile($mediaData, $mediaConfig);
                
                if (!$sourcePath && $mediaConfig['missing_file_strategy'] === 'fail') {
                    throw new Exception("Source file not found: {$fileName}");
                }
                
                if (!$sourcePath) {
                    if ($mediaConfig['missing_file_strategy'] === 'warn') {
                        $this->warnings[] = "Source file not found: {$fileName}";
                    }
                    $this->importStats['media']['skipped']++;
                    continue;
                }

                // Import the file if not in dry run mode
                if (!$this->dryRun && $mediaConfig['import_files']) {
                    $this->importContractDocument($contract, $mediaData, $sourcePath);
                }

                $this->importStats['media']['created']++;

            } catch (Exception $e) {
                $this->importStats['media']['failed']++;
                $this->errors[] = "Failed to import media file {$mediaData['file_name']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Find the source file path for a media record (supports remote fetching)
     */
    protected function findSourceFile(array $mediaData, array $mediaConfig): ?string
    {
        $fileName = $mediaData['file_name'];
        
        // Try remote server first if enabled
        if ($mediaConfig['remote_server']['enabled']) {
            $remotePath = $this->findRemoteFile($mediaData, $mediaConfig);
            if ($remotePath) {
                return $remotePath;
            }
        }
        
        // Fallback to local file search
        return $this->findLocalFile($mediaData, $mediaConfig);
    }

    /**
     * Find file on remote server
     */
    protected function findRemoteFile(array $mediaData, array $mediaConfig): ?string
    {
        $remoteConfig = $mediaConfig['remote_server'];
        $baseUrl = rtrim($remoteConfig['base_url'], '/');
        $storagePath = trim($remoteConfig['storage_path'], '/');
        $fileName = $mediaData['file_name'];
        
        // Common possible URLs where the file might be stored
        $possibleUrls = [
            // Direct storage access (Laravel public storage symlink pattern)
            "{$baseUrl}/storage/{$mediaData['id']}/{$fileName}",
            "{$baseUrl}/storage/{$mediaData['uuid']}/{$fileName}",
            
            // Standard Spatie Media Library paths
            "{$baseUrl}/{$storagePath}/app/public/media/{$mediaData['id']}/{$fileName}",
            "{$baseUrl}/{$storagePath}/media/{$mediaData['id']}/{$fileName}",
            "{$baseUrl}/{$storagePath}/public/media/{$mediaData['id']}/{$fileName}",
            
            // UUID-based paths (if used)
            "{$baseUrl}/{$storagePath}/app/public/media/{$mediaData['uuid']}/{$fileName}",
            "{$baseUrl}/{$storagePath}/media/{$mediaData['uuid']}/{$fileName}",
            
            // Alternative direct access paths
            "{$baseUrl}/storage/media/{$mediaData['id']}/{$fileName}",
            "{$baseUrl}/storage/media/{$mediaData['uuid']}/{$fileName}",
            
            // Alternative paths
            "{$baseUrl}/media/{$mediaData['id']}/{$fileName}",
            "{$baseUrl}/files/{$fileName}",
        ];

        foreach ($possibleUrls as $url) {
            if ($this->checkRemoteFileExists($url, $remoteConfig)) {
                Log::info("Found remote file", ['url' => $url, 'file' => $fileName]);
                return $url;
            }
        }

        return null;
    }

    /**
     * Find file locally (original logic)
     */
    protected function findLocalFile(array $mediaData, array $mediaConfig): ?string
    {
        $basePath = rtrim($mediaConfig['source_storage_path'], '/');
        $fileName = $mediaData['file_name'];
        $disk = $mediaData['disk'] ?? 'public';
        
        // Common possible paths where the file might be stored
        $possiblePaths = [
            // Standard Spatie Media Library paths
            "{$basePath}/app/public/media/{$mediaData['id']}/{$fileName}",
            "{$basePath}/app/public/{$mediaData['id']}/{$fileName}",
            "{$basePath}/public/media/{$mediaData['id']}/{$fileName}",
            "{$basePath}/storage/app/public/media/{$mediaData['id']}/{$fileName}",
            
            // Alternative paths
            "{$basePath}/media/{$mediaData['id']}/{$fileName}",
            "{$basePath}/{$fileName}",
            
            // UUID-based paths (if used)
            "{$basePath}/app/public/media/{$mediaData['uuid']}/{$fileName}",
            "{$basePath}/public/media/{$mediaData['uuid']}/{$fileName}",
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Check if remote file exists
     */
    protected function checkRemoteFileExists(string $url, array $remoteConfig): bool
    {
        try {
            $http = Http::timeout($remoteConfig['timeout']);
            
            // Add authentication if configured
            $auth = $remoteConfig['auth'];
            switch ($auth['type']) {
                case 'basic':
                    $http = $http->withBasicAuth($auth['username'], $auth['password']);
                    break;
                case 'bearer':
                    $http = $http->withToken($auth['token']);
                    break;
                case 'query':
                    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . $auth['query_param'];
                    break;
            }
            
            // Configure SSL verification
            if (!$remoteConfig['verify_ssl']) {
                $http = $http->withoutVerifying();
            }
            
            // Send HEAD request to check if file exists
            $response = $http->head($url);
            
            return $response->successful();
            
        } catch (Exception $e) {
            Log::debug("Remote file check failed", ['url' => $url, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Import a contract document using Spatie Media Library (supports remote URLs)
     */
    protected function importContractDocument(Contract $contract, array $mediaData, string $sourcePath): void
    {
        try {
            $media = null;
            
            // Check if source is a URL (remote file) or local path
            if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
                // Handle remote file
                $media = $this->addMediaFromUrl($contract, $sourcePath, $mediaData);
            } else {
                // Handle local file
                $media = $contract->addMedia($sourcePath)
                    ->usingName($mediaData['name'])
                    ->usingFileName($mediaData['file_name'])
                    ->toMediaCollection('documents');
            }

            // Update additional metadata if available
            if ($media && !empty($mediaData['custom_properties'])) {
                $customProperties = is_string($mediaData['custom_properties']) 
                    ? json_decode($mediaData['custom_properties'], true) 
                    : $mediaData['custom_properties'];
                    
                if (is_array($customProperties)) {
                    $media->custom_properties = $customProperties;
                    $media->save();
                }
            }

            Log::info("Imported document for contract", [
                'contract_id' => $contract->id,
                'contract_title' => $contract->title,
                'file_name' => $mediaData['file_name'],
                'original_media_id' => $mediaData['id'],
                'source_type' => filter_var($sourcePath, FILTER_VALIDATE_URL) ? 'remote' : 'local'
            ]);

        } catch (Exception $e) {
            throw new Exception("Failed to add media to contract: " . $e->getMessage());
        }
    }

    /**
     * Add media from remote URL
     */
    protected function addMediaFromUrl(Contract $contract, string $url, array $mediaData)
    {
        $remoteConfig = $this->config['media_migration']['remote_server'];
        
        try {
            // Setup HTTP client
            $http = Http::timeout($remoteConfig['timeout']);
            
            // Add authentication if configured
            $auth = $remoteConfig['auth'];
            switch ($auth['type']) {
                case 'basic':
                    $http = $http->withBasicAuth($auth['username'], $auth['password']);
                    break;
                case 'bearer':
                    $http = $http->withToken($auth['token']);
                    break;
                case 'query':
                    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . $auth['query_param'];
                    break;
            }
            
            // Configure SSL verification
            if (!$remoteConfig['verify_ssl']) {
                $http = $http->withoutVerifying();
            }
            
            // Download the file
            $response = $http->get($url);
            
            if (!$response->successful()) {
                throw new Exception("Failed to download file from {$url}: HTTP " . $response->status());
            }
            
            // Create temporary file
            $tempPath = tempnam(sys_get_temp_dir(), 'remote_media_');
            file_put_contents($tempPath, $response->body());
            
            // Add media from temporary file
            $media = $contract->addMedia($tempPath)
                ->usingName($mediaData['name'])
                ->usingFileName($mediaData['file_name'])
                ->toMediaCollection('documents');
            
            // Clean up temporary file
            unlink($tempPath);
            
            return $media;
            
        } catch (Exception $e) {
            // Clean up temp file if it exists
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw new Exception("Failed to download and import remote file: " . $e->getMessage());
        }
    }

    /**
     * Generate a user name from an email address
     */
    protected function generateUserNameFromEmail(string $email): string
    {
        // Extract name part from email (before @)
        $namePart = explode('@', $email)[0];
        
        // Convert common patterns to readable names
        $name = str_replace(['.', '_', '-'], ' ', $namePart);
        $name = ucwords($name);
        
        // If it looks like firstname.lastname, format nicely
        $parts = explode(' ', $name);
        if (count($parts) >= 2) {
            return implode(' ', $parts);
        }
        
        // If it's a single name or username, append "User"
        return $name;
    }

    /**
     * Log import summary
     */
    protected function logImportSummary(): void
    {
        $summary = [
            'dry_run' => $this->dryRun,
            'completed_at' => now()->toISOString(),
            'stats' => $this->importStats,
            'warnings_count' => count($this->warnings),
            'errors_count' => count($this->errors),
        ];

        Log::info('Legacy import completed', $summary);

        // Save detailed log
        if ($this->config['logging']['save_failed_records'] && !empty($this->errors)) {
            Storage::put('import/import-errors-' . now()->format('Y-m-d-H-i-s') . '.json', 
                json_encode($this->errors, JSON_PRETTY_PRINT));
        }
    }
}