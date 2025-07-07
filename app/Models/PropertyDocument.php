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
    ];

    protected $casts = [
        'document_date' => 'date',
        'expiration_date' => 'date',
        'file_size' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
}
