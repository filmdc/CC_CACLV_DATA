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
        // Modify the status enum to add 'skipped' and 'filtered' statuses
        DB::statement("ALTER TABLE sage_intacct_sync_items MODIFY COLUMN status ENUM('pending', 'success', 'failed', 'skipped', 'filtered') DEFAULT 'pending'");

        // Add filter_reason column to store why gift was filtered
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->text('filter_reason')->nullable()->after('skip_reason')->comment('Reason why gift was filtered out of sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove filter_reason column
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->dropColumn('filter_reason');
        });

        // Revert status enum to original values
        DB::statement("ALTER TABLE sage_intacct_sync_items MODIFY COLUMN status ENUM('pending', 'success', 'failed') DEFAULT 'pending'");
    }
};
