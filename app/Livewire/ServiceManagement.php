<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\Program;
use App\Models\Service;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';

    public $selectedServiceId = null;

    public $showCreateForm = false;

    public $showEditForm = false;

    public $showViewModal = false;

    public $selectedViewService = null;

    public $filterProgramId = null;

    protected $queryString = ['filterProgramId' => ['as' => 'program']];

    // Service Form Fields
    public $name = '';

    public $description = '';

    public $program_id = '';

    public $start_date = '';

    public $end_date = '';

    public $active = true;

    public $notes = '';

    public $selectedContracts = [];

    public $contractSearch = '';

    public function mount()
    {
        // Set default program if filtering by program
        if ($this->filterProgramId) {
            $this->program_id = $this->filterProgramId;
        }
    }

    /**
     * Check if the current user can edit services for a specific program.
     * Admins and Contract Managers can edit any program.
     * Other users can only edit programs they are assigned to.
     */
    public function canEditProgram($programId): bool
    {
        $user = Auth::user();

        return $user->hasAccessToProgram($programId);
    }

    /**
     * Get the program IDs that the current user can edit.
     */
    public function getEditableProgramIds(): array
    {
        $user = Auth::user();

        // Admins and Contract Managers can edit all programs
        if ($user->isAdmin() || $user->hasRole('Contract Manager')) {
            return Program::pluck('id')->toArray();
        }

        // Other users can only edit their assigned programs
        return $user->programs()->pluck('programs.id')->toArray();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateService()
    {
        // Check if user has any editable programs
        $editablePrograms = $this->getEditableProgramIds();
        if (empty($editablePrograms)) {
            session()->flash('error', 'You do not have permission to create services. You must be assigned to at least one program.');

            return;
        }

        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showViewModal = false;
        $this->selectedServiceId = null;
        $this->resetServiceForm();

        // Set default program if filtering by program and user has access
        if ($this->filterProgramId && $this->canEditProgram($this->filterProgramId)) {
            $this->program_id = $this->filterProgramId;
        }
    }

    public function showEditService($serviceId)
    {
        $service = Service::find($serviceId);
        if ($service) {
            // Check if user can edit this program
            if (! $this->canEditProgram($service->program_id)) {
                session()->flash('error', 'You do not have permission to edit services for this program.');

                return;
            }

            $this->selectedServiceId = $serviceId;
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->showViewModal = false;
            $this->fillServiceForm($service);
        }
    }

    public function viewService($serviceId)
    {
        // Redirect to the service show page instead of opening a modal
        return redirect()->route('service.show', $serviceId);
    }

    public function resetServiceForm()
    {
        $this->name = '';
        $this->description = '';
        $this->program_id = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->active = true;
        $this->notes = '';
        $this->selectedContracts = [];
        $this->contractSearch = '';
        $this->showViewModal = false;
        $this->selectedViewService = null;
        $this->selectedServiceId = null;
    }

    public function fillServiceForm(?Service $service = null)
    {
        $service = $service ?? ($this->selectedServiceId ? Service::find($this->selectedServiceId) : null);

        if ($service) {
            $this->name = $service->name;
            $this->description = $service->description;
            $this->program_id = $service->program_id;
            $this->start_date = $service->start_date ? $service->start_date->format('Y-m-d') : '';
            $this->end_date = $service->end_date ? $service->end_date->format('Y-m-d') : '';
            $this->active = $service->active;
            $this->notes = $service->notes;
            $this->selectedContracts = $service->contracts()->pluck('contracts.id')->toArray();
        }
    }

    public function getSelectedServiceProperty(): ?Service
    {
        return $this->selectedServiceId ? Service::find($this->selectedServiceId) : null;
    }

    public function createService()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        // Verify user has access to the selected program
        if (! $this->canEditProgram($this->program_id)) {
            session()->flash('error', 'You do not have permission to create services for this program.');

            return;
        }

        try {
            $service = Service::create([
                'name' => $this->name,
                'description' => $this->description,
                'program_id' => $this->program_id,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'active' => $this->active,
                'notes' => $this->notes,
            ]);

            // Sync contracts
            if (! empty($this->selectedContracts)) {
                $service->contracts()->sync($this->selectedContracts);
            }

            session()->flash('success', 'Service created successfully.');
            $this->showCreateForm = false;
            $this->resetServiceForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateService()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            $service = $this->selectedServiceId ? Service::find($this->selectedServiceId) : null;

            if (! $service) {
                session()->flash('error', 'Selected service is no longer available.');

                return;
            }

            // Verify user has access to the service's current program
            if (! $this->canEditProgram($service->program_id)) {
                session()->flash('error', 'You do not have permission to edit services for this program.');

                return;
            }

            // If changing programs, verify user has access to the new program too
            if ($this->program_id != $service->program_id && ! $this->canEditProgram($this->program_id)) {
                session()->flash('error', 'You do not have permission to move services to this program.');

                return;
            }

            $service->update([
                'name' => $this->name,
                'description' => $this->description,
                'program_id' => $this->program_id,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'active' => $this->active,
                'notes' => $this->notes,
            ]);

            // Sync contracts
            $service->contracts()->sync($this->selectedContracts);

            session()->flash('success', 'Service updated successfully.');
            $this->showEditForm = false;
            $this->selectedServiceId = null;
            $this->resetServiceForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function toggleServiceStatus($serviceId)
    {
        $service = Service::find($serviceId);
        if ($service) {
            // Verify user has access to the service's program
            if (! $this->canEditProgram($service->program_id)) {
                session()->flash('error', 'You do not have permission to modify services for this program.');

                return;
            }

            $service->update(['active' => ! $service->active]);
            session()->flash('success', 'Service status updated successfully.');
        }
    }

    public function deleteService($serviceId)
    {
        try {
            $service = Service::find($serviceId);
            if ($service) {
                // Verify user has access to the service's program
                if (! $this->canEditProgram($service->program_id)) {
                    session()->flash('error', 'You do not have permission to delete services for this program.');

                    return;
                }

                $service->delete();
                session()->flash('success', 'Service deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete service: '.$e->getMessage());
        }
    }

    public function render()
    {
        $services = Service::with(['program', 'workPlanGoals', 'serviceEnrollments', 'contracts'])
            ->when($this->filterProgramId, function ($query) {
                $query->where('program_id', $this->filterProgramId);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all programs for display
        $programs = Program::active()->regularPrograms()->orderBy('name')->get();

        // Get editable program IDs for the current user
        $editableProgramIds = $this->getEditableProgramIds();

        // For the form dropdown, show only editable programs for non-admins
        $user = Auth::user();
        if ($user->isAdmin() || $user->hasRole('Contract Manager')) {
            $editablePrograms = $programs;
        } else {
            $editablePrograms = Program::active()
                ->regularPrograms()
                ->whereIn('id', $editableProgramIds)
                ->orderBy('name')
                ->get();
        }

        $filteredProgram = null;
        if ($this->filterProgramId) {
            $filteredProgram = Program::find($this->filterProgramId);
        }

        // Get active contracts for selection (filtered by search)
        $contracts = Contract::active()
            ->when($this->contractSearch, function ($query) {
                $search = $this->contractSearch;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('contract_number', 'like', "%{$search}%")
                        ->orWhere('grant_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('grant_code')
            ->orderBy('title')
            ->get();

        // Always include already selected contracts (even if filtered out by search)
        if (! empty($this->selectedContracts) && $this->contractSearch) {
            $selectedContractModels = Contract::whereIn('id', $this->selectedContracts)
                ->whereNotIn('id', $contracts->pluck('id'))
                ->get();
            $contracts = $contracts->merge($selectedContractModels)->sortBy('grant_code');
        }

        return view('livewire.service-management', [
            'services' => $services,
            'programs' => $programs,
            'editablePrograms' => $editablePrograms,
            'editableProgramIds' => $editableProgramIds,
            'filteredProgram' => $filteredProgram,
            'selectedService' => $this->selectedService,
            'contracts' => $contracts,
        ])->layout('components.layouts.app');
    }
}
