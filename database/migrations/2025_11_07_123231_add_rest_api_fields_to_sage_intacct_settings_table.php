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
            // Add REST API credentials
            $table->string('client_id')->nullable()->after('company_id');
            $table->text('client_secret')->nullable()->after('client_id');
            $table->string('api_version')->default('v1')->after('client_secret');

            // Make XML Web Services credentials optional (for backward compatibility)
            $table->string('web_services_user_id')->nullable()->change();
            $table->text('web_services_password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_intacct_settings', function (Blueprint $table) {
            // Remove REST API fields
            $table->dropColumn(['client_id', 'client_secret', 'api_version']);

            // Restore web services fields as required (reverse the change)
            $table->string('web_services_user_id')->nullable(false)->change();
            $table->text('web_services_password')->nullable(false)->change();
        });
    }
};
