<?php

namespace App\Services;

use App\Models\DonorPerfectSetting;
use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncItem;
use App\Models\UserSageIntacctToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing deposit dates from Sage Intacct back to DonorPerfect
 *
 * Workflow:
 * 1. Query sync_items for successfully synced gifts that don't have deposit dates synced yet
 * 2. Query Sage Intacct for deposit status of those receipts
 * 3. For receipts that have been deposited, update DonorPerfect's DEPOSIT_DATE field
 * 4. Track the sync status in the database
 */
class DepositDateSyncService
{
    private ?SageIntacctApiService $sageApiService = null;

    private ?DonorPerfectApiService $dpApiService = null;

    private ?SageIntacctSetting $sageSetting = null;

    private ?DonorPerfectSetting $dpSetting = null;

    private ?UserSageIntacctToken $userToken = null;

    /**
     * The DonorPerfect UDF field name for deposit date
     */
    const DP_DEPOSIT_DATE_FIELD = 'DEPOSIT_DATE';

    /**
     * Constructor
     *
     * @param  UserSageIntacctToken|null  $userToken  Optional user token for OAuth-based Sage auth
     */
    public function __construct(?UserSageIntacctToken $userToken = null)
    {
        $this->userToken = $userToken;
        $this->initialize();
    }

    /**
     * Initialize the service with settings and API services
     */
    private function initialize(): void
    {
        $this->sageSetting = SageIntacctSetting::getActive();
        $this->dpSetting = DonorPerfectSetting::getActive();

        if ($this->sageSetting) {
            $this->sageApiService = new SageIntacctApiService($this->sageSetting, $this->userToken);
        }

        if ($this->dpSetting && $this->dpSetting->isConfigured()) {
            $this->dpApiService = new DonorPerfectApiService($this->dpSetting);
        }
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->sageSetting !== null
            && $this->dpSetting !== null
            && $this->dpSetting->isConfigured()
            && $this->sageApiService !== null
            && $this->dpApiService !== null;
    }

    /**
     * Get configuration status details
     */
    public function getConfigurationStatus(): array
    {
        return [
            'sage_configured' => $this->sageSetting !== null,
            'dp_configured' => $this->dpSetting !== null && $this->dpSetting->isConfigured(),
            'sage_api_ready' => $this->sageApiService !== null,
            'dp_api_ready' => $this->dpApiService !== null,
            'user_token_present' => $this->userToken !== null,
        ];
    }

