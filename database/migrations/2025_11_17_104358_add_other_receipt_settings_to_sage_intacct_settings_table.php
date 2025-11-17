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
            // Toggle to use Other Receipts instead of custom gifts object
            $table->boolean('use_other_receipts')->default(false)->after('include_check_images');

            // Bank/deposit configuration
            $table->boolean('use_undeposited_funds')->default(true)->after('use_other_receipts');
            $table->string('bank_account_id')->nullable()->after('use_undeposited_funds');
            $table->string('undeposited_gl_account')->default('10300')->after('bank_account_id');

            // Default values for missing data
            $table->string('default_gl_account')->default('40100')->after('undeposited_gl_account');
            $table->string('default_payment_method')->default('Cash')->after('default_gl_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropColumn([
                'use_other_receipts',
                'use_undeposited_funds',
                'bank_account_id',
                'undeposited_gl_account',
                'default_gl_account',
                'default_payment_method',
            ]);
        });
    }
};
