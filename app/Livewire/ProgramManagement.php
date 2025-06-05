<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedProgram = null;
    public $showCreateForm = false;
    public $showEditForm = false;

    // Program Form Fields
    public $name = '';
    public $description = '';
    public $program_manager_id = '';
    public $active = true;
    public $start_date = '';
    public $end_date = '';
    public $notes = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateProgram()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedProgram = null;
        $this->resetProgramForm();
    }

    public function showEditProgram($programId)
    {
        $this->selectedProgram = Program::with('programManager')->find($programId);
        if ($this->selectedProgram) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillProgramForm();
        }
    }

    public function resetProgramForm()
    {
        $this->name = '';
        $this->description = '';
        $this->program_manager_id = '';
        $this->active = true;
        $this->start_date = '';
        $this->end_date = '';
        $this->notes = '';
    }

    public function fillProgramForm()
    {
        if ($this->selectedProgram) {
            $this->name = $this->selectedProgram->name;
            $this->description = $this->selectedProgram->description;
            $this->program_manager_id = $this->selectedProgram->program_manager_id;
            $this->active = $this->selectedProgram->active;
            $this->start_date = $this->selectedProgram->start_date ? $this->selectedProgram->start_date->format('Y-m-d') : '';
            $this->end_date = $this->selectedProgram->end_date ? $this->selectedProgram->end_date->format('Y-m-d') : '';
            $this->notes = $this->selectedProgram->notes;
        }
    }

    public function createProgram()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:programs,name',
            'description' => 'nullable|string',
            'program_manager_id' => 'nullable|exists:users,id',
            'active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            Program::create([
                'name' => $this->name,
                'description' => $this->description,
                'program_manager_id' => $this->program_manager_id ?: null,
                'active' => $this->active,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Program created successfully.');
            $this->showCreateForm = false;
            $this->resetProgramForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateProgram()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:programs,name,' . $this->selectedProgram->id,
            'description' => 'nullable|string',
            'program_manager_id' => 'nullable|exists:users,id',
            'active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->selectedProgram->update([
                'name' => $this->name,
                'description' => $this->description,
                'program_manager_id' => $this->program_manager_id ?: null,
                'active' => $this->active,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Program updated successfully.');
            $this->showEditForm = false;
            $this->selectedProgram = null;
            $this->resetProgramForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function toggleProgramStatus($programId)
    {
        try {
            $program = Program::find($programId);
            if ($program) {
                $program->update(['active' => !$program->active]);
                session()->flash('success', 'Program status updated successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteProgram($programId)
    {
        try {
            $program = Program::find($programId);
            if ($program) {
                if ($program->services()->count() > 0 || $program->contracts()->count() > 0) {
                    session()->flash('error', 'Cannot delete program with associated services or contracts.');
                    return;
                }
                
                $program->delete();
                session()->flash('success', 'Program deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete program: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $programs = Program::with(['programManager', 'services', 'contracts'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('programManager', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $users = User::orderBy('name')->get();

        return view('livewire.program-management', [
            'programs' => $programs,
            'users' => $users,
        ])->layout('components.layouts.app');
    }
}