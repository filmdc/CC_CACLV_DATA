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
use App\Models\PotentialDuplicate;
use App\Services\DataTransformationService;
use App\Services\DuplicateDetectionService;
use League\Csv\Reader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RXOfficeHousingImportService
{
    protected DataTransformationService $transformer;
    protected CustomImportTemplate $template;
    protected DuplicateDetectionService $duplicateDetectionService;
    
    const HOUSING_COUNSELING_PROGRAM_ID = 1;

    public function __construct(DataTransformationService $transformer, DuplicateDetectionService $duplicateDetectionService)
    {
        $this->transformer = $transformer;
        $this->duplicateDetectionService = $duplicateDetectionService;
        $this->template = $this->getOrCreateTemplate();
    }

    protected function getOrCreateTemplate(): CustomImportTemplate
    {
        $template = CustomImportTemplate::getBySourceSystem('RX_Office_Housing');
        
        if (!$template) {
            $template = CustomImportTemplate::create([
                'name' => 'PHFA RX Office - Housing Counseling Program',
                'description' => 'Pre-configured template for importing Housing Counseling Program data from PHFA RX Office system',
                'type' => 'preconfigured',
                'source_system' => 'RX_Office_Housing',
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
    
    protected function detectImportType(array $headers): string
    {
        // Check for borrower class registration headers
        if (in_array('Class Name', $headers) && in_array('Client Name', $headers) && in_array('Client Registration Date', $headers)) {
            return 'borrower_class_registration';
        }
        
        // Check for detailed case information headers
        if (in_array('Case ID', $headers) && in_array('Case Type', $headers) && in_array('Case Open Date', $headers)) {
            return 'detailed_case_information';
        }
        
        throw new \Exception('Unrecognized RX Office import format. Expected either Detailed Case Information or Borrower Class Registration format.');
    }

    protected function getFieldMappings(): array
    {
        // Default mappings for detailed case information
        return [
            'Case ID' => 'case_id',
            'Case Type' => 'service_type',
            'Case Open Date' => 'enrollment_date',
            'Borrower First Name' => 'first_name',
            'Borrower Last Name' => 'last_name',
            'Borrower Birthdate' => 'date_of_birth',
            'Borrower Family Size' => 'household_size',
            'Borrower Gender' => 'gender',
            'Borrower Race' => 'race',
            'Borrower Ethnicity' => 'ethnicity',
            'Borrower Dependents' => 'dependents',
            'Military Status' => 'military_status',
            'Borrower Household Type' => 'household_type',
            'Borrower Monthly Income Gross' => 'monthly_income',
            'Owner Occupied' => 'housing_status',
            'Disabled' => 'health_status',
            'AMI' => 'ami_level',
            'Assigned Agent' => 'assigned_agent',
            'Age' => null, // Calculated from birth date
            'Counseling Type' => 'counseling_type',
            'Borrower Total Gross Income' => 'annual_income',
            'Household Income Gross' => 'household_income',
            'Counseling Outcome' => 'counseling_outcome',
            'Employment Status' => 'employment_status',
            'Occupation' => 'occupation',
        ];
    }
    
    protected function getBorrowerClassFieldMappings(): array
    {
        return [
            'Class Name' => 'service_name',
            'Class ID' => 'class_id',
            'Class Status' => 'class_status',
            'Class Date' => 'service_date',
            'Class Hours' => 'class_hours',
            'Instructor' => 'instructor',
            'Class Max Capacity' => 'max_capacity',
            'Number of Users Registered' => 'registered_count',
            'Class Close Date' => 'class_close_date',
            'HUD Category' => 'hud_category',
            'Attendance' => 'attendance_status',
            'Location' => 'location',
            'Client Name' => 'client_name',
            'Intake Client ID' => 'client_id',
            'Client Address' => 'address',
            'Client Phone Number' => 'telephone',
            'Client Email' => 'email',
            'Race' => 'race',
            'Ethnicity' => 'ethnicity',
            'Income Level' => 'income_level',
            'Rural Area Status' => 'rural_status',
            'Family Size' => 'household_size',
            'Household Type' => 'household_type',
            'Language' => 'primary_language',
            'Gender' => 'gender',
            'Client Registration Date' => 'enrollment_date',
            'Disabled' => 'disability_status',
            'Birth Date' => 'date_of_birth',
            'Dependents' => 'dependents',
            'Monthly Income' => 'monthly_income',
            'Veteran' => 'military_status',
            'Age' => null, // Calculated from birth date
            'Military Status' => 'military_status_detail',
            'Referral Source' => 'referral_source',
        ];
    }

    protected function getTransformationRules(): array
    {
        return [
            'first_name' => ['trim', 'title_case'],
            'last_name' => ['trim', 'title_case'],
            'date_of_birth' => ['date_mdy_to_ymd'],
            'enrollment_date' => ['date_mdy_to_ymd'],
            'gender' => ['gender_mapping'],
            'race' => ['race_mapping'],
            'ethnicity' => ['ethnicity_mapping'],
            'household_type' => ['household_type_mapping'],
            'housing_status' => ['housing_status_mapping'],
            'health_status' => ['health_status_mapping'],
            'employment_status' => ['employment_status_mapping'],
            'military_status' => ['military_status_mapping'],
            'monthly_income' => ['numeric'],
            'annual_income' => ['numeric'],
            'household_income' => ['numeric'],
            'household_size' => ['integer'],
            'dependents' => ['integer'],
        ];
    }

    protected function getValidationRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'enrollment_date' => ['required', 'date'],
            'household_size' => ['nullable', 'integer', 'min:1'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'annual_income' => ['nullable', 'numeric', 'min:0'],
            'household_income' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function getImportSettings(): array
    {
        return [
            'program_id' => self::HOUSING_COUNSELING_PROGRAM_ID,
            'create_households' => true,
            'create_services' => true,
            'calculate_poverty' => true,
            'calculate_csbg_age_groups' => true,
            'default_address' => [
                'street' => 'Unknown - Import from RX Office',
                'city' => 'Unknown',
                'state' => 'PA',
                'zip' => '00000',
            ],
            'duplicate_check_fields' => ['case_id', 'first_name', 'last_name'],
            'service_type_mappings' => [
                'PHFA Default And Delinquency Counseling' => 'Default & Delinquency Counseling',
                'PHFA Pre-Closing Seminar' => 'Pre-Closing Seminar',
                'PHFA Pre-Purchase One on One counseling' => 'Pre-Purchase Counseling',
            ],
        ];
    }

    public function validateFile(string $filePath): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'record_count' => 0,
            'import_type' => null,
        ];

        try {
            $reader = Reader::createFromPath($filePath, 'r');
            
            // Try to detect the correct header row for borrower class registration
            $headerOffset = $this->findHeaderRow($reader);
            $reader->setHeaderOffset($headerOffset);
            
            $headers = $reader->getHeader();
            $importType = $this->detectImportType($headers);
            $result['import_type'] = $importType;
            
            $this->validateHeaders($headers, $importType);
            
            $records = iterator_to_array($reader->getRecords());
            $result['record_count'] = count($records);
            
            if ($result['record_count'] === 0) {
                $result['errors'][] = 'File contains no data rows';
                return $result;
            }
            
            // Sample validation on first few records
            $sampleSize = min(5, $result['record_count']);
            $validationErrors = [];
            
            $rowNumber = $headerOffset + 1; // Start counting from after header
            foreach (array_slice($records, 0, $sampleSize) as $record) {
                $rowNumber++;
                try {
                    $this->validateRecord($record, $rowNumber, $importType);
                } catch (\Exception $e) {
                    $validationErrors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }
            
            if (!empty($validationErrors)) {
                $result['warnings'] = array_slice($validationErrors, 0, 10); // Limit to 10 warnings
                if (count($validationErrors) > 10) {
                    $result['warnings'][] = '... and ' . (count($validationErrors) - 10) . ' more validation issues';
                }
            }
            
            $result['valid'] = true;
            
        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }
    
    protected function findHeaderRow(Reader $reader): int
    {
        $reader->setHeaderOffset(null);
        $rows = iterator_to_array($reader->getRecords());
        
        // Check each row to find the one with proper headers
        foreach ($rows as $rowIndex => $row) {
            $values = array_values($row);
            
            // Look for borrower class registration headers
            if (in_array('Class Name', $values) && in_array('Client Name', $values) && in_array('Client Registration Date', $values)) {
                return $rowIndex;
            }
            
            // Look for detailed case information headers
            if (in_array('Case ID', $values) && in_array('Case Type', $values) && in_array('Case Open Date', $values)) {
                return $rowIndex;
            }
        }
        
        // Default to first row if no match found
        return 0;
    }

    protected function validateRecord(array $record, int $rowNumber, string $importType): void
    {
        if ($importType === 'detailed_case_information') {
            $this->validateDetailedCaseRecord($record, $rowNumber);
        } elseif ($importType === 'borrower_class_registration') {
            $this->validateBorrowerClassRecord($record, $rowNumber);
        }
    }
    
    protected function validateDetailedCaseRecord(array $record, int $rowNumber): void
    {
        // Check required fields
        $requiredFields = ['Case ID', 'Case Type', 'Case Open Date', 'Borrower First Name', 'Borrower Last Name'];
        
        foreach ($requiredFields as $field) {
            if (empty(trim($record[$field] ?? ''))) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate date formats
        if (!empty($record['Case Open Date'])) {
            try {
                Carbon::createFromFormat('n/j/Y', $record['Case Open Date']);
            } catch (\Exception $e) {
                throw new \Exception("Invalid Case Open Date format. Expected MM/DD/YYYY");
            }
        }
        
        if (!empty($record['Borrower Birthdate'])) {
            try {
                Carbon::createFromFormat('n/j/Y', $record['Borrower Birthdate']);
            } catch (\Exception $e) {
                throw new \Exception("Invalid Borrower Birthdate format. Expected MM/DD/YYYY");
            }
        }
        
        // Validate numeric fields
        $numericFields = ['Borrower Family Size', 'Borrower Monthly Income Gross', 'Borrower Total Gross Income'];
        foreach ($numericFields as $field) {
            $value = trim($record[$field] ?? '');
            if (!empty($value) && !is_numeric($value)) {
                throw new \Exception("Invalid numeric value for {$field}: {$value}");
            }
        }
    }
    
    protected function validateBorrowerClassRecord(array $record, int $rowNumber): void
    {
        // Check required fields for borrower class registration
        $requiredFields = ['Class Name', 'Client Name', 'Client Registration Date'];
        
        foreach ($requiredFields as $field) {
            if (empty(trim($record[$field] ?? ''))) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Parse client name to get first and last name
        $clientName = trim($record['Client Name'] ?? '');
        if (!$clientName) {
            throw new \Exception("Client Name is required");
        }
        
        $nameParts = explode(' ', $clientName);
        if (count($nameParts) < 2) {
            throw new \Exception("Client Name must include both first and last name");
        }
        
        // Validate date formats
        if (!empty($record['Class Date'])) {
            try {
                Carbon::createFromFormat('n/j/Y', $record['Class Date']);
            } catch (\Exception $e) {
                throw new \Exception("Invalid Class Date format. Expected MM/DD/YYYY");
            }
        }
        
        if (!empty($record['Client Registration Date'])) {
            try {
                Carbon::createFromFormat('n/j/Y', $record['Client Registration Date']);
            } catch (\Exception $e) {
                throw new \Exception("Invalid Client Registration Date format. Expected MM/DD/YYYY");
            }
        }
        
        if (!empty($record['Birth Date'])) {
            try {
                Carbon::createFromFormat('m/d/Y', $record['Birth Date']);
            } catch (\Exception $e) {
                throw new \Exception("Invalid Birth Date format. Expected MM/DD/YYYY");
            }
        }
        
        // Validate numeric fields
        $numericFields = ['Family Size', 'Monthly Income', 'Class Hours', 'Dependents'];
        foreach ($numericFields as $field) {
            $value = trim($record[$field] ?? '');
            if (!empty($value) && !is_numeric($value)) {
                throw new \Exception("Invalid numeric value for {$field}: {$value}");
            }
        }
    }

    public function importFromCsv(string $filePath): array
    {
        $importLog = $this->createImportLog($filePath);
        $results = [
            'success' => false,
            'total_rows' => 0,
            'imported_count' => 0,
            'updated_count' => 0,
            'error_count' => 0,
            'errors' => [],
            'import_log_id' => $importLog->id,
            // Match HMIS format for display
            'processed_participants' => 0,
            'updated_participants' => 0,
            'processed_enrollments' => 0,
            'skipped_records' => 0,
            'import_type' => null,
        ];

        try {
            DB::beginTransaction();

            $reader = Reader::createFromPath($filePath, 'r');
            
            // Try to detect the correct header row for borrower class registration
            $headerOffset = $this->findHeaderRow($reader);
            $reader->setHeaderOffset($headerOffset);
            
            $headers = $reader->getHeader();
            $importType = $this->detectImportType($headers);
            $results['import_type'] = $importType;
            
            $this->validateHeaders($headers, $importType);
            
            $records = $reader->getRecords();
            $rowNumber = 1; // Start from 1 for data rows
            
            foreach ($records as $record) {
                $rowNumber++;
                $results['total_rows']++;
                
                try {
                    $transformedData = $this->transformRecord($record, $importType);
                    
                    // Use centralized duplicate detection
                    $duplicates = $this->duplicateDetectionService->checkForDuplicates(
                        $transformedData, 
                        'rx_office_import', 
                        $importLog->id
                    );

                    if ($duplicates->isNotEmpty()) {
                        // Store for review if duplicates found
                        $storedDuplicates = $this->duplicateDetectionService->storePotentialDuplicates(
                            $duplicates, 
                            Auth::id() ?? 1
                        );
                        
                        $results['updated_count']++;
                        $results['updated_participants']++;
                        
                        Log::info('RX Office Import: Potential duplicates found and stored for review', [
                            'identifier' => $transformedData['case_id'] ?? $transformedData['client_id'] ?? 'Unknown',
                            'duplicates_count' => $duplicates->count(),
                            'stored_for_review' => $storedDuplicates->count(),
                            'import_type' => $importType,
                        ]);
                    } else {
                        $this->createNewRecord($transformedData, $importType);
                        $results['imported_count']++;
                        $results['processed_participants']++;
                    }
                    
                    $results['processed_enrollments']++;
                    
                } catch (\Exception $e) {
                    $results['error_count']++;
                    $results['skipped_records']++;
                    $errorMessage = "Row {$rowNumber}: " . $e->getMessage();
                    $results['errors'][] = $errorMessage;
                    
                    $this->logError($importLog, $errorMessage, $record, $rowNumber);
                }
            }

            $this->updateImportLogSummary($importLog, $results);
            
            DB::commit();
            $results['success'] = true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = 'Import failed: ' . $e->getMessage();
            $this->logError($importLog, 'Import failed: ' . $e->getMessage());
        }

        return $results;
    }

    protected function validateHeaders(array $headers, string $importType): void
    {
        if ($importType === 'detailed_case_information') {
            $requiredHeaders = ['Case ID', 'Case Type', 'Case Open Date', 'Borrower First Name', 'Borrower Last Name'];
        } elseif ($importType === 'borrower_class_registration') {
            $requiredHeaders = ['Class Name', 'Client Name', 'Client Registration Date'];
        } else {
            throw new \Exception('Unknown import type: ' . $importType);
        }
        
        $missingHeaders = array_diff($requiredHeaders, $headers);
        
        if (!empty($missingHeaders)) {
            throw new \Exception('Missing required headers for ' . $importType . ': ' . implode(', ', $missingHeaders));
        }
    }

    protected function transformRecord(array $record, string $importType): array
    {
        $transformed = [];
        
        // Get the appropriate field mappings based on import type
        $fieldMappings = $importType === 'borrower_class_registration' 
            ? $this->getBorrowerClassFieldMappings() 
            : $this->getFieldMappings();
        
        foreach ($fieldMappings as $csvField => $systemField) {
            if ($systemField && isset($record[$csvField])) {
                $value = trim($record[$csvField]);
                
                // Apply transformations
                $transformationRules = $this->getTransformationRules()[$systemField] ?? [];
                foreach ($transformationRules as $rule) {
                    $value = $this->applyTransformation($rule, $value);
                }
                
                $transformed[$systemField] = $value;
            }
        }

        // Special handling for borrower class registration
        if ($importType === 'borrower_class_registration') {
            $transformed = $this->transformBorrowerClassRecord($transformed, $record);
        }

        // Store original record for reference
        $transformed['original_record'] = $record;
        
        return $transformed;
    }
    
    protected function transformBorrowerClassRecord(array $transformed, array $originalRecord): array
    {
        // Parse client name into first and last name
        if (isset($transformed['client_name']) && trim($transformed['client_name'])) {
            $nameParts = array_filter(explode(' ', trim($transformed['client_name'])));
            $transformed['first_name'] = $nameParts[0] ?? 'Unknown';
            $transformed['last_name'] = implode(' ', array_slice($nameParts, 1)) ?: 'Unknown';
        } else {
            // Skip records without client names
            throw new \Exception('Client Name is required and cannot be empty');
        }
        
        // Parse address into components
        if (isset($transformed['address'])) {
            $addressParts = $this->parseAddress($transformed['address']);
            $transformed = array_merge($transformed, $addressParts);
        }
        
        // Map AMI income level to income ranges
        if (isset($transformed['income_level'])) {
            $transformed['ami_level'] = $transformed['income_level'];
        }
        
        // Set service name from class name
        if (isset($transformed['service_name'])) {
            $transformed['service_type'] = $transformed['service_name'];
        }
        
        // Use service date as enrollment date
        if (isset($transformed['service_date'])) {
            $transformed['enrollment_date'] = $transformed['service_date'];
        } elseif (isset($transformed['enrollment_date'])) {
            // Keep enrollment_date as is
        } else {
            $transformed['enrollment_date'] = now()->format('Y-m-d');
        }
        
        return $transformed;
    }
    
    protected function parseAddress(string $address): array
    {
        $parsed = [
            'street_address' => '',
            'city' => '',
            'state' => '',
            'zip_code' => ''
        ];
        
        // Expected format: "1050 N Kiowa Street Allentown,PA-18109"
        if (preg_match('/^(.+?)\s+([^,]+),\s*([A-Z]{2})-?(\d{5})$/', trim($address), $matches)) {
            $parsed['street_address'] = trim($matches[1]);
            $parsed['city'] = trim($matches[2]);
            $parsed['state'] = trim($matches[3]);
            $parsed['zip_code'] = trim($matches[4]);
        } else {
            // Fallback parsing
            $parts = explode(',', $address);
            if (count($parts) >= 2) {
                $parsed['street_address'] = trim($parts[0]);
                $cityStateZip = trim($parts[1]);
                
                // Try to extract state and zip
                if (preg_match('/^(.+?)\s+([A-Z]{2})-?(\d{5})$/', $cityStateZip, $matches)) {
                    $parsed['city'] = trim($matches[1]);
                    $parsed['state'] = trim($matches[2]);
                    $parsed['zip_code'] = trim($matches[3]);
                } else {
                    $parsed['city'] = $cityStateZip;
                }
            } else {
                $parsed['street_address'] = $address;
            }
        }
        
        return $parsed;
    }

    protected function applyTransformation(string $rule, $value)
    {
        switch ($rule) {
            case 'trim':
                return trim($value);
            
            case 'title_case':
                return $this->titleCase($value);
            
            case 'date_mdy_to_ymd':
                return $this->convertDateFormat($value, 'm/d/Y', 'Y-m-d');
            
            case 'numeric':
                return is_numeric($value) ? (float) $value : null;
            
            case 'integer':
                return is_numeric($value) ? (int) $value : null;
            
            case 'gender_mapping':
                return $this->mapGender($value);
            
            case 'race_mapping':
                return $this->mapRace($value);
            
            case 'ethnicity_mapping':
                return $this->mapEthnicity($value);
            
            case 'household_type_mapping':
                return $this->mapHouseholdType($value);
            
            case 'housing_status_mapping':
                return $this->mapHousingStatus($value);
            
            case 'health_status_mapping':
                return $this->mapHealthStatus($value);
            
            case 'employment_status_mapping':
                return $this->mapEmploymentStatus($value);
            
            case 'military_status_mapping':
                return $this->mapMilitaryStatus($value);
            
            default:
                return $value;
        }
    }

    protected function mapGender(string $value): ?string
    {
        $mapping = [
            'Male' => 'Male',
            'Female' => 'Female',
        ];
        
        return $mapping[$value] ?? 'Unknown/not reported';
    }

    protected function mapRace(string $value): array
    {
        $mapping = [
            'White' => ['White'],
            'Black or African American' => ['Black or African American'],
            'Black or African-American and White' => ['Black or African American', 'White'],
            'Other Multiple Race' => ['Other'],
        ];
        
        return $mapping[$value] ?? ['Unknown/not reported'];
    }

    protected function mapEthnicity(string $value): ?string
    {
        $mapping = [
            'Hispanic or Latino' => 'Hispanic, Latino or Spanish Origins',
            'Not Hispanic or Latino' => 'Not Hispanic, Latino or Spanish Origins',
        ];
        
        return $mapping[$value] ?? 'Unknown/not reported';
    }

    protected function mapHouseholdType(string $value): ?string
    {
        $mapping = [
            'Single adult' => 'Single Person',
            'Married without dependents' => 'Two Adults NO Children',
            'Two or more unrelated adults' => 'Two Adults NO Children',
            'Female-headed single parent household' => 'Single Parent Female',
            'Male-headed single parent household' => 'Single Parent Male',
            'Married with dependents' => 'Two Parent Household',
            'Other' => 'Other',
        ];
        
        return $mapping[$value] ?? 'Unknown/not reported';
    }

    protected function mapHousingStatus(string $value): ?string
    {
        $mapping = [
            'Owner Occupied' => 'Own',
        ];
        
        return $mapping[$value] ?? 'Rent'; // Default to rent if not specified
    }

    protected function mapHealthStatus(string $value): ?string
    {
        $mapping = [
            'Yes' => 'Yes',
            'No' => 'No',
        ];
        
        return $mapping[$value] ?? 'Unknown/not reported';
    }

    protected function mapEmploymentStatus(string $value): ?string
    {
        $mapping = [
            '1=Currently Employed full time' => 'Employed Full-Time',
            '2=Currently Employed part time' => 'Employed Part-Time',
            '3=Unemployed and looking for work' => 'Unemployed (Short-Term, 6 months or less)',
            '4= Unable to work due to disability' => 'Unemployed (Not in Labor Force)',
            '4=Unable to work due to disability' => 'Unemployed (Not in Labor Force)',
            '6=Retired' => 'Retired',
            '12=Self Employment' => 'Employed Full-Time',
            '8= Employed full time AND Student' => 'Employed Full-Time',
            '8=Employed full time AND Student' => 'Employed Full-Time',
            '9=Employed part time AND Student' => 'Employed Part-Time',
        ];
        
        return $mapping[$value] ?? 'Unknown/not reported';
    }

    protected function mapMilitaryStatus(string $value): ?string
    {
        $mapping = [
            'Veteran' => 'Veteran',
            'N/A' => 'Never Served in the Military',
        ];
        
        return $mapping[$value] ?? 'Unknown/not reported';
    }

    // Note: Duplicate detection now handled by centralized DuplicateDetectionService

    protected function createNewRecord(array $data, string $importType): void
    {
        // Create household first
        $household = $this->createHousehold($data, $importType);
        
        // Create participant
        $participant = $this->createParticipant($data, $household, $importType);
        
        // Create program enrollment
        $programEnrollment = $this->createProgramEnrollment($participant, $data, $importType);
        
        // Create service and service enrollment
        $service = $this->findOrCreateService($data, $importType);
        $this->createServiceEnrollment($participant, $service, $programEnrollment, $data, $importType);
        
        // Calculate and update poverty level
        $this->calculateAndUpdatePovertyLevel($participant, $household);
        
        // Update CSBG age groups
        $this->updateCSBGAgeGroups($participant);
    }

    // Note: Updates now handled through duplicate resolution workflow

    protected function createHousehold(array $data, string $importType): Household
    {
        $defaultAddress = $this->getImportSettings()['default_address'];
        
        // Use parsed address for borrower class registration, default for detailed case info
        $address = $importType === 'borrower_class_registration' && isset($data['street_address']) 
            ? [
                'street' => $data['street_address'] ?: $defaultAddress['street'],
                'city' => $data['city'] ?: $defaultAddress['city'],
                'state' => $data['state'] ?: $defaultAddress['state'],
                'zip' => $data['zip_code'] ?: $defaultAddress['zip'],
            ]
            : $defaultAddress;
        
        $household = Household::create([
            'household_code' => $this->generateHouseholdCode(),
            'household_size' => $data['household_size'] ?? 1,
            'household_type' => $data['household_type'] ?? 'Unknown/not reported',
            'housing_status' => $data['housing_status'] ?? 'Unknown/not reported',
        ]);
        
        // Create initial address if we have address data
        if ($address['street'] || $address['city'] || $address['state'] || $address['zip']) {
            $household->addresses()->create([
                'address' => $address['street'],
                'city' => $address['city'],
                'state' => $address['state'],
                'zip_code' => $address['zip'],
                'is_current' => true,
                'effective_date' => now()->format('Y-m-d'),
                'reason_for_change' => 'Imported from RX Office Housing',
            ]);
        }
        
        return $household;
    }

    protected function createParticipant(array $data, Household $household, string $importType): Participant
    {
        // Handle date_of_birth - use a default date if not provided since it's required
        $dateOfBirth = $data['date_of_birth'] ?? null;
        if (!$dateOfBirth) {
            // Use January 1st of the year that would make them 18 years old
            // This is a safe fallback that allows age calculations
            $dateOfBirth = now()->subYears(18)->startOfYear()->format('Y-m-d');
        }
        
        // Build notes based on import type
        $notes = [
            'import_source' => 'RX Office Housing',
            'import_type' => $importType,
            'import_date' => now()->toDateString(),
        ];
        
        if ($importType === 'detailed_case_information') {
            $notes = array_merge($notes, [
                'case_id' => $data['case_id'] ?? null,
                'assigned_agent' => $data['assigned_agent'] ?? null,
                'counseling_type' => $data['counseling_type'] ?? null,
                'counseling_outcome' => $data['counseling_outcome'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'ami_level' => $data['ami_level'] ?? null,
            ]);
        } elseif ($importType === 'borrower_class_registration') {
            $notes = array_merge($notes, [
                'client_id' => $data['client_id'] ?? null,
                'class_name' => $data['service_name'] ?? null,
                'instructor' => $data['instructor'] ?? null,
                'attendance_status' => $data['attendance_status'] ?? null,
                'hud_category' => $data['hud_category'] ?? null,
                'location' => $data['location'] ?? null,
                'class_hours' => $data['class_hours'] ?? null,
                'dependents' => $data['dependents'] ?? null,
                'rural_status' => $data['rural_status'] ?? null,
                'referral_source' => $data['referral_source'] ?? null,
                'ami_level' => $data['ami_level'] ?? null,
            ]);
        }
        
        $participant = Participant::create([
            'household_id' => $household->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'date_of_birth' => $dateOfBirth,
            'gender' => $data['gender'] ?? 'Unknown/not reported',
            'ethnicity' => $data['ethnicity'] ?? 'Unknown/not reported',
            'military_status' => $data['military_status'] ?? 'Unknown/not reported',
            'disability_status' => $data['disability_status'] ?? $data['health_status'] ?? 'Unknown/not reported',
            'work_status' => $data['employment_status'] ?? 'Unknown/not reported',
            'race' => $data['race'] ?? ['Unknown/not reported'],
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => json_encode($notes),
        ]);

        // Set as head of household
        $participant->update(['is_head_of_household' => true]);

        // Handle race (stored as JSON array)
        if (isset($data['race']) && is_array($data['race'])) {
            $participant->update(['race' => $data['race']]);
        }

        // Create income record if provided
        $this->createIncomeRecord($participant, $data);

        return $participant;
    }

    protected function createIncomeRecord(Participant $participant, array $data): void
    {
        $monthlyIncome = $data['monthly_income'] ?? null;
        $annualIncome = $data['annual_income'] ?? null;
        $householdIncome = $data['household_income'] ?? null;

        // Determine the best income value to use
        $incomeAmount = null;
        $incomeType = 'monthly'; // Default assumption
        
        if ($monthlyIncome && $monthlyIncome > 0) {
            // If we have explicit monthly income, use it
            $incomeAmount = $monthlyIncome;
            $incomeType = 'monthly';
        } elseif ($householdIncome && $householdIncome > 0) {
            // If household income is provided, determine if it's monthly or annual
            // Heuristic: if household income > 15000, it's likely annual income
            if ($householdIncome > 15000) {
                $incomeAmount = round($householdIncome / 12, 2); // Convert to monthly
                $incomeType = 'monthly (converted from annual)';
                
                // Additional sanity check: if converted monthly income is still > 50000, log as suspicious
                if ($incomeAmount > 50000) {
                    Log::warning('RX Office Import: Extremely high monthly income detected', [
                        'household_income_raw' => $householdIncome,
                        'converted_monthly' => $incomeAmount,
                        'participant_data' => [
                            'first_name' => $data['first_name'] ?? 'Unknown',
                            'last_name' => $data['last_name'] ?? 'Unknown',
                            'case_id' => $data['case_id'] ?? 'Unknown'
                        ]
                    ]);
                }
            } else {
                $incomeAmount = $householdIncome; // Assume monthly
                $incomeType = 'monthly';
            }
        } elseif ($annualIncome && $annualIncome > 0) {
            // If only annual income is available, convert to monthly
            $incomeAmount = round($annualIncome / 12, 2);
            $incomeType = 'monthly (converted from annual)';
        }
        
        if ($incomeAmount && $incomeAmount > 0) {
            $participant->update([
                'employment_income' => $incomeAmount,
                'income_sources' => ['Income from Employment Only'],
            ]);
            
            // Log income processing for debugging
            Log::info('RX Office Income Processing', [
                'participant_id' => $participant->id,
                'monthly_income_raw' => $monthlyIncome,
                'household_income_raw' => $householdIncome,
                'annual_income_raw' => $annualIncome,
                'final_monthly_amount' => $incomeAmount,
                'income_type' => $incomeType,
            ]);
        }
    }

    protected function createProgramEnrollment(Participant $participant, array $data, string $importType): ProgramEnrollment
    {
        $notes = [];
        
        if ($importType === 'detailed_case_information') {
            $notes = [
                'case_id' => $data['case_id'] ?? null,
                'case_type' => $data['service_type'] ?? null,
                'counseling_outcome' => $data['counseling_outcome'] ?? null,
            ];
        } elseif ($importType === 'borrower_class_registration') {
            $notes = [
                'client_id' => $data['client_id'] ?? null,
                'class_name' => $data['service_name'] ?? null,
                'class_date' => $data['service_date'] ?? null,
                'attendance_status' => $data['attendance_status'] ?? null,
            ];
        }
        
        return ProgramEnrollment::create([
            'participant_id' => $participant->id,
            'program_id' => self::HOUSING_COUNSELING_PROGRAM_ID,
            'enrollment_date' => $data['enrollment_date'],
            'status' => 'active',
            'notes' => json_encode($notes),
            'created_by' => Auth::id() ?? 1,
        ]);
    }

    protected function findOrCreateService(array $data, string $importType): Service
    {
        $serviceTypeMappings = $this->getImportSettings()['service_type_mappings'];
        
        if ($importType === 'detailed_case_information') {
            $caseType = $data['service_type'] ?? '';
            $serviceName = $serviceTypeMappings[$caseType] ?? $caseType;
            $description = "Auto-created from RX Office import: {$caseType}";
            $noteData = ['case_type' => $caseType];
        } else {
            // borrower_class_registration
            $className = $data['service_type'] ?? $data['service_name'] ?? '';
            $serviceName = $className ?: 'Housing Education Workshop';
            $description = "Auto-created from RX Office class registration: {$className}";
            $noteData = [
                'class_name' => $className,
                'hud_category' => $data['hud_category'] ?? null,
                'instructor' => $data['instructor'] ?? null,
                'location' => $data['location'] ?? null,
            ];
        }

        if (!$serviceName) {
            $serviceName = 'Housing Counseling Service';
        }

        $service = Service::where('name', $serviceName)
            ->where('program_id', self::HOUSING_COUNSELING_PROGRAM_ID)
            ->first();

        if (!$service) {
            $service = Service::create([
                'name' => $serviceName,
                'description' => $description,
                'program_id' => self::HOUSING_COUNSELING_PROGRAM_ID,
                'service_type' => $importType === 'borrower_class_registration' ? 'education' : 'counseling',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'active' => true,
                'notes' => json_encode(array_merge($noteData, [
                    'import_source' => 'RX Office Housing',
                    'import_type' => $importType,
                    'auto_created' => true,
                ])),
                'created_by' => Auth::id() ?? 1,
            ]);
        }

        return $service;
    }

    protected function createServiceEnrollment(Participant $participant, Service $service, ProgramEnrollment $programEnrollment, array $data, string $importType): void
    {
        $notes = [];
        $status = 'enrolled';
        
        if ($importType === 'detailed_case_information') {
            $notes = [
                'case_id' => $data['case_id'] ?? null,
                'counseling_type' => $data['counseling_type'] ?? null,
                'counseling_outcome' => $data['counseling_outcome'] ?? null,
                'assigned_agent' => $data['assigned_agent'] ?? null,
            ];
        } elseif ($importType === 'borrower_class_registration') {
            $notes = [
                'client_id' => $data['client_id'] ?? null,
                'class_name' => $data['service_name'] ?? null,
                'attendance_status' => $data['attendance_status'] ?? null,
                'instructor' => $data['instructor'] ?? null,
                'location' => $data['location'] ?? null,
                'class_hours' => $data['class_hours'] ?? null,
                'hud_category' => $data['hud_category'] ?? null,
            ];
            
            // Set status based on attendance
            $attendanceStatus = strtolower($data['attendance_status'] ?? '');
            if ($attendanceStatus === 'present') {
                $status = 'completed';
            } elseif ($attendanceStatus === 'absent') {
                $status = 'enrolled'; // They enrolled but didn't complete
            }
        }
        
        ServiceEnrollment::create([
            'participant_id' => $participant->id,
            'service_id' => $service->id,
            'program_enrollment_id' => $programEnrollment->id,
            'enrollment_date' => $data['enrollment_date'],
            'status' => $status,
            'notes' => json_encode($notes),
        ]);
    }

    protected function ensureServiceEnrollment(Participant $participant, Service $service, array $data): void
    {
        $existing = ServiceEnrollment::where('participant_id', $participant->id)
            ->where('service_id', $service->id)
            ->first();

        if (!$existing) {
            $programEnrollment = ProgramEnrollment::where('participant_id', $participant->id)
                ->where('program_id', self::HOUSING_COUNSELING_PROGRAM_ID)
                ->first();

            if (!$programEnrollment) {
                $programEnrollment = $this->createProgramEnrollment($participant, $data);
            }

            $this->createServiceEnrollment($participant, $service, $programEnrollment, $data);
        }
    }

    protected function updateParticipantData(Participant $participant, array $data): void
    {
        $updates = [];
        
        // Update basic info if provided and different
        if (isset($data['date_of_birth']) && $data['date_of_birth'] && !$participant->date_of_birth) {
            $updates['date_of_birth'] = $data['date_of_birth'];
        }

        // Update demographic fields if not set
        if (!$participant->gender && isset($data['gender'])) {
            $updates['gender'] = $data['gender'];
        }

        if (!$participant->ethnicity && isset($data['ethnicity'])) {
            $updates['ethnicity'] = $data['ethnicity'];
        }

        // Update notes with additional case information
        $currentNotes = $participant->notes ? json_decode($participant->notes, true) : [];
        $currentNotes['case_id'] = $data['case_id'] ?? $currentNotes['case_id'] ?? null;
        $currentNotes['assigned_agent'] = $data['assigned_agent'] ?? $currentNotes['assigned_agent'] ?? null;
        $currentNotes['counseling_type'] = $data['counseling_type'] ?? $currentNotes['counseling_type'] ?? null;
        $currentNotes['counseling_outcome'] = $data['counseling_outcome'] ?? $currentNotes['counseling_outcome'] ?? null;
        $currentNotes['last_import_update'] = now()->toDateString();
        
        $updates['notes'] = json_encode($currentNotes);

        if (!empty($updates)) {
            $participant->update($updates);
        }
    }

    protected function calculateAndUpdatePovertyLevel(Participant $participant, Household $household): void
    {
        $incomeAmount = $participant->employment_income;
        
        if ($incomeAmount && $incomeAmount > 0) {
            $povertyPercentage = PovertyGuideline::calculatePovertyPercentage(
                $incomeAmount,
                $household->household_size,
                'monthly' // Assuming monthly income from RX Office data
            );
            
            if ($povertyPercentage !== null) {
                // Cap poverty percentage at 999.99% to fit database constraints (DECIMAL(5,2))
                $cappedPovertyPercentage = min($povertyPercentage, 999.99);
                
                $household->update([
                    'hhs_poverty_percentage' => $cappedPovertyPercentage,
                    'monthly_income' => $incomeAmount,
                    'annual_income' => $incomeAmount * 12,
                ]);
                
                // Log if we had to cap the value
                if ($povertyPercentage > 999.99) {
                    Log::warning('RX Office Import: Poverty percentage capped', [
                        'household_id' => $household->id,
                        'original_percentage' => $povertyPercentage,
                        'capped_percentage' => $cappedPovertyPercentage,
                        'income_amount' => $incomeAmount,
                        'household_size' => $household->household_size,
                    ]);
                }
            }
        }
    }

    protected function updateCSBGAgeGroups(Participant $participant): void
    {
        if ($participant->date_of_birth) {
            $age = Carbon::parse($participant->date_of_birth)->age;
            
            $ageGroup = match (true) {
                $age < 6 => 'Under 6',
                $age <= 17 => '6-17',
                $age <= 54 => '18-54',
                $age <= 64 => '55-64',
                default => '65 and over'
            };
            
            // The CSBG age group is computed by the model's getCSBGAgeGroupAttribute method
            // No need to update since it's dynamic
        }
    }

    protected function generateHouseholdCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7));
        } while (Household::where('household_code', $code)->exists());
        
        return $code;
    }

    protected function findReferenceDataId(string $category, ?string $value): ?int
    {
        if (!$value) return null;
        
        return DB::table('reference_data')
            ->where('category', $category)
            ->where('active', true)
            ->where(function($query) use ($value) {
                $query->where('value', $value)
                      ->orWhere('label', $value);
            })
            ->value('id');
    }

    protected function createImportLog(string $filePath): CustomImportLog
    {
        return CustomImportLog::create([
            'template_id' => $this->template->id,
            'user_id' => Auth::id() ?? 1,
            'original_filename' => basename($filePath),
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    protected function updateImportLogSummary(CustomImportLog $importLog, array $results): void
    {
        $importLog->update([
            'status' => $results['success'] ? 'success' : 'failed',
            'total_records' => $results['total_rows'],
            'successful_records' => $results['imported_count'] + $results['updated_count'],
            'failed_records' => $results['error_count'],
            'completed_at' => now(),
        ]);
    }

    protected function logError(CustomImportLog $importLog, string $message, ?array $record = null, ?int $rowNumber = null): void
    {
        $cleanMessage = mb_convert_encoding($message, 'UTF-8', 'UTF-8');
        
        $errorData = [
            'message' => $cleanMessage,
            'row_number' => $rowNumber,
            'timestamp' => now()->toISOString(),
        ];
        
        if ($record) {
            $errorData['record_data'] = $record;
        }
        
        $currentErrors = $importLog->errors ?? [];
        $currentErrors[] = $errorData;
        
        $importLog->update([
            'errors' => $currentErrors,
        ]);
        
        Log::warning('RX Office import error', $errorData);
    }

    protected function convertDateFormat(string $value, string $fromFormat, string $toFormat): ?string
    {
        if (empty(trim($value))) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat($fromFormat, trim($value));
            return $date->format($toFormat);
        } catch (\Exception $e) {
            // Try fallback parsing
            try {
                $date = Carbon::parse(trim($value));
                return $date->format($toFormat);
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    protected function titleCase(string $value): string
    {
        return trim($value) ? Str::title(trim($value)) : $value;
    }
}