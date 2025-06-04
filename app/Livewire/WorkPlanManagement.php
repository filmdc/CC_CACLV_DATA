<?php

namespace App\Livewire;

use App\Models\WorkPlan;
use App\Models\Program;
use Livewire\Component;
use Livewire\WithPagination;

class WorkPlanManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedWorkPlan = null;
    public $showCreateForm = false;
    public $showEditForm = false;

    // WorkPlan Form Fields
    public $name = '';
    public $program_id = '';
    public $year = '';
    public $description = '';
    public $active = true;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateWorkPlan()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedWorkPlan = null;
        $this->resetWorkPlanForm();
    }

    public function showEditWorkPlan($workPlanId)
    {
        $this->selectedWorkPlan = WorkPlan::with(['program', 'goals'])->find($workPlanId);
        if ($this->selectedWorkPlan) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillWorkPlanForm();
        }
    }

    public function resetWorkPlanForm()
    {
        $this->name = '';
        $this->program_id = '';
        $this->year = date('Y');
        $this->description = '';
        $this->active = true;
    }

    public function fillWorkPlanForm()
    {
        if ($this->selectedWorkPlan) {
            $this->name = $this->selectedWorkPlan->name;
            $this->program_id = $this->selectedWorkPlan->program_id;
            $this->year = $this->selectedWorkPlan->year;
            $this->description = $this->selectedWorkPlan->description;
            $this->active = $this->selectedWorkPlan->active;
        }
    }

    public function createWorkPlan()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        try {
            WorkPlan::create([
                'name' => $this->name,
                'program_id' => $this->program_id,
                'year' => $this->year,
                'description' => $this->description,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Work plan created successfully.');
            $this->showCreateForm = false;
            $this->resetWorkPlanForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateWorkPlan()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        try {
            $this->selectedWorkPlan->update([
                'name' => $this->name,
                'program_id' => $this->program_id,
                'year' => $this->year,
                'description' => $this->description,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Work plan updated successfully.');
            $this->showEditForm = false;
            $this->selectedWorkPlan = null;
            $this->resetWorkPlanForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function toggleWorkPlanStatus($workPlanId)
    {
        try {
            $workPlan = WorkPlan::find($workPlanId);
            if ($workPlan) {
                $workPlan->update(['active' => !$workPlan->active]);
                session()->flash('success', 'Work plan status updated successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteWorkPlan($workPlanId)
    {
        try {
            $workPlan = WorkPlan::find($workPlanId);
            if ($workPlan) {
                if ($workPlan->goals()->count() > 0) {
                    session()->flash('error', 'Cannot delete work plan with associated goals.');
                    return;
                }
                
                $workPlan->delete();
                session()->flash('success', 'Work plan deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete work plan: ' . $e->getMessage());
        }
    }

    public function syncWorkPlanGoals($workPlanId)
    {
        try {
            $workPlan = WorkPlan::with('goals.service')->find($workPlanId);
            if (!$workPlan) {
                session()->flash('error', 'Work plan not found.');
                return;
            }

            $syncedCount = 0;
            foreach ($workPlan->goals as $goal) {
                if ($goal->updateActualFromServiceData()) {
                    $syncedCount++;
                }
            }

            session()->flash('success', "Successfully synced {$syncedCount} goals in {$workPlan->name} with current service data.");
        } catch (\Exception $e) {
            session()->flash('error', 'Error syncing work plan goals: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $workPlans = WorkPlan::with(['program', 'goals'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('year', 'like', '%' . $this->search . '%')
                      ->orWhereHas('program', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $programs = Program::where('active', true)->orderBy('name')->get();

        return view('livewire.work-plan-management', [
            'workPlans' => $workPlans,
            'programs' => $programs,
        ])->layout('components.layouts.app');
    }
}