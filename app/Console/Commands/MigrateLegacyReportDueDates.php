<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyReportDueDates extends Command
{
    protected $signature = 'migrate:legacy-report-due-dates {--dry-run}';

    protected $description = 'Migrate report due dates from legacy system';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Report Due Dates Migration');
        $this->info('======================================');
        $this->newLine();

        $legacyDueDates = DB::connection('legacy_contracts')->table('report_due_dates')->get();
        $this->info("Found {$legacyDueDates->count()} report due dates");

        // Dry-run analysis
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('======================================');
            $this->info('  DRY RUN ANALYSIS');
            $this->info('======================================');

            // Status breakdown
            $completed = $legacyDueDates->where('status', 'completed')->count();
            $incomplete = $legacyDueDates->where('status', 'incomplete')->count();
            $other = $legacyDueDates->whereNotIn('status', ['completed', 'incomplete'])->count();
            $this->info("Completed: {$completed}");
            $this->info("Incomplete: {$incomplete}");
            if ($other > 0) {
                $this->warn("Other Status: {$other}");
            }

            // Contract relationship check
            $withContract = 0;
            $withoutContract = 0;
            foreach ($legacyDueDates as $dueDate) {
                if (Contract::where('legacy_id', $dueDate->contract_id)->exists()) {
                    $withContract++;
                } else {
                    $withoutContract++;
                }
            }
            $this->info("With Valid Contract: {$withContract}");
            $this->warn("Without Contract (will skip): {$withoutContract}");

            // Already migrated
            $alreadyMigrated = ContractReportDueDate::whereNotNull('legacy_id')->count();
            $this->warn("Already Migrated: {$alreadyMigrated}");

            $this->newLine();
        }

        $this->newLine();

        $bar = $this->output->createProgressBar($legacyDueDates->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;

        foreach ($legacyDueDates as $legacyDueDate) {
            $contract = Contract::where('legacy_id', $legacyDueDate->contract_id)->first();

            if (! $contract) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if (ContractReportDueDate::where('legacy_id', $legacyDueDate->id)->exists()) {
                $skipped++;
                $bar->advance();

                continue;
            }

            // Map status
            $status = match ($legacyDueDate->status) {
                'completed' => 'completed',
                'incomplete' => now()->gt($legacyDueDate->due_date) ? 'overdue' : 'pending',
                default => 'pending'
            };

            // Infer report type from description
            $reportType = $this->inferReportType($legacyDueDate->description);

            // Generate report name from description
            $reportName = $this->generateReportName($legacyDueDate->description, $reportType, $legacyDueDate->due_date);

            if (! $this->option('dry-run')) {
                ContractReportDueDate::create([
                    'contract_id' => $contract->id,
                    'due_date' => $legacyDueDate->due_date,
                    'status' => $status,
                    'description' => $legacyDueDate->description,
                    'report_name' => $reportName,
                    'report_type' => $reportType,
                    'microsoft_event_id' => $legacyDueDate->microsoft_event_id,
                    'invite_sent' => $legacyDueDate->invite_sent,
                    'active' => true,
                    'created_at' => $legacyDueDate->created_at,
                    'updated_at' => $legacyDueDate->updated_at,
                    'legacy_id' => $legacyDueDate->id,
                    'last_synced_at' => now(),
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

    protected function inferReportType(?string $description): string
    {
        if (! $description) {
            return 'quarterly';
        }

        $desc = strtolower($description);

        if (str_contains($desc, 'monthly')) {
            return 'monthly';
        }
        if (str_contains($desc, 'quarterly') || str_contains($desc, 'quarter')) {
            return 'quarterly';
        }
        if (str_contains($desc, 'annual') || str_contains($desc, 'yearly')) {
            return 'annual';
        }
        if (str_contains($desc, 'final')) {
            return 'final';
        }
        if (str_contains($desc, 'semi-annual') || str_contains($desc, 'semi annual')) {
            return 'semi_annual';
        }

        return 'quarterly';
    }

    protected function generateReportName(?string $description, string $reportType, $dueDate): string
    {
        if (! $description) {
            return ucfirst($reportType).' Report - '.date('Y-m-d', strtotime($dueDate));
        }

        // Strip HTML tags
        $cleanText = strip_tags($description);

        // Get first sentence or first 100 characters
        $firstSentence = preg_split('/[.!?]/', $cleanText, 2)[0];
        $cleanName = trim($firstSentence);

        // If still too long, truncate to 200 characters
        if (strlen($cleanName) > 200) {
            $cleanName = substr($cleanName, 0, 197).'...';
        }

        // If we got nothing useful, create default name
        if (empty($cleanName) || strlen($cleanName) < 5) {
            $cleanName = ucfirst($reportType).' Report - '.date('Y-m-d', strtotime($dueDate));
        }

        return $cleanName;
    }
}
