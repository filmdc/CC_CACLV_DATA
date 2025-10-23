<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the fund_code column from contracts table as it duplicates
     * the grant_code field. Audit confirmed zero records have fund_code data.
     */
    public function up(): void
    {
        // Check if column exists before trying to drop it
        if (Schema::hasColumn('contracts', 'fund_code')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('fund_code');
            });

            echo "\n✓ Removed fund_code column from contracts table\n";
            echo "  grant_code field remains as the standard code field\n\n";
        } else {
            echo "\n✓ fund_code column does not exist (already removed)\n\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * Restores the fund_code column if needed for rollback.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('contracts', 'fund_code')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('fund_code')->nullable()->after('notes');
            });

            echo "\n✓ Restored fund_code column to contracts table\n\n";
        }
    }
};
