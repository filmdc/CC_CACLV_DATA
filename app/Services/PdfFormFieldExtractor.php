<?php

namespace App\Services;

use mikehaertl\pdftk\Pdf;

class PdfFormFieldExtractor
{
    /**
     * Extract form fields from a PDF file using pdftk
     *
     * @param string $pdfPath Full path to PDF file
     * @return array Array of field definitions
     * @throws \Exception if pdftk is not available or file doesn't exist
     */
    public static function scan(string $pdfPath): array
    {
        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found: {$pdfPath}");
        }

        // Check if pdftk is available
        if (!self::isPdftkAvailable()) {
            throw new \Exception("PDFtk is not installed. Please install it with: sudo apt-get install pdftk");
        }

        try {
            $pdf = new Pdf($pdfPath);
            $rawFields = $pdf->getDataFields();

            if ($rawFields === false || empty($rawFields)) {
                // No form fields found, or error occurred
                $error = $pdf->getError();
                if ($error) {
                    throw new \Exception("PDFtk error: {$error}");
                }

                return []; // PDF has no form fields
            }

            return self::parseFields($rawFields);
        } catch (\Exception $e) {
            \Log::error('PDF field extraction failed', [
                'path' => $pdfPath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse raw pdftk field data into structured array
     *
     * @param array $rawFields Raw field data from pdftk
     * @return array Structured field definitions
     */
    protected static function parseFields(array $rawFields): array
    {
        $fields = [];
        $currentField = null;

        foreach ($rawFields as $line) {
            // PDFtk outputs field info as key-value pairs
            // Example: "FieldName: participant_first_name"
            if (preg_match('/^([\w]+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];

                if ($key === 'FieldName') {
                    // Start of a new field
                    if ($currentField) {
                        $fields[] = $currentField;
                    }
                    $currentField = [
                        'name' => $value,
                        'type' => null,
                        'flags' => [],
                        'options' => null,
                        'value' => null,
                        'value_default' => null,
                    ];
                } elseif ($currentField) {
                    // Add property to current field
                    if ($key === 'FieldType') {
                        $currentField['type'] = $value;
                    } elseif ($key === 'FieldFlags') {
                        $currentField['flags'][] = $value;
                    } elseif ($key === 'FieldStateOption') {
                        if (!isset($currentField['options'])) {
                            $currentField['options'] = [];
                        }
                        $currentField['options'][] = $value;
                    } elseif ($key === 'FieldValue') {
                        $currentField['value'] = $value;
                    } elseif ($key === 'FieldValueDefault') {
                        $currentField['value_default'] = $value;
                    } elseif ($key === 'FieldJustification') {
                        $currentField['justification'] = $value;
                    }
                }
            }
        }

        // Add the last field
        if ($currentField) {
            $fields[] = $currentField;
        }

        return self::enrichFields($fields);
    }

    /**
     * Enrich field data with human-readable labels and type detection
     *
     * @param array $fields Array of field data
     * @return array Enriched field data
     */
    protected static function enrichFields(array $fields): array
    {
        return array_map(function ($field) {
            // Generate human-readable label from field name
            $label = str_replace('_', ' ', $field['name']);
            $label = ucwords($label);

            // Determine simplified field type
            $simplifiedType = self::simplifyFieldType($field['type'], $field['flags']);

            return [
                'name' => $field['name'],
                'type' => $simplifiedType,
                'label' => $label,
                'original_type' => $field['type'],
                'flags' => $field['flags'],
                'options' => $field['options'],
                'default_value' => $field['value_default'],
                'justification' => $field['justification'] ?? null,
            ];
        }, $fields);
    }

    /**
     * Simplify pdftk field type to common types
     *
     * @param string|null $type PDFtk field type
     * @param array $flags Field flags
     * @return string Simplified type
     */
    protected static function simplifyFieldType(?string $type, array $flags): string
    {
        if ($type === 'Text') {
            return 'text';
        }

        if ($type === 'Button') {
            // Checkboxes are buttons with specific flags
            if (in_array('Radio', $flags) || in_array('NoToggleToOff', $flags)) {
                return in_array('Radio', $flags) ? 'radio' : 'checkbox';
            }
            return 'checkbox'; // Default for buttons
        }

        if ($type === 'Choice') {
            // Dropdown or list
            return in_array('Combo', $flags) ? 'dropdown' : 'list';
        }

        if ($type === 'Signature') {
            return 'signature';
        }

        return 'unknown';
    }

    /**
     * Check if pdftk is available on the system
     *
     * @return bool
     */
    public static function isPdftkAvailable(): bool
    {
        $output = [];
        $returnCode = 0;

        exec('which pdftk 2>&1', $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Get pdftk version information
     *
     * @return string|null Version string or null if not available
     */
    public static function getPdftkVersion(): ?string
    {
        if (!self::isPdftkAvailable()) {
            return null;
        }

        $output = [];
        exec('pdftk --version 2>&1', $output);

        return implode("\n", $output);
    }
}
