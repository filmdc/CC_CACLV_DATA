<?php

namespace App\Livewire;

use App\Models\SageIntacctSyncHistory;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SageIntacctSyncHistoryDetails extends Component
{
    use WithPagination, WithPermissionChecks;

    public $syncHistoryId;

    public $syncHistory;

    public $statusFilter = 'all'; // all, success, failed, pending

    public $searchGiftId = '';

    public $showModal = false;

    public $selectedItem = null;

    public $showResetConfirmation = false;

    public function mount($id)
    {
        // Allow viewing if user has sync view permission or admin permission
        if (!$this->canAny(['sage.sync.view', 'intacct.configure'])) {
            abort(403, 'Unauthorized access to sync history.');
        }

        $this->syncHistoryId = $id;
        $this->loadSyncHistory();
    }

    public function loadSyncHistory()
    {
        $this->syncHistory = SageIntacctSyncHistory::with(['syncItems', 'resetBy'])
            ->findOrFail($this->syncHistoryId);
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearchGiftId()
    {
        $this->resetPage();
    }

    public function viewItemDetails($itemId)
    {
        $this->selectedItem = DB::table('sage_intacct_sync_items')
            ->where('id', $itemId)
            ->first();

        // Decode JSON fields
        if ($this->selectedItem) {
            $this->selectedItem->gift_data = json_decode($this->selectedItem->gift_data, true);
            $this->selectedItem->transformed_data = json_decode($this->selectedItem->transformed_data, true);
            $this->selectedItem->api_request = json_decode($this->selectedItem->api_request, true);
            $this->selectedItem->api_response = json_decode($this->selectedItem->api_response, true);
            $this->selectedItem->validation_errors = json_decode($this->selectedItem->validation_errors, true);
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedItem = null;
    }

    public function confirmResetAllGifts()
    {
        // Check permission
        $this->authorizeAction('sage.sync.perform');

        $this->showResetConfirmation = true;
    }

    public function cancelReset()
    {
        $this->showResetConfirmation = false;
    }

    public function resetAllGifts()
    {
        // Check permission
        $this->authorizeAction('sage.sync.perform');

        try {
            // STEP 1: Get all gift_ids from this sync that have success status
            $giftIds = DB::table('sage_intacct_sync_items')
                ->where('sync_history_id', $this->syncHistoryId)
                ->where('status', 'success')
                ->pluck('gift_id')
                ->unique()
                ->toArray();

            if (empty($giftIds)) {
                session()->flash('warning', 'No successfully synced gifts found in this sync job to reset.');
                $this->showResetConfirmation = false;
                return;
            }

            // STEP 2: Count how many records across ALL syncs will be reset
            $totalRecordsToReset = DB::table('sage_intacct_sync_items')
                ->whereIn('gift_id', $giftIds)
                ->where('status', 'success')
                ->count();

            // STEP 3: Reset ALL occurrences of these gift_ids across ALL sync_history_ids
            // This ensures duplicate prevention won't block them
            $resetCount = DB::table('sage_intacct_sync_items')
                ->whereIn('gift_id', $giftIds)
                ->where('status', 'success')
                ->update([
                    'status' => 'pending',
                    'intacct_record_id' => null,
                    'synced_at' => null,
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            // STEP 4: Update sync_history table to track this reset
            DB::table('sage_intacct_sync_history')
                ->where('id', $this->syncHistoryId)
                ->update([
                    'reset_at' => now(),
                    'reset_by_user_id' => auth()->id(),
                    'reset_count' => DB::raw('reset_count + 1'),
                    'updated_at' => now(),
                ]);

            // Log the reset action
            \Log::info('Sage Intacct: Sync gifts reset for re-sync (cross-sync reset)', [
                'sync_history_id' => $this->syncHistoryId,
                'unique_gift_ids' => count($giftIds),
                'total_records_reset' => $resetCount,
                'gift_ids' => $giftIds,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
            ]);

            session()->flash('success', "Successfully reset {$resetCount} record(s) for " . count($giftIds) . " unique gift(s) across all sync jobs. These gifts can now be synced again.");

            $this->showResetConfirmation = false;
            $this->loadSyncHistory();

        } catch (\Exception $e) {
            \Log::error('Sage Intacct: Reset gifts failed', [
                'sync_history_id' => $this->syncHistoryId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to reset gifts: ' . $e->getMessage());
            $this->showResetConfirmation = false;
        }
    }

    public function getEnvironmentInfo()
    {
        if ($this->syncHistory && $this->syncHistory->environment_info) {
            // Already decoded by model cast, return directly
            return is_array($this->syncHistory->environment_info)
                ? $this->syncHistory->environment_info
                : json_decode($this->syncHistory->environment_info, true);
        }

        return null;
    }

    public function getConfigurationSnapshot()
    {
        if ($this->syncHistory && $this->syncHistory->configuration_snapshot) {
            // Already decoded by model cast, return directly
            return is_array($this->syncHistory->configuration_snapshot)
                ? $this->syncHistory->configuration_snapshot
                : json_decode($this->syncHistory->configuration_snapshot, true);
        }

        return null;
    }

    public function render()
    {
        // Build query for sync items
        $query = DB::table('sage_intacct_sync_items')
            ->where('sync_history_id', $this->syncHistoryId);

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Apply gift ID search
        if (! empty($this->searchGiftId)) {
            $query->where('gift_id', 'like', '%'.$this->searchGiftId.'%');
        }

        // Order by status (failed first, then pending, then success)
        $query->orderByRaw("CASE
            WHEN status = 'failed' THEN 1
            WHEN status = 'pending' THEN 2
            WHEN status = 'success' THEN 3
            ELSE 4
        END");
        $query->orderBy('created_at', 'desc');

        $syncItems = $query->paginate(25);

        // Decode JSON for display
        foreach ($syncItems as $item) {
            $item->gift_data_decoded = json_decode($item->gift_data, true);
            $item->has_error = ! empty($item->error_message);
        }

        return view('livewire.sage-intacct-sync-history-details', [
            'syncItems' => $syncItems,
            'environmentInfo' => $this->getEnvironmentInfo(),
            'configurationSnapshot' => $this->getConfigurationSnapshot(),
        ])->layout('components.layouts.app');
    }
}
