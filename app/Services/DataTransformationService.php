<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ReferenceData;
use Illuminate\Support\Str;

class DataTransformationService
{
    protected ReferenceDataTransformationService $referenceDataTransformer;

    public function __construct(ReferenceDataTransformationService $referenceDataTransformer)
    {
        $this->referenceDataTransformer = $referenceDataTransformer;
    }

    public function parseName(string $fullName): array
    {
        $name = trim($fullName);
        
        // Handle "Last, First Middle" format (HMIS format)
        if (str_contains($name, ',')) {
            $parts = array_map('trim', explode(',', $name, 2));
            $lastName = $parts[0];
            $firstAndMiddle = $parts[1] ?? '';
            
            if (!empty($firstAndMiddle)) {
                $nameParts = array_filter(array_map('trim', explode(' ', $firstAndMiddle)));
                $firstName = array_shift($nameParts) ?? '';
                $middleName = implode(' ', $nameParts);
            } else {
                $firstName = '';
                $middleName = '';
            }
        } else {
            // Handle "First Middle Last" format
            $nameParts = array_filter(array_map('trim', explode(' ', $name)));
            $firstName = array_shift($nameParts) ?? '';
            $lastName = array_pop($nameParts) ?? '';
            $middleName = implode(' ', $nameParts);
        }

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName ?: null,
            'last_name' => $lastName
        ];
    }

    public function parseDate(string $date, string $format = null): ?Carbon
    {
        if (empty(trim($date))) {
            return null;
        }

        $date = trim($date);

        // Auto-detect common date formats if no format specified
        if ($format === null) {
            $formats = [
                'Y-m-d',        // 2024-01-15
                'm/d/Y',        // 01/15/2024 (HMIS format)
                'd/m/Y',        // 15/01/2024
                'm-d-Y',        // 01-15-2024
                'd-m-Y',        // 15-01-2024
                'Y/m/d',        // 2024/01/15
                'Y-m-d H:i:s',  // 2024-01-15 10:30:00
                'm/d/Y H:i:s A', // 01/15/2024 10:30:00 AM
            ];

            foreach ($formats as $testFormat) {
                try {
                    return Carbon::createFromFormat($testFormat, $date);
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try Carbon's general parsing as fallback
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::createFromFormat($format, $date);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function transformGender(string $gender): ?string
    {
        return $this->referenceDataTransformer->transformGender($gender);
    }

    public function transformVeteranStatus(string $status): ?string
    {
        return $this->referenceDataTransformer->transformMilitaryStatus($status);
    }

    public function transformDisabilityStatus(string $status): ?string
    {
        return $this->referenceDataTransformer->transformDisabilityStatus($status);
    }

    public function transformHealthInsurance(string $status): ?string
    {
        return $this->referenceDataTransformer->transformHealthInsuranceStatus($status);
    }

    public function parseRaceAndEthnicity(string $raceEthnicity): array
    {
        $result = [
            'race' => null,
            'ethnicity' => null
        ];

        if (empty(trim($raceEthnicity))) {
            return $result;
        }

        $value = trim($raceEthnicity);

        // Parse HMIS format: "Race / Ethnicity" or "Multi-Racial / Hispanic/Latina/e/o"
        if (str_contains($value, ' / ')) {
            $parts = array_map('trim', explode(' / ', $value, 2));
            $racePart = $parts[0];
            $ethnicityPart = $parts[1] ?? '';

            // Map race
            $result['race'] = $this->referenceDataTransformer->transformRace($racePart)[0] ?? null;
            
            // Map ethnicity
            $result['ethnicity'] = $this->referenceDataTransformer->transformEthnicity($ethnicityPart);
        } else {
            // Try to determine if it's race or ethnicity
            if (str_contains(strtolower($value), 'hispanic') || str_contains(strtolower($value), 'latino')) {
                $result['ethnicity'] = $this->referenceDataTransformer->transformEthnicity($value);
            } else {
                $result['race'] = $this->referenceDataTransformer->transformRace($value)[0] ?? null;
            }
        }

        return $result;
    }


    public function transformRelationshipToHeadOfHousehold(string $relationship): bool
    {
        $normalized = strtolower(trim($relationship));
        return in_array($normalized, ['self', 'head of household', 'hoh']);
    }

    public function parseHouseholdType(string $type): ?string
    {
        return $this->referenceDataTransformer->transformHouseholdType($type);
    }

    public function validateAndTransformField(string $field, $value, ?array $transformationRule = null): array
    {
        $result = [
            'value' => $value,
            'transformed' => false,
            'errors' => [],
            'warnings' => []
        ];

        if (empty(trim($value))) {
            return $result;
        }

        try {
            switch ($field) {
                case 'date_of_birth':
                case 'intake_date':
                case 'last_service_date':
                    $transformed = $this->parseDate($value, $transformationRule['format'] ?? null);
                    if ($transformed) {
                        $result['value'] = $transformed->format('Y-m-d');
                        $result['transformed'] = true;
                    } else {
                        $result['errors'][] = "Invalid date format: {$value}";
                    }
                    break;

                case 'gender':
                    $transformed = $this->transformGender($value);
                    if ($transformed !== $value) {
                        $result['value'] = $transformed;
                        $result['transformed'] = true;
                    }
                    break;

                case 'military_status':
                    $transformed = $this->transformVeteranStatus($value);
                    if ($transformed !== $value) {
                        $result['value'] = $transformed;
                        $result['transformed'] = true;
                    }
                    break;

                case 'disability_status':
                    $transformed = $this->transformDisabilityStatus($value);
                    if ($transformed !== $value) {
                        $result['value'] = $transformed;
                        $result['transformed'] = true;
                    }
                    break;

                case 'health_insurance_status':
                    $transformed = $this->transformHealthInsurance($value);
                    if ($transformed !== $value) {
                        $result['value'] = $transformed;
                        $result['transformed'] = true;
                    }
                    break;

                case 'first_name':
                case 'last_name':
                case 'middle_name':
                    // These are handled by parseName method
                    break;

                default:
                    // Apply custom transformation if provided
                    if ($transformationRule && isset($transformationRule['mapping'])) {
                        $mapping = $transformationRule['mapping'];
                        $normalized = strtolower(trim($value));
                        if (isset($mapping[$normalized])) {
                            $result['value'] = $mapping[$normalized];
                            $result['transformed'] = true;
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $result['errors'][] = "Transformation error: " . $e->getMessage();
        }

        return $result;
    }

    public function validateAgainstReferenceData(string $category, $value): array
    {
        $result = [
            'valid' => false,
            'suggested_value' => null,
            'confidence' => 0
        ];

        if (empty(trim($value))) {
            return $result;
        }

        $options = ReferenceData::getOptions($category);
        
        if ($options->isEmpty()) {
            return $result;
        }

        $normalized = strtolower(trim($value));
        
        // Exact match
        foreach ($options as $option) {
            if (strtolower($option->value) === $normalized || strtolower($option->label) === $normalized) {
                $result['valid'] = true;
                $result['suggested_value'] = $option->value;
                $result['confidence'] = 100;
                return $result;
            }
        }

        // Fuzzy match
        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($options as $option) {
            $similarity1 = similar_text($normalized, strtolower($option->value));
            $similarity2 = similar_text($normalized, strtolower($option->label));
            $similarity = max($similarity1, $similarity2);

            if ($similarity > $bestSimilarity && $similarity > 70) { // 70% similarity threshold
                $bestSimilarity = $similarity;
                $bestMatch = $option->value;
            }
        }

        if ($bestMatch) {
            $result['suggested_value'] = $bestMatch;
            $result['confidence'] = $bestSimilarity;
        }

        return $result;
    }
}