<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Services\CheckImageCacheService;
use App\Services\SageIntacctApiService;
use App\Services\SageIntacctSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestOtherReceipt extends Command
{
    protected $signature = 'intacct:test-other-receipt
                            {--with-attachment : Include a test attachment}
                            {--attachment-file= : Path to image file to attach (requires --with-attachment)}
                            {--amount=100.00 : Gift amount}';

    protected $description = 'Create a test Other Receipt in Sage Intacct to verify attachment functionality';

    public function handle()
    {
        $this->info('Creating test Other Receipt in Sage Intacct...');
        $this->newLine();

        // Get Sage Intacct settings
        $setting = SageIntacctSetting::getActive();

        if (!$setting) {
            $this->error('Sage Intacct is not configured. Please configure Sage Intacct settings first.');
            return Command::FAILURE;
        }

        if (!$setting->isConfigured()) {
            $this->error('Sage Intacct configuration is incomplete. Please ensure all required credentials are set.');
            return Command::FAILURE;
        }

        // Create the API service and sync service
        $apiService = new SageIntacctApiService($setting);
        $syncService = new SageIntacctSyncService($apiService, $setting);

        $withAttachment = $this->option('with-attachment');
        $amount = $this->option('amount');

        // Create test gift data
        $testGift = [
            'gift_id' => 'TEST-' . now()->timestamp,
            'gift_date' => now()->format('Y-m-d'),
            'amount' => $amount,
            'first_name' => 'Test',
            'last_name' => 'Donor',
            'donor_id' => 'TEST-DONOR-001',
            'campaign' => 'P1000', // ADMIN-Administration
            'sub_solicit_code' => 'S10999', // AS999-Undesignated Service
            'solicit_code' => 'F8000',
            'gl_code' => '40100',
            'payment_type' => 'Check',
            'gift_narrative' => 'Test donation with attachment verification',
        ];

        // If with-attachment flag is set, add a sample image
        if ($withAttachment) {
            $attachmentFile = $this->option('attachment-file');

            if ($attachmentFile) {
                // Use provided file
                if (!file_exists($attachmentFile)) {
                    $this->error("Attachment file not found: {$attachmentFile}");
                    return Command::FAILURE;
                }

                $this->info("Using attachment file: {$attachmentFile}");

                // Cache the test image in the check_images directory
                $giftId = $testGift['gift_id'];
                $testImageFilename = $this->cacheTestImage($attachmentFile, $giftId);

                if (!$testImageFilename) {
                    $this->error('Failed to cache test image');
                    return Command::FAILURE;
                }

                $this->info("Cached test image as: {$testImageFilename}");
                $testGift['IMAGE_CHECK_URL'] = $testImageFilename;

            } else {
                // Look for cached check images
                $this->info('Looking for a cached check image to attach...');

                $checkImagesPath = storage_path('app/check_images');
                $encryptedFiles = glob($checkImagesPath . '/*.enc');

                if (empty($encryptedFiles)) {
                    $this->warn('No cached check images found and no --attachment-file provided.');
                    $this->info('Use --attachment-file=/path/to/image.jpg to provide a test image.');
                    $this->newLine();
                    if (!$this->confirm('Continue without attachment?')) {
                        return Command::SUCCESS;
                    }
                    $withAttachment = false;
                } else {
                    // Get the first encrypted file
                    $firstEncFile = $encryptedFiles[0];
                    $filename = basename($firstEncFile, '.enc');

                    // Extract gift_id from filename (format: {gift_id}_{original_filename}.enc)
                    $parts = explode('_', $filename, 2);
                    $originalGiftId = $parts[0];

                    $this->info("Found cached check image from gift #{$originalGiftId}");
                    $testGift['IMAGE_CHECK_URL'] = $filename;
                    $testGift['_cached_image_gift_id'] = $originalGiftId;
                }
            }
        }

        $this->newLine();
        $this->info('Test Gift Details:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Gift ID', $testGift['gift_id']],
                ['Amount', '$' . $testGift['amount']],
                ['Date', $testGift['gift_date']],
                ['Donor', $testGift['first_name'] . ' ' . $testGift['last_name']],
                ['Payment Type', $testGift['payment_type']],
                ['GL Account', $testGift['gl_code']],
                ['Program (locationid)', $testGift['campaign']],
                ['Service (departmentid)', $testGift['sub_solicit_code']],
                ['Fund (projectid)', $testGift['solicit_code']],
                ['With Attachment', $withAttachment ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
        if (!$this->confirm('Proceed with creating this test Other Receipt in Sage Intacct?')) {
            $this->warn('Test cancelled by user');
            return Command::SUCCESS;
        }

        try {
            $this->info('Syncing test gift to Sage Intacct...');

            // Step 1: Upload check image attachment if provided
            $fileData = null;
            if ($withAttachment) {
                $this->info('Uploading check image attachment...');
                $fileData = $this->uploadTestAttachment($apiService, $setting, $testGift);

                if ($fileData) {
                    $this->info('✓ Attachment uploaded successfully: SUPDOC ' . $fileData['supdocid']);
                } else {
                    $this->warn('Failed to upload attachment, continuing without it');
                }
            }

            // Step 2: Transform gift to Other Receipt format
            $this->info('Transforming gift data to Other Receipt format...');
            $receiptData = $this->transformTestGiftToOtherReceipt($setting, $testGift, $fileData);

            if (!$receiptData) {
                $this->error('Failed to transform gift data');
                return Command::FAILURE;
            }

            // Step 3: Create Other Receipt in Sage Intacct
            $this->info('Creating Other Receipt in Sage Intacct...');
            $this->info('Receipt Data: ' . json_encode($receiptData, JSON_PRETTY_PRINT));
            $result = $apiService->createOtherReceipt($receiptData);

            $this->newLine();
            if ($result['success']) {
                $this->info('✓ Test Other Receipt created successfully!');
                $this->newLine();

                $this->info('Result Details:');
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Intacct Record Key', $result['key'] ?? 'N/A'],
                        ['Reference ID', $testGift['gift_id']],
                        ['Attachment Included', $withAttachment ? 'Yes' : 'No'],
                        ['Attachment SUPDOCID', $fileData['supdocid'] ?? 'N/A'],
                    ]
                );

                $this->newLine();
                $this->info('Please verify in Sage Intacct:');
                $this->line('1. Navigate to: Cash Management → Other Receipts');
                $this->line('2. Search for reference ID: ' . $testGift['gift_id']);
                $this->line('3. Verify the receipt details match the test data');
                if ($withAttachment) {
                    $this->line('4. Check that the attachment is visible and displays correctly');
                }

            } else {
                $this->error('✗ Failed to create test Other Receipt');
                $this->newLine();
                $this->error('Error: ' . ($result['error'] ?? 'Unknown error'));

                if (isset($result['intacct_error'])) {
                    $this->newLine();
                    $this->error('Intacct Error Details:');
                    $this->line($result['intacct_error']);
                }

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Exception occurred: ' . $e->getMessage());
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());

            Log::error('Test Other Receipt failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'test_gift' => $testGift,
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Cache a test image file in the check_images directory with encryption
     *
     * @param string $imagePath Path to the image file
     * @param string $giftId Gift ID to use in the filename
     * @return string|null The cached filename (without .enc extension) or null on failure
     */
    private function cacheTestImage(string $imagePath, string $giftId): ?string
    {
        try {
            // Read the image file
            $imageContents = file_get_contents($imagePath);

            if ($imageContents === false) {
                $this->error("Failed to read image file: {$imagePath}");
                return null;
            }

            // Generate filename: {giftid}_{original_filename}
            $originalFilename = basename($imagePath);
            $cacheFilename = $giftId . '_' . $originalFilename;

            // Encrypt the file contents
            $encryptedContents = Crypt::encryptString($imageContents);

            // Store encrypted file
            $storagePath = 'check_images/' . $cacheFilename . '.enc';
            Storage::disk('local')->put($storagePath, $encryptedContents);

            // Create metadata
            $metadata = [
                'mime_type' => mime_content_type($imagePath) ?? 'application/octet-stream',
                'size' => strlen($imageContents),
                'checksum' => hash('sha256', $imageContents),
                'original_name' => $originalFilename,
                'created_at' => now()->toIso8601String(),
                'test_file' => true,
            ];

            // Store metadata
            $metadataPath = 'check_images/metadata/' . $cacheFilename . '.json';
            Storage::disk('local')->put($metadataPath, json_encode($metadata));

            return $cacheFilename;

        } catch (\Exception $e) {
            $this->error("Failed to cache test image: " . $e->getMessage());
            Log::error('Failed to cache test image', [
                'error' => $e->getMessage(),
                'path' => $imagePath,
                'gift_id' => $giftId,
            ]);
            return null;
        }
    }

    /**
     * Upload test attachment to Sage Intacct
     */
    private function uploadTestAttachment($apiService, $setting, array $giftData): ?array
    {
        try {
            // Get the cached image
            $cacheFilename = basename($giftData['IMAGE_CHECK_URL']);
            $storagePath = 'check_images/' . str_replace('.enc', '', $cacheFilename);

            $checkImageService = new CheckImageCacheService;
            $decryptResult = $checkImageService->decryptFile($storagePath);

            if (!$decryptResult['success']) {
                $this->error('Failed to decrypt check image: ' . ($decryptResult['error'] ?? 'Unknown error'));
                return null;
            }

            $fileContents = $decryptResult['contents'];
            $giftId = $giftData['gift_id'];

            // Prepare file data
            $fullFilename = 'Gift_' . $giftId . '_test_check.jpg';
            $description = "Test check image for Gift #{$giftId} - Test Donor - \$" . ($giftData['amount'] ?? '0.00');

            // Create SUPDOC attachment
            $supdocResult = $apiService->createAttachment(
                $fullFilename,
                $fileContents,
                'jpg',  // File extension, not MIME type
                $description,
                $setting->attachment_folder_name ?? 'DP_Sync_Attachments'
            );

            if ($supdocResult['success']) {
                return ['supdocid' => $supdocResult['supdocid']];
            }

            $this->error('Failed to create SUPDOC: ' . ($supdocResult['error'] ?? 'Unknown error'));
            return null;

        } catch (\Exception $e) {
            $this->error('Exception uploading attachment: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Transform test gift to Other Receipt format
     */
    private function transformTestGiftToOtherReceipt($setting, array $giftData, ?array $fileData): ?array
    {
        $firstName = $giftData['first_name'] ?? '';
        $lastName = $giftData['last_name'] ?? '';
        $payee = trim($firstName . ' ' . $lastName) ?: 'Anonymous Donor';

        $giftDate = $giftData['gift_date'];
        $depositDate = now()->format('Y-m-d');

        // Build account data
        $accountData = [];
        if ($setting->use_undeposited_funds) {
            $accountData['undepglaccountno'] = $setting->undeposited_gl_account ?? '10300';
        } else {
            $accountData['bankaccountid'] = $setting->bank_account_id;
            $accountData['depositdate'] = $depositDate;
        }

        // Build line item with all 4 dimensions
        $lineItem = [
            'glaccountno' => $giftData['gl_code'],
            'amount' => $giftData['amount'],
            'memo' => $payee,
            'locationid' => $giftData['campaign'],           // Program
            'departmentid' => $giftData['sub_solicit_code'], // Service
            'projectid' => $giftData['solicit_code'],        // Fund
            'classid' => '0',                                 // Restriction (Unrestricted)
        ];

        // Build receipt data
        $receiptData = array_merge([
            'paymentdate' => $giftDate,
            'payee' => $payee,
            'receiveddate' => $giftDate,
            'paymentmethod' => 'Check',
            'refid' => (string) $giftData['gift_id'],
            'description' => $giftData['gift_narrative'] ?? "Test Gift #{$giftData['gift_id']}",
            'receiptitems' => [$lineItem],
        ], $accountData);

        // Add SUPDOC if attachment was uploaded
        if ($fileData && isset($fileData['supdocid'])) {
            $receiptData['supdocid'] = $fileData['supdocid'];
        }

        return $receiptData;
    }
}
