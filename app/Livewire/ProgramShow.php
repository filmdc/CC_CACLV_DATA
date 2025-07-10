<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\Contract;
use App\Models\ProgramEnrollment;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramShow extends Component
{
    use WithPermissionChecks, WithPagination;

    public $program;
    public $programId;
    public $showExpiredContracts = false;
    
    protected $paginationTheme = 'tailwind';
    
    // Define query string parameters for pagination
    protected $queryString = [
        'contractsPage' => ['except' => 1],
        'enrollmentsPage' => ['except' => 1],
    ];

    public function mount($id)
    {
        // Check if user has permission to view programs
        $this->authorizeAction('programs.view', 'You do not have permission to view programs.');
        
        $this->programId = $id;
        $this->program = Program::with('services')->findOrFail($id);
    }

    public function editProgram()
    {
        return redirect()->route('program.management')->with('selectedProgram', $this->programId);
    }

    public function updatedShowExpiredContracts()
    {
        $this->resetPage('contractsPage');
    }

    public function render()
    {
        // Get current year
        $currentYear = now()->year;
        $today = now()->toDateString();
        
        // Get paginated contracts with filtering
        $contractsQuery = Contract::with('funder')
            ->where('program_id', $this->programId);
            
        if (!$this->showExpiredContracts) {
            // Filter out archived contracts and expired contracts from previous years
            $contractsQuery->where('archived', false)
                ->where(function($query) use ($currentYear, $today) {
                    // Include contracts that are not expired
                    $query->where('end_date', '>=', $today)
                        // OR include expired contracts only if they expired in the current year or later
                        ->orWhere(function($q) use ($currentYear) {
                            $q->where('end_date', '<', now()->toDateString())
                              ->whereYear('end_date', '>=', $currentYear);
                        });
                });
        }
        
        $contracts = $contractsQuery->orderBy('start_date', 'desc')
            ->paginate(20, ['*'], 'contractsPage');
            
        // Get paginated enrollments
        $enrollments = ProgramEnrollment::with('participant.household')
            ->where('program_id', $this->programId)
            ->orderBy('enrollment_date', 'desc')
            ->paginate(20, ['*'], 'enrollmentsPage');
        
        return view('livewire.program-show', [
            'contracts' => $contracts,
            'enrollments' => $enrollments
        ])->layout('components.layouts.app');
    }
}