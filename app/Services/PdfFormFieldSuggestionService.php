<?php

namespace App\Services;

class PdfFormFieldSuggestionService
{
    /**
     * Get all available project-related fields in user-friendly format
     *
     * @return array Array of field options with id, display, category, and mapping data
     */
    public static function getAllAvailableFields(): array
    {
        $fields = [];

        // Participant Fields
        $fields = array_merge($fields, self::getParticipantFields());

        // Household Fields (via participant.household)
        $fields = array_merge($fields, self::getHouseholdFields());

        // Property Fields
        $fields = array_merge($fields, self::getPropertyFields());

        // Project/Housing Job Fields
        $fields = array_merge($fields, self::getProjectFields());

        // Contractor Fields
        $fields = array_merge($fields, self::getContractorFields());

        // Program & Service Fields
        $fields = array_merge($fields, self::getProgramFields());

        return $fields;
    }

    /**
     * Suggest best matching field for a PDF field name
     *
     * @param string $pdfFieldName PDF field name to match
     * @param array $availableFields Available field options
     * @return string|null Suggested field ID or null
     */
    public static function suggestMapping(string $pdfFieldName, array $availableFields): ?string
    {
        $pdfFieldLower = strtolower($pdfFieldName);
        $pdfFieldClean = preg_replace('/[^a-z0-9]/', '', $pdfFieldLower);

        $bestMatch = null;
        $bestScore = 0;

        foreach ($availableFields as $field) {
            $score = self::calculateMatchScore($pdfFieldLower, $pdfFieldClean, $field);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $field['id'];
            }
        }

