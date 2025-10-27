<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanContractDataForMigration extends Command
{
    protected $signature = 'migrate:clean-contract-data {--dry-run : Show what would be deleted without actually deleting} {--force : Skip confirmation prompt}';

    protected $description = 'Clean all existing contract data to prepare for fresh legacy migration';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Contract Data Cleanup for Fresh Migration');
        $this->info('==============================================');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - Nothing will be deleted');
            $this->newLine();
        }

        // Show what will be deleted
        $this->info('📊 Current Data Counts:');
        $this->showDataCounts();
        $this->newLine();

        $this->warn('⚠️  WARNING: This will DELETE:');
        $this->warn('   ❌ All Contracts (377)');
        $this->warn('   ❌ All Funders (168)');
        $this->warn('   ❌ All Contract Report Due Dates (19)');
        $this->warn('   ❌ All Contract Reports (0)');
        $this->warn('   ❌ All Due Date Emails');
        $this->warn('   ❌ All Contract-related Media Files (3,469)');
        $this->warn('   ❌ All Contract-Program relationships');
        $this->newLine();

        $this->info('✅ This will PRESERVE:');
        $this->info('   ✓ Users and authentication');
        $this->info('   ✓ Roles and permissions');
        $this->info('   ✓ Programs');
        $this->info('   ✓ Participants and households');
        $this->info('   ✓ Services and enrollments');
        $this->info('   ✓ Work plans');
        $this->info('   ✓ Housing module data (properties, contractors, jobs)');
        $this->newLine();

        // Confirmation
        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Do you want to proceed with data deletion?', false)) {
                $this->info('Operation cancelled.');

                return 0;
            }

            if (! $this->confirm('Are you ABSOLUTELY SURE? This cannot be undone!', false)) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        // Create backup
        if (! $this->option('dry-run')) {
            $this->info('📦 Creating backup...');
            $this->createBackup();
            $this->info('✓ Backup created');
            $this->newLine();
        }

        // Perform cleanup
        if ($this->option('dry-run')) {
            $this->info('DRY RUN: Would delete all contract-related data');
        } else {
            $this->info('🗑️  Deleting contract data...');
            $this->deleteContractData();
            $this->info('✓ Cleanup completed');
        }

        $this->newLine();
        $this->info('📊 Final Data Counts:');
        $this->showDataCounts();
        $this->newLine();

        $this->info('==============================================');
        $this->info('✓ Cleanup Complete - Ready for Fresh Migration');
        $this->info('==============================================');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('  1. php artisan migrate:legacy-funders');
        $this->info('  2. php artisan migrate:legacy-contracts');
        $this->info('  3. Continue with remaining migration commands...');

        return 0;
    }

    protected function showDataCounts()
    {
        $counts = [
            'Contracts' => DB::table('contracts')->count(),
            'Funders' => DB::table('funders')->count(),
            'Due Date Emails' => DB::table('due_date_emails')->count(),
            'Report Due Dates' => DB::table('contract_report_due_dates')->count(),
            'Contract Reports' => DB::table('contract_reports')->count(),
            'Contract Media Files' => DB::table('media')
                ->whereIn('model_type', [
                    'App\\Models\\Contract',
                    'App\\Models\\ContractReportDueDate',
                    'App\\Models\\ContractReport',
                ])
                ->count(),
            'Contract-Program Pivot' => DB::table('contract_program')->count(),
        ];

        foreach ($counts as $label => $count) {
            $this->line("   {$label}: {$count}");
        }
    }

    protected function createBackup()
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupFile = storage_path("backups/pre_migration_backup_{$timestamp}.sql");

        // Create backups directory if it doesn't exist
        if (! file_exists(storage_path('backups'))) {
            mkdir(storage_path('backups'), 0755, true);
        }

        // Get database credentials
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        // Dump contract-related tables only
        $tables = implode(' ', [
            'contracts',
            'funders',
            'contract_report_due_dates',
            'contract_reports',
            'due_date_emails',
            'contract_program',
            'contract_report_due_date_emails',
            'media',
        ]);

        $command = sprintf(
            'mysqldump -h %s -u %s -p\'%s\' %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            $password,
            escapeshellarg($database),
            $tables,
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->warn('Backup creation failed (non-critical, continuing...)');
            $this->warn("You may want to manually backup: mysqldump {$database}");
        } else {
            $this->info("   Backup saved to: {$backupFile}");
        }
    }

    protected function deleteContractData()
    {
        DB::transaction(function () {
            // Delete in proper order to respect foreign keys

            $this->line('   → Deleting contract reports...');
            DB::table('contract_reports')->delete();

            $this->line('   → Deleting report due date email assignments...');
            DB::table('contract_report_due_date_emails')->delete();

            $this->line('   → Deleting contract report due dates...');
            DB::table('contract_report_due_dates')->delete();

            $this->line('   → Deleting due date emails...');
            DB::table('due_date_emails')->delete();

            $this->line('   → Deleting contract-program relationships...');
            DB::table('contract_program')->delete();

            $this->line('   → Deleting contract media files...');
            $contractMedia = DB::table('media')
                ->whereIn('model_type', [
                    'App\\Models\\Contract',
                    'App\\Models\\ContractReportDueDate',
                    'App\\Models\\ContractReport',
                ])
                ->get();

            foreach ($contractMedia as $media) {
                // Delete physical files
                $mediaPath = storage_path("app/public/{$media->id}");
                if (file_exists($mediaPath)) {
                    $this->deleteDirectory($mediaPath);
                }
            }

            DB::table('media')
                ->whereIn('model_type', [
                    'App\\Models\\Contract',
                    'App\\Models\\ContractReportDueDate',
                    'App\\Models\\ContractReport',
                ])
                ->delete();

            $this->line('   → Deleting contracts...');
            DB::table('contracts')->delete();

            $this->line('   → Deleting funders...');
            DB::table('funders')->delete();

            $this->line('   → Clearing validation log...');
            DB::table('migration_validation_log')->delete();
        });
    }

    protected function deleteDirectory($dir)
    {
        if (! file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
