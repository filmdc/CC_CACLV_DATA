<?php

namespace App\Livewire;

use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class ProgramShow extends Component
{
    use WithPermissionChecks;

    public $program;
    public $programId;

    public function mount($id)
    {
        // Check if user has permission to view programs
        $this->authorizeAction('programs.view', 'You do not have permission to view programs.');
        
        $this->programId = $id;
        $this->program = Program::with([
            'services',
            'contracts.funder',
            'programEnrollments' => function($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($id);
    }

    public function editProgram()
    {
        return redirect()->route('program.management')->with('selectedProgram', $this->programId);
    }

    public function render()
    {
        return view('livewire.program-show')->layout('components.layouts.app');
    }
}