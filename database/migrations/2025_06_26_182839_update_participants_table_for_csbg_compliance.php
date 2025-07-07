<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            // Convert ENUM fields to VARCHAR to accommodate CSBG-compliant values
            
            // Update gender enum to accommodate CSBG values
            DB::statement("ALTER TABLE participants MODIFY COLUMN gender VARCHAR(50) NULL");
            
            // Update education_level enum to accommodate CSBG values like "2 or 4 Year College Graduate"
            DB::statement("ALTER TABLE participants MODIFY COLUMN education_level VARCHAR(100) NULL");
            
            // Update ethnicity enum to accommodate CSBG values like "Hispanic, Latino or Spanish Origins"
            DB::statement("ALTER TABLE participants MODIFY COLUMN ethnicity VARCHAR(100) NULL");
            
            // Update military_status enum to accommodate CSBG values like "Never Served in the Military"
            DB::statement("ALTER TABLE participants MODIFY COLUMN military_status VARCHAR(50) NULL");
            
            // Update work_status enum to accommodate CSBG values like "Migrant Seasonal Farm Worker"
            DB::statement("ALTER TABLE participants MODIFY COLUMN work_status VARCHAR(100) NULL");
            
            // disability_status and health_insurance_status are simple Yes/No/Unknown so can stay as is
        });

        // Update existing data to match CSBG values where possible
        $this->updateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible since it involves data transformation
        // If needed, the original ENUM definitions would need to be restored manually
        echo "This migration cannot be automatically reversed.\n";
    }

    /**
     * Update existing participant data to match CSBG reference values
     */
    protected function updateExistingData(): void
    {
        // Map old enum values to new CSBG-compliant values
        $mappings = [
            'gender' => [
                'Male' => 'Male',
                'Female' => 'Female',
                'Non-binary' => 'Other',
                'Other' => 'Other',
                'Prefer not to say' => 'Unknown/not reported'
            ],
            'education_level' => [
                'Less than high school' => 'Grades 0-8',
                'High school diploma/GED' => 'High School Graduate',
                'Some college' => '12th Grade + Some Post-Secondary',
                'Associate degree' => '2 or 4 Year College Graduate',
                'Bachelor degree' => '2 or 4 Year College Graduate',
                'Graduate degree' => '2 or 4 Year College Graduate',
                'Unknown/not reported' => 'Unknown/not reported'
            ],
            'ethnicity' => [
                'Hispanic or Latino' => 'Hispanic, Latino or Spanish Origins',
                'Not Hispanic or Latino' => 'Not Hispanic, Latino or Spanish Origins',
                'Unknown/not reported' => 'Unknown/not reported'
            ],
            'military_status' => [
                'Currently serving' => 'Active Military',
                'Veteran' => 'Veteran',
                'Not applicable' => 'Never Served in the Military',
                'Unknown/not reported' => 'Unknown/not reported'
            ],
            'work_status' => [
                'Employed full-time' => 'Employed Full-Time',
                'Employed part-time' => 'Employed Part-Time',
                'Unemployed' => 'Unemployed (Short-Term, 6 months or less)',
                'Not in labor force' => 'Unemployed (Not in Labor Force)',
                'Unknown/not reported' => 'Unknown/not reported'
            ]
        ];

        foreach ($mappings as $field => $mapping) {
            foreach ($mapping as $oldValue => $newValue) {
                DB::table('participants')
                    ->where($field, $oldValue)
                    ->update([$field => $newValue]);
                
                echo "Updated {$field}: '{$oldValue}' -> '{$newValue}'\n";
            }
        }
    }
};