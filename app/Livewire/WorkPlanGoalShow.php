<?php

namespace App\Livewire;

use App\Models\WorkPlanGoal;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class WorkPlanGoalShow extends Component
{
    use WithPermissionChecks;

    public $goal;
    public $goalId;

    public function mount($id)
    {
        // Check if user has permission to view work plan goals
        $this->authorizeAction('work-plan-goals.view', 'You do not have permission to view work plan goals.');
        
        $this->goalId = $id;
        $this->goal = WorkPlanGoal::with([
            'workPlan.program',
            'service'
        ])->findOrFail($id);
    }

    public function editGoal()
    {
        return redirect()->route('work-plan-goal.management')->with('selectedGoal', $this->goalId);
    }

    public function render()
    {
        return view('livewire.work-plan-goal-show')->layout('components.layouts.app');
    }
}