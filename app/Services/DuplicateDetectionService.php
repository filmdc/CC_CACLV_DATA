<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\PotentialDuplicate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DuplicateDetectionService
{
    protected array $defaultWeights = [
        'first_name_exact' => 25,
        'last_name_exact' => 25,
        'date_of_birth_exact' => 30,
        'first_name_fuzzy' => 15,
        'last_name_fuzzy' => 15,
        'email_exact' => 20,
        'phone_exact' => 15,
        'middle_name_exact' => 10,
        'ssn_exact' => 40,
        'external_id_exact' => 35, // case_id, client_id, etc.
    ];

    protected float $minimumConfidenceThreshold = 60.0;
    protected float $autoMergeThreshold = 95.0;

    /**
     * Check for head of household duplicates using simplified criteria
     * Focuses on first name, last name, and date of birth matching
     */
    public function checkForHeadOfHouseholdDuplicates(array $proposedData, string $sourceType = 'manual'): Collection
    {
        $potentialDuplicates = collect();

        // Only check head of household participants
        $candidates = Participant::where('is_head_of_household', true)
            ->where(function ($query) use ($proposedData) {
                // Strategy 1: Exact name match
                if (!empty($proposedData['first_name']) && !empty($proposedData['last_name'])) {
                    $query->where('first_name', 'LIKE', '%' . trim($proposedData['first_name']) . '%')
                          ->where('last_name', 'LIKE', '%' . trim($proposedData['last_name']) . '%');
                }
            })
            ->orWhere(function ($query) use ($proposedData) {
                // Strategy 2: Date of birth match (if provided)
                if (!empty($proposedData['date_of_birth'])) {
                    $query->where('date_of_birth', $proposedData['date_of_birth'])
                          ->where('is_head_of_household', true);
                }
            })
            ->with('household')
            ->get();

        foreach ($candidates as $candidate) {
            $score = $this->calculateHeadOfHouseholdScore($proposedData, $candidate);
            
            if ($score >= 50.0) { // Lower threshold for head of household check
                $matchingCriteria = $this->getHeadOfHouseholdMatchingCriteria($proposedData, $candidate);
                
                $duplicate = [
                    'participant' => $candidate,
                    'confidence_score' => $score,
                    'matching_criteria' => $matchingCriteria,
                    'proposed_data' => $proposedData,
                    'source_type' => $sourceType,
                    'source_identifier' => session()->getId(),
                ];

                $potentialDuplicates->push($duplicate);
            }
        }

        return $potentialDuplicates->sortByDesc('confidence_score');
    }

    /**
     * Calculate simplified confidence score for head of household matching
     */
    protected function calculateHeadOfHouseholdScore(array $proposedData, Participant $candidate): float
    {
        $totalScore = 0;

        // First name match (40 points)
        if (!empty($proposedData['first_name']) && !empty($candidate->first_name)) {
            if (strtolower(trim($proposedData['first_name'])) === strtolower(trim($candidate->first_name))) {
                $totalScore += 40;
            } else {
                // Fuzzy match
                $similarity = 0;
                similar_text(strtolower($proposedData['first_name']), strtolower($candidate->first_name), $similarity);
                if ($similarity >= 80) {
                    $totalScore += 25 * ($similarity / 100);
                }
            }
        }

        // Last name match (40 points)
        if (!empty($proposedData['last_name']) && !empty($candidate->last_name)) {
            if (strtolower(trim($proposedData['last_name'])) === strtolower(trim($candidate->last_name))) {
                $totalScore += 40;
            } else {
                // Fuzzy match
                $similarity = 0;
                similar_text(strtolower($proposedData['last_name']), strtolower($candidate->last_name), $similarity);
                if ($similarity >= 80) {
                    $totalScore += 25 * ($similarity / 100);
                }
            }
        }

        // Date of birth match (20 points)
        if (!empty($proposedData['date_of_birth']) && !empty($candidate->date_of_birth)) {
            if ($proposedData['date_of_birth'] === $candidate->date_of_birth->format('Y-m-d')) {
                $totalScore += 20;
            }
        }

        return $totalScore;
    }

    /**
     * Get matching criteria for head of household duplicates
     */
    protected function getHeadOfHouseholdMatchingCriteria(array $proposedData, Participant $candidate): array
    {
        $criteria = [];

        if (!empty($proposedData['first_name']) && !empty($candidate->first_name)) {
            if (strtolower(trim($proposedData['first_name'])) === strtolower(trim($candidate->first_name))) {
                $criteria[] = 'Exact first name match';
            } else {
                $similarity = 0;
                similar_text(strtolower($proposedData['first_name']), strtolower($candidate->first_name), $similarity);
                if ($similarity >= 80) {
                    $criteria[] = 'Similar first name (' . round($similarity) . '% match)';
                }
            }
        }

        if (!empty($proposedData['last_name']) && !empty($candidate->last_name)) {
            if (strtolower(trim($proposedData['last_name'])) === strtolower(trim($candidate->last_name))) {
                $criteria[] = 'Exact last name match';
            } else {
                $similarity = 0;
                similar_text(strtolower($proposedData['last_name']), strtolower($candidate->last_name), $similarity);
                if ($similarity >= 80) {
                    $criteria[] = 'Similar last name (' . round($similarity) . '% match)';
                }
            }
        }

        if (!empty($proposedData['date_of_birth']) && !empty($candidate->date_of_birth)) {
            if ($proposedData['date_of_birth'] === $candidate->date_of_birth->format('Y-m-d')) {
                $criteria[] = 'Exact date of birth match';
            }
        }

        return $criteria;
    }

    /**
     * Check for potential duplicates of proposed participant data
     */
    public function checkForDuplicates(array $proposedData, string $sourceType, ?string $sourceIdentifier = null, ?int $excludeParticipantId = null): Collection
    {
        $potentialDuplicates = collect();

        // Get candidates using multiple search strategies
        $candidates = $this->getCandidates($proposedData);

        if ($excludeParticipantId) {
            $candidates = $candidates->reject(fn ($candidate) => $candidate->id === $excludeParticipantId);
        }

        foreach ($candidates as $candidate) {
            $score = $this->calculateConfidenceScore($proposedData, $candidate);
            
            if ($score >= $this->minimumConfidenceThreshold) {
                $matchingCriteria = $this->getMatchingCriteria($proposedData, $candidate);
                
                $duplicate = [
                    'participant' => $candidate,
                    'confidence_score' => $score,
                    'matching_criteria' => $matchingCriteria,
                    'proposed_data' => $proposedData,
                    'source_type' => $sourceType,
                    'source_identifier' => $sourceIdentifier,
                ];

                $potentialDuplicates->push($duplicate);
            }
        }

        // Sort by confidence score (highest first)
        return $potentialDuplicates->sortByDesc('confidence_score');
    }

    /**
     * Store potential duplicates for review
     */
    public function storePotentialDuplicates(Collection $duplicates, int $createdBy): Collection
    {
        $storedDuplicates = collect();

        foreach ($duplicates as $duplicate) {
            $existingPending = PotentialDuplicate::where('matched_participant_id', $duplicate['participant']->id)
                ->where('status', 'pending')
                ->get();

            $alreadyExists = $existingPending->first(function ($existing) use ($duplicate) {
                $existingData = $existing->proposed_data ?? [];

                return $existing->source_type === $duplicate['source_type']
                    && data_get($existingData, 'first_name') === ($duplicate['proposed_data']['first_name'] ?? null)
                    && data_get($existingData, 'last_name') === ($duplicate['proposed_data']['last_name'] ?? null)
                    && data_get($existingData, 'date_of_birth') === ($duplicate['proposed_data']['date_of_birth'] ?? null);
            });

            if ($alreadyExists) {
                continue;
            }

            $stored = PotentialDuplicate::create([
                'source_type' => $duplicate['source_type'],
                'source_identifier' => $duplicate['source_identifier'],
                'proposed_data' => $duplicate['proposed_data'],
                'matched_participant_id' => $duplicate['participant']->id,
                'confidence_score' => $duplicate['confidence_score'],
                'matching_criteria' => $duplicate['matching_criteria'],
                'created_by' => $createdBy,
            ]);

            $storedDuplicates->push($stored);
        }

        return $storedDuplicates;
    }

    public function rescanExistingParticipants(int $chunkSize = 250): array
    {
        $evaluated = 0;
        $created = 0;

        Participant::chunkById($chunkSize, function ($participants) use (&$evaluated, &$created) {
            foreach ($participants as $participant) {
                $evaluated++;

                $proposedData = [
                    'first_name' => $participant->first_name,
                    'last_name' => $participant->last_name,
                    'date_of_birth' => optional($participant->date_of_birth)->format('Y-m-d'),
                    'email' => $participant->email,
                    'telephone' => $participant->telephone,
                ];

                $duplicates = $this->checkForDuplicates(
                    $proposedData,
                    'duplicate_rescan',
                    (string) $participant->id,
                    $participant->id
                );

                if ($duplicates->isNotEmpty()) {
                    $stored = $this->storePotentialDuplicates($duplicates, Auth::id() ?? 1);
                    $created += $stored->count();
                }
            }
        });

        return [
            'evaluated' => $evaluated,
            'created' => $created,
        ];
    }

    /**
     * Check if proposed data should be auto-processed or needs review
     */
    public function shouldRequireReview(Collection $duplicates): bool
    {
        // Require review if any duplicate has confidence above minimum threshold
        return $duplicates->isNotEmpty();
    }

    /**
     * Process automatic merges for very high confidence matches
     */
    public function processAutoMerges(Collection $duplicates): array
    {
        $autoMerged = [];
        $requiresReview = [];

        foreach ($duplicates as $duplicate) {
            if ($duplicate['confidence_score'] >= $this->autoMergeThreshold) {
                // Auto-merge very high confidence matches
                $mergeResult = $this->performMerge($duplicate);
                $autoMerged[] = $mergeResult;
            } else {
                $requiresReview[] = $duplicate;
            }
        }

        return [
            'auto_merged' => $autoMerged,
            'requires_review' => $requiresReview,
        ];
    }

    /**
     * Get candidate participants using multiple search strategies
     */
    protected function getCandidates(array $proposedData): Collection
    {
        $candidates = collect();

        // Strategy 1: Exact name + DOB match
        if (!empty($proposedData['first_name']) && !empty($proposedData['last_name'])) {
            $nameMatches = Participant::where('first_name', 'LIKE', '%' . trim($proposedData['first_name']) . '%')
                ->where('last_name', 'LIKE', '%' . trim($proposedData['last_name']) . '%')
                ->get();
            $candidates = $candidates->merge($nameMatches);
        }

        // Strategy 2: Date of birth match (if provided)
        if (!empty($proposedData['date_of_birth'])) {
            $dobMatches = Participant::where('date_of_birth', $proposedData['date_of_birth'])->get();
            $candidates = $candidates->merge($dobMatches);
        }

        // Strategy 3: Email match (if provided)
        if (!empty($proposedData['email'])) {
            $emailMatches = Participant::where('email', $proposedData['email'])->get();
            $candidates = $candidates->merge($emailMatches);
        }

        // Strategy 4: Phone match (if provided)
        if (!empty($proposedData['telephone'])) {
            $phoneMatches = Participant::where('telephone', $proposedData['telephone'])->get();
            $candidates = $candidates->merge($phoneMatches);
        }

        // Strategy 5: External ID matches (case_id, client_id in notes)
        if (!empty($proposedData['case_id'])) {
            $caseIdMatches = Participant::where('notes', 'LIKE', '%case_id%' . $proposedData['case_id'] . '%')->get();
            $candidates = $candidates->merge($caseIdMatches);
        }

        if (!empty($proposedData['client_id'])) {
            $clientIdMatches = Participant::where('notes', 'LIKE', '%ClientID%' . $proposedData['client_id'] . '%')->get();
            $candidates = $candidates->merge($clientIdMatches);
        }

        // Remove duplicates and return unique candidates
        return $candidates->unique('id');
    }

    /**
     * Calculate confidence score between proposed data and existing participant
     */
    protected function calculateConfidenceScore(array $proposedData, Participant $candidate): float
    {
        $totalScore = 0;
        $maxPossibleScore = 0;

        // First name comparison
        if (!empty($proposedData['first_name']) && !empty($candidate->first_name)) {
            $maxPossibleScore += $this->defaultWeights['first_name_exact'];
            
            if (strtolower(trim($proposedData['first_name'])) === strtolower(trim($candidate->first_name))) {
                $totalScore += $this->defaultWeights['first_name_exact'];
            } else {
                // Fuzzy match using similar_text
                $similarity = 0;
                similar_text(strtolower($proposedData['first_name']), strtolower($candidate->first_name), $similarity);
                if ($similarity >= 80) {
                    $totalScore += $this->defaultWeights['first_name_fuzzy'] * ($similarity / 100);
                }
            }
        }

        // Last name comparison
        if (!empty($proposedData['last_name']) && !empty($candidate->last_name)) {
            $maxPossibleScore += $this->defaultWeights['last_name_exact'];
            
            if (strtolower(trim($proposedData['last_name'])) === strtolower(trim($candidate->last_name))) {
                $totalScore += $this->defaultWeights['last_name_exact'];
            } else {
                // Fuzzy match
                $similarity = 0;
                similar_text(strtolower($proposedData['last_name']), strtolower($candidate->last_name), $similarity);
                if ($similarity >= 80) {
                    $totalScore += $this->defaultWeights['last_name_fuzzy'] * ($similarity / 100);
                }
            }
        }

        // Date of birth comparison
        if (!empty($proposedData['date_of_birth']) && !empty($candidate->date_of_birth)) {
            $maxPossibleScore += $this->defaultWeights['date_of_birth_exact'];
            
            $proposedDate = is_string($proposedData['date_of_birth']) 
                ? Carbon::parse($proposedData['date_of_birth'])->format('Y-m-d')
                : $proposedData['date_of_birth'];
            
            $candidateDate = $candidate->date_of_birth instanceof Carbon 
                ? $candidate->date_of_birth->format('Y-m-d')
                : $candidate->date_of_birth;

            if ($proposedDate === $candidateDate) {
                $totalScore += $this->defaultWeights['date_of_birth_exact'];
            }
        }

        // Middle name comparison
        if (!empty($proposedData['middle_name']) && !empty($candidate->middle_name)) {
            $maxPossibleScore += $this->defaultWeights['middle_name_exact'];
            
            if (strtolower(trim($proposedData['middle_name'])) === strtolower(trim($candidate->middle_name))) {
                $totalScore += $this->defaultWeights['middle_name_exact'];
            }
        }

        // Email comparison
        if (!empty($proposedData['email']) && !empty($candidate->email)) {
            $maxPossibleScore += $this->defaultWeights['email_exact'];
            
            if (strtolower(trim($proposedData['email'])) === strtolower(trim($candidate->email))) {
                $totalScore += $this->defaultWeights['email_exact'];
            }
        }

        // Phone comparison
        if (!empty($proposedData['telephone']) && !empty($candidate->telephone)) {
            $maxPossibleScore += $this->defaultWeights['phone_exact'];
            
            $proposedPhone = preg_replace('/\D/', '', $proposedData['telephone']);
            $candidatePhone = preg_replace('/\D/', '', $candidate->telephone);
            
            if ($proposedPhone === $candidatePhone && strlen($proposedPhone) >= 10) {
                $totalScore += $this->defaultWeights['phone_exact'];
            }
        }

        // External ID comparison (case_id, client_id, etc.)
        $externalIdMatch = $this->checkExternalIdMatch($proposedData, $candidate);
        if ($externalIdMatch) {
            $maxPossibleScore += $this->defaultWeights['external_id_exact'];
            $totalScore += $this->defaultWeights['external_id_exact'];
        }

        // Calculate percentage, ensuring we don't divide by zero
        if ($maxPossibleScore === 0) {
            return 0;
        }

        return min(100, ($totalScore / $maxPossibleScore) * 100);
    }

    /**
     * Check for external ID matches in notes field
     */
    protected function checkExternalIdMatch(array $proposedData, Participant $candidate): bool
    {
        if (empty($candidate->notes)) {
            return false;
        }

        $candidateNotes = is_string($candidate->notes) ? json_decode($candidate->notes, true) : $candidate->notes;
        if (!is_array($candidateNotes)) {
            return false;
        }

        // Check case_id
        if (!empty($proposedData['case_id']) && !empty($candidateNotes['case_id'])) {
            if ($proposedData['case_id'] === $candidateNotes['case_id']) {
                return true;
            }
        }

        // Check client_id or ClientID
        if (!empty($proposedData['client_id'])) {
            if (!empty($candidateNotes['client_id']) && $proposedData['client_id'] === $candidateNotes['client_id']) {
                return true;
            }
            if (!empty($candidateNotes['ClientID']) && $proposedData['client_id'] === $candidateNotes['ClientID']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detailed matching criteria information
     */
    protected function getMatchingCriteria(array $proposedData, Participant $candidate): array
    {
        $criteria = [];

        // Name matches
        if (!empty($proposedData['first_name']) && !empty($candidate->first_name)) {
            $exact = strtolower(trim($proposedData['first_name'])) === strtolower(trim($candidate->first_name));
            $criteria['first_name'] = [
                'proposed' => $proposedData['first_name'],
                'existing' => $candidate->first_name,
                'match_type' => $exact ? 'exact' : 'fuzzy',
                'weight' => $exact ? $this->defaultWeights['first_name_exact'] : $this->defaultWeights['first_name_fuzzy']
            ];
        }

        if (!empty($proposedData['last_name']) && !empty($candidate->last_name)) {
            $exact = strtolower(trim($proposedData['last_name'])) === strtolower(trim($candidate->last_name));
            $criteria['last_name'] = [
                'proposed' => $proposedData['last_name'],
                'existing' => $candidate->last_name,
                'match_type' => $exact ? 'exact' : 'fuzzy',
                'weight' => $exact ? $this->defaultWeights['last_name_exact'] : $this->defaultWeights['last_name_fuzzy']
            ];
        }

        // Date of birth
        if (!empty($proposedData['date_of_birth']) && !empty($candidate->date_of_birth)) {
            $proposedDate = is_string($proposedData['date_of_birth']) 
                ? Carbon::parse($proposedData['date_of_birth'])->format('Y-m-d')
                : $proposedData['date_of_birth'];
            
            $candidateDate = $candidate->date_of_birth instanceof Carbon 
                ? $candidate->date_of_birth->format('Y-m-d')
                : $candidate->date_of_birth;

            if ($proposedDate === $candidateDate) {
                $criteria['date_of_birth'] = [
                    'proposed' => $proposedDate,
                    'existing' => $candidateDate,
                    'match_type' => 'exact',
                    'weight' => $this->defaultWeights['date_of_birth_exact']
                ];
            }
        }

        // External IDs
        if ($this->checkExternalIdMatch($proposedData, $candidate)) {
            $criteria['external_id'] = [
                'match_type' => 'exact',
                'weight' => $this->defaultWeights['external_id_exact']
            ];
        }

        return $criteria;
    }

    /**
     * Perform an automatic merge of duplicate data
     */
    protected function performMerge(array $duplicate): array
    {
        // This would implement the actual merge logic
        // For now, just return the result structure
        return [
            'participant_id' => $duplicate['participant']->id,
            'merged_data' => $duplicate['proposed_data'],
            'confidence_score' => $duplicate['confidence_score'],
            'merge_timestamp' => now(),
        ];
    }

    /**
     * Get configuration for minimum confidence threshold
     */
    public function getMinimumConfidenceThreshold(): float
    {
        return $this->minimumConfidenceThreshold;
    }

    /**
     * Set minimum confidence threshold
     */
    public function setMinimumConfidenceThreshold(float $threshold): void
    {
        $this->minimumConfidenceThreshold = max(0, min(100, $threshold));
    }

    /**
     * Check if there are pending duplicates for review
     */
    public function hasPendingDuplicates(): bool
    {
        return PotentialDuplicate::pending()->exists();
    }

    /**
     * Get pending duplicates count
     */
    public function getPendingDuplicatesCount(): int
    {
        return PotentialDuplicate::pending()->count();
    }

    /**
     * Check for household-level duplicates
     * Helps prevent creating new households when one already exists
     */
    public function checkForHouseholdDuplicates(array $proposedHouseholdData): \Illuminate\Support\Collection
    {
        $potentialDuplicates = collect();

        // Strategy 1: Match by address components
        if (!empty($proposedHouseholdData['street_address']) &&
            !empty($proposedHouseholdData['zip_code'])) {

            $addressMatches = \App\Models\Household::whereHas('addresses', function($query) use ($proposedHouseholdData) {
                $query->where('is_current', true)
                    ->where('address', 'LIKE', '%' . trim($proposedHouseholdData['street_address']) . '%')
                    ->where('zip_code', trim($proposedHouseholdData['zip_code']));
            })->with('participants')->get();

            foreach ($addressMatches as $household) {
                $score = $this->calculateHouseholdMatchScore($proposedHouseholdData, $household);

                if ($score >= 50.0) {
                    $potentialDuplicates->push([
                        'household' => $household,
                        'confidence_score' => $score,
                        'matching_criteria' => $this->getHouseholdMatchingCriteria($proposedHouseholdData, $household),
                    ]);
                }
            }
        }

        // Strategy 2: Match by household_code (if provided)
        if (!empty($proposedHouseholdData['household_code'])) {
            $codeMatch = \App\Models\Household::where('household_code', $proposedHouseholdData['household_code'])
                ->with('participants')
                ->first();

            if ($codeMatch && !$potentialDuplicates->contains('household.id', $codeMatch->id)) {
                $potentialDuplicates->push([
                    'household' => $codeMatch,
                    'confidence_score' => 100.0,
                    'matching_criteria' => ['Exact household_code match'],
                ]);
            }
        }

        return $potentialDuplicates->sortByDesc('confidence_score');
    }

    /**
     * Calculate confidence score for household matching
     */
    protected function calculateHouseholdMatchScore(array $proposedData, \App\Models\Household $candidate): float
    {
        $score = 0;

        // Address match (40 points)
        if (!empty($proposedData['street_address']) && !empty($candidate->currentAddress->address)) {
            $similarity = 0;
            similar_text(
                strtolower(trim($proposedData['street_address'])),
                strtolower(trim($candidate->currentAddress->address)),
                $similarity
            );
            if ($similarity >= 80) {
                $score += 40 * ($similarity / 100);
            }
        }

        // Zip code match (20 points)
        if (!empty($proposedData['zip_code']) && !empty($candidate->currentAddress->zip_code)) {
            if (trim($proposedData['zip_code']) === trim($candidate->currentAddress->zip_code)) {
                $score += 20;
            }
        }

        // City match (10 points)
        if (!empty($proposedData['city']) && !empty($candidate->currentAddress->city)) {
            if (strtolower(trim($proposedData['city'])) === strtolower(trim($candidate->currentAddress->city))) {
                $score += 10;
            }
        }

        // Household size match (15 points)
        if (!empty($proposedData['household_size']) && !empty($candidate->household_size)) {
            if ($proposedData['household_size'] == $candidate->household_size) {
                $score += 15;
            } elseif (abs($proposedData['household_size'] - $candidate->household_size) <= 1) {
                $score += 7.5; // Close match
            }
        }

        // Household type match (15 points)
        if (!empty($proposedData['household_type']) && !empty($candidate->household_type)) {
            if ($proposedData['household_type'] === $candidate->household_type) {
                $score += 15;
            }
        }

        return $score;
    }

    /**
     * Get matching criteria for household duplicates
     */
    protected function getHouseholdMatchingCriteria(array $proposedData, \App\Models\Household $candidate): array
    {
        $criteria = [];

        if (!empty($proposedData['street_address']) && !empty($candidate->currentAddress->address)) {
            $similarity = 0;
            similar_text(
                strtolower(trim($proposedData['street_address'])),
                strtolower(trim($candidate->currentAddress->address)),
                $similarity
            );
            if ($similarity >= 80) {
                $criteria[] = 'Address match (' . round($similarity) . '%)';
            }
        }

        if (!empty($proposedData['zip_code']) && !empty($candidate->currentAddress->zip_code)) {
            if (trim($proposedData['zip_code']) === trim($candidate->currentAddress->zip_code)) {
                $criteria[] = 'Exact zip code match';
            }
        }

        if (!empty($proposedData['household_size']) && !empty($candidate->household_size)) {
            if ($proposedData['household_size'] == $candidate->household_size) {
                $criteria[] = 'Same household size (' . $candidate->household_size . ')';
            }
        }

        if (!empty($proposedData['household_type']) && !empty($candidate->household_type)) {
            if ($proposedData['household_type'] === $candidate->household_type) {
                $criteria[] = 'Same household type';
            }
        }

        return $criteria;
    }
}
