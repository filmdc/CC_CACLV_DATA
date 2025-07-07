<?php

namespace App\Livewire;

use App\Models\Contractor;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ContractorManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // UI State
    public $search = '';
    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedContractor = null;
    public $showDetails = false;
    public $selectedContractorId = null;
    public $filterStatus = '';
    public $filterExpiredLicense = false;
    public $filterExpiredInsurance = false;

    // Form Fields
    public $company_name = '';
    public $contact_first_name = '';
    public $contact_last_name = '';
    public $email = '';
    public $phone = '';
    public $fax = '';
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $license_number = '';
    public $license_expiration = '';
    public $insurance_provider = '';
    public $insurance_policy_number = '';
    public $insurance_expiration = '';
    public $liability_coverage_amount = '';
    public $ein = '';
    public $services_offered = [];
    public $status = 'active';
    public $notes = '';

    // Services selection
    public $availableServices = [];
    public $selectedServices = [];

    protected $rules = [
        'company_name' => 'required|string|max:255',
        'contact_first_name' => 'nullable|string|max:100',
        'contact_last_name' => 'nullable|string|max:100',
        'email' => 'nullable|email|max:255',
        'phone' => 'required|string|max:20',
        'fax' => 'nullable|string|max:20',
        'address_line_1' => 'required|string|max:255',
        'address_line_2' => 'nullable|string|max:255',
        'city' => 'required|string|max:100',
        'state' => 'required|string|size:2',
        'zip_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
        'license_number' => 'nullable|string|max:100',
        'license_expiration' => 'nullable|date',
        'insurance_provider' => 'nullable|string|max:255',
        'insurance_policy_number' => 'nullable|string|max:100',
        'insurance_expiration' => 'nullable|date',
        'liability_coverage_amount' => 'nullable|numeric|min:0',
        'ein' => 'nullable|string|max:20',
        'status' => 'required|in:active,inactive,suspended',
        'notes' => 'nullable|string',
    ];

    public function mount()
    {
        $this->authorizeAction('contractors.view');
        $this->loadAvailableServices();
    }

    public function loadAvailableServices()
    {
        $this->availableServices = ReferenceData::where('category', 'contractor_service')
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterExpiredLicense()
    {
        $this->resetPage();
    }

    public function updatedFilterExpiredInsurance()
    {
        $this->resetPage();
    }

    public function showContractorCreateForm()
    {
        $this->authorizeAction('contractors.create');
        $this->resetForm();
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showDetails = false;
    }

    public function showEditForm($contractorId)
    {
        $this->authorizeAction('contractors.update');
        $this->selectedContractor = Contractor::findOrFail($contractorId);
        $this->fillFormFromContractor();
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->showDetails = false;
    }

    public function viewDetails($contractorId)
    {
        $this->authorizeAction('contractors.view');
        $this->selectedContractorId = $contractorId;
        $this->selectedContractor = Contractor::with(['jobs.property', 'documents'])
            ->findOrFail($contractorId);
        $this->showDetails = true;
        $this->showCreateForm = false;
        $this->showEditForm = false;
    }

    public function save()
    {
        $this->authorizeAction('contractors.create');
        $this->validate();

        try {
            $contractor = Contractor::create([
                'company_name' => $this->company_name,
                'contact_first_name' => $this->contact_first_name,
                'contact_last_name' => $this->contact_last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'fax' => $this->fax,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => strtoupper($this->state),
                'zip_code' => $this->zip_code,
                'license_number' => $this->license_number,
                'license_expiration' => $this->license_expiration ?: null,
                'insurance_provider' => $this->insurance_provider,
                'insurance_policy_number' => $this->insurance_policy_number,
                'insurance_expiration' => $this->insurance_expiration ?: null,
                'liability_coverage_amount' => $this->liability_coverage_amount ?: null,
                'ein' => $this->ein,
                'services_offered' => $this->selectedServices,
                'status' => $this->status,
                'notes' => $this->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            session()->flash('message', 'Contractor created successfully.');
            $this->showCreateForm = false;
            $this->resetForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating contractor: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $this->authorizeAction('contractors.update');
        $this->validate();

        try {
            $this->selectedContractor->update([
                'company_name' => $this->company_name,
                'contact_first_name' => $this->contact_first_name,
                'contact_last_name' => $this->contact_last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'fax' => $this->fax,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => strtoupper($this->state),
                'zip_code' => $this->zip_code,
                'license_number' => $this->license_number,
                'license_expiration' => $this->license_expiration ?: null,
                'insurance_provider' => $this->insurance_provider,
                'insurance_policy_number' => $this->insurance_policy_number,
                'insurance_expiration' => $this->insurance_expiration ?: null,
                'liability_coverage_amount' => $this->liability_coverage_amount ?: null,
                'ein' => $this->ein,
                'services_offered' => $this->selectedServices,
                'status' => $this->status,
                'notes' => $this->notes,
                'updated_by' => Auth::id(),
            ]);

            session()->flash('message', 'Contractor updated successfully.');
            $this->showEditForm = false;
            $this->resetForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating contractor: ' . $e->getMessage());
        }
    }

    public function delete($contractorId)
    {
        $this->authorizeAction('contractors.delete');
        
        try {
            $contractor = Contractor::findOrFail($contractorId);
            
            // Check if contractor has associated jobs
            if ($contractor->jobs()->exists()) {
                session()->flash('error', 'Cannot delete contractor with associated housing jobs.');
                return;
            }
            
            $contractor->delete();
            session()->flash('message', 'Contractor deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting contractor: ' . $e->getMessage());
        }
    }

    public function toggleService($serviceId)
    {
        if (in_array($serviceId, $this->selectedServices)) {
            $this->selectedServices = array_values(array_diff($this->selectedServices, [$serviceId]));
        } else {
            $this->selectedServices[] = $serviceId;
        }
    }

    public function resetForm()
    {
        $this->reset([
            'company_name',
            'contact_first_name',
            'contact_last_name',
            'email',
            'phone',
            'fax',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'zip_code',
            'license_number',
            'license_expiration',
            'insurance_provider',
            'insurance_policy_number',
            'insurance_expiration',
            'liability_coverage_amount',
            'ein',
            'selectedServices',
            'status',
            'notes',
            'selectedContractor',
        ]);
        $this->status = 'active';
        $this->selectedServices = [];
    }

    public function fillFormFromContractor()
    {
        $this->company_name = $this->selectedContractor->company_name;
        $this->contact_first_name = $this->selectedContractor->contact_first_name;
        $this->contact_last_name = $this->selectedContractor->contact_last_name;
        $this->email = $this->selectedContractor->email;
        $this->phone = $this->selectedContractor->phone;
        $this->fax = $this->selectedContractor->fax;
        $this->address_line_1 = $this->selectedContractor->address_line_1;
        $this->address_line_2 = $this->selectedContractor->address_line_2;
        $this->city = $this->selectedContractor->city;
        $this->state = $this->selectedContractor->state;
        $this->zip_code = $this->selectedContractor->zip_code;
        $this->license_number = $this->selectedContractor->license_number;
        $this->license_expiration = $this->selectedContractor->license_expiration ? $this->selectedContractor->license_expiration->format('Y-m-d') : '';
        $this->insurance_provider = $this->selectedContractor->insurance_provider;
        $this->insurance_policy_number = $this->selectedContractor->insurance_policy_number;
        $this->insurance_expiration = $this->selectedContractor->insurance_expiration ? $this->selectedContractor->insurance_expiration->format('Y-m-d') : '';
        $this->liability_coverage_amount = $this->selectedContractor->liability_coverage_amount;
        $this->ein = $this->selectedContractor->ein;
        $this->selectedServices = $this->selectedContractor->services_offered ?? [];
        $this->status = $this->selectedContractor->status;
        $this->notes = $this->selectedContractor->notes;
    }

    public function render()
    {
        // Build the contractors query
        $query = Contractor::with(['jobs'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('company_name', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('license_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterExpiredLicense, function ($q) {
                $q->whereNotNull('license_expiration')
                    ->where('license_expiration', '<', now());
            })
            ->when($this->filterExpiredInsurance, function ($q) {
                $q->whereNotNull('insurance_expiration')
                    ->where('insurance_expiration', '<', now());
            });

        $contractors = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.contractor-management', [
            'contractors' => $contractors,
            'availableServices' => $this->availableServices,
        ])->layout('components.layouts.app');
    }
}
