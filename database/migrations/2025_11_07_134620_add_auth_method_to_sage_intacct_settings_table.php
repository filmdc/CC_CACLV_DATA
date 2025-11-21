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
        // Check if column already exists (may have been added by earlier migration)
        if (!Schema::hasColumn('sage_intacct_settings', 'auth_method')) {
            Schema::table('sage_intacct_settings', function (Blueprint $table) {
                $table->enum('auth_method', ['xml', 'oauth'])->default('xml')->after('api_version');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropColumn('auth_method');
        });
    }
};
