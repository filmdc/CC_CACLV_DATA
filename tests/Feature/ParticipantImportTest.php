<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ReferenceDataSet;
use App\Models\ReferenceData;
use App\Models\Participant;
use App\Models\Household;
use App\Services\ParticipantImportService;
use App\Services\ReferenceDataTransformationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ParticipantImportTest extends TestCase
{
    use RefreshDatabase;

    private ParticipantImportService $importService;
    private ReferenceDataSet $referenceDataSet;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Create or get existing reference data set (handles RefreshDatabase cleanup timing)
        $this->referenceDataSet = ReferenceDataSet::firstOrCreate(
            [
                'version' => 'v1.0.0-test',
            ],
            [
                'name' => 'Test CSBG Data Set',
                'description' => 'Test reference data for import testing',
                'is_active' => true,
                'is_draft' => false,
                'is_import_default' => true,
                'activated_at' => now(),
                'created_by' => $this->user->id
            ]
        );
        
        // Seed reference data
        $this->seedReferenceData();

        // Initialize import service with required dependencies
        $transformer = new ReferenceDataTransformationService();
        $duplicateDetection = new \App\Services\DuplicateDetectionService();
        $this->importService = new ParticipantImportService($transformer, $duplicateDetection);
    }

    private function seedReferenceData(): void
    {
        $data = [
            'gender' => ['Male', 'Female', 'Other', 'Unknown/not reported'],
            'ethnicity' => ['Hispanic, Latino or Spanish Origins', 'Not Hispanic, Latino or Spanish Origins', 'Unknown/not reported'],
            'work_status' => ['Employed Full-Time', 'Employed Part-Time', 'Unemployed (Short-Term, 6 months or less)', 'Retired', 'Unknown/not reported'],
            'household_type' => ['Single Person', 'Two Parent Household', 'Single Parent Female', 'Other', 'Unknown/not reported'],
            'housing_status' => ['Own', 'Rent', 'Homeless', 'Other', 'Unknown/not reported'],
        ];
        
        foreach ($data as $category => $values) {
            foreach ($values as $index => $value) {
                ReferenceData::create([
                    'reference_data_set_id' => $this->referenceDataSet->id,
                    'category' => $category,
                    'value' => $value,
                    'label' => $value,
                    'sort_order' => $index + 1,
                    'active' => true
                ]);
            }
        }
    }

    /** @test */
    public function it_validates_csv_file_with_correct_values()
    {
        $csvContent = $this->getValidCsvContent();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->validateFile($filePath);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(2, $result['record_count']);
        $this->assertEquals(1, $result['household_count']);
    }

    /** @test */
    public function it_detects_invalid_enum_values_and_shows_valid_options()
    {
        $csvContent = $this->getCsvWithInvalidValues();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->validateFile($filePath);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        
        // Check that error messages include valid options
        $errorFound = false;
        foreach ($result['errors'] as $error) {
            if (str_contains($error, 'Valid options are:')) {
                $errorFound = true;
                $this->assertStringContainsString("Invalid value for 'ethnicity'", $error);
                $this->assertStringContainsString('Hispanic, Latino or Spanish Origins', $error);
                break;
            }
        }
        $this->assertTrue($errorFound, 'Error message should include valid options');
    }

    /** @test */
    public function it_validates_with_selected_reference_data_set()
    {
        // Create alternative reference data set
        $altDataSet = ReferenceDataSet::create([
            'name' => 'Historical Data Set',
            'version' => 'v0.9.0',
            'is_active' => false,
            'is_draft' => false,
            'created_by' => $this->user->id
        ]);
        
        // Add different valid value for ethnicity
        ReferenceData::create([
            'reference_data_set_id' => $altDataSet->id,
            'category' => 'ethnicity',
            'value' => 'Hispanic or Latino',
            'label' => 'Hispanic or Latino',
            'sort_order' => 1,
            'active' => true
        ]);
        
        $csvContent = $this->getCsvWithHistoricalValues();
        $filePath = $this->createTempCsvFile($csvContent);
        
        // Validate with alternative data set
        $result = $this->importService->validateFile($filePath, [
            'reference_data_set_id' => $altDataSet->id
        ]);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_transforms_common_value_variations()
    {
        $csvContent = $this->getCsvWithCommonVariations();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->processImport($filePath, [
            'skip_errors' => false,
            'update_existing' => false
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_participants']);
        
        // Check that transformed values were saved correctly
        $participant = Participant::first();
        $this->assertEquals('Male', $participant->gender); // 'M' transformed to 'Male'
        $this->assertEquals('Hispanic, Latino or Spanish Origins', $participant->ethnicity); // 'Hispanic' transformed
    }

    /** @test */
    public function it_handles_missing_required_fields()
    {
        $csvContent = $this->getCsvWithMissingRequiredFields();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->validateFile($filePath);
        
        $this->assertFalse($result['valid']);
        $this->assertContains("Row 2: Missing required field 'household_code'", $result['errors']);
        $this->assertContains("Row 2: Missing required field 'first_name'", $result['errors']);
    }

    /** @test */
    public function it_processes_large_csv_file_efficiently()
    {
        $csvContent = $this->getLargeCsvContent(1000); // 1000 records
        $filePath = $this->createTempCsvFile($csvContent);
        
        $startTime = microtime(true);
        $result = $this->importService->validateFile($filePath);
        $duration = microtime(true) - $startTime;
        
        $this->assertTrue($result['valid']);
        $this->assertEquals(1000, $result['record_count']);
        $this->assertLessThan(5, $duration, 'Validation should complete within 5 seconds for 1000 records');
    }

    /** @test */
    public function it_handles_duplicate_household_codes_correctly()
    {
        // First, import a household
        Household::create([
            'household_code' => 'DUP123H',
            'household_type' => 'Single Person',
            'household_size' => 1,
            'housing_status' => 'Rent'
        ]);
        
        $csvContent = $this->getCsvWithDuplicateHouseholdCode();
        $filePath = $this->createTempCsvFile($csvContent);
        
        // Import without update_existing
        $result = $this->importService->processImport($filePath, [
            'update_existing' => false,
            'skip_errors' => false
        ]);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString("already exists", $result['error']);
    }

    /** @test */
    public function it_updates_existing_records_when_flag_is_set()
    {
        // Create existing household and participant
        $household = Household::create([
            'household_code' => 'UPDATE1',
            'household_type' => 'Single Person',
            'household_size' => 1,
            'housing_status' => 'Rent',
            'monthly_income' => 1000
        ]);
        
        Participant::create([
            'household_id' => $household->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'is_head_of_household' => true
        ]);
        
        $csvContent = $this->getCsvForUpdate();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->processImport($filePath, [
            'update_existing' => true,
            'skip_errors' => false
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated_households']);
        $this->assertEquals(1, $result['updated_participants']);
        
        // Verify updates
        $household->refresh();
        $this->assertEquals(2000, $household->monthly_income);
        $this->assertEquals('Own', $household->housing_status);
    }

    /** @test */
    public function it_validates_date_formats()
    {
        $csvContent = $this->getCsvWithInvalidDateFormat();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->validateFile($filePath);
        
        $this->assertFalse($result['valid']);
        $this->assertContains("Row 2: Invalid date_of_birth format. Use YYYY-MM-DD", $result['errors']);
    }

    /** @test */
    public function it_handles_array_fields_with_pipe_delimiter()
    {
        $csvContent = $this->getCsvWithArrayFields();
        $filePath = $this->createTempCsvFile($csvContent);
        
        $result = $this->importService->processImport($filePath);
        
        $this->assertTrue($result['success']);
        
        $participant = Participant::first();
        $this->assertIsArray($participant->race);
        $this->assertContains('White', $participant->race);
        $this->assertContains('Asian', $participant->race);
    }

    // Helper methods for generating test CSV content

    private function getValidCsvContent(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row1 = 'TEST001,Two Parent Household,2,Own,3500.00,42000.00,185.50,123 Test St,Las Vegas,NV,89101,Test notes,HEAD_OF_HOUSEHOLD,John,M,Doe,,1985-06-15,Male,702-555-0001,john@test.com,High School Graduate,FALSE,,No,,,FALSE,Employment Based,Yes,Not Hispanic, Latino or Spanish Origins,White,Never Served in the Military,Employed Full-Time,40.00,FALSE,FALSE,Employment,2800.00,,SNAP,2024-01-01,2024-12-01,Complete,Test participant';
        
        $row2 = ',,,,,,,,,,,,,FAMILY_MEMBER,Jane,E,Doe,,1987-03-22,Female,702-555-0002,jane@test.com,12th Grade + Some Post-Secondary,TRUE,College,No,,,FALSE,Employment Based,Yes,Hispanic, Latino or Spanish Origins,White,Never Served in the Military,Employed Part-Time,20.00,FALSE,TRUE,Employment,1200.00,,WIC,2024-01-01,2024-12-01,Complete,Family member';
        
        return "$headers\n$row1\n$row2";
    }

    private function getCsvWithInvalidValues(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'INVALID1,Invalid Type,1,Own,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,Test,T,User,,1990-01-01,InvalidGender,555-1234,test@test.com,Invalid Education,FALSE,,Maybe,,,FALSE,Invalid Insurance,Perhaps,Hispanic,White,veteran,employed,40,FALSE,FALSE,Employment,1000,,,2024-01-01,2024-01-01,,';
        
        return "$headers\n$row";
    }

    private function getCsvWithHistoricalValues(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'HIST001,Single Person,1,Rent,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,Test,T,User,,1990-01-01,Male,555-1234,test@test.com,,FALSE,,No,,,FALSE,,No,Hispanic or Latino,White,,,,,FALSE,FALSE,,,,,,,,';
        
        return "$headers\n$row";
    }

    private function getCsvWithCommonVariations(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'VAR001,Single Person,1,Own,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,Test,T,User,,1990-01-01,M,555-1234,test@test.com,,FALSE,,No,,,FALSE,,Yes,Hispanic,White,veteran,employed full-time,40,FALSE,FALSE,Employment,1000,,,2024-01-01,2024-01-01,,';
        
        return "$headers\n$row";
    }

    private function getCsvWithMissingRequiredFields(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = ',Single Person,1,Own,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,,T,User,,1990-01-01,,555-1234,test@test.com,,FALSE,,No,,,FALSE,,Yes,,,,,,,FALSE,FALSE,,,,,,,,';
        
        return "$headers\n$row";
    }

    private function getLargeCsvContent(int $recordCount): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $rows = [$headers];
        
        for ($i = 1; $i <= $recordCount; $i++) {
            $householdCode = sprintf('LARGE%06d', $i);
            $row = "$householdCode,Single Person,1,Rent,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,Test$i,T,User$i,,1990-01-01,Male,555-$i,test$i@test.com,High School Graduate,FALSE,,No,,,FALSE,Employment Based,Yes,Not Hispanic, Latino or Spanish Origins,White,Never Served in the Military,Employed Full-Time,40,FALSE,FALSE,Employment,1000,,,2024-01-01,2024-01-01,,";
            $rows[] = $row;
        }
        
        return implode("\n", $rows);
    }

    private function getCsvWithDuplicateHouseholdCode(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'DUP123H,Single Person,1,Own,2000,24000,100,456 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,New,N,Person,,1995-01-01,Female,555-9999,new@test.com,,FALSE,,No,,,FALSE,,Yes,Not Hispanic, Latino or Spanish Origins,White,Never Served in the Military,Employed Full-Time,40,FALSE,FALSE,Employment,2000,,,2024-01-01,2024-01-01,,';
        
        return "$headers\n$row";
    }

    private function getCsvForUpdate(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'UPDATE1,Single Person,1,Own,2000,24000,100,456 New St,City,ST,12345,Updated notes,HEAD_OF_HOUSEHOLD,John,Michael,Doe,,1990-01-01,Male,555-9999,john.updated@test.com,High School Graduate,FALSE,,No,,,FALSE,Employment Based,Yes,Not Hispanic, Latino or Spanish Origins,White,Never Served in the Military,Employed Full-Time,40,FALSE,FALSE,Employment,2000,,,2024-01-01,2024-12-31,Updated docs,Updated participant';
        
        return "$headers\n$row";
    }

    private function getCsvWithInvalidDateFormat(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'DATE001,Single Person,1,Rent,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,Test,T,User,,01/15/1990,Male,555-1234,test@test.com,,FALSE,,No,,,FALSE,,Yes,Not Hispanic, Latino or Spanish Origins,White,Never Served in the Military,Employed Full-Time,40,FALSE,FALSE,Employment,1000,,,01/01/2024,12/31/2024,,';
        
        return "$headers\n$row";
    }

    private function getCsvWithArrayFields(): string
    {
        $headers = 'household_code,household_type,household_size,housing_status,monthly_income,annual_income,hhs_poverty_percentage,street_address,city,state,zip_code,household_notes,participant_type,first_name,middle_name,last_name,suffix,date_of_birth,gender,telephone,email,education_level,currently_enrolled_in_education,education_enrollment_type,disability_status,disability_types,assistance_needs,has_chronic_health_conditions,health_insurance_sources,health_insurance_status,ethnicity,race,military_status,work_status,hours_worked_per_week,is_seeking_employment,is_underemployed,income_sources,employment_income,other_income_sources,non_cash_benefits,intake_date,last_service_date,documentation_notes,participant_notes';
        
        $row = 'ARRAY01,Single Person,1,Rent,1000,12000,50,123 St,City,ST,12345,,HEAD_OF_HOUSEHOLD,Test,T,User,,1990-01-01,Male,555-1234,test@test.com,High School Graduate,FALSE,,Yes,Physical|Mental,Wheelchair|Medication,TRUE,Medicare|Medicaid|Employment Based,Yes,Not Hispanic, Latino or Spanish Origins,White|Asian,Never Served in the Military,Employed Full-Time,40,FALSE,FALSE,Employment|Other,1000,Child Support|SSI,SNAP|WIC|LIHEAP,2024-01-01,2024-01-01,,Multi-race participant';
        
        return "$headers\n$row";
    }

    private function createTempCsvFile(string $content): string
    {
        $filename = 'test_' . uniqid() . '.csv';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, $content);
        return $path;
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = glob(storage_path('app/test_*.csv'));
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        parent::tearDown();
    }
}