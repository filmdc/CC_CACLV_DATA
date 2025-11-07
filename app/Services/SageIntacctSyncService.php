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
     * @return array Result with success boolean, statistics, and optional error
     */
    public function syncGifts(Carbon $startDate, Carbon $endDate, ?int $userId = null): array
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
            ]);

            // Create sync history record
            $syncHistory = $this->createSyncHistory(
                $userId ?? ($this->userToken ? $this->userToken->user_id : 0),
                'manual',
                0 // Will be updated after we get the gifts
            );

            if (! $syncHistory) {
                return [
                    'success' => false,
                    'error' => 'Failed to create sync history record',
                ];
            }

            // Get syncable gifts from DonorPerfect
            $gifts = $this->getSyncableGifts($startDate, $endDate);

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
                    'message' => 'No gifts to sync for the specified date range',
                ];
            }

            // Update total records in sync history
            $totalRecords = count($gifts);
            DB::table('sage_intacct_sync_history')
                ->where('id', $syncHistory->id)
                ->update(['total_records' => $totalRecords]);

            Log::info('Sage Intacct: Found gifts to sync', [
                'count' => $totalRecords,
            ]);

            // Process gifts in batches of 100
            $batchSize = 100;
            $batches = array_chunk($gifts, $batchSize);
            $totalSynced = 0;
            $totalFailed = 0;
            $failedRecords = [];

            foreach ($batches as $batchIndex => $batch) {
                Log::info('Sage Intacct: Processing batch', [
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

            Log::info('Sage Intacct: Gift sync completed', [
                'total' => $totalRecords,
                'synced' => $totalSynced,
                'failed' => $totalFailed,
            ]);

            return [
                'success' => true,
                'total' => $totalRecords,
                'synced' => $totalSynced,
                'failed' => $totalFailed,
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
     * Create sync history record
     *
     * @param  int  $userId  User ID who triggered the sync
     * @param  string  $triggeredBy  How the sync was triggered
     * @param  int  $totalRecords  Total records to sync
     * @return mixed Sync history model instance or null
     */
    public function createSyncHistory(int $userId, string $triggeredBy, int $totalRecords)
    {
        try {
            $historyId = DB::table('sage_intacct_sync_history')->insertGetId([
                'triggered_by' => 'user:'.$userId,
                'status' => 'running',
                'total_records' => $totalRecords,
                'successful_count' => 0,
                'failed_count' => 0,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Sage Intacct: Created sync history', [
                'id' => $historyId,
                'user_id' => $userId,
                'triggered_by' => $triggeredBy,
                'total_records' => $totalRecords,
            ]);

            // Return a simple object with the necessary properties
            return (object) [
                'id' => $historyId,
                'triggered_by' => $triggeredBy,
                'status' => 'running',
                'total_records' => $totalRecords,
                'successful_count' => 0,
                'failed_count' => 0,
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

            // Transform all gifts in batch
            $transformedRecords = [];
            $giftIdMap = []; // Map index to gift_id for tracking

            foreach ($batch as $index => $giftData) {
                $transformed = $this->transformGiftForIntacct($giftData, $fieldMappings);
                $transformedRecords[] = $transformed;
                $giftIdMap[$index] = $giftData['gift_id'] ?? null;

                // Create sync item record
                $this->createSyncItem($syncHistory, $giftData, 'pending');
            }

            // Send batch to Intacct
            $result = $this->apiService->batchCreateRecords(
                $this->setting->custom_object_name,
                $transformedRecords
            );

            $synced = 0;
            $failed = 0;
            $failedRecords = [];

            if ($result['success']) {
                $synced = $result['created'];
                $failed = count($result['failed']);

                // Update sync items based on batch results
                foreach ($result['details'] as $detail) {
                    $index = $detail['index'];
                    $giftId = $giftIdMap[$index] ?? null;

                    if (! $giftId) {
                        continue;
                    }

                    if ($detail['success']) {
                        // Update as synced
                        DB::table('sage_intacct_sync_items')
                            ->where('sync_history_id', $syncHistory->id)
                            ->where('gift_id', $giftId)
                            ->update([
                                'status' => 'success',
                                'intacct_record_id' => $detail['key'] ?? null,
                                'synced_at' => now(),
                                'updated_at' => now(),
                            ]);
                    } else {
                        // Update as failed
                        DB::table('sage_intacct_sync_items')
                            ->where('sync_history_id', $syncHistory->id)
                            ->where('gift_id', $giftId)
                            ->update([
                                'status' => 'failed',
                                'error_message' => $detail['error'] ?? 'Unknown error',
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

                    DB::table('sage_intacct_sync_items')
                        ->where('sync_history_id', $syncHistory->id)
                        ->where('gift_id', $giftId)
                        ->update([
                            'status' => 'failed',
                            'error_message' => $result['error'] ?? 'Batch failed',
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
     * @return mixed Sync item instance
     */
    private function createSyncItem($syncHistory, array $giftData, string $status)
    {
        try {
            $itemId = DB::table('sage_intacct_sync_items')->insertGetId([
                'sync_history_id' => $syncHistory->id,
                'gift_id' => $giftData['gift_id'] ?? null,
                'gift_cache_id' => null, // TODO: Link to gift cache if needed
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
     */
    private function updateSyncItem($syncItem, string $status, ?string $errorMessage = null, ?string $intacctKey = null): void
    {
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

        // No mapping found, use original value
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
}
