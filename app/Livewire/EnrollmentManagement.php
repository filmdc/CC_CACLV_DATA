<?php

namespace App\Livewire;

use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Service;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class EnrollmentManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $statusFilter = '';
    public $programFilter = '';
    public $enrollmentType = 'program'; // 'program' or 'service'
    
    // Program Enrollment Form
    public $showProgramEnrollmentForm = false;
    public $selectedProgramEnrollment = null;
    public $showViewModal = false;
    public $selectedViewEnrollment = null;
    public $participant_id = '';
    public $program_id = '';
    public $enrollment_date = '';
    public $exit_date = '';
    public $status = 'active';
    public $exit_reason = '';
    public $exit_notes = '';
    public $notes = '';
    
    // Service Enrollment Form
    public $showServiceEnrollmentForm = false;
    public $selectedServiceEnrollment = null;
    public $program_enrollment_id = '';
    public $service_id = '';
    public $service_enrollment_date = '';
    public $completion_date = '';
    public $service_status = 'enrolled';
    public $outcomes = [];
    public $hours_received = '';
    public $sessions_attended = '';
    public $completion_notes = '';
    public $service_notes = '';

    public function mount()
    {
        $this->enrollment_date = Carbon::today()->format('Y-m-d');
        $this->service_enrollment_date = Carbon::today()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedProgramFilter()
    {
        $this->resetPage();
    }

    public function updatedEnrollmentType()
    {
        $this->resetPage();
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->programFilter = '';
    }

    // Program Enrollment Methods
    public function showCreateProgramEnrollment()
    {
        $this->showProgramEnrollmentForm = true;
        $this->selectedProgramEnrollment = null;
        $this->resetProgramEnrollmentForm();
    }

    public function showEditProgramEnrollment($enrollmentId)
    {
        $this->selectedProgramEnrollment = ProgramEnrollment::with(['participant', 'program'])->find($enrollmentId);
        if ($this->selectedProgramEnrollment) {
            $this->showProgramEnrollmentForm = true;
            $this->showViewModal = false;
            $this->fillProgramEnrollmentForm();
        }
    }

    public function resetProgramEnrollmentForm()
    {
        $this->participant_id = '';
        $this->program_id = '';
        $this->enrollment_date = Carbon::today()->format('Y-m-d');
        $this->exit_date = '';
        $this->status = 'active';
        $this->exit_reason = '';
        $this->exit_notes = '';
        $this->notes = '';
        $this->showViewModal = false;
        $this->selectedViewEnrollment = null;
    }

    public function fillProgramEnrollmentForm()
    {
        if ($this->selectedProgramEnrollment) {
            $this->participant_id = $this->selectedProgramEnrollment->participant_id;
            $this->program_id = $this->selectedProgramEnrollment->program_id;
            $this->enrollment_date = $this->selectedProgramEnrollment->enrollment_date?->format('Y-m-d') ?? '';
            $this->exit_date = $this->selectedProgramEnrollment->exit_date?->format('Y-m-d') ?? '';
            $this->status = $this->selectedProgramEnrollment->status;
            $this->exit_reason = $this->selectedProgramEnrollment->exit_reason;
            $this->exit_notes = $this->selectedProgramEnrollment->exit_notes;
            $this->notes = $this->selectedProgramEnrollment->notes;
        }
    }

    public function createProgramEnrollment()
    {
        // Reset any previous errors
        $this->resetErrorBag();
        
        // Debug: Log the form data
        \Log::info('Creating enrollment with data:', [
            'participant_id' => $this->participant_id,
            'program_id' => $this->program_id,
            'enrollment_date' => $this->enrollment_date,
            'status' => $this->status
        ]);

        try {
            $this->validate([
                'participant_id' => 'required|exists:participants,id',
                'program_id' => 'required|exists:programs,id',
                'enrollment_date' => 'required|date',
                'exit_date' => 'nullable|date|after:enrollment_date',
                'status' => 'required|in:active,completed,withdrawn,transferred',
                'exit_reason' => 'nullable|string',
                'exit_notes' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            // Only check for existing active enrollment if the new enrollment is also active
            if ($this->status === 'active') {
                $existingEnrollment = ProgramEnrollment::where('participant_id', $this->participant_id)
                    ->where('program_id', $this->program_id)
                    ->where('status', 'active')
                    ->first();

                if ($existingEnrollment) {
                    $this->addError('participant_id', 'Participant is already actively enrolled in this program.');
                    return;
                }
            }

            $enrollment = ProgramEnrollment::create([
                'participant_id' => $this->participant_id,
                'program_id' => $this->program_id,
                'enrollment_date' => $this->enrollment_date,
                'exit_date' => $this->exit_date ?: null,
                'status' => $this->status,
                'exit_reason' => $this->exit_reason ?: null,
                'exit_notes' => $this->exit_notes ?: null,
                'notes' => $this->notes ?: null,
            ]);

            \Log::info('Enrollment created successfully', ['enrollment_id' => $enrollment->id]);

            session()->flash('success', 'Program enrollment created successfully.');
            $this->showProgramEnrollmentForm = false;
            $this->resetProgramEnrollmentForm();
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e; // Re-throw validation exceptions to show errors
        } catch (\Exception $e) {
            \Log::error('Enrollment creation failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateProgramEnrollment()
    {
        $this->validate([
            'participant_id' => 'required|exists:participants,id',
            'program_id' => 'required|exists:programs,id',
            'enrollment_date' => 'required|date',
            'exit_date' => 'nullable|date|after:enrollment_date',
            'status' => 'required|string',
            'exit_reason' => 'nullable|string',
            'exit_notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->selectedProgramEnrollment->update([
                'participant_id' => $this->participant_id,
                'program_id' => $this->program_id,
                'enrollment_date' => $this->enrollment_date,
                'exit_date' => $this->exit_date,
                'status' => $this->status,
                'exit_reason' => $this->exit_reason,
                'exit_notes' => $this->exit_notes,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Program enrollment updated successfully.');
            $this->showProgramEnrollmentForm = false;
            $this->selectedProgramEnrollment = null;
            $this->resetProgramEnrollmentForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteProgramEnrollment($enrollmentId)
    {
        try {
            $enrollment = ProgramEnrollment::find($enrollmentId);
            if ($enrollment) {
                // Check for associated service enrollments
                if ($enrollment->serviceEnrollments()->count() > 0) {
                    session()->flash('error', 'Cannot delete program enrollment with associated service enrollments.');
                    return;
                }
                
                $enrollment->delete();
                session()->flash('success', 'Program enrollment deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete enrollment: ' . $e->getMessage());
        }
    }

    public function cancelProgramEnrollmentForm()
    {
        $this->showProgramEnrollmentForm = false;
        $this->selectedProgramEnrollment = null;
        $this->resetProgramEnrollmentForm();
    }

    public function viewEnrollment($enrollmentId)
    {
        if ($this->enrollmentType === 'program') {
            $this->selectedViewEnrollment = ProgramEnrollment::with([
                'participant.household',
                'program',
                'serviceEnrollments' => function($query) {
                    $query->with('service', 'participant')->orderBy('enrollment_date', 'desc');
                }
            ])->find($enrollmentId);
        } else {
            $this->selectedViewEnrollment = ServiceEnrollment::with([
                'participant.household',
                'service.program',
                'programEnrollment.program'
            ])->find($enrollmentId);
        }
        
        if ($this->selectedViewEnrollment) {
            $this->showViewModal = true;
            $this->showProgramEnrollmentForm = false;
        }
    }

    public function render()
    {
        if ($this->enrollmentType === 'program') {
            $enrollments = ProgramEnrollment::with(['participant', 'program'])
                ->when($this->search, function ($query) {
                    $query->whereHas('participant', function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                          ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    })->orWhereHas('program', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->statusFilter, function ($query) {
                    $query->where('status', $this->statusFilter);
                })
                ->when($this->programFilter, function ($query) {
                    $query->where('program_id', $this->programFilter);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $enrollments = ServiceEnrollment::with(['participant', 'service', 'programEnrollment.program'])
                ->when($this->search, function ($query) {
                    $query->whereHas('participant', function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                          ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    })->orWhereHas('service', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->statusFilter, function ($query) {
                    $query->where('status', $this->statusFilter);
                })
                ->when($this->programFilter, function ($query) {
                    $query->whereHas('programEnrollment', function ($q) {
                        $q->where('program_id', $this->programFilter);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        $participants = Participant::orderBy('first_name')->get();
        $programs = Program::active()->regularPrograms()->orderBy('name')->get();
        $services = Service::where('active', true)->with('program')->orderBy('name')->get();

        return view('livewire.enrollment-management', [
            'enrollments' => $enrollments,
            'participants' => $participants,
            'programs' => $programs,
            'services' => $services,
        ])->layout('components.layouts.app');
    }
}