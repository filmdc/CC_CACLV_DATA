<?php

namespace App\Console\Commands;

use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixEmailAssignments extends Command
{
    protected $signature = 'contract:fix-email-assignments {--dry-run : Run without making changes}';
    protected $description = 'Fix missing email assignments for contract report due dates';

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
        $failed = 0;

        foreach ($assignments as $assignment) {
            try {
                // Find the contract by title
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
                
                // Find or create DueDateEmail
                $dueDateEmail = DueDateEmail::where('email', $email)->first();
                
                if (!$dueDateEmail) {
                    if (!$dryRun) {
                        $dueDateEmail = DueDateEmail::create([
                            'email' => $email,
                            'name' => $this->generateNameFromEmail($email),
                            'active' => true,
                            'preferences' => [
                                'reminder_intervals' => [30, 15, 7, 3, 1],
                                'receive_overdue_notifications' => true,
                                'receive_completion_notifications' => false,
                            ],
                            'created_by' => 1,
                        ]);
                    }
                    $this->info("Created DueDateEmail: {$email}");
                } else {
                    $this->line("Found existing DueDateEmail: {$email}");
                }
                
                // Check if assignment already exists
                if ($dueDateEmail && !$dryRun) {
                    $exists = $reportDueDate->emails()->where('due_date_email_id', $dueDateEmail->id)->exists();
                    
                    if (!$exists) {
                        $reportDueDate->emails()->attach($dueDateEmail->id, [
                            'reminder_intervals' => json_encode([30, 15, 7, 3, 1]),
                            'active' => true,
                        ]);
                        $this->info("Assigned {$email} to due date: {$contractTitle} - {$dueDate}");
                        $created++;
                    } else {
                        $this->line("Assignment already exists: {$email} - {$contractTitle}");
                        $skipped++;
                    }
                } else {
                    $created++;
                }
                
            } catch (\Exception $e) {
                $this->error("Failed to process assignment: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Created: {$created}");
        $this->line("  Skipped: {$skipped}");
        $this->line("  Failed: {$failed}");
        
        return 0;
    }
    
    private function generateNameFromEmail(string $email): string
    {
        $namePart = explode('@', $email)[0];
        $name = str_replace(['.', '_', '-'], ' ', $namePart);
        return ucwords($name);
    }
}