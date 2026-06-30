<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\PotentialDuplicate;
use App\Services\DuplicateDetectionService;
use App\Services\HouseholdDecisionRequiredException;
use App\Services\ParticipantDeletionService;
use App\Services\ParticipantMergeService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class DuplicateResolutionManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // Active tab: 'duplicates' | 'merge' | 'delete'
    public string $activeTab = 'duplicates';

    // UI State
    public $showDetailModal = false;

    public $selectedDuplicate = null;

    public $reviewNotes = '';

    public $mergeInstructions = [];

    // Manual record merge (Merge Two Records tab)
    public string $mergeSurvivorSearch = '';

    public string $mergeLoserSearch = '';

    public $mergeSurvivorId = null;

    public $mergeLoserId = null;

    public array $mergeFieldChoices = [];

    public array $mergeConflicts = [];

    // Delete participant + all data (Delete Participant tab)
    public string $deleteSearch = '';

    public $deleteParticipantId = null;

    public ?string $deleteParticipantName = null;

    public array $deletePreview = [];

    public bool $deleteNeedsDecision = false;

    public array $deleteCoMembers = [];

    public string $deleteDecision = '';

    public $promoteToHeadId = null;

    public string $deleteConfirmText = '';

    // Filters
    public $statusFilter = 'pending';

    public $sourceFilter = '';

    public $confidenceFilter = '';

    public $search = '';

    // Sorting
    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    protected $duplicateDetectionService;

    public bool $rescanRunning = false;

    public ?array $rescanSummary = null;

    public function boot(DuplicateDetectionService $duplicateDetectionService)
    {
        $this->duplicateDetectionService = $duplicateDetectionService;
    }

    public function mount()
    {
        // Page is reachable by anyone who can view/manage participants; the Merge and
        // Delete tabs and their actions are gated individually by their own permissions.
        $this->authorizeAny(
            ['participants.view', 'participants.create', 'participants.import', 'participants.merge', 'participants.delete'],
            'You do not have permission to access participant tools.'
        );
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['duplicates', 'merge', 'delete'], true) ? $tab : 'duplicates';
    }

    public function render()
    {
        $duplicates = $this->getPotentialDuplicates();

        return view('livewire.duplicate-resolution-management', [
            'duplicates' => $duplicates,
            'pendingCount' => PotentialDuplicate::pending()->count(),
            'totalCount' => PotentialDuplicate::count(),
            'rescanSummary' => $this->rescanSummary,

            // Merge tab — load full detail for the side-by-side comparison
            'mergeSurvivor' => $this->mergeSurvivorId ? $this->loadMergeRecord($this->mergeSurvivorId) : null,
            'mergeLoser' => $this->mergeLoserId ? $this->loadMergeRecord($this->mergeLoserId) : null,
            'mergeSurvivorResults' => $this->activeTab === 'merge'
                ? $this->searchParticipants($this->mergeSurvivorSearch, array_filter([$this->mergeLoserId]))
                : collect(),
            'mergeLoserResults' => $this->activeTab === 'merge'
                ? $this->searchParticipants($this->mergeLoserSearch, array_filter([$this->mergeSurvivorId]))
                : collect(),

            // Delete tab
            'deleteParticipant' => $this->deleteParticipantId ? Participant::with('household')->find($this->deleteParticipantId) : null,
            'deleteResults' => $this->activeTab === 'delete'
                ? $this->searchParticipants($this->deleteSearch)
                : collect(),
        ]);
    }

    public function rescanDuplicates(): void
    {
        $this->authorizeAction('participants.import', 'You do not have permission to rescan duplicates.');

        $this->rescanRunning = true;
        $this->rescanSummary = null;

        try {
            $summary = $this->duplicateDetectionService->rescanExistingParticipants();
            $this->rescanSummary = $summary;

            session()->flash('message', sprintf(
                'Rescan complete. Evaluated %d participants and logged %d potential duplicates.',
                $summary['evaluated'],
                $summary['created']
            ));
        } catch (\Throwable $e) {
            Log::error('Duplicate rescan failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Rescan failed: '.$e->getMessage());
        } finally {
            $this->rescanRunning = false;
        }
    }

    protected function getPotentialDuplicates()
    {
        $query = PotentialDuplicate::with(['matchedParticipant.household', 'reviewer', 'creator']);

        // Apply filters
        if ($this->statusFilter) {
            if ($this->statusFilter === 'reviewed') {
                $query->reviewed();
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        if ($this->sourceFilter) {
            $query->bySource($this->sourceFilter);
        }

        if ($this->confidenceFilter) {
            switch ($this->confidenceFilter) {
                case 'very_high':
                    $query->where('confidence_score', '>=', 90);
                    break;
                case 'high':
                    $query->whereBetween('confidence_score', [80, 89.99]);
                    break;
                case 'medium':
                    $query->whereBetween('confidence_score', [70, 79.99]);
                    break;
                case 'low':
                    $query->where('confidence_score', '<', 70);
                    break;
            }
        }

        // Search functionality
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('matchedParticipant', function ($participant) {
                    $participant->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%');
                })
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(proposed_data, '$.first_name')) LIKE ?", ['%'.$this->search.'%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(proposed_data, '$.last_name')) LIKE ?", ['%'.$this->search.'%']);
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(10);
    }

    public function showDetail($duplicateId)
    {
        $this->selectedDuplicate = PotentialDuplicate::with(['matchedParticipant.household', 'reviewer', 'creator'])
            ->findOrFail($duplicateId);
        $this->reviewNotes = $this->selectedDuplicate->review_notes ?? '';
        $this->mergeInstructions = $this->selectedDuplicate->merge_instructions ?? [];
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedDuplicate = null;
        $this->reviewNotes = '';
        $this->mergeInstructions = [];
    }

    public function approveMerge($duplicateId)
    {
        $this->processDecision($duplicateId, 'approved_merge');
    }

    public function approveSeparate($duplicateId)
    {
        $this->processDecision($duplicateId, 'approved_separate');
    }

    public function reject($duplicateId)
    {
        $this->processDecision($duplicateId, 'rejected');
    }

    protected function processDecision($duplicateId, $decision)
    {
        try {
            DB::beginTransaction();

            $duplicate = PotentialDuplicate::findOrFail($duplicateId);

            if ($duplicate->status !== 'pending') {
                throw new \Exception('This duplicate has already been reviewed.');
            }

            $duplicate->update([
                'status' => $decision,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $this->reviewNotes,
                'merge_instructions' => $this->mergeInstructions ?: null,
            ]);

            // If approved for merge, perform the actual merge
            if ($decision === 'approved_merge') {
                $this->performMerge($duplicate);
            }

            // If approved as separate, create the new participant
            if ($decision === 'approved_separate') {
                $this->createNewParticipant($duplicate);
            }

            DB::commit();

            $this->closeDetailModal();

            session()->flash('message', 'Duplicate resolution processed successfully.');

            // Log the decision
            Log::info('Duplicate resolution decision made', [
                'duplicate_id' => $duplicateId,
                'decision' => $decision,
                'reviewed_by' => Auth::id(),
                'confidence_score' => $duplicate->confidence_score,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error processing duplicate resolution: '.$e->getMessage());
            Log::error('Duplicate resolution failed', [
                'duplicate_id' => $duplicateId,
                'decision' => $decision,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function performMerge(PotentialDuplicate $duplicate)
    {
        $existingParticipant = $duplicate->matchedParticipant;
        $proposedData = $duplicate->proposed_data;

        // Merge strategy: Update existing participant with non-empty proposed data
        $updateData = [];

        foreach ($proposedData as $field => $value) {
            if (! empty($value) && $field !== 'id') {
                // Only update if existing field is empty or we have specific merge instructions
                if (empty($existingParticipant->$field) ||
                    isset($this->mergeInstructions[$field]) && $this->mergeInstructions[$field] === 'use_new') {
                    $updateData[$field] = $value;
                }
            }
        }

        if (! empty($updateData)) {
            $existingParticipant->update($updateData);
        }

        // Update notes to include merge information
        $notes = $existingParticipant->notes ? json_decode($existingParticipant->notes, true) : [];
        $notes['merged_data'] = [
            'source' => $duplicate->source_type,
            'merged_at' => now()->toISOString(),
            'original_proposed_data' => $proposedData,
            'confidence_score' => $duplicate->confidence_score,
        ];

        $existingParticipant->update(['notes' => json_encode($notes)]);
    }

    protected function createNewParticipant(PotentialDuplicate $duplicate)
    {
        // A `duplicate_rescan` PotentialDuplicate's proposed_data is an EXISTING
        // participant's own data — rescanExistingParticipants() seeds it from
        // rows already in the database, not from a new incoming candidate. For
        // these, "approve as separate" means "these two existing records are
        // different people, stop flagging them"; it must NOT materialise a
        // clone. Creating here is what spawned hundreds of duplicate
        // participants when the review queue was bulk-resolved (every
        // approve-separate cloned an already-persisted row). The callers have
        // already marked the flag resolved, so we simply skip the create.
        //
        // Only sources whose proposed_data is a genuinely new, not-yet-persisted
        // record (manual intake holds, rx_office_import / HMIS import holds)
        // should create a participant here.
        if ($duplicate->source_type === 'duplicate_rescan') {
            return null;
        }

        $proposedData = $duplicate->proposed_data;

        // Remove any ID fields and ensure required fields are present
        unset($proposedData['id']);

        $householdId = $proposedData['household_id'] ?? $duplicate->matchedParticipant->household_id;

        if (! $householdId) {
            throw new \RuntimeException('Unable to determine household for this participant.');
        }

        $proposedData['household_id'] = $householdId;
        $proposedData['is_head_of_household'] = $proposedData['is_head_of_household'] ?? false;

        // Create the new participant
        $participant = Participant::create($proposedData);

        // Update notes to include duplicate resolution information
        $notes = $participant->notes ? json_decode($participant->notes, true) : [];
        $notes['duplicate_resolution'] = [
            'source' => $duplicate->source_type,
            'approved_as_separate' => true,
            'potential_duplicate_id' => $duplicate->matched_participant_id,
            'confidence_score' => $duplicate->confidence_score,
            'resolved_at' => now()->toISOString(),
        ];

        $participant->update(['notes' => json_encode($notes)]);

        return $participant;
    }

    public function bulkApprove($decision)
    {
        try {
            $duplicates = PotentialDuplicate::pending()
                ->when($this->confidenceFilter === 'very_high', function ($q) {
                    $q->where('confidence_score', '>=', 90);
                })
                ->get();

            $count = 0;
            DB::beginTransaction();

            foreach ($duplicates as $duplicate) {
                $duplicate->update([
                    'status' => $decision,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'review_notes' => 'Bulk processed',
                ]);

                if ($decision === 'approved_merge') {
                    $this->performMerge($duplicate);
                } elseif ($decision === 'approved_separate') {
                    $this->createNewParticipant($duplicate);
                }

                $count++;
            }

            DB::commit();
            session()->flash('message', "Bulk processed {$count} duplicate(s) successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error during bulk processing: '.$e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Merge Two Records tab — combine two existing participants
    // ------------------------------------------------------------------

    public function selectMergeSurvivor($id): void
    {
        $this->mergeSurvivorId = (int) $id;
        $this->mergeSurvivorSearch = '';
        $this->afterMergeSelectionChanged();
    }

    public function selectMergeLoser($id): void
    {
        $this->mergeLoserId = (int) $id;
        $this->mergeLoserSearch = '';
        $this->afterMergeSelectionChanged();
    }

    public function clearMergeSurvivor(): void
    {
        $this->mergeSurvivorId = null;
        $this->afterMergeSelectionChanged();
    }

    public function clearMergeLoser(): void
    {
        $this->mergeLoserId = null;
        $this->afterMergeSelectionChanged();
    }

    public function swapMergeDirection(): void
    {
        [$this->mergeSurvivorId, $this->mergeLoserId] = [$this->mergeLoserId, $this->mergeSurvivorId];
        $this->afterMergeSelectionChanged();
    }

    protected function afterMergeSelectionChanged(): void
    {
        $this->mergeFieldChoices = [];
        $this->mergeConflicts = [];

        if (! $this->mergeSurvivorId || ! $this->mergeLoserId || $this->mergeSurvivorId === $this->mergeLoserId) {
            return;
        }

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
                $this->mergeFieldChoices[$field] = 'use_survivor';
            }
        }
    }

    public function confirmMerge(ParticipantMergeService $mergeService): void
    {
        $this->authorizeAction('participants.merge', 'You do not have permission to merge participants.');

        $survivor = Participant::find($this->mergeSurvivorId);
        $loser = Participant::find($this->mergeLoserId);

        if (! $survivor || ! $loser || $survivor->id === $loser->id) {
            session()->flash('error', 'Select two different participants to merge.');

            return;
        }

        try {
            $choices = collect($this->mergeFieldChoices)
                ->filter(fn ($choice) => $choice === 'use_loser')
                ->map(fn () => 'use_loser')
                ->toArray();

            $summary = $mergeService->merge($survivor, $loser, $choices, Auth::id());

            session()->flash('message', sprintf(
                'Merged %s into %s. Reassigned %d enrollment(s), %d housing job(s), %d property(ies), %d communication log(s). Recovery snapshot: %s',
                $loser->full_name,
                $survivor->full_name,
                $summary['program_enrollments_reassigned'] + $summary['service_enrollments_reassigned'],
                $summary['housing_jobs_reassigned'],
                $summary['properties_reassigned'],
                $summary['communication_logs_reassigned'],
                $summary['snapshot_path'],
            ));

            $this->resetMergeForm();
        } catch (\Throwable $e) {
            session()->flash('error', 'Merge failed: '.$e->getMessage());
        }
    }

    public function resetMergeForm(): void
    {
        $this->mergeSurvivorId = null;
        $this->mergeLoserId = null;
        $this->mergeSurvivorSearch = '';
        $this->mergeLoserSearch = '';
        $this->mergeFieldChoices = [];
        $this->mergeConflicts = [];
    }

    // ------------------------------------------------------------------
    // Delete Participant tab — delete a participant and all associated records
    // ------------------------------------------------------------------

    public function selectDeleteParticipant($id, ParticipantDeletionService $deletionService): void
    {
        $participant = Participant::find($id);
        if (! $participant) {
            session()->flash('error', 'Participant not found.');

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
        $this->deleteSearch = '';
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
            $this->resetDeleteForm();

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
            $summary = $deletionService->delete($participant, $options, Auth::id());

            session()->flash('message', sprintf(
                'Deleted %s and all associated records. Recovery snapshot: %s',
                $summary['participant_name'],
                $summary['snapshot_path'],
            ));

            $this->resetDeleteForm();
        } catch (HouseholdDecisionRequiredException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Delete failed: '.$e->getMessage());
        }
    }

    public function resetDeleteForm(): void
    {
        $this->deleteParticipantId = null;
        $this->deleteParticipantName = null;
        $this->deletePreview = [];
        $this->deleteNeedsDecision = false;
        $this->deleteCoMembers = [];
        $this->deleteDecision = '';
        $this->promoteToHeadId = null;
        $this->deleteConfirmText = '';
        $this->deleteSearch = '';
    }

    /**
     * Load a participant with the relations needed for the side-by-side merge comparison.
     */
    protected function loadMergeRecord($id)
    {
        return Participant::with([
            'household.headOfHousehold',
            'programEnrollments.program',
            'serviceEnrollments.service',
        ])
            ->withCount(['properties', 'housingJobs'])
            ->find($id);
    }

    /**
     * Search participants by name / household code for the merge & delete pickers.
     */
    protected function searchParticipants(string $term, array $excludeIds = [])
    {
        $term = trim($term);
        if (strlen($term) < 2) {
            return collect();
        }

        return Participant::with('household')
            ->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('household', fn ($h) => $h->where('household_code', 'like', "%{$term}%"));
            })
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSourceFilter()
    {
        $this->resetPage();
    }

    public function updatedConfidenceFilter()
    {
        $this->resetPage();
    }

    public function getSourceTypes()
    {
        return [
            'manual' => 'Manual Entry',
            'hmis_import' => 'HMIS Import',
            'rx_office_import' => 'RX Office Import',
            'general_import' => 'General Import',
        ];
    }

    public function getStatusTypes()
    {
        return [
            'pending' => 'Pending Review',
            'approved_merge' => 'Approved Merge',
            'approved_separate' => 'Approved Separate',
            'rejected' => 'Rejected',
            'reviewed' => 'All Reviewed',
        ];
    }

    public function getConfidenceFilters()
    {
        return [
            'very_high' => 'Very High (90%+)',
            'high' => 'High (80-89%)',
            'medium' => 'Medium (70-79%)',
            'low' => 'Low (<70%)',
        ];
    }
}
