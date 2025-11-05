<?php

namespace App\Traits;

use App\Services\MediaValidationService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Trait HasStandardMediaCollections
 *
 * Provides reusable methods for registering standard media collections
 * and conversions across all models that handle document uploads.
 *
 * Usage:
 * 1. Add trait to your model that implements HasMedia
 * 2. Call registerStandardDocumentCollection() in registerMediaCollections()
 * 3. Optionally call registerStandardConversions() in registerMediaConversions()
 *
 * Example:
 * ```php
 * class Contract extends Model implements HasMedia
 * {
 *     use InteractsWithMedia, HasStandardMediaCollections;
 *
 *     public function registerMediaCollections(): void
 *     {
 *         $this->registerStandardDocumentCollection();
 *     }
 *
 *     public function registerMediaConversions(?Media $media = null): void
 *     {
 *         $this->registerStandardConversions($media);
 *     }
 * }
 * ```
 */
trait HasStandardMediaCollections
{
    /**
     * Register standard 'documents' collection with security best practices
     *
     * This method configures:
     * - Collection name: 'documents'
     * - Disk: 'private_media' (secure, authenticated access)
     * - MIME types: Standard document types from MediaValidationService
     * - Max file size: 100MB
     *
     * @param string|null $collectionName Override default 'documents' name
     * @param array|null $mimeTypes Override default MIME types
     * @param int|null $maxFileSizeMB Override default 100MB limit
     * @return \Spatie\MediaLibrary\MediaCollections\MediaCollection
     */
    public function registerStandardDocumentCollection(
        ?string $collectionName = null,
        ?array $mimeTypes = null,
        ?int $maxFileSizeMB = null
    ) {
        $collectionName = $collectionName ?? 'documents';
        $mimeTypes = $mimeTypes ?? MediaValidationService::DOCUMENT_MIME_TYPES;
        $maxFileSizeMB = $maxFileSizeMB ?? 100;

        return $this->addMediaCollection($collectionName)
            ->acceptsMimeTypes($mimeTypes)
            ->useDisk('private_media');
            // Note: File size validation is handled by MediaValidationService at upload time
    }

    /**
     * Register image-only collection for photos
     *
     * Use this for collections that should only contain images (photos, scans, etc.)
     *
     * @param string $collectionName Collection name (default: 'photos')
     * @param int|null $maxFileSizeMB Max file size in MB (default: 50MB)
     * @return \Spatie\MediaLibrary\MediaCollections\MediaCollection
     */
    public function registerStandardPhotoCollection(
        string $collectionName = 'photos',
        ?int $maxFileSizeMB = null
    ) {
        $maxFileSizeMB = $maxFileSizeMB ?? 50;

        return $this->addMediaCollection($collectionName)
            ->acceptsMimeTypes(MediaValidationService::IMAGE_MIME_TYPES)
            ->useDisk('private_media');
            // Note: File size validation is handled by MediaValidationService at upload time
    }

    /**
     * Register single-file collection (e.g., signatures, avatars)
     *
     * Use this for collections that should only contain one file at a time.
     * Uploading a new file will automatically replace the old one.
     *
     * @param string $collectionName Collection name
     * @param array|null $mimeTypes Allowed MIME types (default: images)
     * @param int|null $maxFileSizeMB Max file size in MB (default: 10MB)
     * @return \Spatie\MediaLibrary\MediaCollections\MediaCollection
     */
    public function registerStandardSingleFileCollection(
        string $collectionName,
        ?array $mimeTypes = null,
        ?int $maxFileSizeMB = null
    ) {
        $mimeTypes = $mimeTypes ?? MediaValidationService::IMAGE_MIME_TYPES;
        $maxFileSizeMB = $maxFileSizeMB ?? 10;

        return $this->addMediaCollection($collectionName)
            ->acceptsMimeTypes($mimeTypes)
            ->useDisk('private_media')
            ->singleFile();
            // Note: File size validation is handled by MediaValidationService at upload time
    }

