<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * MediaValidationService
 *
 * Centralized service for validating, sanitizing, and managing file uploads.
 * Provides consistent MIME type validation and filename sanitization across
 * all models that handle media uploads.
 *
 * Usage:
 * ```php
 * // Validate file type
 * MediaValidationService::validateFileType($file, MediaValidationService::DOCUMENT_MIME_TYPES);
 *
 * // Sanitize filename
 * $safe = MediaValidationService::sanitizeFileName($file->getClientOriginalName());
 * ```
 */
class MediaValidationService
{
    /**
     * Standard document MIME types accepted across the application
     *
     * Use this constant for all document upload collections to ensure consistency.
     * Includes: PDF, Office documents, LibreOffice, text files, images, archives, email
     */
    public const DOCUMENT_MIME_TYPES = [
        // PDF
        'application/pdf',

        // Microsoft Office (Legacy)
        'application/msword',                    // .doc
        'application/vnd.ms-excel',              // .xls
        'application/vnd.ms-powerpoint',         // .ppt

        // Microsoft Office (Modern)
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',     // .docx
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',          // .xlsx
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',  // .pptx

        // LibreOffice/OpenOffice
        'application/vnd.oasis.opendocument.text',         // .odt
        'application/vnd.oasis.opendocument.spreadsheet',  // .ods
        'application/vnd.oasis.opendocument.presentation', // .odp

        // Archives
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/gzip',
        'application/x-tar',

        // Text
        'text/plain',
        'text/csv',
        'text/rtf',
        'application/rtf',

        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/tiff',
        'image/tif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',

        // Email
        'message/rfc822', // .eml files

        // Other Office-related
        'application/vnd.ms-office', // Generic MS Office
    ];

