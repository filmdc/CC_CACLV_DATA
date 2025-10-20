<?php

namespace App\Services;

use App\Models\CheckImageAuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class SageIntacctGiftSyncService
{
    private SageIntacctApiService $apiService;
    private CheckImageCacheService $imageCacheService;

    public function __construct()
    {
        $setting = \App\Models\SageIntacctSetting::getActive();
        if (!$setting || !$setting->isConfigured()) {
            throw new \Exception('Sage Intacct is not configured');
        }

        $this->apiService = new SageIntacctApiService($setting);
        $this->imageCacheService = new CheckImageCacheService();
    }

    /**
     * Sync gift to Intacct with optional check image attachment
     */
    public function syncGiftWithAttachment(array $gift, ?string $checkImagePath = null): array
    {
        try {
            // 1. Post the gift transaction to Intacct
            // Note: You'll need to implement createGiftTransaction() based on your Intacct setup
            // This is a placeholder for the actual gift posting logic

            Log::info('Syncing gift to Intacct', [
                'gift_id' => $gift['gift_id'] ?? 'unknown',
                'has_attachment' => !empty($checkImagePath)
            ]);

            // Placeholder - implement actual gift transaction creation
            // $giftResult = $this->createGiftTransaction($gift);
            // $transactionId = $giftResult['transaction_id'];
            $transactionId = 'PLACEHOLDER_' . ($gift['gift_id'] ?? 'unknown');

            // 2. If check image exists, decrypt and attach it
            if ($checkImagePath) {
                $attachmentResult = $this->attachCheckImage($checkImagePath, $transactionId, $gift['gift_id'] ?? null);

                if (!$attachmentResult['success']) {
                    Log::warning('Failed to attach check image to Intacct gift', [
                        'gift_id' => $gift['gift_id'] ?? 'unknown',
                        'transaction_id' => $transactionId,
                        'error' => $attachmentResult['error'] ?? 'Unknown'
                    ]);

                    // Don't fail the entire sync, just log the attachment failure
                }

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'attachment_uploaded' => $attachmentResult['success'] ?? false
                ];
            }

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'attachment_uploaded' => false
            ];

        } catch (\Exception $e) {
            Log::error('Gift sync with attachment exception', [
                'gift_id' => $gift['gift_id'] ?? 'unknown',
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
     * Decrypt and attach check image to Intacct transaction
     */
    private function attachCheckImage(string $checkImagePath, string $transactionId, ?string $giftId): array
    {
        $tempPath = null;

        try {
            // NOTE: This method requires Phase 3 (Encryption) to be implemented first
            // The following methods need to be added to CheckImageCacheService:
            // - decryptFile()
            // - getFileMetadata()

            // Decrypt file from encrypted storage
            if (!method_exists($this->imageCacheService, 'decryptFile')) {
                return [
                    'success' => false,
                    'error' => 'Encryption service not implemented. Please implement Phase 3 first.'
                ];
            }

            $decryptResult = $this->imageCacheService->decryptFile($checkImagePath);

            if (!$decryptResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to decrypt check image: ' . ($decryptResult['error'] ?? 'Unknown')
                ];
            }

            $decryptedContents = $decryptResult['contents'];

            // Get metadata
            $metadata = $this->imageCacheService->getFileMetadata($checkImagePath);

            if (!$metadata) {
                return [
                    'success' => false,
                    'error' => 'Check image metadata not found'
                ];
            }

            // Validate file integrity before uploading
            $tempPath = tempnam(sys_get_temp_dir(), 'intacct_attach_');
            file_put_contents($tempPath, $decryptedContents);
            $checksum = hash_file('sha256', $tempPath);

            if (!hash_equals($metadata['checksum'], $checksum)) {
                Log::error('File integrity check failed before Intacct upload', [
                    'gift_id' => $giftId,
                    'expected' => $metadata['checksum'],
                    'actual' => $checksum
                ]);

                return [
                    'success' => false,
                    'error' => 'File integrity verification failed'
                ];
            }

            // Upload to Intacct
            $attachmentResult = $this->apiService->createAttachment(
                $metadata['original_name'],
                $decryptedContents,
                'GLTRANSACTION', // Adjust based on your Intacct object type
                $transactionId
            );

            // Log the upload attempt
            if (class_exists(CheckImageAuditLog::class)) {
                CheckImageAuditLog::logAccess('uploaded_to_intacct', basename($checkImagePath), $giftId, [
                    'transaction_id' => $transactionId,
                    'success' => $attachmentResult['success'],
                    'file_size' => strlen($decryptedContents)
                ]);
            }

            return $attachmentResult;

        } catch (\Exception $e) {
            Log::error('Check image attachment exception', [
                'gift_id' => $giftId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } finally {
            // Always clean up temp file
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
