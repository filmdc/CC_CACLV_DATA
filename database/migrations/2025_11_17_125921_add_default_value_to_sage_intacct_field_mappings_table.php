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
        Schema::table('sage_intacct_field_mappings', function (Blueprint $table) {
            $table->string('default_value')->nullable()->after('intacct_field')
                ->comment('Default value to use when DonorPerfect field is missing or empty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_field_mappings', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });
    }
};
