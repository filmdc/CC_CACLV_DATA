<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\Contract;
use App\Models\ProgramEnrollment;
use App\Models\Participant;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ProgramShow extends Component
{
    use WithPermissionChecks, WithPagination, WithProgramAccess;

    public $program;
    public $programId;
    public $showExpiredContracts = false;

    // Enrollment Modal State
    public $showEnrollmentModal = false;
    public $isEditingEnrollment = false;
    public $selectedEnrollmentId = null;
    public $enrollmentForm = [
        'participant_id' => '',
        'enrollment_date' => '',
        'exit_date' => '',
        'status' => 'active',
        'exit_reason' => '',
        'exit_notes' => '',
        'notes' => '',
    ];

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

    // =====================
    // Enrollment Methods
    // =====================

    public function showCreateEnrollment()
    {
        if (!$this->can('enrollments.create')) {
            session()->flash('error', 'You do not have permission to create enrollments.');
            return;
        }

        $this->resetEnrollmentForm();
        $this->enrollmentForm['enrollment_date'] = Carbon::today()->format('Y-m-d');
        $this->showEnrollmentModal = true;
        $this->isEditingEnrollment = false;
    }

    public function showEditEnrollment($enrollmentId)
    {
        if (!$this->can('enrollments.edit')) {
            session()->flash('error', 'You do not have permission to edit enrollments.');
            return;
        }

        $enrollment = ProgramEnrollment::find($enrollmentId);
        if (!$enrollment) {
            session()->flash('error', 'Enrollment not found.');
            return;
        }

        $this->selectedEnrollmentId = $enrollmentId;
        $this->enrollmentForm = [
            'participant_id' => $enrollment->participant_id,
            'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d') ?? '',
            'exit_date' => $enrollment->exit_date?->format('Y-m-d') ?? '',
            'status' => $enrollment->status,
            'exit_reason' => $enrollment->exit_reason ?? '',
            'exit_notes' => $enrollment->exit_notes ?? '',
            'notes' => $enrollment->notes ?? '',
        ];
        $this->showEnrollmentModal = true;
        $this->isEditingEnrollment = true;
    }

    public function saveEnrollment()
    {
        $this->validate([
            'enrollmentForm.participant_id' => 'required|exists:participants,id',
            'enrollmentForm.enrollment_date' => 'required|date',
            'enrollmentForm.exit_date' => 'nullable|date|after:enrollmentForm.enrollment_date',
            'enrollmentForm.status' => 'required|in:active,completed,withdrawn,transferred',
            'enrollmentForm.exit_reason' => 'nullable|string',
            'enrollmentForm.exit_notes' => 'nullable|string',
            'enrollmentForm.notes' => 'nullable|string',
        ], [
            'enrollmentForm.participant_id.required' => 'Please select a participant.',
            'enrollmentForm.enrollment_date.required' => 'Enrollment date is required.',
            'enrollmentForm.exit_date.after' => 'Exit date must be after enrollment date.',
        ]);

        try {
            if ($this->isEditingEnrollment) {
                $enrollment = ProgramEnrollment::find($this->selectedEnrollmentId);
                if ($enrollment) {
                    $enrollment->update([
                        'participant_id' => $this->enrollmentForm['participant_id'],
                        'enrollment_date' => $this->enrollmentForm['enrollment_date'],
                        'exit_date' => $this->enrollmentForm['exit_date'] ?: null,
                        'status' => $this->enrollmentForm['status'],
                        'exit_reason' => $this->enrollmentForm['exit_reason'] ?: null,
                        'exit_notes' => $this->enrollmentForm['exit_notes'] ?: null,
                        'notes' => $this->enrollmentForm['notes'] ?: null,
                    ]);
                    session()->flash('success', 'Enrollment updated successfully.');
                }
            } else {
                // Check for existing active enrollment
                if ($this->enrollmentForm['status'] === 'active') {
                    $existing = ProgramEnrollment::where('participant_id', $this->enrollmentForm['participant_id'])
                        ->where('program_id', $this->programId)
                        ->where('status', 'active')
                        ->first();

                    if ($existing) {
                        $this->addError('enrollmentForm.participant_id', 'This participant is already actively enrolled in this program.');
                        return;
                    }
                }

                ProgramEnrollment::create([
                    'participant_id' => $this->enrollmentForm['participant_id'],
                    'program_id' => $this->programId,
                    'enrollment_date' => $this->enrollmentForm['enrollment_date'],
                    'exit_date' => $this->enrollmentForm['exit_date'] ?: null,
                    'status' => $this->enrollmentForm['status'],
                    'exit_reason' => $this->enrollmentForm['exit_reason'] ?: null,
                    'exit_notes' => $this->enrollmentForm['exit_notes'] ?: null,
                    'notes' => $this->enrollmentForm['notes'] ?: null,
                ]);
                session()->flash('success', 'Participant enrolled successfully.');
            }

            $this->cancelEnrollmentModal();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteEnrollment($enrollmentId)
    {
        if (!$this->can('enrollments.delete')) {
            session()->flash('error', 'You do not have permission to delete enrollments.');
            return;
        }

        try {
            $enrollment = ProgramEnrollment::find($enrollmentId);
            if ($enrollment) {
                // Check for associated service enrollments
                if ($enrollment->serviceEnrollments()->count() > 0) {
                    session()->flash('error', 'Cannot delete enrollment with associated service enrollments. Delete service enrollments first.');
                    return;
                }

                $enrollment->delete();
                session()->flash('success', 'Enrollment deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete enrollment: ' . $e->getMessage());
        }
    }

    public function cancelEnrollmentModal()
    {
        $this->showEnrollmentModal = false;
        $this->resetEnrollmentForm();
    }

    public function resetEnrollmentForm()
    {
        $this->selectedEnrollmentId = null;
        $this->isEditingEnrollment = false;
        $this->enrollmentForm = [
            'participant_id' => '',
            'enrollment_date' => '',
            'exit_date' => '',
            'status' => 'active',
            'exit_reason' => '',
            'exit_notes' => '',
            'notes' => '',
        ];
        $this->resetErrorBag();
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

        // Get accessible participants for enrollment dropdown
        $participants = $this->getAccessibleParticipants()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('livewire.program-show', [
            'contracts' => $contracts,
            'enrollments' => $enrollments,
            'participants' => $participants,
        ])->layout('components.layouts.app');
    }
}