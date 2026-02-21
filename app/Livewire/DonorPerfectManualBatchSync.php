<?php

namespace App\Livewire;

use App\Services\DonorPerfectGiftService;
use App\Traits\WithBatchSyncOperations;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DonorPerfectManualBatchSync extends Component
{
    use WithBatchSyncOperations;
    use WithPermissionChecks;

    public $selectedDate;

    public $selectedBatch = '';

    public $availableBatches = [];

    public $filteredOutGifts = [];

    public $loadingBatches = false;

    public $batchesLoaded = false;

    public $previewGifts = [];

    public $showPreview = false;

    public $showConfirmModal = false;

    public $syncInProgress = false;

    public $syncCompleted = false;

    public $syncResults = [];

    public $downloadUrl = '';

    protected function getBatchLabel(): string
    {
        return 'Manual Batch';
    }

    protected function getBatchFieldName(): string
    {
        return 'BATCH';
    }

    protected function getSyncExportSessionKey(): string
    {
        return 'manual_batch_sync_export';
    }

    protected function getPreviewExcelSessionKey(): string
    {
        return 'manual_batch_preview_excel_export';
    }

    protected function getSyncDownloadRoute(): string
    {
        return 'donor-gifts.manual-batch-sync-download';
    }

    protected function getPreviewExcelDownloadRoute(): string
    {
        return 'donor-gifts.manual-batch-preview-excel-download';
    }

    public function mount()
    {
        $this->authorizeAction('donor_gifts.view');
        $this->selectedDate = Carbon::now()->format('Y-m-d');
        $this->loadBatchesForDate();
    }

    public function updatedSelectedDate()
    {
        $this->selectedBatch = '';
        $this->previewGifts = [];
        $this->filteredOutGifts = [];
        $this->showPreview = false;
        $this->syncCompleted = false;
        $this->downloadUrl = '';
        $this->batchesLoaded = false;
        $this->loadBatchesForDate();
    }

    public function updatedSelectedBatch()
    {
        $this->previewGifts = [];
        $this->filteredOutGifts = [];
        $this->showPreview = false;
        $this->syncCompleted = false;
        $this->downloadUrl = '';
    }

    /**
     * Extract the batch group prefix by stripping trailing iterative numbers.
     *
     * Examples:
     *   ACH-001   → ACH
     *   ACH-002   → ACH
     *   EFT_20260220_1 → EFT_20260220
     *   CashBatch5     → CashBatch
     *   WIRE           → WIRE (no trailing number)
     *   123            → 123  (pure number stays as-is)
     */
    protected function extractBatchGroup(string $batchNo): string
    {
        $batchNo = trim($batchNo);

        if ($batchNo === '') {
            return '';
        }

        // If the entire value is a pure number, keep it as-is (it's a standalone batch)
        if (preg_match('/^\d+$/', $batchNo)) {
            return $batchNo;
        }

        // Strip trailing separator + number: e.g. "ACH-001" → "ACH", "EFT_20260220_1" → "EFT_20260220"
        if (preg_match('/^(.+?)[\s_\-\.]+(\d+)$/', $batchNo, $matches)) {
            return $matches[1];
        }

        // Strip trailing digits with no separator: e.g. "CashBatch5" → "CashBatch"
        if (preg_match('/^(.+?[a-zA-Z])(\d+)$/', $batchNo, $matches)) {
            return $matches[1];
        }

        return $batchNo;
    }

    /**
     * Load available manual batches for the selected date
     */
    public function loadBatchesForDate()
    {
        $this->availableBatches = [];
        $this->batchesLoaded = false;

        if (empty($this->selectedDate)) {
            return;
        }

        $this->loadingBatches = true;

        try {
            $giftService = app(DonorPerfectGiftService::class);
            $date = Carbon::parse($this->selectedDate);

            $result = $giftService->getGiftsWithMetadata($date, $date, '');

            if (! $result['success']) {
                $this->loadingBatches = false;
                $this->batchesLoaded = true;
                session()->flash('error', $result['error'] ?? 'Failed to load gifts');

                return;
            }

            $gifts = $result['gifts'];

            // Extract unique batch groups from BATCH UDF field
            $batches = $gifts
                ->pluck('BATCH')
                ->filter()
                ->map(fn ($batchNo) => $this->extractBatchGroup((string) $batchNo))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $this->availableBatches = $batches;
            $this->loadingBatches = false;
            $this->batchesLoaded = true;

        } catch (\Exception $e) {
            $this->loadingBatches = false;
            $this->batchesLoaded = true;
            Log::error('DonorPerfectManualBatchSync: Failed to load batches', [
                'date' => $this->selectedDate,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load batches: '.$e->getMessage());
        }
    }

    /**
     * Load preview of gifts for selected date and manual batch group
     */
    public function loadPreview()
    {
        if (empty($this->selectedDate) || empty($this->selectedBatch)) {
            session()->flash('error', 'Please select both a date and a batch group');

            return;
        }

        try {
            $giftService = app(DonorPerfectGiftService::class);
            $date = Carbon::parse($this->selectedDate);

            $result = $giftService->getGiftsWithMetadata($date, $date, '');

            if (! $result['success']) {
                session()->flash('error', $result['error'] ?? 'Failed to load gifts');

                return;
            }

            // Filter to gifts whose BATCH UDF maps to the selected group
            $batchGifts = $result['gifts']
                ->filter(function ($gift) {
                    $batchNo = $gift['BATCH'] ?? '';
                    if (empty($batchNo)) {
                        return false;
                    }

                    return $this->extractBatchGroup((string) $batchNo) === $this->selectedBatch;
                })
                ->values()
                ->toArray();

            // Apply sync filters to separate syncable gifts from filtered-out gifts
            $this->applySyncFilters($batchGifts);

        } catch (\Exception $e) {
            Log::error('DonorPerfectManualBatchSync: Failed to load preview', [
                'date' => $this->selectedDate,
                'batch' => $this->selectedBatch,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load preview: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.donor-perfect-manual-batch-sync')
            ->layout('components.layouts.app');
    }
}
