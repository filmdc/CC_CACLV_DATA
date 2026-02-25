<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add fund code and service code columns from Sage Intacct (PROJECTID / DEPARTMENTID).
     */
    public function up(): void
    {
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->string('sage_fund_code', 50)->nullable()->after('sage_deposit_date')
                ->comment('Fund code (PROJECTID) from Sage Intacct');
            $table->string('sage_service_code', 50)->nullable()->after('sage_fund_code')
                ->comment('Service code (DEPARTMENTID) from Sage Intacct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->dropColumn(['sage_fund_code', 'sage_service_code']);
        });
    }
};
