<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyReports extends Command
{
    protected $signature = 'migrate:legacy-reports {--dry-run} {--csv : Generate CSV report of all reports with migration status}';

    protected $description = 'Migrate reports from legacy system';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Reports Migration');
        $this->info('======================================');
        $this->newLine();

        $legacyReports = DB::connection('legacy_contracts')->table('reports')->get();
        $this->info("Found {$legacyReports->count()} reports");

        // Dry-run analysis
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('======================================');
            $this->info('  DRY RUN ANALYSIS');
            $this->info('======================================');

            // Contract relationship check
            $withValidContract = 0;
            $withoutContract = 0;
            $missingContractIds = [];

            foreach ($legacyReports as $report) {
                $contract = Contract::where('legacy_id', $report->contract_id)->first();
                if ($contract) {
                    $withValidContract++;
                } else {
                    $withoutContract++;
                    if (! in_array($report->contract_id, $missingContractIds)) {
                        $missingContractIds[] = $report->contract_id;
                    }
                }
            }

            $this->info("Reports with Valid Contract: {$withValidContract}");
            $this->warn("Reports with Missing Contract (will skip): {$withoutContract}");
            if ($withoutContract > 0) {
                $this->line('  → Missing Contract IDs: '.implode(', ', $missingContractIds));
            }

            // Due date relationship check
            $withDueDate = 0;
            $withoutDueDate = 0;
            $withValidDueDate = 0;

            foreach ($legacyReports as $report) {
                if ($report->due_date_id) {
                    $withDueDate++;
                    $dueDate = ContractReportDueDate::where('legacy_id', $report->due_date_id)->first();
                    if ($dueDate) {
                        $withValidDueDate++;
                    }
                } else {
                    $withoutDueDate++;
                }
            }

            $this->info("Reports with Due Date ID: {$withDueDate}");
            $this->info("  → Valid Due Date Links: {$withValidDueDate}");
            $this->warn('  → Invalid Due Date Links: '.($withDueDate - $withValidDueDate));
            $this->warn("Reports without Due Date ID: {$withoutDueDate}");

            // Check for duplicates
            $alreadyMigrated = ContractReport::whereNotNull('metadata->legacy_report_id')->count();
            $this->warn("Already Migrated Reports: {$alreadyMigrated}");

            $this->newLine();
            $this->info('Summary:');
            $this->info("  Total Reports: {$legacyReports->count()}");
            $this->info("  Will Migrate: {$withValidContract}");
            $this->warn("  Will Skip: {$withoutContract}");

            $this->newLine();
        }

        // Generate CSV if requested
        if ($this->option('csv')) {
            $this->info('Generating CSV report...');
            $csvPath = $this->generateCsvReport($legacyReports);
            $this->info("CSV report saved to: {$csvPath}");
            $this->newLine();
        }

        $this->newLine();

        $bar = $this->output->createProgressBar($legacyReports->count());
        $bar->start();

        $migrated = 0;
        $skippedNoContract = 0;
        $skippedDuplicate = 0;

        foreach ($legacyReports as $legacyReport) {
            // Check if already migrated
            $exists = ContractReport::where('metadata->legacy_report_id', $legacyReport->id)->first();
            if ($exists) {
                $skippedDuplicate++;
                $bar->advance();

                continue;
            }

            $contract = Contract::where('legacy_id', $legacyReport->contract_id)->first();

            if (! $contract) {
                $skippedNoContract++;
                $bar->advance();

                continue;
            }

            $dueDate = ContractReportDueDate::where('legacy_id', $legacyReport->due_date_id)->first();

            // Generate clean title and use full description as description
            $dueDateDescription = $dueDate?->description;
            $title = $this->generateCleanTitle($dueDateDescription, $contract);
            $description = $dueDateDescription ?? 'Migrated from legacy Contract Database system';

            if (! $this->option('dry-run')) {
                ContractReport::create([
                    'contract_id' => $contract->id,
                    'contract_report_due_date_id' => $dueDate?->id,
                    'title' => $title,
                    'description' => $description,
                    'submitted_date' => $legacyReport->created_at,
                    'status' => 'submitted',
                    'metadata' => [
                        'legacy_report_id' => $legacyReport->id,
                        'migrated_at' => now()->toISOString(),
                    ],
                    'created_at' => $legacyReport->created_at,
                    'updated_at' => $legacyReport->updated_at,
                ]);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('======================================');
        $this->info('  Migration Summary');
        $this->info('======================================');
        $this->info("Total Reports: {$legacyReports->count()}");
        $this->info("Migrated: {$migrated}");
        $this->warn("Skipped (no contract): {$skippedNoContract}");
        $this->warn("Skipped (duplicate): {$skippedDuplicate}");

        return 0;
    }

    /**
     * Generate clean, short title from description
     */
    protected function generateCleanTitle(?string $description, $contract): string
    {
        if (! $description) {
            return "Report for {$contract->title}";
        }

        // Strip HTML tags
        $cleanText = strip_tags($description);

        // Get first sentence or first 150 characters
        $firstSentence = preg_split('/[.!?]/', $cleanText, 2)[0];
        $cleanTitle = trim($firstSentence);

        // If still too long, truncate to 250 characters (safe for database)
        if (strlen($cleanTitle) > 250) {
            $cleanTitle = substr($cleanTitle, 0, 247).'...';
        }

        // If we got nothing useful, create default title
        if (empty($cleanTitle) || strlen($cleanTitle) < 5) {
            $cleanTitle = "Report for {$contract->title}";

            // If contract title would make it too long, truncate
            if (strlen($cleanTitle) > 250) {
                $cleanTitle = substr($cleanTitle, 0, 247).'...';
            }
        }

        return $cleanTitle;
    }

    /**
     * Generate CSV report of all reports with migration status
     */
    protected function generateCsvReport($legacyReports)
    {
        $filename = 'legacy_reports_'.date('Y-m-d_His').'.csv';
        $filepath = storage_path('app/'.$filename);

        $handle = fopen($filepath, 'w');

        // Write CSV header
        fputcsv($handle, [
            'Legacy Report ID',
            'Legacy Contract ID',
            'Contract Exists',
            'New Contract ID',
            'Contract Title',
            'Legacy Due Date ID',
            'Due Date Exists',
            'New Due Date ID',
            'Report Created At',
            'Report Updated At',
            'Migration Status',
            'Skip Reason',
        ]);

        // Write data rows
        foreach ($legacyReports as $report) {
            // Check if already migrated
            $existingReport = ContractReport::where('metadata->legacy_report_id', $report->id)->first();
            $isDuplicate = $existingReport !== null;

            // Check contract
            $contract = Contract::where('legacy_id', $report->contract_id)->first();
            $contractExists = $contract !== null;

            // Check due date
            $dueDate = null;
            $dueDateExists = false;
            if ($report->due_date_id) {
                $dueDate = ContractReportDueDate::where('legacy_id', $report->due_date_id)->first();
                $dueDateExists = $dueDate !== null;
            }

            // Determine migration status and reason
            $status = 'Will Migrate';
            $skipReason = '';

            if ($isDuplicate) {
                $status = 'Skip';
                $skipReason = 'Already migrated';
            } elseif (! $contractExists) {
                $status = 'Skip';
                $skipReason = 'Contract not found';
            }

            fputcsv($handle, [
                $report->id,
                $report->contract_id,
                $contractExists ? 'Yes' : 'No',
                $contract?->id ?? '',
                $contract?->title ?? 'N/A',
                $report->due_date_id ?? '',
                $report->due_date_id ? ($dueDateExists ? 'Yes' : 'No') : 'N/A',
                $dueDate?->id ?? '',
                $report->created_at,
                $report->updated_at,
                $status,
                $skipReason,
            ]);
        }

        fclose($handle);

        return $filepath;
    }
}
