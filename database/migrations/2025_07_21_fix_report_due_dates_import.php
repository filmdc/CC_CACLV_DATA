<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clear existing data that might have been incorrectly imported
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('contract_report_due_date_emails')->truncate();
        DB::table('contract_report_due_dates')->truncate();
        DB::table('due_date_emails')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        // Get the imported data
        require base_path('report_due_dates_with_emails.php');
        
        // First, create all unique email records
        $allEmails = [];
        foreach ($reportDueDatesWithEmails as $dueDate) {
            foreach ($dueDate['email_addresses'] as $email) {
                $allEmails[$email] = true;
            }
        }
        
        // Create DueDateEmail records for all unique emails
        foreach (array_keys($allEmails) as $email) {
            $user = User::where('email', $email)->first();
            
            DB::table('due_date_emails')->insert([
                'email' => $email,
                'name' => $user ? $user->name : explode('@', $email)[0],
                'active' => true,
                'preferences' => json_encode([
                    'reminder_intervals' => [30, 15, 7, 3, 1],
                    'receive_overdue_notifications' => true,
                    'receive_completion_notifications' => false,
                ]),
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Import the due dates
        foreach ($reportDueDatesWithEmails as $originalDueDate) {
            // Check if contract exists
            $contract = Contract::find($originalDueDate['contract_id']);
            if (!$contract) {
                continue; // Skip if contract doesn't exist
            }
            
            // Extract report name from description or generate from date
            $reportName = $this->extractReportName($originalDueDate['description'], $originalDueDate['due_date']);
            
            // Determine report type from description
            $reportType = $this->determineReportType($originalDueDate['description']);
            
            // Convert status
            $status = $originalDueDate['status'] === 'incomplete' ? 'pending' : 'completed';
            
            // Create the due date record
            $dueDate = ContractReportDueDate::create([
                'contract_id' => $originalDueDate['contract_id'],
                'due_date' => $originalDueDate['due_date'],
                'status' => $status,
                'description' => $originalDueDate['description'],
                'report_name' => $reportName,
                'report_type' => $reportType,
                'reminder_intervals' => [30, 15, 7, 3, 1], // Default intervals
                'active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]);
            
            // Attach emails
            foreach ($originalDueDate['email_addresses'] as $email) {
                $dueDateEmail = DueDateEmail::where('email', $email)->first();
                if ($dueDateEmail) {
                    $dueDate->emails()->attach($dueDateEmail->id, [
                        'reminder_intervals' => [30, 15, 7, 3, 1],
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
    
    /**
     * Extract report name from description
     */
    private function extractReportName($description, $dueDate)
    {
        if (!$description) {
            return 'Report Due ' . date('F j, Y', strtotime($dueDate));
        }
        
        // Try to extract meaningful report name
        if (preg_match('/CSBG (\d+\w+ Quarter) Report/i', strip_tags($description), $matches)) {
            return 'CSBG ' . $matches[1] . ' Report';
        }
        
        if (preg_match('/^<p>([^<]+)/', $description, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 10 && strlen($name) < 100) {
                return $name;
            }
        }
        
        // Default to date-based name
        return 'Report Due ' . date('F j, Y', strtotime($dueDate));
    }
    
    /**
     * Determine report type from description
     */
    private function determineReportType($description)
    {
        if (!$description) {
            return null;
        }
        
        $desc = strtolower(strip_tags($description));
        
        if (strpos($desc, 'quarterly') !== false || strpos($desc, 'quarter') !== false) {
            return 'quarterly';
        }
        if (strpos($desc, 'annual') !== false || strpos($desc, 'yearly') !== false) {
            return 'annual';
        }
        if (strpos($desc, 'semi-annual') !== false || strpos($desc, 'semi annual') !== false) {
            return 'semi-annual';
        }
        if (strpos($desc, 'final') !== false) {
            return 'final';
        }
        if (strpos($desc, 'monthly') !== false) {
            return 'monthly';
        }
        
        return 'other';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data fix migration, not reversible
    }
};