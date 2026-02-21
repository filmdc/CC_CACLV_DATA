<?php

namespace App\Livewire;

use App\Services\DonorPerfectGiftService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DonorPerfectGiftsFeed extends Component
{
    use WithPagination, WithPermissionChecks;

    // Tab state
    #[Url]
    public $activeTab = 'gifts';

    #[Url]
    public $search = '';

    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    #[Url]
    public $syncStatusFilter = 'all'; // all, success, failed, pending, not_synced

    #[Url]
    public $giftTypeFilter = '';

    #[Url]
    public $paymentTypeFilter = '';

    #[Url]
    public $glCodeFilter = '';

    #[Url]
    public $campaignFilter = '';

    #[Url]
    public $solicitCodeFilter = '';

    #[Url]
    public $subSolicitCodeFilter = '';

    #[Url]
    public $checkScanBatchFilter = '';

    #[Url]
    public $sortField = 'gift_date';

    #[Url]
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

    // Field Mapping properties
    public $showAddMappingForm = false;

    public $editingMappingId = null;

    public $mapping_dp_field = '';

    public $mapping_intacct_field = '';

    public $mapping_is_required = false;

    public $mapping_transformation = '';

    // Code Mapping properties
    public $selectedFieldType = 'gl_code';

    public $mappings = [];

    public $editingCodeMappingId = null;

    public $editingIntacctCode = '';

    public $showOnlyUnmapped = false;

    public $showArchived = false;

    public $mappingNotes = '';

    // Conditional Code Mapping properties
    public $showConditionalMappingForm = false;

    public $editingConditionalMappingId = null;

    public $conditional_intacct_location_code = '';

    public $conditional_field_type = 'solicit_code';

    public $conditional_dp_code = '';

    public $conditional_intacct_code = '';

    public $conditional_priority = 0;

    public $conditional_notes = '';

    public $conditional_is_active = true;

    // Sync Filter properties
    public $showSyncFilterForm = false;

    public $editingSyncFilterId = null;

    public $sync_filter_name = '';

    public $sync_filter_field_name = '';

    public $sync_filter_operator = 'equals';

    public $sync_filter_value = '';

    public $sync_filter_action = 'exclude';

    public $sync_filter_priority = 0;

    public $sync_filter_description = '';

    public $sync_filter_is_active = true;

    // Sync History filters
    public $filterStatus = '';

    public $filterStartDate = '';

    public $filterEndDate = '';

    public function mount()
    {
        // Check permission
        $this->authorizeAction('donor_gifts.view');

        // Set default dates to last 7 days only if not already set via URL
        if (empty($this->startDate)) {
            $this->startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        }
        if (empty($this->endDate)) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }

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

    public function updatedGiftTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedPaymentTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedGlCodeFilter()
    {
        $this->resetPage();
    }

    public function updatedCampaignFilter()
    {
        $this->resetPage();
    }

    public function updatedSolicitCodeFilter()
    {
        $this->resetPage();
    }

    public function updatedSubSolicitCodeFilter()
    {
        $this->resetPage();
    }

    public function updatedCheckScanBatchFilter()
    {
        $this->resetPage();
    }

    public function clearAllFilters()
    {
        $this->giftTypeFilter = '';
        $this->paymentTypeFilter = '';
        $this->glCodeFilter = '';
        $this->campaignFilter = '';
        $this->solicitCodeFilter = '';
        $this->subSolicitCodeFilter = '';
        $this->checkScanBatchFilter = '';
        $this->syncStatusFilter = 'all';
        $this->search = '';
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
        $this->authorizeAction('donorperfect.sync.refresh');

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
                $visibleIds = $visibleGifts->pluck('gift_id')->toArray();

                // Check if all visible gifts are already selected
                $allVisibleSelected = count(array_intersect($visibleIds, $this->selectedGiftsForSync)) === count($visibleIds);

                if ($allVisibleSelected) {
                    // Deselect current page
                    $this->selectedGiftsForSync = array_values(array_diff($this->selectedGiftsForSync, $visibleIds));
                } else {
                    // Add current page to selection (merge, not replace)
                    $this->selectedGiftsForSync = array_values(array_unique(array_merge($this->selectedGiftsForSync, $visibleIds)));
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to select gifts: '.$e->getMessage());
        }
    }

    public function selectAllPages(DonorPerfectGiftService $giftService)
    {
        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            $result = $giftService->getGiftsWithMetadata($startDate, $endDate, $this->search);

            if ($result['success']) {
                // Select ALL gifts across all pages
                $this->selectedGiftsForSync = $result['gifts']->pluck('gift_id')->toArray();

                session()->flash('success', 'Selected all '.count($this->selectedGiftsForSync).' gifts across all pages.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to select all gifts: '.$e->getMessage());
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

    /**
     * Get current Sage Intacct environment string (sandbox or production)
     */
    private function getCurrentEnvironment(): string
    {
        $setting = \App\Models\SageIntacctSetting::first();

        if ($setting && ! $setting->isSandbox()) {
            return 'production';
        }

        return 'sandbox';
    }

    public function getSyncStatus($giftData)
    {
        // Check if this gift has been synced to Intacct
        // Look for sync record in the sage_intacct_sync_items table
        $giftId = $giftData['gift_id'] ?? null;
        if (! $giftId) {
            return 'pending';
        }

        // Filter by current environment so sandbox/production have independent status
        $currentEnvironment = $this->getCurrentEnvironment();

        $syncItem = \App\Models\SageIntacctSyncItem::where('gift_id', $giftId)
            ->where('environment', $currentEnvironment)
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
        // Check permission (allow new sync permission or legacy permission)
        if (! $this->canAny(['sage.sync.perform', 'intacct.sync'])) {
            session()->flash('error', 'You do not have permission to sync to Sage Intacct.');

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
            $giftsToSync = null;
            if (! empty($this->selectedGiftsForSync)) {
                // User selected specific gifts - sync only those
                $giftsToSync = $allGifts->whereIn('gift_id', $this->selectedGiftsForSync);

                Log::info('DonorPerfect: Syncing selected gifts only', [
                    'selected_count' => count($this->selectedGiftsForSync),
                    'filtered_count' => $giftsToSync->count(),
                    'selected_ids' => $this->selectedGiftsForSync,
                ]);

                // Validate we have gifts after filtering
                if ($giftsToSync->isEmpty()) {
                    throw new \Exception('No gifts found matching your selection');
                }
            } else {
                // No gifts selected - sync all VISIBLE gifts on current page only
                $perPage = 15;
                $currentPage = $this->getPage();
                $giftsToSync = $allGifts->forPage($currentPage, $perPage);

                Log::info('DonorPerfect: No gifts selected, syncing all visible gifts on current page', [
                    'page' => $currentPage,
                    'per_page' => $perPage,
                    'visible_count' => $giftsToSync->count(),
                ]);
            }

            // Perform sync using Sage Intacct sync service
            // Pass the filtered gifts array
            $syncResult = $syncService->syncGifts(
                $startDate,
                $endDate,
                auth()->id(),
                $giftsToSync->toArray()
            );

            $this->syncInProgress = false;
            $this->showSyncModal = false;
            $this->selectedGiftsForSync = [];

            if ($syncResult['success']) {
                $created = $syncResult['created'] ?? 0;
                $updated = $syncResult['updated'] ?? 0;
                $skipped = $syncResult['skipped'] ?? 0;
                $failed = $syncResult['failed'] ?? 0;
                $synced = $syncResult['synced'] ?? 0;

                $messageParts = [];
                if ($created > 0) {
                    $messageParts[] = "{$created} created";
                }
                if ($updated > 0) {
                    $messageParts[] = "{$updated} updated";
                }
                if ($skipped > 0) {
                    $messageParts[] = "{$skipped} skipped (no changes)";
                }
                if ($failed > 0) {
                    $messageParts[] = "{$failed} failed";
                }

                $message = 'Sync completed: '.implode(', ', $messageParts);

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

    // ========================================
    // TAB MANAGEMENT METHODS
    // ========================================

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        // Load initial data based on tab
        if ($tab === 'code_mappings') {
            $this->loadMappings($this->selectedFieldType);
        }
    }

    // ========================================
    // FIELD MAPPINGS TAB METHODS
    // ========================================

    public function showAddMapping()
    {
        $this->showAddMappingForm = true;
        $this->editingMappingId = null;
        $this->resetMappingForm();
    }

    public function editFieldMapping($mappingId)
    {
        $mapping = \App\Models\SageIntacctFieldMapping::find($mappingId);
        if ($mapping) {
            $this->editingMappingId = $mappingId;
            $this->showAddMappingForm = true;
            $this->mapping_dp_field = $mapping->donor_perfect_field;
            $this->mapping_intacct_field = $mapping->intacct_field;
            $this->mapping_is_required = $mapping->is_required;
            $this->mapping_transformation = $mapping->default_value ?? '';
        }
    }

    public function saveFieldMapping()
    {
        $this->authorizeAction('sage.mappings.edit');

        $this->validate([
            'mapping_dp_field' => 'required|string|max:255',
            'mapping_intacct_field' => 'required|string|max:255',
        ]);

        try {
            $settings = \App\Models\SageIntacctSetting::first();
            if (! $settings) {
                session()->flash('error', 'Please save Intacct credentials first.');

                return;
            }

            $data = [
                'setting_id' => $settings->id,
                'donor_perfect_field' => $this->mapping_dp_field,
                'intacct_field' => $this->mapping_intacct_field,
                'is_required' => $this->mapping_is_required,
                'default_value' => $this->mapping_transformation ?: null,
            ];

            if ($this->editingMappingId) {
                $mapping = \App\Models\SageIntacctFieldMapping::find($this->editingMappingId);
                if ($mapping) {
                    $mapping->update($data);
                    session()->flash('success', 'Field mapping updated successfully.');
                }
            } else {
                $maxOrder = \App\Models\SageIntacctFieldMapping::where('setting_id', $settings->id)->max('order') ?? 0;
                $data['order'] = $maxOrder + 1;
                \App\Models\SageIntacctFieldMapping::create($data);
                session()->flash('success', 'Field mapping created successfully.');
            }

            $this->showAddMappingForm = false;
            $this->resetMappingForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save mapping: '.$e->getMessage());
        }
    }

    public function deleteFieldMapping($mappingId)
    {
        $this->authorizeAction('sage.mappings.edit');

        try {
            $mapping = \App\Models\SageIntacctFieldMapping::find($mappingId);
            if ($mapping) {
                $mapping->delete();
                session()->flash('success', 'Field mapping deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete mapping: '.$e->getMessage());
        }
    }

    public function moveFieldMapping($mappingId, $direction)
    {
        $this->authorizeAction('sage.mappings.edit');

        try {
            $mapping = \App\Models\SageIntacctFieldMapping::find($mappingId);
            if (! $mapping) {
                return;
            }

            $currentOrder = $mapping->order;
            $newOrder = $direction === 'up' ? $currentOrder - 1 : $currentOrder + 1;

            $swapMapping = \App\Models\SageIntacctFieldMapping::where('setting_id', $mapping->setting_id)
                ->where('order', $newOrder)
                ->first();

            if ($swapMapping) {
                $mapping->update(['order' => $newOrder]);
                $swapMapping->update(['order' => $currentOrder]);
                session()->flash('success', 'Mapping order updated.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reorder mapping: '.$e->getMessage());
        }
    }

    public function cancelFieldMapping()
    {
        $this->showAddMappingForm = false;
        $this->resetMappingForm();
    }

    protected function resetMappingForm()
    {
        $this->mapping_dp_field = '';
        $this->mapping_intacct_field = '';
        $this->mapping_is_required = false;
        $this->mapping_transformation = '';
        $this->editingMappingId = null;
    }

    public function getAvailableDpFieldsProperty()
    {
        $dpSettings = \App\Models\DonorPerfectSetting::first();
        if ($dpSettings && ! empty($dpSettings->selected_fields)) {
            return $dpSettings->selected_fields;
        }

        return [];
    }

    // ========================================
    // CODE MAPPINGS TAB METHODS
    // ========================================

    public function selectFieldType($type)
    {
        $this->selectedFieldType = $type;
        $this->loadMappings($type);
        $this->resetPage();
    }

    public function loadMappings($fieldType)
    {
        $query = \App\Models\DonorPerfectCodeMapping::forFieldType($fieldType);

        if ($this->showOnlyUnmapped) {
            $query->unmapped();
        }

        if (! $this->showArchived) {
            $query->active();
        }

        $this->mappings = $query->orderBy('dp_code')->get();
    }

    public function discoverNewCodes()
    {
        $this->authorizeAction('intacct.configure');

        try {
            $giftService = new \App\Services\DonorPerfectGiftService;
            $result = $giftService->discoverAndSyncCodes(90);

            if ($result['success']) {
                session()->flash('success', "Code discovery complete! Found {$result['new']} new code(s).");
            } else {
                session()->flash('error', 'Code discovery failed: '.($result['error'] ?? 'Unknown error'));
            }

            $this->loadMappings($this->selectedFieldType);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to discover codes: '.$e->getMessage());
        }
    }

    public function editMapping($id)
    {
        $mapping = \App\Models\DonorPerfectCodeMapping::find($id);
        if ($mapping) {
            $this->editingCodeMappingId = $id;
            $this->editingIntacctCode = $mapping->intacct_code ?? '';
            $this->mappingNotes = $mapping->notes ?? '';
        }
    }

    public function saveMapping($id)
    {
        $this->authorizeAction('sage.code_mappings.edit');

        $this->validate([
            'editingIntacctCode' => 'required|string|max:255',
        ], [
            'editingIntacctCode.required' => 'Intacct code is required',
        ]);

        try {
            $mapping = \App\Models\DonorPerfectCodeMapping::find($id);
            if ($mapping) {
                $mapping->setMapping($this->editingIntacctCode);
                $mapping->notes = $this->mappingNotes;
                $mapping->save();

                session()->flash('success', 'Code mapping saved successfully.');
                $this->cancelEdit();
                $this->loadMappings($this->selectedFieldType);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save mapping: '.$e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->editingCodeMappingId = null;
        $this->editingIntacctCode = '';
        $this->mappingNotes = '';
    }

    public function toggleArchive($id)
    {
        $this->authorizeAction('sage.code_mappings.edit');

        try {
            $mapping = \App\Models\DonorPerfectCodeMapping::find($id);
            if ($mapping) {
                if ($mapping->is_archived) {
                    $mapping->markAsActive();
                    session()->flash('success', 'Code unarchived successfully.');
                } else {
                    $mapping->markAsArchived();
                    session()->flash('success', 'Code archived successfully.');
                }
                $this->loadMappings($this->selectedFieldType);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to toggle archive: '.$e->getMessage());
        }
    }

    public function bulkArchiveUnmapped()
    {
        $this->authorizeAction('sage.code_mappings.edit');

        try {
            $count = \App\Models\DonorPerfectCodeMapping::forFieldType($this->selectedFieldType)
                ->unmapped()
                ->active()
                ->update(['is_archived' => true]);

            session()->flash('success', "Archived {$count} unmapped code(s).");
            $this->loadMappings($this->selectedFieldType);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to archive codes: '.$e->getMessage());
        }
    }

    public function updatedShowOnlyUnmapped()
    {
        $this->loadMappings($this->selectedFieldType);
        $this->resetPage();
    }

    public function updatedShowArchived()
    {
        $this->loadMappings($this->selectedFieldType);
        $this->resetPage();
    }

    // Conditional Code Mapping Methods

    public function showCreateConditionalMappingForm()
    {
        $this->authorizeAction('intacct.configure');
        $this->resetConditionalMappingForm();
        $this->showConditionalMappingForm = true;
    }

    public function showEditConditionalMappingForm($id)
    {
        $this->authorizeAction('intacct.configure');

        $mapping = \App\Models\ConditionalCodeMapping::findOrFail($id);

        $this->editingConditionalMappingId = $mapping->id;
        $this->conditional_intacct_location_code = $mapping->intacct_location_code;
        $this->conditional_field_type = $mapping->field_type;
        $this->conditional_dp_code = $mapping->dp_code;
        $this->conditional_intacct_code = $mapping->intacct_code;
        $this->conditional_priority = $mapping->priority;
        $this->conditional_notes = $mapping->notes ?? '';
        $this->conditional_is_active = $mapping->is_active;

        $this->showConditionalMappingForm = true;
    }

    public function saveConditionalMapping()
    {
        $this->authorizeAction('sage.conditional_mappings.edit');

        $this->validate([
            'conditional_intacct_location_code' => 'required|string|max:255',
            'conditional_field_type' => 'required|in:solicit_code,sub_solicit_code',
            'conditional_dp_code' => 'required|string|max:255',
            'conditional_intacct_code' => 'required|string|max:255',
            'conditional_priority' => 'nullable|integer|min:0',
            'conditional_notes' => 'nullable|string',
        ]);

        try {
            if ($this->editingConditionalMappingId) {
                $mapping = \App\Models\ConditionalCodeMapping::findOrFail($this->editingConditionalMappingId);
                $mapping->update([
                    'intacct_location_code' => $this->conditional_intacct_location_code,
                    'field_type' => $this->conditional_field_type,
                    'dp_code' => $this->conditional_dp_code,
                    'intacct_code' => $this->conditional_intacct_code,
                    'priority' => $this->conditional_priority ?? 0,
                    'notes' => $this->conditional_notes,
                    'is_active' => $this->conditional_is_active,
                ]);
                session()->flash('success', 'Conditional mapping updated successfully.');
            } else {
                \App\Models\ConditionalCodeMapping::create([
                    'intacct_location_code' => $this->conditional_intacct_location_code,
                    'field_type' => $this->conditional_field_type,
                    'dp_code' => $this->conditional_dp_code,
                    'intacct_code' => $this->conditional_intacct_code,
                    'priority' => $this->conditional_priority ?? 0,
                    'notes' => $this->conditional_notes,
                    'is_active' => $this->conditional_is_active,
                ]);
                session()->flash('success', 'Conditional mapping created successfully.');
            }

            $this->cancelConditionalMappingForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save conditional mapping: '.$e->getMessage());
        }
    }

    public function deleteConditionalMapping($id)
    {
        $this->authorizeAction('sage.conditional_mappings.edit');

        try {
            $mapping = \App\Models\ConditionalCodeMapping::findOrFail($id);
            $mapping->delete();
            session()->flash('success', 'Conditional mapping deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete conditional mapping: '.$e->getMessage());
        }
    }

    public function toggleConditionalMappingActive($id)
    {
        $this->authorizeAction('intacct.configure');

        try {
            $mapping = \App\Models\ConditionalCodeMapping::findOrFail($id);
            $mapping->update(['is_active' => ! $mapping->is_active]);
            session()->flash('success', 'Conditional mapping status updated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update conditional mapping: '.$e->getMessage());
        }
    }

    public function cancelConditionalMappingForm()
    {
        $this->showConditionalMappingForm = false;
        $this->resetConditionalMappingForm();
    }

    private function resetConditionalMappingForm()
    {
        $this->editingConditionalMappingId = null;
        $this->conditional_intacct_location_code = '';
        $this->conditional_field_type = 'solicit_code';
        $this->conditional_dp_code = '';
        $this->conditional_intacct_code = '';
        $this->conditional_priority = 0;
        $this->conditional_notes = '';
        $this->conditional_is_active = true;
        $this->resetErrorBag();
    }

    // ========================================
    // SYNC HISTORY TAB METHODS
    // ========================================

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filterStatus = '';
        $this->filterStartDate = '';
        $this->filterEndDate = '';
        $this->resetPage();
    }

    // ========================================
    // SYNC FILTERS TAB METHODS
    // ========================================

    public function showCreateSyncFilterForm()
    {
        $this->authorizeAction('intacct.configure');
        $this->resetSyncFilterForm();
        $this->showSyncFilterForm = true;
    }

    public function showEditSyncFilterForm($id)
    {
        $this->authorizeAction('intacct.configure');

        $filter = \App\Models\SageIntacctSyncFilter::findOrFail($id);

        $this->editingSyncFilterId = $filter->id;
        $this->sync_filter_name = $filter->name;
        $this->sync_filter_field_name = $filter->field_name;
        $this->sync_filter_operator = $filter->operator;
        $this->sync_filter_value = $filter->value;
        $this->sync_filter_action = $filter->action;
        $this->sync_filter_priority = $filter->priority;
        $this->sync_filter_description = $filter->description ?? '';
        $this->sync_filter_is_active = $filter->is_active;

        $this->showSyncFilterForm = true;
    }

    public function saveSyncFilter()
    {
        $this->authorizeAction('sage.filters.edit');

        $this->validate([
            'sync_filter_name' => 'required|string|max:255',
            'sync_filter_field_name' => 'required|string|max:255',
            'sync_filter_operator' => 'required|in:equals,not_equals,contains,not_contains,greater_than,less_than,greater_or_equal,less_or_equal,is_empty,is_not_empty',
            'sync_filter_value' => 'nullable|string|max:255',
            'sync_filter_action' => 'required|in:exclude,include',
            'sync_filter_priority' => 'nullable|integer|min:0',
            'sync_filter_description' => 'nullable|string',
        ]);

        try {
            if ($this->editingSyncFilterId) {
                $filter = \App\Models\SageIntacctSyncFilter::findOrFail($this->editingSyncFilterId);
                $filter->update([
                    'name' => $this->sync_filter_name,
                    'field_name' => $this->sync_filter_field_name,
                    'operator' => $this->sync_filter_operator,
                    'value' => $this->sync_filter_value,
                    'action' => $this->sync_filter_action,
                    'priority' => $this->sync_filter_priority ?? 0,
                    'description' => $this->sync_filter_description,
                    'is_active' => $this->sync_filter_is_active,
                ]);
                session()->flash('success', 'Sync filter updated successfully.');
            } else {
                \App\Models\SageIntacctSyncFilter::create([
                    'name' => $this->sync_filter_name,
                    'field_name' => $this->sync_filter_field_name,
                    'operator' => $this->sync_filter_operator,
                    'value' => $this->sync_filter_value,
                    'action' => $this->sync_filter_action,
                    'filter_group' => 0,
                    'group_logic' => 'OR',
                    'priority' => $this->sync_filter_priority ?? 0,
                    'description' => $this->sync_filter_description,
                    'is_active' => $this->sync_filter_is_active,
                ]);
                session()->flash('success', 'Sync filter created successfully.');
            }

            $this->cancelSyncFilterForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save sync filter: '.$e->getMessage());
        }
    }

    public function deleteSyncFilter($id)
    {
        $this->authorizeAction('sage.filters.edit');

        try {
            $filter = \App\Models\SageIntacctSyncFilter::findOrFail($id);
            $filter->delete();
            session()->flash('success', 'Sync filter deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete sync filter: '.$e->getMessage());
        }
    }

    public function toggleSyncFilterActive($id)
    {
        $this->authorizeAction('intacct.configure');

        try {
            $filter = \App\Models\SageIntacctSyncFilter::findOrFail($id);
            $filter->update(['is_active' => ! $filter->is_active]);
            session()->flash('success', 'Sync filter status updated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update sync filter: '.$e->getMessage());
        }
    }

    public function cancelSyncFilterForm()
    {
        $this->showSyncFilterForm = false;
        $this->resetSyncFilterForm();
    }

    private function resetSyncFilterForm()
    {
        $this->editingSyncFilterId = null;
        $this->sync_filter_name = '';
        $this->sync_filter_field_name = '';
        $this->sync_filter_operator = 'equals';
        $this->sync_filter_value = '';
        $this->sync_filter_action = 'exclude';
        $this->sync_filter_priority = 0;
        $this->sync_filter_description = '';
        $this->sync_filter_is_active = true;
        $this->resetErrorBag();
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

            // Extract unique values for filter dropdowns (before filtering)
            $uniqueGiftTypes = $gifts->pluck('gift_type')->filter()->unique()->sort()->values();
            $uniquePaymentTypes = $gifts->pluck('payment_type')->filter()->unique()->sort()->values();
            $uniqueGlCodes = $gifts->pluck('gl_code')->filter()->unique()->sort()->values();
            $uniqueCampaigns = $gifts->pluck('campaign')->filter()->unique()->sort()->values();
            $uniqueSolicitCodes = $gifts->pluck('solicit_code')->filter()->unique()->sort()->values();
            $uniqueSubSolicitCodes = $gifts->pluck('sub_solicit_code')->filter()->unique()->sort()->values();
            $uniqueCheckScanBatches = $gifts->pluck('DP_CheckScan_Batch')->filter()->unique()->sort()->values();

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

            // Apply additional filters
            if (! empty($this->giftTypeFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    return ($gift['gift_type'] ?? '') === $this->giftTypeFilter;
                });
            }

            if (! empty($this->paymentTypeFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    return ($gift['payment_type'] ?? '') === $this->paymentTypeFilter;
                });
            }

            if (! empty($this->glCodeFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    return ($gift['gl_code'] ?? '') === $this->glCodeFilter;
                });
            }

            if (! empty($this->campaignFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    return ($gift['campaign'] ?? '') === $this->campaignFilter;
                });
            }

            if (! empty($this->solicitCodeFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    return ($gift['solicit_code'] ?? '') === $this->solicitCodeFilter;
                });
            }

            if (! empty($this->subSolicitCodeFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    return ($gift['sub_solicit_code'] ?? '') === $this->subSolicitCodeFilter;
                });
            }

            if (! empty($this->checkScanBatchFilter)) {
                $gifts = $gifts->filter(function ($gift) {
                    $batchValue = $gift['DP_CheckScan_Batch'] ?? '';

                    return stripos($batchValue, $this->checkScanBatchFilter) !== false;
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

            // Get tab-specific data
            $settings = \App\Models\SageIntacctSetting::first();

            // Field Mappings tab data
            $fieldMappings = $settings
                ? \App\Models\SageIntacctFieldMapping::where('setting_id', $settings->id)->orderBy('order')->get()
                : collect();

            // Sync History tab data - filtered by current environment
            $syncHistory = \App\Models\SageIntacctSyncHistory::query()
                ->with('resetBy')
                ->where('environment', $this->getCurrentEnvironment())
                ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
                ->when($this->filterStartDate, fn ($query) => $query->whereDate('started_at', '>=', $this->filterStartDate))
                ->when($this->filterEndDate, fn ($query) => $query->whereDate('started_at', '<=', $this->filterEndDate))
                ->orderBy('started_at', 'desc')
                ->paginate(15);

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
                // Tab data
                'fieldMappings' => $fieldMappings,
                'availableDpFields' => $this->availableDpFields,
                'syncHistory' => $syncHistory,
                // Filter options
                'uniqueGiftTypes' => $uniqueGiftTypes,
                'uniquePaymentTypes' => $uniquePaymentTypes,
                'uniqueGlCodes' => $uniqueGlCodes,
                'uniqueCampaigns' => $uniqueCampaigns,
                'uniqueSolicitCodes' => $uniqueSolicitCodes,
                'uniqueSubSolicitCodes' => $uniqueSubSolicitCodes,
                'uniqueCheckScanBatches' => $uniqueCheckScanBatches,
            ])->layout('components.layouts.app');
        } catch (\Exception $e) {
            Log::error('DonorPerfect Gifts Feed render error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $isActuallyConfigured = false;
            try {
                $isActuallyConfigured = $giftService->isConfigured();
            } catch (\Exception $configCheckException) {
                // Configuration check itself failed
            }

            return view('livewire.donor-perfect-gifts-feed', [
                'gifts' => collect(),
                'isConfigured' => $isActuallyConfigured,
                'errorMessage' => 'Error fetching gifts: '.$e->getMessage(),
                'isAuthError' => false,
                'totalGifts' => 0,
                'totalAmount' => 0,
            ])->layout('components.layouts.app');
        }
    }

    /**
     * Get the correct Intacct code mapping for display
     * Replicates the sync's conditional mapping priority logic
     *
     * @param  array  $gift  Gift data
     * @param  string  $fieldType  Field type (gl_code, campaign, solicit_code, sub_solicit_code)
     * @return array ['code' => string, 'source' => string] Source: 'conditional' or 'standard'
     */
    public function getDisplayCodeMapping(array $gift, string $fieldType): array
    {
        $dpCode = $gift[$fieldType] ?? '';

        if (empty($dpCode)) {
            return ['code' => '', 'source' => null, 'blocked' => false, 'reason' => null];
        }

        // For solicit_code and sub_solicit_code, check conditional mappings first
        if (in_array($fieldType, ['solicit_code', 'sub_solicit_code'])) {
            // First, we need to map the campaign to Intacct location
            $dpCampaign = $gift['campaign'] ?? '';
            $intacctLocation = null;

            if ($dpCampaign) {
                $intacctLocation = \App\Models\DonorPerfectCodeMapping::getMappingFor('campaign', $dpCampaign);
            }

            // If we have a location, check for conditional mapping
            if ($intacctLocation) {
                // Check if conditional rules exist for this location + field type
                $hasConditionalRules = \App\Models\ConditionalCodeMapping::hasRulesForLocation(
                    $intacctLocation,
                    $fieldType
                );

                if ($hasConditionalRules) {
                    // Conditional rules exist - try to get the mapping
                    $conditionalCode = \App\Models\ConditionalCodeMapping::getMapping(
                        $intacctLocation,
                        $fieldType,
                        $dpCode
                    );

                    if ($conditionalCode) {
                        return ['code' => $conditionalCode, 'source' => 'conditional', 'blocked' => false, 'reason' => null];
                    } else {
                        // Rules exist but this code is NOT in the allowlist - will be BLOCKED during sync
                        $standardCode = \App\Models\DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

                        return [
                            'code' => $standardCode ?: $dpCode,
                            'source' => 'standard',
                            'blocked' => true,
                            'reason' => "Not allowed for location {$intacctLocation}",
                        ];
                    }
                }
            }
        }

        // Fall back to standard mapping (no conditional blocking)
        $standardCode = \App\Models\DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

        return ['code' => $standardCode ?: $dpCode, 'source' => 'standard', 'blocked' => false, 'reason' => null];
    }
}