    /**
     * Register standard image conversions (thumbnail and preview)
     *
     * This method creates:
     * - 'thumb': 150x150 square thumbnail, generated immediately
     * - 'preview': 300x300 square preview, queued for background processing
     *
     * Conversions are only performed for image files (PDF, Office docs are ignored)
     *
     * @param Media|null $media
     * @return void
     */
    public function registerStandardConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued(); // Generate immediately for fast loading

        $this->addMediaConversion('preview')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->queued(); // Generate in background to avoid blocking
    }

    /**
     * Register large image conversions (for photo galleries)
     *
     * Creates additional larger sizes:
     * - 'small': 640x480
     * - 'medium': 1280x720
     * - 'large': 1920x1080
     *
     * @param Media|null $media
     * @return void
     */
    public function registerLargeImageConversions(?Media $media = null): void
    {
        // Include standard conversions
        $this->registerStandardConversions($media);

        // Add larger sizes
        $this->addMediaConversion('small')
            ->width(640)
            ->height(480)
            ->sharpen(5)
            ->queued();

        $this->addMediaConversion('medium')
            ->width(1280)
            ->height(720)
            ->sharpen(5)
            ->queued();

        $this->addMediaConversion('large')
            ->width(1920)
            ->height(1080)
            ->sharpen(3)
            ->queued();
    }

    /**
     * Get documents from standard 'documents' collection
     *
     * Convenience method for accessing documents. Can be used in appends array:
     * protected $appends = ['documents'];
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }

    /**
     * Get photos from standard 'photos' collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPhotosAttribute()
    {
        return $this->getMedia('photos');
    }

    /**
     * Check if model has any documents
     *
     * @param string $collectionName
     * @return bool
     */
    public function hasDocuments(string $collectionName = 'documents'): bool
    {
        return $this->hasMedia($collectionName);
    }

    /**
     * Get total size of all media in bytes
     *
     * @param string|null $collectionName Specific collection or all if null
     * @return int Total size in bytes
     */
    public function getTotalMediaSize(?string $collectionName = null): int
    {
        $media = $collectionName
            ? $this->getMedia($collectionName)
            : $this->getMedia();

        return $media->sum('size');
    }

    /**
     * Get total size of all media in human-readable format
     *
     * @param string|null $collectionName Specific collection or all if null
     * @return string Human-readable size (e.g., "2.5 MB")
     */
    public function getTotalMediaSizeFormatted(?string $collectionName = null): string
    {
        $bytes = $this->getTotalMediaSize($collectionName);

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete all media from a collection
     *
     * @param string $collectionName
     * @return void
     */
    public function clearDocuments(string $collectionName = 'documents'): void
    {
        $this->clearMediaCollection($collectionName);
    }

    /**
     * Get document count for a collection
     *
     * @param string $collectionName
     * @return int
     */
    public function getDocumentCount(string $collectionName = 'documents'): int
    {
        return $this->getMedia($collectionName)->count();
    }

    /**
     * Get most recent document from collection
     *
     * @param string $collectionName
     * @return Media|null
     */
    public function getLatestDocument(string $collectionName = 'documents'): ?Media
    {
        return $this->getMedia($collectionName)->sortByDesc('created_at')->first();
    }

    /**
     * Get oldest document from collection
     *
     * @param string $collectionName
     * @return Media|null
     */
    public function getOldestDocument(string $collectionName = 'documents'): ?Media
    {
        return $this->getMedia($collectionName)->sortBy('created_at')->first();
    }

    /**
     * Check if model has media of specific type
     *
     * @param string $mimeType MIME type to check (e.g., 'application/pdf')
     * @param string $collectionName
     * @return bool
     */
    public function hasMediaOfType(string $mimeType, string $collectionName = 'documents'): bool
    {
        return $this->getMedia($collectionName)
            ->filter(fn($media) => $media->mime_type === $mimeType)
            ->isNotEmpty();
    }

    /**
     * Get media filtered by MIME type
     *
     * @param string $mimeType MIME type to filter (e.g., 'application/pdf')
     * @param string $collectionName
     * @return \Illuminate\Support\Collection
     */
    public function getMediaOfType(string $mimeType, string $collectionName = 'documents')
    {
        return $this->getMedia($collectionName)
            ->filter(fn($media) => $media->mime_type === $mimeType);
    }
}
