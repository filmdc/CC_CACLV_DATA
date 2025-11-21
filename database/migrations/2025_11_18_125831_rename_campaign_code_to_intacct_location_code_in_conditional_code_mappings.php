<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping of DonorPerfect campaign codes to Sage Intacct location codes
     */
    private array $codeMapping = [
        'AD' => 'P1000',         // Admin
        'FB' => 'P1400',         // Food Bank
        'SF' => 'P1400',         // Food Bank (alternate)
        'SHFB_FY22-23' => 'P1400', // Food Bank FY22-23
        'SS' => 'P1600',         // Shelter
        'SSS_FY22-23' => 'P1600', // Shelter FY22-23
        'SSS_FY23-24' => 'P1600', // Shelter FY23-24
        'CA' => 'P2000',         // CADCA Allentown
        'CB' => 'P3000',         // CADCB Bethlehem
        'GN' => 'P1300',         // Racial & Ethnic Justice
        'RJ' => 'P1300',         // Racial & Ethnic Justice (alternate)
        'HC' => 'P1100',         // Housing Counseling
        'RT' => 'P5000',         // Rising Tide
        'CACLV_FY22-23' => 'P1000', // Admin legacy
        'SH' => 'P1400',         // Food Bank legacy
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Drop existing constraints and indexes FIRST (if they exist)
        try {
            Schema::table('conditional_code_mappings', function (Blueprint $table) {
                $table->dropUnique('unique_conditional_mapping');
            });
        } catch (\Exception $e) {
            // Index may not exist if migration partially ran before
        }

        try {
            Schema::table('conditional_code_mappings', function (Blueprint $table) {
                $table->dropIndex('idx_campaign_field_active');
            });
        } catch (\Exception $e) {
            // Index may not exist if migration partially ran before
        }

        // Step 2: Check if column has already been renamed (from partial migration)
        $columnExists = DB::select("SHOW COLUMNS FROM conditional_code_mappings LIKE 'campaign_code'");
        $columnName = count($columnExists) > 0 ? 'campaign_code' : 'intacct_location_code';

        // Step 3: Transform existing data from DP codes to Intacct location codes
        // This must happen AFTER dropping constraints because multiple DP campaigns
        // map to the same Intacct location (e.g., FB, SF, SHFB_FY22-23 all → P1400)
        foreach ($this->codeMapping as $dpCode => $intacctCode) {
            DB::table('conditional_code_mappings')
                ->where($columnName, $dpCode)
                ->update([$columnName => $intacctCode]);
        }

        // Step 4: Remove duplicate rows that resulted from consolidating multiple DP campaigns
        // to the same Intacct location (e.g., FB, SF, SHFB_FY22-23 all → P1400)
        // Keep the row with the lowest ID (oldest) for each unique combination
        // Use the correct column name (might be campaign_code or intacct_location_code)
        DB::statement("
            DELETE t1 FROM conditional_code_mappings t1
            INNER JOIN (
                SELECT MIN(id) as min_id, {$columnName}, field_type, dp_code
                FROM conditional_code_mappings
                GROUP BY {$columnName}, field_type, dp_code
            ) t2 ON t1.{$columnName} = t2.{$columnName}
                AND t1.field_type = t2.field_type
                AND t1.dp_code = t2.dp_code
            WHERE t1.id > t2.min_id
        ");

        // Step 5: Rename the column (if not already renamed)
        if ($columnName === 'campaign_code') {
            Schema::table('conditional_code_mappings', function (Blueprint $table) {
                $table->renameColumn('campaign_code', 'intacct_location_code');
            });
        }

        // Step 5: Update column comment
        DB::statement("ALTER TABLE conditional_code_mappings MODIFY COLUMN intacct_location_code VARCHAR(255) COMMENT 'Sage Intacct location code (e.g., P1000, P1400, P1600)'");

        // Step 6: Re-create unique constraint and index with new column name
        Schema::table('conditional_code_mappings', function (Blueprint $table) {
            $table->unique(['intacct_location_code', 'field_type', 'dp_code'], 'unique_conditional_mapping');
            $table->index(['intacct_location_code', 'field_type', 'is_active'], 'idx_location_field_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop constraints and indexes
        Schema::table('conditional_code_mappings', function (Blueprint $table) {
            $table->dropUnique('unique_conditional_mapping');
            $table->dropIndex('idx_location_field_active');
        });

        // Step 2: Rename column back
        Schema::table('conditional_code_mappings', function (Blueprint $table) {
            $table->renameColumn('intacct_location_code', 'campaign_code');
        });

        // Step 3: Re-create original constraints and indexes
        Schema::table('conditional_code_mappings', function (Blueprint $table) {
            $table->unique(['campaign_code', 'field_type', 'dp_code'], 'unique_conditional_mapping');
            $table->index(['campaign_code', 'field_type', 'is_active'], 'idx_campaign_field_active');
        });

        // Step 4: Transform data back from Intacct codes to DP codes
        $reverseMapping = array_flip($this->codeMapping);
        foreach ($reverseMapping as $intacctCode => $dpCode) {
            DB::table('conditional_code_mappings')
                ->where('campaign_code', $intacctCode)
                ->update(['campaign_code' => $dpCode]);
        }

        // Step 5: Update column comment
        DB::statement("ALTER TABLE conditional_code_mappings MODIFY COLUMN campaign_code VARCHAR(255) COMMENT 'DonorPerfect campaign code (e.g., AD, FB, SS)'");
    }
};
