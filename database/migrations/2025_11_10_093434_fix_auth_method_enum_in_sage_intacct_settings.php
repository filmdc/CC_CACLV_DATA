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
        // Check if auth_method column exists and needs fixing
        if (!Schema::hasTable('sage_intacct_settings') || !Schema::hasColumn('sage_intacct_settings', 'auth_method')) {
            return;
        }

        // Check if column already has correct enum values
        $columnInfo = DB::select("SHOW COLUMNS FROM sage_intacct_settings WHERE Field = 'auth_method'");
        if (!empty($columnInfo) && str_contains($columnInfo[0]->Type, 'xml')) {
            // Already has correct enum values, skip
            return;
        }

        // First, convert enum to varchar to allow updating values
        DB::statement("ALTER TABLE sage_intacct_settings MODIFY COLUMN auth_method VARCHAR(20) NOT NULL DEFAULT 'xml'");

        // Update 'credentials' to 'xml'
        DB::table('sage_intacct_settings')
            ->where('auth_method', 'credentials')
            ->update(['auth_method' => 'xml']);

        // Now change back to enum with correct values
        DB::statement("ALTER TABLE sage_intacct_settings MODIFY COLUMN auth_method ENUM('xml', 'oauth') NOT NULL DEFAULT 'xml'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to the old enum values
        DB::statement("ALTER TABLE sage_intacct_settings MODIFY COLUMN auth_method VARCHAR(20) NOT NULL DEFAULT 'credentials'");

        // Update 'xml' back to 'credentials'
        DB::table('sage_intacct_settings')
            ->where('auth_method', 'xml')
            ->update(['auth_method' => 'credentials']);

        // Restore old enum
        DB::statement("ALTER TABLE sage_intacct_settings MODIFY COLUMN auth_method ENUM('credentials', 'oauth') NOT NULL DEFAULT 'credentials'");
    }
};
