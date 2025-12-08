<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use App\Models\Funder;
use App\Models\Program;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LegacyContractSyncService
{
    protected string $connection = 'legacy_contracts';

    protected array $syncStats = [];

    protected array $errors = [];

    protected array $warnings = [];

    /**
     * Preview contracts from legacy database with filters
     */
    public function previewContracts(
        ?string $legacyIdFilter = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $limit = 100
    ): Collection {
        $query = DB::connection($this->connection)
            ->table('contracts');

        // Apply legacy ID filter
        if ($legacyIdFilter) {
            if (str_contains($legacyIdFilter, ',')) {
                // Multiple IDs
                $ids = array_map('trim', explode(',', $legacyIdFilter));
                $query->whereIn('id', $ids);
            } elseif (str_contains($legacyIdFilter, '-')) {
                // Range
                [$start, $end] = explode('-', $legacyIdFilter);
                $query->whereBetween('id', [trim($start), trim($end)]);
            } else {
                // Single ID or pattern
                $query->where('id', 'like', '%'.trim($legacyIdFilter).'%');
            }
        }

        // Apply date filters
        if ($startDate) {
            $query->where('start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('end_date', '<=', $endDate);
        }

        // Get contracts with related data counts
        $contracts = $query
            ->select([
                'id as legacy_id',
                'title',
                'contract_number',
                'grant_code',
                'purpose',
                'notes',
                'funder_id',
                'start_date',
                'end_date',
                'amount',
                'date_of_award',
                'cfda_number',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        // Enrich with additional information
        foreach ($contracts as $contract) {
            // Check if already imported
            $existingContract = Contract::where('legacy_id', $contract->legacy_id)->first();
            $contract->already_imported = (bool) $existingContract;
            $contract->current_id = $existingContract?->id;
            $contract->last_synced = $existingContract?->last_synced_at?->format('Y-m-d H:i:s');

            // Get related counts from legacy database
            $contract->due_dates_count = DB::connection($this->connection)
                ->table('report_due_dates')
                ->where('contract_id', $contract->legacy_id)
                ->count();

            $contract->media_count = DB::connection($this->connection)
                ->table('media')
                ->where('model_type', 'App\\Models\\Contract')
                ->where('model_id', $contract->legacy_id)
                ->count();

            // Get program names from pivot table (contracts can have multiple programs)
            $programIds = DB::connection($this->connection)
                ->table('contract_program')
                ->where('contract_id', $contract->legacy_id)
                ->pluck('program_id');

            if ($programIds->isNotEmpty()) {
                $programNames = DB::connection($this->connection)
                    ->table('programs')
                    ->whereIn('id', $programIds)
                    ->pluck('program_name');
                $contract->program_name = $programNames->implode(', ');
                $contract->program_ids = $programIds->toArray();
            } else {
                $contract->program_name = '-';
                $contract->program_ids = [];
            }

            $contract->funder_name = DB::connection($this->connection)
                ->table('funders')
                ->where('id', $contract->funder_id)
                ->value('funder_name') ?? '-';

            // Format amount
            $contract->amount_formatted = number_format($contract->amount, 2);
        }

        return $contracts;
    }

    /**
     * Sync selected contracts from legacy database
     */
    public function syncContracts(array $legacyIds, bool $updateExisting = false): array
    {
        $this->initializeStats();

        try {
            DB::beginTransaction();

            foreach ($legacyIds as $legacyId) {
                try {
                    $this->syncSingleContract($legacyId, $updateExisting);
                } catch (Exception $e) {
                    $this->errors[] = "Contract ID {$legacyId}: ".$e->getMessage();
                    Log::error("Failed to sync contract {$legacyId}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'stats' => $this->syncStats,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->syncStats,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        }
    }

    /**
     * Sync a single contract with all related data
     */
    protected function syncSingleContract(int $legacyId, bool $updateExisting = false): void
    {
        // Get contract from legacy database
        $legacyContract = DB::connection($this->connection)
            ->table('contracts')
            ->where('id', $legacyId)
            ->first();

        if (! $legacyContract) {
            throw new Exception('Contract not found in legacy database');
        }

        // Check if already exists
        $existingContract = Contract::where('legacy_id', $legacyId)->first();

        if ($existingContract && ! $updateExisting) {
            $this->syncStats['skipped']++;
            $this->warnings[] = "Contract ID {$legacyId} already exists (skipped)";

            return;
        }

        // Get program IDs from pivot table
        $legacyProgramIds = DB::connection($this->connection)
            ->table('contract_program')
            ->where('contract_id', $legacyId)
            ->pluck('program_id')
            ->toArray();

        // Map the first program (or use default if none)
        $programId = $this->mapProgramFromLegacyIds($legacyProgramIds);
        $funderId = $this->mapFunder($legacyContract->funder_id);

        // Prepare contract data - preserve HTML formatting in purpose and notes
        $contractData = [
            'legacy_id' => $legacyId,
            'title' => $legacyContract->title,
            'contract_number' => $legacyContract->contract_number,
            'cfda_number' => $legacyContract->cfda_number,
            'purpose' => $legacyContract->purpose, // Keep HTML as-is
            'notes' => $legacyContract->notes, // Keep HTML as-is
            'program_id' => $programId,
            'funder_id' => $funderId,
            'date_of_award' => $legacyContract->date_of_award,
            'amount' => $legacyContract->amount,
            'start_date' => $legacyContract->start_date,
            'end_date' => $legacyContract->end_date,
            'grant_code' => $legacyContract->grant_code,
            'last_synced_at' => now(),
            'created_by' => 1, // System user
            'updated_by' => 1,
        ];

        // Create or update contract
        if ($existingContract) {
            $existingContract->update($contractData);
            $contract = $existingContract;
            $this->syncStats['updated']++;
        } else {
            // Preserve original timestamps
            $contractData['created_at'] = $legacyContract->created_at;
            $contractData['updated_at'] = $legacyContract->updated_at;

            $contract = Contract::create($contractData);
            $this->syncStats['created']++;
        }

        // Sync related data
        $this->syncContractDueDates($legacyId, $contract->id);
        $this->syncContractMedia($legacyId, $contract);

        Log::info('Successfully synced contract', [
            'legacy_id' => $legacyId,
            'contract_id' => $contract->id,
            'title' => $contract->title,
        ]);
    }

    /**
     * Sync contract report due dates
     */
    protected function syncContractDueDates(int $legacyContractId, int $contractId): void
    {
        $legacyDueDates = DB::connection($this->connection)
            ->table('report_due_dates')
            ->where('contract_id', $legacyContractId)
            ->get();

        foreach ($legacyDueDates as $legacyDueDate) {
            // Check if already synced
            $existing = ContractReportDueDate::where('legacy_id', $legacyDueDate->id)->first();

            // Generate report name from description or use default
            $reportName = $legacyDueDate->description
                ? (strlen($legacyDueDate->description) > 100
                    ? substr($legacyDueDate->description, 0, 100).'...'
                    : $legacyDueDate->description)
                : 'Report Due '.date('m/d/Y', strtotime($legacyDueDate->due_date));

            $dueDateData = [
                'legacy_id' => $legacyDueDate->id,
                'contract_id' => $contractId,
                'report_name' => $reportName,
                'due_date' => $legacyDueDate->due_date,
                'description' => $legacyDueDate->description,
                'reminder_intervals' => [30, 15, 7, 3, 1], // Use default intervals
                'active' => true,
                'status' => $legacyDueDate->status ?? 'pending',
                'last_synced_at' => now(),
                'created_by' => 1,
                'updated_by' => 1,
            ];

            if ($existing) {
                $existing->update($dueDateData);
                $dueDate = $existing;
            } else {
                $dueDateData['created_at'] = $legacyDueDate->created_at;
                $dueDateData['updated_at'] = $legacyDueDate->updated_at;
                $dueDate = ContractReportDueDate::create($dueDateData);
            }

            // Sync assigned users for this due date
            $this->syncDueDateAssignments($legacyDueDate->id, $dueDate->id);
        }
    }

    /**
     * Sync due date email assignments
     */
    protected function syncDueDateAssignments(int $legacyDueDateId, int $dueDateId): void
    {
        // Get assignments from pivot table
        $assignments = DB::connection($this->connection)
            ->table('due_date_email_report_due_date')
            ->where('report_due_date_id', $legacyDueDateId)
            ->get();

        $dueDate = ContractReportDueDate::find($dueDateId);

        foreach ($assignments as $assignment) {
            // Get the due date email record
            $legacyEmail = DB::connection($this->connection)
                ->table('due_date_emails')
                ->where('id', $assignment->due_date_email_id)
                ->first();

            if (! $legacyEmail) {
                continue;
            }

            // Find or create user by email
            $user = User::where('email', $legacyEmail->email)->first();

            if (! $user) {
                // Create DueDateEmail without user
                $dueDateEmail = DueDateEmail::firstOrCreate([
                    'email' => $legacyEmail->email,
                ], [
                    'name' => $legacyEmail->name,
                    'active' => $legacyEmail->active ?? true,
                    'created_by' => 1,
                ]);
            } else {
                // Create DueDateEmail with user
                $dueDateEmail = DueDateEmail::firstOrCreate([
                    'user_id' => $user->id,
                    'email' => $legacyEmail->email,
                ], [
                    'name' => $legacyEmail->name ?? $user->name,
                    'active' => $legacyEmail->active ?? true,
                    'created_by' => 1,
                ]);
            }

            // Attach to due date if not already attached
            if (! $dueDate->emails()->where('due_date_email_id', $dueDateEmail->id)->exists()) {
                $dueDate->emails()->attach($dueDateEmail->id, [
                    'reminder_intervals' => json_decode($assignment->reminder_intervals ?? '[]', true) ?: [30, 15, 7, 3, 1],
                    'active' => $assignment->active ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Sync contract media/attachments
     */
    protected function syncContractMedia(int $legacyContractId, Contract $contract): void
    {
        $mediaRecords = DB::connection($this->connection)
            ->table('media')
            ->where('model_type', 'App\\Models\\Contract')
            ->where('model_id', $legacyContractId)
            ->get();

        foreach ($mediaRecords as $mediaRecord) {
            try {
                // Check if media already synced
                if ($contract->getMedia('documents')->where('uuid', $mediaRecord->uuid)->count() > 0) {
                    continue;
                }

                // Download file from legacy server
                $baseUrl = config('legacy-import.media_migration.remote_server.base_url');
                $storagePath = config('legacy-import.media_migration.remote_server.storage_path', '/storage');
                $fileUrl = $baseUrl.$storagePath.'/'.$mediaRecord->id.'/'.$mediaRecord->file_name;

                $response = Http::timeout(60)
                    ->withOptions(['verify' => false]) // Skip SSL verification for internal server
                    ->get($fileUrl);

                if ($response->successful()) {
                    // Save file temporarily
                    $tempPath = storage_path('app/temp/'.$mediaRecord->file_name);
                    file_put_contents($tempPath, $response->body());

                    // Add to media library
                    $contract->addMedia($tempPath)
                        ->usingName($mediaRecord->name)
                        ->usingFileName($mediaRecord->file_name)
                        ->withCustomProperties([
                            'uuid' => $mediaRecord->uuid,
                            'legacy_id' => $mediaRecord->id,
                        ])
                        ->toMediaCollection('documents');

                    // Clean up temp file
                    @unlink($tempPath);

                    $this->syncStats['media_synced']++;
                } else {
                    $this->warnings[] = "Failed to download media file: {$mediaRecord->file_name} (HTTP {$response->status()})";
                }
            } catch (Exception $e) {
                $this->warnings[] = "Failed to sync media {$mediaRecord->file_name}: ".$e->getMessage();
                Log::error('Media sync failed', [
                    'media_id' => $mediaRecord->id,
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Map multiple legacy program IDs to a single new program ID
     */
    protected function mapProgramFromLegacyIds(array $legacyProgramIds): int
    {
        if (empty($legacyProgramIds)) {
            return config('legacy-import.default_program_id', 5);
        }

        // Try to map the first program
        foreach ($legacyProgramIds as $legacyProgramId) {
            $mappedId = $this->mapProgram($legacyProgramId);
            if ($mappedId !== config('legacy-import.default_program_id', 5)) {
                return $mappedId;
            }
        }

        // If none mapped successfully, return default
        return config('legacy-import.default_program_id', 5);
    }

    /**
     * Map legacy program ID to new program ID
     */
    protected function mapProgram(?int $legacyProgramId): int
    {
        if (! $legacyProgramId) {
            return config('legacy-import.default_program_id', 5);
        }

        // Get legacy program name
        $legacyProgram = DB::connection($this->connection)
            ->table('programs')
            ->where('id', $legacyProgramId)
            ->first();

        if (! $legacyProgram) {
            return config('legacy-import.default_program_id', 5);
        }

        // Check if program with same legacy_id exists
        $program = Program::where('legacy_id', $legacyProgramId)->first();

        if ($program) {
            return $program->id;
        }

        // Try to find by name match
        $program = Program::where('name', $legacyProgram->program_name)->first();

        if ($program) {
            return $program->id;
        }

        // Use mapping from config
        $mapping = config('legacy-import.program_mapping', []);
        if (isset($mapping[$legacyProgram->program_name])) {
            return $mapping[$legacyProgram->program_name];
        }

        return config('legacy-import.default_program_id', 5);
    }

    /**
     * Map legacy funder ID to new funder ID
     */
    protected function mapFunder(?int $legacyFunderId): int
    {
        if (! $legacyFunderId) {
            throw new Exception('Funder ID is required');
        }

        // Get legacy funder
        $legacyFunder = DB::connection($this->connection)
            ->table('funders')
            ->where('id', $legacyFunderId)
            ->first();

        if (! $legacyFunder) {
            throw new Exception("Legacy funder not found: {$legacyFunderId}");
        }

        // Check if funder with same legacy_id exists
        $funder = Funder::where('legacy_id', $legacyFunderId)->first();

        if ($funder) {
            return $funder->id;
        }

        // Try to find by name match
        $funder = Funder::where('name', $legacyFunder->funder_name)->first();

        if ($funder) {
            // Update with legacy_id
            $funder->update(['legacy_id' => $legacyFunderId]);

            return $funder->id;
        }

        // Create new funder
        $funder = Funder::create([
            'legacy_id' => $legacyFunderId,
            'name' => $legacyFunder->funder_name,
            'active' => true,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $legacyFunder->created_at,
            'updated_at' => $legacyFunder->updated_at,
        ]);

        $this->syncStats['funders_created']++;

        return $funder->id;
    }

    /**
     * Initialize sync statistics
     */
    protected function initializeStats(): void
    {
        $this->syncStats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'funders_created' => 0,
            'media_synced' => 0,
        ];
        $this->errors = [];
        $this->warnings = [];
    }

    /**
     * Test legacy database connection
     */
    public function testConnection(): array
    {
        try {
            $result = DB::connection($this->connection)->select('SELECT COUNT(*) as count FROM contracts');

            return [
                'success' => true,
                'message' => 'Connection successful',
                'contracts_count' => $result[0]->count ?? 0,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }
}
