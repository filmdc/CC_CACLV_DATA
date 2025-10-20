<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckImageCacheService
{
    private string $cacheDirectory = 'check_images';
    private ?string $smbUsername = null;
    private ?string $smbPassword = null;
    private ?string $smbDomain = null;

    public function __construct()
    {
        // Load SMB credentials from environment or config
        $this->smbUsername = env('SMB_USERNAME');
        $this->smbPassword = env('SMB_PASSWORD');
        $this->smbDomain = env('SMB_DOMAIN', 'CACLV');
    }

    /**
     * Cache an image from a UNC network path
     *
     * @param string $uncPath UNC path like \\SERVER\Share\path\file.tif
     * @param string $giftId Gift ID for organizing cached files
     * @return array Result with success status and local URL
     */
    public function cacheImageFromUncPath(string $uncPath, string $giftId): array
    {
        try {
            // Parse the UNC path
            $parsedPath = $this->parseUncPath($uncPath);
            if (!$parsedPath['success']) {
                return [
                    'success' => false,
                    'error' => $parsedPath['error']
                ];
            }

            $server = $parsedPath['server'];
            $share = $parsedPath['share'];
            $filePath = $parsedPath['file_path'];
            $fileName = basename($filePath);

            // Create a unique cache filename based on gift ID and original filename
            $cacheFilename = $giftId . '_' . Str::slug(pathinfo($fileName, PATHINFO_FILENAME)) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $localPath = $this->cacheDirectory . '/' . $cacheFilename;

            // Check if already cached
            if (Storage::disk('public')->exists($localPath)) {
                Log::info('Check image already cached', [
                    'gift_id' => $giftId,
                    'cached_file' => $cacheFilename
                ]);

                return [
                    'success' => true,
                    'cached' => true,
                    'url' => route('donor-gifts.check-image', ['filename' => $cacheFilename]),
                    'path' => $localPath
                ];
            }

            // Fetch file from SMB share using smbclient
            $result = $this->fetchFileViaSmbClient($server, $share, $filePath, $localPath);

            if ($result['success']) {
                return [
                    'success' => true,
                    'cached' => false,
                    'url' => route('donor-gifts.check-image', ['filename' => $cacheFilename]),
                    'path' => $localPath
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error caching check image', [
                'unc_path' => $uncPath,
                'gift_id' => $giftId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse a UNC path into components
     *
     * @param string $uncPath
     * @return array
     */
    private function parseUncPath(string $uncPath): array
    {
        // UNC format: \\SERVER\Share\path\to\file.ext
        $uncPath = str_replace('/', '\\', $uncPath);

        if (!str_starts_with($uncPath, '\\\\')) {
            return [
                'success' => false,
                'error' => 'Invalid UNC path format'
            ];
        }

        // Remove leading \\
        $path = substr($uncPath, 2);
        $parts = explode('\\', $path, 3);

        if (count($parts) < 3) {
            return [
                'success' => false,
                'error' => 'Invalid UNC path structure'
            ];
        }

        return [
            'success' => true,
            'server' => $parts[0],
            'share' => $parts[1],
            'file_path' => $parts[2] ?? ''
        ];
    }

    /**
     * Fetch file from SMB share using smbclient command
     *
     * @param string $server
     * @param string $share
     * @param string $remoteFilePath
     * @param string $localStoragePath
     * @return array
     */
    private function fetchFileViaSmbClient(string $server, string $share, string $remoteFilePath, string $localStoragePath): array
    {
        try {
            // Build local absolute path
            $localAbsolutePath = Storage::disk('public')->path($localStoragePath);
            $localDir = dirname($localAbsolutePath);

            // Ensure directory exists
            if (!file_exists($localDir)) {
                mkdir($localDir, 0755, true);
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
                escapeshellarg('get "' . $smbRemotePath . '" "' . $localAbsolutePath . '"')
            );

            Log::info('Executing smbclient command', [
                'server' => $server,
                'share' => $share,
                'remote_file' => $remoteFilePath,
                'local_path' => $localAbsolutePath,
                'command_preview' => substr($command, 0, 200)
            ]);

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($localAbsolutePath)) {
                Log::error('smbclient failed to fetch file', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output),
                    'server' => $server,
                    'share' => $share,
                    'file' => $remoteFilePath
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to fetch file from network share: ' . implode(' ', $output)
                ];
            }

            Log::info('Successfully cached check image', [
                'unc_path' => "\\\\{$server}\\{$share}\\{$remoteFilePath}",
                'local_path' => $localStoragePath,
                'file_size' => filesize($localAbsolutePath)
            ]);

            return [
                'success' => true,
                'message' => 'File cached successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Exception in fetchFileViaSmbClient', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cache multiple images from gift data
     *
     * @param array $gifts Array of gift records with IMAGE_CHECK field
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
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        }

        return [
            'success' => true,
            'cached' => $cached,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => count($gifts),
            'gifts' => $gifts
        ];
    }

    /**
     * Clear old cached images (older than specified days)
     *
     * @param int $olderThanDays
     * @return int Number of files deleted
     */
    public function clearOldCachedImages(int $olderThanDays = 30): int
    {
        $deleted = 0;
        $files = Storage::disk('public')->files($this->cacheDirectory);

        $cutoffTime = now()->subDays($olderThanDays)->timestamp;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);

            if ($lastModified < $cutoffTime) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        Log::info('Cleared old cached check images', [
            'deleted_count' => $deleted,
            'older_than_days' => $olderThanDays
        ]);

        return $deleted;
    }
}
