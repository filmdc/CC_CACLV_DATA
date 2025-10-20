<?php

namespace App\Services;

use App\Models\DonorPerfectSetting;
use App\Models\DonorPerfectGiftCache;
use App\Models\DonorPerfectCodeMapping;
use App\Models\SageIntacctSetting;
use Illuminate\Support\Facades\Log;
use App\Services\CheckImageCacheService;

class DonorPerfectGiftService
{
    private ?DonorPerfectApiService $apiService = null;
    private ?DonorPerfectSetting $setting = null;

    /**
     * Constructor - setting is loaded on demand
     */
    public function __construct()
    {
        $this->setting = DonorPerfectSetting::getActive();

        if ($this->setting && $this->setting->isConfigured()) {
            $this->apiService = new DonorPerfectApiService($this->setting);
        }
    }

    /**
     * Check if DonorPerfect is configured
     */
    public function isConfigured(): bool
    {
        return $this->setting && $this->setting->isConfigured();
    }

    /**
     * Get gifts with caching - checks cache first, then calls API if needed
     *
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @param array $fields Optional field names to retrieve
     * @return array Gift data with metadata
     */
    public function getCachedGifts(string $startDate, string $endDate, array $fields = []): array
    {
        try {
            // Check if API is configured
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'cached' => false,
                    'error' => 'DonorPerfect API is not configured',
                    'gifts' => [],
                    'count' => 0,
                ];
            }

            // Use configured fields if none provided
            $fieldsToUse = !empty($fields) ? $fields : $this->setting->getSelectedFields();

            // Try to get from cache
            $cached = DonorPerfectGiftCache::getCached($startDate, $endDate, $fieldsToUse);

            if ($cached && $cached->isValid()) {
                Log::info('DonorPerfect: Using cached gift data', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'record_count' => $cached->record_count,
                    'expires_at' => $cached->expires_at
                ]);

