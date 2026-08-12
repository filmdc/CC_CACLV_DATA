<?php

namespace App\Livewire;

use App\Models\LegacyContractSyncRun;
use App\Services\LegacyContractSyncService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class LegacyContractImport extends Component
{
    use WithPagination, WithPermissionChecks;

    // Automatic sync status panel
    public $driftSummary = null;

    public $driftCheckedAt = null;

    public $deletedInLegacy = [];

    public $isCheckingDrift = false;

    public $isRunningSync = false;

    public $showDeletedDetail = false;

    // Filter properties
    public $legacyIdFilter = '';

    public $startDateFilter = '';

    public $endDateFilter = '';

    public $previewLimit = 100;

    // Preview and selection
    public $previewContracts = [];

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

    public $showSuccessSummary = false;

    protected $queryString = [
        'legacyIdFilter' => ['except' => ''],
        'startDateFilter' => ['except' => ''],
        'endDateFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('contracts.import_legacy');
        $this->previewContracts = [];
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
    public function loadPreview()
    {
        $this->validate([
            'legacyIdFilter' => 'nullable|string|max:255',
            'startDateFilter' => 'nullable|date',
            'endDateFilter' => 'nullable|date|after_or_equal:startDateFilter',
            'previewLimit' => 'required|integer|min:1|max:500',
        ]);

        try {
            $service = app(LegacyContractSyncService::class);

            $contracts = $service->previewContracts(
                $this->legacyIdFilter,
                $this->startDateFilter,
                $this->endDateFilter,
                $this->previewLimit
            );

            $this->previewContracts = $contracts->toArray();
            $this->showPreview = true;
            $this->selectedContracts = [];
            $this->selectAll = false;

            session()->flash('success', 'Found '.count($this->previewContracts).' contracts matching your filters.');
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
        $this->previewContracts = [];
        $this->selectedContracts = [];
        $this->selectAll = false;
        $this->dismissSuccessSummary();
    }

    /**
     * Dismiss the success summary
     */
    public function dismissSuccessSummary()
    {
        $this->showSuccessSummary = false;
        $this->importResults = null;
        $this->lastImportStats = [];
        $this->lastImportErrors = [];
        $this->lastImportWarnings = [];
    }

    /**
     * Toggle select all contracts
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedContracts = array_column($this->previewContracts, 'legacy_id');
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
                $this->showSuccessSummary = true;

                // Refresh preview to show updated sync status
                $this->loadPreview();
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
     * Compare the legacy database against CAP TRACK without changing anything.
     */
    public function checkDrift()
    {
        $this->isCheckingDrift = true;

        try {
            $changes = app(LegacyContractSyncService::class)->detectChanges();

            $this->driftSummary = [
                'new' => count($changes['new']),
                'updated' => count($changes['updated']),
                'deleted_in_legacy' => count($changes['deleted_in_legacy']),
                'unchanged' => $changes['unchanged'],
                'legacy_total' => $changes['legacy_total'],
            ];
            $this->deletedInLegacy = $changes['deleted_in_legacy'];
            $this->driftCheckedAt = $changes['checked_at']->format('M j, Y g:i A');
        } catch (\Exception $e) {
            session()->flash('error', 'Could not check the legacy database: '.$e->getMessage());
            \Log::error('Legacy drift check failed', ['error' => $e->getMessage()]);
        } finally {
            $this->isCheckingDrift = false;
        }
    }

    /**
     * Apply everything the nightly job would apply, on demand.
     */
    public function runAutoSyncNow()
    {
        $this->authorizeAction('contracts.import_legacy');

        $this->isRunningSync = true;

        $run = LegacyContractSyncRun::create([
            'status' => 'running',
            'triggered_by' => 'manual',
            'started_at' => now(),
            'executed_by_user_id' => auth()->id(),
        ]);

        $started = microtime(true);

        try {
            $result = app(LegacyContractSyncService::class)->autoSync();

            $stats = $result['stats'] ?? [];
            $detected = $result['detected'] ?? [];
            $errors = $result['errors'] ?? [];
            $applied = ($stats['created'] ?? 0) + ($stats['updated'] ?? 0);

            $run->update([
                'status' => match (true) {
                    ! ($result['success'] ?? false) => 'failed',
                    ! empty($errors) && $applied > 0 => 'partial',
                    ! empty($errors) => 'failed',
                    default => 'success',
                },
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'detected_new' => $detected['new'] ?? 0,
                'detected_updated' => $detected['updated'] ?? 0,
                'detected_deleted' => $detected['deleted_in_legacy'] ?? 0,
                'unchanged_count' => $detected['unchanged'] ?? 0,
                'created_count' => $stats['created'] ?? 0,
                'updated_count' => $stats['updated'] ?? 0,
                'skipped_count' => $stats['skipped'] ?? 0,
                'due_dates_created' => $stats['due_dates_created'] ?? 0,
                'due_dates_updated' => $stats['due_dates_updated'] ?? 0,
                'media_synced' => $stats['media_synced'] ?? 0,
                'funders_created' => $stats['funders_created'] ?? 0,
                'deferred_count' => $result['deferred'] ?? 0,
                'errors' => $errors ?: null,
                'warnings' => ($result['warnings'] ?? []) ?: null,
                'deleted_legacy_ids' => array_column($result['changes']['deleted_in_legacy'] ?? [], 'legacy_id') ?: null,
            ]);

            $this->lastImportStats = $stats;
            $this->lastImportErrors = $errors;
            $this->lastImportWarnings = $result['warnings'] ?? [];
            $this->importResults = $result;
            $this->showSuccessSummary = true;

            session()->flash('success', 'Sync complete — '.$run->fresh()->summaryLine());

            $this->checkDrift();
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'error_message' => $e->getMessage(),
            ]);

            session()->flash('error', 'Sync failed: '.$e->getMessage());
            \Log::error('Manual legacy auto-sync failed', ['error' => $e->getMessage()]);
        } finally {
            $this->isRunningSync = false;
        }
    }

    public function toggleDeletedDetail()
    {
        $this->showDeletedDetail = ! $this->showDeletedDetail;
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
        return view('livewire.legacy-contract-import', [
            'lastRun' => LegacyContractSyncRun::latestRun(),
            'autoSyncEnabled' => (bool) config('legacy-import.auto_sync.enabled', true),
        ])->layout('components.layouts.app');
    }
}
