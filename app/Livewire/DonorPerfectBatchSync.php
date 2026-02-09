<?php

namespace App\Livewire;

use App\Models\SageIntacctSyncFilter;
use App\Services\DonorPerfectGiftService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DonorPerfectBatchSync extends Component
{
    use WithPermissionChecks;

    public $selectedDate;

    public $selectedBatch = '';

    public $availableBatches = [];

    public $filteredOutGifts = [];

    public $loadingBatches = false;

    public $batchesLoaded = false;

    public $previewGifts = [];

    public $showPreview = false;

    public $showConfirmModal = false;

    public $syncInProgress = false;

    public $syncCompleted = false;

    public $syncResults = [];

    public $downloadUrl = '';

    public function mount()
    {
        $this->authorizeAction('donor_gifts.view');
        $this->selectedDate = Carbon::now()->format('Y-m-d');
        $this->loadBatchesForDate();
    }

    public function updatedSelectedDate()
    {
        $this->selectedBatch = '';
        $this->previewGifts = [];
        $this->filteredOutGifts = [];
        $this->showPreview = false;
        $this->syncCompleted = false;
        $this->downloadUrl = '';
        $this->batchesLoaded = false;
        $this->loadBatchesForDate();
    }

    public function updatedSelectedBatch()
    {
        $this->previewGifts = [];
        $this->filteredOutGifts = [];
        $this->showPreview = false;
        $this->syncCompleted = false;
        $this->downloadUrl = '';
    }

    /**
     * Load available batches for the selected date
     */
    public function loadBatchesForDate()
    {
        $this->availableBatches = [];
        $this->batchesLoaded = false;

        if (empty($this->selectedDate)) {
            return;
        }

        $this->loadingBatches = true;

        try {
            $giftService = app(DonorPerfectGiftService::class);
            $date = Carbon::parse($this->selectedDate);

            $result = $giftService->getGiftsWithMetadata($date, $date, '');

            if (! $result['success']) {
                $this->loadingBatches = false;
                $this->batchesLoaded = true;
                session()->flash('error', $result['error'] ?? 'Failed to load gifts');

                return;
            }

            $gifts = $result['gifts'];

            // Extract unique batch numbers (part before underscore)
            $batches = $gifts
                ->pluck('DP_CheckScan_Batch')
                ->filter()
                ->map(function ($batchId) {
                    // Extract batch number (part before underscore)
                    $parts = explode('_', $batchId);

                    return $parts[0] ?? null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $this->availableBatches = $batches;
            $this->loadingBatches = false;
            $this->batchesLoaded = true;

        } catch (\Exception $e) {
            $this->loadingBatches = false;
            $this->batchesLoaded = true;
            Log::error('DonorPerfectBatchSync: Failed to load batches', [
                'date' => $this->selectedDate,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load batches: '.$e->getMessage());
        }
    }

    /**
     * Load preview of gifts for selected date and batch
     */
    public function loadPreview()
    {
        if (empty($this->selectedDate) || empty($this->selectedBatch)) {
            session()->flash('error', 'Please select both a date and a batch');

            return;
        }

        try {
            $giftService = app(DonorPerfectGiftService::class);
            $date = Carbon::parse($this->selectedDate);

            $result = $giftService->getGiftsWithMetadata($date, $date, '');

            if (! $result['success']) {
                session()->flash('error', $result['error'] ?? 'Failed to load gifts');

                return;
            }

            // Filter to selected batch (matching prefix before underscore)
            $batchGifts = $result['gifts']
                ->filter(function ($gift) {
                    $batchId = $gift['DP_CheckScan_Batch'] ?? '';
                    if (empty($batchId)) {
                        return false;
                    }
                    $parts = explode('_', $batchId);
                    $batchPrefix = $parts[0] ?? '';

                    return $batchPrefix === $this->selectedBatch;
                })
                ->values()
                ->toArray();

            // Apply sync filters to separate syncable gifts from filtered-out gifts
            $this->previewGifts = [];
            $this->filteredOutGifts = [];

            foreach ($batchGifts as $gift) {
                $filterResult = SageIntacctSyncFilter::shouldFilterGift($gift);

                if ($filterResult['should_filter']) {
                    // Add filter reason to the gift for display
                    $gift['_filter_reason'] = $filterResult['reason'];
                    $this->filteredOutGifts[] = $gift;
                } else {
                    $this->previewGifts[] = $gift;
                }
            }

            $this->showPreview = true;

            // Show warning if any gifts were filtered
            if (count($this->filteredOutGifts) > 0) {
                session()->flash('info', count($this->filteredOutGifts).' gift(s) will be excluded by sync filters.');
            }

        } catch (\Exception $e) {
            Log::error('DonorPerfectBatchSync: Failed to load preview', [
                'date' => $this->selectedDate,
                'batch' => $this->selectedBatch,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load preview: '.$e->getMessage());
        }
    }

    /**
     * Show confirmation modal before sync
     */
    public function confirmSync()
    {
        if (empty($this->previewGifts)) {
            session()->flash('error', 'No gifts to sync. Please load preview first.');

            return;
        }

        $this->showConfirmModal = true;
    }

    /**
     * Cancel sync confirmation
     */
    public function cancelSync()
    {
        $this->showConfirmModal = false;
    }

    /**
     * Perform the sync to Sage Intacct
     */
    public function performSync()
    {
        if (! $this->canAny(['sage.sync.perform', 'intacct.sync'])) {
            session()->flash('error', 'You do not have permission to sync to Sage Intacct.');

            return;
        }

        $this->showConfirmModal = false;
        $this->syncInProgress = true;

        try {
            $giftService = app(DonorPerfectGiftService::class);
            $date = Carbon::parse($this->selectedDate);

            // Get Sage Intacct settings
            $intacctSetting = \App\Models\SageIntacctSetting::getActive();

            if (! $intacctSetting) {
                throw new \Exception('Sage Intacct is not configured');
            }

            // Initialize API and sync services
            if ($intacctSetting->isXmlApi()) {
                $apiService = new \App\Services\SageIntacctApiService($intacctSetting);
                $syncService = new \App\Services\SageIntacctSyncService($apiService, $intacctSetting);
            } else {
                $userToken = auth()->user()->sageIntacctToken;

                if (! $userToken || ! $userToken->hasValidToken()) {
                    session()->flash('error', 'You must connect to Sage Intacct first.');
                    $this->syncInProgress = false;

                    return;
                }

                $apiService = new \App\Services\SageIntacctApiService($intacctSetting, $userToken);
                $syncService = new \App\Services\SageIntacctSyncService($apiService, $intacctSetting, $userToken);
            }

            // Perform sync with the preview gifts
            $syncResult = $syncService->syncGifts(
                $date,
                $date,
                auth()->id(),
                $this->previewGifts
            );

            $this->syncInProgress = false;

            if ($syncResult['success']) {
                $this->syncCompleted = true;
                $this->syncResults = $syncResult;

                // Generate CSV export
                $this->generateSyncExport();

                $synced = $syncResult['synced'] ?? 0;
                $failed = $syncResult['failed'] ?? 0;
                $skipped = $syncResult['skipped'] ?? 0;
                $alreadySynced = $syncResult['already_synced'] ?? 0;

                // Build a detailed message
                $messageParts = ["{$synced} synced"];
                if ($alreadySynced > 0) {
                    $messageParts[] = "{$alreadySynced} already synced (duplicates prevented)";
                }
                if ($skipped > 0) {
                    $messageParts[] = "{$skipped} skipped (missing mappings)";
                }
                if ($failed > 0) {
                    $messageParts[] = "{$failed} failed";
                }

                session()->flash('success', 'Batch sync completed: '.implode(', ', $messageParts));
            } else {
                throw new \Exception($syncResult['error'] ?? 'Sync failed');
            }

        } catch (\Exception $e) {
            $this->syncInProgress = false;
            Log::error('DonorPerfectBatchSync: Sync failed', [
                'date' => $this->selectedDate,
                'batch' => $this->selectedBatch,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Sync failed: '.$e->getMessage());
        }
    }

    /**
     * Generate CSV export of synced gifts
     */
    private function generateSyncExport()
    {
        try {
            $filename = 'batch_sync_'.$this->selectedDate.'_batch_'.$this->selectedBatch.'_'.time().'.csv';
            $filepath = storage_path('app/exports/'.$filename);

            // Ensure exports directory exists
            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $handle = fopen($filepath, 'w');

            // Write header
            fputcsv($handle, [
                'Gift ID',
                'Gift Date',
                'Amount',
                'Donor Name',
                'GL Code',
                'GL Code (Intacct)',
                'Campaign',
                'Location (Intacct)',
                'Solicit Code',
                'Project (Intacct)',
                'Sub-Solicit Code',
                'Service (Intacct)',
                'Payment Type',
                'Check Scan Batch',
                'Has Check Image',
                'Sync Status',
            ]);

            // Write gift data
            foreach ($this->previewGifts as $gift) {
                $hasCheckImage = ! empty($gift['IMAGE_CHECK']) ? 'Yes' : 'No';
                $donorName = trim(($gift['first_name'] ?? '').' '.($gift['last_name'] ?? ''));

                // Get mapped codes
                $glMapping = $this->getMappedCode($gift, 'gl_code');
                $campaignMapping = $this->getMappedCode($gift, 'campaign');
                $solicitMapping = $this->getMappedCode($gift, 'solicit_code');
                $subSolicitMapping = $this->getMappedCode($gift, 'sub_solicit_code');

                fputcsv($handle, [
                    $gift['gift_id'] ?? '',
                    $gift['gift_date'] ?? '',
                    $gift['amount'] ?? '',
                    $donorName ?: 'Anonymous',
                    $gift['gl_code'] ?? '',
                    $glMapping['code'] ?? '',
                    $gift['campaign'] ?? '',
                    $campaignMapping['code'] ?? '',
                    $gift['solicit_code'] ?? '',
                    $solicitMapping['code'] ?? '',
                    $gift['sub_solicit_code'] ?? '',
                    $subSolicitMapping['code'] ?? '',
                    $gift['payment_type'] ?? '',
                    $gift['DP_CheckScan_Batch'] ?? '',
                    $hasCheckImage,
                    'Synced',
                ]);
            }

            // Add summary section
            fputcsv($handle, []);
            fputcsv($handle, ['--- SYNC SUMMARY ---']);
            fputcsv($handle, ['Sync Date', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Gift Date', $this->selectedDate]);
            fputcsv($handle, ['Batch Number', $this->selectedBatch]);
            fputcsv($handle, ['Total Gifts', count($this->previewGifts)]);
            fputcsv($handle, ['Total Amount', '$'.number_format(collect($this->previewGifts)->sum('amount'), 2)]);
            fputcsv($handle, ['Gifts with Check Images', collect($this->previewGifts)->filter(fn ($g) => ! empty($g['IMAGE_CHECK']))->count()]);
            fputcsv($handle, ['Created', $this->syncResults['created'] ?? 0]);
            fputcsv($handle, ['Skipped', $this->syncResults['skipped'] ?? 0]);
            fputcsv($handle, ['Failed', $this->syncResults['failed'] ?? 0]);
            fputcsv($handle, ['Synced By', auth()->user()->name ?? 'Unknown']);

            fclose($handle);

            // Store filename in session for download
            session()->put('batch_sync_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            $this->downloadUrl = route('donor-gifts.batch-sync-download');

        } catch (\Exception $e) {
            Log::error('DonorPerfectBatchSync: Failed to generate export', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get total amount of preview gifts
     */
    public function getPreviewTotalProperty()
    {
        return collect($this->previewGifts)->sum('amount');
    }

    /**
     * Get count of gifts with check images
     */
    public function getCheckImageCountProperty()
    {
        return collect($this->previewGifts)->filter(fn ($g) => ! empty($g['IMAGE_CHECK']))->count();
    }

    /**
     * Check if Intacct is configured
     */
    public function getIntacctConfiguredProperty()
    {
        $intacctSetting = \App\Models\SageIntacctSetting::where('is_active', true)->first();

        return $intacctSetting && $intacctSetting->isConfigured();
    }

    /**
     * Check if code mapping is enabled
     */
    public function getCodeMappingEnabledProperty()
    {
        $intacctSetting = \App\Models\SageIntacctSetting::first();

        return $intacctSetting && $intacctSetting->isCodeMappingEnabled();
    }

    /**
     * Get the mapped Intacct code for a field
     */
    public function getMappedCode(array $gift, string $fieldType): array
    {
        $dpCode = $gift[$fieldType] ?? '';

        if (empty($dpCode)) {
            return ['code' => '', 'source' => null, 'blocked' => false, 'reason' => null];
        }

        // For solicit_code and sub_solicit_code, check conditional mappings first
        if (in_array($fieldType, ['solicit_code', 'sub_solicit_code'])) {
            $dpCampaign = $gift['campaign'] ?? '';
            $intacctLocation = null;

            if ($dpCampaign) {
                $intacctLocation = \App\Models\DonorPerfectCodeMapping::getMappingFor('campaign', $dpCampaign);
            }

            if ($intacctLocation) {
                // Check if conditional rules exist for this location + field type
                $hasConditionalRules = \App\Models\ConditionalCodeMapping::hasRulesForLocation(
                    $intacctLocation,
                    $fieldType
                );

                if ($hasConditionalRules) {
                    $conditionalCode = \App\Models\ConditionalCodeMapping::getMapping(
                        $intacctLocation,
                        $fieldType,
                        $dpCode
                    );

                    if ($conditionalCode) {
                        return ['code' => $conditionalCode, 'source' => 'conditional', 'blocked' => false, 'reason' => null];
                    } else {
                        // Rules exist but this code is NOT in the allowlist - will be BLOCKED during sync
                        $standardCode = \App\Models\DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

                        return [
                            'code' => $standardCode ?: $dpCode,
                            'source' => 'standard',
                            'blocked' => true,
                            'reason' => "Not allowed for location {$intacctLocation}",
                        ];
                    }
                }
            }
        }

        // Fall back to standard mapping (no conditional blocking)
        $standardCode = \App\Models\DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

        return ['code' => $standardCode ?: '', 'source' => $standardCode ? 'standard' : null, 'blocked' => false, 'reason' => null];
    }

    /**
     * Export the preview gifts to an Excel file for download
     */
    public function exportPreviewToExcel()
    {
        if (empty($this->previewGifts) && empty($this->filteredOutGifts)) {
            session()->flash('error', 'No preview data to export. Please load a preview first.');
            return;
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Batch Preview');

            // Header style
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];

            // Title row
            $sheet->setCellValue('A1', 'Batch ' . $this->selectedBatch . ' Preview - ' . Carbon::parse($this->selectedDate)->format('F j, Y'));
            $sheet->mergeCells('A1:L1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            // Summary row
            $totalAmount = collect($this->previewGifts)->sum('amount');
            $sheet->setCellValue('A2', 'Total: ' . count($this->previewGifts) . ' gifts | Amount: $' . number_format($totalAmount, 2));
            $sheet->mergeCells('A2:L2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 11, 'color' => ['rgb' => '6B7280']],
            ]);

            // Column headers (row 4)
            $headers = [
                'A' => 'Gift ID',
                'B' => 'Gift Date',
                'C' => 'Batch ID',
                'D' => 'Amount',
                'E' => 'Donor Name',
                'F' => 'GL Code',
                'G' => 'GL (Intacct)',
                'H' => 'Campaign',
                'I' => 'Location (Intacct)',
                'J' => 'Solicit Code',
                'K' => 'Project (Intacct)',
                'L' => 'Sub-Solicit',
                'M' => 'Service (Intacct)',
                'N' => 'Payment Type',
                'O' => 'Check Image',
            ];

            $headerRow = 4;
            foreach ($headers as $col => $label) {
                $sheet->setCellValue($col . $headerRow, $label);
            }
            $sheet->getStyle('A' . $headerRow . ':O' . $headerRow)->applyFromArray($headerStyle);

            // Data rows
            $row = $headerRow + 1;
            foreach ($this->previewGifts as $gift) {
                $donorName = trim(($gift['first_name'] ?? '') . ' ' . ($gift['last_name'] ?? ''));
                $glMapping = $this->getMappedCode($gift, 'gl_code');
                $campaignMapping = $this->getMappedCode($gift, 'campaign');
                $solicitMapping = $this->getMappedCode($gift, 'solicit_code');
                $subSolicitMapping = $this->getMappedCode($gift, 'sub_solicit_code');

                $sheet->setCellValue('A' . $row, $gift['gift_id'] ?? '');
                $sheet->setCellValue('B' . $row, $gift['gift_date'] ?? '');
                $sheet->setCellValue('C' . $row, $gift['DP_CheckScan_Batch'] ?? '');
                $sheet->setCellValue('D' . $row, floatval(str_replace(',', '', $gift['amount'] ?? 0)));
                $sheet->setCellValue('E' . $row, $donorName ?: 'Anonymous');
                $sheet->setCellValue('F' . $row, $gift['gl_code'] ?? '');
                $sheet->setCellValue('G' . $row, $glMapping['code'] ?? '');
                $sheet->setCellValue('H' . $row, $gift['campaign'] ?? '');
                $sheet->setCellValue('I' . $row, $campaignMapping['code'] ?? '');
                $sheet->setCellValue('J' . $row, $gift['solicit_code'] ?? '');
                $sheet->setCellValue('K' . $row, $solicitMapping['code'] ?? '');
                $sheet->setCellValue('L' . $row, $gift['sub_solicit_code'] ?? '');
                $sheet->setCellValue('M' . $row, $subSolicitMapping['code'] ?? '');
                $sheet->setCellValue('N' . $row, $gift['payment_type'] ?? '');
                $sheet->setCellValue('O' . $row, !empty($gift['IMAGE_CHECK']) ? 'Yes' : 'No');

                // Alternate row shading
                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                }

                $row++;
            }

            // Format amount column as currency
            $lastDataRow = $row - 1;
            if ($lastDataRow >= $headerRow + 1) {
                $sheet->getStyle('D' . ($headerRow + 1) . ':D' . $lastDataRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

                // Data borders
                $sheet->getStyle('A' . ($headerRow + 1) . ':O' . $lastDataRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
            }

            // Total row
            $sheet->setCellValue('C' . $row, 'Total:');
            $sheet->setCellValue('D' . $row, $totalAmount);
            $sheet->getStyle('C' . $row . ':D' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

            // Filtered out gifts sheet
            if (!empty($this->filteredOutGifts)) {
                $filteredSheet = $spreadsheet->createSheet();
                $filteredSheet->setTitle('Filtered Out');

                $filteredSheet->setCellValue('A1', 'Filtered Out Gifts (' . count($this->filteredOutGifts) . ')');
                $filteredSheet->mergeCells('A1:F1');
                $filteredSheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'B45309']],
                ]);

                $filteredHeaders = ['A' => 'Gift ID', 'B' => 'Amount', 'C' => 'Gift Type', 'D' => 'Donor', 'E' => 'Batch ID', 'F' => 'Filter Reason'];
                $fHeaderStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ];

                foreach ($filteredHeaders as $col => $label) {
                    $filteredSheet->setCellValue($col . '3', $label);
                }
                $filteredSheet->getStyle('A3:F3')->applyFromArray($fHeaderStyle);

                $fRow = 4;
                foreach ($this->filteredOutGifts as $gift) {
                    $donorName = trim(($gift['first_name'] ?? '') . ' ' . ($gift['last_name'] ?? ''));
                    $filteredSheet->setCellValue('A' . $fRow, $gift['gift_id'] ?? '');
                    $filteredSheet->setCellValue('B' . $fRow, floatval(str_replace(',', '', $gift['amount'] ?? 0)));
                    $filteredSheet->setCellValue('C' . $fRow, $gift['gift_type'] ?? '');
                    $filteredSheet->setCellValue('D' . $fRow, $donorName ?: 'Anonymous');
                    $filteredSheet->setCellValue('E' . $fRow, $gift['DP_CheckScan_Batch'] ?? '');
                    $filteredSheet->setCellValue('F' . $fRow, $gift['_filter_reason'] ?? 'Excluded by filter');
                    $fRow++;
                }

                $filteredSheet->getStyle('B4:B' . max($fRow - 1, 4))
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);

                foreach (range('A', 'F') as $col) {
                    $filteredSheet->getColumnDimension($col)->setAutoSize(true);
                }
            }

            // Auto-size columns on main sheet
            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Set active sheet back to first
            $spreadsheet->setActiveSheetIndex(0);

            // Save file
            $filename = 'batch_preview_' . $this->selectedDate . '_batch_' . $this->selectedBatch . '_' . time() . '.xlsx';
            $filepath = storage_path('app/exports/' . $filename);

            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            session()->put('batch_preview_excel_export', [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            return redirect()->route('donor-gifts.batch-preview-excel-download');

        } catch (\Exception $e) {
            Log::error('DonorPerfectBatchSync: Failed to generate Excel export', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to generate Excel export: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.donor-perfect-batch-sync')
            ->layout('components.layouts.app');
    }
}
