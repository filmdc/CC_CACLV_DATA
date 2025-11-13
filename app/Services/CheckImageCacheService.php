<?php

namespace App\Services;

use App\Models\CheckImageAuditLog;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckImageCacheService
{
    private string $cacheDirectory = 'check_images';

    private ?string $smbUsername = null;

    private ?string $smbPassword = null;

    private ?string $smbDomain = null;

    private array $allowedMimeTypes = [
        'image/tiff',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/msword', // .doc
        'application/vnd.ms-excel', // .xls
    ];

    private int $maxFileSizeMB = 50;

    private bool $optimizationEnabled = true;

    private int $maxWidth = 2400;

    private int $maxHeight = 1200;

    private int $jpegQuality = 85;

    private int $targetSizeKB = 2048;

    public function __construct()
    {
        // Load SMB credentials from environment or config
        $this->smbUsername = env('SMB_USERNAME');
        $this->smbPassword = env('SMB_PASSWORD');
        $this->smbDomain = env('SMB_DOMAIN', 'CACLV');
        $this->maxFileSizeMB = env('CHECK_IMAGE_MAX_SIZE_MB', 50);

        // Load optimization settings
        $this->optimizationEnabled = env('ENABLE_IMAGE_OPTIMIZATION', true);
        $this->maxWidth = env('CHECK_IMAGE_MAX_WIDTH', 2400);
        $this->maxHeight = env('CHECK_IMAGE_MAX_HEIGHT', 1200);
        $this->jpegQuality = env('CHECK_IMAGE_JPEG_QUALITY', 85);
        $this->targetSizeKB = env('CHECK_IMAGE_TARGET_SIZE_KB', 2048);
    }

    /**
     * Cache an image from a UNC network path
     *
     * @param  string  $uncPath  UNC path like \\SERVER\Share\path\file.tif
     * @param  string  $giftId  Gift ID for organizing cached files
     * @return array Result with success status and local URL
     */
    public function cacheImageFromUncPath(string $uncPath, string $giftId): array
    {
        try {
            // Parse the UNC path
            $parsedPath = $this->parseUncPath($uncPath);
            if (! $parsedPath['success']) {
                return [
                    'success' => false,
                    'error' => $parsedPath['error'],
                ];
            }

            $server = $parsedPath['server'];
            $share = $parsedPath['share'];
            $filePath = $parsedPath['file_path'];
            $fileName = basename($filePath);

            // Create a unique cache filename based on gift ID and original filename
            $cacheFilename = $giftId.'_'.Str::slug(pathinfo($fileName, PATHINFO_FILENAME)).'.'.pathinfo($fileName, PATHINFO_EXTENSION);
            $localPath = $this->cacheDirectory.'/'.$cacheFilename;

            // Check if already cached (encrypted file)
            if (Storage::disk('local')->exists($localPath.'.enc')) {
                Log::info('Check image already cached', [
                    'gift_id' => $giftId,
                    'cached_file' => $cacheFilename,
                ]);

                // Log audit event
                CheckImageAuditLog::logAccess('cached', $cacheFilename, $giftId, [
                    'unc_path' => $uncPath,
                    'already_cached' => true,
                ]);

                return [
                    'success' => true,
                    'cached' => true,
                    'url' => route('donor-gifts.check-image', ['filename' => $cacheFilename]),
                    'path' => $localPath,
                ];
            }

            // Fetch file from SMB share using smbclient
            $result = $this->fetchFileViaSmbClient($server, $share, $filePath, $localPath, [
                'gift_id' => $giftId,
                'original_name' => $fileName,
            ]);

            if ($result['success']) {
                // Log audit event for newly cached file
                CheckImageAuditLog::logAccess('cached', $cacheFilename, $giftId, [
                    'unc_path' => $uncPath,
                    'already_cached' => false,
                    'file_size' => $result['file_size'] ?? null,
                    'mime_type' => $result['mime_type'] ?? null,
                    'checksum' => $result['checksum'] ?? null,
                ]);

                return [
                    'success' => true,
                    'cached' => false,
                    'url' => route('donor-gifts.check-image', ['filename' => $cacheFilename]),
                    'path' => $localPath,
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error caching check image', [
                'unc_path' => $uncPath,
                'gift_id' => $giftId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse a UNC path into components
     */
    private function parseUncPath(string $uncPath): array
    {
        // UNC format: \\SERVER\Share\path\to\file.ext
        $uncPath = str_replace('/', '\\', $uncPath);

        if (! str_starts_with($uncPath, '\\\\')) {
            return [
                'success' => false,
                'error' => 'Invalid UNC path format',
            ];
        }

        // Remove leading \\
        $path = substr($uncPath, 2);
        $parts = explode('\\', $path, 3);

        if (count($parts) < 3) {
            return [
                'success' => false,
                'error' => 'Invalid UNC path structure',
            ];
        }

        return [
            'success' => true,
            'server' => $parts[0],
            'share' => $parts[1],
            'file_path' => $parts[2] ?? '',
        ];
    }

    /**
     * Validate file type and size
     */
    private function validateFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['valid' => false, 'error' => 'File does not exist'];
        }

        // Check file size
        $fileSize = filesize($filePath);
        $maxBytes = $this->maxFileSizeMB * 1024 * 1024;

        if ($fileSize > $maxBytes) {
            return [
                'valid' => false,
                'error' => 'File too large: '.round($fileSize / 1024 / 1024, 2)."MB (max: {$this->maxFileSizeMB}MB)",
            ];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'valid' => false,
                'error' => "File type not allowed: {$mimeType}",
            ];
        }

        return [
            'valid' => true,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ];
    }

    /**
     * Scan file for malware using ClamAV (if enabled)
     *
     * @param  string  $filePath  Absolute path to file
     * @return array Scan result with 'clean' boolean
     */
    private function scanForMalware(string $filePath): array
    {
        // Check if malware scanning is enabled
        if (! env('ENABLE_MALWARE_SCANNING', false)) {
            return ['clean' => true, 'skipped' => true];
        }

        // Verify ClamAV is installed
        exec('which clamdscan', $output, $returnCode);
        if ($returnCode !== 0) {
            Log::warning('ClamAV not found - malware scanning disabled');

            return ['clean' => true, 'skipped' => true, 'error' => 'ClamAV not installed'];
        }

        // Scan the file
        $escapedPath = escapeshellarg($filePath);
        exec("clamdscan --no-summary {$escapedPath}", $output, $returnCode);

        // ClamAV returns:
        // 0 = clean
        // 1 = infected
        // 2 = error

        if ($returnCode === 1) {
            Log::error('MALWARE DETECTED in check image', [
                'file' => basename($filePath),
                'scan_output' => implode("\n", $output),
            ]);

            // Delete the infected file immediately
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return ['clean' => false, 'infected' => true];
        }

        if ($returnCode === 2) {
            Log::warning('ClamAV scan error', [
                'file' => basename($filePath),
                'output' => implode("\n", $output),
            ]);

            return ['clean' => true, 'skipped' => true, 'error' => 'Scan error'];
        }

        Log::info('File passed malware scan', ['file' => basename($filePath)]);

        return ['clean' => true, 'scanned' => true];
    }

    /**
     * Verify file integrity against stored checksum
     */
    public function verifyFileIntegrity(string $storagePath, string $expectedChecksum): bool
    {
        if (! Storage::disk('local')->exists($storagePath.'.enc')) {
            Log::warning('Cannot verify integrity - file not found', ['path' => $storagePath]);

            return false;
        }

        // Decrypt and calculate checksum
        $decryptResult = $this->decryptFile($storagePath);
        if (! $decryptResult['success']) {
            return false;
        }

        $actualChecksum = hash('sha256', $decryptResult['contents']);
        $isValid = hash_equals($expectedChecksum, $actualChecksum);

        if (! $isValid) {
            Log::error('File integrity check failed', [
                'path' => $storagePath,
                'expected' => $expectedChecksum,
                'actual' => $actualChecksum,
            ]);
        }

        return $isValid;
    }

    /**
     * Get file metadata with integrity verification
     */
    public function getFileMetadata(string $storagePath): ?array
    {
        $metadataPath = 'check_images/metadata/'.basename($storagePath).'.json';

        if (! Storage::disk('local')->exists($metadataPath)) {
            return null;
        }

        return json_decode(Storage::disk('local')->get($metadataPath), true);
    }

    /**
     * Fetch file from SMB share using smbclient command
     */
    private function fetchFileViaSmbClient(string $server, string $share, string $remoteFilePath, string $localStoragePath, array $metadata = []): array
    {
        try {
            // Build local absolute path (temporary, unencrypted location)
            $tempPath = 'temp/'.basename($localStoragePath);
            $localAbsolutePath = Storage::disk('local')->path($tempPath);
            $localDir = dirname($localAbsolutePath);

            // Ensure directory exists with restricted permissions
            if (! file_exists($localDir)) {
                mkdir($localDir, 0700, true);
            }

            // Build smbclient command
            // Format: smbclient //server/share -U username%password -D "" -c "get 'remote/path' 'local/path'"
            $smbPath = "//{$server}/{$share}";

            // Build authentication string
            $authString = '';
            if ($this->smbUsername && $this->smbPassword) {
                if ($this->smbDomain) {
                    $authString = "{$this->smbDomain}\\{$this->smbUsername}%{$this->smbPassword}";
                } else {
                    $authString = "{$this->smbUsername}%{$this->smbPassword}";
                }
            } else {
                $authString = 'guest%';
            }

            // Convert backslashes to forward slashes for smbclient
            $smbRemotePath = str_replace('\\', '/', $remoteFilePath);

            // Build command - use variables to avoid escaping issues with special chars in password
            $command = sprintf(
                'SMB_USER=%s SMB_PASS=%s smbclient %s -U "${SMB_USER}%%${SMB_PASS}" -c \'get "%s" "%s"\' 2>&1',
                escapeshellarg($authString),
                '',  // Password is included in authString
                escapeshellarg($smbPath),
                $smbRemotePath,
                $localAbsolutePath
            );

            // Simpler approach: use environment variables
            $escapedAuthString = $authString;
            $command = sprintf(
                'smbclient %s -U %s -c %s 2>&1',
                escapeshellarg($smbPath),
                escapeshellarg($escapedAuthString),
                escapeshellarg('get "'.$smbRemotePath.'" "'.$localAbsolutePath.'"')
            );

            Log::info('Executing smbclient command', [
                'server' => $server,
                'share' => $share,
                'remote_file' => $remoteFilePath,
                'local_path' => $localAbsolutePath,
                'command_preview' => substr($command, 0, 200),
            ]);

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || ! file_exists($localAbsolutePath)) {
                Log::error('smbclient failed to fetch file', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output),
                    'server' => $server,
                    'share' => $share,
                    'file' => $remoteFilePath,
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to fetch file from network share: '.implode(' ', $output),
                ];
            }

            // Validate file after successful download
            $validationResult = $this->validateFile($localAbsolutePath);
            if (! $validationResult['valid']) {
                // Delete invalid file
                @unlink($localAbsolutePath);

                Log::error('File validation failed after download', [
                    'server' => $server,
                    'share' => $share,
                    'file' => $remoteFilePath,
                    'error' => $validationResult['error'],
                ]);

                return [
                    'success' => false,
                    'error' => 'File validation failed: '.$validationResult['error'],
                ];
            }

            // Optimize image before encryption
            $optimizeResult = $this->optimizeImage($localAbsolutePath, $validationResult['mime_type']);
            if ($optimizeResult['success'] && $optimizeResult['optimized']) {
                Log::info('Image optimized before caching', [
                    'original_size_mb' => round(($optimizeResult['original_size'] ?? 0) / 1024 / 1024, 2),
                    'optimized_size_mb' => round(($optimizeResult['optimized_size'] ?? 0) / 1024 / 1024, 2),
                    'savings_percent' => $optimizeResult['savings_percent'] ?? 0,
                    'format_changed' => $optimizeResult['format_changed'] ?? false,
                ]);

                // Update path if changed (e.g., TIFF → JPEG)
                $localAbsolutePath = $optimizeResult['path'];

                // Re-validate optimized file
                $validationResult = $this->validateFile($localAbsolutePath);
                if (! $validationResult['valid']) {
                    @unlink($localAbsolutePath);

                    return [
                        'success' => false,
                        'error' => 'Optimized file validation failed: '.$validationResult['error'],
                    ];
                }
            }

            // Scan for malware after validation/optimization
            $scanResult = $this->scanForMalware($localAbsolutePath);
            if (! $scanResult['clean']) {
                return [
                    'success' => false,
                    'error' => 'File failed security scan: malware detected',
                ];
            }

            Log::info('Successfully fetched check image, encrypting...', [
                'unc_path' => "\\\\{$server}\\{$share}\\{$remoteFilePath}",
                'local_path' => $localStoragePath,
                'file_size' => $validationResult['file_size'],
                'mime_type' => $validationResult['mime_type'],
                'malware_scan' => $scanResult,
            ]);

            // Encrypt and store the file
            $encryptResult = $this->encryptAndStoreFile(
                $localAbsolutePath,
                $localStoragePath,
                array_merge($metadata, [
                    'original_name' => basename($remoteFilePath),
                    'mime_type' => $validationResult['mime_type'],
                    'file_size' => $validationResult['file_size'],
                ])
            );

            if (! $encryptResult['success']) {
                return [
                    'success' => false,
                    'error' => 'File downloaded but encryption failed: '.($encryptResult['error'] ?? 'Unknown error'),
                ];
            }

            Log::info('Successfully encrypted and cached check image', [
                'unc_path' => "\\\\{$server}\\{$share}\\{$remoteFilePath}",
                'local_path' => $localStoragePath,
                'checksum' => $encryptResult['checksum'],
            ]);

            return [
                'success' => true,
                'message' => 'File cached and encrypted successfully',
                'mime_type' => $validationResult['mime_type'],
                'file_size' => $validationResult['file_size'],
                'checksum' => $encryptResult['checksum'],
            ];

        } catch (\Exception $e) {
            Log::error('Exception in fetchFileViaSmbClient', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cache multiple images from gift data
     *
     * @param  array  $gifts  Array of gift records with IMAGE_CHECK field
     * @return array Summary of caching operation
     */
    public function cacheImagesFromGifts(array $gifts): array
    {
        $cached = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($gifts as &$gift) {
            if (empty($gift['IMAGE_CHECK'])) {
                $skipped++;

                continue;
            }

            $giftId = $gift['gift_id'] ?? 'unknown';
            $result = $this->cacheImageFromUncPath($gift['IMAGE_CHECK'], $giftId);

            if ($result['success']) {
                // Add the local URL to the gift data
                $gift['IMAGE_CHECK_URL'] = $result['url'];
                $cached++;
            } else {
                $failed++;
                Log::warning('Failed to cache check image', [
                    'gift_id' => $giftId,
                    'unc_path' => $gift['IMAGE_CHECK'],
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        }

        return [
            'success' => true,
            'cached' => $cached,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => count($gifts),
            'gifts' => $gifts,
        ];
    }

    /**
     * Clear old cached images (older than specified days)
     *
     * @return int Number of files deleted
     */
    public function clearOldCachedImages(int $olderThanDays = 30): int
    {
        $deleted = 0;
        $files = Storage::disk('local')->files($this->cacheDirectory);

        $cutoffTime = now()->subDays($olderThanDays)->timestamp;

        foreach ($files as $file) {
            // Only process encrypted files
            if (! str_ends_with($file, '.enc')) {
                continue;
            }

            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified < $cutoffTime) {
                $filename = basename($file, '.enc');
                Storage::disk('local')->delete($file);

                // Also delete corresponding metadata file
                $metadataPath = 'check_images/metadata/'.$filename.'.json';
                if (Storage::disk('local')->exists($metadataPath)) {
                    Storage::disk('local')->delete($metadataPath);
                }

                $deleted++;

                // Log audit event for deleted file
                CheckImageAuditLog::logAccess('deleted', $filename, null, [
                    'reason' => 'auto_cleanup',
                    'age_days' => round((now()->timestamp - $lastModified) / 86400, 1),
                    'older_than_days' => $olderThanDays,
                ]);
            }
        }

        Log::info('Cleared old cached check images', [
            'deleted_count' => $deleted,
            'older_than_days' => $olderThanDays,
        ]);

        return $deleted;
    }

    /**
     * Optimize image file size and format
     *
     * @param  string  $filePath  Absolute path to source file
     * @param  string  $mimeType  Original MIME type
     * @return array Result with optimized file path
     */
    private function optimizeImage(string $filePath, string $mimeType): array
    {
        try {
            // Check if optimization is enabled
            if (! $this->optimizationEnabled) {
                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $filePath,
                    'reason' => 'optimization_disabled',
                ];
            }

            // Check current file size
            $currentSize = filesize($filePath);

            // Determine optimization strategy based on MIME type
            // Always convert TIFF to JPEG regardless of size (better compatibility)
            if (in_array($mimeType, ['image/tiff', 'image/tif'])) {
                return $this->convertTiffToJpeg($filePath);
            }

            // For other image types, skip optimization if already under target size
            if ($currentSize < ($this->targetSizeKB * 1024)) {
                Log::info('Image already under target size, skipping optimization', [
                    'file' => basename($filePath),
                    'size_kb' => round($currentSize / 1024, 2),
                    'target_kb' => $this->targetSizeKB,
                ]);

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $filePath,
                    'reason' => 'already_optimized',
                ];
            }

            if (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'])) {
                return $this->optimizePhotoImage($filePath);
            }

            if ($mimeType === 'application/pdf') {
                return $this->optimizePdf($filePath);
            }

            // Unsupported type - return original
            Log::info('Unsupported image type for optimization', [
                'mime_type' => $mimeType,
                'file' => basename($filePath),
            ]);

            return [
                'success' => true,
                'optimized' => false,
                'path' => $filePath,
                'reason' => 'unsupported_type',
            ];

        } catch (\Exception $e) {
            Log::error('Image optimization exception', [
                'file' => basename($filePath),
                'error' => $e->getMessage(),
            ]);

            // Return original file if optimization fails
            return [
                'success' => true,
                'optimized' => false,
                'path' => $filePath,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert TIFF to optimized JPEG
     *
     * @param  string  $tiffPath  Absolute path to TIFF file
     * @return array Result with optimized file path
     */
    public function convertTiffToJpeg(string $tiffPath): array
    {
        try {
            // Check if ImageMagick is available
            exec('which convert', $output, $returnCode);
            if ($returnCode !== 0) {
                Log::warning('ImageMagick not available, skipping TIFF conversion');

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $tiffPath,
                    'reason' => 'imagemagick_not_available',
                ];
            }

            $originalSize = filesize($tiffPath);
            $outputPath = preg_replace('/\.(tif|tiff)$/i', '.jpg', $tiffPath);

            // ImageMagick command - use [0] to convert only first page for multi-page TIFFs
            $command = sprintf(
                'convert %s -resize %dx%d\> -quality %d -colorspace RGB %s 2>&1',
                escapeshellarg($tiffPath.'[0]'),
                $this->maxWidth,
                $this->maxHeight,
                $this->jpegQuality,
                escapeshellarg($outputPath)
            );

            Log::info('Converting TIFF to JPEG (first page only for multi-page TIFFs)', [
                'input' => basename($tiffPath),
                'output' => basename($outputPath),
                'max_dimensions' => "{$this->maxWidth}x{$this->maxHeight}",
                'quality' => $this->jpegQuality,
            ]);

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || ! file_exists($outputPath)) {
                Log::warning('TIFF conversion failed, using original', [
                    'error' => implode("\n", $output),
                    'return_code' => $returnCode,
                ]);

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $tiffPath,
                    'error' => 'conversion_failed',
                ];
            }

            $optimizedSize = filesize($outputPath);
            $savings = round((1 - $optimizedSize / $originalSize) * 100, 1);

            // Delete original TIFF
            unlink($tiffPath);

            Log::info('TIFF converted to JPEG successfully', [
                'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                'optimized_size_mb' => round($optimizedSize / 1024 / 1024, 2),
                'savings_percent' => $savings,
            ]);

            return [
                'success' => true,
                'optimized' => true,
                'path' => $outputPath,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'savings_percent' => $savings,
                'format_changed' => 'tiff_to_jpeg',
            ];

        } catch (\Exception $e) {
            Log::error('TIFF conversion exception', [
                'file' => basename($tiffPath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => true,
                'optimized' => false,
                'path' => $tiffPath,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Optimize JPEG/PNG image
     *
     * @param  string  $imagePath  Absolute path to image file
     * @return array Result with optimized file path
     */
    private function optimizePhotoImage(string $imagePath): array
    {
        try {
            // Check if ImageMagick is available
            exec('which convert', $output, $returnCode);
            if ($returnCode !== 0) {
                Log::warning('ImageMagick not available, skipping photo optimization');

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $imagePath,
                    'reason' => 'imagemagick_not_available',
                ];
            }

            $originalSize = filesize($imagePath);
            $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
            $tempPath = str_replace('.'.$ext, '_optimized.'.$ext, $imagePath);

            $command = sprintf(
                'convert %s -resize %dx%d\> -quality %d -strip %s 2>&1',
                escapeshellarg($imagePath),
                $this->maxWidth,
                $this->maxHeight,
                $this->jpegQuality,
                escapeshellarg($tempPath)
            );

            Log::info('Optimizing photo image', [
                'file' => basename($imagePath),
                'max_dimensions' => "{$this->maxWidth}x{$this->maxHeight}",
                'quality' => $this->jpegQuality,
            ]);

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || ! file_exists($tempPath)) {
                Log::warning('Photo optimization failed, using original', [
                    'error' => implode("\n", $output),
                ]);

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $imagePath,
                    'error' => 'optimization_failed',
                ];
            }

            $optimizedSize = filesize($tempPath);
            $savings = round((1 - $optimizedSize / $originalSize) * 100, 1);

            // Replace original with optimized
            unlink($imagePath);
            rename($tempPath, $imagePath);

            Log::info('Photo image optimized successfully', [
                'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                'optimized_size_mb' => round($optimizedSize / 1024 / 1024, 2),
                'savings_percent' => $savings,
            ]);

            return [
                'success' => true,
                'optimized' => true,
                'path' => $imagePath,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'savings_percent' => $savings,
            ];

        } catch (\Exception $e) {
            Log::error('Photo optimization exception', [
                'file' => basename($imagePath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => true,
                'optimized' => false,
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Optimize PDF file using Ghostscript
     *
     * @param  string  $pdfPath  Absolute path to PDF file
     * @return array Result with optimized file path
     */
    private function optimizePdf(string $pdfPath): array
    {
        try {
            // Check if Ghostscript is available
            exec('which gs', $output, $returnCode);
            if ($returnCode !== 0) {
                Log::warning('Ghostscript not available, skipping PDF optimization');

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $pdfPath,
                    'reason' => 'ghostscript_not_available',
                ];
            }

            $originalSize = filesize($pdfPath);
            $tempPath = str_replace('.pdf', '_optimized.pdf', $pdfPath);

            // Ghostscript compression (ebook = 150dpi, good quality for documents)
            $command = sprintf(
                'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook '.
                '-dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                escapeshellarg($tempPath),
                escapeshellarg($pdfPath)
            );

            Log::info('Optimizing PDF', [
                'file' => basename($pdfPath),
            ]);

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || ! file_exists($tempPath)) {
                Log::warning('PDF optimization failed, using original', [
                    'error' => implode("\n", $output),
                ]);

                return [
                    'success' => true,
                    'optimized' => false,
                    'path' => $pdfPath,
                    'error' => 'optimization_failed',
                ];
            }

            $optimizedSize = filesize($tempPath);
            $savings = round((1 - $optimizedSize / $originalSize) * 100, 1);

            // Replace original with optimized
            unlink($pdfPath);
            rename($tempPath, $pdfPath);

            Log::info('PDF optimized successfully', [
                'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                'optimized_size_mb' => round($optimizedSize / 1024 / 1024, 2),
                'savings_percent' => $savings,
            ]);

            return [
                'success' => true,
                'optimized' => true,
                'path' => $pdfPath,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'savings_percent' => $savings,
            ];

        } catch (\Exception $e) {
            Log::error('PDF optimization exception', [
                'file' => basename($pdfPath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => true,
                'optimized' => false,
                'path' => $pdfPath,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Encrypt file contents and store with metadata
     *
     * @param  string  $sourcePath  Absolute path to source file
     * @param  string  $storagePath  Storage path (relative to disk)
     * @param  array  $metadata  Metadata to store with file
     * @return array Result with success status and checksum
     */
    private function encryptAndStoreFile(string $sourcePath, string $storagePath, array $metadata): array
    {
        try {
            // Read file contents
            $fileContents = file_get_contents($sourcePath);

            // Calculate checksum before encryption
            $checksum = hash('sha256', $fileContents);

            // Encrypt the contents
            $encryptedContents = Crypt::encryptString($fileContents);

            // Store encrypted file
            Storage::disk('local')->put($storagePath.'.enc', $encryptedContents);

            // Store metadata
            $metadataPath = 'check_images/metadata/'.basename($storagePath).'.json';
            Storage::disk('local')->put($metadataPath, json_encode([
                'original_name' => $metadata['original_name'] ?? basename($sourcePath),
                'mime_type' => $metadata['mime_type'] ?? mime_content_type($sourcePath),
                'checksum' => $checksum,
                'gift_id' => $metadata['gift_id'] ?? null,
                'cached_at' => now()->toIso8601String(),
                'file_size' => $metadata['file_size'] ?? strlen($fileContents),
            ], JSON_PRETTY_PRINT));

            // Delete unencrypted source
            unlink($sourcePath);

            return ['success' => true, 'checksum' => $checksum];

        } catch (\Exception $e) {
            Log::error('File encryption failed', [
                'source_path' => $sourcePath,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Decrypt a file and return its contents
     *
     * @param  string  $storagePath  Storage path (relative to disk, without .enc extension)
     * @return array Result with success status and decrypted contents
     */
    public function decryptFile(string $storagePath): array
    {
        try {
            $encryptedPath = $storagePath.'.enc';

            if (! Storage::disk('local')->exists($encryptedPath)) {
                return ['success' => false, 'error' => 'Encrypted file not found'];
            }

            $encryptedContents = Storage::disk('local')->get($encryptedPath);
            $decryptedContents = Crypt::decryptString($encryptedContents);

            // Also load metadata if available
            $metadataPath = 'check_images/metadata/'.basename($storagePath).'.json';
            $metadata = null;

            if (Storage::disk('local')->exists($metadataPath)) {
                $metadata = json_decode(Storage::disk('local')->get($metadataPath), true);
            }

            return [
                'success' => true,
                'contents' => $decryptedContents,
                'metadata' => $metadata,
            ];

        } catch (\Exception $e) {
            Log::error('File decryption failed', [
                'storage_path' => $storagePath,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
