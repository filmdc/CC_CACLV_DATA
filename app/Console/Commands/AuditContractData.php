<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\ContractReport;
use App\Models\Funder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditContractData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:audit
                            {--export-csv : Export results to CSV files}
                            {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit contract data for legacy migration issues and data quality problems';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('Contract Database Audit Report');
        $this->info('========================================');
        $this->newLine();

        // Section 1: Contract Number Issues
        $this->auditContractNumbers();

        // Section 2: Program Relationship Analysis
        $this->auditProgramRelationships();

        // Section 3: Field Duplication Analysis
        $this->auditFieldDuplication();

        // Section 4: Media Collection Analysis
        $this->auditMediaCollections();

        // Section 5: Archive Status Analysis
        $this->auditArchiveStatus();

        // Section 6: Foreign Key Integrity
        $this->auditForeignKeys();

        // Section 7: Summary Statistics
        $this->displaySummaryStatistics();

        $this->newLine();
        $this->info('Audit complete!');

        if ($this->option('export-csv')) {
            $this->exportToCsv();
        }

        return Command::SUCCESS;
    }

    /**
     * Audit contract number issues
     */
    protected function auditContractNumbers()
    {
        $this->info('1. CONTRACT NUMBER ANALYSIS');
        $this->info('----------------------------');

        // Total contracts
        $total = Contract::count();
        $this->line("Total contracts: {$total}");

        // Null contract numbers
        $nullNumbers = Contract::whereNull('contract_number')->count();
        $this->line("Contracts with NULL contract_number: {$nullNumbers}");

        // Numeric-only contract numbers (likely IDs from legacy)
        $numericOnly = Contract::whereNotNull('contract_number')
            ->whereRaw('contract_number REGEXP \'^[0-9]+$\'')
            ->get();

        $this->warn("Contracts with numeric-only contract_number (likely legacy IDs): {$numericOnly->count()}");

        if ($this->option('detailed') && $numericOnly->count() > 0) {
            $this->table(
                ['ID', 'Title', 'Contract Number', 'Legacy ID'],
                $numericOnly->map(fn($c) => [
                    $c->id,
                    substr($c->title, 0, 50),
                    $c->contract_number,
                    $c->legacy_id ?? 'N/A'
                ])
            );
        }

        // Contract numbers that match their own ID
        $idAsNumber = Contract::whereNotNull('contract_number')
            ->whereRaw('CAST(contract_number AS UNSIGNED) = id')
            ->get();

        if ($idAsNumber->count() > 0) {
            $this->error("⚠️  Contracts where contract_number equals ID: {$idAsNumber->count()}");
        }

        // Contract numbers that match legacy_id
        $legacyIdAsNumber = Contract::whereNotNull('contract_number')
            ->whereNotNull('legacy_id')
            ->whereRaw('CAST(contract_number AS UNSIGNED) = legacy_id')
            ->get();

        if ($legacyIdAsNumber->count() > 0) {
            $this->error("⚠️  Contracts where contract_number equals legacy_id: {$legacyIdAsNumber->count()}");

            if ($this->option('detailed')) {
                $this->table(
                    ['ID', 'Title', 'Contract Number', 'Legacy ID'],
                    $legacyIdAsNumber->map(fn($c) => [
                        $c->id,
                        substr($c->title, 0, 40),
                        $c->contract_number,
                        $c->legacy_id
                    ])
                );
            }
        }

        $this->newLine();
    }

    /**
     * Audit program relationships
     */
    protected function auditProgramRelationships()
    {
        $this->info('2. PROGRAM RELATIONSHIP ANALYSIS');
        $this->info('---------------------------------');

        // Contracts with direct program_id
        $withProgramId = Contract::whereNotNull('program_id')->count();
        $this->line("Contracts with direct program_id: {$withProgramId}");

        // Contracts with pivot table programs
        $withPivotPrograms = DB::table('contract_program')
            ->select('contract_id')
            ->distinct()
            ->count();
        $this->line("Contracts with pivot table programs: {$withPivotPrograms}");

        // Contracts with BOTH
        $withBoth = Contract::whereNotNull('program_id')
            ->whereHas('programs')
            ->count();

        if ($withBoth > 0) {
            $this->warn("⚠️  Contracts with BOTH program_id AND pivot programs: {$withBoth}");
        }

        // Contracts with multiple programs (pivot)
        $multiplePrograms = DB::table('contract_program')
            ->select('contract_id', DB::raw('COUNT(*) as program_count'))
            ->groupBy('contract_id')
            ->having('program_count', '>', 1)
            ->get();

        $this->line("Contracts with multiple programs (pivot): {$multiplePrograms->count()}");

        if ($this->option('detailed') && $multiplePrograms->count() > 0) {
            $this->info('Contracts with multiple programs:');
            foreach ($multiplePrograms as $item) {
                $contract = Contract::find($item->contract_id);
                if ($contract) {
                    $this->line("  - {$contract->title}: {$item->program_count} programs");
                }
            }
        }

        // Contracts with NO program relationship
        $noPrograms = Contract::whereNull('program_id')
            ->whereDoesntHave('programs')
            ->count();

        if ($noPrograms > 0) {
            $this->error("⚠️  Contracts with NO program assignment: {$noPrograms}");
        }

        $this->newLine();
    }

    /**
     * Audit field duplication (fund_code vs grant_code)
     */
    protected function auditFieldDuplication()
    {
        $this->info('3. FIELD DUPLICATION ANALYSIS');
        $this->info('-----------------------------');

        // Check if fund_code column exists
        $hasFundCode = DB::getSchemaBuilder()->hasColumn('contracts', 'fund_code');

        if (!$hasFundCode) {
            $this->line('✓ fund_code column does not exist (already cleaned up)');
            $this->newLine();
            return;
        }

        $withGrantCode = Contract::whereNotNull('grant_code')->count();
        $withFundCode = Contract::whereNotNull('fund_code')->count();
        $withBoth = Contract::whereNotNull('grant_code')
            ->whereNotNull('fund_code')
            ->count();

        $this->line("Contracts with grant_code: {$withGrantCode}");
        $this->line("Contracts with fund_code: {$withFundCode}");

        if ($withBoth > 0) {
            $this->warn("⚠️  Contracts with BOTH fund_code AND grant_code: {$withBoth}");

            // Check if values differ
            $differing = Contract::whereNotNull('grant_code')
                ->whereNotNull('fund_code')
                ->whereRaw('grant_code != fund_code')
                ->get();

            if ($differing->count() > 0) {
                $this->error("⚠️  Contracts where fund_code != grant_code: {$differing->count()}");

                if ($this->option('detailed')) {
                    $this->table(
                        ['ID', 'Title', 'grant_code', 'fund_code'],
                        $differing->map(fn($c) => [
                            $c->id,
                            substr($c->title, 0, 30),
                            $c->grant_code,
                            $c->fund_code ?? ''
                        ])
                    );
                }
            }
        }

        $this->newLine();
    }

    /**
     * Audit media collections
     */
    protected function auditMediaCollections()
    {
        $this->info('4. MEDIA COLLECTION ANALYSIS');
        $this->info('----------------------------');

        // Get media stats by model and collection
        $mediaStats = DB::table('media')
            ->select('model_type', 'collection_name', DB::raw('COUNT(*) as count'))
            ->groupBy('model_type', 'collection_name')
            ->get();

        if ($mediaStats->count() === 0) {
            $this->line('No media files found in database');
            $this->newLine();
            return;
        }

        $this->table(
            ['Model', 'Collection', 'File Count'],
            $mediaStats->map(fn($s) => [
                class_basename($s->model_type),
                $s->collection_name,
                $s->count
            ])
        );

        // Check for collection name collisions
        $collisions = $mediaStats->groupBy('collection_name')
            ->filter(fn($group) => $group->count() > 1);

        if ($collisions->count() > 0) {
            $this->warn('⚠️  Collection name collisions detected:');
            foreach ($collisions as $collectionName => $models) {
                $modelNames = $models->pluck('model_type')->map(fn($m) => class_basename($m))->join(', ');
                $this->line("  - '{$collectionName}' used by: {$modelNames}");
            }
        }

        $this->newLine();
    }

    /**
     * Audit archive status
     */
    protected function auditArchiveStatus()
    {
        $this->info('5. ARCHIVE STATUS ANALYSIS');
        $this->info('--------------------------');

        $archived = Contract::where('archived', true)->count();
        $active = Contract::where('archived', false)->count();

        $this->line("Archived contracts: {$archived}");
        $this->line("Active contracts: {$active}");

        // Archived with no date
        $archivedNoDate = Contract::where('archived', true)
            ->whereNull('date_archived')
            ->count();

        if ($archivedNoDate > 0) {
            $this->warn("⚠️  Archived contracts without date_archived: {$archivedNoDate}");
        }

        // Not archived but has date
        $notArchivedWithDate = Contract::where('archived', false)
            ->whereNotNull('date_archived')
            ->count();

        if ($notArchivedWithDate > 0) {
            $this->warn("⚠️  Non-archived contracts with date_archived set: {$notArchivedWithDate}");
        }

        $this->newLine();
    }

    /**
     * Audit foreign key integrity
     */
    protected function auditForeignKeys()
    {
        $this->info('6. FOREIGN KEY INTEGRITY');
        $this->info('------------------------');

        // Contracts with invalid program_id
        $invalidProgramId = Contract::whereNotNull('program_id')
            ->whereDoesntHave('program')
            ->count();

        if ($invalidProgramId > 0) {
            $this->error("⚠️  Contracts with invalid program_id: {$invalidProgramId}");
        } else {
            $this->line('✓ All program_id references are valid');
        }

        // Contracts with invalid funder_id
        $invalidFunderId = Contract::whereNotNull('funder_id')
            ->whereDoesntHave('funder')
            ->count();

        if ($invalidFunderId > 0) {
            $this->error("⚠️  Contracts with invalid funder_id: {$invalidFunderId}");
        } else {
            $this->line('✓ All funder_id references are valid');
        }

        // Orphaned pivot table entries
        $orphanedPivot = DB::table('contract_program')
            ->leftJoin('contracts', 'contract_program.contract_id', '=', 'contracts.id')
            ->whereNull('contracts.id')
            ->count();

        if ($orphanedPivot > 0) {
            $this->error("⚠️  Orphaned contract_program pivot entries: {$orphanedPivot}");
        } else {
            $this->line('✓ No orphaned pivot table entries');
        }

        $this->newLine();
    }

    /**
     * Display summary statistics
     */
    protected function displaySummaryStatistics()
    {
        $this->info('7. SUMMARY STATISTICS');
        $this->info('---------------------');

        $this->table(
            ['Entity', 'Total', 'Active', 'Soft Deleted'],
            [
                [
                    'Contracts',
                    Contract::withTrashed()->count(),
                    Contract::count(),
                    Contract::onlyTrashed()->count()
                ],
                [
                    'Funders',
                    Funder::withTrashed()->count(),
                    Funder::count(),
                    Funder::onlyTrashed()->count()
                ],
                [
                    'Report Due Dates',
                    ContractReportDueDate::withTrashed()->count(),
                    ContractReportDueDate::count(),
                    ContractReportDueDate::onlyTrashed()->count()
                ],
                [
                    'Contract Reports',
                    ContractReport::withTrashed()->count(),
                    ContractReport::count(),
                    ContractReport::onlyTrashed()->count()
                ],
            ]
        );
    }

    /**
     * Export audit results to CSV files
     */
    protected function exportToCsv()
    {
        $this->info('Exporting audit results to CSV...');

        $exportPath = storage_path('app/audit-exports');

        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0755, true);
        }

        // Export contracts with numeric-only contract_number
        $numericOnly = Contract::whereNotNull('contract_number')
            ->whereRaw('contract_number REGEXP \'^[0-9]+$\'')
            ->get();

        if ($numericOnly->count() > 0) {
            $filename = $exportPath . '/contracts_numeric_numbers_' . date('Y-m-d_His') . '.csv';
            $file = fopen($filename, 'w');
            fputcsv($file, ['ID', 'Title', 'Contract Number', 'Legacy ID', 'Created At']);

            foreach ($numericOnly as $contract) {
                fputcsv($file, [
                    $contract->id,
                    $contract->title,
                    $contract->contract_number,
                    $contract->legacy_id ?? '',
                    $contract->created_at
                ]);
            }

            fclose($file);
            $this->line("✓ Exported to: {$filename}");
        }

        $this->info('Export complete!');
    }
}
