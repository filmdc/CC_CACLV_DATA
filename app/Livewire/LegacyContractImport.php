<?php

namespace App\Livewire;

use App\Services\LegacyContractSyncService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class LegacyContractImport extends Component
{
    use WithPagination, WithPermissionChecks;

    // Filter properties
    public $legacyIdFilter = '';

    public $startDateFilter = '';

    public $endDateFilter = '';

    public $previewLimit = 100;

    // Preview and selection
    public Collection $previewContracts;

    public $selectedContracts = [];

    public $selectAll = false;

    // UI state
    public $showPreview = false;

    public $showImportModal = false;

    public $updateExisting = false;

    public $isImporting = false;

    // Connection test
    public $connectionStatus = null;

    public $connectionMessage = '';

    public $contractsCount = 0;

    // Import results
    public $importResults = null;

    public $lastImportStats = [];

    public $lastImportErrors = [];

    public $lastImportWarnings = [];

    protected $queryString = [
        'legacyIdFilter' => ['except' => ''],
        'startDateFilter' => ['except' => ''],
        'endDateFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('contracts.import_legacy');
        $this->previewContracts = collect();
    }

    /**
     * Test connection to legacy database
     */
    public function testConnection()
    {
        try {
            $service = app(LegacyContractSyncService::class);
            $result = $service->testConnection();

            $this->connectionStatus = $result['success'];
            $this->connectionMessage = $result['message'];
            $this->contractsCount = $result['contracts_count'] ?? 0;

            if ($result['success']) {
                session()->flash('success', 'Connection successful! Found '.$this->contractsCount.' contracts in legacy database.');
            } else {
                session()->flash('error', 'Connection failed: '.$this->connectionMessage);
            }
        } catch (\Exception $e) {
            $this->connectionStatus = false;
            $this->connectionMessage = $e->getMessage();
            session()->flash('error', 'Connection failed: '.$e->getMessage());
        }
    }

    /**
     * Preview contracts based on filters
     */
    public function previewContracts()
    {
        $this->validate([
            'legacyIdFilter' => 'nullable|string|max:255',
            'startDateFilter' => 'nullable|date',
            'endDateFilter' => 'nullable|date|after_or_equal:startDateFilter',
            'previewLimit' => 'required|integer|min:1|max:500',
        ]);

        try {
            $service = app(LegacyContractSyncService::class);

            $this->previewContracts = $service->previewContracts(
                $this->legacyIdFilter,
                $this->startDateFilter,
                $this->endDateFilter,
                $this->previewLimit
            );

            $this->showPreview = true;
            $this->selectedContracts = [];
            $this->selectAll = false;

            session()->flash('success', 'Found '.$this->previewContracts->count().' contracts matching your filters.');
        } catch (\Exception $e) {
            session()->flash('error', 'Preview failed: '.$e->getMessage());
            \Log::error('Legacy contract preview failed', [
                'error' => $e->getMessage(),
                'filters' => [
                    'legacy_id' => $this->legacyIdFilter,
                    'start_date' => $this->startDateFilter,
                    'end_date' => $this->endDateFilter,
                ],
            ]);
        }
    }

    /**
     * Clear preview and reset
     */
    public function clearPreview()
    {
        $this->showPreview = false;
        $this->previewContracts = collect();
        $this->selectedContracts = [];
        $this->selectAll = false;
    }

    /**
     * Toggle select all contracts
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedContracts = $this->previewContracts->pluck('legacy_id')->toArray();
        } else {
            $this->selectedContracts = [];
        }
    }

    /**
     * Show import confirmation modal
     */
    public function showImportConfirmation()
    {
        if (empty($this->selectedContracts)) {
            session()->flash('error', 'Please select at least one contract to import.');

            return;
        }

        $this->showImportModal = true;
    }

    /**
     * Cancel import
     */
    public function cancelImport()
    {
        $this->showImportModal = false;
    }

    /**
     * Import selected contracts
     */
    public function importContracts()
    {
        if (empty($this->selectedContracts)) {
            session()->flash('error', 'Please select at least one contract to import.');

            return;
        }

        $this->isImporting = true;
        $this->importResults = null;

        try {
            $service = app(LegacyContractSyncService::class);

            $result = $service->syncContracts(
                $this->selectedContracts,
                $this->updateExisting
            );

            $this->importResults = $result;
            $this->lastImportStats = $result['stats'] ?? [];
            $this->lastImportErrors = $result['errors'] ?? [];
            $this->lastImportWarnings = $result['warnings'] ?? [];

            if ($result['success']) {
                $message = sprintf(
                    'Import completed! Created: %d, Updated: %d, Skipped: %d',
                    $this->lastImportStats['created'] ?? 0,
                    $this->lastImportStats['updated'] ?? 0,
                    $this->lastImportStats['skipped'] ?? 0
                );
                session()->flash('success', $message);

                // Refresh preview to show updated sync status
                $this->previewContracts();
                $this->selectedContracts = [];
                $this->selectAll = false;
            } else {
                session()->flash('error', 'Import failed: '.($result['error'] ?? 'Unknown error'));
            }

            $this->showImportModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: '.$e->getMessage());
            \Log::error('Legacy contract import failed', [
                'error' => $e->getMessage(),
                'selected_contracts' => $this->selectedContracts,
            ]);
        } finally {
            $this->isImporting = false;
        }
    }

    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->legacyIdFilter = '';
        $this->startDateFilter = '';
        $this->endDateFilter = '';
        $this->previewLimit = 100;
        $this->clearPreview();
    }

    public function render()
    {
        return view('livewire.legacy-contract-import')->layout('components.layouts.app');
    }
}