                return [
                    'success' => true,
                    'cached' => true,
                    'gifts' => $cached->gift_data,
                    'count' => $cached->record_count,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'cached_at' => $cached->created_at,
                    'expires_at' => $cached->expires_at,
                ];
            }

            // Cache miss or expired - fetch fresh data
            Log::info('DonorPerfect: Cache miss, fetching from API', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            return $this->refreshGifts($startDate, $endDate, $fieldsToUse);

        } catch (\Exception $e) {
            Log::error('DonorPerfect getCachedGifts exception', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's an authentication exception (code 401)
            $isAuthError = $e->getCode() === 401;

            return [
                'success' => false,
                'cached' => false,
                'error' => $e->getMessage(),
                'auth_error' => $isAuthError,
                'gifts' => [],
                'count' => 0,
            ];
        }
    }

    /**
     * Force fresh API call and update cache
     *
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @param array $fields Optional field names to retrieve
     * @return array Gift data with metadata
     */
    public function refreshGifts(string $startDate, string $endDate, array $fields = []): array
    {
        try {
            // Check if API is configured
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'cached' => false,
                    'error' => 'DonorPerfect API is not configured',
                    'gifts' => [],
                    'count' => 0,
                ];
            }

            // Use configured fields if none provided
            $fieldsToUse = !empty($fields) ? $fields : $this->setting->getSelectedFields();

            // Fetch from API
            $apiResult = $this->apiService->getGifts($startDate, $endDate, $fieldsToUse);

            if (isset($apiResult['error'])) {
                Log::error('DonorPerfect API returned error', [
                    'error' => $apiResult['error'],
                    'auth_error' => $apiResult['auth_error'] ?? false,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                return [
                    'success' => false,
                    'cached' => false,
                    'error' => $apiResult['error'],
                    'auth_error' => $apiResult['auth_error'] ?? false,
                    'gifts' => [],
                    'count' => 0,
                ];
            }

            // Extract gifts from API result
            $gifts = $apiResult['gifts'] ?? [];

            // Transform data to user-friendly format
            $transformedGifts = $this->transformGiftData($gifts);

            // Cache check images if IMAGE_CHECK field is present
            $cachingEnabled = env('ENABLE_CHECK_IMAGE_CACHING', false);
            Log::info('Check image caching check', [
                'enabled' => $cachingEnabled,
                'gift_count' => count($transformedGifts)
            ]);

            if ($cachingEnabled) {
                $hasImageCheckField = collect($transformedGifts)->contains(function ($gift) {
                    return array_key_exists('IMAGE_CHECK', $gift);
                });

                Log::info('IMAGE_CHECK field detection', [
                    'has_image_check_field' => $hasImageCheckField,
                    'sample_gift_keys' => !empty($transformedGifts) ? array_keys($transformedGifts[0]) : []
                ]);

                if ($hasImageCheckField) {
                    Log::info('Starting check image caching...');

                    $imageCacheService = new CheckImageCacheService();
                    $cacheResult = $imageCacheService->cacheImagesFromGifts($transformedGifts);

                    Log::info('Check image caching completed', [
                        'cached' => $cacheResult['cached'],
                        'failed' => $cacheResult['failed'],
                        'skipped' => $cacheResult['skipped']
                    ]);

                    // Update transformed gifts with cached URLs
                    $transformedGifts = $cacheResult['gifts'];
                }
            }

            // Store in cache
            $cacheDuration = $this->setting->cache_duration ?? 60;
            $cached = DonorPerfectGiftCache::storeCache(
                $startDate,
                $endDate,
                $fieldsToUse,
                $transformedGifts,
                $cacheDuration
            );

            Log::info('DonorPerfect: Fresh data cached successfully', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'record_count' => count($transformedGifts),
                'cache_duration_minutes' => $cacheDuration
            ]);

            return [
                'success' => true,
                'cached' => false,
                'gifts' => $transformedGifts,
                'count' => count($transformedGifts),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fetched_at' => now(),
                'expires_at' => $cached->expires_at,
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect refreshGifts exception', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'cached' => false,
                'error' => $e->getMessage(),
                'gifts' => [],
                'count' => 0,
            ];
        }
    }

    /**
     * Clear expired cache entries
     */
    public function clearCache(): array
    {
        try {
            $deletedCount = DonorPerfectGiftCache::clearExpired();

            Log::info('DonorPerfect: Cleared expired cache entries', [
                'deleted_count' => $deletedCount
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "Cleared {$deletedCount} expired cache entries"
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect clearCache exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'deleted_count' => 0
            ];
        }
    }

    /**
     * Clear all cache entries
     */
    public function clearAllCache(): array
    {
        try {
            $deletedCount = DonorPerfectGiftCache::clearAll();

            Log::info('DonorPerfect: Cleared all cache entries', [
                'deleted_count' => $deletedCount
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "Cleared all {$deletedCount} cache entries"
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect clearAllCache exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'deleted_count' => 0
            ];
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        try {
            $stats = DonorPerfectGiftCache::getStatistics();

            return [
                'success' => true,
                'statistics' => $stats
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect getCacheStatistics exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'statistics' => []
            ];
        }
    }

    /**
     * Get gifts for a date range with optional search filter
     * Returns a collection that can be paginated by the caller
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $search Optional search term
     * @return \Illuminate\Support\Collection
     */
    public function getGifts($startDate, $endDate, string $search = '')
    {
        try {
            // Convert Carbon to string format
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');

            // Get cached gifts
            $result = $this->getCachedGifts($startDateStr, $endDateStr);

            if (!$result['success']) {
                Log::warning('DonorPerfect getGifts failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return collect([]);
            }

            $gifts = collect($result['gifts'] ?? []);

            // Apply search filter if provided
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $gifts = $gifts->filter(function ($gift) use ($searchLower) {
                    return str_contains(strtolower($gift['donor_name'] ?? ''), $searchLower)
                        || str_contains(strtolower($gift['campaign'] ?? ''), $searchLower)
                        || str_contains(strtolower($gift['amount'] ?? ''), $searchLower)
                        || str_contains(strtolower($gift['payment_type'] ?? ''), $searchLower);
                });
            }

            return $gifts;

        } catch (\Exception $e) {
            Log::error('DonorPerfect getGifts exception', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trace' => $e->getTraceAsString()
            ]);

            return collect([]);
        }
    }

    /**
     * Get gifts with full metadata including error information
     * Returns array with success flag, gifts collection, and error details
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $search Optional search term
     * @return array
     */
    public function getGiftsWithMetadata($startDate, $endDate, string $search = '')
    {
        try {
            // Convert Carbon to string format
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');

            // Get cached gifts
            $result = $this->getCachedGifts($startDateStr, $endDateStr);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Unknown error',
                    'auth_error' => $result['auth_error'] ?? false,
                    'gifts' => collect([]),
                ];
            }

            $gifts = collect($result['gifts'] ?? []);

            // Apply search filter if provided
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $gifts = $gifts->filter(function ($gift) use ($searchLower) {
                    return str_contains(strtolower($gift['donor_name'] ?? ''), $searchLower)
                        || str_contains(strtolower($gift['campaign'] ?? ''), $searchLower)
                        || str_contains(strtolower($gift['amount'] ?? ''), $searchLower)
                        || str_contains(strtolower($gift['payment_type'] ?? ''), $searchLower);
                });
            }

            // Convert amount strings to floats for calculations
            $gifts = $gifts->map(function ($gift) {
                if (isset($gift['amount']) && is_string($gift['amount'])) {
                    $gift['amount'] = floatval($gift['amount']);
                }
                return $gift;
            });

            return [
                'success' => true,
                'gifts' => $gifts,
                'auth_error' => false,
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect getGiftsWithMetadata exception', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's an authentication exception (code 401)
            $isAuthError = $e->getCode() === 401;

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'auth_error' => $isAuthError,
                'gifts' => collect([]),
            ];
        }
    }

    /**
     * Transform raw API gift data to user-friendly format
     */
    private function transformGiftData(array $rawGifts): array
    {
        $transformed = [];

        foreach ($rawGifts as $gift) {
            $transformedGift = [];

            foreach ($gift as $field => $value) {
                // Format values based on field type, but keep snake_case keys
                $formattedValue = $this->formatFieldValue($field, $value);
                $transformedGift[$field] = $formattedValue;
            }

            // Ensure we have a donor_name field (combination of first_name and last_name)
            if (!isset($transformedGift['donor_name'])) {
                $firstName = $transformedGift['first_name'] ?? '';
                $lastName = $transformedGift['last_name'] ?? '';
                $organization = $transformedGift['organization'] ?? '';

                if (!empty($organization)) {
                    $transformedGift['donor_name'] = $organization;
                } else {
                    $transformedGift['donor_name'] = trim($firstName . ' ' . $lastName);
                }
            }

            // Convert gift_amount to amount if needed
            if (isset($transformedGift['gift_amount']) && !isset($transformedGift['amount'])) {
                $transformedGift['amount'] = $transformedGift['gift_amount'];
            }

            $transformed[] = $transformedGift;
        }

        return $transformed;
    }

    /**
     * Convert API field names to user-friendly names
     */
    private function convertFieldName(string $field): string
    {
        // Map of common DonorPerfect field names to friendly names
        $fieldMap = [
            'gift_id' => 'Gift ID',
            'donor_id' => 'Donor ID',
            'gift_date' => 'Gift Date',
            'gift_amount' => 'Amount',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'zip' => 'ZIP Code',
            'phone' => 'Phone',
            'campaign' => 'Campaign',
            'fund' => 'Fund',
            'gl_code' => 'GL Code',
            'solicit_code' => 'Solicitation Code',
            'sub_solicit_code' => 'Sub-Solicitation Code',
            'gift_type' => 'Gift Type',
            'payment_type' => 'Payment Type',
            'narrative' => 'Narrative',
            'acknowledgepref' => 'Acknowledgement Preference',
            'ty_letter_no' => 'Thank You Letter Number',
        ];

        return $fieldMap[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Format field values based on type
     */
    private function formatFieldValue(string $field, $value): mixed
    {
        // Format currency fields
        if (in_array($field, ['gift_amount', 'amount'])) {
            return is_numeric($value) ? number_format((float) $value, 2, '.', ',') : $value;
        }

        // Format date fields
        if (str_contains($field, '_date') || $field === 'date') {
            try {
                $date = \Carbon\Carbon::parse($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // Return value as-is for other fields
        return $value;
    }

    /**
     * Export gifts to CSV format
     * Exports only the filtered gifts based on date range and search
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $search Optional search term
     * @param bool $useIntacctCodes Whether to use mapped Intacct codes
     * @return string CSV content
     */
    public function exportToCsv($startDate, $endDate, string $search = '', bool $useIntacctCodes = false): string
    {
        // Get filtered gifts
        $gifts = $this->getGifts($startDate, $endDate, $search);

        // Convert collection to array
        $giftsArray = $gifts->toArray();

        // Use export service to generate CSV with all fields
        $exportService = new DonorPerfectExportService();
        return $exportService->exportToCsv($giftsArray, [], $useIntacctCodes);
    }

    /**
     * Export gifts to Excel format
     * Exports only the filtered gifts based on date range and search
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $search Optional search term
     * @param bool $useIntacctCodes Whether to use mapped Intacct codes
     * @return string File content
     */
    public function exportToExcel($startDate, $endDate, string $search = '', bool $useIntacctCodes = false): string
    {
        // Get filtered gifts
        $gifts = $this->getGifts($startDate, $endDate, $search);

        // Convert collection to array
        $giftsArray = $gifts->toArray();

        // Use export service to generate Excel with all fields
        $exportService = new DonorPerfectExportService();
        $filePath = $exportService->exportToExcel($giftsArray, [], $useIntacctCodes);

        // Read file content and return
        $content = file_get_contents($filePath);

        // Clean up temp file
        unlink($filePath);

        return $content;
    }

    /**
     * Export gifts to PDF format
     * Exports only the filtered gifts based on date range and search
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $search Optional search term
     * @param bool $useIntacctCodes Whether to use mapped Intacct codes
     * @return string File content
     */
    public function exportToPdf($startDate, $endDate, string $search = '', bool $useIntacctCodes = false): string
    {
        // Get filtered gifts
        $gifts = $this->getGifts($startDate, $endDate, $search);

        // Convert collection to array
        $giftsArray = $gifts->toArray();

        // Use export service to generate PDF with all fields
        $exportService = new DonorPerfectExportService();
        $filePath = $exportService->exportToPdf($giftsArray, [], $useIntacctCodes);

        // Read file content and return
        $content = file_get_contents($filePath);

        // Clean up temp file
        unlink($filePath);

        return $content;
    }

    /**
     * Get the API service instance
     */
    public function getApiService(): DonorPerfectApiService
    {
        return $this->apiService;
    }

    /**
     * Get the setting instance
     */
    public function getSetting(): DonorPerfectSetting
    {
        return $this->setting;
    }

    /**
     * Discover and sync codes from recent gifts
     * Extracts unique values from code fields and creates/updates mappings
     *
     * @param int $days Number of days to look back (default: 90)
     * @return array Statistics about discovered codes
     */
    public function discoverAndSyncCodes(int $days = 90): array
    {
        try {
            Log::info('DonorPerfect: Starting code discovery', ['days' => $days]);

            // Calculate date range
            $endDate = now();
            $startDate = now()->subDays($days);

            // Get gifts from the specified period
            $result = $this->getCachedGifts(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!$result['success']) {
                Log::error('DonorPerfect: Code discovery failed - could not get gifts', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to retrieve gifts',
                    'total' => 0,
                    'new' => 0,
                    'by_type' => [],
                ];
            }

            $gifts = $result['gifts'] ?? [];
            $stats = [
                'total' => 0,
                'new' => 0,
                'by_type' => [
                    'gl_code' => ['total' => 0, 'new' => 0],
                    'campaign' => ['total' => 0, 'new' => 0],
                    'solicit_code' => ['total' => 0, 'new' => 0],
                    'sub_solicit_code' => ['total' => 0, 'new' => 0],
                ],
            ];

            // Field types to extract
            $fieldTypes = ['gl_code', 'campaign', 'solicit_code', 'sub_solicit_code'];

            // Extract unique codes from each field type
            foreach ($fieldTypes as $fieldType) {
                $uniqueCodes = collect($gifts)
                    ->pluck($fieldType)
                    ->filter(function ($value) {
                        return !empty($value) && $value !== null && $value !== '';
                    })
                    ->unique()
                    ->values();

                Log::info('DonorPerfect: Discovered codes', [
                    'field_type' => $fieldType,
                    'count' => $uniqueCodes->count()
                ]);

                // Process each unique code
                foreach ($uniqueCodes as $code) {
                    $code = trim($code);
                    if (empty($code)) {
                        continue;
                    }

                    // Check if mapping exists
                    $existing = DonorPerfectCodeMapping::where('field_type', $fieldType)
                        ->where('dp_code', $code)
                        ->first();

                    if ($existing) {
                        // Update last_seen_at
                        $existing->update(['last_seen_at' => now()]);
                        $stats['by_type'][$fieldType]['total']++;
                    } else {
                        // Create new mapping
                        DonorPerfectCodeMapping::create([
                            'field_type' => $fieldType,
                            'dp_code' => $code,
                            'is_mapped' => false,
                            'is_archived' => false,
                            'first_seen_at' => now(),
                            'last_seen_at' => now(),
                        ]);
                        $stats['by_type'][$fieldType]['total']++;
                        $stats['by_type'][$fieldType]['new']++;
                        $stats['new']++;
                    }

                    $stats['total']++;
                }
            }

            Log::info('DonorPerfect: Code discovery completed', $stats);

            return [
                'success' => true,
                'total' => $stats['total'],
                'new' => $stats['new'],
                'by_type' => $stats['by_type'],
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect: Code discovery exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'total' => 0,
                'new' => 0,
                'by_type' => [],
            ];
        }
    }

    /**
     * Get display value for a code with optional Intacct mapping
     *
     * @param string $fieldType Field type (gl_code, campaign, etc.)
     * @param string $dpCode DonorPerfect code value
     * @param string $format Display format: 'dp_only', 'intacct_only', 'both'
     * @return string Formatted display string
     */
    public function getCodeDisplayValue(string $fieldType, string $dpCode, string $format = 'both'): string
    {
        // Return early if code is empty
        if (empty($dpCode)) {
            return '';
        }

        // Check if code mapping is enabled (use first() instead of getActive() to allow mapping without API activation)
        $intacctSetting = SageIntacctSetting::first();
        $codeMappingEnabled = $intacctSetting && $intacctSetting->isCodeMappingEnabled();

        // If code mapping is disabled or format is dp_only, return DP code only
        if (!$codeMappingEnabled || $format === 'dp_only') {
            return $dpCode;
        }

        // Get the Intacct code mapping
        $intacctCode = DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

        // Format based on requested format
        switch ($format) {
            case 'intacct_only':
                return $intacctCode ?? $dpCode;

            case 'both':
                if ($intacctCode) {
                    return "{$dpCode} → {$intacctCode}";
                }
                return "{$dpCode} (not mapped)";

            default:
                return $dpCode;
        }
    }

    /**
     * Transform gift for export by replacing DP codes with Intacct codes
     *
     * @param array $gift Gift data array
     * @param bool $useIntacctCodes Whether to use Intacct codes
     * @return array Transformed gift array
     */
    public function transformGiftForExport(array $gift, bool $useIntacctCodes = false): array
    {
        // If not using Intacct codes, return gift as-is
        if (!$useIntacctCodes) {
            return $gift;
        }

        // Check if code mapping is enabled (use first() instead of getActive() to allow mapping without API activation)
        $intacctSetting = SageIntacctSetting::first();
        if (!$intacctSetting || !$intacctSetting->isCodeMappingEnabled()) {
            Log::info('DonorPerfect: Code mapping disabled, returning original gift');
            return $gift;
        }

        // Clone the gift array so we don't modify the original
        $transformedGift = $gift;

        // Field types to transform
        $fieldTypes = ['gl_code', 'campaign', 'solicit_code', 'sub_solicit_code'];

        foreach ($fieldTypes as $fieldType) {
            if (isset($gift[$fieldType]) && !empty($gift[$fieldType])) {
                $dpCode = $gift[$fieldType];
                $intacctCode = DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

                // Replace with Intacct code if mapping exists, otherwise keep original
                if ($intacctCode) {
                    $transformedGift[$fieldType] = $intacctCode;
                    Log::debug('DonorPerfect: Transformed code', [
                        'field_type' => $fieldType,
                        'dp_code' => $dpCode,
                        'intacct_code' => $intacctCode
                    ]);
                }
            }
        }

        return $transformedGift;
    }
}
