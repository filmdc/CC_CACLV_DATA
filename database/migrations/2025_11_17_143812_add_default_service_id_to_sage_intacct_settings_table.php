<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            // Default Service/Item ID for Other Receipts when sub-solicitation has no mapping
            $table->string('default_service_id')->default('S14999')->after('default_payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropColumn('default_service_id');
        });
    }
};
