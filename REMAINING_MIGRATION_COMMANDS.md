# Remaining Migration Commands - Complete Implementation

**Status:** Ready to create
**Commands Remaining:** 6

---

## ✅ COMPLETED COMMANDS
1. ✅ `migrate:clean-contract-data` - Cleanup botched data
2. ✅ `migrate:legacy-funders` - Migrate 167 funders (skip 3 unused)
3. ✅ `migrate:legacy-contracts` - Migrate 452 contracts
4. ✅ `migrate:legacy-programs` - Auto-map programs by name

---

## 📝 COMMANDS TO CREATE

### 1. Due Date Emails Migration

**Create:**
```bash
# Already created, just need implementation
```

**File:** `app/Console/Commands/MigrateLegacyDueDateEmails.php`

**Complete Code:**
```php
<?php

namespace App\Console\Commands;

use App\Models\DueDateEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyDueDateEmails extends Command
{
    protected $signature = 'migrate:legacy-due-date-emails {--dry-run}';
    protected $description = 'Migrate due date emails with user mapping';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Due Date Emails Migration');
        $this->info('======================================');
        $this->newLine();

        $legacyEmails = DB::connection('legacy_contracts')->table('due_date_emails')->get();
        $this->info("Found {$legacyEmails->count()} due date emails");
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyEmails->count());
        $bar->start();

        $migrated = 0;
        $withUser = 0;
        $withoutUser = 0;

        foreach ($legacyEmails as $legacyEmail) {
            if (DueDateEmail::where('email', $legacyEmail->email)->exists()) {
                $bar->advance();
                continue;
            }

            // Try to find matching user
            $user = User::where('email', $legacyEmail->email)->first();

            // Generate display name
            $name = $this->generateDisplayName($legacyEmail->email, $user);

            if (!$this->option('dry-run')) {
                DueDateEmail::create([
                    'email' => $legacyEmail->email,
                    'user_id' => $user?->id,
                    'name' => $name,
                    'active' => true,
                    'preferences' => [
                        'reminder_intervals' => [30, 15, 7, 3, 1],
                        'receive_overdue_notifications' => true,
                        'receive_completion_notifications' => false,
                    ],
                    'created_at' => $legacyEmail->created_at,
                    'updated_at' => $legacyEmail->updated_at,
                ]);
            }

            $migrated++;
            if ($user) {
                $withUser++;
            } else {
                $withoutUser++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migrated: {$migrated}");
        $this->info("Linked to users: {$withUser}");
        $this->warn("Distribution/role emails (no user): {$withoutUser}");

        return 0;
    }

    protected function generateDisplayName(string $email, ?User $user): string
    {
        if ($user) {
            return $user->name ?? $email;
        }

        // Handle planner-* emails
        if (preg_match('/planner-(.+)@/', $email, $matches)) {
            return strtoupper($matches[1]) . ' Planner';
        }

        // Handle _AllPlanningStaff
        if ($email === '_AllPlanningStaff@caclv.org') {
            return 'All Planning Staff';
        }

        return $email;
    }
}
```

---

### 2. Report Due Dates Migration

**File:** `app/Console/Commands/MigrateLegacyReportDueDates.php`

**Complete Code:**
```php
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
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyDueDates->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;

        foreach ($legacyDueDates as $legacyDueDate) {
            $contract = Contract::where('legacy_id', $legacyDueDate->contract_id)->first();

            if (!$contract) {
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
            $status = match($legacyDueDate->status) {
                'completed' => 'completed',
                'incomplete' => now()->gt($legacyDueDate->due_date) ? 'overdue' : 'pending',
                default => 'pending'
            };

            // Infer report type from description
            $reportType = $this->inferReportType($legacyDueDate->description);

            if (!$this->option('dry-run')) {
                ContractReportDueDate::create([
                    'contract_id' => $contract->id,
                    'due_date' => $legacyDueDate->due_date,
                    'status' => $status,
                    'description' => $legacyDueDate->description,
                    'report_name' => $legacyDueDate->description ?? 'Report ' . $legacyDueDate->id,
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
        if (!$description) return 'quarterly';

        $desc = strtolower($description);

        if (str_contains($desc, 'monthly')) return 'monthly';
        if (str_contains($desc, 'quarterly') || str_contains($desc, 'quarter')) return 'quarterly';
        if (str_contains($desc, 'annual') || str_contains($desc, 'yearly')) return 'annual';
        if (str_contains($desc, 'final')) return 'final';
        if (str_contains($desc, 'semi-annual') || str_contains($desc, 'semi annual')) return 'semi_annual';

        return 'quarterly';
    }
}
```

