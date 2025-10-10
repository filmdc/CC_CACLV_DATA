<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * Get the authenticated URL for this media item
     * Path format: storage/authenticated/{media_id}/{filename}
     */
    public function getAuthenticatedUrl(): string
    {
        // Get the relative path from storage/app/public/
        // Spatie stores as: {model_id}/{filename}
        $relativePath = str_replace(storage_path('app/public/'), '', $this->getPath());

        return route('storage.authenticated', [
            'path' => ltrim($relativePath, '/')
        ]);
    }

    /**
     * Get the download URL for this media item
     */
    public function getDownloadUrl(): string
    {
        $relativePath = str_replace(storage_path('app/public/'), '', $this->getPath());

        return route('storage.authenticated', [
            'path' => ltrim($relativePath, '/')
        ]) . '?download=1';
    }
    
    /**
     * Get the file type icon class based on mime type
     */
    public function getFileTypeIcon(): string
    {
        $mimeType = $this->mime_type;
        
        switch (true) {
            case str_contains($mimeType, 'pdf'):
                return 'text-red-500';
            case str_contains($mimeType, 'word') || str_contains($mimeType, 'document'):
                return 'text-blue-500';
            case str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet'):
                return 'text-green-500';
            case str_contains($mimeType, 'image'):
                return 'text-purple-500';
            default:
                return 'text-gray-500';
        }
    }
    
    /**
     * Get the file type label based on mime type
     */
    public function getFileTypeLabel(): string
    {
        $mimeType = $this->mime_type;
        
        switch (true) {
            case str_contains($mimeType, 'pdf'):
                return 'PDF';
            case str_contains($mimeType, 'word') || str_contains($mimeType, 'document'):
                return 'Word Document';
            case str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet'):
                return 'Excel Spreadsheet';
            case str_contains($mimeType, 'image/jpeg'):
                return 'JPEG Image';
            case str_contains($mimeType, 'image/png'):
                return 'PNG Image';
            case str_contains($mimeType, 'image'):
                return 'Image';
            default:
                return 'Document';
        }
    }
}