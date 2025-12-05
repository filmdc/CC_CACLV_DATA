<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use App\Models\Program;
use App\Models\Service;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Livewire\Component;
use Carbon\Carbon;

class ParticipantShow extends Component
{
    use WithPermissionChecks, WithProgramAccess;

    public $participant;
    public $participantId;
    public $canViewSensitiveData = false;

    // Program Enrollment Modal State
    public $showProgramEnrollmentModal = false;
    public $isEditingProgramEnrollment = false;
    public $selectedProgramEnrollmentId = null;
    public $programEnrollmentForm = [
        'program_id' => '',
        'enrollment_date' => '',
        'exit_date' => '',
        'status' => 'active',
        'exit_reason' => '',
        'exit_notes' => '',
        'notes' => '',
    ];

    // Service Enrollment Modal State
    public $showServiceEnrollmentModal = false;
    public $isEditingServiceEnrollment = false;
    public $selectedServiceEnrollmentId = null;
    public $serviceEnrollmentForm = [
        'program_enrollment_id' => '',
        'service_id' => '',
        'enrollment_date' => '',
        'completion_date' => '',
        'status' => 'enrolled',
        'hours_received' => '',
        'sessions_attended' => '',
        'completion_notes' => '',
        'notes' => '',
    ];
    public $availableServices = [];

    public function mount($id)
    {
        // Check if user has permission to view participants
        $this->authorizeAction('participants.view', 'You do not have permission to view participants.');

        $this->participantId = $id;
        $this->participant = Participant::with([
            'household.headOfHousehold',
            'household.currentAddress',
            'programEnrollments.program',
            'serviceEnrollments.service'
        ])->findOrFail($id);

        // Check if user can view sensitive data
        $this->canViewSensitiveData = $this->canViewSensitiveData($this->participant);
    }

    public function editParticipant()
    {
        // Check if user can edit this participant
        if (!auth()->user()->can('update', $this->participant)) {
            session()->flash('error', 'You do not have permission to edit this participant.');
            return;
        }

        return redirect()->route('participant.management')->with('selectedParticipant', $this->participantId);
    }

    // =====================
    // Program Enrollment Methods
    // =====================

    public function showCreateProgramEnrollment()
    {
        if (!$this->can('enrollments.create')) {
            session()->flash('error', 'You do not have permission to create enrollments.');
            return;
        }

        $this->resetProgramEnrollmentForm();
        $this->programEnrollmentForm['enrollment_date'] = Carbon::today()->format('Y-m-d');
        $this->showProgramEnrollmentModal = true;
        $this->isEditingProgramEnrollment = false;
    }

    public function showEditProgramEnrollment($enrollmentId)
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

