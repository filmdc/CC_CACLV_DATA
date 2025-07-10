<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\WorkPlan;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramServiceHub extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $activeTab = 'programs'; // programs, workplans

    public function mount()
    {
        // Check if user has any relevant permissions
        $this->authorizeAny([
            'programs.view',
            'services.view',
            'work_plans.view',
            'work_plan_goals.view'
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.program-service-hub', [
            'programs' => $this->getPrograms(),
            'workPlans' => $this->getWorkPlans(),
        ]);
    }

    protected function getPrograms()
    {
        if ($this->activeTab !== 'programs' || !$this->can('programs.view')) {
            return collect();
        }

        return Program::withCount(['services', 'programEnrollments'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);
    }


    protected function getWorkPlans()
    {
        if ($this->activeTab !== 'workplans' || !$this->can('work_plans.view')) {
            return collect();
        }

        return WorkPlan::with(['program'])
            ->withCount('workPlanGoals')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('program', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
}