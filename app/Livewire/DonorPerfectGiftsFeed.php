<?php

namespace App\Livewire;

use App\Services\DonorPerfectGiftService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class DonorPerfectGiftsFeed extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';

    public $startDate;

    public $endDate;

    public $syncStatusFilter = 'all'; // all, success, failed, pending, not_synced

    public $sortField = 'gift_date';

    public $sortDirection = 'desc';

    public $showExportModal = false;

    public $showMappedCodes = true; // Toggle to show/hide mapped codes in table

    public $showDetailsModal = false;

    public $selectedGift = null;

    public $showSyncModal = false;

    public $selectedGiftsForSync = [];

    public $syncInProgress = false;

    public $useIntacctCodes = 0; // For export - 0 = DonorPerfect codes, 1 = Intacct mapped codes

    public $codeMappingEnabled = false;

    public $availableFields = []; // All available fields from gift data

    public $selectedFields = []; // Fields selected for export

    public $includeCheckImages = true; // Include check images in export

    public $zipPassword = ''; // Password for check images ZIP file

    public $pendingCheckImagesDownload = false; // Flag to trigger check images download via JavaScript

    public $showDownloadModal = false; // Show download modal with links

    public $dataFileUrl = ''; // URL for data file download

    public $checkImagesUrl = ''; // URL for check images ZIP download

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
        if (! $this->codeMappingEnabled) {
            $hasCodeMappings = \App\Models\DonorPerfectCodeMapping::exists();
            if ($hasCodeMappings) {
                $this->codeMappingEnabled = true;
            }
        }

        // Set default export fields
        $this->selectedFields = [
            'gift_id',
            'gift_date',
            'amount',
            'campaign',
            'solicit_code',
            'sub_solicit_code',
            'gl_code',
            'payment_type',
            'donor_id',
            'first_name',
            'last_name',
        ];
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

    public function updatedSyncStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

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
            $this->prepareExport($giftService, 'csv');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to prepare export: '.$e->getMessage());
            $this->showExportModal = false;
        }
    }

    private function prepareExport(DonorPerfectGiftService $giftService, string $format)
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        // Generate the data export file
        $result = $giftService->prepareExportFiles(
            $startDate,
            $endDate,
            $this->search,
            $this->selectedFields,
            $format,
            (bool) $this->useIntacctCodes,
            $this->includeCheckImages,
            $this->zipPassword
        );

        if (! $result['success']) {
            throw new \Exception($result['error'] ?? 'Export preparation failed');
        }

        // Store download tokens in session
        session()->put('export_downloads', $result);

        // Close export modal and show download modal
        $this->showExportModal = false;
        $this->showDownloadModal = true;

        // Set download URLs
        $this->dataFileUrl = route('donor-gifts.download-export', ['type' => 'data']);
        if ($this->includeCheckImages && isset($result['check_images_token'])) {
            $this->checkImagesUrl = route('donor-gifts.download-export', ['type' => 'images']);
        }
    }

    public function exportCheckImages(DonorPerfectGiftService $giftService)
    {
        try {
            Log::info('=== exportCheckImages method called ===', [
                'has_session_data' => session()->has('pending_check_images_export'),
                'session_keys' => array_keys(session()->all()),
            ]);

            $exportData = session()->get('pending_check_images_export');
            if (! $exportData) {
                Log::warning('No pending check images export data in session', [
                    'session_id' => session()->getId(),
                    'all_session_data' => session()->all(),
                ]);
                session()->flash('error', 'No pending check images export found.');

                return redirect()->route('donor-gifts.feed');
            }

            Log::info('Starting check images export', array_merge($exportData, [
                'data_keys' => array_keys($exportData),
            ]));

            $startDate = Carbon::parse($exportData['start_date']);
            $endDate = Carbon::parse($exportData['end_date']);
            $search = $exportData['search'] ?? '';
            $password = $exportData['password'] ?? '';

            Log::info('Parsed export parameters', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'search' => $search,
                'has_password' => ! empty($password),
            ]);

            $result = $giftService->exportCheckImagesOnly(
                $startDate,
                $endDate,
                $search,
                $password
            );

            if (! $result['success']) {
                throw new \Exception($result['error'] ?? 'Check images export failed');
            }

            session()->forget('pending_check_images_export');

            $filename = 'check_images_'.$startDate->format('Ymd').'_'.$endDate->format('Ymd').'.zip';

            return response()->download($result['zip_path'], $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to export check images: '.$e->getMessage());
        }
    }

    public function exportExcel(DonorPerfectGiftService $giftService)
    {
        try {
            $this->prepareExport($giftService, 'excel');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to prepare export: '.$e->getMessage());
            $this->showExportModal = false;
        }
    }

    public function exportPdf(DonorPerfectGiftService $giftService)
    {
        try {
            $this->prepareExport($giftService, 'pdf');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to prepare export: '.$e->getMessage());
            $this->showExportModal = false;
        }
    }

    public function closeDownloadModal()
    {
        $this->showDownloadModal = false;
        $this->dataFileUrl = '';
        $this->checkImagesUrl = '';
        session()->forget('export_downloads');
    }

    public function toggleExportModal()
    {
        $this->showExportModal = ! $this->showExportModal;

        // Populate available fields when opening modal
        if ($this->showExportModal) {
            $this->loadAvailableFields();
        }
    }

    public function loadAvailableFields()
    {
        $giftService = app(DonorPerfectGiftService::class);
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $result = $giftService->getGiftsWithMetadata($startDate, $endDate, $this->search);

        if ($result['success'] && $result['gifts']->isNotEmpty()) {
            // Get all available fields from first gift
            $firstGift = $result['gifts']->first();
            $this->availableFields = array_keys($firstGift);
        }
    }

    public function selectAllFields()
    {
        $this->selectedFields = $this->availableFields;
    }

    public function deselectAllFields()
    {
        $this->selectedFields = [];
    }

    public function resetToDefaultFields()
    {
        $this->selectedFields = [
            'gift_id',
            'gift_date',
            'amount',
            'campaign',
            'solicit_code',
            'sub_solicit_code',
            'gl_code',
            'payment_type',
            'donor_id',
            'first_name',
            'last_name',
        ];
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
        // Look for sync record in the sage_intacct_sync_items table
        $giftId = $giftData['gift_id'] ?? null;
        if (! $giftId) {
            return 'pending';
        }

        $syncItem = \App\Models\SageIntacctSyncItem::where('gift_id', $giftId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $syncItem) {
            return 'pending';
        }

        // Return the status from the sync item
        return $syncItem->status; // 'pending', 'success', or 'failed'
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

            // Get Sage Intacct settings
            $intacctSetting = \App\Models\SageIntacctSetting::getActive();

            if (! $intacctSetting) {
                throw new \Exception('Sage Intacct is not configured');
            }

            // Check auth method and initialize services accordingly
            if ($intacctSetting->isXmlApi()) {
                // XML mode - no user token needed
                $apiService = new \App\Services\SageIntacctApiService($intacctSetting);
                $syncService = new \App\Services\SageIntacctSyncService($apiService, $intacctSetting);
            } else {
                // OAuth mode - requires user token
                $userToken = auth()->user()->sageIntacctToken;

                if (! $userToken || ! $userToken->hasValidToken()) {
                    session()->flash('error', 'You must connect to Sage Intacct first.');
                    $this->showSyncModal = false;

                    return redirect()->route('intacct.oauth.authorize');
                }

                $apiService = new \App\Services\SageIntacctApiService($intacctSetting, $userToken);
                $syncService = new \App\Services\SageIntacctSyncService($apiService, $intacctSetting, $userToken);
            }

            // Get gifts to sync
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

            // Perform sync using Sage Intacct sync service
            $syncResult = $syncService->syncGifts($startDate, $endDate, auth()->id());

            $this->syncInProgress = false;
            $this->showSyncModal = false;
            $this->selectedGiftsForSync = [];

            if ($syncResult['success']) {
                $syncedCount = $syncResult['synced'] ?? 0;
                $failedCount = $syncResult['failed'] ?? 0;

                $message = "Sync completed: {$syncedCount} synced";
                if ($failedCount > 0) {
                    $message .= ", {$failedCount} failed";
                }

                session()->flash('success', $message);
            } else {
                throw new \Exception($syncResult['error'] ?? 'Sync failed');
            }

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

            // Filter by sync status if selected
            if ($this->syncStatusFilter !== 'all') {
                $gifts = $gifts->filter(function ($gift) {
                    $syncStatus = $this->getSyncStatus($gift);

                    switch ($this->syncStatusFilter) {
                        case 'success':
                            return $syncStatus === 'success';
                        case 'failed':
                            return $syncStatus === 'failed';
                        case 'pending':
                            return $syncStatus === 'pending';
                        case 'not_synced':
                            return $syncStatus === 'pending'; // Not synced yet
                        default:
                            return true;
                    }
                });
            }

            // Sort by selected field
            $gifts = $gifts->sortBy($this->sortField, SORT_REGULAR, $this->sortDirection === 'desc')
                ->values(); // Re-index after sorting

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
