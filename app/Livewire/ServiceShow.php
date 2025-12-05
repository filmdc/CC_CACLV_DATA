<?php

namespace App\Livewire;

use App\Models\ProgramEnrollment;
use App\Models\Service;
use App\Models\ServiceEnrollment;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceShow extends Component
{
    use WithPagination, WithPermissionChecks, WithProgramAccess;

    public $service;

    public $serviceId;

    // Enrollment Modal State
    public $showEnrollmentModal = false;

    public $isEditingEnrollment = false;

    public $selectedEnrollmentId = null;

    public $enrollmentForm = [
        'participant_id' => '',
        'program_enrollment_id' => '',
        'enrollment_date' => '',
        'completion_date' => '',
        'status' => 'enrolled',
        'sessions_attended' => '',
        'hours_received' => '',
        'notes' => '',
    ];

    // For cascading dropdowns
    public $availableProgramEnrollments = [];

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'enrollmentsPage' => ['except' => 1],
    ];

    public function mount($id)
    {
        // Check if user has permission to view services
        $this->authorizeAction('services.view', 'You do not have permission to view services.');

        $this->serviceId = $id;
        $this->service = Service::with('program')->findOrFail($id);
    }

    public function editService()
    {
        return redirect()->route('service.management')->with('selectedService', $this->serviceId);
    }

    // =====================
    // Enrollment Methods
    // =====================

    public function showCreateEnrollment()
    {
        if (! $this->can('enrollments.create')) {
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
        if (! $this->can('enrollments.edit')) {
            session()->flash('error', 'You do not have permission to edit enrollments.');

            return;
        }

        $enrollment = ServiceEnrollment::with('programEnrollment')->find($enrollmentId);
        if (! $enrollment) {
            session()->flash('error', 'Enrollment not found.');

            return;
        }

        $this->selectedEnrollmentId = $enrollmentId;

        // Load available program enrollments for this participant
        if ($enrollment->participant_id) {
            $this->availableProgramEnrollments = ProgramEnrollment::where('participant_id', $enrollment->participant_id)
                ->where('program_id', $this->service->program_id)
                ->get();
        }

        $this->enrollmentForm = [
            'participant_id' => $enrollment->participant_id,
            'program_enrollment_id' => $enrollment->program_enrollment_id,
            'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d') ?? '',
            'completion_date' => $enrollment->completion_date?->format('Y-m-d') ?? '',
            'status' => $enrollment->status,
            'sessions_attended' => $enrollment->sessions_attended ?? '',
            'hours_received' => $enrollment->hours_received ?? '',
            'notes' => $enrollment->notes ?? '',
        ];
        $this->showEnrollmentModal = true;
        $this->isEditingEnrollment = true;
    }

    public function updatedEnrollmentFormParticipantId($value)
    {
        // When participant changes, load their program enrollments for this service's program
        $this->enrollmentForm['program_enrollment_id'] = '';
        $this->availableProgramEnrollments = [];

        if ($value) {
            $this->availableProgramEnrollments = ProgramEnrollment::where('participant_id', $value)
                ->where('program_id', $this->service->program_id)
                ->get();
        }
    }

    public function saveEnrollment()
    {
        $rules = [
            'enrollmentForm.participant_id' => 'required|exists:participants,id',
            'enrollmentForm.program_enrollment_id' => 'required|exists:program_enrollments,id',
            'enrollmentForm.enrollment_date' => 'required|date',
            'enrollmentForm.completion_date' => 'nullable|date|after_or_equal:enrollmentForm.enrollment_date',
            'enrollmentForm.status' => 'required|in:enrolled,in_progress,completed,withdrawn,transferred',
            'enrollmentForm.sessions_attended' => 'nullable|integer|min:0',
            'enrollmentForm.hours_received' => 'nullable|numeric|min:0',
            'enrollmentForm.notes' => 'nullable|string',
        ];

        $this->validate($rules, [
            'enrollmentForm.participant_id.required' => 'Please select a participant.',
            'enrollmentForm.program_enrollment_id.required' => 'Please select a program enrollment.',
            'enrollmentForm.enrollment_date.required' => 'Enrollment date is required.',
            'enrollmentForm.completion_date.after_or_equal' => 'Completion date must be on or after enrollment date.',
        ]);

        try {
            if ($this->isEditingEnrollment) {
                $enrollment = ServiceEnrollment::find($this->selectedEnrollmentId);
                if ($enrollment) {
                    $enrollment->update([
                        'participant_id' => $this->enrollmentForm['participant_id'],
                        'program_enrollment_id' => $this->enrollmentForm['program_enrollment_id'],
                        'enrollment_date' => $this->enrollmentForm['enrollment_date'],
                        'completion_date' => $this->enrollmentForm['completion_date'] ?: null,
                        'status' => $this->enrollmentForm['status'],
                        'sessions_attended' => $this->enrollmentForm['sessions_attended'] ?: null,
                        'hours_received' => $this->enrollmentForm['hours_received'] ?: null,
                        'notes' => $this->enrollmentForm['notes'] ?: null,
                    ]);
                    session()->flash('success', 'Enrollment updated successfully.');
                }
            } else {
                // Check for existing active enrollment
                if (in_array($this->enrollmentForm['status'], ['enrolled', 'in_progress'])) {
                    $existing = ServiceEnrollment::where('participant_id', $this->enrollmentForm['participant_id'])
                        ->where('service_id', $this->serviceId)
                        ->whereIn('status', ['enrolled', 'in_progress'])
                        ->first();

                    if ($existing) {
                        $this->addError('enrollmentForm.participant_id', 'This participant is already actively enrolled in this service.');

                        return;
                    }
                }

                ServiceEnrollment::create([
                    'participant_id' => $this->enrollmentForm['participant_id'],
                    'service_id' => $this->serviceId,
                    'program_enrollment_id' => $this->enrollmentForm['program_enrollment_id'],
                    'enrollment_date' => $this->enrollmentForm['enrollment_date'],
                    'completion_date' => $this->enrollmentForm['completion_date'] ?: null,
                    'status' => $this->enrollmentForm['status'],
                    'sessions_attended' => $this->enrollmentForm['sessions_attended'] ?: null,
                    'hours_received' => $this->enrollmentForm['hours_received'] ?: null,
                    'notes' => $this->enrollmentForm['notes'] ?: null,
                ]);
                session()->flash('success', 'Participant enrolled in service successfully.');
            }

            $this->cancelEnrollmentModal();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function deleteEnrollment($enrollmentId)
    {
        if (! $this->can('enrollments.delete')) {
            session()->flash('error', 'You do not have permission to delete enrollments.');

            return;
        }

        try {
            $enrollment = ServiceEnrollment::find($enrollmentId);
            if ($enrollment) {
                $enrollment->delete();
                session()->flash('success', 'Enrollment deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete enrollment: '.$e->getMessage());
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
        $this->availableProgramEnrollments = [];
        $this->enrollmentForm = [
            'participant_id' => '',
            'program_enrollment_id' => '',
            'enrollment_date' => '',
            'completion_date' => '',
            'status' => 'enrolled',
            'sessions_attended' => '',
            'hours_received' => '',
            'notes' => '',
        ];
        $this->resetErrorBag();
    }

    public function render()
    {
        // Get paginated enrollments
        $enrollments = ServiceEnrollment::with(['participant', 'programEnrollment'])
            ->where('service_id', $this->serviceId)
            ->orderBy('enrollment_date', 'desc')
            ->paginate(20, ['*'], 'enrollmentsPage');

        // Get accessible participants for enrollment dropdown
        $participants = $this->getAccessibleParticipants()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('livewire.service-show', [
            'enrollments' => $enrollments,
            'participants' => $participants,
        ])->layout('components.layouts.app');
    }
}
