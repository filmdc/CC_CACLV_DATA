<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all report due dates
        $dueDates = DB::table('contract_report_due_dates')->get();
        
        foreach ($dueDates as $dueDate) {
            $updates = [];
            
            // Clean HTML from description
            if ($dueDate->description) {
                $cleanDescription = strip_tags($dueDate->description);
                if ($cleanDescription !== $dueDate->description) {
                    $updates['description'] = $cleanDescription;
                    $updates['updated_at'] = now();
                }
            }
            
            // Update if there are changes
            if (!empty($updates)) {
                DB::table('contract_report_due_dates')
                    ->where('id', $dueDate->id)
                    ->update($updates);
            }
        }
        
        echo "Cleaned HTML from " . count($dueDates) . " report due dates.\n";
        
        // Also clean contract reports
        $reports = DB::table('contract_reports')->get();
        $reportCount = 0;
        
        foreach ($reports as $report) {
            $updates = [];
            
            // Clean HTML from title
            if ($report->title) {
                $cleanTitle = strip_tags($report->title);
                if ($cleanTitle !== $report->title) {
                    $updates['title'] = $cleanTitle;
                }
            }
            
            // Clean HTML from description
            if ($report->description) {
                $cleanDescription = strip_tags($report->description);
                if ($cleanDescription !== $report->description) {
                    $updates['description'] = $cleanDescription;
                }
            }
            
            // Update if there are changes
            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('contract_reports')
                    ->where('id', $report->id)
                    ->update($updates);
                $reportCount++;
            }
        }
        
        echo "Cleaned HTML from " . $reportCount . " contract reports.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as we don't store the original HTML
        echo "This migration cannot be reversed.\n";
    }
};