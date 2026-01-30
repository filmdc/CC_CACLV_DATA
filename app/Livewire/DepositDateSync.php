<?php

namespace App\Livewire;

use App\Models\SageIntacctSyncItem;
use App\Models\UserSageIntacctToken;
use App\Services\DepositDateSyncService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DepositDateSync extends Component
{
    use WithPagination, WithPermissionChecks;

    // Tab state
    public $activeTab = 'overview';

    // Sync state
    public $syncInProgress = false;

    public $lastSyncResult = null;

    // Filters for pending items
    public $searchGiftId = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    // Statistics (cached for performance)
    public $statistics = [];

    // Configuration status
    public $isConfigured = false;

    public $configurationStatus = [];

    public function mount()
    {
        $this->loadStatistics();
        $this->checkConfiguration();
    }

    /**
     * Check if the service is properly configured
     */
    private function checkConfiguration(): void
    {
        $userToken = $this->getUserToken();
        $service = new DepositDateSyncService($userToken);

        $this->isConfigured = $service->isConfigured();
        $this->configurationStatus = $service->getConfigurationStatus();
    }

    /**
     * Get the current user's Sage Intacct token
     */
    private function getUserToken(): ?UserSageIntacctToken
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'sageIntacctToken')) {
            return $user->sageIntacctToken;
        }

        return null;
    }

    /**
     * Load statistics from the service
     */
    public function loadStatistics(): void
    {
        $userToken = $this->getUserToken();
        $service = new DepositDateSyncService($userToken);
        $this->statistics = $service->getStatistics();
    }

    /**
     * Run the deposit date sync
     */
    public function runSync(): void
    {
        if ($this->syncInProgress) {
            return;
        }

        $this->syncInProgress = true;
        $this->lastSyncResult = null;

        try {
            $userToken = $this->getUserToken();
            $service = new DepositDateSyncService($userToken);

            if (! $service->isConfigured()) {
                session()->flash('error', 'Deposit date sync is not properly configured. Please check your Sage Intacct and DonorPerfect settings.');
                $this->syncInProgress = false;

                return;
            }

            $result = $service->sync(100, false);
            $this->lastSyncResult = $result;

            if ($result['success']) {
                session()->flash('message', $result['message'] ?? 'Sync completed successfully.');
            } else {
                $errorMsg = implode('; ', array_slice($result['errors'] ?? ['Unknown error'], 0, 3));
                session()->flash('error', 'Sync completed with errors: '.$errorMsg);
            }

            // Reload statistics
            $this->loadStatistics();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred during sync: '.$e->getMessage());
        }

        $this->syncInProgress = false;
    }

    /**
     * Run a dry run sync (preview only)
     */
    public function runDrySync(): void
    {
        if ($this->syncInProgress) {
            return;
        }

        $this->syncInProgress = true;
        $this->lastSyncResult = null;

        try {
            $userToken = $this->getUserToken();
            $service = new DepositDateSyncService($userToken);

            if (! $service->isConfigured()) {
                session()->flash('error', 'Deposit date sync is not properly configured.');
                $this->syncInProgress = false;

                return;
            }

            $result = $service->sync(100, true);
            $this->lastSyncResult = $result;

            session()->flash('message', 'Dry run completed. '.($result['deposited_found'] ?? 0).' items with deposit dates found. No changes were made to DonorPerfect.');

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }

        $this->syncInProgress = false;
    }

    /**
     * Retry failed items
     */
    public function retryFailed(): void
    {
        if ($this->syncInProgress) {
            return;
        }

        $this->syncInProgress = true;

        try {
            $userToken = $this->getUserToken();
            $service = new DepositDateSyncService($userToken);

            $result = $service->retryFailed(false);
            $this->lastSyncResult = $result;

            if ($result['success']) {
                session()->flash('message', $result['message'] ?? 'Retry completed.');
            } else {
                session()->flash('error', 'Retry completed with errors.');
            }

            $this->loadStatistics();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }

        $this->syncInProgress = false;
    }

    /**
     * Get paginated pending items
     */
    public function getPendingItemsProperty()
    {
        $query = SageIntacctSyncItem::needsDepositSync()
            ->orderBy('synced_at', 'desc');

        if (! empty($this->searchGiftId)) {
            $query->where('gift_id', 'like', '%'.$this->searchGiftId.'%');
        }

        return $query->paginate(15, ['*'], 'pendingPage');
    }

    /**
     * Get paginated items with deposit dates pending DP sync
     */
    public function getItemsWithDepositDatesProperty()
    {
        return SageIntacctSyncItem::hasDepositDatePendingSync()
            ->orderBy('sage_deposit_date', 'desc')
            ->paginate(15, ['*'], 'depositPage');
    }

    /**
     * Get paginated items with errors
     */
    public function getItemsWithErrorsProperty()
    {
        return SageIntacctSyncItem::withDepositSyncErrors()
            ->orderBy('updated_at', 'desc')
            ->paginate(15, ['*'], 'errorsPage');
    }

    /**
     * Get recently synced items
     */
    public function getRecentlySyncedItemsProperty()
    {
        return SageIntacctSyncItem::whereNotNull('deposit_date_synced_at')
            ->orderBy('deposit_date_synced_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Check deposit status for a single item
     */
    public function checkSingleItem(int $itemId): void
    {
        try {
            $item = SageIntacctSyncItem::find($itemId);

            if (! $item) {
                session()->flash('error', 'Item not found.');

                return;
            }

            $userToken = $this->getUserToken();
            $service = new DepositDateSyncService($userToken);
            $result = $service->checkSingleItemDepositStatus($item);

            if ($result['success']) {
                if ($result['has_deposit_date']) {
                    $item->updateSageDepositDate($result['deposit_date']);
                    session()->flash('message', 'Deposit date found: '.$result['deposit_date']);
                } else {
                    session()->flash('message', 'Receipt exists in Sage but has not been deposited yet.');
                }
            } else {
                session()->flash('error', 'Failed to check status: '.($result['error'] ?? 'Unknown error'));
            }

            $this->loadStatistics();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    /**
     * Sync a single item's deposit date to DonorPerfect
     */
    public function syncSingleItem(int $itemId): void
    {
        try {
            $item = SageIntacctSyncItem::find($itemId);

            if (! $item || ! $item->sage_deposit_date) {
                session()->flash('error', 'Item not found or has no deposit date.');

                return;
            }

            $userToken = $this->getUserToken();
            $service = new DepositDateSyncService($userToken);

            // Use reflection or direct call to update DP
            $dpSetting = \App\Models\DonorPerfectSetting::getActive();
            if (! $dpSetting || ! $dpSetting->isConfigured()) {
                session()->flash('error', 'DonorPerfect is not configured.');

                return;
            }

            $dpApi = new \App\Services\DonorPerfectApiService($dpSetting);
            $result = $dpApi->updateGiftUdf(
                (int) $item->gift_id,
                DepositDateSyncService::DP_DEPOSIT_DATE_FIELD,
                $item->sage_deposit_date->format('Y-m-d')
            );

            if ($result['success']) {
                $item->markDepositDateSynced();
                session()->flash('message', 'Successfully synced deposit date for gift #'.$item->gift_id);
            } else {
                $item->markDepositDateSyncFailed($result['error'] ?? 'Unknown error');
                session()->flash('error', 'Failed to sync: '.($result['error'] ?? 'Unknown error'));
            }

            $this->loadStatistics();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    /**
     * Clear error for an item to allow retry
     */
    public function clearError(int $itemId): void
    {
        $item = SageIntacctSyncItem::find($itemId);

        if ($item) {
            $item->update(['deposit_sync_error' => null]);
            session()->flash('message', 'Error cleared. Item will be picked up in the next sync.');
            $this->loadStatistics();
        }
    }

    public function render()
    {
        return view('livewire.deposit-date-sync', [
            'pendingItems' => $this->pendingItems,
            'itemsWithDepositDates' => $this->itemsWithDepositDates,
            'itemsWithErrors' => $this->itemsWithErrors,
            'recentlySyncedItems' => $this->recentlySyncedItems,
        ]);
    }
}
