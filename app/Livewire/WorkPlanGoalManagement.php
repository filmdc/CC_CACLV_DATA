<?php

namespace App\Livewire;

use App\Models\WorkPlanGoal;
use App\Models\WorkPlan;
use App\Models\Service;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class WorkPlanGoalManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $selectedGoal = null;
    public $showCreateForm = false;
    public $showEditForm = false;
    public $showViewModal = false;
    public $selectedViewGoal = null;

    // Goal Form Fields
    public $work_plan_id = '';
    public $service_id = '';
    public $goal_name = '';
    public $description = '';
    public $target_number = '';
    public $actual_number = '';

    public function mount()
    {
        if (!$this->can('work_plan_goals.view') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to access this page.');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateGoal()
    {
        if (!$this->can('work_plan_goals.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create work plan goals.');
        }
        
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedGoal = null;
        $this->resetGoalForm();
    }

    public function showEditGoal($goalId)
    {
        if (!$this->can('work_plan_goals.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit work plan goals.');
        }
        
        $this->selectedGoal = WorkPlanGoal::with(['workPlan', 'service'])->find($goalId);
        if ($this->selectedGoal) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->showViewModal = false;
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
        $this->showViewModal = false;
        $this->selectedViewGoal = null;
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
        if (!$this->can('work_plan_goals.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create work plan goals.');
        }
        
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
        if (!$this->can('work_plan_goals.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit work plan goals.');
        }
        
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
        if (!$this->can('work_plan_goals.delete') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to delete work plan goals.');
        }
        
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

    public function syncGoalWithServiceData($goalId)
    {
        if (!$this->can('work_plan_goals.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to sync work plan goals.');
        }
        
        try {
            $goal = WorkPlanGoal::with(['service', 'workPlan.program'])->find($goalId);
            if ($goal && $goal->updateActualFromServiceData()) {
                $message = 'Goal synced successfully. ';
                if ($goal->service) {
                    $message .= "Service '{$goal->service->name}' has {$goal->service->getEnrolledParticipantsCount()} active enrollments.";
                } else {
                    $message .= "Program-wide count: {$goal->getProgramWideServiceCount()} total enrollments.";
                }
                session()->flash('success', $message);
            } else {
                session()->flash('error', 'Unable to sync goal - no service linked or data unavailable.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error syncing goal: ' . $e->getMessage());
        }
    }

    public function syncAllGoalsWithServiceData()
    {
        if (!$this->can('work_plan_goals.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to sync work plan goals.');
        }
        
        try {
            $goals = WorkPlanGoal::with(['service', 'workPlan.program'])->get();
            $syncedCount = 0;
            
            foreach ($goals as $goal) {
                if ($goal->updateActualFromServiceData()) {
                    $syncedCount++;
                }
            }
            
            session()->flash('success', "Successfully synced {$syncedCount} goals with current service data.");
        } catch (\Exception $e) {
            session()->flash('error', 'Error syncing goals: ' . $e->getMessage());
        }
    }

    public function makeGoalProgramWide($goalId)
    {
        if (!$this->can('work_plan_goals.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit work plan goals.');
        }
        
        try {
            $goal = WorkPlanGoal::with(['workPlan.program'])->find($goalId);
            if ($goal) {
                $goal->update(['service_id' => null]);
                $goal->updateActualFromServiceData(); // This will now use program-wide counting
                session()->flash('success', "Goal updated to track all services in the program. New count: {$goal->actual_number}");
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating goal: ' . $e->getMessage());
        }
    }

    public function viewGoal($goalId)
    {
        // Redirect to the goal show page instead of opening a modal
        return redirect()->route('work-plan-goal.show', $goalId);
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