        // Only return if confidence is high enough
        return $bestScore >= 50 ? $bestMatch : null;
    }

    /**
     * Calculate match score between PDF field and database field
     */
    protected static function calculateMatchScore(string $pdfFieldLower, string $pdfFieldClean, array $field): int
    {
        $fieldNameLower = strtolower($field['field_name']);
        $displayLower = strtolower($field['display']);
        $score = 0;

        // Exact match on field name
        if ($pdfFieldLower === $fieldNameLower) {
            return 100;
        }

        // Exact match on clean names
        if ($pdfFieldClean === preg_replace('/[^a-z0-9]/', '', $fieldNameLower)) {
            return 95;
        }

        // Contains field name
        if (str_contains($pdfFieldLower, $fieldNameLower)) {
            $score += 60;
        }

        // Field name contains PDF name
        if (str_contains($fieldNameLower, $pdfFieldLower)) {
            $score += 50;
        }

        // Common field name mappings
        $mappings = [
            'firstname' => ['first_name', 'participant: first name'],
            'lastname' => ['last_name', 'participant: last name'],
            'ssn' => ['ssn', 'social security'],
            'dob' => ['date_of_birth', 'birth date'],
            'birthdate' => ['date_of_birth', 'birth date'],
            'phone' => ['telephone', 'phone'],
            'email' => ['email'],
            'address' => ['address_line_1', 'street address'],
            'city' => ['city'],
            'state' => ['state'],
            'zip' => ['zip_code', 'postal code'],
        ];

        foreach ($mappings as $pattern => $targets) {
            if (str_contains($pdfFieldClean, $pattern)) {
                foreach ($targets as $target) {
                    if (str_contains($displayLower, $target)) {
                        $score += 40;
                        break;
                    }
                }
            }
        }

        return $score;
    }

    protected static function getParticipantFields(): array
    {
        return [
            ['id' => 'participant.first_name', 'display' => 'Participant: First Name', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'first_name', 'relationship_path' => null, 'field_name' => 'first_name'],
            ['id' => 'participant.last_name', 'display' => 'Participant: Last Name', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'last_name', 'relationship_path' => null, 'field_name' => 'last_name'],
            ['id' => 'participant.middle_name', 'display' => 'Participant: Middle Name', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'middle_name', 'relationship_path' => null, 'field_name' => 'middle_name'],
            ['id' => 'participant.ssn', 'display' => 'Participant: Social Security Number', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'ssn', 'relationship_path' => null, 'field_name' => 'ssn'],
            ['id' => 'participant.date_of_birth', 'display' => 'Participant: Date of Birth', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'date_of_birth', 'relationship_path' => null, 'field_name' => 'date_of_birth'],
            ['id' => 'participant.telephone', 'display' => 'Participant: Phone Number', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'telephone', 'relationship_path' => null, 'field_name' => 'telephone'],
            ['id' => 'participant.email', 'display' => 'Participant: Email Address', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'email', 'relationship_path' => null, 'field_name' => 'email'],
            ['id' => 'participant.gender', 'display' => 'Participant: Gender', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'gender', 'relationship_path' => null, 'field_name' => 'gender'],
            ['id' => 'participant.race', 'display' => 'Participant: Race', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'race', 'relationship_path' => null, 'field_name' => 'race'],
            ['id' => 'participant.ethnicity', 'display' => 'Participant: Ethnicity', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'ethnicity', 'relationship_path' => null, 'field_name' => 'ethnicity'],
            ['id' => 'participant.marital_status', 'display' => 'Participant: Marital Status', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'marital_status', 'relationship_path' => null, 'field_name' => 'marital_status'],
            ['id' => 'participant.veteran_status', 'display' => 'Participant: Veteran Status', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'veteran_status', 'relationship_path' => null, 'field_name' => 'veteran_status'],
            ['id' => 'participant.disability_status', 'display' => 'Participant: Disability Status', 'category' => 'Participant', 'model_name' => 'Participant', 'model_field' => 'disability_status', 'relationship_path' => null, 'field_name' => 'disability_status'],
        ];
    }

    protected static function getHouseholdFields(): array
    {
        return [
            ['id' => 'participant.household.household_code', 'display' => 'Household: Household Code', 'category' => 'Household', 'model_name' => 'Participant', 'model_field' => 'household_code', 'relationship_path' => 'household', 'field_name' => 'household_code'],
            ['id' => 'participant.household.household_type', 'display' => 'Household: Household Type', 'category' => 'Household', 'model_name' => 'Participant', 'model_field' => 'household_type', 'relationship_path' => 'household', 'field_name' => 'household_type'],
            ['id' => 'participant.household.household_size', 'display' => 'Household: Size (Number of Members)', 'category' => 'Household', 'model_name' => 'Participant', 'model_field' => 'household_size', 'relationship_path' => 'household', 'field_name' => 'household_size'],
            ['id' => 'participant.household.income_total', 'display' => 'Household: Total Income', 'category' => 'Household', 'model_name' => 'Participant', 'model_field' => 'income_total', 'relationship_path' => 'household', 'field_name' => 'income_total'],
            ['id' => 'participant.household.housing_status', 'display' => 'Household: Housing Status', 'category' => 'Household', 'model_name' => 'Participant', 'model_field' => 'housing_status', 'relationship_path' => 'household', 'field_name' => 'housing_status'],
        ];
    }

    protected static function getPropertyFields(): array
    {
        return [
            ['id' => 'property.address_line_1', 'display' => 'Property: Street Address', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'address_line_1', 'relationship_path' => null, 'field_name' => 'address_line_1'],
            ['id' => 'property.address_line_2', 'display' => 'Property: Address Line 2', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'address_line_2', 'relationship_path' => null, 'field_name' => 'address_line_2'],
            ['id' => 'property.city', 'display' => 'Property: City', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'city', 'relationship_path' => null, 'field_name' => 'city'],
            ['id' => 'property.state', 'display' => 'Property: State', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'state', 'relationship_path' => null, 'field_name' => 'state'],
            ['id' => 'property.zip_code', 'display' => 'Property: Zip Code', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'zip_code', 'relationship_path' => null, 'field_name' => 'zip_code'],
            ['id' => 'property.county', 'display' => 'Property: County', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'county', 'relationship_path' => null, 'field_name' => 'county'],
            ['id' => 'property.property_type', 'display' => 'Property: Property Type', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'property_type', 'relationship_path' => null, 'field_name' => 'property_type'],
            ['id' => 'property.ownership_status', 'display' => 'Property: Ownership Status', 'category' => 'Property', 'model_name' => 'Property', 'model_field' => 'ownership_status', 'relationship_path' => null, 'field_name' => 'ownership_status'],
        ];
    }

    protected static function getProjectFields(): array
    {
        return [
            ['id' => 'housingjob.job_number', 'display' => 'Project: Job Number', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'job_number', 'relationship_path' => null, 'field_name' => 'job_number'],
            ['id' => 'housingjob.job_title', 'display' => 'Project: Job Title', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'job_title', 'relationship_path' => null, 'field_name' => 'job_title'],
            ['id' => 'housingjob.job_description', 'display' => 'Project: Description', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'job_description', 'relationship_path' => null, 'field_name' => 'job_description'],
            ['id' => 'housingjob.status', 'display' => 'Project: Status', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'status', 'relationship_path' => null, 'field_name' => 'status'],
            ['id' => 'housingjob.request_date', 'display' => 'Project: Request Date', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'request_date', 'relationship_path' => null, 'field_name' => 'request_date'],
            ['id' => 'housingjob.start_date', 'display' => 'Project: Start Date', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'start_date', 'relationship_path' => null, 'field_name' => 'start_date'],
            ['id' => 'housingjob.target_completion_date', 'display' => 'Project: Target Completion Date', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'target_completion_date', 'relationship_path' => null, 'field_name' => 'target_completion_date'],
            ['id' => 'housingjob.actual_completion_date', 'display' => 'Project: Actual Completion Date', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'actual_completion_date', 'relationship_path' => null, 'field_name' => 'actual_completion_date'],
            ['id' => 'housingjob.estimated_cost', 'display' => 'Project: Estimated Cost', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'estimated_cost', 'relationship_path' => null, 'field_name' => 'estimated_cost'],
            ['id' => 'housingjob.approved_amount', 'display' => 'Project: Approved Amount', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'approved_amount', 'relationship_path' => null, 'field_name' => 'approved_amount'],
            ['id' => 'housingjob.priority', 'display' => 'Project: Priority', 'category' => 'Project', 'model_name' => 'HousingJob', 'model_field' => 'priority', 'relationship_path' => null, 'field_name' => 'priority'],
        ];
    }

    protected static function getContractorFields(): array
    {
        return [
            ['id' => 'contractor.company_name', 'display' => 'Contractor: Company Name', 'category' => 'Contractor', 'model_name' => 'Contractor', 'model_field' => 'company_name', 'relationship_path' => null, 'field_name' => 'company_name'],
            ['id' => 'contractor.contact_name', 'display' => 'Contractor: Contact Name', 'category' => 'Contractor', 'model_name' => 'Contractor', 'model_field' => 'contact_name', 'relationship_path' => null, 'field_name' => 'contact_name'],
            ['id' => 'contractor.phone', 'display' => 'Contractor: Phone Number', 'category' => 'Contractor', 'model_name' => 'Contractor', 'model_field' => 'phone', 'relationship_path' => null, 'field_name' => 'phone'],
            ['id' => 'contractor.email', 'display' => 'Contractor: Email', 'category' => 'Contractor', 'model_name' => 'Contractor', 'model_field' => 'email', 'relationship_path' => null, 'field_name' => 'email'],
            ['id' => 'contractor.license_number', 'display' => 'Contractor: License Number', 'category' => 'Contractor', 'model_name' => 'Contractor', 'model_field' => 'license_number', 'relationship_path' => null, 'field_name' => 'license_number'],
        ];
    }

    protected static function getProgramFields(): array
    {
        return [
            ['id' => 'program.name', 'display' => 'Program: Name', 'category' => 'Program', 'model_name' => 'Program', 'model_field' => 'name', 'relationship_path' => null, 'field_name' => 'name'],
            ['id' => 'service.name', 'display' => 'Service: Name', 'category' => 'Program', 'model_name' => 'Service', 'model_field' => 'name', 'relationship_path' => null, 'field_name' => 'name'],
        ];
    }
}
