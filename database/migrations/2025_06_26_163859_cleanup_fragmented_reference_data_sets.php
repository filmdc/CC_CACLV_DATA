<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\ReferenceDataSet;
use App\Models\ReferenceData;
use App\Models\Participant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find the unified CSBG reference data set (should be created by updated seeder)
        $unifiedDataSet = ReferenceDataSet::where('name', 'CSBG 2025 Standard')->first();
        
        if (!$unifiedDataSet) {
            // If the unified set doesn't exist, create it by running the seeder
            echo "Creating unified CSBG reference data set...\n";
            Artisan::call('db:seed', ['--class' => 'ReferenceDataSeeder']);
            $unifiedDataSet = ReferenceDataSet::where('name', 'CSBG 2025 Standard')->first();
        }
        
        if (!$unifiedDataSet) {
            throw new Exception('Could not find or create unified CSBG reference data set');
        }

        echo "Found unified data set: {$unifiedDataSet->name} (ID: {$unifiedDataSet->id})\n";

        // Get all fragmented reference data sets (those with category names as set names)
        $categoryNames = [
            'gender', 'education_level', 'ethnicity', 'race', 'disability_status',
            'health_insurance_status', 'health_insurance_sources', 'military_status',
            'work_status', 'income_sources', 'other_income_sources', 'non_cash_benefits',
            'housing_status', 'housing_type', 'household_type', 'property_type',
            'housing_job_type', 'contractor_service', 'contact_type'
        ];

        $fragmentedSets = ReferenceDataSet::whereIn('name', $categoryNames)
            ->where('id', '!=', $unifiedDataSet->id)
            ->get();

        echo "Found " . $fragmentedSets->count() . " fragmented reference data sets to clean up\n";

        // Update any participants that are linked to fragmented sets
        foreach ($fragmentedSets as $fragmentedSet) {
            $participantCount = Participant::where('reference_data_set_id', $fragmentedSet->id)->count();
            
            if ($participantCount > 0) {
                echo "Updating {$participantCount} participants from fragmented set '{$fragmentedSet->name}' to unified set\n";
                
                Participant::where('reference_data_set_id', $fragmentedSet->id)
                    ->update(['reference_data_set_id' => $unifiedDataSet->id]);
            }
        }

        // Delete reference data records from fragmented sets
        foreach ($fragmentedSets as $fragmentedSet) {
            $dataCount = ReferenceData::where('reference_data_set_id', $fragmentedSet->id)->count();
            
            if ($dataCount > 0) {
                echo "Deleting {$dataCount} reference data records from fragmented set '{$fragmentedSet->name}'\n";
                
                ReferenceData::where('reference_data_set_id', $fragmentedSet->id)->delete();
            }
        }

        // Delete the fragmented reference data sets
        foreach ($fragmentedSets as $fragmentedSet) {
            echo "Deleting fragmented reference data set: '{$fragmentedSet->name}'\n";
            $fragmentedSet->delete();
        }

        // Ensure the unified set is active
        ReferenceDataSet::where('id', '!=', $unifiedDataSet->id)->update(['is_active' => false]);
        $unifiedDataSet->update(['is_active' => true]);

        echo "Cleanup completed. Unified CSBG reference data set is now active.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it involves data cleanup
        // If needed, the original data would need to be restored from backup
        echo "This migration cannot be reversed. Please restore from backup if needed.\n";
    }
};
