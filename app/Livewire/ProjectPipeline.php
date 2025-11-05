<?php

namespace App\Livewire;

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectPipeline extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $filterProgram = '';
    public $filterStatus = '';
    public $sortBy = 'target_completion_date';
    public $sortDirection = 'asc';

    public function mount()
    {
        // Check if user has permission to view projects
        $this->authorizeAny(['housing_jobs.view', 'projects.view']);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getRiskStatus($job): array
    {
        if (!$job->target_completion_date) {
            return ['status' => 'unknown', 'color' => 'gray', 'label' => 'No Deadline'];
        }

        $daysUntil = now()->diffInDays($job->target_completion_date, false);

        if ($daysUntil < 0) {
            return ['status' => 'overdue', 'color' => 'red', 'label' => 'Overdue'];
        } elseif ($daysUntil <= 7) {
            return ['status' => 'at_risk', 'color' => 'yellow', 'label' => 'At Risk'];
        } else {
            return ['status' => 'on_track', 'color' => 'green', 'label' => 'On Track'];
        }
    }

    public function render()
    {
        // Get all housing jobs that have milestones (PM enabled)
        $projects = HousingJob::query()
            ->with(['program', 'participant', 'property', 'milestones.tasks', 'milestones.documents'])
            ->whereHas('milestones')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('job_title', 'like', '%' . $this->search . '%')
                      ->orWhere('job_number', 'like', '%' . $this->search . '%')
                      ->orWhereHas('participant', function ($pq) {
                          $pq->where('first_name', 'like', '%' . $this->search . '%')
                             ->orWhere('last_name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('property', function ($propq) {
                          $propq->where('address', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filterProgram, function ($query) {
                $query->where('program_id', $this->filterProgram);
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $programs = Program::orderBy('name')->get();

        return view('livewire.project-pipeline', [
            'projects' => $projects,
            'programs' => $programs,
        ]);
    }
}
