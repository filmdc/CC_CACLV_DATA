<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyOrphanReports extends Command
{
    protected $signature = 'migrate:legacy-orphan-reports {--dry-run} {--csv : Generate CSV report}';

    protected $description = 'Migrate orphan reports (reports without due dates) from legacy system';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Orphan Reports Migration');
        $this->info('  (Reports without Due Dates)');
        $this->info('======================================');
        $this->newLine();

        // Get only reports WITHOUT due_date_id
        $orphanReports = DB::connection('legacy_contracts')
            ->table('reports')
            ->whereNull('due_date_id')
            ->get();

        $this->info("Found {$orphanReports->count()} orphan reports (no due date)");

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

            foreach ($orphanReports as $report) {
                $contract = Contract::where('legacy_id', $report->contract_id)->first();
                if ($contract) {
                    $withValidContract++;
                } else {
                    $withoutContract++;
                    if (!in_array($report->contract_id, $missingContractIds)) {
                        $missingContractIds[] = $report->contract_id;
                    }
                }
            }

            $this->info("Orphan Reports with Valid Contract: {$withValidContract}");
            $this->warn("Orphan Reports with Missing Contract (will skip): {$withoutContract}");
            if ($withoutContract > 0) {
                $this->line('  → Missing Contract IDs: ' . implode(', ', $missingContractIds));
            }

            // Check for duplicates
            $alreadyMigrated = ContractReport::where('metadata->is_orphan_report', true)->count();
            $this->warn("Already Migrated Orphan Reports: {$alreadyMigrated}");

            $this->newLine();
            $this->info('Summary:');
            $this->info("  Total Orphan Reports: {$orphanReports->count()}");
            $this->info("  Will Migrate: {$withValidContract}");
            $this->warn("  Will Skip: {$withoutContract}");

            $this->newLine();
        }

        // Generate CSV if requested
        if ($this->option('csv')) {
            $this->info('Generating CSV report...');
            $csvPath = $this->generateCsvReport($orphanReports);
            $this->info("CSV report saved to: {$csvPath}");
            $this->newLine();
        }

        if ($this->option('dry-run')) {
            return 0;
        }

        $this->newLine();

        $bar = $this->output->createProgressBar($orphanReports->count());
        $bar->start();

        $migrated = 0;
        $skippedNoContract = 0;
        $skippedDuplicate = 0;

        foreach ($orphanReports as $legacyReport) {
            // Check if already migrated
            $exists = ContractReport::where('metadata->legacy_report_id', $legacyReport->id)
                ->where('metadata->is_orphan_report', true)
                ->first();

            if ($exists) {
                $skippedDuplicate++;
                $bar->advance();
                continue;
            }

            $contract = Contract::where('legacy_id', $legacyReport->contract_id)->first();

            if (!$contract) {
                $skippedNoContract++;
                $bar->advance();
                continue;
            }

            // Generate title for orphan report
            $title = $this->generateOrphanReportTitle($contract, $legacyReport);

            ContractReport::create([
                'contract_id' => $contract->id,
                'contract_report_due_date_id' => null, // Explicitly null for orphans
                'title' => $title,
                'description' => 'Migrated from legacy Contract Database system (no due date assigned)',
                'submitted_date' => $legacyReport->created_at,
                'status' => 'submitted',
                'metadata' => [
                    'legacy_report_id' => $legacyReport->id,
                    'is_orphan_report' => true,
                    'migrated_at' => now()->toISOString(),
                    'migration_note' => 'Legacy report without due date - consider associating with a due date',
                ],
                'created_at' => $legacyReport->created_at,
                'updated_at' => $legacyReport->updated_at,
            ]);

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('======================================');
        $this->info('  Migration Summary');
        $this->info('======================================');
        $this->info("Total Orphan Reports: {$orphanReports->count()}");
        $this->info("Migrated: {$migrated}");
        $this->warn("Skipped (no contract): {$skippedNoContract}");
        $this->warn("Skipped (duplicate): {$skippedDuplicate}");
        $this->newLine();

        if ($migrated > 0) {
            $this->line('✓ These reports are now visible in the "General Reports" section of each contract.');
            $this->line('✓ Users with appropriate permissions can associate them with due dates.');
        }

        return 0;
    }

    /**
     * Generate appropriate title for orphan report
     */
    protected function generateOrphanReportTitle($contract, $legacyReport): string
    {
        $date = $legacyReport->created_at ?
            \Carbon\Carbon::parse($legacyReport->created_at)->format('M Y') :
            'Unknown Date';

        return "General Report - {$contract->title} ({$date})";
    }

    /**
     * Generate CSV report of all orphan reports with migration status
     */
    protected function generateCsvReport($orphanReports)
    {
        $filename = 'legacy_orphan_reports_' . date('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/' . $filename);

        $handle = fopen($filepath, 'w');

        // Write CSV header
        fputcsv($handle, [
            'Legacy Report ID',
            'Legacy Contract ID',
            'Contract Exists',
            'New Contract ID',
            'Contract Title',
            'Report Created At',
            'Report Updated At',
            'Already Migrated',
            'Migration Status',
            'Skip Reason',
        ]);

        // Write data rows
        foreach ($orphanReports as $report) {
            // Check if already migrated
            $existingReport = ContractReport::where('metadata->legacy_report_id', $report->id)
                ->where('metadata->is_orphan_report', true)
                ->first();
            $isDuplicate = $existingReport !== null;

            // Check contract
            $contract = Contract::where('legacy_id', $report->contract_id)->first();
            $contractExists = $contract !== null;

            // Determine migration status and reason
            $status = 'Will Migrate';
            $skipReason = '';

            if ($isDuplicate) {
                $status = 'Skip';
                $skipReason = 'Already migrated as orphan';
            } elseif (!$contractExists) {
                $status = 'Skip';
                $skipReason = 'Contract not found';
            }

            fputcsv($handle, [
                $report->id,
                $report->contract_id,
                $contractExists ? 'Yes' : 'No',
                $contract?->id ?? '',
                $contract?->title ?? 'N/A',
                $report->created_at,
                $report->updated_at,
                $isDuplicate ? 'Yes' : 'No',
                $status,
                $skipReason,
            ]);
        }

        fclose($handle);

        return $filepath;
    }
}
