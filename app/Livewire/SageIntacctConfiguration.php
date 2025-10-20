<?php

namespace App\Livewire;

use App\Models\SageIntacctSetting;
use App\Models\SageIntacctFieldMapping;
use App\Models\SageIntacctSyncHistory;
use App\Models\DonorPerfectSetting;
use App\Services\SageIntacctApiService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class SageIntacctConfiguration extends Component
{
    use WithPagination, WithPermissionChecks;

    // Tab state
    public $activeTab = 'credentials';

    // API Credentials
    public $company_id = '';
    public $user_id = '';
    public $sender_id = '';
    public $sender_password = '';
    public $web_services_user_id = '';
    public $web_services_password = '';
    public $location_id = '';
    public $department_id = '';
    public $class_id = '';
    public $is_active = false;
    public $code_mapping_enabled = false;

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

    // Sync History filters
    public $filterStatus = '';
    public $filterStartDate = '';
    public $filterEndDate = '';

    protected $rules = [
        'company_id' => 'required|string|max:255',
        'user_id' => 'required|string|max:255',
        'sender_id' => 'required|string|max:255',
        'sender_password' => 'required|string|max:255',
        'web_services_user_id' => 'required|string|max:255',
        'web_services_password' => 'required|string|max:255',
        'location_id' => 'nullable|string|max:255',
        'department_id' => 'nullable|string|max:255',
        'class_id' => 'nullable|string|max:255',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'company_id.required' => 'Company ID is required.',
        'user_id.required' => 'User ID is required.',
        'sender_id.required' => 'Sender ID is required.',
        'sender_password.required' => 'Sender Password is required.',
        'web_services_user_id.required' => 'Web Services User ID is required.',
        'web_services_password.required' => 'Web Services Password is required.',
    ];

    public function mount()
    {
        $this->authorizeAction('intacct.configure');
        $this->loadExistingSettings();
    }

    protected function loadExistingSettings()
    {
        $settings = SageIntacctSetting::first();

        if ($settings) {
            $this->company_id = $settings->company_id ?? '';
            $this->user_id = $settings->user_id ?? '';
            $this->sender_id = $settings->sender_id ?? '';
            $this->sender_password = $settings->sender_password ?? '';
            $this->web_services_user_id = $settings->web_services_user_id ?? '';
            $this->web_services_password = $settings->web_services_password ?? '';
            $this->location_id = $settings->location_id ?? '';
            $this->department_id = $settings->department_id ?? '';
            $this->class_id = $settings->class_id ?? '';
            $this->is_active = $settings->is_active ?? false;
            $this->code_mapping_enabled = $settings->code_mapping_enabled ?? false;
        } else {
            // No settings exist yet - default code mapping to false but allow access
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
        $this->authorizeAction('intacct.configure');

        $this->validate();

        try {
            $settings = SageIntacctSetting::first();

            $data = [
                'company_id' => $this->company_id,
                'user_id' => $this->user_id,
                'sender_id' => $this->sender_id,
                'sender_password' => $this->sender_password,
                'web_services_user_id' => $this->web_services_user_id,
                'web_services_password' => $this->web_services_password,
                'location_id' => $this->location_id,
                'department_id' => $this->department_id,
                'class_id' => $this->class_id,
                'is_active' => $this->is_active,
                'code_mapping_enabled' => $this->code_mapping_enabled,
            ];

            if ($settings) {
                $settings->update($data);
            } else {
                SageIntacctSetting::create($data);
            }

            session()->flash('success', 'Sage Intacct credentials saved successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save credentials: ' . $e->getMessage());
        }
    }

    public function testConnection()
    {
        $this->authorizeAction('intacct.configure');

        $this->validate([
            'company_id' => 'required|string',
            'user_id' => 'required|string',
            'sender_id' => 'required|string',
            'sender_password' => 'required|string',
            'web_services_user_id' => 'required|string',
            'web_services_password' => 'required|string',
        ]);

        $this->showTestResult = true;
        $this->testConnectionResult = null;

        try {
            // Create temporary setting object for testing
            $tempSetting = new SageIntacctSetting();
            $tempSetting->company_id = $this->company_id;
            $tempSetting->user_id = $this->user_id;
            $tempSetting->sender_id = $this->sender_id;
            $tempSetting->sender_password = $this->sender_password;
            $tempSetting->web_services_user_id = $this->web_services_user_id;
            $tempSetting->web_services_password = $this->web_services_password;

            $apiService = new SageIntacctApiService($tempSetting);
            $result = $apiService->testConnection();

            if ($result['success']) {
                $this->testConnectionResult = [
                    'success' => true,
                    'message' => 'Connection successful! API is responding correctly.',
                    'details' => $result['details'] ?? null,
                ];
            } else {
                $this->testConnectionResult = [
                    'success' => false,
                    'message' => 'Connection failed: ' . ($result['error'] ?? 'Unknown error'),
                    'details' => $result['details'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            $this->testConnectionResult = [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'details' => null,
            ];
        }
    }

    public function closeTestResult()
    {
        $this->showTestResult = false;
        $this->testConnectionResult = null;
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
        $this->validate([
            'mapping_dp_field' => 'required|string|max:255',
            'mapping_intacct_field' => 'required|string|max:255',
        ]);

        try {
            $settings = SageIntacctSetting::first();
            if (!$settings) {
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
            session()->flash('error', 'Failed to save mapping: ' . $e->getMessage());
        }
    }

    public function deleteFieldMapping($mappingId)
    {
        try {
            $mapping = SageIntacctFieldMapping::find($mappingId);
            if ($mapping) {
                $mapping->delete();
                session()->flash('success', 'Field mapping deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete mapping: ' . $e->getMessage());
        }
    }

    public function moveFieldMapping($mappingId, $direction)
    {
        try {
            $mapping = SageIntacctFieldMapping::find($mappingId);
            if (!$mapping) {
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
            session()->flash('error', 'Failed to reorder mapping: ' . $e->getMessage());
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
        if ($dpSettings && !empty($dpSettings->selected_fields)) {
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

        if (!$this->showArchived) {
            $query->active();
        }

        $this->mappings = $query->orderBy('dp_code')->get();
    }

    public function discoverNewCodes()
    {
        $this->authorizeAction('intacct.configure');

        try {
            $giftService = new \App\Services\DonorPerfectGiftService();
            $result = $giftService->discoverAndSyncCodes(90);

            if ($result['success']) {
                session()->flash('success', "Code discovery complete! Found {$result['new']} new code(s).");
            } else {
                session()->flash('error', 'Code discovery failed: ' . ($result['error'] ?? 'Unknown error'));
            }

            $this->loadMappings($this->selectedFieldType);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to discover codes: ' . $e->getMessage());
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
        $this->authorizeAction('intacct.configure');

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
            session()->flash('error', 'Failed to save mapping: ' . $e->getMessage());
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
        $this->authorizeAction('intacct.configure');

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
            session()->flash('error', 'Failed to toggle archive: ' . $e->getMessage());
        }
    }

    public function bulkArchiveUnmapped()
    {
        $this->authorizeAction('intacct.configure');

        try {
            $count = \App\Models\DonorPerfectCodeMapping::forFieldType($this->selectedFieldType)
                ->unmapped()
                ->active()
                ->update(['is_archived' => true]);

            session()->flash('success', "Archived {$count} unmapped code(s).");
            $this->loadMappings($this->selectedFieldType);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to archive codes: ' . $e->getMessage());
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
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterStartDate, function ($query) {
                $query->whereDate('started_at', '>=', $this->filterStartDate);
            })
            ->when($this->filterEndDate, function ($query) {
                $query->whereDate('started_at', '<=', $this->filterEndDate);
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
