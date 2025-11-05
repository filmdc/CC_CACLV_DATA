<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JobDocumentChecklist extends Model
{
    protected $fillable = [
        'housing_job_id',
        'document_name',
        'document_description',
        'is_required',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_at',
        'uploaded_by',
        'verified_at',
        'verified_by',
        'status',
        'rejection_reason',
        'sort_order',
        'created_by',
        'requires_approval',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'requires_approval' => 'boolean',
    ];

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'uploaded' => 'blue',
            'verified' => 'green',
            'rejected' => 'red',
            default => 'gray',
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

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        return false;
    }

    public function isUploaded(): bool
    {
        return in_array($this->status, ['uploaded', 'verified']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the document requires approval.
     */
    public function requiresApproval(): bool
    {
        return (bool) $this->requires_approval;
    }

    /**
     * Approve/verify the document.
     * Alias for verify() to maintain consistency with other document models.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $approver->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Verify the document (original method).
     */
    public function verify(User $verifier): void
    {
        $this->approve($verifier);
    }

    /**
     * Reject the document with a reason.
     */
    public function reject(User $verifier, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if document is approved (alias for isVerified).
     */
    public function isApproved(): bool
    {
        return $this->isVerified();
    }
}
