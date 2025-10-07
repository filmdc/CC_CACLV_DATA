<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Household;
use App\Models\PovertyGuideline;
use App\Models\ReferenceDataSet;
use App\Services\ReferenceDataTransformationService;
use League\Csv\Reader;
use League\Csv\Statement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ParticipantImportService
{
    protected ReferenceDataTransformationService $transformer;
    protected ?ReferenceDataSet $importDefaultSet;
    protected ?ReferenceDataSet $selectedReferenceDataSet = null;

    public function __construct(ReferenceDataTransformationService $transformer)
    {
        $this->transformer = $transformer;
        $this->importDefaultSet = ReferenceDataSet::getImportDefault();
    }

    protected array $requiredHeaders = [
        'household_code',
        'participant_type',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender'
    ];

    protected array $householdFields = [
        'household_code',
        'household_type',
        'household_size',
        'housing_status',
        'monthly_income',
        'annual_income',
        'hhs_poverty_percentage',
        'street_address',
        'city',
        'state',
        'zip_code',
        'household_notes'
    ];

    protected function getValidEnumValues(string $category): array
    {
        // Use selected reference data set if available, otherwise use import default
        $referenceSet = $this->selectedReferenceDataSet ?? $this->importDefaultSet;
        
        if (!$referenceSet) {
            // Fallback to basic CSBG-compliant values if no set available
            return $this->getFallbackValues($category);
        }

        $referenceData = $referenceSet->referenceData()
            ->where('category', $category)
            ->where('active', true)
            ->pluck('value')
            ->toArray();

        return !empty($referenceData) ? $referenceData : $this->getFallbackValues($category);
    }

    protected function getFallbackValues(string $category): array
    {
        $fallbacks = [
            'household_type' => [
                'Single Person',
                'Two Adults NO Children',
                'Single Parent Female',
                'Single Parent Male',
                'Two Parent Household',
                'Non-related Adults with Children',
                'Multigenerational Household',
                'Other',
                'Unknown/not reported'
            ],
            'housing_status' => [
                'Own',
                'Rent',
                'Other permanent housing',
                'Homeless',
                'Other',
                'Unknown/not reported'
            ],
            'gender' => [
                'Male',
                'Female',
                'Other',
                'Unknown/not reported'
            ],
            'education_level' => [
                'Grades 0-8',
                'Grades 9-12/Non-Graduate',
                'High School Graduate',
                'GED/Equivalency Diploma',
                '12th Grade + Some Post-Secondary',
                '2 or 4 Year College Graduate',
                'Graduate of Other Post-Secondary School',
                'Unknown/not reported'
            ],
            'disability_status' => [
                'Yes',
                'No',
                'Unknown/not reported'
            ],
            'health_insurance_status' => [
                'Yes',
                'No',
                'Unknown/not reported'
            ],
            'ethnicity' => [
                'Hispanic, Latino or Spanish Origins',
                'Not Hispanic, Latino or Spanish Origins',
                'Unknown/not reported'
            ],
            'military_status' => [
                'Veteran',
                'Active Military',
                'Never Served in the Military',
                'Unknown/not reported'
            ],
            'work_status' => [
                'Employed Full-Time',
                'Employed Part-Time',
                'Migrant Seasonal Farm Worker',
                'Unemployed (Short-Term, 6 months or less)',
                'Unemployed (Long-Term, more than 6 months)',
                'Unemployed (Not in Labor Force)',
                'Retired',
                'Unknown/not reported'
            ]
        ];

        return $fallbacks[$category] ?? [];
    }

    public function validateFile(string $filePath, array $options = []): array
    {
        // Set selected reference data set if provided
        if (!empty($options['reference_data_set_id'])) {
            $this->selectedReferenceDataSet = ReferenceDataSet::find($options['reference_data_set_id']);
            // Also set it in the transformer for consistency
            $this->transformer->setSelectedReferenceDataSet($this->selectedReferenceDataSet);
        }
        
        try {
            
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());
            
            $errors = [];
            $warnings = [];
            
            // Validate headers
            $headerValidation = $this->validateHeaders($headers);
            if (!empty($headerValidation['errors'])) {
                $errors = array_merge($errors, $headerValidation['errors']);
            }
            if (!empty($headerValidation['warnings'])) {
                $warnings = array_merge($warnings, $headerValidation['warnings']);
            }
            
            // If header validation failed, return early
            if (!empty($errors)) {
                return [
                    'valid' => false,
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'record_count' => count($records)
                ];
            }
            
            // Validate records
            $recordValidation = $this->validateRecords($records);
            $errors = array_merge($errors, $recordValidation['errors']);
            $warnings = array_merge($warnings, $recordValidation['warnings']);
            
            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
                'record_count' => count($records),
                'household_count' => $this->countHouseholds($records)
            ];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['File processing error: ' . $e->getMessage()],
                'warnings' => [],
                'record_count' => 0
            ];
        }
    }

    public function previewImport(string $filePath, int $limit = 10): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $stmt = Statement::create()->limit($limit);
            $records = iterator_to_array($stmt->process($csv));
            
            $preview = [];
            foreach ($records as $index => $record) {
                $preview[] = [
                    'row_number' => $index + 2, // +2 because index starts at 0 and we have a header row
                    'data' => $record,
                    'parsed' => $this->parseRecord($record),
                    'validation' => $this->validateSingleRecord($record, $index + 2)
                ];
            }
            
            return [
                'success' => true,
                'preview' => $preview,
                'total_records' => count(iterator_to_array($csv->getRecords()))
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'File processing error: ' . $e->getMessage(),
                'preview' => []
            ];
        }
    }

    public function processImport(string $filePath, array $options = []): array
    {
        $updateExisting = $options['update_existing'] ?? false;
        $skipErrors = $options['skip_errors'] ?? false;
        $chunkSize = $options['chunk_size'] ?? 100; // Process 100 records at a time
        
        // Set selected reference data set if provided
        if (!empty($options['reference_data_set_id'])) {
            $this->selectedReferenceDataSet = ReferenceDataSet::find($options['reference_data_set_id']);
            // Also set it in the transformer for consistency
            $this->transformer->setSelectedReferenceDataSet($this->selectedReferenceDataSet);
        }
        
        try {
            DB::beginTransaction();
            
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $results = [
                'success' => true,
                'processed_households' => 0,
                'processed_participants' => 0,
                'updated_households' => 0,
                'updated_participants' => 0,
                'skipped_records' => 0,
                'errors' => []
            ];
            
            $currentHousehold = null;
            $householdParticipants = [];
            $recordCount = 0;
            $processedChunks = 0;
            
            // Process CSV in chunks for better memory efficiency
            $stmt = (new \League\Csv\Statement())
                ->offset(0)
                ->limit($chunkSize);
            
            do {
                $records = $stmt->process($csv);
                $hasRecords = false;
                
                foreach ($records as $index => $record) {
                    $hasRecords = true;
                    $rowNumber = ($processedChunks * $chunkSize) + $index + 2;
                    $recordCount++;
                    
                    try {
                        $parsedRecord = $this->parseRecord($record);
                        $validation = $this->validateSingleRecord($record, $rowNumber);
                        
                        if (!$validation['valid'] && !$skipErrors) {
                            throw new \Exception(implode('; ', $validation['errors']));
                        }
                        
                        if (!$validation['valid'] && $skipErrors) {
                            $results['skipped_records']++;
                            $results['errors'][] = "Row {$rowNumber}: " . implode('; ', $validation['errors']);
                            continue;
                        }
                        
                        // Check if this is a new household (only when household_code is provided and different)
                        if (!empty($record['household_code']) && 
                            (!$currentHousehold || $currentHousehold->household_code !== $record['household_code'])) {
                            
                            // Process previous household participants if any
                            if ($currentHousehold && !empty($householdParticipants)) {
                                $processedCounts = $this->processHouseholdParticipants($currentHousehold, $householdParticipants, $updateExisting);
                                $results['processed_participants'] += $processedCounts['created'];
                                $results['updated_participants'] += $processedCounts['updated'];
                            }
                            
                            // Create or find household
                            $currentHousehold = $this->processHousehold($parsedRecord, $updateExisting);
                            $householdParticipants = [];
                            
                            if ($updateExisting && $currentHousehold->wasRecentlyCreated === false) {
                                $results['updated_households']++;
                            } else {
                                $results['processed_households']++;
                            }
                        } elseif (empty($record['household_code']) && $currentHousehold) {
                            // Family member with no household_code - inherit from current household
                            $parsedRecord['household']['household_code'] = $currentHousehold->household_code;
                            
                            // Copy essential household data from current household for consistency
                            $parsedRecord['household']['id'] = $currentHousehold->id;
                        }
                        
                        // Add participant to current household
                        if ($currentHousehold) {
                            $parsedRecord['household_id'] = $currentHousehold->id; // Ensure household_id is set
                            $householdParticipants[] = $parsedRecord;
                        }
                        
                    } catch (\Exception $e) {
                        if (!$skipErrors) {
                            throw $e;
                        }
                        
                        $results['skipped_records']++;
                        $results['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
                    }
                }
                
                // Move to next chunk
                $processedChunks++;
                $stmt = $stmt->offset($processedChunks * $chunkSize);
                
                // Log progress for large imports
                if ($recordCount % 500 === 0) {
                    Log::info('Import progress', [
                        'processed_records' => $recordCount,
                        'processed_households' => $results['processed_households'],
                        'processed_participants' => $results['processed_participants']
                    ]);
                }
                
            } while ($hasRecords);
            
            // Process final household participants
            if ($currentHousehold && !empty($householdParticipants)) {
                $processedCounts = $this->processHouseholdParticipants($currentHousehold, $householdParticipants, $updateExisting);
                $results['processed_participants'] += $processedCounts['created'];
                $results['updated_participants'] += $processedCounts['updated'];
            }
            
            DB::commit();
            
            return $results;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed_households' => 0,
                'processed_participants' => 0,
                'updated_households' => 0,
                'updated_participants' => 0,
                'skipped_records' => 0,
                'errors' => []
            ];
        }
    }

    protected function validateHeaders(array $headers): array
    {
        $errors = [];
        $warnings = [];
        
        // Check for required headers
        foreach ($this->requiredHeaders as $required) {
            if (!in_array($required, $headers)) {
                $errors[] = "Missing required header: {$required}";
            }
        }
        
        // Check for unknown headers
        $validHeaders = array_merge(
            $this->householdFields,
            array_keys($this->getParticipantFields())
        );
        
        foreach ($headers as $header) {
            if (!in_array($header, $validHeaders)) {
                $warnings[] = "Unknown header (will be ignored): {$header}";
            }
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    protected function validateRecords(array $records): array
    {
        $errors = [];
        $warnings = [];
        $householdCodes = [];
        
        foreach ($records as $index => $record) {
            $rowNumber = $index + 2;
            $validation = $this->validateSingleRecord($record, $rowNumber);
            
            $errors = array_merge($errors, $validation['errors']);
            $warnings = array_merge($warnings, $validation['warnings']);
            
            // Track household codes for duplicate checking
            if (!empty($record['household_code'])) {
                $householdCodes[] = $record['household_code'];
            }
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    protected function validateSingleRecord(array $record, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];
        
        // Required field validation
        foreach ($this->requiredHeaders as $field) {
            // Special handling for household_code - only required for HEAD_OF_HOUSEHOLD
            if ($field === 'household_code') {
                if (empty($record[$field]) && 
                    (!isset($record['participant_type']) || $record['participant_type'] === 'HEAD_OF_HOUSEHOLD')) {
                    $errors[] = "Row {$rowNumber}: Missing required field 'household_code' for head of household";
                }
            } else {
                if (empty($record[$field])) {
                    $errors[] = "Row {$rowNumber}: Missing required field '{$field}'";
                }
            }
        }
        
        // Participant type validation
        if (!empty($record['participant_type']) && 
            !in_array($record['participant_type'], ['HEAD_OF_HOUSEHOLD', 'FAMILY_MEMBER'])) {
            $errors[] = "Row {$rowNumber}: Invalid participant_type. Must be 'HEAD_OF_HOUSEHOLD' or 'FAMILY_MEMBER'";
        }
        
        // Date validation
        if (!empty($record['date_of_birth']) && !$this->isValidDate($record['date_of_birth'])) {
            $errors[] = "Row {$rowNumber}: Invalid date_of_birth format. Use YYYY-MM-DD";
        }
        
        // Enum field validation
        $enumFields = ['gender', 'ethnicity', 'education_level', 'disability_status', 'health_insurance_status', 'military_status', 'work_status'];
        foreach ($enumFields as $field) {
            if (!empty($record[$field])) {
                $validValues = $this->getValidEnumValues($field);
                if (!empty($validValues) && !in_array($record[$field], $validValues)) {
                    $validOptions = implode('; ', array_slice($validValues, 0, 5));
                    if (count($validValues) > 5) {
                        $validOptions .= '... (' . count($validValues) . ' total options)';
                    }
                    $errors[] = "Row {$rowNumber}: Invalid value for '{$field}': '{$record[$field]}'. Valid options are: {$validOptions}";
                }
            }
        }
        
        // Household field validation
        $householdEnumFields = ['household_type', 'housing_status'];
        foreach ($householdEnumFields as $field) {
            if (!empty($record[$field])) {
                $validValues = $this->getValidEnumValues($field);
                if (!empty($validValues) && !in_array($record[$field], $validValues)) {
                    $validOptions = implode('; ', $validValues);
                    $errors[] = "Row {$rowNumber}: Invalid value for '{$field}': '{$record[$field]}'. Valid options are: {$validOptions}";
                }
            }
        }
        
        // Numeric field validation
        $numericFields = ['household_size', 'monthly_income', 'annual_income', 'hhs_poverty_percentage', 'employment_income', 'hours_worked_per_week'];
        foreach ($numericFields as $field) {
            if (!empty($record[$field]) && !is_numeric($record[$field])) {
                $errors[] = "Row {$rowNumber}: '{$field}' must be numeric";
            }
        }
        
        // Boolean field validation
        $booleanFields = ['currently_enrolled_in_education', 'has_chronic_health_conditions', 'is_seeking_employment', 'is_underemployed'];
        foreach ($booleanFields as $field) {
            if (!empty($record[$field]) && !in_array(strtoupper($record[$field]), ['TRUE', 'FALSE', '1', '0'])) {
                $warnings[] = "Row {$rowNumber}: '{$field}' should be TRUE/FALSE or 1/0";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    protected function parseRecord(array $record): array
    {
        $parsed = [];
        
        // Parse household data
        foreach ($this->householdFields as $field) {
            $parsed['household'][$field] = $this->parseField($record[$field] ?? '', $field);
        }
        
        // Parse participant data
        foreach ($this->getParticipantFields() as $field => $type) {
            $parsed['participant'][$field] = $this->parseField($record[$field] ?? '', $field, $type);
        }
        
        return $parsed;
    }

    protected function parseField($value, string $field, string $type = 'string')
    {
        if (empty($value)) {
            return null;
        }
        
        // Apply transformation for demographic fields
        $transformedValue = $this->applyTransformation($field, $value);
        
        switch ($type) {
            case 'boolean':
                return in_array(strtoupper($transformedValue), ['TRUE', '1']);
            case 'decimal':
                return is_numeric($transformedValue) ? (float) $transformedValue : null;
            case 'integer':
                return is_numeric($transformedValue) ? (int) $transformedValue : null;
            case 'date':
                return is_string($transformedValue) ? $this->parseDate($transformedValue) : $transformedValue;
            case 'array':
                return $this->parseArray($transformedValue);
            default:
                return $transformedValue;
        }
    }

    protected function applyTransformation(string $field, $value)
    {
        if (empty($value)) {
            return $value;
        }

        // Apply transformation based on field type
        switch ($field) {
            case 'gender':
                return $this->transformer->transformGender($value);
            case 'ethnicity':
                return $this->transformer->transformEthnicity($value);
            case 'education_level':
                return $this->transformer->transformEducationLevel($value);
            case 'military_status':
                return $this->transformer->transformMilitaryStatus($value);
            case 'disability_status':
                return $this->transformer->transformDisabilityStatus($value);
            case 'health_insurance_status':
                return $this->transformer->transformHealthInsuranceStatus($value);
            case 'work_status':
                return $this->transformer->transformWorkStatus($value);
            case 'household_type':
                return $this->transformer->transformHouseholdType($value);
            case 'housing_status':
                return $this->transformer->transformHousingStatus($value);
            case 'race':
                $raceArray = $this->transformer->transformRace($value);
                return $raceArray; // Return as array for JSON storage
            default:
                return $value;
        }
    }

    protected function parseArray($value): ?array
    {
        // If it's already an array, return it
        if (is_array($value)) {
            return empty($value) ? null : $value;
        }
        
        // If it's a string, parse it
        if (is_string($value)) {
            if (empty($value)) {
                return null;
            }
            return array_map('trim', explode('|', $value));
        }
        
        // For any other type, return null
        return null;
    }

    protected function parseDate(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function isValidDate(string $date): bool
    {
        return $this->parseDate($date) !== null;
    }

    protected function processHousehold(array $parsedRecord, bool $updateExisting): Household
    {
        $householdData = $parsedRecord['household'];
        $householdCode = $householdData['household_code'];
        
        if ($updateExisting) {
            $household = Household::firstOrNew(['household_code' => $householdCode]);
        } else {
            $household = Household::where('household_code', $householdCode)->first();
            if ($household) {
                throw new \Exception("Household with code '{$householdCode}' already exists");
            }
            $household = new Household();
        }
        
        // Extract address fields before processing
        $addressFields = ['street_address', 'city', 'state', 'zip_code'];
        $addressData = [];
        
        foreach ($householdData as $field => $value) {
            if (in_array($field, $addressFields)) {
                // Map street_address to address for the address history table
                if ($field === 'street_address') {
                    $addressData['address'] = $value;
                } else {
                    $addressData[$field] = $value;
                }
            } elseif ($value !== null && $field !== 'household_notes') {
                $household->$field = $value;
            } elseif ($field === 'household_notes') {
                $household->notes = $value;
            }
        }
        
        $household->save();
        
        // Create address history record if we have address data
        if (!empty(array_filter($addressData))) {
            $household->updateAddress($addressData, 'Initial import from participant data');
        }
        
        // Calculate poverty percentage after saving household data
        $this->calculateAndUpdatePovertyLevel($household);
        $household->save();
        
        return $household;
    }

    protected function processHouseholdParticipants(Household $household, array $participantsData, bool $updateExisting): array
    {
        $counts = ['created' => 0, 'updated' => 0];
        
        foreach ($participantsData as $participantData) {
            $participant = $participantData['participant'];
            $participant['household_id'] = $household->id;
            
            // Determine if this is head of household
            $participant['is_head_of_household'] = 
                ($participantData['participant']['participant_type'] ?? '') === 'HEAD_OF_HOUSEHOLD';
            
            // Remove participant_type as it's not a database field
            unset($participant['participant_type']);

            $dateOfBirth = $participant['date_of_birth'];
            $dateOfBirthString = null;
            if ($dateOfBirth instanceof \Carbon\CarbonInterface) {
                $dateOfBirthString = $dateOfBirth->format('Y-m-d');
            } elseif (!empty($dateOfBirth)) {
                $dateOfBirthString = Carbon::parse($dateOfBirth)->format('Y-m-d');
            }

            $participant['date_of_birth'] = $dateOfBirthString;

            if ($updateExisting) {
                $lookup = [
                    'household_id' => $household->id,
                    'first_name' => $participant['first_name'],
                    'last_name' => $participant['last_name'],
                ];

                if ($dateOfBirthString !== null) {
                    $lookup['date_of_birth'] = $dateOfBirthString;
                }

                $savedParticipant = Participant::updateOrCreate($lookup, $participant);

                if ($savedParticipant->wasRecentlyCreated) {
                    $counts['created']++;
                } else {
                    $counts['updated']++;
                }
            } else {
                Participant::create($participant);
                $counts['created']++;
            }
        }
        
        // Update household size based on actual participant count
        $household->household_size = $household->participants()->count();
        
        // Calculate and update poverty percentage if we have income data
        $this->calculateAndUpdatePovertyLevel($household);
        
        $household->save();
        
        return $counts;
    }

    protected function countHouseholds(array $records): int
    {
        $householdCodes = [];
        foreach ($records as $record) {
            if (!empty($record['household_code']) && !in_array($record['household_code'], $householdCodes)) {
                $householdCodes[] = $record['household_code'];
            }
        }
        return count($householdCodes);
    }

    protected function calculateAndUpdatePovertyLevel(Household $household): void
    {
        $incomeAmount = null;
        $incomeType = null;
        
        // Determine income amount and type (prioritize annual, then monthly)
        if ($household->annual_income && $household->annual_income > 0) {
            $incomeAmount = $household->annual_income;
            $incomeType = 'annual';
        } elseif ($household->monthly_income && $household->monthly_income > 0) {
            $incomeAmount = $household->monthly_income;
            $incomeType = 'monthly';
        }
        
        if ($incomeAmount && $household->household_size > 0) {
            $povertyPercentage = PovertyGuideline::calculatePovertyPercentage(
                $incomeAmount,
                $household->household_size,
                $incomeType
            );
            
            if ($povertyPercentage !== null) {
                // Cap poverty percentage at 999.99% to fit database constraints (DECIMAL(5,2))
                $cappedPovertyPercentage = min($povertyPercentage, 999.99);
                
                $household->hhs_poverty_percentage = $cappedPovertyPercentage;
                
                // Ensure both annual and monthly income are set
                if ($incomeType === 'annual') {
                    $household->annual_income = $incomeAmount;
                    $household->monthly_income = $incomeAmount / 12;
                } else {
                    $household->monthly_income = $incomeAmount;
                    $household->annual_income = $incomeAmount * 12;
                }
                
                // Log if we had to cap the value
                if ($povertyPercentage > 999.99) {
                    Log::warning('General Import: Poverty percentage capped', [
                        'household_id' => $household->id,
                        'household_code' => $household->household_code,
                        'original_percentage' => $povertyPercentage,
                        'capped_percentage' => $cappedPovertyPercentage,
                        'income_amount' => $incomeAmount,
                        'income_type' => $incomeType,
                        'household_size' => $household->household_size,
                    ]);
                }
                
                Log::info('General Import: Poverty percentage calculated', [
                    'household_id' => $household->id,
                    'household_code' => $household->household_code,
                    'poverty_percentage' => $cappedPovertyPercentage,
                    'income_amount' => $incomeAmount,
                    'income_type' => $incomeType,
                    'household_size' => $household->household_size,
                ]);
            }
        }
    }

    protected function getParticipantFields(): array
    {
        return [
            'participant_type' => 'string',
            'first_name' => 'string',
            'middle_name' => 'string',
            'last_name' => 'string',
            'suffix' => 'string',
            'date_of_birth' => 'date',
            'gender' => 'string',
            'telephone' => 'string',
            'email' => 'string',
            'education_level' => 'string',
            'currently_enrolled_in_education' => 'boolean',
            'education_enrollment_type' => 'string',
            'disability_status' => 'string',
            'disability_types' => 'array',
            'assistance_needs' => 'array',
            'has_chronic_health_conditions' => 'boolean',
            'health_insurance_sources' => 'array',
            'health_insurance_status' => 'string',
            'ethnicity' => 'string',
            'race' => 'array',
            'military_status' => 'string',
            'work_status' => 'string',
            'hours_worked_per_week' => 'decimal',
            'is_seeking_employment' => 'boolean',
            'is_underemployed' => 'boolean',
            'income_sources' => 'array',
            'employment_income' => 'decimal',
            'other_income_sources' => 'array',
            'non_cash_benefits' => 'array',
            'intake_date' => 'date',
            'last_service_date' => 'date',
            'documentation_notes' => 'string',
            'participant_notes' => 'string'
        ];
    }
}
