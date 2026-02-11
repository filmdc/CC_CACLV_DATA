<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->string('gift_id_field_name', 100)->default('GIFT_ID')->after('default_payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropColumn('gift_id_field_name');
        });
    }
};
