<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotentialDuplicate extends Model
{
    protected $fillable = [
        'source_type',
        'source_identifier',
        'proposed_data',
        'matched_participant_id',
        'confidence_score',
        'matching_criteria',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'merge_instructions',
        'created_by'
    ];

    protected $casts = [
        'proposed_data' => 'array',
        'matching_criteria' => 'array',
        'merge_instructions' => 'array',
        'reviewed_at' => 'datetime',
        'confidence_score' => 'decimal:2'
    ];

    public function matchedParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'matched_participant_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewed($query)
    {
        return $query->whereIn('status', ['approved_merge', 'approved_separate', 'rejected']);
    }

    public function scopeBySource($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopeHighConfidence($query, $threshold = 80.00)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReviewed(): bool
    {
        return in_array($this->status, ['approved_merge', 'approved_separate', 'rejected']);
    }

    public function isApprovedMerge(): bool
    {
        return $this->status === 'approved_merge';
    }

    public function isApprovedSeparate(): bool
    {
        return $this->status === 'approved_separate';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getConfidenceLevel(): string
    {
        return match (true) {
            $this->confidence_score >= 90 => 'Very High',
            $this->confidence_score >= 80 => 'High',
            $this->confidence_score >= 70 => 'Medium',
            $this->confidence_score >= 60 => 'Low',
            default => 'Very Low'
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved_merge' => 'bg-green-100 text-green-800',
            'approved_separate' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getProposedName(): string
    {
        $data = $this->proposed_data;
        $parts = array_filter([
            $data['first_name'] ?? '',
            $data['middle_name'] ?? '',
            $data['last_name'] ?? ''
        ]);
        
        return implode(' ', $parts) ?: 'Unknown Name';
    }
}