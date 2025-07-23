<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the column already exists
        if (!Schema::hasColumn('contract_report_due_dates', 'report_name')) {
            Schema::table('contract_report_due_dates', function (Blueprint $table) {
                $table->string('report_name')->nullable()->after('due_date');
            });
            
            // Populate report_name based on report_type
            DB::table('contract_report_due_dates')->get()->each(function ($dueDate) {
                $reportName = match($dueDate->report_type) {
                    'quarterly' => 'Quarterly Report',
                    'annual' => 'Annual Report',
                    'semi-annual' => 'Semi-Annual Report',
                    'monthly' => 'Monthly Report',
                    'final' => 'Final Report',
                    default => ucfirst($dueDate->report_type ?? 'Other') . ' Report'
                };
                
                DB::table('contract_report_due_dates')
                    ->where('id', $dueDate->id)
                    ->update(['report_name' => $reportName]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            $table->dropColumn('report_name');
        });
    }
};