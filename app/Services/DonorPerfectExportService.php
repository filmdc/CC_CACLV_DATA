<?php

namespace App\Services;

use League\Csv\Writer;
use League\Csv\Bom;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DonorPerfectExportService
{
    /**
     * Export gifts to CSV format
     *
     * @param array $gifts Array of gift records
     * @param array $fields Field names to include in export
     * @param bool $useIntacctCodes Whether to use Sage Intacct mapped codes
     * @return string CSV content as string
     */
    public function exportToCsv(array $gifts, array $fields, bool $useIntacctCodes = false): string
    {
        try {
            // Transform gifts if using Intacct codes
            if ($useIntacctCodes) {
                $giftService = app(DonorPerfectGiftService::class);
                $gifts = array_map(function($gift) use ($giftService) {
                    return $giftService->transformGiftForExport($gift, true);
                }, $gifts);
            }

            $csv = Writer::createFromString();

            // Add UTF-8 BOM for Excel compatibility
            $csv->setOutputBOM(Bom::Utf8);

            // Add format comment header
            $formatComment = $useIntacctCodes
                ? '# Code Format: Sage Intacct Mapped Codes'
                : '# Code Format: DonorPerfect Original Codes';
            $csv->insertOne([$formatComment]);

            // Determine headers - use provided fields or extract from first gift
            $headers = $this->determineHeaders($gifts, $fields);

            // Insert headers
            $csv->insertOne($headers);

            // Insert gift data rows
            foreach ($gifts as $gift) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $gift[$header] ?? '';
                }
                $csv->insertOne($row);
            }

            Log::info('DonorPerfect: CSV export generated', [
                'record_count' => count($gifts),
                'column_count' => count($headers),
                'use_intacct_codes' => $useIntacctCodes
            ]);

            return $csv->toString();

        } catch (\Exception $e) {
            Log::error('DonorPerfect CSV export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('CSV export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export gifts to Excel format
     *
     * @param array $gifts Array of gift records
     * @param array $fields Field names to include in export
     * @param bool $useIntacctCodes Whether to use Sage Intacct mapped codes
     * @return string Path to generated Excel file in storage
     */
    public function exportToExcel(array $gifts, array $fields, bool $useIntacctCodes = false): string
    {
        try {
            // Transform gifts if using Intacct codes
            if ($useIntacctCodes) {
                $giftService = app(DonorPerfectGiftService::class);
                $gifts = array_map(function($gift) use ($giftService) {
                    return $giftService->transformGiftForExport($gift, true);
                }, $gifts);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('DonorPerfect Gifts');

            // Add format note row
            $formatNote = $useIntacctCodes
                ? 'Code Format: Sage Intacct Mapped Codes'
                : 'Code Format: DonorPerfect Original Codes';
            $sheet->setCellValue('A1', $formatNote);
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'italic' => true,
                    'color' => ['rgb' => '4F46E5'],
                    'size' => 11,
                ],
            ]);

            // Determine headers
            $headers = $this->determineHeaders($gifts, $fields);

            // Write headers with styling (now on row 2)
            $sheet->fromArray($headers, null, 'A2');
            $this->styleHeaderRow($sheet, count($headers), 2);

            // Write data rows
            $rowNum = 3;
            foreach ($gifts as $gift) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $gift[$header] ?? '';
                }
                $sheet->fromArray($row, null, 'A' . $rowNum);
                $rowNum++;
            }

            // Auto-size columns
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Apply borders to data range (starting from row 2)
            $this->applyBorders($sheet, count($headers), count($gifts) + 2, 2);

            // Save to temporary file
            $filename = 'donorperfect_gifts_' . now()->format('Y-m-d_His') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            Log::info('DonorPerfect: Excel export generated', [
                'record_count' => count($gifts),
                'column_count' => count($headers),
                'file_path' => $tempPath,
                'use_intacct_codes' => $useIntacctCodes
            ]);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error('DonorPerfect Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Excel export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export gifts to PDF format
     *
     * @param array $gifts Array of gift records
     * @param array $fields Field names to include in export
     * @param bool $useIntacctCodes Whether to use Sage Intacct mapped codes
     * @return string Path to generated PDF file in storage
     */
    public function exportToPdf(array $gifts, array $fields, bool $useIntacctCodes = false): string
    {
        try {
            // Transform gifts if using Intacct codes
            if ($useIntacctCodes) {
                $giftService = app(DonorPerfectGiftService::class);
                $gifts = array_map(function($gift) use ($giftService) {
                    return $giftService->transformGiftForExport($gift, true);
                }, $gifts);
            }

            // Determine headers
            $headers = $this->determineHeaders($gifts, $fields);

            // Limit fields for PDF to prevent overflow (max 6 columns for readability)
            if (count($headers) > 6) {
                Log::warning('DonorPerfect: PDF export has too many columns, limiting to first 6', [
                    'original_count' => count($headers)
                ]);
                $headers = array_slice($headers, 0, 6);
            }

            // Prepare format subtitle
            $formatSubtitle = $useIntacctCodes
                ? 'Code Format: Sage Intacct Mapped Codes'
                : 'Code Format: DonorPerfect Original Codes';

            // Prepare data for PDF view
            $data = [
                'headers' => $headers,
                'gifts' => $gifts,
                'total_count' => count($gifts),
                'export_date' => now()->format('F j, Y'),
                'export_time' => now()->format('g:i A'),
                'format_subtitle' => $formatSubtitle,
            ];

            // Generate PDF using view
            $pdf = Pdf::loadView('exports.donorperfect-gifts-pdf', $data);
            $pdf->setPaper('letter', 'landscape');

            // Save to temporary file
            $filename = 'donorperfect_gifts_' . now()->format('Y-m-d_His') . '.pdf';
            $tempPath = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            Log::info('DonorPerfect: PDF export generated', [
                'record_count' => count($gifts),
                'column_count' => count($headers),
                'file_path' => $tempPath,
                'use_intacct_codes' => $useIntacctCodes
            ]);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error('DonorPerfect PDF export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('PDF export failed: ' . $e->getMessage());
        }
    }

    /**
     * Determine headers from gifts data or provided fields
     */
    private function determineHeaders(array $gifts, array $fields): array
    {
        // If specific fields are provided, use those
        if (!empty($fields)) {
            return $fields;
        }

        // Otherwise, extract from first gift record
        if (!empty($gifts)) {
            return array_keys($gifts[0]);
        }

        // Fallback to default headers
        return ['Gift ID', 'Donor ID', 'Gift Date', 'Amount', 'First Name', 'Last Name'];
    }

    /**
     * Style the header row in Excel
     */
    private function styleHeaderRow($sheet, int $columnCount, int $rowNumber = 1): void
    {
        $lastColumn = chr(64 + $columnCount); // Convert number to letter (A, B, C, etc.)

        $headerRange = 'A' . $rowNumber . ':' . $lastColumn . $rowNumber;

        // Apply header styling
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'], // Indigo color
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Set row height
        $sheet->getRowDimension($rowNumber)->setRowHeight(25);
    }

    /**
     * Apply borders to the data range
     */
    private function applyBorders($sheet, int $columnCount, int $rowCount, int $startRow = 1): void
    {
        $lastColumn = chr(64 + $columnCount);
        $dataRange = 'A' . $startRow . ':' . $lastColumn . $rowCount;

        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
    }

    /**
     * Get export format options
     */
    public function getExportFormats(): array
    {
        return [
            'csv' => [
                'label' => 'CSV (Excel Compatible)',
                'extension' => 'csv',
                'mime_type' => 'text/csv',
                'description' => 'Comma-separated values file that can be opened in Excel, Google Sheets, or any spreadsheet application',
            ],
            'excel' => [
                'label' => 'Excel (.xlsx)',
                'extension' => 'xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'description' => 'Native Excel format with formatting and styling',
            ],
            'pdf' => [
                'label' => 'PDF Document',
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'description' => 'Portable Document Format for printing and sharing (limited columns for readability)',
            ],
        ];
    }

    /**
     * Export gifts in specified format
     *
     * @param array $gifts Array of gift records
     * @param array $fields Field names to include
     * @param string $format Export format (csv, excel, pdf)
     * @param bool $useIntacctCodes Whether to use Sage Intacct mapped codes
     * @return array Result with file path or content
     */
    public function export(array $gifts, array $fields, string $format = 'csv', bool $useIntacctCodes = false): array
    {
        try {
            switch ($format) {
                case 'csv':
                    $content = $this->exportToCsv($gifts, $fields, $useIntacctCodes);
                    return [
                        'success' => true,
                        'format' => 'csv',
                        'content' => $content,
                        'filename' => 'donorperfect_gifts_' . now()->format('Y-m-d_His') . '.csv',
                    ];

                case 'excel':
                    $filePath = $this->exportToExcel($gifts, $fields, $useIntacctCodes);
                    return [
                        'success' => true,
                        'format' => 'excel',
                        'file_path' => $filePath,
                        'filename' => basename($filePath),
                    ];

                case 'pdf':
                    $filePath = $this->exportToPdf($gifts, $fields, $useIntacctCodes);
                    return [
                        'success' => true,
                        'format' => 'pdf',
                        'file_path' => $filePath,
                        'filename' => basename($filePath),
                    ];

                default:
                    throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

        } catch (\Exception $e) {
            Log::error('DonorPerfect export failed', [
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'format' => $format,
            ];
        }
    }
}