        $this->selectedProgramEnrollmentId = $enrollmentId;
        $this->programEnrollmentForm = [
            'program_id' => $enrollment->program_id,
            'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d') ?? '',
            'exit_date' => $enrollment->exit_date?->format('Y-m-d') ?? '',
            'status' => $enrollment->status,
            'exit_reason' => $enrollment->exit_reason ?? '',
            'exit_notes' => $enrollment->exit_notes ?? '',
            'notes' => $enrollment->notes ?? '',
        ];
        $this->showProgramEnrollmentModal = true;
        $this->isEditingProgramEnrollment = true;
    }

    public function saveProgramEnrollment()
    {
        $this->validate([
            'programEnrollmentForm.program_id' => 'required|exists:programs,id',
            'programEnrollmentForm.enrollment_date' => 'required|date',
            'programEnrollmentForm.exit_date' => 'nullable|date|after:programEnrollmentForm.enrollment_date',
            'programEnrollmentForm.status' => 'required|in:active,completed,withdrawn,transferred',
            'programEnrollmentForm.exit_reason' => 'nullable|string',
            'programEnrollmentForm.exit_notes' => 'nullable|string',
            'programEnrollmentForm.notes' => 'nullable|string',
        ], [
            'programEnrollmentForm.program_id.required' => 'Please select a program.',
            'programEnrollmentForm.enrollment_date.required' => 'Enrollment date is required.',
            'programEnrollmentForm.exit_date.after' => 'Exit date must be after enrollment date.',
        ]);

        try {
            if ($this->isEditingProgramEnrollment) {
                $enrollment = ProgramEnrollment::find($this->selectedProgramEnrollmentId);
                if ($enrollment) {
                    $enrollment->update([
                        'program_id' => $this->programEnrollmentForm['program_id'],
                        'enrollment_date' => $this->programEnrollmentForm['enrollment_date'],
                        'exit_date' => $this->programEnrollmentForm['exit_date'] ?: null,
                        'status' => $this->programEnrollmentForm['status'],
                        'exit_reason' => $this->programEnrollmentForm['exit_reason'] ?: null,
                        'exit_notes' => $this->programEnrollmentForm['exit_notes'] ?: null,
                        'notes' => $this->programEnrollmentForm['notes'] ?: null,
                    ]);
                    session()->flash('success', 'Program enrollment updated successfully.');
                }
            } else {
                // Check for existing active enrollment
                if ($this->programEnrollmentForm['status'] === 'active') {
                    $existing = ProgramEnrollment::where('participant_id', $this->participantId)
                        ->where('program_id', $this->programEnrollmentForm['program_id'])
                        ->where('status', 'active')
                        ->first();

                    if ($existing) {
                        $this->addError('programEnrollmentForm.program_id', 'Participant is already actively enrolled in this program.');
                        return;
                    }
                }

                ProgramEnrollment::create([
                    'participant_id' => $this->participantId,
                    'program_id' => $this->programEnrollmentForm['program_id'],
                    'enrollment_date' => $this->programEnrollmentForm['enrollment_date'],
                    'exit_date' => $this->programEnrollmentForm['exit_date'] ?: null,
                    'status' => $this->programEnrollmentForm['status'],
                    'exit_reason' => $this->programEnrollmentForm['exit_reason'] ?: null,
                    'exit_notes' => $this->programEnrollmentForm['exit_notes'] ?: null,
                    'notes' => $this->programEnrollmentForm['notes'] ?: null,
                ]);
                session()->flash('success', 'Program enrollment created successfully.');
            }

            $this->cancelProgramEnrollmentModal();
            $this->refreshParticipant();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteProgramEnrollment($enrollmentId)
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
                    session()->flash('error', 'Cannot delete program enrollment with associated service enrollments. Delete service enrollments first.');
                    return;
                }

                $enrollment->delete();
                session()->flash('success', 'Program enrollment deleted successfully.');
                $this->refreshParticipant();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete enrollment: ' . $e->getMessage());
        }
    }

    public function cancelProgramEnrollmentModal()
    {
        $this->showProgramEnrollmentModal = false;
        $this->resetProgramEnrollmentForm();
    }

    public function resetProgramEnrollmentForm()
    {
        $this->selectedProgramEnrollmentId = null;
        $this->isEditingProgramEnrollment = false;
        $this->programEnrollmentForm = [
            'program_id' => '',
            'enrollment_date' => '',
            'exit_date' => '',
            'status' => 'active',
            'exit_reason' => '',
            'exit_notes' => '',
            'notes' => '',
        ];
        $this->resetErrorBag();
    }

    // =====================
    // Service Enrollment Methods
    // =====================

    public function showCreateServiceEnrollment()
    {
        if (!$this->can('enrollments.create')) {
            session()->flash('error', 'You do not have permission to create enrollments.');
            return;
        }

        $this->resetServiceEnrollmentForm();
        $this->serviceEnrollmentForm['enrollment_date'] = Carbon::today()->format('Y-m-d');
        $this->showServiceEnrollmentModal = true;
        $this->isEditingServiceEnrollment = false;
    }

    public function showEditServiceEnrollment($enrollmentId)
    {
        if (!$this->can('enrollments.edit')) {
            session()->flash('error', 'You do not have permission to edit enrollments.');
            return;
        }

        $enrollment = ServiceEnrollment::with('programEnrollment')->find($enrollmentId);
        if (!$enrollment) {
            session()->flash('error', 'Service enrollment not found.');
            return;
        }

        $this->selectedServiceEnrollmentId = $enrollmentId;
        $this->serviceEnrollmentForm = [
            'program_enrollment_id' => $enrollment->program_enrollment_id,
            'service_id' => $enrollment->service_id,
            'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d') ?? '',
            'completion_date' => $enrollment->completion_date?->format('Y-m-d') ?? '',
            'status' => $enrollment->status,
            'hours_received' => $enrollment->hours_received ?? '',
            'sessions_attended' => $enrollment->sessions_attended ?? '',
            'completion_notes' => $enrollment->completion_notes ?? '',
            'notes' => $enrollment->notes ?? '',
        ];

        // Load services for the selected program
        $this->loadServicesForProgramEnrollment($enrollment->program_enrollment_id);

        $this->showServiceEnrollmentModal = true;
        $this->isEditingServiceEnrollment = true;
    }

    public function updatedServiceEnrollmentFormProgramEnrollmentId($value)
    {
        $this->serviceEnrollmentForm['service_id'] = '';
        $this->loadServicesForProgramEnrollment($value);
    }

    protected function loadServicesForProgramEnrollment($programEnrollmentId)
    {
        if ($programEnrollmentId) {
            $programEnrollment = ProgramEnrollment::find($programEnrollmentId);
            if ($programEnrollment) {
                $this->availableServices = Service::where('program_id', $programEnrollment->program_id)
                    ->where('active', true)
                    ->orderBy('name')
                    ->get()
                    ->toArray();
                return;
            }
        }
        $this->availableServices = [];
    }

    public function saveServiceEnrollment()
    {
        $this->validate([
            'serviceEnrollmentForm.program_enrollment_id' => 'required|exists:program_enrollments,id',
            'serviceEnrollmentForm.service_id' => 'required|exists:services,id',
            'serviceEnrollmentForm.enrollment_date' => 'required|date',
            'serviceEnrollmentForm.completion_date' => 'nullable|date|after_or_equal:serviceEnrollmentForm.enrollment_date',
            'serviceEnrollmentForm.status' => 'required|in:enrolled,in_progress,completed,withdrawn',
            'serviceEnrollmentForm.hours_received' => 'nullable|numeric|min:0',
            'serviceEnrollmentForm.sessions_attended' => 'nullable|integer|min:0',
            'serviceEnrollmentForm.completion_notes' => 'nullable|string',
            'serviceEnrollmentForm.notes' => 'nullable|string',
        ], [
            'serviceEnrollmentForm.program_enrollment_id.required' => 'Please select a program enrollment.',
            'serviceEnrollmentForm.service_id.required' => 'Please select a service.',
            'serviceEnrollmentForm.enrollment_date.required' => 'Enrollment date is required.',
            'serviceEnrollmentForm.completion_date.after_or_equal' => 'Completion date must be on or after enrollment date.',
        ]);

        try {
            if ($this->isEditingServiceEnrollment) {
                $enrollment = ServiceEnrollment::find($this->selectedServiceEnrollmentId);
                if ($enrollment) {
                    $enrollment->update([
                        'program_enrollment_id' => $this->serviceEnrollmentForm['program_enrollment_id'],
                        'service_id' => $this->serviceEnrollmentForm['service_id'],
                        'enrollment_date' => $this->serviceEnrollmentForm['enrollment_date'],
                        'completion_date' => $this->serviceEnrollmentForm['completion_date'] ?: null,
                        'status' => $this->serviceEnrollmentForm['status'],
                        'hours_received' => $this->serviceEnrollmentForm['hours_received'] ?: null,
                        'sessions_attended' => $this->serviceEnrollmentForm['sessions_attended'] ?: null,
                        'completion_notes' => $this->serviceEnrollmentForm['completion_notes'] ?: null,
                        'notes' => $this->serviceEnrollmentForm['notes'] ?: null,
                    ]);
                    session()->flash('success', 'Service enrollment updated successfully.');
                }
            } else {
                ServiceEnrollment::create([
                    'participant_id' => $this->participantId,
                    'program_enrollment_id' => $this->serviceEnrollmentForm['program_enrollment_id'],
                    'service_id' => $this->serviceEnrollmentForm['service_id'],
                    'enrollment_date' => $this->serviceEnrollmentForm['enrollment_date'],
                    'completion_date' => $this->serviceEnrollmentForm['completion_date'] ?: null,
                    'status' => $this->serviceEnrollmentForm['status'],
                    'hours_received' => $this->serviceEnrollmentForm['hours_received'] ?: null,
                    'sessions_attended' => $this->serviceEnrollmentForm['sessions_attended'] ?: null,
                    'completion_notes' => $this->serviceEnrollmentForm['completion_notes'] ?: null,
                    'notes' => $this->serviceEnrollmentForm['notes'] ?: null,
                ]);
                session()->flash('success', 'Service enrollment created successfully.');
            }

            $this->cancelServiceEnrollmentModal();
            $this->refreshParticipant();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteServiceEnrollment($enrollmentId)
    {
        if (!$this->can('enrollments.delete')) {
            session()->flash('error', 'You do not have permission to delete enrollments.');
            return;
        }

        try {
            $enrollment = ServiceEnrollment::find($enrollmentId);
            if ($enrollment) {
                $enrollment->delete();
                session()->flash('success', 'Service enrollment deleted successfully.');
                $this->refreshParticipant();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete service enrollment: ' . $e->getMessage());
        }
    }

    public function cancelServiceEnrollmentModal()
    {
        $this->showServiceEnrollmentModal = false;
        $this->resetServiceEnrollmentForm();
    }

    public function resetServiceEnrollmentForm()
    {
        $this->selectedServiceEnrollmentId = null;
        $this->isEditingServiceEnrollment = false;
        $this->serviceEnrollmentForm = [
            'program_enrollment_id' => '',
            'service_id' => '',
            'enrollment_date' => '',
            'completion_date' => '',
            'status' => 'enrolled',
            'hours_received' => '',
            'sessions_attended' => '',
            'completion_notes' => '',
            'notes' => '',
        ];
        $this->availableServices = [];
        $this->resetErrorBag();
    }

    // =====================
    // Helper Methods
    // =====================

    protected function refreshParticipant()
    {
        $this->participant = Participant::with([
            'household.headOfHousehold',
            'household.currentAddress',
            'programEnrollments.program',
            'serviceEnrollments.service'
        ])->findOrFail($this->participantId);
    }

    public function render()
    {
        // Get reference data for display
        $referenceData = [
            'genders' => ReferenceData::getOptions('gender'),
            'ethnicities' => ReferenceData::getOptions('ethnicity'),
            'races' => ReferenceData::getOptions('race'),
            'education_levels' => ReferenceData::getOptions('education_level'),
            'disability_statuses' => ReferenceData::getOptions('disability_status'),
            'health_insurance_statuses' => ReferenceData::getOptions('health_insurance_status'),
            'military_statuses' => ReferenceData::getOptions('military_status'),
            'work_statuses' => ReferenceData::getOptions('work_status'),
        ];

        // Filter enrollments to only show those in user's programs (if not admin)
        $programEnrollments = $this->participant->programEnrollments;
        $serviceEnrollments = $this->participant->serviceEnrollments;

        if (!$this->canViewSensitiveData) {
            // Non-admin users without program access see no enrollments
            $programEnrollments = collect();
            $serviceEnrollments = collect();
        } elseif (!auth()->user()->isAdmin()) {
            // Filter to only user's programs
            $userProgramIds = auth()->user()->getAccessibleProgramIds();

            $programEnrollments = $programEnrollments->filter(function ($enrollment) use ($userProgramIds) {
                return in_array($enrollment->program_id, $userProgramIds);
            });

            $serviceEnrollments = $serviceEnrollments->filter(function ($enrollment) use ($userProgramIds) {
                return in_array($enrollment->service->program_id ?? null, $userProgramIds);
            });
        }

        // Get accessible programs for enrollment dropdown
        $programs = $this->getAccessiblePrograms()
            ->active()
            ->regularPrograms()
            ->orderBy('name')
            ->get();

        // Get active program enrollments for this participant (for service enrollment dropdown)
        $activeProgramEnrollments = $this->participant->programEnrollments
            ->where('status', 'active')
            ->filter(function ($enrollment) use ($programs) {
                return $programs->contains('id', $enrollment->program_id);
            });

        return view('livewire.participant-show', [
            'referenceData' => $referenceData,
            'programEnrollments' => $programEnrollments,
            'serviceEnrollments' => $serviceEnrollments,
            'programs' => $programs,
            'activeProgramEnrollments' => $activeProgramEnrollments,
        ])->layout('components.layouts.app');
    }
}
