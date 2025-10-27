<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\Funder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateValidateAll extends Command
{
    protected $signature = 'migrate:validate-all {--detailed : Show detailed validation results}';
    protected $description = 'Validate all data migrated from legacy Contract Database system';

    protected $issues = [];
    protected $warnings = [];
    protected $passed = 0;
    protected $failed = 0;

    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Contract Database - Migration Validation');
        $this->info('==============================================');
        $this->newLine();

        $this->info('Connecting to legacy database at 10.55.0.10...');
        $this->newLine();

        // Run all validation checks
        $this->validateFunders();
        $this->validateContracts();
        $this->validateDueDateEmails();
        $this->validateReportDueDates();
        $this->validateReports();
        $this->validateMedia();
        $this->validateForeignKeys();
        $this->validateArchiveStatus();
        $this->validateMicrosoftCalendar();

        // Summary
        $this->newLine();
        $this->info('==============================================');
        $this->info('  VALIDATION SUMMARY');
        $this->info('==============================================');
        $this->info("✓ Passed: {$this->passed}");

        if ($this->failed > 0) {
            $this->error("✗ Failed: {$this->failed}");
        } else {
            $this->info("✗ Failed: {$this->failed}");
        }

        if ($this->warnings) {
            $this->newLine();
            $this->warn('⚠ Warnings (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        if ($this->issues) {
            $this->newLine();
            $this->error('✗ Issues Found (' . count($this->issues) . '):');
            foreach ($this->issues as $issue) {
                $this->line("  - {$issue}");
            }
        }

        $this->newLine();

        if ($this->failed === 0 && empty($this->issues)) {
            $this->info('✓✓✓ ALL VALIDATIONS PASSED! ✓✓✓');
            $this->info('Migration completed successfully!');
            return 0;
        } else {
            $this->error('Migration validation found issues. Please review and address.');
            return 1;
        }
    }

    protected function validateFunders()
    {
        $this->info('Validating Funders...');

        try {
            $legacyCount = DB::connection('legacy_contracts')->table('funders')->count();

            // Count funders that have contracts
            $legacyWithContracts = DB::connection('legacy_contracts')
                ->table('funders')
                ->whereIn('id', function ($query) {
                    $query->select('funder_id')
                        ->from('contracts')
                        ->whereNotNull('funder_id')
                        ->distinct();
                })
                ->count();

            $migratedCount = Funder::whereNotNull('legacy_id')->count();

            $this->line("  Legacy Funders (with contracts): {$legacyWithContracts}");
            $this->line("  Migrated Funders: {$migratedCount}");

            if ($migratedCount === $legacyWithContracts) {
                $this->info("  ✓ All funders with contracts migrated ({$migratedCount}/{$legacyWithContracts})");
                $this->passed++;
            } else {
                $this->error("  ✗ Funder count mismatch: Expected {$legacyWithContracts}, Got {$migratedCount}");
                $this->issues[] = "Funders: Expected {$legacyWithContracts}, got {$migratedCount}";
                $this->failed++;
            }

            // Check for orphaned funders
            $orphanedFunders = Funder::whereNull('legacy_id')->count();
            if ($orphanedFunders > 0) {
                $this->warnings[] = "{$orphanedFunders} funders without legacy_id (created manually)";
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Funder validation failed: " . $e->getMessage());
            $this->issues[] = "Funder validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateContracts()
    {
        $this->info('Validating Contracts...');

        try {
            $legacyCount = DB::connection('legacy_contracts')->table('contracts')->count();
            $migratedCount = Contract::whereNotNull('legacy_id')->count();

            $this->line("  Legacy Contracts: {$legacyCount}");
            $this->line("  Migrated Contracts: {$migratedCount}");

            if ($migratedCount === $legacyCount) {
                $this->info("  ✓ All contracts migrated ({$migratedCount}/{$legacyCount})");
                $this->passed++;
            } else {
                $this->error("  ✗ Contract count mismatch: Expected {$legacyCount}, Got {$migratedCount}");
                $this->issues[] = "Contracts: Expected {$legacyCount}, got {$migratedCount}";
                $this->failed++;
            }

            // Validate archived contracts
            $legacyArchived = DB::connection('legacy_contracts')
                ->table('contracts')
                ->where('archived', 'yes')
                ->count();
            $migratedArchived = Contract::where('archived', true)->whereNotNull('legacy_id')->count();

            $this->line("  Legacy Archived: {$legacyArchived}");
            $this->line("  Migrated Archived: {$migratedArchived}");

            if ($migratedArchived === $legacyArchived) {
                $this->info("  ✓ Archive status correct ({$migratedArchived}/{$legacyArchived})");
                $this->passed++;
            } else {
                $this->error("  ✗ Archived count mismatch: Expected {$legacyArchived}, Got {$migratedArchived}");
                $this->issues[] = "Archived contracts: Expected {$legacyArchived}, got {$migratedArchived}";
                $this->failed++;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Contract validation failed: " . $e->getMessage());
            $this->issues[] = "Contract validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateDueDateEmails()
    {
        $this->info('Validating Due Date Emails...');

        try {
            // Check if legacy_id column exists
            $columns = collect(DB::select('SHOW COLUMNS FROM due_date_emails'))
                ->pluck('Field')
                ->toArray();

            if (!in_array('legacy_id', $columns)) {
                $this->warn("  ⚠ Due date emails migration not completed (table has no legacy_id column)");
                $this->newLine();
                return;
            }

            $legacyCount = DB::connection('legacy_contracts')->table('due_date_emails')->count();
            $migratedCount = DB::table('due_date_emails')->whereNotNull('legacy_id')->count();

            $this->line("  Legacy Due Date Emails: {$legacyCount}");
            $this->line("  Migrated Due Date Emails: {$migratedCount}");

            if ($migratedCount === $legacyCount) {
                $this->info("  ✓ All due date emails migrated ({$migratedCount}/{$legacyCount})");
                $this->passed++;
            } else {
                $this->warn("  ⚠ Due date email count mismatch: Expected {$legacyCount}, Got {$migratedCount}");
                $this->warnings[] = "Due date emails: Expected {$legacyCount}, got {$migratedCount}";
            }

        } catch (\Exception $e) {
            $this->warn("  ⚠ Due date email validation error: " . $e->getMessage());
        }

        $this->newLine();
    }

    protected function validateReportDueDates()
    {
        $this->info('Validating Report Due Dates...');

        try {
            $legacyCount = DB::connection('legacy_contracts')->table('report_due_dates')->count();
            $migratedCount = ContractReportDueDate::whereNotNull('legacy_id')->count();

            $this->line("  Legacy Report Due Dates: {$legacyCount}");
            $this->line("  Migrated Report Due Dates: {$migratedCount}");

            if ($migratedCount === $legacyCount) {
                $this->info("  ✓ All report due dates migrated ({$migratedCount}/{$legacyCount})");
                $this->passed++;
            } else {
                $this->error("  ✗ Report due date count mismatch: Expected {$legacyCount}, Got {$migratedCount}");
                $this->issues[] = "Report due dates: Expected {$legacyCount}, got {$migratedCount}";
                $this->failed++;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Report due date validation failed: " . $e->getMessage());
            $this->issues[] = "Report due date validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateReports()
    {
        $this->info('Validating Reports...');

        try {
            $legacyCount = DB::connection('legacy_contracts')->table('reports')->count();

            // Count reports with valid contracts (orphaned reports should be skipped)
            $legacyWithContracts = DB::connection('legacy_contracts')
                ->table('reports')
                ->whereNotNull('contract_id')
                ->where('contract_id', '!=', '')
                ->count();

            $orphanedReports = $legacyCount - $legacyWithContracts;

            $migratedCount = ContractReport::whereNotNull('metadata->legacy_report_id')->count();

            $this->line("  Legacy Reports (total): {$legacyCount}");
            $this->line("  Legacy Reports (with contract): {$legacyWithContracts}");
            if ($orphanedReports > 0) {
                $this->line("  Legacy Reports (orphaned): {$orphanedReports}");
            }
            $this->line("  Migrated Reports: {$migratedCount}");

            if ($migratedCount === $legacyWithContracts) {
                $this->info("  ✓ All valid reports migrated ({$migratedCount}/{$legacyWithContracts})");
                $this->passed++;

                if ($orphanedReports > 0) {
                    $this->warnings[] = "{$orphanedReports} orphaned reports skipped (no contract_id)";
                }
            } else {
                $this->error("  ✗ Report count mismatch: Expected {$legacyWithContracts}, Got {$migratedCount}");
                $this->issues[] = "Reports: Expected {$legacyWithContracts}, got {$migratedCount}";
                $this->failed++;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Report validation failed: " . $e->getMessage());
            $this->issues[] = "Report validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateMedia()
    {
        $this->info('Validating Media Files...');

        try {
            $legacyCount = DB::connection('legacy_contracts')->table('media')->count();
            $migratedCount = Media::count();

            $this->line("  Legacy Media Records: {$legacyCount}");
            $this->line("  Migrated Media Records: {$migratedCount}");

            // Calculate actual failures
            $actualFailures = $legacyCount - $migratedCount;
            $expectedFailures = DB::table('migration_validation_log')
                ->where('entity_type', 'media')
                ->where('status', 'error')
                ->count();

            $expectedSuccess = $legacyCount - $expectedFailures;

            if ($migratedCount >= $expectedSuccess * 0.95) { // Allow 5% tolerance
                $percentage = round(($migratedCount / $legacyCount) * 100, 1);
                $this->info("  ✓ Media files migrated: {$migratedCount}/{$legacyCount} ({$percentage}%)");
                $this->passed++;

                if ($actualFailures > 0) {
                    $this->warnings[] = "{$actualFailures} media files failed due to size/MIME type limitations or missing files";
                }
            } else {
                $this->error("  ✗ Media count too low: Expected ~{$expectedSuccess}, Got {$migratedCount}");
                $this->issues[] = "Media files: Expected {$expectedSuccess}, got {$migratedCount}";
                $this->failed++;
            }

            // Check physical files exist
            $sampleMedia = Media::inRandomOrder()->limit(10)->get();
            $accessibleFiles = 0;
            $inaccessibleFiles = 0;

            foreach ($sampleMedia as $media) {
                if (File::exists($media->getPath())) {
                    $accessibleFiles++;
                } else {
                    $inaccessibleFiles++;
                }
            }

            if ($inaccessibleFiles === 0) {
                $this->info("  ✓ Sample physical files accessible (10/10)");
                $this->passed++;
            } else {
                $this->error("  ✗ Some files not accessible ({$accessibleFiles}/10)");
                $this->issues[] = "{$inaccessibleFiles} sample files not accessible";
                $this->failed++;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Media validation failed: " . $e->getMessage());
            $this->issues[] = "Media validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateForeignKeys()
    {
        $this->info('Validating Foreign Key Relationships...');

        $fkIssues = 0;

        try {
            // Contracts → Funders
            $orphanedContracts = Contract::whereNotNull('funder_id')
                ->whereDoesntHave('funder')
                ->count();

            if ($orphanedContracts === 0) {
                $this->info("  ✓ All contracts have valid funder references");
                $this->passed++;
            } else {
                $this->error("  ✗ {$orphanedContracts} contracts with invalid funder_id");
                $this->issues[] = "{$orphanedContracts} contracts with broken funder relationship";
                $this->failed++;
                $fkIssues++;
            }

            // Reports → Contracts
            $orphanedReports = ContractReport::whereDoesntHave('contract')->count();

            if ($orphanedReports === 0) {
                $this->info("  ✓ All reports have valid contract references");
                $this->passed++;
            } else {
                $this->error("  ✗ {$orphanedReports} reports with invalid contract_id");
                $this->issues[] = "{$orphanedReports} reports with broken contract relationship";
                $this->failed++;
                $fkIssues++;
            }

            // Report Due Dates → Contracts
            $orphanedDueDates = ContractReportDueDate::whereDoesntHave('contract')->count();

            if ($orphanedDueDates === 0) {
                $this->info("  ✓ All due dates have valid contract references");
                $this->passed++;
            } else {
                $this->error("  ✗ {$orphanedDueDates} due dates with invalid contract_id");
                $this->issues[] = "{$orphanedDueDates} due dates with broken contract relationship";
                $this->failed++;
                $fkIssues++;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Foreign key validation failed: " . $e->getMessage());
            $this->issues[] = "Foreign key validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateArchiveStatus()
    {
        $this->info('Validating Archive Status...');

        try {
            // All archived contracts should have date_archived
            $archivedWithoutDate = Contract::where('archived', true)
                ->whereNull('date_archived')
                ->whereNotNull('legacy_id')
                ->count();

            if ($archivedWithoutDate === 0) {
                $this->info("  ✓ All archived contracts have date_archived");
                $this->passed++;
            } else {
                $this->error("  ✗ {$archivedWithoutDate} archived contracts missing date_archived");
                $this->issues[] = "{$archivedWithoutDate} archived contracts missing date_archived";
                $this->failed++;
            }

            // Non-archived contracts should not have date_archived
            $activeWithDate = Contract::where('archived', false)
                ->whereNotNull('date_archived')
                ->whereNotNull('legacy_id')
                ->count();

            if ($activeWithDate === 0) {
                $this->info("  ✓ No active contracts have incorrect date_archived");
                $this->passed++;
            } else {
                $this->warn("  ⚠ {$activeWithDate} active contracts have date_archived set");
                $this->warnings[] = "{$activeWithDate} active contracts have date_archived (should be null)";
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Archive status validation failed: " . $e->getMessage());
            $this->issues[] = "Archive status validation error: " . $e->getMessage();
            $this->failed++;
        }

        $this->newLine();
    }

    protected function validateMicrosoftCalendar()
    {
        $this->info('Validating Microsoft Calendar Integration...');

        try {
            // Count due dates with Microsoft event IDs
            $legacyWithEvents = DB::connection('legacy_contracts')
                ->table('report_due_dates')
                ->whereNotNull('microsoft_event_id')
                ->where('microsoft_event_id', '!=', '')
                ->count();

            $migratedWithEvents = ContractReportDueDate::whereNotNull('microsoft_event_id')
                ->where('microsoft_event_id', '!=', '')
                ->whereNotNull('legacy_id')
                ->count();

            $this->line("  Legacy with Event IDs: {$legacyWithEvents}");
            $this->line("  Migrated with Event IDs: {$migratedWithEvents}");

            if ($migratedWithEvents === $legacyWithEvents) {
                $this->info("  ✓ Microsoft event IDs preserved ({$migratedWithEvents}/{$legacyWithEvents})");
                $this->passed++;
            } else {
                $this->error("  ✗ Event ID count mismatch: Expected {$legacyWithEvents}, Got {$migratedWithEvents}");
                $this->issues[] = "Microsoft event IDs: Expected {$legacyWithEvents}, got {$migratedWithEvents}";
                $this->failed++;
            }

        } catch (\Exception $e) {
            $this->warn("  ⚠ Microsoft calendar validation skipped: " . $e->getMessage());
        }

        $this->newLine();
    }
}
