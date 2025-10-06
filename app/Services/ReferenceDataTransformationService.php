<?php

namespace App\Services;

use App\Models\ReferenceDataSet;
use App\Models\ReferenceData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReferenceDataTransformationService
{
    protected ?ReferenceDataSet $importDefaultSet = null;
    protected ?ReferenceDataSet $selectedReferenceDataSet = null;
    protected Collection $referenceDataCache;

    public function __construct()
    {
        $this->loadImportDefaultSet();
        $this->loadReferenceDataCache();
    }

    /**
     * Load the import default reference data set
     */
    protected function loadImportDefaultSet(): void
    {
        try {
            $this->importDefaultSet = Cache::remember('import_default_reference_set', 3600, function () {
                return ReferenceDataSet::getImportDefault();
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->importDefaultSet = null;
        }

        if (!$this->importDefaultSet) {
            try {
                Log::warning('No import default reference data set found. Using active set as fallback.');
                $this->importDefaultSet = ReferenceDataSet::getActive();
            } catch (Throwable $exception) {
                report($exception);
                $this->importDefaultSet = null;
            }
        }
    }

    /**
     * Load reference data cache for quick lookups
     */
    protected function loadReferenceDataCache(): void
    {
        $referenceSet = $this->getActiveReferenceSet();
        
        if (!$referenceSet) {
            $this->referenceDataCache = collect();
            return;
        }

        $cacheKey = "reference_data_cache_{$referenceSet->id}";
        
        try {
            $this->referenceDataCache = Cache::remember($cacheKey, 3600, function () use ($referenceSet) {
                return $referenceSet->referenceData()
                    ->where('active', true)
                    ->get()
                    ->groupBy('category');
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->referenceDataCache = collect();
        }
    }

    /**
     * Transform external gender value to CSBG-compliant value
     */
    public function transformGender(string $gender): string
    {
        return $this->transformValue('gender', $gender, $this->getGenderMappings());
    }

    /**
     * Transform external ethnicity value to CSBG-compliant value
     */
    public function transformEthnicity(string $ethnicity): string
    {
        return $this->transformValue('ethnicity', $ethnicity, $this->getEthnicityMappings());
    }

    /**
     * Transform external race value to CSBG-compliant value(s)
     */
    public function transformRace(string $race): array
    {
        $transformed = $this->transformValue('race', $race, $this->getRaceMappings());
        
        // Handle multi-race values
        if (strpos(strtolower($race), 'multi') !== false || strpos(strtolower($race), 'mixed') !== false) {
            return ['Multi-race (two or more of the above)'];
        }
        
        return [$transformed];
    }

    /**
     * Transform external military status value to CSBG-compliant value
     */
    public function transformMilitaryStatus(string $status): string
    {
        return $this->transformValue('military_status', $status, $this->getMilitaryStatusMappings());
    }

    /**
     * Transform external disability status value to CSBG-compliant value
     */
    public function transformDisabilityStatus(string $status): string
    {
        return $this->transformValue('disability_status', $status, $this->getDisabilityStatusMappings());
    }

    /**
     * Transform external health insurance status value to CSBG-compliant value
     */
    public function transformHealthInsuranceStatus(string $status): string
    {
        return $this->transformValue('health_insurance_status', $status, $this->getHealthInsuranceMappings());
    }

    /**
     * Transform external education level value to CSBG-compliant value
     */
    public function transformEducationLevel(string $level): string
    {
        return $this->transformValue('education_level', $level, $this->getEducationLevelMappings());
    }

    /**
     * Transform external work status value to CSBG-compliant value
     */
    public function transformWorkStatus(string $status): string
    {
        return $this->transformValue('work_status', $status, $this->getWorkStatusMappings());
    }

    /**
     * Transform external household type value to CSBG-compliant value
     */
    public function transformHouseholdType(string $type): string
    {
        return $this->transformValue('household_type', $type, $this->getHouseholdTypeMappings());
    }

    /**
     * Transform external housing status value to CSBG-compliant value
     */
    public function transformHousingStatus(string $status): string
    {
        return $this->transformValue('housing_status', $status, $this->getHousingStatusMappings());
    }

    /**
     * Core transformation method that uses reference data for mapping
     */
    public function transformValue(string $category, string $value, array $fallbackMappings = []): string
    {
        if (empty(trim($value))) {
            return 'Unknown/not reported';
        }

        $normalizedValue = strtolower(trim($value));
        
        // Get reference data options for this category
        $referenceOptions = $this->getReferenceDataOptions($category);
        
        // Try exact match first
        $exactMatch = $this->findExactMatch($normalizedValue, $referenceOptions);
        if ($exactMatch) {
            return $exactMatch;
        }
        
        // Try fuzzy match
        $fuzzyMatch = $this->findFuzzyMatch($normalizedValue, $referenceOptions);
        if ($fuzzyMatch['confidence'] > 70) {
            Log::info('Reference data transformation: Fuzzy match applied', [
                'category' => $category,
                'original_value' => $value,
                'matched_value' => $fuzzyMatch['value'],
                'confidence' => $fuzzyMatch['confidence']
            ]);
            return $fuzzyMatch['value'];
        }
        
        // Try fallback mappings
        if (isset($fallbackMappings[$normalizedValue])) {
            return $fallbackMappings[$normalizedValue];
        }
        
        // Log unmapped value for review
        Log::warning('Reference data transformation: Unmapped value', [
            'category' => $category,
            'original_value' => $value,
            'reference_data_set_id' => $this->importDefaultSet?->id
        ]);
        
        return 'Unknown/not reported';
    }

    /**
     * Get reference data options for a category
     */
    protected function getReferenceDataOptions(string $category): Collection
    {
        return $this->referenceDataCache->get($category, collect());
    }

    /**
     * Find exact match in reference data options
     */
    protected function findExactMatch(string $normalizedValue, Collection $options): ?string
    {
        foreach ($options as $option) {
            if (strtolower($option->value) === $normalizedValue || 
                strtolower($option->label) === $normalizedValue) {
                return $option->value;
            }
        }
        
        return null;
    }

    /**
     * Find fuzzy match in reference data options
     */
    protected function findFuzzyMatch(string $normalizedValue, Collection $options): array
    {
        $bestMatch = ['value' => null, 'confidence' => 0];
        
        foreach ($options as $option) {
            $valueConfidence = $this->calculateSimilarity($normalizedValue, strtolower($option->value));
            $labelConfidence = $this->calculateSimilarity($normalizedValue, strtolower($option->label));
            
            $confidence = max($valueConfidence, $labelConfidence);
            
            if ($confidence > $bestMatch['confidence']) {
                $bestMatch = [
                    'value' => $option->value,
                    'confidence' => $confidence
                ];
            }
        }
        
        return $bestMatch;
    }

    /**
     * Calculate similarity percentage between two strings
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        similar_text($str1, $str2, $percentage);
        return $percentage;
    }

    /**
     * Fallback gender mappings for common external system values
     */
    protected function getGenderMappings(): array
    {
        return [
            'man (boy, if child)' => 'Male',
            'woman (girl, if child)' => 'Female',
            'male' => 'Male',
            'female' => 'Female',
            'm' => 'Male',
            'f' => 'Female',
            'boy' => 'Male',
            'girl' => 'Female',
            'transgender' => 'Other',
            'trans' => 'Other',
            'non-binary' => 'Other',
            'nonbinary' => 'Other',
            'questioning' => 'Other',
            'other' => 'Other',
            'prefer not to answer' => 'Unknown/not reported',
            'prefer not to say' => 'Unknown/not reported',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback ethnicity mappings
     */
    protected function getEthnicityMappings(): array
    {
        return [
            'hispanic/latina/e/o' => 'Hispanic, Latino or Spanish Origins',
            'hispanic or latino' => 'Hispanic, Latino or Spanish Origins',
            'hispanic' => 'Hispanic, Latino or Spanish Origins',
            'latino' => 'Hispanic, Latino or Spanish Origins',
            'latina' => 'Hispanic, Latino or Spanish Origins',
            'non-hispanic/non-latina/e/o' => 'Not Hispanic, Latino or Spanish Origins',
            'not hispanic or latino' => 'Not Hispanic, Latino or Spanish Origins',
            'non-hispanic' => 'Not Hispanic, Latino or Spanish Origins',
            'non-latino' => 'Not Hispanic, Latino or Spanish Origins',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback race mappings
     */
    protected function getRaceMappings(): array
    {
        return [
            'white' => 'White',
            'black, african american, or african' => 'Black or African American',
            'black' => 'Black or African American',
            'african american' => 'Black or African American',
            'african' => 'Black or African American',
            'american indian or alaska native' => 'American Indian or Alaska Native',
            'american indian' => 'American Indian or Alaska Native',
            'alaska native' => 'American Indian or Alaska Native',
            'native american' => 'American Indian or Alaska Native',
            'asian' => 'Asian',
            'native hawaiian or other pacific islander' => 'Native Hawaiian and Other Pacific Islander',
            'native hawaiian' => 'Native Hawaiian and Other Pacific Islander',
            'pacific islander' => 'Native Hawaiian and Other Pacific Islander',
            'multi-racial' => 'Multi-race (two or more of the above)',
            'multiracial' => 'Multi-race (two or more of the above)',
            'mixed' => 'Multi-race (two or more of the above)',
            'other' => 'Other',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback military status mappings
     */
    protected function getMilitaryStatusMappings(): array
    {
        return [
            'yes' => 'Veteran',
            'no' => 'Never Served in the Military',
            'veteran' => 'Veteran',
            'currently serving' => 'Active Military',
            'active duty' => 'Active Military',
            'reserve' => 'Active Military',
            'national guard' => 'Active Military',
            'not applicable' => 'Never Served in the Military',
            'n/a' => 'Never Served in the Military',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback disability status mappings
     */
    protected function getDisabilityStatusMappings(): array
    {
        return [
            'yes' => 'Yes',
            'no' => 'No',
            'true' => 'Yes',
            'false' => 'No',
            '1' => 'Yes',
            '0' => 'No',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback health insurance mappings
     */
    protected function getHealthInsuranceMappings(): array
    {
        return [
            'yes' => 'Yes',
            'no' => 'No',
            'true' => 'Yes',
            'false' => 'No',
            '1' => 'Yes',
            '0' => 'No',
            'insured' => 'Yes',
            'uninsured' => 'No',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback education level mappings
     */
    protected function getEducationLevelMappings(): array
    {
        return [
            'less than high school' => 'Grades 0-8',
            'some high school' => 'Grades 9-12/Non-Graduate',
            'high school diploma/ged' => 'High School Graduate',
            'high school graduate' => 'High School Graduate',
            'ged' => 'GED/Equivalency Diploma',
            'some college' => '12th Grade + Some Post-Secondary',
            'associate degree' => '2 or 4 Year College Graduate',
            'bachelor degree' => '2 or 4 Year College Graduate',
            'graduate degree' => 'Graduate of Other Post-Secondary School',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback work status mappings
     */
    protected function getWorkStatusMappings(): array
    {
        return [
            'employed full-time' => 'Employed Full-Time',
            'employed part-time' => 'Employed Part-Time',
            'unemployed' => 'Unemployed (Short-Term, 6 months or less)',
            'not in labor force' => 'Unemployed (Not in Labor Force)',
            'retired' => 'Retired',
            'migrant seasonal farm worker' => 'Migrant Seasonal Farm Worker',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback household type mappings
     */
    protected function getHouseholdTypeMappings(): array
    {
        return [
            'single person' => 'Single Person',
            'single adult' => 'Single Person',
            'adults only' => 'Two Adults NO Children',
            'two adults no children' => 'Two Adults NO Children',
            'single parent' => 'Single Parent Female', // Default assumption
            'female-headed single parent household' => 'Single Parent Female',
            'male-headed single parent household' => 'Single Parent Male',
            'two parent household' => 'Two Parent Household',
            'married with dependents' => 'Two Parent Household',
            'other' => 'Other',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Fallback housing status mappings
     */
    protected function getHousingStatusMappings(): array
    {
        return [
            'owner occupied' => 'Own',
            'own' => 'Own',
            'rent' => 'Rent',
            'rental' => 'Rent',
            'homeless' => 'Homeless',
            'other permanent housing' => 'Other permanent housing',
            'other' => 'Other',
            'unknown' => 'Unknown/not reported',
            'not reported' => 'Unknown/not reported'
        ];
    }

    /**
     * Clear transformation cache
     */
    public function clearCache(): void
    {
        Cache::forget('import_default_reference_set');
        if ($this->importDefaultSet) {
            Cache::forget("reference_data_cache_{$this->importDefaultSet->id}");
        }
        
        $this->loadImportDefaultSet();
        $this->loadReferenceDataCache();
    }

    /**
     * Set the selected reference data set for transformations
     */
    public function setSelectedReferenceDataSet(?ReferenceDataSet $dataSet): void
    {
        $this->selectedReferenceDataSet = $dataSet;
        // Reload cache with new data set
        $this->loadReferenceDataCache();
    }

    /**
     * Get the currently active reference data set (selected or default)
     */
    protected function getActiveReferenceSet(): ?ReferenceDataSet
    {
        return $this->selectedReferenceDataSet ?? $this->importDefaultSet;
    }

    /**
     * Get the current import default reference data set
     */
    public function getImportDefaultSet(): ?ReferenceDataSet
    {
        return $this->importDefaultSet;
    }
}
