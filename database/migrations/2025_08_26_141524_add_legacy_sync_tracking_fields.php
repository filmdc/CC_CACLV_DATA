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
        // Add legacy sync fields to contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            
            $table->index('legacy_id');
            $table->index('last_synced_at');
        });
        
        // Add legacy sync fields to programs table
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            
            $table->index('legacy_id');
            $table->index('last_synced_at');
        });
        
        // Add legacy sync fields to funders table
        Schema::table('funders', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            
            $table->index('legacy_id');
            $table->index('last_synced_at');
        });
        
        // Add legacy sync fields to contract_report_due_dates table
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            
            $table->index('legacy_id');
            $table->index('last_synced_at');
        });
        
        // Add legacy sync fields to contract_reports table
        Schema::table('contract_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            
            $table->index('legacy_id');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['contracts_legacy_id_index']);
            $table->dropIndex(['contracts_last_synced_at_index']);
            $table->dropUnique(['contracts_legacy_id_unique']);
            $table->dropColumn(['legacy_id', 'last_synced_at']);
        });
        
        // Remove from programs table
        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex(['programs_legacy_id_index']);
            $table->dropIndex(['programs_last_synced_at_index']);
            $table->dropUnique(['programs_legacy_id_unique']);
            $table->dropColumn(['legacy_id', 'last_synced_at']);
        });
        
        // Remove from funders table
        Schema::table('funders', function (Blueprint $table) {
            $table->dropIndex(['funders_legacy_id_index']);
            $table->dropIndex(['funders_last_synced_at_index']);
            $table->dropUnique(['funders_legacy_id_unique']);
            $table->dropColumn(['legacy_id', 'last_synced_at']);
        });
        
        // Remove from contract_report_due_dates table
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            $table->dropIndex(['contract_report_due_dates_legacy_id_index']);
            $table->dropIndex(['contract_report_due_dates_last_synced_at_index']);
            $table->dropUnique(['contract_report_due_dates_legacy_id_unique']);
            $table->dropColumn(['legacy_id', 'last_synced_at']);
        });
        
        // Remove from contract_reports table
        Schema::table('contract_reports', function (Blueprint $table) {
            $table->dropIndex(['contract_reports_legacy_id_index']);
            $table->dropIndex(['contract_reports_last_synced_at_index']);
            $table->dropUnique(['contract_reports_legacy_id_unique']);
            $table->dropColumn(['legacy_id', 'last_synced_at']);
        });
    }
};