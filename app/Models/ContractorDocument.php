<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContractorDocument extends Model
{
    protected $fillable = [
        'contractor_id',
        'document_name',
        'document_description',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'document_date',
        'expiration_date',
        'is_current',
        'uploaded_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'expiration_date' => 'date',
        'file_size' => 'integer',
        'is_current' => 'boolean',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDocumentTypeDisplayAttribute(): string
    {
        return match ($this->document_type) {
            'license' => 'License',
            'insurance' => 'Insurance',
            'w9' => 'W-9 Form',
            'agreement' => 'Agreement',
            'certification' => 'Certification',
            'reference' => 'Reference',
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

    public function markAsExpired(): void
    {
        $this->update(['is_current' => false]);
    }
}
