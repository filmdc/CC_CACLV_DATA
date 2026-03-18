<?php

namespace App\Traits;

use App\Models\ConditionalCodeMapping;
use App\Models\DonorPerfectCodeMapping;
use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncFilter;
use App\Services\SageIntacctApiService;
use App\Services\SageIntacctSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait WithBatchSyncOperations
{
    abstract protected function getBatchLabel(): string;

    abstract protected function getBatchFieldName(): string;

    abstract protected function getSyncExportSessionKey(): string;

    abstract protected function getPreviewExcelSessionKey(): string;

    abstract protected function getSyncDownloadRoute(): string;

    abstract protected function getPreviewExcelDownloadRoute(): string;

    public function getPreviewTotalProperty()
    {
        return collect($this->previewGifts)->sum('amount');
    }

    public function getCheckImageCountProperty()
    {
        return collect($this->previewGifts)->filter(fn ($g) => ! empty($g['IMAGE_CHECK']))->count();
    }

    public function getIntacctConfiguredProperty()
    {
        $intacctSetting = SageIntacctSetting::where('is_active', true)->first();

        return $intacctSetting && $intacctSetting->isConfigured();
    }

    public function applySyncFilters(array $batchGifts): void
    {
        $this->previewGifts = [];
        $this->filteredOutGifts = [];

        foreach ($batchGifts as $gift) {
            $filterResult = SageIntacctSyncFilter::shouldFilterGift($gift);

            if ($filterResult['should_filter']) {
                $gift['_filter_reason'] = $filterResult['reason'];
                $this->filteredOutGifts[] = $gift;
            } else {
                $this->previewGifts[] = $gift;
            }
        }

        $this->showPreview = true;

        if (count($this->filteredOutGifts) > 0) {
            session()->flash('info', count($this->filteredOutGifts).' gift(s) will be excluded by sync filters.');
        }
    }

    public function getMappedCode(array $gift, string $fieldType): array
    {
        $dpCode = $gift[$fieldType] ?? '';

        if (empty($dpCode)) {
            return ['code' => '', 'source' => null, 'blocked' => false, 'reason' => null];
        }

        if (in_array($fieldType, ['solicit_code', 'sub_solicit_code'])) {
            $dpCampaign = $gift['campaign'] ?? '';
            $intacctLocation = null;

            if ($dpCampaign) {
                $intacctLocation = DonorPerfectCodeMapping::getMappingFor('campaign', $dpCampaign);
            }

            if ($intacctLocation) {
                $hasConditionalRules = ConditionalCodeMapping::hasRulesForLocation(
                    $intacctLocation,
                    $fieldType
                );

                if ($hasConditionalRules) {
                    $conditionalCode = ConditionalCodeMapping::getMapping(
                        $intacctLocation,
                        $fieldType,
                        $dpCode
                    );

                    if ($conditionalCode) {
                        return ['code' => $conditionalCode, 'source' => 'conditional', 'blocked' => false, 'reason' => null];
                    } else {
                        $standardCode = DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

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

        $standardCode = DonorPerfectCodeMapping::getMappingFor($fieldType, $dpCode);

        return ['code' => $standardCode ?: '', 'source' => $standardCode ? 'standard' : null, 'blocked' => false, 'reason' => null];
    }

    public function confirmSync()
    {
        if (empty($this->previewGifts)) {
            session()->flash('error', 'No gifts to sync. Please load preview first.');

            return;
        }

        $this->showConfirmModal = true;
    }

    public function cancelSync()
    {
        $this->showConfirmModal = false;
    }

    public function performSync()
    {
        if (! $this->canAny(['sage.sync.perform', 'intacct.sync'])) {
            session()->flash('error', 'You do not have permission to sync to Sage Intacct.');

            return;
        }

        $this->showConfirmModal = false;
        $this->syncInProgress = true;

        try {
            if (! empty($this->selectedDate)) {
                $startDate = Carbon::parse($this->selectedDate);
                $endDate = $startDate->copy();
            } else {
                // Derive date range from the gifts themselves
                $giftDates = collect($this->previewGifts)
                    ->pluck('gift_date')
                    ->filter()
                    ->map(fn ($d) => Carbon::parse($d))
                    ->sort();

                $startDate = $giftDates->first() ?? Carbon::now();
                $endDate = $giftDates->last() ?? Carbon::now();
            }

            $intacctSetting = SageIntacctSetting::getActive();

            if (! $intacctSetting) {
                throw new \Exception('Sage Intacct is not configured');
            }

            if ($intacctSetting->isXmlApi()) {
                $apiService = new SageIntacctApiService($intacctSetting);
                $syncService = new SageIntacctSyncService($apiService, $intacctSetting);
            } else {
                $userToken = auth()->user()->sageIntacctToken;

                if (! $userToken || ! $userToken->hasValidToken()) {
                    session()->flash('error', 'You must connect to Sage Intacct first.');
                    $this->syncInProgress = false;

                    return;
                }

                $apiService = new SageIntacctApiService($intacctSetting, $userToken);
                $syncService = new SageIntacctSyncService($apiService, $intacctSetting, $userToken);
            }

            $syncResult = $syncService->syncGifts(
                $startDate,
                $endDate,
                auth()->id(),
                $this->previewGifts
            );

            $this->syncInProgress = false;

            if ($syncResult['success']) {
                $this->syncCompleted = true;
                $this->syncResults = $syncResult;

                $this->generateSyncExport();

                $synced = $syncResult['synced'] ?? 0;
                $failed = $syncResult['failed'] ?? 0;
                $skipped = $syncResult['skipped'] ?? 0;
                $alreadySynced = $syncResult['already_synced'] ?? 0;

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
            Log::error('Batch Sync: Sync failed', [
                'date' => $this->selectedDate,
                'batch' => $this->selectedBatch,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Sync failed: '.$e->getMessage());
        }
    }

    public function exportPreviewToExcel()
    {
        if (empty($this->previewGifts) && empty($this->filteredOutGifts)) {
            session()->flash('error', 'No preview data to export. Please load a preview first.');

            return;
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Batch Preview');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            ];

            $dateLabel = ! empty($this->selectedDate) ? Carbon::parse($this->selectedDate)->format('F j, Y') : 'All Dates';
            $sheet->setCellValue('A1', 'Batch '.$this->selectedBatch.' Preview - '.$dateLabel);
            $sheet->mergeCells('A1:L1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
            ]);

            $totalAmount = collect($this->previewGifts)->sum('amount');
            $sheet->setCellValue('A2', 'Total: '.count($this->previewGifts).' gifts | Amount: $'.number_format($totalAmount, 2));
            $sheet->mergeCells('A2:L2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 11, 'color' => ['rgb' => '6B7280']],
            ]);

            $headers = [
                'A' => 'Gift ID', 'B' => 'Gift Date', 'C' => 'Batch ID', 'D' => 'Amount',
                'E' => 'Donor Name', 'F' => 'GL Code', 'G' => 'GL (Intacct)', 'H' => 'Campaign',
                'I' => 'Location (Intacct)', 'J' => 'Solicit Code', 'K' => 'Project (Intacct)',
                'L' => 'Sub-Solicit', 'M' => 'Service (Intacct)', 'N' => 'Payment Type', 'O' => 'Check Image',
            ];

            $headerRow = 4;
            foreach ($headers as $col => $label) {
                $sheet->setCellValue($col.$headerRow, $label);
            }
            $sheet->getStyle('A'.$headerRow.':O'.$headerRow)->applyFromArray($headerStyle);

            $row = $headerRow + 1;
            foreach ($this->previewGifts as $gift) {
                $donorName = trim(($gift['first_name'] ?? '').' '.($gift['last_name'] ?? ''));
                $glMapping = $this->getMappedCode($gift, 'gl_code');
                $campaignMapping = $this->getMappedCode($gift, 'campaign');
                $solicitMapping = $this->getMappedCode($gift, 'solicit_code');
                $subSolicitMapping = $this->getMappedCode($gift, 'sub_solicit_code');

                $sheet->setCellValue('A'.$row, $gift['gift_id'] ?? '');
                $sheet->setCellValue('B'.$row, $gift['gift_date'] ?? '');
                $sheet->setCellValue('C'.$row, $gift['BATCH'] ?? ($gift['DP_CheckScan_Batch'] ?? ''));
                $sheet->setCellValue('D'.$row, floatval(str_replace(',', '', $gift['amount'] ?? 0)));
                $sheet->setCellValue('E'.$row, $donorName ?: 'Anonymous');
                $sheet->setCellValue('F'.$row, $gift['gl_code'] ?? '');
                $sheet->setCellValue('G'.$row, $glMapping['code'] ?? '');
                $sheet->setCellValue('H'.$row, $gift['campaign'] ?? '');
                $sheet->setCellValue('I'.$row, $campaignMapping['code'] ?? '');
                $sheet->setCellValue('J'.$row, $gift['solicit_code'] ?? '');
                $sheet->setCellValue('K'.$row, $solicitMapping['code'] ?? '');
                $sheet->setCellValue('L'.$row, $gift['sub_solicit_code'] ?? '');
                $sheet->setCellValue('M'.$row, $subSolicitMapping['code'] ?? '');
                $sheet->setCellValue('N'.$row, $gift['payment_type'] ?? '');
                $sheet->setCellValue('O'.$row, ! empty($gift['IMAGE_CHECK']) ? 'Yes' : 'No');

                if ($row % 2 === 0) {
                    $sheet->getStyle('A'.$row.':O'.$row)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                    ]);
                }

                $row++;
            }

            $lastDataRow = $row - 1;
            if ($lastDataRow >= $headerRow + 1) {
                $sheet->getStyle('D'.($headerRow + 1).':D'.$lastDataRow)
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);

                $sheet->getStyle('A'.($headerRow + 1).':O'.$lastDataRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
            }

            $sheet->setCellValue('C'.$row, 'Total:');
            $sheet->setCellValue('D'.$row, $totalAmount);
            $sheet->getStyle('C'.$row.':D'.$row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]],
            ]);
            $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);

            if (! empty($this->filteredOutGifts)) {
                $filteredSheet = $spreadsheet->createSheet();
                $filteredSheet->setTitle('Filtered Out');

                $filteredSheet->setCellValue('A1', 'Filtered Out Gifts ('.count($this->filteredOutGifts).')');
                $filteredSheet->mergeCells('A1:F1');
                $filteredSheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'B45309']],
                ]);

                $filteredHeaders = ['A' => 'Gift ID', 'B' => 'Amount', 'C' => 'Gift Type', 'D' => 'Donor', 'E' => 'Batch ID', 'F' => 'Filter Reason'];
                $fHeaderStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ];

                foreach ($filteredHeaders as $col => $label) {
                    $filteredSheet->setCellValue($col.'3', $label);
                }
                $filteredSheet->getStyle('A3:F3')->applyFromArray($fHeaderStyle);

                $fRow = 4;
                foreach ($this->filteredOutGifts as $gift) {
                    $donorName = trim(($gift['first_name'] ?? '').' '.($gift['last_name'] ?? ''));
                    $filteredSheet->setCellValue('A'.$fRow, $gift['gift_id'] ?? '');
                    $filteredSheet->setCellValue('B'.$fRow, floatval(str_replace(',', '', $gift['amount'] ?? 0)));
                    $filteredSheet->setCellValue('C'.$fRow, $gift['gift_type'] ?? '');
                    $filteredSheet->setCellValue('D'.$fRow, $donorName ?: 'Anonymous');
                    $filteredSheet->setCellValue('E'.$fRow, $gift['BATCH'] ?? ($gift['DP_CheckScan_Batch'] ?? ''));
                    $filteredSheet->setCellValue('F'.$fRow, $gift['_filter_reason'] ?? 'Excluded by filter');
                    $fRow++;
                }

                $filteredSheet->getStyle('B4:B'.max($fRow - 1, 4))
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);

                foreach (range('A', 'F') as $col) {
                    $filteredSheet->getColumnDimension($col)->setAutoSize(true);
                }
            }

            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $datePart = ! empty($this->selectedDate) ? $this->selectedDate : 'multi_date';
            $filename = 'batch_preview_'.$datePart.'_batch_'.$this->selectedBatch.'_'.time().'.xlsx';
            $filepath = storage_path('app/exports/'.$filename);

            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);

            session()->put($this->getPreviewExcelSessionKey(), [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            return redirect()->route($this->getPreviewExcelDownloadRoute());

        } catch (\Exception $e) {
            Log::error('Batch Sync: Failed to generate Excel export', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to generate Excel export: '.$e->getMessage());
        }
    }

    private function generateSyncExport()
    {
        try {
            $datePart = ! empty($this->selectedDate) ? $this->selectedDate : 'multi_date';
            $filename = 'batch_sync_'.$datePart.'_batch_'.$this->selectedBatch.'_'.time().'.csv';
            $filepath = storage_path('app/exports/'.$filename);

            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $handle = fopen($filepath, 'w');

            fputcsv($handle, [
                'Gift ID', 'Gift Date', 'Amount', 'Donor Name', 'GL Code', 'GL Code (Intacct)',
                'Campaign', 'Location (Intacct)', 'Solicit Code', 'Project (Intacct)',
                'Sub-Solicit Code', 'Service (Intacct)', 'Payment Type', 'Batch',
                'Has Check Image', 'Sync Status',
            ]);

            foreach ($this->previewGifts as $gift) {
                $hasCheckImage = ! empty($gift['IMAGE_CHECK']) ? 'Yes' : 'No';
                $donorName = trim(($gift['first_name'] ?? '').' '.($gift['last_name'] ?? ''));

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
                    $gift['BATCH'] ?? ($gift['DP_CheckScan_Batch'] ?? ''),
                    $hasCheckImage,
                    'Synced',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['--- SYNC SUMMARY ---']);
            fputcsv($handle, ['Sync Date', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Gift Date', ! empty($this->selectedDate) ? $this->selectedDate : 'Multiple Dates']);
            fputcsv($handle, ['Batch', $this->selectedBatch]);
            fputcsv($handle, ['Total Gifts', count($this->previewGifts)]);
            fputcsv($handle, ['Total Amount', '$'.number_format(collect($this->previewGifts)->sum('amount'), 2)]);
            fputcsv($handle, ['Created', $this->syncResults['created'] ?? 0]);
            fputcsv($handle, ['Skipped', $this->syncResults['skipped'] ?? 0]);
            fputcsv($handle, ['Failed', $this->syncResults['failed'] ?? 0]);
            fputcsv($handle, ['Synced By', auth()->user()->name ?? 'Unknown']);

            fclose($handle);

            session()->put($this->getSyncExportSessionKey(), [
                'filename' => $filename,
                'filepath' => $filepath,
                'created_at' => now(),
            ]);

            $this->downloadUrl = route($this->getSyncDownloadRoute());

        } catch (\Exception $e) {
            Log::error('Batch Sync: Failed to generate export', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
