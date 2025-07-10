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
    public $selectedService = null;
    public $showCreateForm = false;
    public $showEditForm = false;
    public $showViewModal = false;
    public $selectedViewService = null;

    // Service Form Fields
    public $name = '';
    public $description = '';
    public $program_id = '';
    public $start_date = '';
    public $end_date = '';
    public $active = true;
    public $notes = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateService()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showViewModal = false;
        $this->selectedService = null;
        $this->resetServiceForm();
    }

    public function showEditService($serviceId)
    {
        $this->selectedService = Service::find($serviceId);
        if ($this->selectedService) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->showViewModal = false;
            $this->fillServiceForm();
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
    }

    public function fillServiceForm()
    {
        if ($this->selectedService) {
            $this->name = $this->selectedService->name;
            $this->description = $this->selectedService->description;
            $this->program_id = $this->selectedService->program_id;
            $this->start_date = $this->selectedService->start_date ? $this->selectedService->start_date->format('Y-m-d') : '';
            $this->end_date = $this->selectedService->end_date ? $this->selectedService->end_date->format('Y-m-d') : '';
            $this->active = $this->selectedService->active;
            $this->notes = $this->selectedService->notes;
        }
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
            $this->selectedService->update([
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
            $this->selectedService = null;
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
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('program', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $programs = Program::active()->regularPrograms()->orderBy('name')->get();

        return view('livewire.service-management', [
            'services' => $services,
            'programs' => $programs,
        ])->layout('components.layouts.app');
    }
}