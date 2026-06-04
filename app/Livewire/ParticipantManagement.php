<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Models\ReferenceData;
use App\Models\Service;
use App\Models\ServiceEnrollment;
use App\Services\HouseholdDecisionRequiredException;
use App\Services\ParticipantDeletionService;
use App\Services\ParticipantMergeService;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ParticipantManagement extends Component
{
    use WithPagination, WithPermissionChecks, WithProgramAccess;

    public function mount()
    {
        // Check if user has permission to view participants
        $this->authorizeAction('participants.view', 'You do not have permission to view participants.');

        // Check if we were redirected here to edit a specific participant
        if (session()->has('selectedParticipant')) {
            $participantId = session()->get('selectedParticipant');
            session()->forget('selectedParticipant');
            $this->showEditParticipant($participantId);
        }
    }

    public $search = '';

    public $selectedParticipant = null;

    public $showEditForm = false;

    public $showViewModal = false;

    // Record selection (for merge)
    public array $selectedIds = [];

    // Merge flow
    public bool $showMergeModal = false;

    public $mergeSurvivorId = null;

    public $mergeLoserId = null;

    public array $mergeFieldChoices = [];

    public array $mergeConflicts = [];

    // Delete-with-associated-records flow
    public bool $showDeleteModal = false;

    public $deleteParticipantId = null;

    public ?string $deleteParticipantName = null;

    public array $deletePreview = [];

    public bool $deleteNeedsDecision = false;

    public array $deleteCoMembers = [];

    public string $deleteDecision = '';      // '' | 'promote' | 'force'

    public $promoteToHeadId = null;

    public string $deleteConfirmText = '';

    // Participant Form Fields
    public $first_name = '';

    public $middle_name = '';

    public $last_name = '';

    public $suffix = '';

    public $date_of_birth = '';

    public $gender = '';

    public $telephone = '';

    public $email = '';

    public $education_level = '';

    public $disability_status = '';

    public $health_insurance_status = '';

    public $ethnicity = '';

    public $race = [];

    public $military_status = '';

    public $work_status = '';

    // Enrollment Management
    public $showEnrollmentSection = false;

    public $program_id = '';

    public $enrollment_date = '';

    public $enrollment_notes = '';

    public $service_id = '';

    public $service_enrollment_date = '';

    public $service_notes = '';

    public $selectedEnrollment = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showEditParticipant($participantId)
    {
        $this->selectedParticipant = Participant::with('household')->find($participantId);
        if ($this->selectedParticipant) {
            // Check if user has access to edit this participant
            if (! auth()->user()->can('update', $this->selectedParticipant)) {
                session()->flash('error', 'You do not have access to edit this participant. You must be assigned to one of their enrolled programs.');

                return;
            }

            $this->showEditForm = true;
            $this->showEnrollmentSection = false;
            $this->fillParticipantForm();
            $this->resetEnrollmentForm();
        }
    }

    public function fillParticipantForm()
    {
        if ($this->selectedParticipant) {
            $this->first_name = $this->selectedParticipant->first_name;
            $this->middle_name = $this->selectedParticipant->middle_name;
            $this->last_name = $this->selectedParticipant->last_name;
            $this->suffix = $this->selectedParticipant->suffix;
            $this->date_of_birth = $this->selectedParticipant->date_of_birth?->format('Y-m-d') ?? '';
            $this->gender = $this->selectedParticipant->gender;
            $this->telephone = $this->selectedParticipant->telephone;
            $this->email = $this->selectedParticipant->email;
            $this->education_level = $this->selectedParticipant->education_level;
            $this->disability_status = $this->selectedParticipant->disability_status;
            $this->health_insurance_status = $this->selectedParticipant->health_insurance_status;
            $this->ethnicity = $this->selectedParticipant->ethnicity;
            $this->race = is_string($this->selectedParticipant->race) ? json_decode($this->selectedParticipant->race, true) ?? [] : ($this->selectedParticipant->race ?? []);
            $this->military_status = $this->selectedParticipant->military_status;
            $this->work_status = $this->selectedParticipant->work_status;
        }
    }

    public function updateParticipant()
    {
        $this->authorizeAction('participants.edit', 'You do not have permission to edit participants.');

        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'education_level' => 'nullable|string',
            'disability_status' => 'nullable|string',
            'health_insurance_status' => 'nullable|string',
            'ethnicity' => 'nullable|string',
            'race' => 'nullable|array',
            'military_status' => 'nullable|string',
            'work_status' => 'nullable|string',
        ]);

        try {
            // Get the active reference data set for updated data
            $activeDataSet = \App\Models\ReferenceDataSet::getActive();

            $this->selectedParticipant->update([
                'reference_data_set_id' => $activeDataSet?->id,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'date_of_birth' => $this->date_of_birth,
                'gender' => $this->gender,
                'telephone' => $this->telephone,
                'email' => $this->email,
                'education_level' => $this->education_level,
                'disability_status' => $this->disability_status,
                'health_insurance_status' => $this->health_insurance_status,
                'ethnicity' => $this->ethnicity,
                'race' => is_array($this->race) ? json_encode($this->race) : $this->race,
                'military_status' => $this->military_status,
                'work_status' => $this->work_status,
            ]);

            session()->flash('success', 'Participant updated successfully.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function viewParticipant($participantId)
    {
        // Redirect to the participant show page instead of opening a modal
        return redirect()->route('participant.show', $participantId);
    }

    public function resetForm()
    {
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->suffix = '';
        $this->date_of_birth = '';
        $this->gender = '';
        $this->telephone = '';
        $this->email = '';
        $this->education_level = '';
        $this->disability_status = '';
        $this->health_insurance_status = '';
        $this->ethnicity = '';
        $this->race = [];
        $this->military_status = '';
        $this->work_status = '';
        $this->showEditForm = false;
        $this->showViewModal = false;
        $this->selectedParticipant = null;
        $this->showEnrollmentSection = false;
        $this->resetEnrollmentForm();
    }

    public function resetEnrollmentForm()
    {
        $this->program_id = '';
        $this->enrollment_date = Carbon::today()->format('Y-m-d');
        $this->enrollment_notes = '';
        $this->service_id = '';
        $this->service_enrollment_date = Carbon::today()->format('Y-m-d');
        $this->service_notes = '';
        $this->selectedEnrollment = null;
    }

    public function toggleEnrollmentSection()
    {
        $this->showEnrollmentSection = ! $this->showEnrollmentSection;
        if ($this->showEnrollmentSection) {
            $this->resetEnrollmentForm();
        }
    }

    public function createProgramEnrollment()
    {
        if (! $this->selectedParticipant) {
            session()->flash('error', 'No participant selected.');

            return;
        }

        $this->validate([
            'program_id' => 'required|exists:programs,id',
            'enrollment_date' => 'required|date',
        ]);

        // Check if user has access to enroll in this program
        if (! auth()->user()->hasAccessToProgram($this->program_id)) {
            session()->flash('error', 'You do not have permission to enroll participants in this program.');

            return;
        }

        try {
            // Check for existing active enrollment
            $existingEnrollment = ProgramEnrollment::where('participant_id', $this->selectedParticipant->id)
                ->where('program_id', $this->program_id)
                ->where('status', 'active')
                ->first();

            if ($existingEnrollment) {
                session()->flash('error', 'Participant is already actively enrolled in this program.');

                return;
            }

            ProgramEnrollment::create([
                'participant_id' => $this->selectedParticipant->id,
                'program_id' => $this->program_id,
                'enrollment_date' => $this->enrollment_date,
                'status' => 'active',
                'notes' => $this->enrollment_notes,
            ]);

            session()->flash('success', 'Program enrollment created successfully.');
            $this->resetEnrollmentForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function createServiceEnrollment()
    {
        if (! $this->selectedParticipant) {
            session()->flash('error', 'No participant selected.');

            return;
        }

        $this->validate([
            'program_id' => 'required|exists:programs,id',
            'service_id' => 'required|exists:services,id',
            'service_enrollment_date' => 'required|date',
        ]);

        try {
            // Find active program enrollment
            $programEnrollment = ProgramEnrollment::where('participant_id', $this->selectedParticipant->id)
                ->where('program_id', $this->program_id)
                ->where('status', 'active')
                ->first();

            if (! $programEnrollment) {
                session()->flash('error', 'Participant must be enrolled in the program first.');

                return;
            }

            ServiceEnrollment::create([
                'participant_id' => $this->selectedParticipant->id,
                'service_id' => $this->service_id,
                'program_enrollment_id' => $programEnrollment->id,
                'enrollment_date' => $this->service_enrollment_date,
                'status' => 'enrolled',
                'notes' => $this->service_notes,
            ]);

            session()->flash('success', 'Service enrollment created successfully.');
            $this->resetEnrollmentForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->resetForm();
    }

    public function deleteParticipant($participantId)
    {
        $this->authorizeAction('participants.delete', 'You do not have permission to delete participants.');

        try {
            $participant = Participant::find($participantId);
            if ($participant) {
                // Check if user has access to delete this participant
                if (! auth()->user()->can('delete', $participant)) {
                    session()->flash('error', 'You do not have access to delete this participant.');

                    return;
                }

                if ($participant->is_head_of_household) {
                    session()->flash('error', 'Cannot delete head of household. Transfer headship first.');

                    return;
                }

                $participant->delete();
                session()->flash('success', 'Participant deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete participant: '.$e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Record merge (select two participants, combine into one)
    // ------------------------------------------------------------------

    public function openMergeModal(): void
    {
        $this->authorizeAction('participants.merge', 'You do not have permission to merge participants.');

        if (count($this->selectedIds) !== 2) {
            session()->flash('error', 'Select exactly two participants to merge.');

            return;
        }

        [$first, $second] = array_values($this->selectedIds);
        $this->mergeSurvivorId = (int) $first;
        $this->mergeLoserId = (int) $second;
        $this->mergeFieldChoices = [];
        $this->computeMergeConflicts();
        $this->showMergeModal = true;
    }

    public function swapMergeDirection(): void
    {
        [$this->mergeSurvivorId, $this->mergeLoserId] = [$this->mergeLoserId, $this->mergeSurvivorId];
        $this->mergeFieldChoices = [];
        $this->computeMergeConflicts();
    }

    /**
     * Build the list of fields where both records hold a different non-empty value,
     * so the admin can choose which to keep. Empty survivor fields are auto-filled
     * from the loser by the service and need no choice.
     */
    protected function computeMergeConflicts(): void
    {
        $this->mergeConflicts = [];

        $survivor = Participant::find($this->mergeSurvivorId);
        $loser = Participant::find($this->mergeLoserId);

        if (! $survivor || ! $loser) {
            return;
        }

        $protected = ['id', 'household_id', 'created_at', 'updated_at', 'notes', 'is_head_of_household', 'reference_data_set_id'];

        foreach ($survivor->getFillable() as $field) {
            if (in_array($field, $protected, true)) {
                continue;
            }

            $sv = $survivor->$field;
            $lv = $loser->$field;
            $svScalar = is_array($sv) ? json_encode($sv) : $sv;
            $lvScalar = is_array($lv) ? json_encode($lv) : $lv;

            $bothPresent = ($svScalar !== null && $svScalar !== '') && ($lvScalar !== null && $lvScalar !== '');

            if ($bothPresent && $svScalar !== $lvScalar) {
                $this->mergeConflicts[] = [
                    'field' => $field,
                    'survivor' => is_array($sv) ? implode(', ', $sv) : (string) $sv,
                    'loser' => is_array($lv) ? implode(', ', $lv) : (string) $lv,
                ];
                // Default to keeping the survivor's value.
                $this->mergeFieldChoices[$field] = $this->mergeFieldChoices[$field] ?? 'use_survivor';
            }
        }
    }

    public function confirmMerge(ParticipantMergeService $mergeService): void
    {
        $this->authorizeAction('participants.merge', 'You do not have permission to merge participants.');

        $survivor = Participant::find($this->mergeSurvivorId);
        $loser = Participant::find($this->mergeLoserId);

        if (! $survivor || ! $loser || $survivor->id === $loser->id) {
            session()->flash('error', 'Invalid merge selection.');
            $this->closeMergeModal();

            return;
        }

        try {
            $choices = collect($this->mergeFieldChoices)
                ->filter(fn ($choice) => $choice === 'use_loser')
                ->map(fn () => 'use_loser')
                ->toArray();

            $summary = $mergeService->merge($survivor, $loser, $choices, auth()->id());

            session()->flash('success', sprintf(
                'Merged %s into %s. Reassigned %d enrollment(s), %d housing job(s), %d property(ies), %d communication log(s).',
                $loser->full_name,
                $survivor->full_name,
                $summary['program_enrollments_reassigned'] + $summary['service_enrollments_reassigned'],
                $summary['housing_jobs_reassigned'],
                $summary['properties_reassigned'],
                $summary['communication_logs_reassigned'],
            ));
        } catch (\Throwable $e) {
            session()->flash('error', 'Merge failed: '.$e->getMessage());
        }

        $this->closeMergeModal();
    }

    public function closeMergeModal(): void
    {
        $this->showMergeModal = false;
        $this->mergeSurvivorId = null;
        $this->mergeLoserId = null;
        $this->mergeFieldChoices = [];
        $this->mergeConflicts = [];
        $this->selectedIds = [];
    }

    // ------------------------------------------------------------------
    // Delete a participant and all associated records
    // ------------------------------------------------------------------

    public function startDelete($participantId, ParticipantDeletionService $deletionService): void
    {
        $this->authorizeAction('participants.delete', 'You do not have permission to delete participants.');

        $participant = Participant::find($participantId);
        if (! $participant) {
            session()->flash('error', 'Participant not found.');

            return;
        }

        if (! auth()->user()->can('delete', $participant)) {
            session()->flash('error', 'You do not have access to delete this participant.');

            return;
        }

        $inspection = $deletionService->inspect($participant);

        $this->deleteParticipantId = $participant->id;
        $this->deleteParticipantName = $participant->full_name;
        $this->deletePreview = $deletionService->previewCounts($participant);
        $this->deleteNeedsDecision = $inspection['needs_decision'];
        $this->deleteCoMembers = $inspection['other_members']
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->full_name])
            ->values()
            ->toArray();
        $this->deleteDecision = '';
        $this->promoteToHeadId = null;
        $this->deleteConfirmText = '';
        $this->showDeleteModal = true;
    }

    public function confirmDelete(ParticipantDeletionService $deletionService): void
    {
        $this->authorizeAction('participants.delete', 'You do not have permission to delete participants.');

        if (strtoupper(trim($this->deleteConfirmText)) !== 'DELETE') {
            session()->flash('error', 'Type DELETE to confirm.');

            return;
        }

        $participant = Participant::find($this->deleteParticipantId);
        if (! $participant) {
            session()->flash('error', 'Participant not found.');
            $this->closeDeleteModal();

            return;
        }

        if (! auth()->user()->can('delete', $participant)) {
            session()->flash('error', 'You do not have access to delete this participant.');
            $this->closeDeleteModal();

            return;
        }

        $options = [];
        if ($this->deleteNeedsDecision) {
            if ($this->deleteDecision === 'promote') {
                if (! $this->promoteToHeadId) {
                    session()->flash('error', 'Select a household member to promote to head.');

                    return;
                }
                $options['promote_to_head_id'] = (int) $this->promoteToHeadId;
            } elseif ($this->deleteDecision === 'force') {
                $options['force'] = true;
            } else {
                session()->flash('error', 'Choose how to handle the household before deleting.');

                return;
            }
        }

        try {
            $summary = $deletionService->delete($participant, $options, auth()->id());

            session()->flash('success', sprintf(
                'Deleted %s and all associated records. A recovery snapshot was saved to %s.',
                $summary['participant_name'],
                $summary['snapshot_path'],
            ));
        } catch (HouseholdDecisionRequiredException $e) {
            session()->flash('error', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            session()->flash('error', 'Delete failed: '.$e->getMessage());
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteParticipantId = null;
        $this->deleteParticipantName = null;
        $this->deletePreview = [];
        $this->deleteNeedsDecision = false;
        $this->deleteCoMembers = [];
        $this->deleteDecision = '';
        $this->promoteToHeadId = null;
        $this->deleteConfirmText = '';
    }

    /**
     * Check if current user can edit a specific participant.
     */
    public function canEditParticipant($participantId): bool
    {
        $participant = Participant::find($participantId);

        return $participant && auth()->user()->can('update', $participant);
    }

    /**
     * Check if current user can view sensitive data for a participant.
     */
    public function canViewParticipantSensitiveData($participantId): bool
    {
        return auth()->user()->canAccessParticipantData($participantId);
    }

    public function render()
    {
        // Build base query - ALL users can see all participants for contact purposes
        // Sensitive data access is controlled at the field/view level
        $participants = Participant::with(['household', 'programEnrollments.program', 'serviceEnrollments.service'])
            ->when($this->search, function ($query) {
                $query->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhereHas('household', function ($q) {
                        $q->where('household_code', 'like', '%'.$this->search.'%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Use the selected participant's reference data set if editing, otherwise use active set
        $dataSetId = $this->selectedParticipant ? $this->selectedParticipant->reference_data_set_id : null;

        // Get programs - restrict to user's accessible programs for non-admins
        $programs = $this->getAccessiblePrograms()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $services = collect();

        if ($this->program_id) {
            // Verify user has access to this program before showing services
            if (auth()->user()->hasAccessToProgram($this->program_id)) {
                $services = Service::where('program_id', $this->program_id)
                    ->where('active', true)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('livewire.participant-management', [
            'participants' => $participants,
            'genderOptions' => ReferenceData::getOptions('gender', true, $dataSetId),
            'educationLevels' => ReferenceData::getOptions('education_level', true, $dataSetId),
            'ethnicityOptions' => ReferenceData::getOptions('ethnicity', true, $dataSetId),
            'raceOptions' => ReferenceData::getOptions('race', true, $dataSetId),
            'disabilityStatuses' => ReferenceData::getOptions('disability_status', true, $dataSetId),
            'healthInsuranceStatuses' => ReferenceData::getOptions('health_insurance_status', true, $dataSetId),
            'militaryStatuses' => ReferenceData::getOptions('military_status', true, $dataSetId),
            'workStatuses' => ReferenceData::getOptions('work_status', true, $dataSetId),
            'programs' => $programs,
            'services' => $services,
        ])->layout('components.layouts.app');
    }
}