    /**
     * Image-only MIME types for photo collections
     *
     * Use this for collections that should only accept images (property photos,
     * contractor photos, participant photos, etc.)
     */
    public const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/tiff',
        'image/tif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',
    ];

    /**
     * PDF-only MIME type
     *
     * Use for collections that should only accept PDF files
     */
    public const PDF_MIME_TYPES = [
        'application/pdf',
    ];

    /**
     * Spreadsheet MIME types
     *
     * Use for collections specifically for spreadsheet uploads
     */
    public const SPREADSHEET_MIME_TYPES = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.spreadsheet',
        'text/csv',
    ];

    /**
     * Maximum file size constants (in bytes)
     */
    public const SIZE_1MB = 1024 * 1024;
    public const SIZE_10MB = 10 * 1024 * 1024;
    public const SIZE_50MB = 50 * 1024 * 1024;
    public const SIZE_100MB = 100 * 1024 * 1024;
    public const SIZE_500MB = 500 * 1024 * 1024;

    /**
     * Default maximum file size (100MB)
     */
    public const DEFAULT_MAX_SIZE = self::SIZE_100MB;

    /**
     * Validate uploaded file MIME type
     *
     * @param UploadedFile $file The uploaded file to validate
     * @param array $allowedMimeTypes Array of allowed MIME types
     * @return void
     * @throws InvalidArgumentException If file type is not allowed
     */
    public static function validateFileType(UploadedFile $file, array $allowedMimeTypes): void
    {
        $mimeType = $file->getMimeType();
        $clientMimeType = $file->getClientMimeType();

        // Check both server-detected and client-provided MIME types
        if (!in_array($mimeType, $allowedMimeTypes) && !in_array($clientMimeType, $allowedMimeTypes)) {
            throw new InvalidArgumentException(
                "File type not allowed. Detected: {$mimeType}, Client: {$clientMimeType}. " .
                "Allowed types: " . implode(', ', $allowedMimeTypes)
            );
        }
    }

    /**
     * Validate file size
     *
     * @param UploadedFile $file The uploaded file to validate
     * @param int $maxSize Maximum size in bytes (default: 100MB)
     * @return void
     * @throws InvalidArgumentException If file is too large
     */
    public static function validateFileSize(UploadedFile $file, int $maxSize = self::DEFAULT_MAX_SIZE): void
    {
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $actualSizeMB = round($file->getSize() / (1024 * 1024), 2);

            throw new InvalidArgumentException(
                "File size exceeds maximum allowed size. " .
                "Maximum: {$maxSizeMB}MB, Uploaded: {$actualSizeMB}MB"
            );
        }
    }

    /**
     * Sanitize filename for safe storage
     *
     * Removes or replaces unsafe characters, prevents directory traversal,
     * and ensures filename is safe for use in filesystem and URLs.
     *
     * Rules:
     * - Converts to lowercase
     * - Replaces spaces with hyphens
     * - Removes or replaces special characters
     * - Prevents directory traversal (../)
     * - Limits length to 255 characters
     * - Preserves file extension
     *
     * @param string $fileName Original filename
     * @param int $maxLength Maximum filename length (default: 255)
     * @return string Sanitized filename
     */
    public static function sanitizeFileName(string $fileName, int $maxLength = 255): string
    {
        // Get file extension
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

        // Convert to lowercase
        $nameWithoutExtension = Str::lower($nameWithoutExtension);

        // Replace spaces with hyphens
        $nameWithoutExtension = str_replace(' ', '-', $nameWithoutExtension);

        // Remove special characters (keep only alphanumeric, hyphens, underscores)
        $nameWithoutExtension = preg_replace('/[^a-z0-9\-_]/', '', $nameWithoutExtension);

        // Remove multiple consecutive hyphens
        $nameWithoutExtension = preg_replace('/-+/', '-', $nameWithoutExtension);

        // Trim hyphens from start and end
        $nameWithoutExtension = trim($nameWithoutExtension, '-');

        // If name is empty after sanitization, use a timestamp
        if (empty($nameWithoutExtension)) {
            $nameWithoutExtension = 'file-' . time();
        }

        // Limit length (accounting for extension and dot)
        $maxNameLength = $maxLength - strlen($extension) - 1;
        if (strlen($nameWithoutExtension) > $maxNameLength) {
            $nameWithoutExtension = substr($nameWithoutExtension, 0, $maxNameLength);
        }

        // Rebuild filename with extension
        return $nameWithoutExtension . '.' . Str::lower($extension);
    }

    /**
     * Generate unique filename to prevent collisions
     *
     * Adds timestamp and random string to filename while preserving extension.
     *
     * @param string $fileName Original filename
     * @return string Unique filename
     */
    public static function generateUniqueFileName(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

        // Sanitize base name
        $sanitized = self::sanitizeFileName($nameWithoutExtension . '.tmp');
        $sanitized = str_replace('.tmp', '', $sanitized);

        // Add timestamp and random string
        $unique = $sanitized . '-' . time() . '-' . Str::random(8);

        return $unique . '.' . Str::lower($extension);
    }

    /**
     * Get human-readable file size
     *
     * @param int $bytes File size in bytes
     * @param int $precision Decimal precision (default: 2)
     * @return string Human-readable size (e.g., "2.5 MB")
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Validate file is an actual file (not directory, symlink, etc.)
     *
     * @param UploadedFile $file
     * @return void
     * @throws InvalidArgumentException If not a valid file
     */
    public static function validateIsFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('File upload is not valid');
        }

        if ($file instanceof TemporaryUploadedFile) {
            $realPath = $file->getRealPath();

            if ($realPath === false || ! file_exists($realPath)) {
                throw new InvalidArgumentException('Temporary upload file is missing on disk');
            }

            return;
        }

        if (method_exists($file, 'isFile') && $file->isFile()) {
            return;
        }

        $realPath = method_exists($file, 'getRealPath')
            ? $file->getRealPath()
            : null;

        if (!$realPath || ! file_exists($realPath)) {
            throw new InvalidArgumentException('Upload is not a file');
        }
    }

    /**
     * Validate multiple files at once
     *
     * @param array $files Array of UploadedFile objects
     * @param array $allowedMimeTypes
     * @param int $maxSize Maximum size per file
     * @return void
     * @throws InvalidArgumentException If any file fails validation
     */
    public static function validateMultipleFiles(
        array $files,
        array $allowedMimeTypes,
        int $maxSize = self::DEFAULT_MAX_SIZE
    ): void {
        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                throw new InvalidArgumentException("Item at index {$index} is not a valid uploaded file");
            }

            self::validateIsFile($file);
            self::validateFileType($file, $allowedMimeTypes);
            self::validateFileSize($file, $maxSize);
        }
    }

    /**
     * Get file extension from filename
     *
     * @param string $fileName
     * @return string Lowercase extension without dot
     */
    public static function getExtension(string $fileName): string
    {
        return Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is an image based on MIME type
     *
     * @param UploadedFile|string $fileOrMimeType File or MIME type string
     * @return bool
     */
    public static function isImage($fileOrMimeType): bool
    {
        $mimeType = $fileOrMimeType instanceof UploadedFile
            ? $fileOrMimeType->getMimeType()
            : $fileOrMimeType;

        return in_array($mimeType, self::IMAGE_MIME_TYPES);
    }

    /**
     * Check if file is a PDF based on MIME type
     *
     * @param UploadedFile|string $fileOrMimeType File or MIME type string
     * @return bool
     */
    public static function isPdf($fileOrMimeType): bool
    {
        $mimeType = $fileOrMimeType instanceof UploadedFile
            ? $fileOrMimeType->getMimeType()
            : $fileOrMimeType;

        return $mimeType === 'application/pdf';
    }

    /**
     * Check if file is a spreadsheet based on MIME type
     *
     * @param UploadedFile|string $fileOrMimeType File or MIME type string
     * @return bool
     */
    public static function isSpreadsheet($fileOrMimeType): bool
    {
        $mimeType = $fileOrMimeType instanceof UploadedFile
            ? $fileOrMimeType->getMimeType()
            : $fileOrMimeType;

        return in_array($mimeType, self::SPREADSHEET_MIME_TYPES);
    }

    /**
     * Get display name for MIME type
     *
     * @param string $mimeType
     * @return string Human-readable type name
     */
    public static function getMimeTypeDisplayName(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'PDF Document',
            'application/msword' => 'Word Document (.doc)',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document (.docx)',
            'application/vnd.ms-excel' => 'Excel Spreadsheet (.xls)',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheet (.xlsx)',
            'application/vnd.ms-powerpoint' => 'PowerPoint Presentation (.ppt)',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint Presentation (.pptx)',
            'text/plain' => 'Text File',
            'text/csv' => 'CSV File',
            'application/zip' => 'ZIP Archive',
            'image/jpeg', 'image/jpg' => 'JPEG Image',
            'image/png' => 'PNG Image',
            'image/gif' => 'GIF Image',
            'image/webp' => 'WebP Image',
            'message/rfc822' => 'Email File',
            default => ucfirst(str_replace(['application/', 'image/', 'text/'], '', $mimeType)),
        };
    }

    /**
     * Comprehensive validation for uploaded file
     *
     * Performs all standard validations in one call.
     *
     * @param UploadedFile $file
     * @param array $allowedMimeTypes
     * @param int $maxSize
     * @return void
     * @throws InvalidArgumentException If any validation fails
     */
    public static function validate(
        UploadedFile $file,
        array $allowedMimeTypes = self::DOCUMENT_MIME_TYPES,
        int $maxSize = self::DEFAULT_MAX_SIZE
    ): void {
        self::validateIsFile($file);
        self::validateFileType($file, $allowedMimeTypes);
        self::validateFileSize($file, $maxSize);
    }
}
