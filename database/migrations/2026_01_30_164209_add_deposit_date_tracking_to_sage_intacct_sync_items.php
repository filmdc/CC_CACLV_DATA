<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds columns for tracking deposit date synchronization from Sage Intacct back to DonorPerfect.
     */
    public function up(): void
    {
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            // The deposit date retrieved from Sage Intacct
            $table->date('sage_deposit_date')->nullable()->after('intacct_record_id')
                ->comment('Deposit date from Sage Intacct');

            // When the deposit date was synced back to DonorPerfect
            $table->timestamp('deposit_date_synced_at')->nullable()->after('sage_deposit_date')
                ->comment('When the deposit date was written back to DonorPerfect');

            // Error message if deposit date sync failed
            $table->text('deposit_sync_error')->nullable()->after('deposit_date_synced_at')
                ->comment('Error message if deposit date sync failed');

            // Add index for finding items that need deposit date sync
            $table->index(['status', 'intacct_record_id', 'deposit_date_synced_at'], 'idx_deposit_sync_pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->dropIndex('idx_deposit_sync_pending');
            $table->dropColumn(['sage_deposit_date', 'deposit_date_synced_at', 'deposit_sync_error']);
        });
    }
};
