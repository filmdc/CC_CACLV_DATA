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
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('sage_intacct_settings', 'user_id')) {
                $table->string('user_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('sage_intacct_settings', 'location_id')) {
                $table->string('location_id')->nullable()->after('web_services_password');
            }
            if (!Schema::hasColumn('sage_intacct_settings', 'department_id')) {
                $table->string('department_id')->nullable()->after('location_id');
            }
            if (!Schema::hasColumn('sage_intacct_settings', 'class_id')) {
                $table->string('class_id')->nullable()->after('department_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'location_id', 'department_id', 'class_id']);
        });
    }
};
