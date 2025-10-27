<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyAuditLogs extends Command
{
    protected $signature = 'migrate:legacy-audit-logs {--dry-run}';
    protected $description = 'Archive legacy audit logs to read-only table';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Audit Logs Archive');
        $this->info('======================================');
        $this->newLine();

        $legacyAuditLogs = DB::connection('legacy_contracts')->table('audit_logs')->get();
        $this->info("Found {$legacyAuditLogs->count()} audit log entries");
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyAuditLogs->count());
        $bar->start();

        $migrated = 0;

        foreach ($legacyAuditLogs as $log) {
            if (!$this->option('dry-run')) {
                // Parse subject_type to link to new records
                $legacyContractId = null;
                $legacyReportId = null;

                if (preg_match('/Contract#(\d+)/', $log->subject_type, $matches)) {
                    $legacyContractId = (int)$matches[1];
                } elseif (preg_match('/Report#(\d+)/', $log->subject_type, $matches)) {
                    $legacyReportId = (int)$matches[1];
                }

                DB::table('legacy_audit_logs')->insert([
                    'id' => $log->id,
                    'description' => $log->description,
                    'subject_id' => $log->subject_id,
                    'subject_type' => $log->subject_type,
                    'user_id' => $log->user_id,
                    'properties' => $log->properties,
                    'host' => $log->host,
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                    'legacy_contract_id' => $legacyContractId
                        ? Contract::where('legacy_id', $legacyContractId)->value('id')
                        : null,
                    'legacy_report_id' => $legacyReportId
                        ? ContractReport::whereJsonContains('metadata->legacy_report_id', (string)$legacyReportId)->value('id')
                        : null,
                ]);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Archived: {$migrated} audit log entries");
        return 0;
    }
}