<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SageIntacctSyncService
{
    private SageIntacctApiService $apiService;

    private $setting;

    private $userToken;

    /**
     * Constructor - accepts SageIntacctApiService, SageIntacctSetting, and UserSageIntacctToken instances
     */
    public function __construct(SageIntacctApiService $apiService, $setting, $userToken = null)
    {
        $this->apiService = $apiService;
        $this->setting = $setting;
        $this->userToken = $userToken ?? $apiService->getUserToken();
    }

    /**
     * Sync gifts for a specific date range
     *
     * @param  Carbon  $startDate  Start date for gift sync
     * @param  Carbon  $endDate  End date for gift sync
     * @param  int|null  $userId  User ID who triggered the sync
     * @param  array|null  $specificGifts  Optional array of specific gifts to sync (if null, syncs all unsynced)
     * @return array Result with success boolean, statistics, and optional error
     */
    public function syncGifts(Carbon $startDate, Carbon $endDate, ?int $userId = null, ?array $specificGifts = null): array
    {
        try {
            // Sandbox safety check
            $isSandbox = $this->userToken ? $this->userToken->isSandbox() : $this->setting->isSandbox();

            if (! $isSandbox) {
                Log::warning('Sage Intacct: Syncing to non-sandbox environment', [
                    'company_id' => $this->setting->company_id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ]);
            }

            Log::info('Sage Intacct: Starting gift sync', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'user_id' => $userId ?? ($this->userToken ? $this->userToken->user_id : 'system'),
                'is_sandbox' => $isSandbox,
                'auth_method' => $this->setting->auth_method ?? 'xml',
                'specific_gifts_provided' => $specificGifts !== null,
                'specific_gifts_count' => $specificGifts ? count($specificGifts) : 0,
            ]);

            // Capture environment information
            $environmentInfo = $this->captureEnvironmentInfo();

            // Capture configuration snapshot
            $configurationSnapshot = $this->captureConfigurationSnapshot();

            // Create sync history record with detailed information
            $syncHistory = $this->createSyncHistory(
                $userId ?? ($this->userToken ? $this->userToken->user_id : 0),
                'manual',
                0, // Will be updated after we get the gifts
                $startDate,
                $endDate,
                $environmentInfo,
                $configurationSnapshot
            );

            if (! $syncHistory) {
                return [
                    'success' => false,
                    'error' => 'Failed to create sync history record',
                ];
            }

            // Get gifts to sync - use specific gifts if provided, otherwise get all unsynced
            if ($specificGifts !== null) {
                // Convert collection to array if needed
                $gifts = is_array($specificGifts) ? $specificGifts : $specificGifts->toArray();

                Log::info('Sage Intacct: Using specific gifts provided by caller', [
                    'count' => count($gifts),
                ]);
            } else {
                // Get all gifts from DonorPerfect (don't filter by local sync status)
                $dpGiftService = new DonorPerfectGiftService;
                $result = $dpGiftService->getCachedGifts(
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                );

                if (! $result['success']) {
                    throw new \Exception($result['error'] ?? 'Failed to get gifts');
                }

                $gifts = $result['gifts'] ?? [];

                Log::info('Sage Intacct: Retrieved all gifts from date range', [
                    'count' => count($gifts),
                ]);
            }

            if (empty($gifts)) {
                $this->updateSyncHistory($syncHistory, 'success', null);

                Log::info('Sage Intacct: No gifts to sync', [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ]);

                return [
                    'success' => true,
                    'total' => 0,
                    'synced' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'message' => 'No gifts to sync for the specified date range',
                ];
            }

            // Query Intacct for existing records (only for custom object, not Other Receipts)
            $existingRecords = [];
            $categorized = [];

            if ($this->setting->use_other_receipts) {
                // For Other Receipts: Create all as new receipts (no query-compare)
                Log::info('Sage Intacct: Using Other Receipts mode - creating all as new receipts', [
                    'gift_count' => count($gifts),
                ]);

                $categorized = [
                    'create' => $gifts,
                    'update' => [],
                    'skip' => [],
                ];
            } else {
                // For Custom Object: Use query-compare-sync logic
                $existingRecords = $this->getExistingIntacctRecords($gifts);
                $categorized = $this->categorizeGifts($gifts, $existingRecords);
            }

            $totalRecords = count($gifts);
            $toCreate = count($categorized['create']);
            $toUpdate = count($categorized['update']);
            $toSkip = count($categorized['skip']);

            // Update total records in sync history
            DB::table('sage_intacct_sync_history')
                ->where('id', $syncHistory->id)
                ->update([
                    'total_records' => $totalRecords,
                    'skipped_count' => $toSkip,
                ]);

            Log::info('Sage Intacct: Sync plan determined', [
                'total' => $totalRecords,
                'to_create' => $toCreate,
                'to_update' => $toUpdate,
                'to_skip' => $toSkip,
            ]);

            // Process CREATE operations in batches
            $totalSynced = 0;
            $totalFailed = 0;
            $failedRecords = [];

            if (! empty($categorized['create'])) {
                $batchSize = 100;
                $batches = array_chunk($categorized['create'], $batchSize);

                foreach ($batches as $batchIndex => $batch) {
                    Log::info('Sage Intacct: Processing CREATE batch', [
                        'batch_number' => $batchIndex + 1,
                        'batch_size' => count($batch),
                        'total_batches' => count($batches),
                    ]);

                    $batchResult = $this->syncBatch($batch, $syncHistory);

                    $totalSynced += $batchResult['synced'];
                    $totalFailed += $batchResult['failed'];
                    $failedRecords = array_merge($failedRecords, $batchResult['failed_count']);

                    // Update sync history progress
                    DB::table('sage_intacct_sync_history')
                        ->where('id', $syncHistory->id)
                        ->update([
                            'successful_count' => $totalSynced,
                            'failed_count' => $totalFailed,
                        ]);
                }
            }

            // Process UPDATE operations individually
            if (! empty($categorized['update'])) {
                Log::info('Sage Intacct: Processing UPDATE operations', [
                    'count' => count($categorized['update']),
                ]);

                foreach ($categorized['update'] as $gift) {
                    $giftId = $gift['gift_id'] ?? 'unknown';

                    Log::debug('Sage Intacct: About to update gift', [
                        'gift_id' => $giftId,
                        'has_record_key' => isset($gift['_intacct_record_key']),
                        'changes' => $gift['_changes'] ?? [],
                    ]);

                    try {
                        $updateResult = $this->updateGiftRecord($gift, $syncHistory);

                        Log::debug('Sage Intacct: Update result received', [
                            'gift_id' => $giftId,
                            'success' => $updateResult['success'],
                            'error' => $updateResult['error'] ?? null,
                        ]);

                        if ($updateResult['success']) {
                            $totalSynced++;
                        } else {
                            $totalFailed++;
                            $failedRecords[] = [
                                'gift_id' => $giftId,
                                'error' => $updateResult['error'] ?? 'Update failed',
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::error('Sage Intacct: Exception during updateGiftRecord', [
                            'gift_id' => $giftId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        $totalFailed++;
                        $failedRecords[] = [
                            'gift_id' => $giftId,
                            'error' => 'Exception: '.$e->getMessage(),
                        ];
                    }
                }
            }

            // Update sync history status
            $finalStatus = $totalFailed > 0 ? 'partial' : 'success';
            $errorMessage = $totalFailed > 0 ? "Failed to sync {$totalFailed} records" : null;
            $this->updateSyncHistory($syncHistory, $finalStatus, $errorMessage);

            Log::info('Sage Intacct: Gift sync completed', [
                'total' => $totalRecords,
                'created' => $toCreate - $totalFailed, // Subtract failures
                'updated' => $toUpdate,
                'skipped' => $toSkip,
                'failed' => $totalFailed,
            ]);

            return [
                'success' => true,
                'total' => $totalRecords,
                'synced' => $totalSynced,
                'failed' => $totalFailed,
                'skipped' => $toSkip,
                'created' => $toCreate,
                'updated' => $toUpdate,
                'failed_count' => $failedRecords,
                'sync_history_id' => $syncHistory->id,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct syncGifts exception', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync history if it exists
            if (isset($syncHistory)) {
                $this->updateSyncHistory($syncHistory, 'failed', $e->getMessage());
            }

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
            ];
        }
    }

    /**
     * Sync all unsynced gifts
     *
     * @param  int|null  $userId  User ID who triggered the sync
     * @return array Result with success boolean, statistics, and optional error
     */
    public function syncUnsyncedGifts(?int $userId = null): array
    {
        try {
            Log::info('Sage Intacct: Starting unsynced gifts sync', [
                'user_id' => $userId,
            ]);

            // Get all unsynced gifts from sync items table
            $unsyncedGifts = DB::table('sage_intacct_sync_items')
                ->where('status', 'pending')
                ->orWhere('status', 'failed')
                ->get();

            if ($unsyncedGifts->isEmpty()) {
                Log::info('Sage Intacct: No unsynced gifts found');

                return [
                    'success' => true,
                    'total' => 0,
                    'synced' => 0,
                    'failed' => 0,
                    'message' => 'No unsynced gifts found',
                ];
            }

            // Create sync history record
            $syncHistory = $this->createSyncHistory(
                $userId ?? ($this->userToken ? $this->userToken->user_id : 0),
                $userId ? 'manual_retry' : 'automatic_retry',
                $unsyncedGifts->count()
            );

            if (! $syncHistory) {
                return [
                    'success' => false,
                    'error' => 'Failed to create sync history record',
                ];
            }

            // Transform unsynced items to gift data format
            $gifts = $unsyncedGifts->map(function ($item) {
                return json_decode($item->gift_data, true);
            })->toArray();

            Log::info('Sage Intacct: Found unsynced gifts', [
                'count' => count($gifts),
            ]);

            // Process gifts in batches of 100
            $batchSize = 100;
            $batches = array_chunk($gifts, $batchSize);
            $totalSynced = 0;
            $totalFailed = 0;
            $failedRecords = [];

            foreach ($batches as $batchIndex => $batch) {
                Log::info('Sage Intacct: Processing unsynced batch', [
                    'batch_number' => $batchIndex + 1,
                    'batch_size' => count($batch),
                    'total_batches' => count($batches),
                ]);

                $batchResult = $this->syncBatch($batch, $syncHistory);

                $totalSynced += $batchResult['synced'];
                $totalFailed += $batchResult['failed'];
                $failedRecords = array_merge($failedRecords, $batchResult['failed_count']);

                // Update sync history progress
                DB::table('sage_intacct_sync_history')
                    ->where('id', $syncHistory->id)
                    ->update([
                        'successful_count' => $totalSynced,
                        'failed_count' => $totalFailed,
                    ]);
            }

            // Update sync history status
            $finalStatus = $totalFailed > 0 ? 'partial' : 'success';
            $errorMessage = $totalFailed > 0 ? "Failed to sync {$totalFailed} records" : null;
            $this->updateSyncHistory($syncHistory, $finalStatus, $errorMessage);

            Log::info('Sage Intacct: Unsynced gifts sync completed', [
                'total' => count($gifts),
                'synced' => $totalSynced,
                'failed' => $totalFailed,
            ]);

            return [
                'success' => true,
                'total' => count($gifts),
                'synced' => $totalSynced,
                'failed' => $totalFailed,
                'failed_count' => $failedRecords,
                'sync_history_id' => $syncHistory->id,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct syncUnsyncedGifts exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync history if it exists
            if (isset($syncHistory)) {
                $this->updateSyncHistory($syncHistory, 'failed', $e->getMessage());
            }

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
            ];
        }
    }

    /**
     * Sync a single gift
     *
     * @param  array  $giftData  Gift data from DonorPerfect
     * @param  int|null  $userId  User ID who triggered the sync
     * @return array Result with success boolean and optional error
     */
    public function syncSingleGift(array $giftData, ?int $userId = null): array
    {
        try {
            Log::info('Sage Intacct: Syncing single gift', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'user_id' => $userId,
            ]);

            // Create a temporary sync history for this single gift
            $syncHistory = $this->createSyncHistory(
                $userId ?? ($this->userToken ? $this->userToken->user_id : 0),
                $userId ? 'manual_single' : 'automatic_single',
                1
            );

            if (! $syncHistory) {
                return [
                    'success' => false,
                    'error' => 'Failed to create sync history record',
                ];
            }

            // Transform gift data
            $fieldMappings = $this->setting->fieldMappings()->orderBy('order')->get()->toArray();
            $transformedGift = $this->transformGiftForIntacct($giftData, $fieldMappings);

            // Create sync item record
            $syncItem = $this->createSyncItem($syncHistory, $giftData, 'pending');

            // Send to Intacct
            $result = $this->apiService->createCustomObjectRecord(
                $this->setting->custom_object_name,
                $transformedGift
            );

            if ($result['success']) {
                // Update sync item as synced
                $this->updateSyncItem($syncItem, 'success', null, $result['key'] ?? null);

                // Update sync history
                DB::table('sage_intacct_sync_history')
                    ->where('id', $syncHistory->id)
                    ->update(['successful_count' => 1]);
                $this->updateSyncHistory($syncHistory, 'success', null);

                Log::info('Sage Intacct: Single gift synced successfully', [
                    'gift_id' => $giftData['gift_id'] ?? 'unknown',
                    'intacct_key' => $result['key'] ?? null,
                ]);

                return [
                    'success' => true,
                    'intacct_key' => $result['key'] ?? null,
                ];
            } else {
                // Update sync item as failed
                $errorMessage = $result['error'] ?? 'Unknown error';
                $this->updateSyncItem($syncItem, 'failed', $errorMessage);

                // Update sync history
                DB::table('sage_intacct_sync_history')
                    ->where('id', $syncHistory->id)
                    ->update(['failed_count' => 1]);
                $this->updateSyncHistory($syncHistory, 'failed', $errorMessage);

                Log::error('Sage Intacct: Single gift sync failed', [
                    'gift_id' => $giftData['gift_id'] ?? 'unknown',
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Sage Intacct syncSingleGift exception', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync history if it exists
            if (isset($syncHistory)) {
                $this->updateSyncHistory($syncHistory, 'failed', $e->getMessage());
            }

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get syncable gifts from DonorPerfect for a date range
     *
     * @param  Carbon  $startDate  Start date
     * @param  Carbon  $endDate  End date
     * @return array Array of gift data
     */
    public function getSyncableGifts(Carbon $startDate, Carbon $endDate): array
    {
        try {
            // Get DonorPerfect setting and service
            $dpSetting = DB::table('donor_perfect_settings')
                ->where('is_active', true)
                ->first();

            if (! $dpSetting) {
                Log::warning('Sage Intacct: No active DonorPerfect settings found');

                return [];
            }

            // Create DonorPerfect setting model instance
            $dpSettingModel = new \App\Models\DonorPerfectSetting((array) $dpSetting);

            // Get gifts from cache
            $dpGiftService = new DonorPerfectGiftService;
            $gifts = $dpGiftService->getCachedGifts(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (! $gifts['success']) {
                Log::warning('Sage Intacct: Failed to get DonorPerfect gifts', [
                    'error' => $gifts['error'] ?? 'Unknown error',
                ]);

                return [];
            }

            // Filter out gifts that have already been synced successfully
            $syncedGiftIds = DB::table('sage_intacct_sync_items')
                ->where('status', 'success')
                ->pluck('gift_id')
                ->toArray();

            $unsyncedGifts = array_filter($gifts['gifts'] ?? [], function ($gift) use ($syncedGiftIds) {
                $giftId = $gift['gift_id'] ?? null;

                return $giftId && ! in_array($giftId, $syncedGiftIds);
            });

            Log::info('Sage Intacct: Filtered syncable gifts', [
                'total_gifts' => count($gifts['gifts'] ?? []),
                'already_synced' => count($syncedGiftIds),
                'syncable' => count($unsyncedGifts),
            ]);

            return array_values($unsyncedGifts);

        } catch (\Exception $e) {
            Log::error('Sage Intacct getSyncableGifts exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Transform gift data for Sage Intacct using field mappings
     *
     * @param  array  $giftData  Raw gift data from DonorPerfect
     * @param  array  $fieldMappings  Field mappings from settings
     * @return array Transformed data ready for Intacct
     */
    public function transformGiftForIntacct(array $giftData, array $fieldMappings): array
    {
        $transformed = [];

        foreach ($fieldMappings as $mapping) {
            $dpField = $mapping['donor_perfect_field'] ?? null;
            $intacctField = $mapping['intacct_field'] ?? null;

            if (! $dpField || ! $intacctField) {
                continue;
            }

            // Get value from gift data
            $value = $giftData[$dpField] ?? null;

            // Apply any transformations if specified
            if ($value !== null && isset($mapping['transformation'])) {
                $value = $this->applyTransformation($value, $mapping['transformation']);
            }

            // Apply code mappings if available (GL, Campaign, Solicit, Sub-Solicit)
            if ($value !== null && $this->setting->isCodeMappingEnabled()) {
                $value = $this->applyCodeMapping($dpField, $value);
            }

            // Only include non-null values
            if ($value !== null) {
                $transformed[$intacctField] = $value;
            }
        }

        Log::debug('Sage Intacct: Transformed gift data', [
            'gift_id' => $giftData['gift_id'] ?? 'unknown',
            'mapped_fields' => count($transformed),
            'fields' => array_keys($transformed),
        ]);

        return $transformed;
    }

    /**
     * Transform DonorPerfect gift data to Sage Intacct Other Receipt format
     *
     * @param  array  $giftData  Gift data from DonorPerfect
     * @param  array|null  $fileData  Optional file data for attachment
     * @return array|null Other Receipt data ready for API, or null if gift should be skipped
     */
    private function transformGiftToOtherReceipt(array $giftData, ?array $fileData = null): ?array
    {
        // Build payee name from donor info
        $firstName = trim($giftData['first_name'] ?? '');
        $lastName = trim($giftData['last_name'] ?? '');
        $payee = trim($firstName.' '.$lastName) ?: 'Anonymous Donor';

        // Get payment method from mapping table (with gift_type fallback)
        $dpPaymentType = $giftData['PAYMENT_TYPE'] ?? $giftData['payment_type'] ?? null;

        // If payment_type is empty, try gift_type as fallback
        if (empty($dpPaymentType) && ! empty($giftData['gift_type'])) {
            $dpPaymentType = $giftData['gift_type'];
            Log::debug('Sage Intacct: Using gift_type as payment_type fallback', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'gift_type' => $giftData['gift_type'],
            ]);
        }

        $paymentMethod = \App\Models\DonorPerfectPaymentMethodMapping::getIntacctPaymentMethod($dpPaymentType);

        // Get GL account (apply code mapping if enabled)
        $glCode = $giftData['gl_code'] ?? null;
        if ($this->setting->isCodeMappingEnabled() && $glCode) {
            $glCode = $this->applyCodeMapping('gl_code', $glCode);
        }

        // For Other Receipts, GL account is required and cannot use default
        // If no GL code provided or mapping failed, return null to skip this gift
        if (! $glCode) {
            Log::warning('Sage Intacct: Skipping gift - missing GL code for Other Receipt', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'gl_code_raw' => $giftData['gl_code'] ?? null,
            ]);

            return null;
        }

        $glAccount = $glCode;

        // Prepare dates
        $giftDate = \Carbon\Carbon::parse($giftData['gift_date'] ?? now())->format('Y-m-d');
        $depositDate = now()->format('Y-m-d'); // Current date per user requirement

        // Build account field (bank or undeposited funds)
        $accountData = [];
        if ($this->setting->use_undeposited_funds) {
            $accountData['undepglaccountno'] = $this->setting->undeposited_gl_account ?? '10300';
        } else {
            $accountData['bankaccountid'] = $this->setting->bank_account_id;
            $accountData['depositdate'] = $depositDate;
        }

        // Get dimension values (Location, Project, Class/Service) - apply code mappings
        $location = null;
        $project = null;
        $class = null;

        if ($this->setting->isCodeMappingEnabled()) {
            // STEP 1: Map campaign → location first
            if (! empty($giftData['campaign'])) {
                $location = $this->applyCodeMapping('campaign', $giftData['campaign']);
            }

            // STEP 2: Check if this location has a campaign override (conditional mapping)
            $campaignOverride = null;
            if ($location) {
                $campaignOverride = \App\Models\CampaignServiceOverride::where('intacct_location_code', $location)
                    ->where('is_active', true)
                    ->first();

                if ($campaignOverride) {
                    Log::info('Sage Intacct: Using campaign-based override for location', [
                        'gift_id' => $giftData['gift_id'] ?? 'unknown',
                        'dp_campaign' => $giftData['campaign'] ?? null,
                        'mapped_location' => $location,
                        'override_service' => $campaignOverride->service_code,
                        'override_project' => $campaignOverride->project_code,
                    ]);
                }
            }

            // STEP 3: Apply project mapping (use override if available)
            if ($campaignOverride && $campaignOverride->project_code) {
                $project = $campaignOverride->project_code;
            } elseif (! empty($giftData['solicit_code'])) {
                $project = $this->applyCodeMapping('solicit_code', $giftData['solicit_code']);
            }

            // STEP 4: Apply service/class mapping (use override if available)
            if ($campaignOverride) {
                $class = $campaignOverride->service_code;
            } elseif (! empty($giftData['sub_solicit_code'])) {
                $class = $this->applyCodeMapping('sub_solicit_code', $giftData['sub_solicit_code']);
            }
        }

        // Validate required dimensions are present (skip if any are missing)
        if (! $location || ! $project || ! $class) {
            Log::warning('Sage Intacct: Skipping gift - missing required dimensions', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'location' => $location ?: 'MISSING',
                'project' => $project ?: 'MISSING',
                'class' => $class ?: 'MISSING',
                'campaign' => $giftData['campaign'] ?? null,
                'solicit_code' => $giftData['solicit_code'] ?? null,
                'sub_solicit_code' => $giftData['sub_solicit_code'] ?? null,
            ]);

            return null;
        }

        // Build line item with dimensions
        // CRITICAL: Field order matters! departmentid must come before projectid in XML
        // CRITICAL: GL account 40100 requires BOTH departmentid (Service) AND classid (Restriction)
        $lineItem = [
            'glaccountno' => $glAccount,
            'amount' => $giftData['amount'] ?? '0.00',
            'memo' => $giftData['gift_narrative'] ?? $giftData['campaign'] ?? 'Donation',
            'locationid' => $location,       // Location dimension → "Program" in UI
            'departmentid' => $class,        // Department dimension → "Service" in UI
            'projectid' => $project,         // Project dimension → "Fund" in UI
            'classid' => '0',                // Class dimension → "Restriction" in UI (0 = Unrestricted)
        ];

        // Build receipt data
        $receiptData = array_merge([
            'paymentdate' => $giftDate,
            'payee' => $payee,
            'receiveddate' => $giftDate,
            'paymentmethod' => $paymentMethod,
            'refid' => (string) $giftData['gift_id'],
            'description' => $giftData['gift_narrative'] ?? "Gift from DonorPerfect #{$giftData['gift_id']}",
            'receiptitems' => [$lineItem],
        ], $accountData);

        // Add SUPDOC if file attachment was prepared
        if ($fileData && isset($fileData['supdocid'])) {
            $receiptData['supdocid'] = $fileData['supdocid'];
        }

        Log::debug('Sage Intacct: Transformed gift to Other Receipt', [
            'gift_id' => $giftData['gift_id'] ?? 'unknown',
            'payee' => $payee,
            'payment_method' => $paymentMethod,
            'dp_payment_type' => $dpPaymentType,
            'gl_account' => $glAccount,
            'amount' => $giftData['amount'] ?? '0.00',
        ]);

        return $receiptData;
    }

    /**
     * Create sync history record
     *
     * @param  int  $userId  User ID who triggered the sync
     * @param  string  $triggeredBy  How the sync was triggered
     * @param  int  $totalRecords  Total records to sync
     * @param  Carbon|null  $startDate  Sync start date
     * @param  Carbon|null  $endDate  Sync end date
     * @param  array|null  $environmentInfo  Environment information
     * @param  array|null  $configurationSnapshot  Configuration snapshot
     * @return mixed Sync history model instance or null
     */
    public function createSyncHistory(
        int $userId,
        string $triggeredBy,
        int $totalRecords,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?array $environmentInfo = null,
        ?array $configurationSnapshot = null
    ) {
        try {
            $historyId = DB::table('sage_intacct_sync_history')->insertGetId([
                'triggered_by' => 'user:'.$userId,
                'executed_by_user_id' => $userId,
                'status' => 'running',
                'total_records' => $totalRecords,
                'successful_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'started_at' => now(),
                'sync_start_date' => $startDate?->toDateString(),
                'sync_end_date' => $endDate?->toDateString(),
                'environment_info' => $environmentInfo ? json_encode($environmentInfo) : null,
                'configuration_snapshot' => $configurationSnapshot ? json_encode($configurationSnapshot) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Sage Intacct: Created sync history', [
                'id' => $historyId,
                'user_id' => $userId,
                'triggered_by' => $triggeredBy,
                'total_records' => $totalRecords,
                'date_range' => $startDate && $endDate ? $startDate->toDateString().' to '.$endDate->toDateString() : 'N/A',
            ]);

            // Return a simple object with the necessary properties
            return (object) [
                'id' => $historyId,
                'triggered_by' => $triggeredBy,
                'status' => 'running',
                'total_records' => $totalRecords,
                'successful_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'save' => function () {
                    // This is a placeholder - updates should use updateSyncHistory
                    return true;
                },
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct createSyncHistory exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Update sync history record
     *
     * @param  mixed  $history  Sync history instance
     * @param  string  $status  New status
     * @param  string|null  $errorMessage  Optional error message
     */
    public function updateSyncHistory($history, string $status, ?string $errorMessage = null): void
    {
        try {
            $updateData = [
                'status' => $status,
                'updated_at' => now(),
            ];

            if ($status === 'success' || $status === 'partial' || $status === 'failed') {
                $updateData['completed_at'] = now();
            }

            if ($errorMessage !== null) {
                $updateData['error_message'] = $errorMessage;
            }

            DB::table('sage_intacct_sync_history')
                ->where('id', $history->id)
                ->update($updateData);

            Log::info('Sage Intacct: Updated sync history', [
                'id' => $history->id,
                'status' => $status,
                'has_error' => $errorMessage !== null,
            ]);

        } catch (\Exception $e) {
            Log::error('Sage Intacct updateSyncHistory exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get sync statistics
     *
     * @return array Statistics about syncs
     */
    public function getSyncStatistics(): array
    {
        try {
            $stats = [
                'total_syncs' => DB::table('sage_intacct_sync_history')->count(),
                'successful_syncs' => DB::table('sage_intacct_sync_history')
                    ->where('status', 'success')
                    ->count(),
                'failed_syncs' => DB::table('sage_intacct_sync_history')
                    ->where('status', 'failed')
                    ->count(),
                'syncs_with_errors' => DB::table('sage_intacct_sync_history')
                    ->where('status', 'partial')
                    ->count(),
                'total_records_synced' => DB::table('sage_intacct_sync_history')
                    ->sum('successful_count'),
                'total_records_failed' => DB::table('sage_intacct_sync_history')
                    ->sum('failed_count'),
                'pending_items' => DB::table('sage_intacct_sync_items')
                    ->where('status', 'pending')
                    ->count(),
                'failed_items' => DB::table('sage_intacct_sync_items')
                    ->where('status', 'failed')
                    ->count(),
                'last_sync' => DB::table('sage_intacct_sync_history')
                    ->orderBy('created_at', 'desc')
                    ->first(),
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Sage Intacct getSyncStatistics exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Sync a batch of gifts
     *
     * @param  array  $batch  Array of gift data
     * @param  mixed  $syncHistory  Sync history instance
     * @return array Result with synced/failed counts
     */
    private function syncBatch(array $batch, $syncHistory): array
    {
        try {
            $fieldMappings = $this->setting->fieldMappings()->orderBy('order')->get()->toArray();

            // Transform all gifts in batch and prepare attachments
            $transformedRecords = [];
            $giftIdMap = []; // Map index to gift_id for tracking
            $transformedMap = []; // Map gift_id to transformed data
            $attachmentMap = []; // Map gift_id to file data array

            foreach ($batch as $index => $giftData) {
                $giftId = $giftData['gift_id'] ?? null;

                // Prepare check image attachment
                $fileData = $this->uploadCheckImageAttachment($giftData);
                if ($fileData) {
                    $attachmentMap[$giftId] = $fileData;
                }

                // Transform based on destination type
                if ($this->setting->use_other_receipts) {
                    // Transform to Other Receipt format
                    $transformed = $this->transformGiftToOtherReceipt($giftData, $fileData);

                    // Skip if transformation returned null (missing required fields/codes)
                    if ($transformed === null) {
                        $this->createSyncItem($syncHistory, $giftData, 'skipped', [
                            'skip_reason' => 'Missing required GL code or dimensions',
                        ]);

                        continue;
                    }
                } else {
                    // Transform to Custom Object format
                    $transformed = $this->transformGiftForIntacct($giftData, $fieldMappings);

                    // Add Document File attribute fields if attachment was prepared
                    if ($fileData && isset($fileData['base64_content'])) {
                        $fileField = env('INTACCT_DOCUMENT_FILE_FIELD', 'file');
                        $transformed[$fileField.'_contenttype'] = $fileData['content_type'];
                        $transformed[$fileField.'_filename'] = $fileData['filename'];
                        $transformed[$fileField] = $fileData['base64_content'];
                    }
                }

                $transformedRecords[] = $transformed;
                $giftIdMap[$index] = $giftId;
                $transformedMap[$giftId] = $transformed;

                // Create sync item record with transformed data
                $this->createSyncItem($syncHistory, $giftData, 'pending', $transformed);
            }

            // Send batch to Intacct based on destination type
            if ($this->setting->use_other_receipts) {
                Log::info('Sage Intacct: Creating Other Receipts batch', [
                    'batch_size' => count($transformedRecords),
                ]);
                $result = $this->apiService->batchCreateOtherReceipts($transformedRecords);
            } else {
                Log::info('Sage Intacct: Creating custom object batch', [
                    'object_name' => $this->setting->custom_object_name,
                    'batch_size' => count($transformedRecords),
                ]);
                $result = $this->apiService->batchCreateRecords(
                    $this->setting->custom_object_name,
                    $transformedRecords
                );
            }

            $synced = 0;
            $failed = 0;
            $failedRecords = [];

            if ($result['success']) {
                $synced = $result['created'];
                $failed = $result['failed']; // Already an integer, don't count

                // Update sync items based on batch results
                foreach ($result['details'] as $detail) {
                    $index = $detail['index'];
                    $giftId = $giftIdMap[$index] ?? null;

                    if (! $giftId) {
                        continue;
                    }

                    $transformed = $transformedMap[$giftId] ?? [];

                    if ($detail['success']) {
                        $recordKey = $detail['key'] ?? null;

                        // Update as synced with API details
                        DB::table('sage_intacct_sync_items')
                            ->where('sync_history_id', $syncHistory->id)
                            ->where('gift_id', $giftId)
                            ->update([
                                'status' => 'success',
                                'intacct_record_id' => $recordKey,
                                'synced_at' => now(),
                                'api_request' => json_encode($transformed),
                                'api_response' => json_encode($detail),
                                'updated_at' => now(),
                            ]);
                    } else {
                        // Update as failed with API details
                        DB::table('sage_intacct_sync_items')
                            ->where('sync_history_id', $syncHistory->id)
                            ->where('gift_id', $giftId)
                            ->update([
                                'status' => 'failed',
                                'error_message' => $detail['error'] ?? 'Unknown error',
                                'api_request' => json_encode($transformed),
                                'api_response' => json_encode($detail),
                                'updated_at' => now(),
                            ]);

                        $failedRecords[] = [
                            'gift_id' => $giftId,
                            'error' => $detail['error'] ?? 'Unknown error',
                        ];
                    }
                }
            } else {
                // Entire batch failed
                $failed = count($batch);

                foreach ($batch as $giftData) {
                    $giftId = $giftData['gift_id'] ?? null;
                    if (! $giftId) {
                        continue;
                    }

                    $transformed = $transformedMap[$giftId] ?? [];

                    DB::table('sage_intacct_sync_items')
                        ->where('sync_history_id', $syncHistory->id)
                        ->where('gift_id', $giftId)
                        ->update([
                            'status' => 'failed',
                            'error_message' => $result['error'] ?? 'Batch failed',
                            'api_request' => json_encode($transformed),
                            'api_response' => json_encode($result),
                            'updated_at' => now(),
                        ]);

                    $failedRecords[] = [
                        'gift_id' => $giftId,
                        'error' => $result['error'] ?? 'Batch failed',
                    ];
                }
            }

            return [
                'synced' => $synced,
                'failed' => $failed,
                'failed_count' => $failedRecords,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct syncBatch exception', [
                'batch_size' => count($batch),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark all as failed
            foreach ($batch as $giftData) {
                $giftId = $giftData['gift_id'] ?? null;
                if ($giftId) {
                    DB::table('sage_intacct_sync_items')
                        ->where('sync_history_id', $syncHistory->id)
                        ->where('gift_id', $giftId)
                        ->update([
                            'status' => 'failed',
                            'error_message' => 'Exception: '.$e->getMessage(),
                            'updated_at' => now(),
                        ]);
                }
            }

            return [
                'synced' => 0,
                'failed' => count($batch),
                'failed_count' => array_map(function ($gift) use ($e) {
                    return [
                        'gift_id' => $gift['gift_id'] ?? 'unknown',
                        'error' => 'Exception: '.$e->getMessage(),
                    ];
                }, $batch),
            ];
        }
    }

    /**
     * Create sync item record
     *
     * @param  mixed  $syncHistory  Sync history instance
     * @param  array  $giftData  Gift data
     * @param  string  $status  Initial status
     * @param  array|null  $transformedData  Transformed data for Intacct
     * @return mixed Sync item instance
     */
    private function createSyncItem($syncHistory, array $giftData, string $status, ?array $transformedData = null)
    {
        try {
            $itemId = DB::table('sage_intacct_sync_items')->insertGetId([
                'sync_history_id' => $syncHistory->id,
                'gift_id' => $giftData['gift_id'] ?? null,
                'gift_cache_id' => null, // TODO: Link to gift cache if needed
                'gift_data' => json_encode($giftData),
                'transformed_data' => $transformedData ? json_encode($transformedData) : null,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (object) [
                'id' => $itemId,
                'sync_history_id' => $syncHistory->id,
                'gift_id' => $giftData['gift_id'] ?? null,
                'status' => $status,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct createSyncItem exception', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update sync item record
     *
     * @param  mixed  $syncItem  Sync item instance
     * @param  string  $status  New status
     * @param  string|null  $errorMessage  Optional error message
     * @param  string|null  $intacctKey  Optional Intacct record key
     * @param  array|null  $apiRequest  Optional API request data
     * @param  array|null  $apiResponse  Optional API response data
     */
    private function updateSyncItem(
        $syncItem,
        string $status,
        ?string $errorMessage = null,
        ?string $intacctKey = null,
        ?array $apiRequest = null,
        ?array $apiResponse = null
    ): void {
        try {
            $updateData = [
                'status' => $status,
                'updated_at' => now(),
            ];

            if ($status === 'success') {
                $updateData['synced_at'] = now();
            }

            if ($errorMessage !== null) {
                $updateData['error_message'] = $errorMessage;
            }

            if ($intacctKey !== null) {
                $updateData['intacct_record_id'] = $intacctKey;
            }

            if ($apiRequest !== null) {
                $updateData['api_request'] = json_encode($apiRequest);
            }

            if ($apiResponse !== null) {
                $updateData['api_response'] = json_encode($apiResponse);
            }

            DB::table('sage_intacct_sync_items')
                ->where('id', $syncItem->id)
                ->update($updateData);

        } catch (\Exception $e) {
            Log::error('Sage Intacct updateSyncItem exception', [
                'sync_item_id' => $syncItem->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prepare check image data for Document File attribute
     *
     * @param  array  $giftData  Gift data with IMAGE_CHECK_URL
     * @return array|null Returns file data array on success, null on failure
     */
    private function uploadCheckImageAttachment(array $giftData): ?array
    {
        try {
            // Check if check image uploads are enabled
            if (! $this->setting->include_check_images ?? false) {
                return null;
            }

            // Check if gift has a cached check image URL
            if (empty($giftData['IMAGE_CHECK_URL'])) {
                Log::debug('Sage Intacct: Gift has no cached check image, skipping attachment', [
                    'gift_id' => $giftData['gift_id'] ?? 'unknown',
                ]);

                return null;
            }

            $giftId = $giftData['gift_id'] ?? 'unknown';

            // Use CheckImageCacheService to retrieve the cached image
            $checkImageService = new \App\Services\CheckImageCacheService;

            // Extract filename from gift data (format: giftid_filename.ext)
            $cacheFilename = basename(parse_url($giftData['IMAGE_CHECK_URL'], PHP_URL_PATH));
            $storagePath = 'check_images/'.str_replace('.enc', '', $cacheFilename);

            // Decrypt the cached file
            $decryptResult = $checkImageService->decryptFile($storagePath);

            if (! $decryptResult['success']) {
                Log::warning('Sage Intacct: Failed to decrypt check image, skipping attachment', [
                    'gift_id' => $giftId,
                    'error' => $decryptResult['error'] ?? 'Unknown error',
                ]);

                return null;
            }

            $fileContents = $decryptResult['contents'];
            $metadata = $decryptResult['metadata'] ?? [];

            // Check file size against limit
            $fileSizeMB = strlen($fileContents) / 1024 / 1024;
            $maxSizeMB = $this->setting->attachment_max_size_mb ?? 10;

            if ($fileSizeMB > $maxSizeMB) {
                Log::warning('Sage Intacct: Check image too large for attachment', [
                    'gift_id' => $giftId,
                    'size_mb' => round($fileSizeMB, 2),
                    'max_mb' => $maxSizeMB,
                ]);

                return null;
            }

            // Extract file info - use basename to get just the filename, not full path
            $originalName = $metadata['original_name'] ?? $cacheFilename;
            $baseFilename = basename($originalName); // Strip any directory path
            $pathInfo = pathinfo($baseFilename);
            $fileName = $pathInfo['filename'] ?? 'check_'.$giftId;
            $fileExtension = strtolower($pathInfo['extension'] ?? 'jpg'); // Lowercase extension

            // Convert TIF/TIFF to JPEG for better compatibility and smaller size
            if (in_array($fileExtension, ['tif', 'tiff'])) {
                try {
                    // Write temp file for conversion (must have .tif extension for convertTiffToJpeg)
                    $tempTif = tempnam(sys_get_temp_dir(), 'tif_').'.tif';
                    file_put_contents($tempTif, $fileContents);

                    // Use CheckImageCacheService conversion method
                    $convertResult = $checkImageService->convertTiffToJpeg($tempTif);

                    Log::debug('Sage Intacct: Conversion result', [
                        'gift_id' => $giftId,
                        'success' => $convertResult['success'],
                        'optimized' => $convertResult['optimized'] ?? false,
                        'path' => $convertResult['path'] ?? null,
                        'path_exists' => isset($convertResult['path']) ? file_exists($convertResult['path']) : false,
                        'temp_tif_exists' => file_exists($tempTif),
                    ]);

                    if ($convertResult['success'] && $convertResult['optimized']) {
                        // Read converted JPEG
                        if (file_exists($convertResult['path'])) {
                            $fileContents = file_get_contents($convertResult['path']);
                            $fileExtension = 'jpg';

                            Log::info('Sage Intacct: Converted TIF to JPEG before upload', [
                                'gift_id' => $giftId,
                                'new_size_mb' => round(strlen($fileContents) / 1024 / 1024, 2),
                            ]);

                            // Clean up converted file
                            @unlink($convertResult['path']);
                        } else {
                            Log::warning('Sage Intacct: Converted file path does not exist', [
                                'gift_id' => $giftId,
                                'expected_path' => $convertResult['path'],
                            ]);
                        }
                    }

                    // Clean up temp file (and .jpg if it exists)
                    @unlink($tempTif);
                    $tempJpg = preg_replace('/\.tif$/i', '.jpg', $tempTif);
                    if (file_exists($tempJpg)) {
                        @unlink($tempJpg);
                    }
                } catch (\Exception $e) {
                    Log::warning('Sage Intacct: Failed to convert TIF to JPEG, uploading as-is', [
                        'gift_id' => $giftId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sanitize filename for XML/Intacct (remove special chars)
            $fileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $fileName);
            $fullFilename = 'Gift_'.$giftId.'_'.$fileName.'.'.$fileExtension;

            // Determine MIME type based on file extension
            $contentType = match ($fileExtension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'tif', 'tiff' => 'image/tiff',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream',
            };

            // Check if using Other Receipts (SUPDOC) or Custom Object (Document File)
            if ($this->setting->use_other_receipts) {
                // For Other Receipts: Create SUPDOC and return supdocid
                $description = "Check image for Gift #{$giftId}";
                if (! empty($giftData['first_name']) || ! empty($giftData['last_name'])) {
                    $donorName = trim(($giftData['first_name'] ?? '').' '.($giftData['last_name'] ?? ''));
                    $description .= " - {$donorName}";
                }
                if (! empty($giftData['amount'])) {
                    $description .= ' - $'.number_format($giftData['amount'], 2);
                }

                Log::info('Sage Intacct: Creating SUPDOC for Other Receipt', [
                    'gift_id' => $giftId,
                    'filename' => $fullFilename,
                    'size_mb' => round(strlen($fileContents) / 1024 / 1024, 2),
                ]);

                $supdocResult = $this->apiService->createAttachment(
                    $fullFilename,
                    $fileContents,
                    $fileExtension,
                    $description,
                    $this->setting->attachment_folder_name ?? 'DP_Check_Images'
                );

                if ($supdocResult['success']) {
                    $supdocid = $supdocResult['supdocid'] ?? null;
                    Log::info('Sage Intacct: SUPDOC created for Other Receipt', [
                        'gift_id' => $giftId,
                        'supdocid' => $supdocid,
                    ]);

                    return ['supdocid' => $supdocid];
                } else {
                    Log::error('Sage Intacct: SUPDOC creation failed', [
                        'gift_id' => $giftId,
                        'error' => $supdocResult['error'] ?? 'Unknown error',
                    ]);

                    return null;
                }
            } else {
                // For Custom Object: Return Document File attribute data
                $base64Content = base64_encode($fileContents);

                Log::info('Sage Intacct: Prepared Document File attribute data', [
                    'gift_id' => $giftId,
                    'filename' => $fullFilename,
                    'content_type' => $contentType,
                    'size_mb' => round(strlen($fileContents) / 1024 / 1024, 2),
                    'base64_size_mb' => round(strlen($base64Content) / 1024 / 1024, 2),
                ]);

                return [
                    'content_type' => $contentType,
                    'filename' => $fullFilename,
                    'base64_content' => $base64Content,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Sage Intacct uploadCheckImageAttachment exception', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Update a single gift record in Intacct
     *
     * @param  array  $giftData  Gift data with changes
     * @param  mixed  $syncHistory  Sync history instance
     * @return array Result with success status
     */
    private function updateGiftRecord(array $giftData, $syncHistory): array
    {
        try {
            $giftId = $giftData['gift_id'] ?? 'unknown';
            $recordKey = $giftData['_intacct_record_key'] ?? null;
            $changes = $giftData['_changes'] ?? [];

            if (! $recordKey) {
                return [
                    'success' => false,
                    'error' => 'No record key provided for update',
                ];
            }

            Log::info('Sage Intacct: Updating gift record', [
                'gift_id' => $giftId,
                'record_key' => $recordKey,
                'changes' => $changes,
            ]);

            // Prepare attachment file data if needed
            $fileData = null;
            if (in_array('attachment_added', $changes)) {
                $fileData = $this->uploadCheckImageAttachment($giftData);
                if ($fileData) {
                    Log::info('Sage Intacct: Document File data prepared for update', [
                        'gift_id' => $giftId,
                        'filename' => $fileData['filename'],
                        'content_type' => $fileData['content_type'],
                    ]);
                }
            }

            // Transform gift data
            $fieldMappings = $this->setting->fieldMappings()->orderBy('order')->get()->toArray();
            $transformed = $this->transformGiftForIntacct($giftData, $fieldMappings);

            // Add Document File attribute fields if attachment was prepared
            if ($fileData) {
                // Get Document File field name from config (default: 'file')
                $fileField = env('INTACCT_DOCUMENT_FILE_FIELD', 'file');

                // Populate the three required fields for Document File attribute
                $transformed[$fileField.'_contenttype'] = $fileData['content_type'];
                $transformed[$fileField.'_filename'] = $fileData['filename'];
                $transformed[$fileField] = $fileData['base64_content'];

                Log::info('Sage Intacct: Added Document File attribute fields for update', [
                    'gift_id' => $giftId,
                    'field_name' => $fileField,
                    'filename' => $fileData['filename'],
                    'content_type' => $fileData['content_type'],
                ]);
            }

            // Create sync item record
            $syncItem = $this->createSyncItem($syncHistory, $giftData, 'pending', $transformed);

            Log::debug('Sage Intacct: About to send update to API', [
                'gift_id' => $giftId,
                'object_name' => $this->setting->custom_object_name,
                'record_key' => $recordKey,
                'record_key_length' => strlen($recordKey ?? ''),
                'field_count' => count($transformed),
            ]);

            // Send update to Intacct
            $updateResult = $this->apiService->updateCustomObjectRecord(
                $this->setting->custom_object_name,
                $recordKey,
                $transformed
            );

            if ($updateResult['success']) {
                // Update sync item as synced
                $this->updateSyncItem($syncItem, 'success', null, $recordKey, $transformed, $updateResult);

                Log::info('Sage Intacct: Gift updated successfully', [
                    'gift_id' => $giftId,
                    'record_key' => $recordKey,
                    'had_attachment' => $fileData !== null,
                ]);

                return ['success' => true];
            } else {
                // Update sync item as failed
                $errorMessage = $updateResult['error'] ?? 'Update failed';
                $this->updateSyncItem($syncItem, 'failed', $errorMessage, null, $transformed, $updateResult);

                Log::error('Sage Intacct: Gift update failed', [
                    'gift_id' => $giftId,
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Sage Intacct updateGiftRecord exception', [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get existing records from Sage Intacct for comparison
     *
     * @param  array  $gifts  Array of gift data from DonorPerfect
     * @return array Existing records keyed by gift_id
     */
    private function getExistingIntacctRecords(array $gifts): array
    {
        try {
            $giftIds = array_column($gifts, 'gift_id');

            if (empty($giftIds)) {
                return [];
            }

            Log::info('Sage Intacct: Querying existing records from Intacct', [
                'gift_count' => count($giftIds),
            ]);

            $queryResult = $this->apiService->queryCustomObjectRecords(
                $this->setting->custom_object_name,
                $giftIds
            );

            if (! $queryResult['success']) {
                Log::warning('Sage Intacct: Query failed, assuming no existing records', [
                    'error' => $queryResult['error'] ?? 'Unknown error',
                ]);

                return [];
            }

            Log::info('Sage Intacct: Retrieved existing records', [
                'found_count' => count($queryResult['records']),
            ]);

            return $queryResult['records'];

        } catch (\Exception $e) {
            Log::error('Sage Intacct getExistingIntacctRecords exception', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Detect changes between local gift and Intacct record
     *
     * @param  array  $localGift  Gift data from DonorPerfect
     * @param  array  $intacctRecord  Existing record from Intacct
     * @return array List of changed fields
     */
    private function detectChanges(array $localGift, array $intacctRecord): array
    {
        $changes = [];

        // Compare amount
        $localAmount = number_format((float) ($localGift['amount'] ?? 0), 2, '.', '');
        $intacctAmount = number_format((float) ($intacctRecord['amount'] ?? 0), 2, '.', '');
        if ($localAmount != $intacctAmount) {
            $changes[] = 'amount';
        }

        // Compare gift date
        if (($localGift['gift_date'] ?? '') != ($intacctRecord['gift_date'] ?? '')) {
            $changes[] = 'gift_date';
        }

        // Compare GL code (after mapping)
        $localGlCode = $this->applyCodeMapping('gl_code', $localGift['gl_code'] ?? '');
        if ($localGlCode != ($intacctRecord['gift_gl_code'] ?? '')) {
            $changes[] = 'gl_code';
        }

        // Compare campaign (after mapping)
        $localCampaign = $this->applyCodeMapping('campaign', $localGift['campaign'] ?? '');
        if ($localCampaign != ($intacctRecord['gift_campaign'] ?? '')) {
            $changes[] = 'campaign';
        }

        // Check for new attachment
        if (! empty($localGift['IMAGE_CHECK_URL']) && empty($intacctRecord['SUPDOCID'])) {
            $changes[] = 'attachment_added';
        }

        return $changes;
    }

    /**
     * Categorize gifts into create/update/skip based on Intacct records
     *
     * @param  array  $gifts  Gifts to sync
     * @param  array  $existingRecords  Existing records from Intacct
     * @return array Categorized gifts
     */
    private function categorizeGifts(array $gifts, array $existingRecords): array
    {
        $create = [];
        $update = [];
        $skip = [];

        foreach ($gifts as $gift) {
            $giftId = $gift['gift_id'] ?? null;
            if (! $giftId) {
                continue;
            }

            // Check if exists in Intacct
            if (isset($existingRecords[$giftId])) {
                // Record exists - check for changes
                $changes = $this->detectChanges($gift, $existingRecords[$giftId]);

                if (empty($changes)) {
                    // No changes - skip
                    $skip[] = $gift;
                    Log::debug('Sage Intacct: Gift identical to Intacct record, skipping', [
                        'gift_id' => $giftId,
                    ]);
                } else {
                    // Has changes - update
                    // Note: Sage Intacct returns RECORDNO as 'id' in query results
                    $recordKey = $existingRecords[$giftId]['id'] ?? $existingRecords[$giftId]['RECORDNO'] ?? null;

                    Log::debug('Sage Intacct: Extracting record key for update', [
                        'gift_id' => $giftId,
                        'record_key' => $recordKey,
                        'existing_record_keys' => array_keys($existingRecords[$giftId]),
                    ]);

                    $gift['_intacct_record_key'] = $recordKey;
                    $gift['_changes'] = $changes;
                    $update[] = $gift;
                    Log::info('Sage Intacct: Gift has changes, will update', [
                        'gift_id' => $giftId,
                        'changes' => $changes,
                    ]);
                }
            } else {
                // Record doesn't exist - create
                $create[] = $gift;
                Log::debug('Sage Intacct: Gift not in Intacct, will create', [
                    'gift_id' => $giftId,
                ]);
            }
        }

        Log::info('Sage Intacct: Gifts categorized', [
            'create' => count($create),
            'update' => count($update),
            'skip' => count($skip),
        ]);

        return [
            'create' => $create,
            'update' => $update,
            'skip' => $skip,
        ];
    }

    /**
     * Apply code mapping to translate DonorPerfect codes to Sage Intacct codes
     *
     * @param  string  $dpField  DonorPerfect field name
     * @param  mixed  $value  Original DonorPerfect code
     * @return mixed Mapped Sage Intacct code or original value
     */
    private function applyCodeMapping(string $dpField, $value)
    {
        // Map DonorPerfect field names to code mapping field types
        $fieldTypeMap = [
            'gl_code' => 'gl_code',
            'campaign' => 'campaign',
            'solicit_code' => 'solicit_code',
            'sub_solicit_code' => 'sub_solicit_code',
        ];

        // Check if this field has code mappings
        if (! isset($fieldTypeMap[$dpField])) {
            return $value; // Not a code field, return original value
        }

        $fieldType = $fieldTypeMap[$dpField];

        // Look up the code mapping
        $mapping = \App\Models\DonorPerfectCodeMapping::where('field_type', $fieldType)
            ->where('dp_code', $value)
            ->whereNotNull('intacct_code')
            ->first();

        if ($mapping && ! empty($mapping->intacct_code)) {
            Log::debug('Sage Intacct: Applied code mapping', [
                'field' => $dpField,
                'dp_code' => $value,
                'intacct_code' => $mapping->intacct_code,
            ]);

            return $mapping->intacct_code;
        }

        // No mapping found - log warning and use default for sub-solicitation (Class/Service)
        if ($dpField === 'sub_solicit_code') {
            $defaultClass = $this->setting->default_service_id ?? 'S14999';
            Log::warning('Sage Intacct: No Class/Service mapping found for sub-solicitation code, using default', [
                'field' => $dpField,
                'dp_code' => $value,
                'default_used' => $defaultClass,
                'note' => 'Class dimension (classid) - renamed to "Service" in UI',
            ]);

            return $defaultClass;
        }

        // For other fields, return the original value if no mapping found
        return $value;
    }

    /**
     * Apply transformation to a value
     *
     * @param  mixed  $value  Original value
     * @param  string  $transformation  Transformation type
     * @return mixed Transformed value
     */
    private function applyTransformation($value, string $transformation)
    {
        switch ($transformation) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'trim':
                return trim($value);
            case 'currency':
                // Remove formatting and keep just the number
                return preg_replace('/[^0-9.]/', '', $value);
            case 'date_ymd':
                // Convert to Y-m-d format
                try {
                    return Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return $value;
                }
            case 'date_mdy':
                // Convert to m/d/Y format
                try {
                    return Carbon::parse($value)->format('m/d/Y');
                } catch (\Exception $e) {
                    return $value;
                }
            default:
                return $value;
        }
    }

    /**
     * Capture environment information for debugging
     *
     * @return array Environment details
     */
    private function captureEnvironmentInfo(): array
    {
        return [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'database_connection' => config('database.default'),
            'timestamp' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
    }

    /**
     * Capture configuration snapshot for debugging
     *
     * @return array Configuration details
     */
    private function captureConfigurationSnapshot(): array
    {
        return [
            'setting_id' => $this->setting->id ?? null,
            'company_id' => $this->setting->company_id ?? null,
            'entity_id' => $this->setting->entity_id ?? null,
            'custom_object_name' => $this->setting->custom_object_name ?? null,
            'auth_method' => $this->setting->auth_method ?? 'xml',
            'is_sandbox' => $this->setting->isSandbox() ?? false,
            'code_mapping_enabled' => $this->setting->isCodeMappingEnabled() ?? false,
            'field_mappings_count' => $this->setting->fieldMappings()->count() ?? 0,
            'user_token_id' => $this->userToken->id ?? null,
            'user_id' => $this->userToken->user_id ?? null,
            'has_valid_token' => $this->userToken ? $this->userToken->hasValidToken() : null,
        ];
    }
}
