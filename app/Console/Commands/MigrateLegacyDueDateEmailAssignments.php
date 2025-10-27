<?php

namespace App\Console\Commands;

use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyDueDateEmailAssignments extends Command
{
    protected $signature = 'migrate:legacy-due-date-email-assignments {--dry-run}';
    protected $description = 'Migrate due date email assignments (pivot table)';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Due Date Email Assignments');
        $this->info('======================================');
        $this->newLine();

        $legacyAssignments = DB::connection('legacy_contracts')
            ->table('due_date_email_report_due_date')
            ->get();

        $this->info("Found {$legacyAssignments->count()} assignments");
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyAssignments->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;

        foreach ($legacyAssignments as $assignment) {
            $dueDate = ContractReportDueDate::where('legacy_id', $assignment->report_due_date_id)->first();
            $legacyEmail = DB::connection('legacy_contracts')
                ->table('due_date_emails')
                ->where('id', $assignment->due_date_email_id)
                ->first();

            if (!$dueDate || !$legacyEmail) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $dueDateEmail = DueDateEmail::where('email', $legacyEmail->email)->first();

            if (!$dueDateEmail) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$this->option('dry-run')) {
                DB::table('contract_report_due_date_emails')->insertOrIgnore([
                    'contract_report_due_date_id' => $dueDate->id,
                    'due_date_email_id' => $dueDateEmail->id,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migrated: {$migrated} | Skipped: {$skipped}");
        return 0;
    }
}