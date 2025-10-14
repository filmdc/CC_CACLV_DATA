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
        if (Schema::hasColumn('reference_data', 'reference_data_set_id')) {
            return;
        }

        // First, create or reuse a default reference data set for existing data
        $defaultSet = \App\Models\ReferenceDataSet::firstOrCreate(
            ['version' => '1.0.0'],
            [
                'name' => 'Initial Data Set',
                'description' => 'Default reference data set created during migration',
                'is_active' => true,
                'is_draft' => false,
                'activated_at' => now(),
                'created_by' => null,
            ]
        );

        // Add the column as nullable first (if it still does not exist)
        Schema::table('reference_data', function (Blueprint $table) {
            if (!Schema::hasColumn('reference_data', 'reference_data_set_id')) {
                $table->foreignId('reference_data_set_id')->after('id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        if (!Schema::hasColumn('reference_data', 'reference_data_set_id')) {
            return;
        }

        // Update all existing records to use the default set
        DB::table('reference_data')->update(['reference_data_set_id' => $defaultSet->id]);

        // Now make the column not nullable and update constraints
        Schema::table('reference_data', function (Blueprint $table) {
            if (Schema::hasColumn('reference_data', 'reference_data_set_id')) {
                $table->foreignId('reference_data_set_id')->nullable(false)->change();

                try {
                    $table->dropUnique(['category', 'value']);
                } catch (\Throwable $e) {
                    // Index might already be removed; ignore
                }

                $table->unique(['reference_data_set_id', 'category', 'value']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_data', function (Blueprint $table) {
            $table->dropForeign(['reference_data_set_id']);
            $table->dropUnique(['reference_data_set_id', 'category', 'value']);
            $table->dropColumn('reference_data_set_id');
            $table->unique(['category', 'value']);
        });
    }
};
