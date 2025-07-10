<?php

namespace App\Console\Commands;

use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CreateEmailAssignments extends Command
{
    protected $signature = 'contract:create-email-assignments {--dry-run : Show what would be created}';
    protected $description = 'Create email assignments for report due dates using existing DueDateEmail records';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Loading due date email assignments...');
        
        // Load the assignments data
        $content = Storage::get('import/due_date_email_assignments.json');
        $assignments = json_decode($content, true);
        
        if (!$assignments) {
            $this->error('Could not load email assignments data');
            return 1;
        }

        $this->info('Found ' . count($assignments) . ' email assignments to process');
        
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($assignments as $assignment) {
            try {
                $contractTitle = $assignment['contract_title'];
                $dueDate = $assignment['due_date'];
                $email = $assignment['email'];
                
                // Find contract report due date
                $reportDueDate = ContractReportDueDate::whereHas('contract', function($query) use ($contractTitle) {
                    $query->where('title', $contractTitle);
                })->where('due_date', $dueDate)->first();
                
                if (!$reportDueDate) {
                    $this->warn("Report due date not found: {$contractTitle} - {$dueDate}");
                    $skipped++;
                    continue;
                }
                
                // Find existing DueDateEmail
                $dueDateEmail = DueDateEmail::where('email', $email)->first();
                
                if (!$dueDateEmail) {
                    $this->warn("DueDateEmail not found for: {$email}");
                    $skipped++;
                    continue;
                }
                
                // Check if assignment already exists
                $exists = DB::table('contract_report_due_date_emails')
                    ->where('contract_report_due_date_id', $reportDueDate->id)
                    ->where('due_date_email_id', $dueDateEmail->id)
                    ->exists();
                
                if ($exists) {
                    $this->line("Assignment already exists: {$email} - {$contractTitle}");
                    $skipped++;
                    continue;
                }
                
                if (!$dryRun) {
                    // Create assignment
                    DB::table('contract_report_due_date_emails')->insert([
                        'contract_report_due_date_id' => $reportDueDate->id,
                        'due_date_email_id' => $dueDateEmail->id,
                        'reminder_intervals' => json_encode([30, 15, 7, 3, 1]),
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                $this->info("Created assignment: {$email} → {$contractTitle} - {$dueDate}");
                $created++;
                
            } catch (\Exception $e) {
                $this->error("Error processing assignment: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Created: {$created}");
        $this->line("  Skipped: {$skipped}");
        $this->line("  Errors: {$errors}");
        
        return 0;
    }
}