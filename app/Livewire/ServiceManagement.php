<?php

namespace App\Livewire;

use App\Models\Service;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
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

    public function mount()
    {
        // Set default program if filtering by program
        if ($this->filterProgramId) {
            $this->program_id = $this->filterProgramId;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateService()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showViewModal = false;
        $this->selectedServiceId = null;
        $this->resetServiceForm();
        
        // Set default program if filtering by program
        if ($this->filterProgramId) {
            $this->program_id = $this->filterProgramId;
        }
    }

    public function showEditService($serviceId)
    {
        $service = Service::find($serviceId);
        if ($service) {
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
        $this->showViewModal = false;
        $this->selectedViewService = null;
        $this->selectedServiceId = null;
    }

    public function fillServiceForm(Service $service = null)
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
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            Service::create([
                'name' => $this->name,
                'description' => $this->description,
                'program_id' => $this->program_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'active' => $this->active,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Service created successfully.');
            $this->showCreateForm = false;
            $this->resetServiceForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateService()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            $service = $this->selectedServiceId ? Service::find($this->selectedServiceId) : null;

            if (!$service) {
                session()->flash('error', 'Selected service is no longer available.');
                return;
            }

            $service->update([
                'name' => $this->name,
                'description' => $this->description,
                'program_id' => $this->program_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'active' => $this->active,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Service updated successfully.');
            $this->showEditForm = false;
            $this->selectedServiceId = null;
            $this->resetServiceForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function toggleServiceStatus($serviceId)
    {
        $service = Service::find($serviceId);
        if ($service) {
            $service->update(['active' => !$service->active]);
            session()->flash('success', 'Service status updated successfully.');
        }
    }

    public function deleteService($serviceId)
    {
        try {
            $service = Service::find($serviceId);
            if ($service) {
                $service->delete();
                session()->flash('success', 'Service deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete service: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $services = Service::with(['program', 'workPlanGoals', 'serviceEnrollments'])
            ->when($this->filterProgramId, function ($query) {
                $query->where('program_id', $this->filterProgramId);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $programs = Program::active()->regularPrograms()->orderBy('name')->get();
        
        $filteredProgram = null;
        if ($this->filterProgramId) {
            $filteredProgram = Program::find($this->filterProgramId);
        }

        return view('livewire.service-management', [
            'services' => $services,
            'programs' => $programs,
            'filteredProgram' => $filteredProgram,
            'selectedService' => $this->selectedService,
        ])->layout('components.layouts.app');
    }
}
