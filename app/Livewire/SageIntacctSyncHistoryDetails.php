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

    public $showItemDetails = false;

    public $selectedItem = null;

    public function mount($id)
    {
        $this->authorizeAction('intacct.view');

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

    public function showItemDetails($itemId)
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

        $this->showItemDetails = true;
    }

    public function closeItemDetails()
    {
        $this->showItemDetails = false;
        $this->selectedItem = null;
    }

    public function getEnvironmentInfo()
    {
        if ($this->syncHistory && $this->syncHistory->environment_info) {
            return json_decode($this->syncHistory->environment_info, true);
        }

        return null;
    }

    public function getConfigurationSnapshot()
    {
        if ($this->syncHistory && $this->syncHistory->configuration_snapshot) {
            return json_decode($this->syncHistory->configuration_snapshot, true);
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
