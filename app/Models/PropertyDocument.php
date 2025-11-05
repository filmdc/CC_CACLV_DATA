<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyDocument extends Model
{
    protected $fillable = [
        'property_id',
        'document_name',
        'document_description',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'document_date',
        'expiration_date',
        'uploaded_by',
        'approved_at',
        'approved_by',
        'approval_status',
        'rejection_reason',
        'requires_approval',
    ];

    protected $casts = [
        'document_date' => 'date',
        'expiration_date' => 'date',
        'file_size' => 'integer',
        'approved_at' => 'datetime',
        'requires_approval' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who approved this document.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getDocumentTypeDisplayAttribute(): string
    {
        return match ($this->document_type) {
            'deed' => 'Deed',
            'title' => 'Title',
            'insurance' => 'Insurance',
            'inspection' => 'Inspection',
            'assessment' => 'Assessment',
            'photo' => 'Photo',
            'permit' => 'Permit',
            'contract' => 'Contract',
            'other' => 'Other',
            default => $this->document_type,
        };
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiration_date 
            && $this->expiration_date->isFuture() 
            && $this->expiration_date->diffInDays(now()) <= 30;
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        return false;
    }

    public function getFileUrl(): ?string
    {
        if ($this->file_path) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    /**
     * Check if the document is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if the document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Check if the document is pending approval.
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending' || ($this->requires_approval && !$this->approval_status);
    }

    /**
     * Check if the document requires approval.
     */
    public function requiresApproval(): bool
    {
        return (bool) $this->requires_approval;
    }

    /**
     * Approve the document.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the document.
     */
    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Reset the approval status.
     */
    public function resetApprovalStatus(): void
    {
        $this->update([
            'approval_status' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->approval_status) {
            'approved' => 'green',
            'rejected' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }
}
