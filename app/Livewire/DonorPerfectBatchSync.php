<?php

namespace App\Livewire;

use App\Services\DonorPerfectGiftService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DonorPerfectBatchSync extends Component
{
    use WithPermissionChecks;

    public $selectedDate;

    public $selectedBatch = '';

    public $availableBatches = [];

    public $loadingBatches = false;

    public $batchesLoaded = false;

    public $previewGifts = [];

    public $showPreview = false;

    public $showConfirmModal = false;

    public $syncInProgress = false;

    public $syncCompleted = false;

    public $syncResults = [];

    public $downloadUrl = '';

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
        $this->showPreview = false;
        $this->syncCompleted = false;
        $this->downloadUrl = '';
        $this->batchesLoaded = false;
        $this->loadBatchesForDate();
    }

    public function updatedSelectedBatch()
    {
        $this->previewGifts = [];
        $this->showPreview = false;
        $this->syncCompleted = false;
        $this->downloadUrl = '';
    }

    /**
     * Load available batches for the selected date
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

            // Extract unique batch numbers (part before underscore)
            $batches = $gifts
                ->pluck('DP_CheckScan_Batch')
                ->filter()
                ->map(function ($batchId) {
                    // Extract batch number (part before underscore)
                    $parts = explode('_', $batchId);

                    return $parts[0] ?? null;
                })
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
            Log::error('DonorPerfectBatchSync: Failed to load batches', [
                'date' => $this->selectedDate,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load batches: '.$e->getMessage());
        }
    }

    /**
     * Load preview of gifts for selected date and batch
     */
    public function loadPreview()
    {
        if (empty($this->selectedDate) || empty($this->selectedBatch)) {
            session()->flash('error', 'Please select both a date and a batch');

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

            // Filter to selected batch (matching prefix before underscore)
            $this->previewGifts = $result['gifts']
                ->filter(function ($gift) {
                    $batchId = $gift['DP_CheckScan_Batch'] ?? '';
                    if (empty($batchId)) {
                        return false;
                    }
                    $parts = explode('_', $batchId);
                    $batchPrefix = $parts[0] ?? '';

                    return $batchPrefix === $this->selectedBatch;
                })
                ->values()
                ->toArray();

            $this->showPreview = true;

        } catch (\Exception $e) {
            Log::error('DonorPerfectBatchSync: Failed to load preview', [
                'date' => $this->selectedDate,
                'batch' => $this->selectedBatch,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load preview: '.$e->getMessage());
        }
    }

    /**
     * Show confirmation modal before sync
     */
    public function confirmSync()
    {
        if (empty($this->previewGifts)) {
            session()->flash('error', 'No gifts to sync. Please load preview first.');

            return;
        }

        $this->showConfirmModal = true;
    }

    /**
     * Cancel sync confirmation
     */
    public function cancelSync()
    {
        $this->showConfirmModal = false;
    }

    /**
     * Perform the sync to Sage Intacct
     */
    public function performSync()
    {
        if (! $this->canAny(['sage.sync.perform', 'intacct.sync'])) {
            session()->flash('error', 'You do not have permission to sync to Sage Intacct.');

            return;
        }

        $this->showConfirmModal = false;
        $this->syncInProgress = true;

        try {
            $giftService = app(DonorPerfectGiftService::class);
            $date = Carbon::parse($this->selectedDate);

            // Get Sage Intacct settings
            $intacctSetting = \App\Models\SageIntacctSetting::getActive();

            if (! $intacctSetting) {
                throw new \Exception('Sage Intacct is not configured');
            }

            // Initialize API and sync services
            if ($intacctSetting->isXmlApi()) {
                $apiService = new \App\Services\SageIntacctApiService($intacctSetting);
                $syncService = new \App\Services\SageIntacctSyncService($apiService, $intacctSetting);
            } else {
                $userToken = auth()->user()->sageIntacctToken;

                if (! $userToken || ! $userToken->hasValidToken()) {
                    session()->flash('error', 'You must connect to Sage Intacct first.');
                    $this->syncInProgress = false;

                    return;
                }

                $apiService = new \App\Services\SageIntacctApiService($intacctSetting, $userToken);
                $syncService = new \App\Services\SageIntacctSyncService($apiService, $intacctSetting, $userToken);
            }

            // Perform sync with the preview gifts
            $syncResult = $syncService->syncGifts(
                $date,
                $date,
                auth()->id(),
                $this->previewGifts
            );

            $this->syncInProgress = false;

            if ($syncResult['success']) {
                $this->syncCompleted = true;
                $this->syncResults = $syncResult;

                // Generate CSV export
                $this->generateSyncExport();

                $created = $syncResult['created'] ?? 0;
                $failed = $syncResult['failed'] ?? 0;
                $skipped = $syncResult['skipped'] ?? 0;

                session()->flash('success', "Batch sync completed: {$created} created, {$skipped} skipped, {$failed} failed");
            } else {
                throw new \Exception($syncResult['error'] ?? 'Sync failed');
            }

        } catch (\Exception $e) {
            $this->syncInProgress = false;
            Log::error('DonorPerfectBatchSync: Sync failed', [
                'date' => $this->selectedDate,
                'batch' => $this->selectedBatch,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Sync failed: '.$e->getMessage());
        }
    }

    /**
     * Generate CSV export of synced gifts
     */
    private function generateSyncExport()
    {
        try {
            $filename = 'batch_sync_'.$this->selectedDate.'_batch_'.$this->selectedBatch.'_'.time().'.csv';
            $filepath = storage_path('app/exports/'.$filename);

            // Ensure exports directory exists
            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $handle = fopen($filepath, 'w');

            // Write header
            fputcsv($handle, [
                'Gift ID',
                'Gift Date',
                'Amount',
                'Donor Name',
                'GL Code',
                'GL Code (Intacct)',
                'Campaign',
                'Location (Intacct)',
                'Solicit Code',
                'Project (Intacct)',
                'Sub-Solicit Code',
                'Service (Intacct)',
                'Payment Type',
                'Check Scan Batch',
                'Has Check Image',
                'Sync Status',
            ]);

            // Write gift data
            foreach ($this->previewGifts as $gift) {
                $hasCheckImage = ! empty($gift['IMAGE_CHECK']) ? 'Yes' : 'No';
                $donorName = trim(($gift['first_name'] ?? '').' '.($gift['last_name'] ?? ''));

                // Get mapped codes
                $glMapping = $this->getMappedCode($gift, 'gl_code');
                $campaignMapping = $this->getMappedCode($gift, 'campaign');
                $solicitMapping = $this->getMappedCode($gift, 'solicit_code');
                $subSolicitMapping = $this->getMappedCode($gift, 'sub_solicit_code');

                fputcsv($handle, [
                    $gift['gift_id'] ?? '',
                    $gift['gift_date'] ?? '',
                    $gift['amount'] ?? '',
                    $donorName ?: 'Anonymous',
                    $gift['gl_code'] ?? '',
                    $glMapping['code'] ?? '',
                    $gift['campaign'] ?? '',
                    $campaignMapping['code'] ?? '',
                    $gift['solicit_code'] ?? '',
                    $solicitMapping['code'] ?? '',
                    $gift['sub_solicit_code'] ?? '',
                    $subSolicitMapping['code'] ?? '',
                    $gift['payment_type'] ?? '',
                    $gift['DP_CheckScan_Batch'] ?? '',
                    $hasCheckImage,
                    'Synced',
                ]);
            }

            // Add summary section
            fputcsv($handle, []);
            fputcsv($handle, ['--- SYNC SUMMARY ---']);
            fputcsv($handle, ['Sync Date', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Gift Date', $this->selectedDate]);
            fputcsv($handle, ['Batch Number', $this->selectedBatch]);
            fputcsv($handle, ['Total Gifts', count($this->previewGifts)]);
            fputcsv($handle, ['Total Amount', '$'.number_format(collect($this->previewGifts)->sum('amount'), 2)]);
            fputcsv($handle, ['Gifts with Check Images', collect($this->previewGifts)->filter(fn ($g) => ! empty($g['IMAGE_CHECK']))->count()]);
            fputcsv($handle, ['Created', $this->syncResults['created'] ?? 0]);
            fputcsv($handle, ['Skipped', $this->syncResults['skipped'] ?? 0]);
            fputcsv($handle, ['Failed', $this->syncResults['failed'] ?? 0]);
            fputcsv($handle, ['Synced By', auth()->user()->name ?? 'Unknown']);

            fclose($handle);

            // Store filename in session for download
            session()->put('batch_sync_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            $this->downloadUrl = route('donor-gifts.batch-sync-download');

        } catch (\Exception $e) {
            Log::error('DonorPerfectBatchSync: Failed to generate export', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get total amount of preview gifts
     */
    public function getPreviewTotalProperty()
    {
        return collect($this->previewGifts)->sum('amount');
    }

    /**
     * Get count of gifts with check images
     */
    public function getCheckImageCountProperty()
    {
        return collect($this->previewGifts)->filter(fn ($g) => ! empty($g['IMAGE_CHECK']))->count();
    }

    /**
     * Check if Intacct is configured
     */
    public function getIntacctConfiguredProperty()
    {
        $intacctSetting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

        return $intacctSetting && $intacctSetting->isConfigured();
    }

    /**
     * Check if code mapping is enabled
     */
    public function getCodeMappingEnabledProperty()
    {
        $intacctSetting = \App\Models\SageIntacctSetting::first();

        return $intacctSetting && $intacctSetting->isCodeMappingEnabled();
    }

    /**
     * Get the mapped Intacct code for a field
     */
    public function getMappedCode(array $gift, string $fieldType): array
    {
        $dpCode = $gift[$fieldType] ?? '';

        if (empty($dpCode)) {
            return ['code' => '', 'source' => null];
        }

        // For solicit_code and sub_solicit_code, check conditional mappings first
        if (in_array($fieldType, ['solicit_code', 'sub_solicit_code'])) {
            $dpCampaign = $gift['campaign'] ?? '';
            $intacctLocation = null;

            if ($dpCampaign) {
                $intacctLocation = \App\Models\DonorPerfectCodeMapping::getMappingFor('campaign', $dpCampaign);
            }

            if ($intacctLocation) {
                $conditionalCode = \App\Models\ConditionalCodeMapping::getMapping(
                    $intacctLocation,
                    $fieldType,
                    $dpCode
                );

                if ($conditionalCode) {
                    return ['code' => $conditionalCode, 'source' => 'conditional'];
                }
            }
        }

        // Fall back to standard mapping
        $standardCode = \App\Models\DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

        return ['code' => $standardCode ?: '', 'source' => $standardCode ? 'standard' : null];
    }

    public function render()
    {
        return view('livewire.donor-perfect-batch-sync')
            ->layout('components.layouts.app');
    }
}
