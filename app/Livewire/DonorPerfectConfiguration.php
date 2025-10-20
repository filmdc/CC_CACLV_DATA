<?php

namespace App\Livewire;

use App\Models\DonorPerfectSetting;
use App\Services\DonorPerfectApiService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class DonorPerfectConfiguration extends Component
{
    use WithPermissionChecks;

    public $api_url = '';
    public $api_key = '';
    public $api_login = '';
    public $api_password = '';
    public $cache_duration = 3600;
    public $selected_fields = [];
    public $is_active = true;

    public $availableFields = [];
    public $testConnectionResult = null;
    public $showTestResult = false;
    public $showFieldBrowser = false;
    public $dpGiftFields = [];
    public $dpDonorFields = [];
    public $dpGiftUdfFields = [];
    public $loadingFields = false;

    protected $rules = [
        'api_url' => 'required|url|max:255',
        'api_key' => 'required|string|max:255',
        'api_login' => 'required|string|max:255',
        'api_password' => 'required|string|max:255',
        'cache_duration' => 'required|integer|min:60|max:86400',
        'selected_fields' => 'nullable|array',
        'selected_fields.*' => 'string',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'api_url.required' => 'API URL is required.',
        'api_url.url' => 'API URL must be a valid URL.',
        'api_key.required' => 'API Key is required.',
        'api_login.required' => 'API Login is required.',
        'api_password.required' => 'API Password is required.',
        'cache_duration.min' => 'Cache duration must be at least 60 seconds.',
        'cache_duration.max' => 'Cache duration cannot exceed 86400 seconds (24 hours).',
        'selected_fields.*.in' => 'Invalid field selected.',
    ];

    public function mount()
    {
        $this->authorizeAction('donor_gifts.configure');

        $this->initializeAvailableFields();
        $this->loadExistingSettings();
    }

    protected function initializeAvailableFields()
    {
        $this->availableFields = [
            'donor' => [
                'donor_id' => 'Donor ID',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'Email Address',
                'phone' => 'Phone Number',
                'address' => 'Street Address',
                'city' => 'City',
                'state' => 'State',
                'zip' => 'ZIP Code',
            ],
            'gift' => [
                'gift_id' => 'Gift ID',
                'gift_date' => 'Gift Date',
                'amount' => 'Amount',
                'campaign' => 'Campaign',
                'gift_type' => 'Gift Type',
                'payment_type' => 'Payment Type',
                'gl_code' => 'GL Code',
                'solicit_code' => 'Solicitation Code',
                'sub_solicit_code' => 'Sub-Solicitation Code',
            ],
        ];
    }

    protected function loadExistingSettings()
    {
        // Load the first setting record regardless of active status
        $settings = DonorPerfectSetting::first();

        if ($settings) {
            $this->api_url = $settings->api_url ?? '';
            $this->api_key = $settings->api_key ?? '';
            $this->api_login = $settings->api_login ?? '';
            $this->api_password = $settings->api_password ?? '';
            // Convert minutes to seconds for display
            $this->cache_duration = ($settings->cache_duration ?? 15) * 60;
            $this->selected_fields = $settings->selected_fields ?? [];
            $this->is_active = $settings->is_active ?? false;
        } else {
            $this->selected_fields = $this->getDefaultFields();
            $this->cache_duration = 3600; // 60 minutes default
        }
    }

    protected function getDefaultFields()
    {
        return [
            'donor_id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'gift_id',
            'gift_date',
            'amount',
            'campaign',
        ];
    }

    public function toggleFieldSelection($field)
    {
        if (in_array($field, $this->selected_fields)) {
            $this->selected_fields = array_values(
                array_filter($this->selected_fields, fn($f) => $f !== $field)
            );
        } else {
            $this->selected_fields[] = $field;
        }
    }

    public function isFieldSelected($field)
    {
        return in_array($field, $this->selected_fields);
    }

    public function save()
    {
        $this->authorizeAction('donor_gifts.configure');

        $this->validate();

        try {
            // Load the first setting record (we only support one configuration)
            $settings = DonorPerfectSetting::first();

            // Convert cache duration from seconds to minutes for storage
            $cacheMinutes = intval($this->cache_duration / 60);

            $data = [
                'api_url' => $this->api_url,
                'api_key' => $this->api_key,
                'api_login' => $this->api_login,
                'api_password' => $this->api_password,
                'cache_duration' => $cacheMinutes,
                'selected_fields' => $this->selected_fields,
                'is_active' => $this->is_active,
            ];

            if ($settings) {
                $settings->update($data);
            } else {
                DonorPerfectSetting::create($data);
            }

            session()->flash('success', 'DonorPerfect configuration saved successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save configuration: ' . $e->getMessage());
        }
    }

    public function testConnection()
    {
        $this->authorizeAction('donor_gifts.configure');

        $this->validate([
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'api_login' => 'required|string',
            'api_password' => 'required|string',
        ]);

        $this->showTestResult = true;
        $this->testConnectionResult = null;

        try {
            // Create temporary setting object for testing
            $tempSetting = new \App\Models\DonorPerfectSetting();
            $tempSetting->api_url = $this->api_url;
            $tempSetting->api_key = $this->api_key;
            $tempSetting->api_login = $this->api_login;
            $tempSetting->api_password = $this->api_password;

            $apiService = new \App\Services\DonorPerfectApiService($tempSetting);

            $result = $apiService->testConnection();

            if ($result['success']) {
                $this->testConnectionResult = [
                    'success' => true,
                    'message' => 'Connection successful! API is responding correctly.',
                    'details' => $result['details'] ?? null,
                ];

                $settings = DonorPerfectSetting::getActive();
                if ($settings) {
                    $settings->update([
                        'last_connection_test' => now(),
                    ]);
                }
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

    public function selectAllFields()
    {
        $this->selected_fields = array_merge(
            array_keys($this->availableFields['donor']),
            array_keys($this->availableFields['gift'])
        );
    }

    public function deselectAllFields()
    {
        $this->selected_fields = [];
    }

    public function toggleFieldBrowser()
    {
        $this->showFieldBrowser = !$this->showFieldBrowser;

        // Load fields from API if not already loaded
        if ($this->showFieldBrowser && empty($this->dpGiftFields) && empty($this->dpDonorFields) && empty($this->dpGiftUdfFields)) {
            $this->loadFieldsFromApi();
        }
    }

    public function loadFieldsFromApi()
    {
        $this->loadingFields = true;

        try {
            // Create temporary setting object with current credentials
            $tempSetting = new \App\Models\DonorPerfectSetting();
            $tempSetting->api_url = $this->api_url;
            $tempSetting->api_key = $this->api_key;
            $tempSetting->api_login = $this->api_login;
            $tempSetting->api_password = $this->api_password;

            $apiService = new \App\Services\DonorPerfectApiService($tempSetting);
            $result = $apiService->getAvailableFields();

            if ($result['success']) {
                $this->dpGiftFields = $result['tables']['dpgift'] ?? [];
                $this->dpDonorFields = $result['tables']['dp'] ?? [];
                $this->dpGiftUdfFields = $result['tables']['dpgiftudf'] ?? [];

                $totalFields = count($this->dpGiftFields) + count($this->dpDonorFields) + count($this->dpGiftUdfFields);
                session()->flash('success', "Loaded {$totalFields} fields: " . count($this->dpGiftFields) . ' gift, ' . count($this->dpDonorFields) . ' donor, ' . count($this->dpGiftUdfFields) . ' UDF fields.');
            } else {
                session()->flash('error', 'Failed to load fields: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load fields: ' . $e->getMessage());
        }

        $this->loadingFields = false;
    }

    public function toggleFieldFromBrowser($field)
    {
        if (in_array($field, $this->selected_fields)) {
            $this->selected_fields = array_values(
                array_filter($this->selected_fields, fn($f) => $f !== $field)
            );
        } else {
            $this->selected_fields[] = $field;
        }
    }

    public function render()
    {
        return view('livewire.donor-perfect-configuration', [
            'donorFields' => $this->availableFields['donor'] ?? [],
            'giftFields' => $this->availableFields['gift'] ?? [],
        ])->layout('components.layouts.app');
    }
}
