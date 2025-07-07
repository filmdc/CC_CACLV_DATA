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
        Schema::table('reference_data_sets', function (Blueprint $table) {
            $table->boolean('is_import_default')->default(false)->after('is_active');
            
            // Add index for performance when querying import default
            $table->index('is_import_default');
        });
        
        // Set the currently active CSBG reference data set as the initial import default
        $activeSet = DB::table('reference_data_sets')
            ->where('is_active', true)
            ->where('name', 'like', '%CSBG%')
            ->first();
            
        if ($activeSet) {
            DB::table('reference_data_sets')
                ->where('id', $activeSet->id)
                ->update(['is_import_default' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_data_sets', function (Blueprint $table) {
            $table->dropIndex(['is_import_default']);
            $table->dropColumn('is_import_default');
        });
    }
};