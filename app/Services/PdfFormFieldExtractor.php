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
            $dataFields = $pdf->getDataFields();

            if ($dataFields === false) {
                // Error occurred
                $error = $pdf->getError();
                throw new \Exception("PDFtk error: " . ($error ?: 'Unknown error'));
            }

            // Convert DataFields object to array
            if (is_object($dataFields)) {
                $rawFields = iterator_to_array($dataFields);
            } else {
                $rawFields = (array) $dataFields;
            }

            if (empty($rawFields)) {
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
     * @param array $rawFields Raw field data from pdftk (array of arrays or array of strings)
     * @return array Structured field definitions
     */
    protected static function parseFields(array $rawFields): array
    {
        // Check if we got an array of field arrays (modern DataFields format)
        if (isset($rawFields[0]) && is_array($rawFields[0])) {
            return self::parseFieldArrays($rawFields);
        }

        // Otherwise parse the old string-based format
        $fields = [];
        $currentField = null;

        foreach ($rawFields as $line) {
            // Skip if line is an array (shouldn't happen but defensive)
            if (!is_string($line)) {
                continue;
            }

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
     * Parse field data when DataFields returns array of arrays
     *
     * @param array $rawFields Array of field arrays
     * @return array Structured field definitions
     */
    protected static function parseFieldArrays(array $rawFields): array
    {
        $fields = [];

        foreach ($rawFields as $fieldData) {
            if (!is_array($fieldData)) {
                continue;
            }

            $field = [
                'name' => $fieldData['FieldName'] ?? $fieldData['name'] ?? 'unknown',
                'type' => $fieldData['FieldType'] ?? $fieldData['type'] ?? null,
                'flags' => isset($fieldData['FieldFlags']) ? (array) $fieldData['FieldFlags'] : [],
                'options' => $fieldData['FieldStateOption'] ?? $fieldData['options'] ?? null,
                'value' => $fieldData['FieldValue'] ?? null,
                'value_default' => $fieldData['FieldValueDefault'] ?? null,
                'justification' => $fieldData['FieldJustification'] ?? null,
            ];

            $fields[] = $field;
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
