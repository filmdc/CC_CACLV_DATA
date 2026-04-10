<?php

namespace App\Livewire;

use App\Models\DonorPerfectSetting;
use App\Models\SageIntacctFieldMapping;
use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncHistory;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SageIntacctConfiguration extends Component
{
    use WithFileUploads, WithPagination, WithPermissionChecks;

    // Tab state
    #[Url(as: 'tab')]
    public $activeTab = 'credentials';

    // Authentication Method
    public $auth_method = 'xml';

    // REST API Credentials (App-level)
    public $company_id = '';

    public $client_id = '';

    public $client_secret = '';

    public $redirect_uri = '';

    // XML Web Services Credentials (Optional/Legacy)
    public $user_id = '';

    public $sender_id = '';

    public $sender_password = '';

    public $web_services_user_id = '';

    public $web_services_password = '';

    // Dimensional Accounting (Optional)
    public $location_id = '';

    public $department_id = '';

    public $class_id = '';

    // Settings
    public $custom_object_name = 'Donor Perfect Gifts';

    // Flags
    public $is_active = false;

    public $code_mapping_enabled = false;

    // Sync Destination Settings
    public $use_other_receipts = true;

    public $use_undeposited_funds = true;

    public $bank_account_id = '';

    public $undeposited_gl_account = '10300';

    // Check Image Settings
    public $include_check_images = true;

    public $attachment_folder_name = 'DP_Check_Images';

    public $attachment_max_size_mb = 10;

    // Custom Field Names
    public $gift_id_field_name = 'GIFT_ID';

    // Default Dimension Settings
    public $default_service_id = 'S14999';

    // Connection test
    public $testConnectionResult = null;

    public $showTestResult = false;

    // Field Mapping
    public $showAddMappingForm = false;

    public $mapping_dp_field = '';

    public $mapping_intacct_field = '';

    public $mapping_is_required = false;

    public $mapping_transformation = '';

    // Code Mapping properties
    public $selectedFieldType = 'gl_code';

    public $mappings = [];

    public $editingMappingId = null;

    public $editingIntacctCode = '';

    public $showOnlyUnmapped = false;

    public $showArchived = false;

    public $mappingNotes = '';

    // Code Mapping Import
    public $showImportModal = false;

    public $importFile = null;

    public $importResults = null;

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

    protected $rules = [
        'auth_method' => 'required|in:xml,oauth',
        'company_id' => 'required|string|max:255',
        'client_id' => 'required_if:auth_method,oauth|nullable|string|max:255',
        'client_secret' => 'required_if:auth_method,oauth|nullable|string',
        'redirect_uri' => 'required_if:auth_method,oauth|nullable|url',
        'sender_id' => 'required_if:auth_method,xml|nullable|string|max:255',
        'sender_password' => 'required_if:auth_method,xml|nullable|string|max:255',
        'web_services_user_id' => 'required_if:auth_method,xml|nullable|string|max:255',
        'web_services_password' => 'required_if:auth_method,xml|nullable|string|max:255',
        'user_id' => 'nullable|string|max:255',
        'location_id' => 'nullable|string|max:255',
        'department_id' => 'nullable|string|max:255',
        'class_id' => 'nullable|string|max:255',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'company_id.required' => 'Company ID is required.',
        'client_id.required' => 'Client ID is required for REST API authentication.',
        'client_secret.required' => 'Client Secret is required for REST API authentication.',
        'redirect_uri.required' => 'Redirect URI is required for OAuth.',
        'redirect_uri.url' => 'Redirect URI must be a valid URL.',
    ];

    public function mount()
    {
        // Allow viewing if user has sync view permission or admin permission
        if (! $this->canAny(['sage.sync.view', 'intacct.configure'])) {
            abort(403, 'Unauthorized access to Sage Intacct configuration.');
        }

        $this->loadExistingSettings();
    }

    /**
     * Check if user can edit API configuration (admin only)
     */
    protected function canEditApiConfig(): bool
    {
        return $this->can('intacct.configure') || $this->isAdmin();
    }

    protected function loadExistingSettings()
    {
        $settings = SageIntacctSetting::first();

        if ($settings) {
            // Load non-encrypted fields first
            $this->auth_method = $settings->auth_method ?? 'xml';
            $this->company_id = $settings->company_id ?? 'CACLV-sandbox';
            $this->redirect_uri = $settings->redirect_uri ?? route('intacct.oauth.callback');
            $this->user_id = $settings->user_id ?? '';
            $this->location_id = $settings->location_id ?? '';
            $this->department_id = $settings->department_id ?? '';
            $this->class_id = $settings->class_id ?? '';
            $this->custom_object_name = $settings->custom_object_name ?? 'Donor Perfect Gifts';
            $this->is_active = $settings->is_active ?? false;
            $this->code_mapping_enabled = $settings->code_mapping_enabled ?? false;
            $this->web_services_user_id = $settings->web_services_user_id ?? '';

            // Sync destination settings
            $this->use_other_receipts = $settings->use_other_receipts ?? true;
            $this->use_undeposited_funds = $settings->use_undeposited_funds ?? true;
            $this->bank_account_id = $settings->bank_account_id ?? '';
            $this->undeposited_gl_account = $settings->undeposited_gl_account ?? '10300';

            // Check image settings
            $this->include_check_images = $settings->include_check_images ?? true;
            $this->attachment_folder_name = $settings->attachment_folder_name ?? 'DP_Check_Images';
            $this->attachment_max_size_mb = $settings->attachment_max_size_mb ?? 10;

            // Custom field name settings
            $this->gift_id_field_name = $settings->gift_id_field_name ?? 'DP_GIFT_ID';

            // Default dimension settings
            $this->default_service_id = $settings->default_service_id ?? 'S14999';

            // Load encrypted fields with fallback - these may fail if APP_KEY changed
            try {
                $this->client_id = $settings->client_id ?? '';
                $this->client_secret = $settings->client_secret ?? '';
                $this->sender_id = $settings->sender_id ?? '';
                $this->sender_password = $settings->sender_password ?? '';
                $this->web_services_password = $settings->web_services_password ?? '';
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                \Illuminate\Support\Facades\Log::warning('Sage Intacct: encrypted credentials could not be decrypted (APP_KEY may have changed). Please re-enter credentials.', [
                    'error' => $e->getMessage(),
                ]);
                $this->client_id = '';
                $this->client_secret = '';
                $this->sender_id = '';
                $this->sender_password = '';
                $this->web_services_password = '';
                session()->flash('error', 'Encrypted credentials could not be loaded (encryption key may have changed). Please re-enter your API credentials and save.');
            }
        } else {
            // No settings exist yet - default to XML for firewalled environments
            $this->auth_method = 'xml';
            $this->company_id = 'CACLV-sandbox';
            $this->redirect_uri = route('intacct.oauth.callback');
            $this->code_mapping_enabled = false;
        }

        $this->loadMappings($this->selectedFieldType);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        if ($tab === 'code_mappings') {
            $this->loadMappings($this->selectedFieldType);
        }

        $this->resetPage();
    }

    public function saveCredentials()
    {
        // Only admins can edit API credentials
        if (! $this->canEditApiConfig()) {
            abort(403, 'Only administrators can modify API configuration.');
        }

        $this->validate();

        try {
            $settings = SageIntacctSetting::first();

            $data = [
                'auth_method' => $this->auth_method,
                'company_id' => $this->company_id,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'user_id' => $this->user_id,
                'sender_id' => $this->sender_id,
                'sender_password' => $this->sender_password,
                'web_services_user_id' => $this->web_services_user_id,
                'web_services_password' => $this->web_services_password,
                'location_id' => $this->location_id,
                'department_id' => $this->department_id,
                'class_id' => $this->class_id,
                'custom_object_name' => $this->custom_object_name,
                'is_active' => $this->is_active,
                'code_mapping_enabled' => $this->code_mapping_enabled,
                'endpoint_url' => $this->auth_method === 'xml'
                    ? 'https://api.intacct.com/ia/xml/xmlgw.phtml'
                    : 'https://api.intacct.com',
                // Sync destination settings
                'use_other_receipts' => $this->use_other_receipts,
                'use_undeposited_funds' => $this->use_undeposited_funds,
                'bank_account_id' => $this->bank_account_id,
                'undeposited_gl_account' => $this->undeposited_gl_account,
                // Check image settings
                'include_check_images' => $this->include_check_images,
                'attachment_folder_name' => $this->attachment_folder_name,
                'attachment_max_size_mb' => $this->attachment_max_size_mb,
                // Custom field name settings
                'gift_id_field_name' => $this->gift_id_field_name,
                // Default dimension settings
                'default_service_id' => $this->default_service_id,
            ];

            if ($settings) {
                // Build a fresh model instance to avoid decrypting old encrypted
                // values which fails if the APP_KEY has changed since they were stored.
                $fresh = new SageIntacctSetting();
                $fresh->exists = true;
                $fresh->id = $settings->id;
                $fresh->forceFill($data)->save();
            } else {
                SageIntacctSetting::create($data);
            }

            session()->flash('success', 'Sage Intacct credentials saved successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save credentials: '.$e->getMessage());
        }
    }

    public function disconnectMyAccount()
    {
        $user = auth()->user();
        $token = $user->sageIntacctToken;

        if ($token) {
            $token->delete();
            session()->flash('success', 'Your Sage Intacct account has been disconnected.');
        }
    }

    public function showAddMapping()
    {
        $this->showAddMappingForm = true;
        $this->editingMappingId = null;
        $this->resetMappingForm();
    }

    public function editFieldMapping($mappingId)
    {
        $mapping = SageIntacctFieldMapping::find($mappingId);
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
            $settings = SageIntacctSetting::first();
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
                $mapping = SageIntacctFieldMapping::find($this->editingMappingId);
                if ($mapping) {
                    $mapping->update($data);
                    session()->flash('success', 'Field mapping updated successfully.');
                }
            } else {
                // Get the highest order value and add 1
                $maxOrder = SageIntacctFieldMapping::where('setting_id', $settings->id)
                    ->max('order') ?? 0;
                $data['order'] = $maxOrder + 1;

                SageIntacctFieldMapping::create($data);
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
            $mapping = SageIntacctFieldMapping::find($mappingId);
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
            $mapping = SageIntacctFieldMapping::find($mappingId);
            if (! $mapping) {
                return;
            }

            $currentOrder = $mapping->order;
            $newOrder = $direction === 'up' ? $currentOrder - 1 : $currentOrder + 1;

            // Find the mapping to swap with
            $swapMapping = SageIntacctFieldMapping::where('setting_id', $mapping->setting_id)
                ->where('order', $newOrder)
                ->first();

            if ($swapMapping) {
                // Swap orders
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

    public function getAvailableDpFieldsProperty()
    {
        $dpSettings = DonorPerfectSetting::first();
        if ($dpSettings && ! empty($dpSettings->selected_fields)) {
            return $dpSettings->selected_fields;
        }

        return [];
    }

    // Code Mapping Methods

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
        $this->authorizeAction('sage.code_mappings.edit');

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
            $this->editingMappingId = $id;
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
        $this->editingMappingId = null;
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

    // ========================================================================
    // Code Mapping Import Methods
    // ========================================================================

    public function showImportMappings()
    {
        $this->authorizeAction('sage.code_mappings.edit');
        $this->showImportModal = true;
        $this->importFile = null;
        $this->importResults = null;
    }

    public function cancelImport()
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importResults = null;
    }

    public function downloadImportTemplate()
    {
        $fieldType = $this->selectedFieldType;
        $isConditional = in_array($fieldType, ['solicit_code', 'sub_solicit_code']);

        if ($isConditional) {
            $headers = "intacct_location_code,dp_code,intacct_code,priority,notes\n";
            $example = "P1000,ANNLFUND,F8000,0,Optional note\nP1000,CAPITAL,F8100,0,\nP1400,ANNLFUND,F9000,0,Different location\n";
        } else {
            $headers = "dp_code,intacct_code,notes\n";
            $example = "EXAMPLE_CODE,1000,Optional note\n";
        }

        return response()->streamDownload(function () use ($headers, $example) {
            echo $headers.$example;
        }, "code_mapping_template_{$fieldType}.csv", ['Content-Type' => 'text/csv']);
    }

    public function importCodeMappings()
    {
        $this->authorizeAction('sage.code_mappings.edit');

        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'importFile.required' => 'Please select a CSV file to import.',
            'importFile.mimes' => 'File must be a CSV file.',
            'importFile.max' => 'File must be less than 2MB.',
        ]);

        try {
            $path = $this->importFile->getRealPath();
            $handle = fopen($path, 'r');

            if (! $handle) {
                session()->flash('error', 'Could not open the uploaded file.');

                return;
            }

            // Read header row
            $header = fgetcsv($handle);
            if (! $header) {
                fclose($handle);
                session()->flash('error', 'CSV file is empty.');

                return;
            }

            // Normalize headers
            $header = array_map(fn ($h) => strtolower(trim($h)), $header);

            // Validate required columns
            if (! in_array('dp_code', $header) || ! in_array('intacct_code', $header)) {
                fclose($handle);
                session()->flash('error', 'CSV must have "dp_code" and "intacct_code" columns.');

                return;
            }

            $dpCodeIdx = array_search('dp_code', $header);
            $intacctCodeIdx = array_search('intacct_code', $header);
            $notesIdx = array_search('notes', $header);
            $locationIdx = array_search('intacct_location_code', $header);
            $priorityIdx = array_search('priority', $header);

            $hasLocationColumn = $locationIdx !== false;
            $isConditionalType = in_array($this->selectedFieldType, ['solicit_code', 'sub_solicit_code']);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $conditionalCreated = 0;
            $conditionalUpdated = 0;
            $errors = [];
            $row = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $row++;

                $dpCode = trim($data[$dpCodeIdx] ?? '');
                $intacctCode = trim($data[$intacctCodeIdx] ?? '');
                $notes = $notesIdx !== false ? trim($data[$notesIdx] ?? '') : '';
                $locationCode = $hasLocationColumn ? trim($data[$locationIdx] ?? '') : '';
                $priority = $priorityIdx !== false ? (int) trim($data[$priorityIdx] ?? '0') : 0;

                if ($dpCode === '' || $intacctCode === '') {
                    $skipped++;

                    continue;
                }

                try {
                    // If location code is provided and field type supports it, import as conditional mapping
                    if ($locationCode !== '' && $isConditionalType) {
                        $this->authorizeAction('sage.conditional_mappings.edit');

                        $mapping = \App\Models\ConditionalCodeMapping::firstOrCreate(
                            [
                                'intacct_location_code' => $locationCode,
                                'field_type' => $this->selectedFieldType,
                                'dp_code' => $dpCode,
                            ],
                            [
                                'intacct_code' => $intacctCode,
                                'priority' => $priority,
                                'is_active' => true,
                            ]
                        );

                        if ($mapping->wasRecentlyCreated) {
                            $conditionalCreated++;
                        } else {
                            $mapping->intacct_code = $intacctCode;
                            $mapping->priority = $priority;
                            if ($notes !== '') {
                                $mapping->notes = $notes;
                            }
                            $mapping->save();
                            $conditionalUpdated++;
                        }

                        if ($notes !== '' && $mapping->wasRecentlyCreated) {
                            $mapping->update(['notes' => $notes]);
                        }
                    } else {
                        // Standard code mapping
                        $mapping = \App\Models\DonorPerfectCodeMapping::firstOrCreate(
                            [
                                'field_type' => $this->selectedFieldType,
                                'dp_code' => $dpCode,
                            ],
                            [
                                'first_seen_at' => now(),
                                'last_seen_at' => now(),
                            ]
                        );

                        $wasNew = $mapping->wasRecentlyCreated;
                        $mapping->setMapping($intacctCode);
                        if ($notes !== '') {
                            $mapping->notes = $notes;
                        }
                        $mapping->save();

                        $wasNew ? $created++ : $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row {$row} ({$dpCode}): {$e->getMessage()}";
                }
            }

            fclose($handle);

            $this->importResults = [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'conditional_created' => $conditionalCreated,
                'conditional_updated' => $conditionalUpdated,
                'errors' => $errors,
            ];

            $this->loadMappings($this->selectedFieldType);
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: '.$e->getMessage());
        }
    }

    // ========================================================================
    // Conditional Code Mapping CRUD Methods
    // ========================================================================

    /**
     * Show the create conditional mapping form
     */
    public function showCreateConditionalMappingForm()
    {
        $this->authorizeAction('sage.conditional_mappings.edit');
        $this->resetConditionalMappingForm();
        $this->showConditionalMappingForm = true;
    }

    /**
     * Show the edit conditional mapping form
     */
    public function showEditConditionalMappingForm($id)
    {
        $this->authorizeAction('sage.conditional_mappings.edit');

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

    /**
     * Save a new conditional mapping
     */
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
        ], [
            'conditional_intacct_location_code.required' => 'Sage Intacct location code is required',
            'conditional_field_type.required' => 'Field type is required',
            'conditional_dp_code.required' => 'DonorPerfect code is required',
            'conditional_intacct_code.required' => 'Sage Intacct code is required',
        ]);

        try {
            if ($this->editingConditionalMappingId) {
                // Update existing mapping
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
                session()->flash('success', 'Conditional mapping updated successfully. Gift cache invalidated - unsynced gifts will be revalidated on next sync.');
            } else {
                // Create new mapping
                \App\Models\ConditionalCodeMapping::create([
                    'intacct_location_code' => $this->conditional_intacct_location_code,
                    'field_type' => $this->conditional_field_type,
                    'dp_code' => $this->conditional_dp_code,
                    'intacct_code' => $this->conditional_intacct_code,
                    'priority' => $this->conditional_priority ?? 0,
                    'notes' => $this->conditional_notes,
                    'is_active' => $this->conditional_is_active,
                ]);
                session()->flash('success', 'Conditional mapping created successfully. Gift cache invalidated - unsynced gifts will be revalidated on next sync.');
            }

            $this->cancelConditionalMappingForm();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                session()->flash('error', 'A conditional mapping already exists for this location + field type + code combination.');
            } else {
                session()->flash('error', 'Failed to save conditional mapping: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save conditional mapping: '.$e->getMessage());
        }
    }

    /**
     * Delete a conditional mapping
     */
    public function deleteConditionalMapping($id)
    {
        $this->authorizeAction('sage.conditional_mappings.edit');

        try {
            $mapping = \App\Models\ConditionalCodeMapping::findOrFail($id);
            $mapping->delete();
            session()->flash('success', 'Conditional mapping deleted successfully. Gift cache invalidated - unsynced gifts will be revalidated on next sync.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete conditional mapping: '.$e->getMessage());
        }
    }

    /**
     * Toggle active status of a conditional mapping
     */
    public function toggleConditionalMappingActive($id)
    {
        $this->authorizeAction('sage.conditional_mappings.edit');

        try {
            $mapping = \App\Models\ConditionalCodeMapping::findOrFail($id);
            $mapping->update(['is_active' => ! $mapping->is_active]);
            session()->flash('success', 'Conditional mapping status updated. Gift cache invalidated - unsynced gifts will be revalidated on next sync.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update conditional mapping: '.$e->getMessage());
        }
    }

    /**
     * Cancel and reset the conditional mapping form
     */
    public function cancelConditionalMappingForm()
    {
        $this->showConditionalMappingForm = false;
        $this->resetConditionalMappingForm();
    }

    /**
     * Reset conditional mapping form fields
     */
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

    // ========================================================================
    // Sync Filter CRUD Methods
    // ========================================================================

    /**
     * Show the create sync filter form
     */
    public function showCreateSyncFilterForm()
    {
        $this->authorizeAction('sage.filters.edit');
        $this->resetSyncFilterForm();
        $this->showSyncFilterForm = true;
    }

    /**
     * Show the edit sync filter form
     */
    public function showEditSyncFilterForm($id)
    {
        $this->authorizeAction('sage.filters.edit');

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

    /**
     * Save a sync filter
     */
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
        ], [
            'sync_filter_name.required' => 'Filter name is required',
            'sync_filter_field_name.required' => 'Field name is required',
            'sync_filter_operator.required' => 'Operator is required',
        ]);

        try {
            if ($this->editingSyncFilterId) {
                // Update existing filter
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
                // Create new filter
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

    /**
     * Delete a sync filter
     */
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

    /**
     * Toggle active status of a sync filter
     */
    public function toggleSyncFilterActive($id)
    {
        $this->authorizeAction('sage.filters.edit');

        try {
            $filter = \App\Models\SageIntacctSyncFilter::findOrFail($id);
            $filter->update(['is_active' => ! $filter->is_active]);
            session()->flash('success', 'Sync filter status updated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update sync filter: '.$e->getMessage());
        }
    }

    /**
     * Cancel and reset the sync filter form
     */
    public function cancelSyncFilterForm()
    {
        $this->showSyncFilterForm = false;
        $this->resetSyncFilterForm();
    }

    /**
     * Reset sync filter form fields
     */
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

    public function render()
    {
        $settings = SageIntacctSetting::first();

        $fieldMappings = collect([]);
        if ($settings) {
            $fieldMappings = SageIntacctFieldMapping::where('setting_id', $settings->id)
                ->orderBy('order')
                ->get();
        }

        $syncHistory = SageIntacctSyncHistory::query()
            ->with('resetBy') // Eager load reset user to prevent N+1 queries
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterStartDate, function ($query) {
                $query->whereDate('sync_start_date', '>=', $this->filterStartDate);
            })
            ->when($this->filterEndDate, function ($query) {
                $query->whereDate('sync_end_date', '<=', $this->filterEndDate);
            })
            ->orderBy('started_at', 'desc')
            ->paginate(15);

        return view('livewire.sage-intacct-configuration', [
            'settings' => $settings,
            'fieldMappings' => $fieldMappings,
            'syncHistory' => $syncHistory,
            'availableDpFields' => $this->availableDpFields,
        ])->layout('components.layouts.app');
    }
}
