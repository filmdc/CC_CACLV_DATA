<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Household;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Models\Service;
use App\Models\ServiceEnrollment;
use App\Models\CustomImportTemplate;
use App\Models\CustomImportLog;
use App\Models\PovertyGuideline;
use App\Services\DataTransformationService;
use League\Csv\Reader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HMISPennsylvaniaImportService
{
    protected DataTransformationService $transformer;
    protected CustomImportTemplate $template;
    
    const SIXTH_STREET_SHELTER_PROGRAM_ID = 4;

    public function __construct(DataTransformationService $transformer)
    {
        $this->transformer = $transformer;
        $this->template = $this->getOrCreateTemplate();
    }

    protected function getOrCreateTemplate(): CustomImportTemplate
    {
        $template = CustomImportTemplate::getBySourceSystem('HMIS_Pennsylvania');
        
        if (!$template) {
            $template = CustomImportTemplate::create([
                'name' => 'HMIS Pennsylvania ClientTrack - Active Client List',
                'description' => 'Pre-configured template for importing HMIS Active Client List exports from Pennsylvania ClientTrack system',
                'type' => 'preconfigured',
                'source_system' => 'HMIS_Pennsylvania',
                'active' => true,
                'field_mappings' => $this->getFieldMappings(),
                'transformation_rules' => $this->getTransformationRules(),
                'validation_rules' => $this->getValidationRules(),
                'import_settings' => $this->getImportSettings(),
                'created_by' => Auth::id() ?? 1,
            ]);
        }

        return $template;
    }

    protected function getFieldMappings(): array
    {
        return [
            'OrganizationName' => null, // Not mapped, just for reference
            'ProgramName' => 'service_name', // Maps to service enrollment for program ID 4
            'CaseID' => 'household_code',
            'ClientName' => 'full_name', // Will be parsed into first/middle/last
            'DOB' => 'date_of_birth',
            'Gender' => 'gender',
            'RaceAndEthnicity' => 'race_ethnicity', // Will be parsed into race and ethnicity
            'VeteranStatus' => 'military_status',
            'Relationship' => 'relationship', // Will be converted to is_head_of_household
            'HouseholdType' => 'household_type',
            'HealthInsurance' => 'health_insurance_status',
            'DisablingCondition' => 'disability_status',
            'CashIncome' => 'monthly_income',
            'EnrollDate' => 'enrollment_date',
            'ExitDate' => 'exit_date',
            'ClientID' => 'client_id', // For reference tracking
            'EnrollID' => 'enroll_id', // For reference tracking
        ];
    }

    protected function getTransformationRules(): array
    {
        return [
            'date_of_birth' => ['format' => 'm/d/Y'],
            'enrollment_date' => ['format' => 'm/d/Y H:i:s A'],
            'exit_date' => ['format' => 'm/d/Y H:i:s A'],
            'gender' => ['type' => 'predefined_mapping'],
            'military_status' => ['type' => 'predefined_mapping'],
            'disability_status' => ['type' => 'predefined_mapping'],
            'health_insurance_status' => ['type' => 'predefined_mapping'],
            'household_type' => ['type' => 'predefined_mapping'],
            'full_name' => ['type' => 'name_parsing', 'format' => 'last_first_middle'],
            'race_ethnicity' => ['type' => 'race_ethnicity_parsing'],
            'relationship' => ['type' => 'head_of_household_conversion'],
        ];
    }

    protected function getValidationRules(): array
    {
        return [
            'household_code' => ['required'],
            'full_name' => ['required'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required'],
        ];
    }

    protected function getImportSettings(): array
    {
        return [
            'default_program_id' => self::SIXTH_STREET_SHELTER_PROGRAM_ID,
            'auto_enroll_in_program' => true,
            'auto_enroll_in_services' => true,
            'create_services_from_program_name' => true,
            'update_existing_participants' => true,
            'skip_duplicate_enrollments' => true,
        ];
    }

    public function validateFile(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            
            // Clean corrupted headers - fix the specific ". The " corruption
            $cleanedHeaders = array_map(function($header) {
                // Fix the specific corruption where "OrganizationName" was overwritten with ". The "
                if (strpos($header, '. The ') === 0) {
                    return 'OrganizationName';
                }
                return trim($header);
            }, $headers);
            
            $records = iterator_to_array($csv->getRecords());
            
            $errors = [];
            $warnings = [];
            
            // Debug logging
            Log::info('CSV Headers found', ['original' => $headers, 'cleaned' => $cleanedHeaders]);
            Log::info('CSV Records count', ['count' => count($records)]);
            if (!empty($records)) {
                Log::info('First record sample', ['record' => $records[0] ?? 'No first record']);
            }
            
            // Validate required headers (skip null mappings like OrganizationName)
            $fieldMappings = $this->getFieldMappings();
            $requiredHeaders = array_keys(array_filter($fieldMappings, function($mapping) {
                return $mapping !== null;
            }));
            
            foreach ($requiredHeaders as $header) {
                if (!in_array($header, $cleanedHeaders)) {
                    $errors[] = "Missing required header: {$header}";
                }
            }
            
            if (!empty($errors)) {
                return [
                    'valid' => false,
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'record_count' => count($records)
                ];
            }
            
            // Validate sample records
            if (!empty($records)) {
                // Convert to array_values to ensure numeric indexing
                $recordsArray = array_values($records);
                $sampleSize = min(10, count($recordsArray));
                
                for ($i = 0; $i < $sampleSize; $i++) {
                    if (!isset($recordsArray[$i])) {
                        $errors[] = "Missing record at index {$i}";
                        continue;
                    }
                    
                    $record = $recordsArray[$i];
                    $rowNumber = $i + 2; // Account for header row
                    
                    $validation = $this->validateRecord($record, $rowNumber);
                    $errors = array_merge($errors, $validation['errors']);
                    $warnings = array_merge($warnings, $validation['warnings']);
                }
            } else {
                $errors[] = "No data records found in CSV file";
            }
            
            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
                'record_count' => count($records),
                'household_count' => $this->countUniqueHouseholds(array_values($records))
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

    public function processImport(string $filePath, array $options = []): array
    {
        $log = CustomImportLog::create([
            'template_id' => $this->template->id,
            'user_id' => Auth::id(),
            'original_filename' => basename($filePath),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            DB::beginTransaction();
            
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            // Apply the same header cleaning as in validateFile
            $headers = $csv->getHeader();
            $cleanedHeaders = array_map(function($header) {
                if (strpos($header, '. The ') === 0) {
                    return 'OrganizationName';
                }
                return trim($header);
            }, $headers);
            
            $records = iterator_to_array($csv->getRecords());
            
            $results = [
                'success' => true,
                'processed_households' => 0,
                'processed_participants' => 0,
                'processed_enrollments' => 0,
                'updated_households' => 0,
                'updated_participants' => 0,
                'skipped_records' => 0,
                'errors' => [],
                'warnings' => []
            ];
            
            $householdGroups = $this->groupRecordsByHousehold($records);
            
            foreach ($householdGroups as $householdCode => $householdRecords) {
                try {
                    $householdResult = $this->processHouseholdGroup($householdCode, $householdRecords, $options);
                    
                    $results['processed_households'] += $householdResult['households'];
                    $results['processed_participants'] += $householdResult['participants'];
                    $results['processed_enrollments'] += $householdResult['enrollments'];
                    $results['updated_households'] += $householdResult['updated_households'];
                    $results['updated_participants'] += $householdResult['updated_participants'];
                    $results['warnings'] = array_merge($results['warnings'], $householdResult['warnings']);
                    
                } catch (\Exception $e) {
                    $error = "Household {$householdCode}: " . mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');
                    $results['errors'][] = $error;
                    $results['skipped_records'] += count($householdRecords);
                    
                    // Log detailed error for debugging
                    Log::error('Household processing failed', [
                        'household_code' => $householdCode,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'records_count' => count($householdRecords)
                    ]);
                }
            }
            
            DB::commit();
            
            // Sanitize errors and warnings for UTF-8 encoding
            $cleanErrors = array_map(function($error) {
                return mb_convert_encoding($error, 'UTF-8', 'UTF-8');
            }, $results['errors']);
            
            $cleanWarnings = array_map(function($warning) {
                return mb_convert_encoding($warning, 'UTF-8', 'UTF-8');
            }, $results['warnings']);
            
            // Update log
            $log->update([
                'status' => empty($cleanErrors) ? 'success' : 'partial',
                'total_records' => count($records),
                'successful_records' => $results['processed_participants'],
                'failed_records' => $results['skipped_records'],
                'errors' => $cleanErrors,
                'warnings' => $cleanWarnings,
                'completed_at' => now(),
            ]);
            
            Log::info('HMIS Pennsylvania import completed', [
                'template_id' => $this->template->id,
                'user_id' => Auth::id(),
                'results' => $results
            ]);
            
            return $results;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $log->update([
                'status' => 'failed',
                'total_records' => count($records ?? []),
                'successful_records' => 0,
                'failed_records' => count($records ?? []),
                'errors' => [mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8')],
                'completed_at' => now(),
            ]);
            
            Log::error('HMIS Pennsylvania import failed', [
                'error' => $e->getMessage(),
                'template_id' => $this->template->id,
                'user_id' => Auth::id()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed_households' => 0,
                'processed_participants' => 0,
                'processed_enrollments' => 0,
                'updated_households' => 0,
                'updated_participants' => 0,
                'skipped_records' => 0,
                'errors' => [$e->getMessage()],
                'warnings' => []
            ];
        }
    }

    protected function validateRecord(array $record, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];
        
        // Required fields validation
        if (empty($record['CaseID'])) {
            $errors[] = "Row {$rowNumber}: Missing CaseID (household code)";
        }
        
        if (empty($record['ClientName'])) {
            $errors[] = "Row {$rowNumber}: Missing ClientName";
        }
        
        if (empty($record['DOB'])) {
            $errors[] = "Row {$rowNumber}: Missing DOB (date of birth)";
        } else {
            $date = $this->transformer->parseDate($record['DOB'], 'm/d/Y');
            if (!$date) {
                $errors[] = "Row {$rowNumber}: Invalid DOB format: {$record['DOB']}";
            }
        }
        
        if (empty($record['Gender'])) {
            $errors[] = "Row {$rowNumber}: Missing Gender";
        }
        
        // Data quality warnings
        if (empty($record['Relationship'])) {
            $warnings[] = "Row {$rowNumber}: Missing Relationship - will default to family member";
        }
        
        if (empty($record['RaceAndEthnicity'])) {
            $warnings[] = "Row {$rowNumber}: Missing RaceAndEthnicity";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    protected function groupRecordsByHousehold(array $records): array
    {
        $groups = [];
        
        foreach ($records as $record) {
            $householdCode = $record['CaseID'];
            if (!isset($groups[$householdCode])) {
                $groups[$householdCode] = [];
            }
            $groups[$householdCode][] = $record;
        }
        
        return $groups;
    }

    protected function processHouseholdGroup(string $householdCode, array $records, array $options): array
    {
        $results = [
            'households' => 0,
            'participants' => 0,
            'enrollments' => 0,
            'updated_households' => 0,
            'updated_participants' => 0,
            'warnings' => []
        ];
        
        // Find or create household
        $household = Household::firstOrNew(['household_code' => $householdCode]);
        $isNewHousehold = !$household->exists;
        
        // Get household data from first record (they should all be the same for household-level data)
        $firstRecord = $records[0];
        $householdData = $this->transformHouseholdData($firstRecord);
        
        foreach ($householdData as $field => $value) {
            if ($value !== null) {
                $household->$field = $value;
            }
        }
        
        // Set default household_size if not already set
        if (!$household->household_size) {
            $household->household_size = 1; // Will be updated later with actual count
        }
        
        $household->save();
        
        if ($isNewHousehold) {
            $results['households'] = 1;
        } else {
            $results['updated_households'] = 1;
        }
        
        // Process each participant in the household
        foreach ($records as $record) {
            try {
                $participantResult = $this->processParticipant($household, $record, $options);
                $results['participants'] += $participantResult['participants'];
                $results['enrollments'] += $participantResult['enrollments'];
                $results['updated_participants'] += $participantResult['updated_participants'];
                $results['warnings'] = array_merge($results['warnings'], $participantResult['warnings']);
            } catch (\Exception $e) {
                Log::error('Participant processing failed', [
                    'household_code' => $householdCode,
                    'participant_data' => $record,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // Re-throw to be caught by household processing
            }
        }
        
        // Update household size and calculate poverty percentage
        $actualCount = $household->participants()->count();
        $household->household_size = $actualCount;
        
        // Calculate poverty percentage if we have income data
        if ($household->annual_income && $household->annual_income > 0) {
            $povertyPercentage = PovertyGuideline::calculatePovertyPercentage(
                $household->annual_income,
                $actualCount,
                'annual'
            );
            
            if ($povertyPercentage !== null) {
                $household->hhs_poverty_percentage = $povertyPercentage;
            } else {
                $results['warnings'][] = "Could not calculate poverty percentage for household {$householdCode} - poverty guidelines not found for family size {$actualCount}";
            }
        }
        
        $household->save();
        
        // Refresh the household relationship and update CSBG computed fields for all participants
        $household->load('participants');
        foreach ($household->participants as $participant) {
            $participant->updateCSBGComputedFields();
        }
        
        return $results;
    }

    protected function transformHouseholdData(array $record): array
    {
        $data = [];
        
        // Household type
        if (!empty($record['HouseholdType'])) {
            $data['household_type'] = $this->transformer->parseHouseholdType($record['HouseholdType']);
        }
        
        // Monthly income
        if (!empty($record['CashIncome']) && is_numeric($record['CashIncome'])) {
            $data['monthly_income'] = (float) $record['CashIncome'];
            $data['annual_income'] = $data['monthly_income'] * 12;
        }
        
        return $data;
    }

    protected function processParticipant(Household $household, array $record, array $options): array
    {
        $results = [
            'participants' => 0,
            'enrollments' => 0,
            'updated_participants' => 0,
            'warnings' => []
        ];
        
        $participantData = $this->transformParticipantData($record);
        $participantData['household_id'] = $household->id;
        
        // Find existing participant or create new one
        $participant = null;
        
        if (!empty($record['ClientID'])) {
            // Try to find by ClientID first (stored in notes field)
            $participant = Participant::where('household_id', $household->id)
                ->where('notes', 'LIKE', '%ClientID:' . $record['ClientID'] . '%')
                ->first();
        }
        
        if (!$participant) {
            // Try to find by name and DOB
            $nameData = $this->transformer->parseName($record['ClientName']);
            $dob = $this->transformer->parseDate($record['DOB'], 'm/d/Y');
            
            if ($dob) {
                $participant = Participant::where('household_id', $household->id)
                    ->where('first_name', $nameData['first_name'])
                    ->where('last_name', $nameData['last_name'])
                    ->where('date_of_birth', $dob->format('Y-m-d'))
                    ->first();
            }
        }
        
        $isNewParticipant = !$participant;
        
        if ($isNewParticipant) {
            $participant = Participant::create($participantData);
            $results['participants'] = 1;
        } else {
            $participant->update($participantData);
            $results['updated_participants'] = 1;
        }
        
        // Update CSBG computed fields (age group, income classification, disconnected youth status)
        $participant->updateCSBGComputedFields();
        
        // Handle program enrollment
        if ($this->template->getImportSetting('auto_enroll_in_program', false)) {
            $enrollmentResult = $this->processEnrollment($participant, $record, $options);
            $results['enrollments'] += $enrollmentResult['enrollments'];
            $results['warnings'] = array_merge($results['warnings'], $enrollmentResult['warnings']);
        }
        
        return $results;
    }

    protected function transformParticipantData(array $record): array
    {
        $data = [];
        
        // Parse name
        if (!empty($record['ClientName'])) {
            $nameData = $this->transformer->parseName($record['ClientName']);
            $data = array_merge($data, $nameData);
        }
        
        // Date of birth
        if (!empty($record['DOB'])) {
            $dob = $this->transformer->parseDate($record['DOB'], 'm/d/Y');
            if ($dob) {
                $data['date_of_birth'] = $dob;
            }
        }
        
        // Gender
        if (!empty($record['Gender'])) {
            $data['gender'] = $this->transformer->transformGender($record['Gender']);
        }
        
        // Race and ethnicity
        if (!empty($record['RaceAndEthnicity'])) {
            $raceEthnicityData = $this->transformer->parseRaceAndEthnicity($record['RaceAndEthnicity']);
            if ($raceEthnicityData['race']) {
                $data['race'] = [$raceEthnicityData['race']];
            }
            if ($raceEthnicityData['ethnicity']) {
                $data['ethnicity'] = $raceEthnicityData['ethnicity'];
            }
        }
        
        // Military status
        if (!empty($record['VeteranStatus'])) {
            $data['military_status'] = $this->transformer->transformVeteranStatus($record['VeteranStatus']);
        }
        
        // Head of household
        if (!empty($record['Relationship'])) {
            $data['is_head_of_household'] = $this->transformer->transformRelationshipToHeadOfHousehold($record['Relationship']);
        }
        
        // Health insurance
        if (!empty($record['HealthInsurance'])) {
            $data['health_insurance_status'] = $this->transformer->transformHealthInsurance($record['HealthInsurance']);
        }
        
        // Disability status
        if (!empty($record['DisablingCondition'])) {
            $data['disability_status'] = $this->transformer->transformDisabilityStatus($record['DisablingCondition']);
        }
        
        // Add ClientID to notes for tracking
        if (!empty($record['ClientID'])) {
            $data['notes'] = "HMIS ClientID:" . $record['ClientID'];
            if (!empty($record['EnrollID'])) {
                $data['notes'] .= " | EnrollID:" . $record['EnrollID'];
            }
        }
        
        return $data;
    }

    protected function processEnrollment(Participant $participant, array $record, array $options): array
    {
        $results = [
            'enrollments' => 0,
            'warnings' => []
        ];
        
        $programId = $this->template->getImportSetting('default_program_id');
        $program = Program::find($programId);
        
        if (!$program) {
            $results['warnings'][] = "Program with ID {$programId} not found for participant {$participant->id}";
            return $results;
        }
        
        // Parse enrollment date
        $enrollmentDate = null;
        if (!empty($record['EnrollDate'])) {
            $enrollmentDate = $this->transformer->parseDate($record['EnrollDate'], 'm/d/Y H:i:s A');
        }
        
        // Parse exit date
        $exitDate = null;
        if (!empty($record['ExitDate'])) {
            $exitDate = $this->transformer->parseDate($record['ExitDate'], 'm/d/Y H:i:s A');
        }
        
        // Process program enrollment
        $programEnrollmentResult = $this->processProgramEnrollment($participant, $programId, $enrollmentDate, $exitDate, $record);
        $results['enrollments'] += $programEnrollmentResult['enrollments'];
        $results['warnings'] = array_merge($results['warnings'], $programEnrollmentResult['warnings']);
        
        // Process service enrollment based on ProgramName (requires program enrollment to exist)
        if (!empty($record['ProgramName']) && isset($programEnrollmentResult['program_enrollment'])) {
            $serviceEnrollmentResult = $this->processServiceEnrollment(
                $participant, 
                $record['ProgramName'], 
                $enrollmentDate, 
                $exitDate, 
                $record, 
                $programEnrollmentResult['program_enrollment']
            );
            $results['enrollments'] += $serviceEnrollmentResult['enrollments'];
            $results['warnings'] = array_merge($results['warnings'], $serviceEnrollmentResult['warnings']);
        }
        
        return $results;
    }
    
    protected function processProgramEnrollment(Participant $participant, int $programId, $enrollmentDate, $exitDate, array $record): array
    {
        $results = [
            'enrollments' => 0,
            'warnings' => [],
            'program_enrollment' => null
        ];
        
        // Check if enrollment already exists
        $enrollment = ProgramEnrollment::where('participant_id', $participant->id)
            ->where('program_id', $programId)
            ->when($enrollmentDate, function ($query) use ($enrollmentDate) {
                return $query->where('enrollment_date', $enrollmentDate->format('Y-m-d'));
            })
            ->first();
        
        if (!$enrollment) {
            $enrollment = ProgramEnrollment::create([
                'participant_id' => $participant->id,
                'program_id' => $programId,
                'enrollment_date' => $enrollmentDate ? $enrollmentDate->format('Y-m-d') : now()->format('Y-m-d'),
                'exit_date' => $exitDate ? $exitDate->format('Y-m-d') : null,
                'notes' => !empty($record['EnrollID']) ? "HMIS EnrollID:" . $record['EnrollID'] : null,
            ]);
            
            $results['enrollments'] = 1;
        } else if ($exitDate && !$enrollment->exit_date) {
            // Update exit date if provided and not already set
            $enrollment->update(['exit_date' => $exitDate->format('Y-m-d')]);
        }
        
        $results['program_enrollment'] = $enrollment;
        return $results;
    }
    
    protected function processServiceEnrollment(Participant $participant, string $programName, $enrollmentDate, $exitDate, array $record, ProgramEnrollment $programEnrollment): array
    {
        $results = [
            'enrollments' => 0,
            'warnings' => []
        ];
        
        $targetProgramId = self::SIXTH_STREET_SHELTER_PROGRAM_ID;
        
        // Find existing service with exact name match for program ID 4
        $service = Service::where('program_id', $targetProgramId)
            ->where('name', $programName)
            ->first();
        
        // If no exact match, create new service
        if (!$service) {
            try {
                $service = Service::create([
                    'program_id' => $targetProgramId,
                    'name' => $programName,
                    'description' => "Service created from HMIS import: {$programName}",
                    'service_type' => 'Housing',
                    'active' => true,
                    'created_by' => Auth::id() ?? 1,
                ]);
                
                $results['warnings'][] = "Created new service '{$programName}' for program ID {$targetProgramId}";
            } catch (\Exception $e) {
                $results['warnings'][] = "Failed to create service '{$programName}': " . $e->getMessage();
                return $results;
            }
        }
        
        // Check if service enrollment already exists
        $serviceEnrollment = ServiceEnrollment::where('participant_id', $participant->id)
            ->where('service_id', $service->id)
            ->where('program_enrollment_id', $programEnrollment->id)
            ->when($enrollmentDate, function ($query) use ($enrollmentDate) {
                return $query->where('enrollment_date', $enrollmentDate->format('Y-m-d'));
            })
            ->first();
        
        if (!$serviceEnrollment) {
            $serviceEnrollment = ServiceEnrollment::create([
                'participant_id' => $participant->id,
                'service_id' => $service->id,
                'program_enrollment_id' => $programEnrollment->id,
                'enrollment_date' => $enrollmentDate ? $enrollmentDate->format('Y-m-d') : now()->format('Y-m-d'),
                'completion_date' => $exitDate ? $exitDate->format('Y-m-d') : null,
                'status' => $exitDate ? 'completed' : 'enrolled',
                'notes' => !empty($record['EnrollID']) ? "HMIS EnrollID:" . $record['EnrollID'] : null,
            ]);
            
            $results['enrollments'] = 1;
        } else if ($exitDate && !$serviceEnrollment->completion_date) {
            // Update completion date and status if provided and not already set
            $serviceEnrollment->update([
                'completion_date' => $exitDate->format('Y-m-d'),
                'status' => 'completed'
            ]);
        }
        
        return $results;
    }

    protected function countUniqueHouseholds(array $records): int
    {
        $householdCodes = [];
        foreach ($records as $record) {
            if (!empty($record['CaseID']) && !in_array($record['CaseID'], $householdCodes)) {
                $householdCodes[] = $record['CaseID'];
            }
        }
        return count($householdCodes);
    }

    public function getTemplate(): CustomImportTemplate
    {
        return $this->template;
    }

    /**
     * Generate a CSV template file for HMIS Pennsylvania imports
     *
     * @return string CSV content with headers, field descriptions, and example data
     */
    public function generateTemplate(): string
    {
        $csv = \League\Csv\Writer::createFromString();

        // Add UTF-8 BOM for Excel compatibility
        $csv->setOutputBOM(\League\Csv\Bom::Utf8);

        // Get field mappings to determine columns
        $fieldMappings = $this->getFieldMappings();
        $headers = array_keys($fieldMappings);

        // Insert header row
        $csv->insertOne($headers);

        // Insert field description row (commented for reference)
        $descriptions = [
            'OrganizationName' => 'TEXT - Organization name (optional, not imported)',
            'ProgramName' => 'TEXT - Service name for enrollment (e.g., "Emergency Shelter", "Case Management")',
            'CaseID' => 'TEXT - Household code (required, groups family members)',
            'ClientName' => 'TEXT - Format: "Last, First Middle" (required)',
            'DOB' => 'DATE - Format: MM/DD/YYYY (required)',
            'Gender' => 'TEXT - Options: Male, Female, Other',
            'RaceAndEthnicity' => 'TEXT - Format: "Race|Ethnicity" (e.g., "White|Not Hispanic")',
            'VeteranStatus' => 'TEXT - Options: Veteran, Active Military, Never Served in the Military',
            'Relationship' => 'TEXT - Options: Self (Head of Household), Spouse, Child, Other Family Member',
            'HouseholdType' => 'TEXT - Options: Single, Two Parent Household, Single Parent Household, etc.',
            'HealthInsurance' => 'TEXT - Options: Yes, No, Unknown',
            'DisablingCondition' => 'TEXT - Options: Yes, No, Unknown',
            'CashIncome' => 'NUMBER - Monthly income amount (dollars)',
            'EnrollDate' => 'DATETIME - Format: MM/DD/YYYY HH:MM:SS AM/PM (e.g., "01/15/2024 09:30:00 AM")',
            'ExitDate' => 'DATETIME - Format: MM/DD/YYYY HH:MM:SS AM/PM (leave blank if still enrolled)',
            'ClientID' => 'TEXT - Unique client identifier for tracking (optional)',
            'EnrollID' => 'TEXT - Unique enrollment identifier (optional)',
        ];

        $descriptionRow = [];
        foreach ($headers as $header) {
            $descriptionRow[] = $descriptions[$header] ?? '';
        }
        $csv->insertOne($descriptionRow);

        // Insert example data rows
        $exampleRows = [
            [
                'OrganizationName' => 'Sixth Street Shelter',
                'ProgramName' => 'Emergency Shelter',
                'CaseID' => 'HMIS-001',
                'ClientName' => 'Smith, John Michael',
                'DOB' => '06/15/1985',
                'Gender' => 'Male',
                'RaceAndEthnicity' => 'White|Not Hispanic, Latino or Spanish Origins',
                'VeteranStatus' => 'Veteran',
                'Relationship' => 'Self (Head of Household)',
                'HouseholdType' => 'Single',
                'HealthInsurance' => 'No',
                'DisablingCondition' => 'No',
                'CashIncome' => '1200',
                'EnrollDate' => '01/15/2024 09:30:00 AM',
                'ExitDate' => '',
                'ClientID' => 'CLI-12345',
                'EnrollID' => 'ENR-98765',
            ],
            [
                'OrganizationName' => 'Sixth Street Shelter',
                'ProgramName' => 'Case Management',
                'CaseID' => 'HMIS-002',
                'ClientName' => 'Johnson, Mary Elizabeth',
                'DOB' => '03/22/1990',
                'Gender' => 'Female',
                'RaceAndEthnicity' => 'Black or African American|Hispanic, Latino or Spanish Origins',
                'VeteranStatus' => 'Never Served in the Military',
                'Relationship' => 'Self (Head of Household)',
                'HouseholdType' => 'Single Parent Household',
                'HealthInsurance' => 'Yes',
                'DisablingCondition' => 'Yes',
                'CashIncome' => '850',
                'EnrollDate' => '02/01/2024 10:00:00 AM',
                'ExitDate' => '06/30/2024 05:00:00 PM',
                'ClientID' => 'CLI-12346',
                'EnrollID' => 'ENR-98766',
            ],
        ];

        foreach ($exampleRows as $row) {
            $csv->insertOne($row);
        }

        return $csv->toString();
    }
}