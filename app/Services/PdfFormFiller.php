<?php

namespace App\Services;

use mikehaertl\pdftk\Pdf;

class PdfFormFiller
{
    /**
     * Fill a PDF form with data
     *
     * @param string $templatePath Path to template PDF
     * @param array $data Associative array of field_name => value
     * @param array $options Options for filling (flatten, needAppearances)
     * @return string Path to filled PDF
     * @throws \Exception if filling fails
     */
    public static function fill(string $templatePath, array $data, array $options = []): string
    {
        if (!file_exists($templatePath)) {
            throw new \Exception("Template PDF not found: {$templatePath}");
        }

        // Default options
        $options = array_merge([
            'flatten' => false,  // Keep form editable by default
            'needAppearances' => true,  // UTF-8 safe
            'outputPath' => null,  // Auto-generate if not provided
        ], $options);

        // Convert data values for PDF compatibility
        $convertedData = self::convertDataForPdf($data);

        try {
            $pdf = new Pdf($templatePath);

            // Fill the form
            $pdf->fillForm($convertedData);

            // Apply options
            if ($options['needAppearances']) {
                $pdf->needAppearances();
            }

            if ($options['flatten']) {
                $pdf->flatten();
            }

            // Generate output path if not provided
            $outputPath = $options['outputPath'] ?? self::generateTempPath();

            // Save the filled PDF
            if (!$pdf->saveAs($outputPath)) {
                $error = $pdf->getError();
                throw new \Exception("Failed to save filled PDF: {$error}");
            }

            return $outputPath;
        } catch (\Exception $e) {
            \Log::error('PDF form filling failed', [
                'template' => $templatePath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Convert data values to PDF-compatible format
     *
     * @param array $data Raw data array
     * @return array Converted data
     */
    protected static function convertDataForPdf(array $data): array
    {
        $converted = [];

        foreach ($data as $fieldName => $value) {
            $converted[$fieldName] = self::convertValue($value);
        }

        return $converted;
    }

    /**
     * Convert a single value to PDF-compatible format
     *
     * @param mixed $value Original value
     * @return string|null Converted value
     */
    protected static function convertValue($value): ?string
    {
        // Handle null
        if ($value === null) {
            return null;
        }

        // Handle boolean (checkboxes use 'Yes'/'Off' in PDF)
        if (is_bool($value)) {
            return $value ? 'Yes' : 'Off';
        }

        // Handle arrays (multi-select fields)
        if (is_array($value)) {
            return implode(', ', $value);
        }

        // Handle dates
        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value->format('m/d/Y');
        }

        // Handle Carbon dates
        if (method_exists($value, 'toDateString')) {
            return $value->format('m/d/Y');
        }

        // Handle numbers
        if (is_numeric($value)) {
            return (string) $value;
        }

        // Default: convert to string
        return (string) $value;
    }

    /**
     * Generate a temporary file path for filled PDF
     *
     * @return string
     */
    protected static function generateTempPath(): string
    {
        $filename = 'filled_' . uniqid() . '_' . time() . '.pdf';
        return storage_path('app/temp/' . $filename);
    }

    /**
     * Flatten a PDF form (make it non-editable)
     *
     * @param string $pdfPath Path to PDF file
     * @param string|null $outputPath Output path (overwrites input if null)
     * @return bool Success status
     */
    public static function flatten(string $pdfPath, ?string $outputPath = null): bool
    {
        if (!file_exists($pdfPath)) {
            return false;
        }

        $outputPath = $outputPath ?? $pdfPath;

        try {
            $pdf = new Pdf($pdfPath);
            $pdf->flatten();

            return $pdf->saveAs($outputPath);
        } catch (\Exception $e) {
            \Log::error('PDF flattening failed', [
                'path' => $pdfPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the number of pages in a PDF
     *
     * @param string $pdfPath Path to PDF file
     * @return int Number of pages
     */
    public static function getPageCount(string $pdfPath): int
    {
        if (!file_exists($pdfPath)) {
            return 0;
        }

        try {
            $pdf = new Pdf($pdfPath);
            $data = $pdf->getData();

            if (isset($data['NumberOfPages'])) {
                return (int) $data['NumberOfPages'];
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
