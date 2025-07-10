<?php

namespace App\Livewire;

use App\Models\WorkPlan;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class WorkPlanShow extends Component
{
    use WithPermissionChecks;

    public $workPlan;
    public $workPlanId;

    public function mount($id)
    {
        // Check if user has permission to view work plans
        $this->authorizeAction('work-plans.view', 'You do not have permission to view work plans.');
        
        $this->workPlanId = $id;
        $this->workPlan = WorkPlan::with([
            'program',
            'goals.service'
        ])->findOrFail($id);
    }

    public function editWorkPlan()
    {
        return redirect()->route('work-plan.management')->with('selectedWorkPlan', $this->workPlanId);
    }

    public function render()
    {
        return view('livewire.work-plan-show')->layout('components.layouts.app');
    }
}