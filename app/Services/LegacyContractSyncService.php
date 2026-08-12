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
     * Whether attachments should be downloaded during this sync.
     */
    protected bool $syncMedia = true;

    /**
     * User id to attribute created_by / updated_by to. Resolved lazily because
     * scheduled runs have no authenticated user.
     */
    protected ?int $actorId = null;

    protected bool $actorResolved = false;

    /**
     * Toggle attachment downloading for this sync (scheduled runs may skip it).
     */
    public function withMedia(bool $syncMedia): static
    {
        $this->syncMedia = $syncMedia;

        return $this;
    }

    /**
     * Resolve the user to credit for records this sync touches.
     *
     * Interactive imports use the logged-in user. Scheduled runs fall back to
     * the configured system user, or null when none is configured.
     */
    protected function actorId(): ?int
    {
        if ($this->actorResolved) {
            return $this->actorId;
        }

        $this->actorResolved = true;

        if ($id = auth()->id()) {
            return $this->actorId = $id;
        }

        if ($email = config('legacy-import.auto_sync.system_user_email')) {
            $this->actorId = User::where('email', $email)->value('id');
        }

        return $this->actorId;
    }

    /**
     * Remove fields CAP TRACK owns from an update payload.
     *
     * Applied only when updating a record that already exists here. On first
     * import every field comes from legacy, so nothing is stripped.
     *
     * @param  array<string, mixed>  $payload
     * @param  'contract'|'due_date'  $entity
     * @param  object|null  $existing  Current local record, for fill-if-empty checks
     * @return array<string, mixed>
     */
    protected function stripLocallyOwned(array $payload, string $entity, $existing = null): array
    {
        // No local record yet means there is nothing to protect: a first import
        // takes every field from legacy.
        if (! $existing) {
            return $payload;
        }

        $owned = config("legacy-import.auto_sync.locally_owned.{$entity}", []);
        $fillIfEmpty = config("legacy-import.auto_sync.fill_if_empty.{$entity}", []);

        foreach ($owned as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            // A locally-owned field may still be backfilled while it is empty,
            // e.g. a contract imported before program mapping was configured.
            if (in_array($field, $fillIfEmpty, true) && blank($existing->{$field})) {
                continue;
            }

            unset($payload[$field]);
        }

        return $payload;
    }

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
            ->table('contracts')
            // Contracts deleted in the legacy system are never candidates for
            // import. Records already imported before deletion are reported by
            // detectChanges() instead of being silently re-imported.
            ->whereNull('deleted_at');

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

        foreach ($legacyIds as $legacyId) {
            // One transaction per contract. A bad record rolls back only its
            // own changes, and no lock is held across the HTTP attachment
            // downloads that follow.
            try {
                $contract = DB::transaction(fn () => $this->syncSingleContract($legacyId, $updateExisting));

                if ($contract && $this->syncMedia) {
                    $this->syncContractMedia($legacyId, $contract);
                }
            } catch (Exception $e) {
                $this->errors[] = "Contract ID {$legacyId}: ".$e->getMessage();
                Log::error("Failed to sync contract {$legacyId}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'success' => empty($this->errors) || $this->syncStats['created'] + $this->syncStats['updated'] > 0,
            'stats' => $this->syncStats,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Sync a single contract and its due dates.
     *
     * Attachments are handled by the caller, outside the transaction.
     *
     * @return Contract|null The synced contract, or null when skipped
     */
    protected function syncSingleContract(int $legacyId, bool $updateExisting = false): ?Contract
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

        if ($legacyContract->deleted_at && ! $existingContract) {
            $this->syncStats['skipped']++;
            $this->warnings[] = "Contract ID {$legacyId} is deleted in the legacy system (skipped)";

            return null;
        }

        if ($existingContract && ! $updateExisting) {
            $this->syncStats['skipped']++;
            $this->warnings[] = "Contract ID {$legacyId} already exists (skipped)";

            return null;
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
            'archived' => false, // New imports start active
            'last_synced_at' => now(),
            'updated_by' => $this->actorId(),
        ];

        // Create or update contract
        if ($existingContract) {
            // Fields CAP TRACK owns (archived state, hand-corrected program)
            // must survive a re-sync from legacy.
            $existingContract->update(
                $this->stripLocallyOwned($contractData, 'contract', $existingContract)
            );
            $contract = $existingContract;
            $this->syncStats['updated']++;
        } else {
            // Preserve original timestamps
            $contractData['created_at'] = $legacyContract->created_at;
            $contractData['updated_at'] = $legacyContract->updated_at;
            $contractData['created_by'] = $this->actorId();

            $contract = Contract::create($contractData);
            $this->syncStats['created']++;
        }

        // Sync related data (attachments are handled by the caller)
        $this->syncContractDueDates($legacyId, $contract->id);

        Log::info('Successfully synced contract', [
            'legacy_id' => $legacyId,
            'contract_id' => $contract->id,
            'title' => $contract->title,
        ]);

        return $contract;
    }

    /**
     * Sync contract report due dates
     */
    protected function syncContractDueDates(int $legacyContractId, int $contractId): void
    {
        $legacyDueDates = DB::connection($this->connection)
            ->table('report_due_dates')
            ->where('contract_id', $legacyContractId)
            // Due dates deleted upstream are not imported. Ones imported before
            // deletion stay as historical record, matching the contract policy.
            ->whereNull('deleted_at')
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
                'reminder_intervals' => config('legacy-import.default_reminder_intervals', [30, 15, 7, 3, 1]),
                'active' => true,
                'status' => $this->mapDueDateStatus($legacyDueDate->status ?? null),
                'last_synced_at' => now(),
                'updated_by' => $this->actorId(),
            ];

            if ($existing) {
                // Preserve the CAP TRACK-side workflow: derived statuses
                // (pending/overdue/cancelled), customised reminder intervals
                // and any locally renamed report. Only the legacy-authoritative
                // due date and description are refreshed.
                $existing->update(
                    $this->stripLocallyOwned($dueDateData, 'due_date', $existing)
                );
                $dueDate = $existing;
                $this->syncStats['due_dates_updated']++;
            } else {
                $dueDateData['created_at'] = $legacyDueDate->created_at;
                $dueDateData['updated_at'] = $legacyDueDate->updated_at;
                $dueDateData['created_by'] = $this->actorId();
                $dueDate = ContractReportDueDate::create($dueDateData);
                $this->syncStats['due_dates_created']++;
            }

            // Sync assigned users for this due date
            $this->syncDueDateAssignments($legacyDueDate->id, $dueDate->id);
        }
    }

    /**
     * Translate a legacy due-date status into a CAP TRACK status.
     *
     * Legacy only distinguishes incomplete/completed. CAP TRACK's vocabulary is
     * pending/completed/overdue/cancelled, and derives overdue itself, so an
     * incomplete legacy report lands as pending.
     */
    protected function mapDueDateStatus(?string $legacyStatus): string
    {
        $map = config('legacy-import.status_mapping.report_due_dates', []);

        if ($legacyStatus && isset($map[$legacyStatus])) {
            return $map[$legacyStatus];
        }

        // Not in the configured map — accept it if CAP TRACK knows the value,
        // otherwise fall back to pending.
        if ($legacyStatus && array_key_exists($legacyStatus, ContractReportDueDate::STATUS_OPTIONS)) {
            return $legacyStatus;
        }

        return 'pending';
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

            // The legacy due_date_emails table carries only an email address —
            // no name or active column — so fall back to the matched CAP TRACK
            // user, then to the local part of the address.
            $legacyName = $legacyEmail->name
                ?? $user?->name
                ?? ucwords(str_replace(['.', '_', '-'], ' ', strstr($legacyEmail->email, '@', true) ?: $legacyEmail->email));

            if (! $user) {
                // Create DueDateEmail without user
                $dueDateEmail = DueDateEmail::firstOrCreate([
                    'email' => $legacyEmail->email,
                ], [
                    'name' => $legacyName,
                    'active' => $legacyEmail->active ?? true,
                    'created_by' => $this->actorId(),
                ]);
            } else {
                // Create DueDateEmail with user
                $dueDateEmail = DueDateEmail::firstOrCreate([
                    'user_id' => $user->id,
                    'email' => $legacyEmail->email,
                ], [
                    'name' => $legacyName,
                    'active' => $legacyEmail->active ?? true,
                    'created_by' => $this->actorId(),
                ]);
            }

            // Attach to due date if not already attached
            if (! $dueDate->emails()->where('due_date_email_id', $dueDateEmail->id)->exists()) {
                $reminderIntervals = json_decode($assignment->reminder_intervals ?? '[]', true) ?: [30, 15, 7, 3, 1];

                $dueDate->emails()->attach($dueDateEmail->id, [
                    'reminder_intervals' => $reminderIntervals,
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

                // A handful of legacy rows point at zero-byte files. There is
                // nothing to carry over, and attempting it produces a confusing
                // mime-type rejection instead of a clear reason.
                if ((int) $mediaRecord->size === 0) {
                    $this->warnings[] = "Skipped empty legacy file (0 bytes): {$mediaRecord->file_name}";

                    continue;
                }

                // Download file from legacy server
                $baseUrl = config('legacy-import.media_migration.remote_server.base_url');
                $storagePath = config('legacy-import.media_migration.remote_server.storage_path', '/storage');
                $fileUrl = $baseUrl.$storagePath.'/'.$mediaRecord->id.'/'.$mediaRecord->file_name;

                $response = Http::timeout(60)
                    ->withOptions(['verify' => false]) // Skip SSL verification for internal server
                    ->get($fileUrl);

                if (! $response->successful()) {
                    $this->warnings[] = "Failed to download media file: {$mediaRecord->file_name} (HTTP {$response->status()})";

                    continue;
                }

                // Stage the download in a per-media directory. Writing straight
                // into a shared temp folder collided with stale files left by
                // earlier runs under a different user, which the current
                // process then could not overwrite.
                $tempDir = storage_path('app/temp/legacy-'.$mediaRecord->id);
                if (! is_dir($tempDir)) {
                    mkdir($tempDir, 0775, true);
                }

                $tempPath = $tempDir.'/'.basename($mediaRecord->file_name);

                try {
                    if (file_put_contents($tempPath, $response->body()) === false) {
                        throw new Exception('could not stage the download at '.$tempPath);
                    }

                    // Add to media library
                    $contract->addMedia($tempPath)
                        ->usingName($mediaRecord->name)
                        ->usingFileName($mediaRecord->file_name)
                        ->withCustomProperties([
                            'uuid' => $mediaRecord->uuid,
                            'legacy_id' => $mediaRecord->id,
                        ])
                        ->toMediaCollection('documents');

                    $this->syncStats['media_synced']++;
                } finally {
                    // Always clear the staging area, including on failure —
                    // otherwise a rejected file is left behind to collide with
                    // the next run.
                    @unlink($tempPath);
                    @rmdir($tempDir);
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
     * Map legacy funder ID to new funder ID.
     *
     * A contract with no funder is legitimate in both systems — letters of
     * support and pre-award records often have none — so this returns null
     * rather than blocking the import. A dangling reference is warned about
     * for the run report but likewise does not stop the contract landing.
     */
    protected function mapFunder(?int $legacyFunderId): ?int
    {
        if (! $legacyFunderId) {
            return null;
        }

        // Get legacy funder
        $legacyFunder = DB::connection($this->connection)
            ->table('funders')
            ->where('id', $legacyFunderId)
            ->first();

        if (! $legacyFunder) {
            $this->warnings[] = "Legacy funder {$legacyFunderId} not found; contract imported without a funder.";

            return null;
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
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
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
            'due_dates_created' => 0,
            'due_dates_updated' => 0,
        ];
        $this->errors = [];
        $this->warnings = [];
    }

    /**
     * Download any legacy attachments missing from already-synced contracts.
     *
     * The normal sync only fetches attachments for contracts it touches, so a
     * file that failed once — rejected by a mime or size rule since relaxed —
     * would never be retried while the contract itself stays in step. This
     * walks every synced contract and fills the gaps. Idempotent: attachments
     * already present are matched on uuid and skipped.
     */
    public function backfillMedia(?int $limit = null): array
    {
        $this->initializeStats();
        $this->withMedia(true);

        $checked = 0;

        Contract::whereNotNull('legacy_id')
            ->orderBy('legacy_id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->chunkById(50, function ($contracts) use (&$checked) {
                foreach ($contracts as $contract) {
                    try {
                        $this->syncContractMedia($contract->legacy_id, $contract);
                        $checked++;
                    } catch (Exception $e) {
                        $this->errors[] = "Contract {$contract->id} (legacy {$contract->legacy_id}): ".$e->getMessage();
                    }
                }
            });

        return [
            'success' => true,
            'contracts_checked' => $checked,
            'stats' => $this->syncStats,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Compare the legacy contract database against CAP TRACK.
     *
     * Classifies every legacy contract into one of four buckets without
     * writing anything, so both the scheduled command and the UI can report
     * pending drift before it is applied.
     *
     * @return array{
     *     new: array<int, array<string, mixed>>,
     *     updated: array<int, array<string, mixed>>,
     *     deleted_in_legacy: array<int, array<string, mixed>>,
     *     unchanged: int,
     *     legacy_total: int,
     *     checked_at: \Carbon\Carbon
     * }
     */
    public function detectChanges(): array
    {
        $skew = (int) config('legacy-import.auto_sync.clock_skew_seconds', 120);

        // Everything we already hold, keyed by legacy id.
        $local = Contract::whereNotNull('legacy_id')
            ->get(['id', 'legacy_id', 'title', 'last_synced_at', 'archived'])
            ->keyBy('legacy_id');

        $legacyRows = DB::connection($this->connection)
            ->table('contracts')
            ->select(['id', 'title', 'contract_number', 'amount', 'updated_at', 'deleted_at'])
            ->orderBy('id')
            ->get();

        $new = [];
        $updated = [];
        $deletedInLegacy = [];
        $unchanged = 0;

        foreach ($legacyRows as $row) {
            $existing = $local->get($row->id);

            if ($row->deleted_at) {
                // Only interesting if we imported it before it was deleted.
                if ($existing) {
                    $deletedInLegacy[] = [
                        'legacy_id' => $row->id,
                        'contract_id' => $existing->id,
                        'title' => $existing->title ?: $row->title,
                        'deleted_at' => $row->deleted_at,
                        'archived' => (bool) $existing->archived,
                    ];
                }

                continue;
            }

            if (! $existing) {
                $new[] = [
                    'legacy_id' => $row->id,
                    'title' => $row->title,
                    'contract_number' => $row->contract_number,
                    'amount' => $row->amount,
                    'updated_at' => $row->updated_at,
                ];

                continue;
            }

            // Never synced, or legacy changed after our last sync. The skew
            // allowance stops a slightly-fast legacy clock from hiding an edit.
            $threshold = $existing->last_synced_at?->copy()->addSeconds($skew);

            if (! $threshold || ($row->updated_at && $row->updated_at > $threshold->format('Y-m-d H:i:s'))) {
                $updated[] = [
                    'legacy_id' => $row->id,
                    'contract_id' => $existing->id,
                    'title' => $existing->title ?: $row->title,
                    'legacy_updated_at' => $row->updated_at,
                    'last_synced_at' => $existing->last_synced_at?->format('Y-m-d H:i:s'),
                    'archived' => (bool) $existing->archived,
                ];

                continue;
            }

            $unchanged++;
        }

        return [
            'new' => $new,
            'updated' => $updated,
            'deleted_in_legacy' => $deletedInLegacy,
            'unchanged' => $unchanged,
            'legacy_total' => $legacyRows->count(),
            'checked_at' => now(),
        ];
    }

    /**
     * Detect drift and apply it.
     *
     * This is the entry point for the scheduled sync. Honours the auto_sync
     * config for what to import, how much to touch in one run, and what to do
     * about contracts deleted upstream.
     *
     * @param  array{import_new?: bool, update_existing?: bool, sync_media?: bool, limit?: int|null, dry_run?: bool}  $options
     */
    public function autoSync(array $options = []): array
    {
        $config = config('legacy-import.auto_sync');

        $importNew = $options['import_new'] ?? $config['import_new'] ?? true;
        $updateExisting = $options['update_existing'] ?? $config['update_existing'] ?? true;
        $syncMedia = $options['sync_media'] ?? $config['sync_media'] ?? true;
        $limit = $options['limit'] ?? $config['max_per_run'] ?? 250;
        $dryRun = $options['dry_run'] ?? false;

        $this->withMedia($syncMedia);
        $this->initializeStats();

        $changes = $this->detectChanges();

        $targets = [];
        if ($importNew) {
            $targets = array_column($changes['new'], 'legacy_id');
        }
        if ($updateExisting) {
            $targets = array_merge($targets, array_column($changes['updated'], 'legacy_id'));
        }

        $detected = count($targets);
        $deferred = 0;

        // Safety valve: an unexpectedly large drift is reported, not applied.
        if ($limit && $detected > $limit) {
            $deferred = $detected - $limit;
            $targets = array_slice($targets, 0, $limit);
            $this->warnings[] = "Drift of {$detected} contracts exceeds the {$limit}-per-run limit; ".
                "{$deferred} deferred to the next run.";
        }

        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'detected' => [
                    'new' => count($changes['new']),
                    'updated' => count($changes['updated']),
                    'deleted_in_legacy' => count($changes['deleted_in_legacy']),
                    'unchanged' => $changes['unchanged'],
                ],
                'would_sync' => $targets,
                'deferred' => $deferred,
                'changes' => $changes,
                'stats' => $this->syncStats,
                'errors' => [],
                'warnings' => $this->warnings,
            ];
        }

        // Captured before syncContracts(), which resets warnings via initializeStats().
        $preSyncWarnings = $this->warnings;

        $result = empty($targets)
            ? ['success' => true, 'stats' => $this->syncStats, 'errors' => [], 'warnings' => $preSyncWarnings]
            : $this->syncContracts($targets, true);

        $result['warnings'] = array_values(array_unique(array_merge($preSyncWarnings, $result['warnings'] ?? [])));

        $result['detected'] = [
            'new' => count($changes['new']),
            'updated' => count($changes['updated']),
            'deleted_in_legacy' => count($changes['deleted_in_legacy']),
            'unchanged' => $changes['unchanged'],
        ];
        $result['changes'] = $changes;
        $result['deferred'] = $deferred;
        $result['dry_run'] = false;
        $result['deleted_handling'] = $this->handleDeletedInLegacy($changes['deleted_in_legacy']);

        return $result;
    }

    /**
     * Act on contracts that were soft-deleted upstream.
     *
     * Default strategy is 'report': the run summary names them and nothing
     * local is touched, because CAP TRACK reports and attachments may hang off
     * those contracts. 'archive' hides them locally but keeps all data.
     *
     * @param  array<int, array<string, mixed>>  $deleted
     */
    protected function handleDeletedInLegacy(array $deleted): array
    {
        $strategy = config('legacy-import.auto_sync.deleted_in_legacy', 'report');

        if ($strategy !== 'archive' || empty($deleted)) {
            return ['strategy' => $strategy, 'archived' => 0, 'legacy_ids' => array_column($deleted, 'legacy_id')];
        }

        $archived = 0;

        foreach ($deleted as $row) {
            if ($row['archived']) {
                continue;
            }

            $contract = Contract::find($row['contract_id']);

            if (! $contract) {
                continue;
            }

            $contract->update([
                'archived' => true,
                'date_archived' => now(),
                'updated_by' => $this->actorId(),
            ]);
            $archived++;
        }

        return ['strategy' => $strategy, 'archived' => $archived, 'legacy_ids' => array_column($deleted, 'legacy_id')];
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
