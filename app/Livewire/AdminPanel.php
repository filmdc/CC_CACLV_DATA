<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Service;
use App\Models\Contract;
use App\Models\WorkPlan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AdminPanel extends Component
{
    use WithPagination;

    public $activeTab = 'overview';
    public $search = '';
    public $showCreateForm = false;
    
    // Program Form
    public $program_name = '';
    public $program_description = '';
    public $program_start_date = '';
    public $program_end_date = '';
    public $active = true;

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function showCreateProgram()
    {
        $this->showCreateForm = true;
        $this->resetProgramForm();
    }

    public function resetProgramForm()
    {
        $this->program_name = '';
        $this->program_description = '';
        $this->program_start_date = '';
        $this->program_end_date = '';
        $this->active = true;
    }

    public function updatedProgramStartDate()
    {
        // Normalize the date input to handle keyboard entries
        $this->normalizeDate('program_start_date');
        
        $this->validateOnly('program_start_date', [
            'program_start_date' => 'required|date',
        ]);
    }

    public function updatedProgramEndDate()
    {
        // Normalize the date input to handle keyboard entries
        $this->normalizeDate('program_end_date');
        
        $this->validateOnly('program_end_date', [
            'program_end_date' => 'nullable|date|after:program_start_date',
        ]);
    }

    private function normalizeDate($property)
    {
        if (empty($this->$property)) {
            return;
        }

        $date = trim($this->$property);
        
        // Skip if already in correct format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }
        
        try {
            // Handle common keyboard input formats
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
                // MM/DD/YYYY format
                $this->$property = sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
            } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $date, $matches)) {
                // MM-DD-YYYY format
                $this->$property = sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
            } elseif (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
                // YYYY/MM/DD format
                $this->$property = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
            } elseif (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $matches)) {
                // DD.MM.YYYY format (European)
                $this->$property = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            } else {
                // Try to parse with Carbon for other formats
                $carbonDate = \Carbon\Carbon::parse($date);
                $this->$property = $carbonDate->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // If parsing fails, leave as is and let validation handle it
        }
    }

    public function createProgram()
    {
        $this->validate([
            'program_name' => 'required|string|max:255',
            'program_description' => 'nullable|string',
            'program_start_date' => 'required|date',
            'program_end_date' => 'nullable|date|after:program_start_date',
        ]);

        try {
            Program::create([
                'name' => $this->program_name,
                'description' => $this->program_description,
                'start_date' => $this->program_start_date,
                'end_date' => $this->program_end_date,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Program created successfully.');
            $this->showCreateForm = false;
            $this->resetProgramForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function toggleProgramStatus($programId)
    {
        $program = Program::find($programId);
        if ($program) {
            $program->update(['active' => !$program->active]);
            session()->flash('success', 'Program status updated successfully.');
        }
    }

    public function render()
    {
        $data = [];

        switch ($this->activeTab) {
            case 'overview':
                $data['systemStats'] = [
                    'total_participants' => Participant::count(),
                    'total_households' => Household::count(),
                    'total_programs' => Program::count(),
                    'active_programs' => Program::where('active', true)->count(),
                    'total_services' => Service::count(),
                    'total_contracts' => Contract::count(),
                    'total_work_plans' => WorkPlan::count(),
                    'total_users' => User::count(),
                ];
                break;

            case 'programs':
                $data['programs'] = Program::when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('description', 'like', '%' . $this->search . '%');
                })
                ->withCount(['services', 'workPlans'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
                break;

            case 'users':
                $data['users'] = User::when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
                break;

            case 'reports':
                $data['reportData'] = [
                    'participants_by_month' => Participant::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                        ->groupBy('month')
                        ->orderBy('month', 'desc')
                        ->limit(12)
                        ->get(),
                    'households_by_poverty_level' => Household::whereNotNull('hhs_poverty_percentage')
                        ->selectRaw('
                            CASE 
                                WHEN hhs_poverty_percentage <= 50 THEN "0-50%"
                                WHEN hhs_poverty_percentage <= 100 THEN "51-100%"
                                WHEN hhs_poverty_percentage <= 150 THEN "101-150%"
                                WHEN hhs_poverty_percentage <= 200 THEN "151-200%"
                                ELSE "200%+"
                            END as poverty_range,
                            COUNT(*) as count
                        ')
                        ->groupBy('poverty_range')
                        ->get(),
                ];
                break;
        }

        return view('livewire.admin-panel', $data)->layout('components.layouts.app');
    }
}
