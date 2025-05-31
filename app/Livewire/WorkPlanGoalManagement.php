<?php

namespace App\Livewire;

use App\Models\WorkPlanGoal;
use App\Models\WorkPlan;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class WorkPlanGoalManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedGoal = null;
    public $showCreateForm = false;
    public $showEditForm = false;

    // Goal Form Fields
    public $work_plan_id = '';
    public $service_id = '';
    public $goal_name = '';
    public $description = '';
    public $target_number = '';
    public $actual_number = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateGoal()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedGoal = null;
        $this->resetGoalForm();
    }

    public function showEditGoal($goalId)
    {
        $this->selectedGoal = WorkPlanGoal::with(['workPlan', 'service'])->find($goalId);
        if ($this->selectedGoal) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillGoalForm();
        }
    }

    public function resetGoalForm()
    {
        $this->work_plan_id = '';
        $this->service_id = '';
        $this->goal_name = '';
        $this->description = '';
        $this->target_number = '';
        $this->actual_number = '';
    }

    public function fillGoalForm()
    {
        if ($this->selectedGoal) {
            $this->work_plan_id = $this->selectedGoal->work_plan_id;
            $this->service_id = $this->selectedGoal->service_id;
            $this->goal_name = $this->selectedGoal->goal_name;
            $this->description = $this->selectedGoal->description;
            $this->target_number = $this->selectedGoal->target_number;
            $this->actual_number = $this->selectedGoal->actual_number;
        }
    }

    public function createGoal()
    {
        $this->validate([
            'work_plan_id' => 'required|exists:work_plans,id',
            'service_id' => 'nullable|exists:services,id',
            'goal_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_number' => 'required|integer|min:0',
            'actual_number' => 'nullable|integer|min:0',
        ]);

        try {
            WorkPlanGoal::create([
                'work_plan_id' => $this->work_plan_id,
                'service_id' => $this->service_id ?: null,
                'goal_name' => $this->goal_name,
                'description' => $this->description,
                'target_number' => $this->target_number,
                'actual_number' => $this->actual_number ?: 0,
            ]);

            session()->flash('success', 'Work plan goal created successfully.');
            $this->showCreateForm = false;
            $this->resetGoalForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateGoal()
    {
        $this->validate([
            'work_plan_id' => 'required|exists:work_plans,id',
            'service_id' => 'nullable|exists:services,id',
            'goal_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_number' => 'required|integer|min:0',
            'actual_number' => 'nullable|integer|min:0',
        ]);

        try {
            $this->selectedGoal->update([
                'work_plan_id' => $this->work_plan_id,
                'service_id' => $this->service_id ?: null,
                'goal_name' => $this->goal_name,
                'description' => $this->description,
                'target_number' => $this->target_number,
                'actual_number' => $this->actual_number ?: 0,
            ]);

            session()->flash('success', 'Work plan goal updated successfully.');
            $this->showEditForm = false;
            $this->selectedGoal = null;
            $this->resetGoalForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteGoal($goalId)
    {
        try {
            $goal = WorkPlanGoal::find($goalId);
            if ($goal) {
                $goal->delete();
                session()->flash('success', 'Work plan goal deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete goal: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $goals = WorkPlanGoal::with(['workPlan.program', 'service'])
            ->when($this->search, function ($query) {
                $query->where('goal_name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('workPlan', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('service', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $workPlans = WorkPlan::where('active', true)->orderBy('name')->get();
        $services = Service::where('active', true)->orderBy('name')->get();

        return view('livewire.work-plan-goal-management', [
            'goals' => $goals,
            'workPlans' => $workPlans,
            'services' => $services,
        ])->layout('components.layouts.app');
    }
}