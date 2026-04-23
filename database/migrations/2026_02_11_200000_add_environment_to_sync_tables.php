<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add environment column to sage_intacct_sync_history
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->string('environment', 20)->default('sandbox')->after('id');
            $table->index('environment');
        });

        // Add environment column to sage_intacct_sync_items
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->string('environment', 20)->default('sandbox')->after('sync_history_id');
            $table->index('environment');
        });

        // Backfill existing records using configuration_snapshot to determine environment
        $histories = DB::table('sage_intacct_sync_history')->get();
        foreach ($histories as $history) {
            $environment = 'sandbox'; // default

            if ($history->configuration_snapshot) {
                $snapshot = json_decode($history->configuration_snapshot, true);
                $companyId = $snapshot['company_id'] ?? '';
                if (! empty($companyId) && strtolower($companyId) !== 'caclv-sandbox') {
                    $environment = 'production';
                }
            }

            DB::table('sage_intacct_sync_history')
                ->where('id', $history->id)
                ->update(['environment' => $environment]);

            // Also update all sync items for this history record
            DB::table('sage_intacct_sync_items')
                ->where('sync_history_id', $history->id)
                ->update(['environment' => $environment]);
        }
    }

    public function down(): void
    {
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->dropIndex(['environment']);
            $table->dropColumn('environment');
        });

        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->dropIndex(['environment']);
            $table->dropColumn('environment');
        });
    }
};
