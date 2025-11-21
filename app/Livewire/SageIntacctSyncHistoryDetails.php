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
        $this->syncHistory = SageIntacctSyncHistory::with('syncItems')
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
            // Get count before reset
            $successCount = DB::table('sage_intacct_sync_items')
                ->where('sync_history_id', $this->syncHistoryId)
                ->where('status', 'success')
                ->count();

            if ($successCount === 0) {
                session()->flash('warning', 'No successfully synced gifts found in this sync job to reset.');
                $this->showResetConfirmation = false;
                return;
            }

            // Reset all successfully synced items to pending
            DB::table('sage_intacct_sync_items')
                ->where('sync_history_id', $this->syncHistoryId)
                ->where('status', 'success')
                ->update([
                    'status' => 'pending',
                    'intacct_record_id' => null,
                    'synced_at' => null,
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            // Log the reset action
            \Log::info('Sage Intacct: Sync gifts reset for re-sync', [
                'sync_history_id' => $this->syncHistoryId,
                'reset_count' => $successCount,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
            ]);

            session()->flash('success', "Successfully reset {$successCount} gift(s) to pending status. These gifts can now be synced again.");

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