---

### 3. Due Date Email Assignments (Pivot)

**Create:**
```bash
php artisan make:command MigrateLegacyDueDateEmailAssignments
```

**File:** `app/Console/Commands/MigrateLegacyDueDateEmailAssignments.php`

**Complete Code:**
```php
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
```

---

### 4. Reports Migration

**File:** `app/Console/Commands/MigrateLegacyReports.php`

**Complete Code:**
```php
<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyReports extends Command
{
    protected $signature = 'migrate:legacy-reports {--dry-run}';
    protected $description = 'Migrate reports from legacy system';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Reports Migration');
        $this->info('======================================');
        $this->newLine();

        $legacyReports = DB::connection('legacy_contracts')->table('reports')->get();
        $this->info("Found {$legacyReports->count()} reports");
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyReports->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;

        foreach ($legacyReports as $legacyReport) {
            $contract = Contract::where('legacy_id', $legacyReport->contract_id)->first();

            if (!$contract) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $dueDate = ContractReportDueDate::where('legacy_id', $legacyReport->due_date_id)->first();

            // Use due date description as title (per user decision)
            $title = $dueDate?->description ?? "Report for {$contract->title}";

            if (!$this->option('dry-run')) {
                ContractReport::create([
                    'contract_id' => $contract->id,
                    'contract_report_due_date_id' => $dueDate?->id,
                    'title' => $title,
                    'description' => 'Migrated from legacy Contract Database system',
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

        $this->info("Migrated: {$migrated} | Skipped: {$skipped}");
        return 0;
    }
}
```

---

### 5. Media Files Migration

**Create:**
```bash
php artisan make:command MigrateLegacyMedia
```

**File:** `app/Console/Commands/MigrateLegacyMedia.php`

**This is the most complex - handles 4 types of media with file transfer**

**Complete Code:** See next page

---

### 6. Audit Logs Archive

**Create:**
```bash
php artisan make:command MigrateLegacyAuditLogs
```

**File:** `app/Console/Commands/MigrateLegacyAuditLogs.php`

**Complete Code:**
```php
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
```

---

### 7. Validation Command

**Create:**
```bash
php artisan make:command MigrateValidateAll
```

**File:** `app/Console/Commands/MigrateValidateAll.php`

**Complete Code:** [See validation implementation in separate section]

---

## 🚀 QUICK CREATE SCRIPT

To quickly create all remaining commands, save each code block to its respective file:

```bash
# Create missing commands
php artisan make:command MigrateLegacyDueDateEmailAssignments
php artisan make:command MigrateLegacyMedia
php artisan make:command MigrateLegacyAuditLogs
php artisan make:command MigrateValidateAll

# Then copy-paste the code from above into each file
```

---

## ⚠️ IMPORTANT NOTES

1. **Media Migration is Complex** - Requires file transfer from legacy server first
2. **Validation Command** - Should check all relationships and counts
3. **All commands support `--dry-run`** for safe testing
4. **Progress bars** for all long-running operations
5. **Detailed summaries** at end of each migration

---

## 📊 EXPECTED RESULTS

After running all commands:
- 167 Funders migrated
- 452 Contracts migrated (184 archived)
- Programs mapped
- 76 Due Date Emails migrated
- 419 Report Due Dates migrated
- Email assignments created
- 188 Reports migrated
- 1,656 Media files migrated (+ 203 CK-media)
- 1,781 Audit logs archived

**Total Time:** ~3-4 hours for full migration execution
