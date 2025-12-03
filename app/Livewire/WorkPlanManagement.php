<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\WorkPlan;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class WorkPlanManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';

    public $selectedWorkPlan = null;

    public $showCreateForm = false;

    public $showEditForm = false;

    public $showViewModal = false;

    public $selectedViewWorkPlan = null;

    // WorkPlan Form Fields
    public $name = '';

    public $program_id = '';

    public $year = '';

    public $description = '';

    public $active = true;

    public function mount()
    {
        if (! $this->can('work_plans.view') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to access this page.');
        }
    }

    /**
     * Check if the current user can edit work plans for a specific program.
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

    public function showCreateWorkPlan()
    {
        if (! $this->can('work_plans.create') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to create work plans.');
        }

        // Check if user has any editable programs
        $editablePrograms = $this->getEditableProgramIds();
        if (empty($editablePrograms)) {
            session()->flash('error', 'You do not have permission to create work plans. You must be assigned to at least one program.');

            return;
        }

        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedWorkPlan = null;
        $this->resetWorkPlanForm();
    }

    public function showEditWorkPlan($workPlanId)
    {
        if (! $this->can('work_plans.edit') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to edit work plans.');
        }

        $this->selectedWorkPlan = WorkPlan::with(['program', 'goals'])->find($workPlanId);
        if ($this->selectedWorkPlan) {
            // Check if user can edit this program
            if (! $this->canEditProgram($this->selectedWorkPlan->program_id)) {
                session()->flash('error', 'You do not have permission to edit work plans for this program.');

                return;
            }

            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->showViewModal = false;
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
        $this->showViewModal = false;
        $this->selectedViewWorkPlan = null;
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
        if (! $this->can('work_plans.create') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to create work plans.');
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        // Verify user has access to the selected program
        if (! $this->canEditProgram($this->program_id)) {
            session()->flash('error', 'You do not have permission to create work plans for this program.');

            return;
        }

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
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateWorkPlan()
    {
        if (! $this->can('work_plans.edit') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to edit work plans.');
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        // Verify user has access to the work plan's current program
        if (! $this->canEditProgram($this->selectedWorkPlan->program_id)) {
            session()->flash('error', 'You do not have permission to edit work plans for this program.');

            return;
        }

        // If changing programs, verify user has access to the new program too
        if ($this->program_id != $this->selectedWorkPlan->program_id && ! $this->canEditProgram($this->program_id)) {
            session()->flash('error', 'You do not have permission to move work plans to this program.');

            return;
        }

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
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function cancelForm()
    {
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->selectedWorkPlan = null;
        $this->resetWorkPlanForm();
    }

    public function toggleWorkPlanStatus($workPlanId)
    {
        if (! $this->can('work_plans.edit') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to edit work plans.');
        }

        try {
            $workPlan = WorkPlan::find($workPlanId);
            if ($workPlan) {
                // Verify user has access to the work plan's program
                if (! $this->canEditProgram($workPlan->program_id)) {
                    session()->flash('error', 'You do not have permission to modify work plans for this program.');

                    return;
                }

                $workPlan->update(['active' => ! $workPlan->active]);
                session()->flash('success', 'Work plan status updated successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function deleteWorkPlan($workPlanId)
    {
        if (! $this->can('work_plans.delete') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to delete work plans.');
        }

        try {
            $workPlan = WorkPlan::find($workPlanId);
            if ($workPlan) {
                // Verify user has access to the work plan's program
                if (! $this->canEditProgram($workPlan->program_id)) {
                    session()->flash('error', 'You do not have permission to delete work plans for this program.');

                    return;
                }

                if ($workPlan->goals()->count() > 0) {
                    session()->flash('error', 'Cannot delete work plan with associated goals.');

                    return;
                }

                $workPlan->delete();
                session()->flash('success', 'Work plan deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete work plan: '.$e->getMessage());
        }
    }

    public function syncWorkPlanGoals($workPlanId)
    {
        if (! $this->can('work_plans.edit') && ! $this->isAdmin()) {
            abort(403, 'You do not have permission to sync work plan goals.');
        }

        try {
            $workPlan = WorkPlan::with('goals.service')->find($workPlanId);
            if (! $workPlan) {
                session()->flash('error', 'Work plan not found.');

                return;
            }

            // Verify user has access to the work plan's program
            if (! $this->canEditProgram($workPlan->program_id)) {
                session()->flash('error', 'You do not have permission to sync work plans for this program.');

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
            session()->flash('error', 'Error syncing work plan goals: '.$e->getMessage());
        }
    }

    public function viewWorkPlan($workPlanId)
    {
        // Redirect to the workPlan show page instead of opening a modal
        return redirect()->route('work-plan.show', $workPlanId);
    }

    public function render()
    {
        $workPlans = WorkPlan::with(['program', 'goals'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('year', 'like', '%'.$this->search.'%')
                    ->orWhereHas('program', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    });
            })
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get all programs for display
        $programs = Program::where('active', true)->orderBy('name')->get();

        // Get editable program IDs for the current user
        $editableProgramIds = $this->getEditableProgramIds();

        // For the form dropdown, show only editable programs for non-admins
        $user = Auth::user();
        if ($user->isAdmin() || $user->hasRole('Contract Manager')) {
            $editablePrograms = $programs;
        } else {
            $editablePrograms = Program::where('active', true)
                ->whereIn('id', $editableProgramIds)
                ->orderBy('name')
                ->get();
        }

        return view('livewire.work-plan-management', [
            'workPlans' => $workPlans,
            'programs' => $programs,
            'editablePrograms' => $editablePrograms,
            'editableProgramIds' => $editableProgramIds,
        ])->layout('components.layouts.app');
    }
}
