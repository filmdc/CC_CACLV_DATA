<?php

namespace App\Livewire;

use App\Services\DonorPerfectGiftService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class DonorPerfectGiftsFeed extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';

    public $startDate;

    public $endDate;

    public $showExportModal = false;

    public $showMappedCodes = true; // Toggle to show/hide mapped codes in table

    public $showDetailsModal = false;

    public $selectedGift = null;

    public $showSyncModal = false;

    public $selectedGiftsForSync = [];

    public $syncInProgress = false;

    public $useIntacctCodes = false; // For export

    public $codeMappingEnabled = false;

    public function mount()
    {
        // Check permission
        $this->authorizeAction('donor_gifts.view');

        // Set default dates to last 7 days
        $this->startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');

        // Check if code mapping is enabled
        $intacctSetting = \App\Models\SageIntacctSetting::first();
        $this->codeMappingEnabled = $intacctSetting && $intacctSetting->isCodeMappingEnabled();

        // Also check if any code mappings exist (for display purposes)
        if (!$this->codeMappingEnabled) {
            $hasCodeMappings = \App\Models\DonorPerfectCodeMapping::exists();
            if ($hasCodeMappings) {
                $this->codeMappingEnabled = true;
            }
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function refreshData(DonorPerfectGiftService $giftService)
    {
        try {
            // Clear cache and reload
            $giftService->clearCache();
            session()->flash('success', 'Data refreshed successfully.');
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to refresh data: '.$e->getMessage());
        }
    }

    public function exportCsv(DonorPerfectGiftService $giftService)
    {
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            $csvContent = $giftService->exportToCsv($startDate, $endDate, $this->search, $this->useIntacctCodes);

            $filename = 'donor_gifts_'.$startDate->format('Ymd').'_'.$endDate->format('Ymd').'.csv';

            return response()->streamDownload(function () use ($csvContent) {
                echo $csvContent;
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to export CSV: '.$e->getMessage());
            $this->showExportModal = false;
        }
    }

    public function exportExcel(DonorPerfectGiftService $giftService)
    {
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            $excelContent = $giftService->exportToExcel($startDate, $endDate, $this->search, $this->useIntacctCodes);

            $filename = 'donor_gifts_'.$startDate->format('Ymd').'_'.$endDate->format('Ymd').'.xlsx';

            return response()->streamDownload(function () use ($excelContent) {
                echo $excelContent;
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to export Excel: '.$e->getMessage());
            $this->showExportModal = false;
        }
    }

    public function exportPdf(DonorPerfectGiftService $giftService)
    {
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            $pdfContent = $giftService->exportToPdf($startDate, $endDate, $this->search, $this->useIntacctCodes);

            $filename = 'donor_gifts_'.$startDate->format('Ymd').'_'.$endDate->format('Ymd').'.pdf';

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to export PDF: '.$e->getMessage());
            $this->showExportModal = false;
        }
    }

    public function toggleExportModal()
    {
        $this->showExportModal = ! $this->showExportModal;
    }

    public function showGiftDetails($giftData)
    {
        $this->selectedGift = $giftData;
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedGift = null;
    }

    public function getDonorPerfectGiftUrl($giftId)
    {
        // Build URL to view gift in DonorPerfect
        // Modern DonorPerfect URL pattern for viewing/editing gifts
        return 'https://www.donorperfect.net/m/ScreenDesigner/Gift/Edit/'.$giftId;
    }

    public function toggleSyncModal()
    {
        $this->showSyncModal = ! $this->showSyncModal;
        if (! $this->showSyncModal) {
            $this->selectedGiftsForSync = [];
        }
    }

    public function selectAllForSync(DonorPerfectGiftService $giftService)
    {
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            $result = $giftService->getGiftsWithMetadata($startDate, $endDate, $this->search);

            if ($result['success']) {
                $gifts = $result['gifts'];
                $perPage = 15;
                $currentPage = $this->getPage();
                $visibleGifts = $gifts->forPage($currentPage, $perPage);

                $this->selectedGiftsForSync = $visibleGifts->pluck('gift_id')->toArray();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to select gifts: '.$e->getMessage());
        }
    }

    public function deselectAllForSync()
    {
        $this->selectedGiftsForSync = [];
    }

    public function checkIntacctConfigured()
    {
        // Check if Sage Intacct integration is configured
        $intacctSetting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

        return $intacctSetting && $intacctSetting->isConfigured();
    }

    public function getSyncStatus($giftData)
    {
        // Check if this gift has been synced to Intacct
        // Look for sync record in the cache table
        $giftId = $giftData['gift_id'] ?? null;
        if (! $giftId) {
            return 'pending';
        }

        $cache = \App\Models\DonorPerfectGiftCache::where('gift_id', $giftId)->first();

        if (! $cache) {
            return 'pending';
        }

        // Check if intacct_synced_at field exists and is populated
        if (isset($cache->intacct_synced_at) && $cache->intacct_synced_at) {
            return 'synced';
        }

        // Check if sync failed
        if (isset($cache->intacct_sync_error) && $cache->intacct_sync_error) {
            return 'failed';
        }

        return 'pending';
    }

    public function syncToIntacct(DonorPerfectGiftService $giftService)
    {
        // Check permission
        if (! $this->can('intacct.sync')) {
            session()->flash('error', 'You do not have permission to sync to Intacct.');

            return;
        }

        try {
            $this->syncInProgress = true;

            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            // Get all gifts or selected gifts
            $result = $giftService->getGiftsWithMetadata($startDate, $endDate, $this->search);

            if (! $result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to fetch gifts');
            }

            $allGifts = $result['gifts'];

            // Filter to selected gifts if any are selected
            if (! empty($this->selectedGiftsForSync)) {
                $giftsToSync = $allGifts->whereIn('gift_id', $this->selectedGiftsForSync);
            } else {
                // Sync all visible gifts on current page
                $perPage = 15;
                $currentPage = $this->getPage();
                $giftsToSync = $allGifts->forPage($currentPage, $perPage);
            }

            if ($giftsToSync->isEmpty()) {
                throw new \Exception('No gifts selected for sync');
            }

            $syncedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($giftsToSync as $gift) {
                try {
                    // TODO: Implement actual Intacct API sync
                    // For now, just mark as synced in the cache
                    $giftId = $gift['gift_id'] ?? null;

                    if ($giftId) {
                        $cache = \App\Models\DonorPerfectGiftCache::updateOrCreate(
                            ['gift_id' => $giftId],
                            [
                                'gift_data' => json_encode($gift),
                                'intacct_synced_at' => now(),
                                'intacct_sync_error' => null,
                            ]
                        );
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Gift {$gift['gift_id']}: ".$e->getMessage();

                    // Log the error in the cache
                    if (isset($gift['gift_id'])) {
                        \App\Models\DonorPerfectGiftCache::updateOrCreate(
                            ['gift_id' => $gift['gift_id']],
                            [
                                'gift_data' => json_encode($gift),
                                'intacct_sync_error' => $e->getMessage(),
                            ]
                        );
                    }
                }
            }

            $this->syncInProgress = false;
            $this->showSyncModal = false;
            $this->selectedGiftsForSync = [];

            $message = "Sync completed: {$syncedCount} synced";
            if ($failedCount > 0) {
                $message .= ", {$failedCount} failed";
            }

            session()->flash('success', $message);

            if (! empty($errors)) {
                session()->flash('error', 'Some errors occurred: '.implode('; ', array_slice($errors, 0, 3)));
            }

        } catch (\Exception $e) {
            $this->syncInProgress = false;
            session()->flash('error', 'Sync failed: '.$e->getMessage());
        }
    }

    public function render(DonorPerfectGiftService $giftService)
    {
        try {
            // Check if API is configured
            if (! $giftService->isConfigured()) {
                return view('livewire.donor-perfect-gifts-feed', [
                    'gifts' => collect(),
                    'isConfigured' => false,
                    'errorMessage' => 'DonorPerfect API is not configured. Please contact your administrator to configure the connection.',
                ])->layout('components.layouts.app');
            }

            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            // Get result with metadata from service
            $result = $giftService->getGiftsWithMetadata($startDate, $endDate, $this->search);

            // Check for errors (including authentication errors)
            if (! $result['success']) {
                $isAuthError = $result['auth_error'] ?? false;

                return view('livewire.donor-perfect-gifts-feed', [
                    'gifts' => collect(),
                    'isConfigured' => true,
                    'errorMessage' => $result['error'] ?? 'Unknown error occurred',
                    'isAuthError' => $isAuthError,
                    'totalGifts' => 0,
                    'totalAmount' => 0,
                ])->layout('components.layouts.app');
            }

            $gifts = $result['gifts'];

            // Paginate results
            $perPage = 15;
            $currentPage = $this->getPage();
            $paginatedGifts = new \Illuminate\Pagination\LengthAwarePaginator(
                $gifts->forPage($currentPage, $perPage),
                $gifts->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url()]
            );

            return view('livewire.donor-perfect-gifts-feed', [
                'gifts' => $paginatedGifts,
                'isConfigured' => true,
                'errorMessage' => null,
                'isAuthError' => false,
                'totalGifts' => $gifts->count(),
                'totalAmount' => $gifts->sum('amount'),
                'intacctConfigured' => $this->checkIntacctConfigured(),
                'selectedCount' => count($this->selectedGiftsForSync),
                'codeMappingEnabled' => $this->codeMappingEnabled,
                'useIntacctCodes' => $this->useIntacctCodes,
            ])->layout('components.layouts.app');
        } catch (\Exception $e) {
            return view('livewire.donor-perfect-gifts-feed', [
                'gifts' => collect(),
                'isConfigured' => false,
                'errorMessage' => 'Error fetching gifts: '.$e->getMessage(),
                'isAuthError' => false,
            ])->layout('components.layouts.app');
        }
    }
}