    /**
     * Run the full deposit date sync process
     *
     * @param  int  $batchSize  Number of items to process per batch
     * @param  bool  $dryRun  If true, don't actually write to DonorPerfect
     * @return array Results of the sync operation
     */
    public function sync(int $batchSize = 100, bool $dryRun = false): array
    {
        $results = [
            'success' => false,
            'total_pending' => 0,
            'sage_queried' => 0,
            'deposited_found' => 0,
            'dp_updated' => 0,
            'dp_failed' => 0,
            'errors' => [],
            'dry_run' => $dryRun,
        ];

        try {
            if (! $this->isConfigured()) {
                $results['errors'][] = 'Service not properly configured';
                $results['configuration_status'] = $this->getConfigurationStatus();

                return $results;
            }

            // Step 1: Get items that need deposit date sync
            $pendingItems = $this->getPendingItems($batchSize);
            $results['total_pending'] = $pendingItems->count();

            if ($pendingItems->isEmpty()) {
                $results['success'] = true;
                $results['message'] = 'No items pending deposit date sync';

                return $results;
            }

            Log::info('DepositDateSync: Starting sync', [
                'pending_count' => $pendingItems->count(),
                'dry_run' => $dryRun,
            ]);

            // Step 2: Query Sage Intacct for deposit status
            $recordNos = $pendingItems->pluck('intacct_record_id')->filter()->toArray();
            $sageResult = $this->sageApiService->queryOtherReceiptsByRecordNos($recordNos);

            if (! $sageResult['success']) {
                $results['errors'][] = 'Sage query failed: '.($sageResult['error'] ?? 'Unknown error');

                return $results;
            }

            $results['sage_queried'] = count($recordNos);
            $receipts = collect($sageResult['receipts'])->keyBy('recordno');

            // Step 3: Process each pending item
            foreach ($pendingItems as $item) {
                $receipt = $receipts->get($item->intacct_record_id);

                if (! $receipt) {
                    Log::warning('DepositDateSync: Receipt not found in Sage', [
                        'gift_id' => $item->gift_id,
                        'intacct_record_id' => $item->intacct_record_id,
                    ]);

                    continue;
                }

                $depositDate = $receipt['depositdate'] ?? null;

                // Update the Sage deposit date in our tracking
                if (! empty($depositDate)) {
                    $item->updateSageDepositDate($depositDate);
                    $results['deposited_found']++;

                    // Step 4: Write deposit date back to DonorPerfect
                    if (! $dryRun) {
                        $dpResult = $this->updateDonorPerfectDepositDate(
                            (int) $item->gift_id,
                            $depositDate
                        );

                        if ($dpResult['success']) {
                            $item->markDepositDateSynced();
                            $results['dp_updated']++;

                            Log::info('DepositDateSync: Successfully updated DP', [
                                'gift_id' => $item->gift_id,
                                'deposit_date' => $depositDate,
                            ]);
                        } else {
                            $item->markDepositDateSyncFailed($dpResult['error'] ?? 'Unknown error');
                            $results['dp_failed']++;
                            $results['errors'][] = "Gift {$item->gift_id}: ".($dpResult['error'] ?? 'Unknown error');

                            Log::error('DepositDateSync: Failed to update DP', [
                                'gift_id' => $item->gift_id,
                                'error' => $dpResult['error'] ?? 'Unknown error',
                            ]);
                        }
                    } else {
                        Log::info('DepositDateSync: Dry run - would update DP', [
                            'gift_id' => $item->gift_id,
                            'deposit_date' => $depositDate,
                        ]);
                    }
                }
            }

            $results['success'] = true;
            $results['message'] = sprintf(
                'Processed %d items: %d deposited found, %d updated in DP, %d failed',
                $results['total_pending'],
                $results['deposited_found'],
                $results['dp_updated'],
                $results['dp_failed']
            );

            Log::info('DepositDateSync: Sync completed', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('DepositDateSync: Exception during sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $results['errors'][] = 'Exception: '.$e->getMessage();

            return $results;
        }
    }

    /**
     * Get items that need deposit date sync
     *
     * @param  int  $limit  Maximum number of items to retrieve
     */
    public function getPendingItems(int $limit = 100): Collection
    {
        return SageIntacctSyncItem::needsDepositSync()
            ->orderBy('synced_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get count of items pending deposit date sync
     */
    public function getPendingCount(): int
    {
        return SageIntacctSyncItem::needsDepositSync()->count();
    }

    /**
     * Get items that have deposit dates from Sage but not yet synced to DP
     *
     * @param  int  $limit  Maximum number of items to retrieve
     */
    public function getItemsWithDepositDatePendingSync(int $limit = 100): Collection
    {
        return SageIntacctSyncItem::hasDepositDatePendingSync()
            ->orderBy('sage_deposit_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get items with deposit sync errors
     */
    public function getItemsWithErrors(): Collection
    {
        return SageIntacctSyncItem::withDepositSyncErrors()->get();
    }

    /**
     * Retry failed deposit date syncs
     *
     * @param  bool  $dryRun  If true, don't actually write to DonorPerfect
     * @return array Results of the retry operation
     */
    public function retryFailed(bool $dryRun = false): array
    {
        $results = [
            'success' => false,
            'total_errors' => 0,
            'retried' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => [],
            'dry_run' => $dryRun,
        ];

        try {
            if (! $this->isConfigured()) {
                $results['errors'][] = 'Service not properly configured';

                return $results;
            }

            $errorItems = $this->getItemsWithErrors();
            $results['total_errors'] = $errorItems->count();

            if ($errorItems->isEmpty()) {
                $results['success'] = true;
                $results['message'] = 'No failed items to retry';

                return $results;
            }

            foreach ($errorItems as $item) {
                if (empty($item->sage_deposit_date)) {
                    continue;
                }

                $results['retried']++;

                if (! $dryRun) {
                    $dpResult = $this->updateDonorPerfectDepositDate(
                        (int) $item->gift_id,
                        $item->sage_deposit_date->format('Y-m-d')
                    );

                    if ($dpResult['success']) {
                        $item->markDepositDateSynced();
                        $results['succeeded']++;
                    } else {
                        $item->markDepositDateSyncFailed($dpResult['error'] ?? 'Unknown error');
                        $results['failed']++;
                        $results['errors'][] = "Gift {$item->gift_id}: ".($dpResult['error'] ?? 'Unknown error');
                    }
                }
            }

            $results['success'] = true;
            $results['message'] = sprintf(
                'Retried %d items: %d succeeded, %d failed',
                $results['retried'],
                $results['succeeded'],
                $results['failed']
            );

            return $results;

        } catch (\Exception $e) {
            Log::error('DepositDateSync: Exception during retry', [
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = 'Exception: '.$e->getMessage();

            return $results;
        }
    }

    /**
     * Update a gift's deposit date in DonorPerfect
     *
     * @param  int  $giftId  The DonorPerfect gift ID
     * @param  string  $depositDate  The deposit date (Y-m-d format)
     * @return array Result with success status
     */
    private function updateDonorPerfectDepositDate(int $giftId, string $depositDate): array
    {
        return $this->dpApiService->updateGiftUdf(
            $giftId,
            self::DP_DEPOSIT_DATE_FIELD,
            $depositDate
        );
    }

    /**
     * Get statistics about deposit date sync status
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $totalSynced = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
            ->whereNotNull('intacct_record_id')
            ->count();

        $pendingDepositCheck = SageIntacctSyncItem::needsDepositSync()
            ->whereNull('sage_deposit_date')
            ->count();

        $hasDepositDate = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
            ->whereNotNull('sage_deposit_date')
            ->count();

        $pendingDpUpdate = SageIntacctSyncItem::hasDepositDatePendingSync()->count();

        $syncedToDP = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
            ->whereNotNull('deposit_date_synced_at')
            ->count();

        $withErrors = SageIntacctSyncItem::withDepositSyncErrors()->count();

        return [
            'total_synced_to_sage' => $totalSynced,
            'pending_deposit_check' => $pendingDepositCheck,
            'has_deposit_date' => $hasDepositDate,
            'pending_dp_update' => $pendingDpUpdate,
            'synced_to_dp' => $syncedToDP,
            'with_errors' => $withErrors,
        ];
    }

    /**
     * Check a single item's deposit status in Sage
     *
     * @param  SageIntacctSyncItem  $item  The sync item to check
     * @return array Result with deposit information
     */
    public function checkSingleItemDepositStatus(SageIntacctSyncItem $item): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Service not configured',
            ];
        }

        if (empty($item->intacct_record_id)) {
            return [
                'success' => false,
                'error' => 'Item has no Intacct record ID',
            ];
        }

        $result = $this->sageApiService->queryOtherReceiptsByRecordNos([$item->intacct_record_id]);

        if (! $result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Query failed',
            ];
        }

        $receipt = $result['receipts'][0] ?? null;

        if (! $receipt) {
            return [
                'success' => false,
                'error' => 'Receipt not found in Sage',
            ];
        }

        return [
            'success' => true,
            'receipt' => $receipt,
            'has_deposit_date' => ! empty($receipt['depositdate']),
            'deposit_date' => $receipt['depositdate'] ?? null,
            'state' => $receipt['state'] ?? null,
        ];
    }
}
