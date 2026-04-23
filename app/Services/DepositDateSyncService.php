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

    private ?string $environment = null;

    /**
     * The DonorPerfect UDF field name for deposit date
     */
    const DP_DEPOSIT_DATE_FIELD = 'DEPOSIT_DATE';

    /**
     * The DonorPerfect UDF field name for fund code (Sage PROJECTID)
     */
    const DP_FUND_CODE_FIELD = 'FUND_CODE';

    /**
     * The DonorPerfect UDF field name for service code (Sage DEPARTMENTID)
     */
    const DP_SERVICE_CODE_FIELD = 'SERVICE_CODE';

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
     * Get the current environment based on settings/token configuration
     */
    public function getEnvironment(): string
    {
        if ($this->environment !== null) {
            return $this->environment;
        }

        if ($this->userToken) {
            $this->environment = $this->userToken->isSandbox() ? 'sandbox' : 'production';
        } elseif ($this->sageSetting) {
            $this->environment = $this->sageSetting->isSandbox() ? 'sandbox' : 'production';
        } else {
            $this->environment = 'production';
        }

        return $this->environment;
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
            'fund_codes_found' => 0,
            'service_codes_found' => 0,
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
                'environment' => $this->getEnvironment(),
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
                $fundCode = ! empty($receipt['projectid']) ? $receipt['projectid'] : null;
                $serviceCode = ! empty($receipt['departmentid']) ? $receipt['departmentid'] : null;

                if ($fundCode) {
                    $results['fund_codes_found']++;
                }
                if ($serviceCode) {
                    $results['service_codes_found']++;
                }

                // Update the Sage codes in our tracking
                if (! empty($depositDate) || $fundCode || $serviceCode) {
                    $item->updateSageCodes($depositDate, $fundCode, $serviceCode);

                    if (! empty($depositDate)) {
                        $results['deposited_found']++;
                    }

                    // Step 4: Write deposit date, fund code, and service code back to DonorPerfect
                    if (! $dryRun) {
                        $dpResult = $this->updateDonorPerfectCodes(
                            (int) $item->gift_id,
                            $depositDate,
                            $fundCode,
                            $serviceCode
                        );

                        if ($dpResult['success']) {
                            $item->markDepositDateSynced();
                            $results['dp_updated']++;

                            Log::info('DepositDateSync: Successfully updated DP', [
                                'gift_id' => $item->gift_id,
                                'deposit_date' => $depositDate,
                                'fund_code' => $fundCode,
                                'service_code' => $serviceCode,
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
                            'fund_code' => $fundCode,
                            'service_code' => $serviceCode,
                        ]);
                    }
                }
            }

            $results['success'] = true;
            $results['message'] = sprintf(
                'Processed %d items: %d deposited, %d fund codes, %d service codes, %d updated in DP, %d failed',
                $results['total_pending'],
                $results['deposited_found'],
                $results['fund_codes_found'],
                $results['service_codes_found'],
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
        return SageIntacctSyncItem::needsDepositSync($this->getEnvironment())
            ->orderBy('synced_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get count of items pending deposit date sync
     */
    public function getPendingCount(): int
    {
        return SageIntacctSyncItem::needsDepositSync($this->getEnvironment())->count();
    }

    /**
     * Get items that have deposit dates from Sage but not yet synced to DP
     *
     * @param  int  $limit  Maximum number of items to retrieve
     */
    public function getItemsWithDepositDatePendingSync(int $limit = 100): Collection
    {
        return SageIntacctSyncItem::hasDepositDatePendingSync($this->getEnvironment())
            ->orderBy('sage_deposit_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get items with deposit sync errors
     */
    public function getItemsWithErrors(): Collection
    {
        return SageIntacctSyncItem::withDepositSyncErrors($this->getEnvironment())->get();
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
                if (empty($item->sage_deposit_date) && empty($item->sage_fund_code) && empty($item->sage_service_code)) {
                    continue;
                }

                $results['retried']++;

                if (! $dryRun) {
                    $dpResult = $this->updateDonorPerfectCodes(
                        (int) $item->gift_id,
                        $item->sage_deposit_date ? $item->sage_deposit_date->format('Y-m-d') : null,
                        $item->sage_fund_code,
                        $item->sage_service_code
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
     * One-time backfill for items that synced before the parser fix landed —
     * they have a deposit_date but no fund/service codes. Re-queries Sage,
     * writes codes to DP, leaves deposit_date untouched.
     *
     * @param  int   $limit   Max items per batch
     * @param  bool  $dryRun  If true, don't actually write to DonorPerfect
     */
    public function backfillMissingCodes(int $limit = 100, bool $dryRun = false): array
    {
        $results = [
            'success' => false,
            'total_candidates' => 0,
            'sage_queried' => 0,
            'codes_found' => 0,
            'dp_updated' => 0,
            'dp_failed' => 0,
            'errors' => [],
            'dry_run' => $dryRun,
        ];

        try {
            if (! $this->isConfigured()) {
                $results['errors'][] = 'Service not properly configured';

                return $results;
            }

            $candidates = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
                ->whereNotNull('intacct_record_id')
                ->whereNotNull('deposit_date_synced_at')
                ->where('environment', $this->getEnvironment())
                ->where(function ($q) {
                    $q->whereNull('sage_fund_code')
                        ->orWhere('sage_fund_code', '')
                        ->orWhereNull('sage_service_code')
                        ->orWhere('sage_service_code', '');
                })
                ->orderBy('id')
                ->limit($limit)
                ->get();

            $results['total_candidates'] = $candidates->count();

            if ($candidates->isEmpty()) {
                $results['success'] = true;
                $results['message'] = 'No items need code backfill';

                return $results;
            }

            $recordNos = $candidates->pluck('intacct_record_id')->filter()->toArray();
            $sageResult = $this->sageApiService->queryOtherReceiptsByRecordNos($recordNos);

            if (! $sageResult['success']) {
                $results['errors'][] = 'Sage query failed: '.($sageResult['error'] ?? 'Unknown error');

                return $results;
            }

            $results['sage_queried'] = count($recordNos);
            $receipts = collect($sageResult['receipts'])->keyBy('recordno');

            foreach ($candidates as $item) {
                $receipt = $receipts->get($item->intacct_record_id);
                if (! $receipt) {
                    continue;
                }

                $fundCode = ! empty($receipt['projectid']) ? $receipt['projectid'] : null;
                $serviceCode = ! empty($receipt['departmentid']) ? $receipt['departmentid'] : null;

                if (! $fundCode && ! $serviceCode) {
                    continue;
                }

                $results['codes_found']++;

                if ($dryRun) {
                    Log::info('DepositDateSync: Dry-run backfill would update DP', [
                        'gift_id' => $item->gift_id,
                        'fund_code' => $fundCode,
                        'service_code' => $serviceCode,
                    ]);

                    continue;
                }

                $depositDateStr = $item->sage_deposit_date
                    ? $item->sage_deposit_date->format('Y-m-d')
                    : null;
                $item->updateSageCodes($depositDateStr, $fundCode, $serviceCode);

                $dpResult = $this->updateDonorPerfectCodes(
                    (int) $item->gift_id,
                    null,
                    $fundCode,
                    $serviceCode
                );

                if ($dpResult['success']) {
                    $results['dp_updated']++;
                } else {
                    $results['dp_failed']++;
                    $results['errors'][] = "Gift {$item->gift_id}: ".($dpResult['error'] ?? 'Unknown');
                }
            }

            $results['success'] = true;
            $results['message'] = sprintf(
                'Processed %d candidates: %d with codes, %d updated in DP, %d failed',
                $results['total_candidates'],
                $results['codes_found'],
                $results['dp_updated'],
                $results['dp_failed']
            );

            return $results;

        } catch (\Exception $e) {
            Log::error('DepositDateSync: Exception during code backfill', [
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = 'Exception: '.$e->getMessage();

            return $results;
        }
    }

    /**
     * Update a gift's deposit date, fund code, and service code in DonorPerfect
     *
     * @param  int  $giftId  The DonorPerfect gift ID
     * @param  string|null  $depositDate  The deposit date (Y-m-d format)
     * @param  string|null  $fundCode  The fund code (Sage PROJECTID)
     * @param  string|null  $serviceCode  The service code (Sage DEPARTMENTID)
     * @return array Result with success status
     */
    private function updateDonorPerfectCodes(int $giftId, ?string $depositDate, ?string $fundCode, ?string $serviceCode): array
    {
        $errors = [];

        // Write deposit date
        if (! empty($depositDate)) {
            $result = $this->dpApiService->updateGiftUdf($giftId, self::DP_DEPOSIT_DATE_FIELD, $depositDate);
            if (! $result['success']) {
                $errors[] = 'DEPOSIT_DATE: '.($result['error'] ?? 'Unknown error');
            }
        }

        // Write fund code
        if (! empty($fundCode)) {
            $result = $this->dpApiService->updateGiftUdf($giftId, self::DP_FUND_CODE_FIELD, $fundCode);
            if (! $result['success']) {
                $errors[] = 'FUND_CODE: '.($result['error'] ?? 'Unknown error');
            }
        }

        // Write service code
        if (! empty($serviceCode)) {
            $result = $this->dpApiService->updateGiftUdf($giftId, self::DP_SERVICE_CODE_FIELD, $serviceCode);
            if (! $result['success']) {
                $errors[] = 'SERVICE_CODE: '.($result['error'] ?? 'Unknown error');
            }
        }

        if (! empty($errors)) {
            return [
                'success' => false,
                'error' => implode('; ', $errors),
            ];
        }

        return ['success' => true];
    }

    /**
     * Get statistics about deposit date sync status
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $env = $this->getEnvironment();

        $totalSynced = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
            ->whereNotNull('intacct_record_id')
            ->where('environment', $env)
            ->count();

        $pendingDepositCheck = SageIntacctSyncItem::needsDepositSync($env)
            ->whereNull('sage_deposit_date')
            ->count();

        $hasDepositDate = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
            ->whereNotNull('sage_deposit_date')
            ->where('environment', $env)
            ->count();

        $pendingDpUpdate = SageIntacctSyncItem::hasDepositDatePendingSync($env)->count();

        $syncedToDP = SageIntacctSyncItem::where('status', SageIntacctSyncItem::STATUS_SUCCESS)
            ->whereNotNull('deposit_date_synced_at')
            ->where('environment', $env)
            ->count();

        $withErrors = SageIntacctSyncItem::withDepositSyncErrors($env)->count();

        return [
            'total_synced_to_sage' => $totalSynced,
            'pending_deposit_check' => $pendingDepositCheck,
            'has_deposit_date' => $hasDepositDate,
            'pending_dp_update' => $pendingDpUpdate,
            'synced_to_dp' => $syncedToDP,
            'with_errors' => $withErrors,
            'environment' => $env,
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
