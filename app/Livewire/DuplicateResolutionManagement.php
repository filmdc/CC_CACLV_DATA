<?php

namespace App\Livewire;

use App\Models\PotentialDuplicate;
use App\Models\Participant;
use App\Services\DuplicateDetectionService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateResolutionManagement extends Component
{
    use WithPagination;

    // UI State
    public $showDetailModal = false;
    public $selectedDuplicate = null;
    public $reviewNotes = '';
    public $mergeInstructions = [];
    
    // Filters
    public $statusFilter = 'pending';
    public $sourceFilter = '';
    public $confidenceFilter = '';
    public $search = '';

    // Sorting
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $duplicateDetectionService;

    public function boot(DuplicateDetectionService $duplicateDetectionService)
    {
        $this->duplicateDetectionService = $duplicateDetectionService;
    }

    public function mount()
    {
        // Initialize component
    }

    public function render()
    {
        $duplicates = $this->getPotentialDuplicates();
        
        return view('livewire.duplicate-resolution-management', [
            'duplicates' => $duplicates,
            'pendingCount' => PotentialDuplicate::pending()->count(),
            'totalCount' => PotentialDuplicate::count(),
        ]);
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
                    $participant->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(proposed_data, '$.first_name')) LIKE ?", ['%' . $this->search . '%'])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(proposed_data, '$.last_name')) LIKE ?", ['%' . $this->search . '%']);
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
            session()->flash('error', 'Error processing duplicate resolution: ' . $e->getMessage());
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
            if (!empty($value) && $field !== 'id') {
                // Only update if existing field is empty or we have specific merge instructions
                if (empty($existingParticipant->$field) || 
                    isset($this->mergeInstructions[$field]) && $this->mergeInstructions[$field] === 'use_new') {
                    $updateData[$field] = $value;
                }
            }
        }

        if (!empty($updateData)) {
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
        $proposedData = $duplicate->proposed_data;
        
        // Remove any ID fields and ensure required fields are present
        unset($proposedData['id']);
        
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
            session()->flash('error', 'Error during bulk processing: ' . $e->getMessage());
        }
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