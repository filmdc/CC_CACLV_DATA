# Legacy Contract Database Migration Implementation Guide

## Status: IN PROGRESS

**Date Started:** October 23, 2025
**Target Completion:** Within 1 week
**Approach:** Parallel Run (1-2 months validation period)

---

## ✅ COMPLETED COMPONENTS

### 1. Database Infrastructure
- ✅ `legacy_audit_logs` table created
- ✅ `migration_validation_log` table created
- ✅ `legacy_contracts` database connection configured

### 2. Migration Commands Created
- ✅ `migrate:legacy-funders` - Fully implemented and tested (dry-run successful - 170 funders found)
- ✅ `migrate:legacy-contracts` - Fully implemented with:
  - Archive status conversion (`"yes"` → `true`, `NULL` → `false`)
  - Date conversion (DATE → DATETIME)
  - Funder mapping via `legacy_id`
  - Program mapping (auto-map + many-to-many relationships)
  - Progress bars and error tracking

---

## 🚧 REMAINING COMMANDS TO CREATE

### 3. Programs Migration (`migrate:legacy-programs`)

**Purpose:** Auto-map legacy programs to CAP TRACK programs by name

**Implementation:**
```php
php artisan make:command MigrateLegacyPrograms
```

**Key Logic:**
1. Fetch all programs from legacy system
2. For each legacy program:
   - Search for matching program in CAP TRACK by name (case-insensitive)
   - If found: Update CAP TRACK program with `legacy_id`
   - If not found: Create new program or log warning
3. Track mappings in `migration_validation_log`

**Example Code Structure:**
```php
$legacyPrograms = DB::connection('legacy_contracts')->table('programs')->get();

foreach ($legacyPrograms as $legacyProgram) {
    // Try to find by name
    $existingProgram = Program::whereRaw('LOWER(name) = ?', [strtolower($legacyProgram->program_name)])
        ->first();

    if ($existingProgram) {
        $existingProgram->update(['legacy_id' => $legacyProgram->id]);
    } else {
        // Create or warn
        $this->warn("No match found for legacy program: {$legacyProgram->program_name}");
    }
}
```

---

### 4. Due Date Emails Migration (`migrate:legacy-due-date-emails`)

**Purpose:** Migrate due date email addresses with user mapping

**Key Logic:**
1. Fetch all `due_date_emails` from legacy
2. For each email:
   - Try to match to existing CAP TRACK user by email
   - If user found: Set `user_id`
   - If not found (distribution lists, role emails): Leave `user_id` NULL
   - Generate display name:
     - For user emails: Use email as name
     - For `planner-*` emails: Extract and capitalize (e.g., "CADCA Planner")
     - For `_AllPlanningStaff`: Use "All Planning Staff"
3. Set default preferences: `{"reminder_intervals":[30,15,7,3,1]}`

**Special Cases:**
- 23 role-based emails (planner-*)
- 1 distribution list (_AllPlanningStaff@caclv.org)
- ~52 individual staff emails

---

### 5. Report Due Dates Migration (`migrate:legacy-report-due-dates`)

**Purpose:** Migrate report_due_dates to contract_report_due_dates

**Key Logic:**
1. Map `contract_id` via Contract's `legacy_id`
2. Map status: `incomplete` → `pending`, `completed` → `completed`
3. Infer report_type from description:
   - Contains "monthly" → `monthly`
   - Contains "quarterly" → `quarterly`
   - Contains "annual" → `annual`
   - Contains "final" → `final`
   - Default → `quarterly`
4. Use `description` as `report_name` (per user decision)
5. Preserve `microsoft_event_id` and `invite_sent` fields
6. Set `active` = `true` by default

**Field Mapping:**
```
Legacy                  → New
====================================
due_date                → due_date
status (incomplete)     → status (pending)
status (completed)      → status (completed)
description             → report_name + description
microsoft_event_id      → microsoft_event_id (preserve)
invite_sent             → invite_sent
-                       → due_time (NULL)
-                       → report_type (inferred)
-                       → active (true)
```

---

### 6. Report Due Date Email Assignments (`migrate:legacy-due-date-email-assignments`)

**Purpose:** Recreate pivot table relationships

**Key Logic:**
1. Fetch from legacy `due_date_email_report_due_date` pivot
2. Map `report_due_date_id` → `contract_report_due_date_id` via `legacy_id`
3. Map `due_date_email_id` via email address match
4. Insert into `contract_report_due_date_emails` with:
   - `active` = `true`
   - `reminder_intervals` = `NULL` (use global defaults)

---

### 7. Reports Migration (`migrate:legacy-reports`)

**Purpose:** Migrate reports to contract_reports

**Key Logic:**
1. Map `contract_id` via Contract's `legacy_id`
2. Map `due_date_id` → `contract_report_due_date_id` via DueDate's `legacy_id`
3. For title: Use related report_due_date's description (per user decision)
4. Set:
   - `status` = `submitted` (assume all legacy reports were submitted)
   - `description` = "Migrated from legacy system"
   - `submitted_date` = report's `created_at` (best approximation)
5. Store legacy report ID in metadata JSON:
   ```json
   {"legacy_report_id": 123, "migrated_at": "2025-10-23T..."}
   ```

---

### 8. Media Files Migration (`migrate:legacy-media`)

**Purpose:** Incremental transfer of 1,859 media files

**Implementation Phases:**

#### Phase A: File Transfer
```bash
# Create staging directory
mkdir -p /var/www/CC_CAP_TRACK/storage/app/public/migration_temp

# Incremental rsync from legacy server
# Contract documents (1,201 files)
rsync -avz --progress dchevalier@10.55.0.10:/var/www/Contracts_Database_Laravel_Gen/storage/app/public/ \
    /var/www/CC_CAP_TRACK/storage/app/public/migration_temp/

# Verify file count
find /var/www/CC_CAP_TRACK/storage/app/public/migration_temp/ -type f | wc -l
```

#### Phase B: Database Migration
**Command:** `migrate:legacy-media {--type=contracts|due-dates|reports|ck-media}`

**For each media type:**

**Contracts (documents):**
```php
$legacyMedia = DB::connection('legacy_contracts')
    ->table('media')
    ->where('model_type', 'App\\Models\\Contract')
    ->where('collection_name', 'documents')
    ->get();

foreach ($legacyMedia as $media) {
    $contract = Contract::where('legacy_id', $media->model_id)->first();

    // Copy file from temp to proper location
    $sourcePath = storage_path('app/public/migration_temp/' . $media->id . '/' . $media->file_name);
    $destPath = storage_path('app/public/' . $contract->id . '/' . $media->file_name);

    // Insert media record with new model_id
    DB::table('media')->insert([
        'model_type' => 'App\\Models\\Contract',
        'model_id' => $contract->id,
        'collection_name' => 'documents', // SAME
        // ... copy all other fields
    ]);
}
```

**Report Due Dates (documents → templates):**
```php
$legacyMedia = DB::connection('legacy_contracts')
    ->table('media')
    ->where('model_type', 'App\\Models\\ReportDueDate')
    ->where('collection_name', 'documents')
    ->get();

foreach ($legacyMedia as $media) {
    $dueDate = ContractReportDueDate::where('legacy_id', $media->model_id)->first();

    DB::table('media')->insert([
        'model_type' => 'App\\Models\\ContractReportDueDate',
        'model_id' => $dueDate->id,
        'collection_name' => 'templates', // RENAMED from 'documents'
        // ... copy all other fields
    ]);
}
```

**Reports (upload → submissions):**
```php
$legacyMedia = DB::connection('legacy_contracts')
    ->table('media')
    ->where('model_type', 'App\\Models\\Report')
    ->where('collection_name', 'upload')
    ->get();

foreach ($legacyMedia as $media) {
    // Find report by legacy_report_id stored in metadata
    $report = ContractReport::whereJsonContains('metadata->legacy_report_id', (string)$media->model_id)->first();

    DB::table('media')->insert([
        'model_type' => 'App\\Models\\ContractReport',
        'model_id' => $report->id,
        'collection_name' => 'submissions', // RENAMED from 'upload'
        // ... copy all other fields
    ]);
}
```

**CKEditor Media (ck-media → ck-media):**
```php
// Preserve structure for inline editor images
$legacyMedia = DB::connection('legacy_contracts')
    ->table('media')
    ->where('collection_name', 'ck-media')
    ->get();

// Map to appropriate model (Contract or ContractReportDueDate)
// Keep collection_name as 'ck-media'
```

---

### 9. Audit Logs Archive (`migrate:legacy-audit-logs`)

**Purpose:** Archive 1,781 audit log entries to read-only table

**Key Logic:**
```php
$auditLogs = DB::connection('legacy_contracts')->table('audit_logs')->get();

foreach ($auditLogs as $log) {
    // Parse subject_type to link to new records
    $legacyContractId = null;
    $legacyReportId = null;

    if (str_contains($log->subject_type, 'Contract#')) {
        $legacyContractId = (int)str_replace('App\\Models\\Contract#', '', $log->subject_type);
    } elseif (str_contains($log->subject_type, 'Report#')) {
        $legacyReportId = (int)str_replace('App\\Models\\Report#', '', $log->subject_type);
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
```

---

### 10. Validation Command (`migrate:validate-all`)

**Purpose:** Comprehensive validation report

**Checks:**

1. **Record Counts:**
```sql
SELECT
    'funders' as entity,
    (SELECT COUNT(*) FROM Contracts.funders) as legacy_count,
    (SELECT COUNT(*) FROM funders WHERE legacy_id IS NOT NULL) as migrated_count;
-- Repeat for all entities
```

2. **Relationship Integrity:**
```sql
-- Contracts with invalid funder_id
SELECT COUNT(*) FROM contracts
WHERE funder_id IS NOT NULL
  AND funder_id NOT IN (SELECT id FROM funders);

-- Due dates with invalid contract_id
SELECT COUNT(*) FROM contract_report_due_dates
WHERE contract_id NOT IN (SELECT id FROM contracts);
```

3. **Media File Accessibility:**
```php
$mediaSample = DB::table('media')
    ->where('created_at', '>=', $migrationStartTime)
    ->inRandomOrder()
    ->limit(20)
    ->get();

foreach ($mediaSample as $media) {
    $path = storage_path('app/public/' . $media->id . '/' . $media->file_name);
    if (!file_exists($path)) {
        $this->error("Missing file: {$media->file_name}");
    }
}
```

4. **Archive Status Conversion:**
```sql
SELECT
    COUNT(*) as total_archived,
    COUNT(CASE WHEN date_archived IS NOT NULL THEN 1 END) as with_date
FROM contracts
WHERE archived = true AND legacy_id IS NOT NULL;
```

---

## 📋 EXECUTION ORDER

**Day 1: Data Migration**
```bash
# 1. Programs (prerequisite for contracts)
php artisan migrate:legacy-programs

# 2. Funders
php artisan migrate:legacy-funders

# 3. Contracts (requires funders and programs)
php artisan migrate:legacy-contracts

# Validate so far
SELECT COUNT(*) FROM contracts WHERE legacy_id IS NOT NULL;
```

**Day 2: Reporting Infrastructure**
```bash
# 4. Due Date Emails
php artisan migrate:legacy-due-date-emails

# 5. Report Due Dates
php artisan migrate:legacy-report-due-dates

# 6. Due Date Email Assignments (pivot)
php artisan migrate:legacy-due-date-email-assignments

# 7. Reports
php artisan migrate:legacy-reports
```

**Days 3-4: Media Migration**
```bash
# Transfer files from legacy server
rsync -avz --progress dchevalier@10.55.0.10:/var/www/Contracts_Database_Laravel_Gen/storage/app/public/ \
    storage/app/public/migration_temp/

# Migrate media incrementally
php artisan migrate:legacy-media --type=contracts
php artisan migrate:legacy-media --type=due-dates
php artisan migrate:legacy-media --type=reports
php artisan migrate:legacy-media --type=ck-media
```

**Day 5: Audit & Validation**
```bash
# Archive audit logs
php artisan migrate:legacy-audit-logs

# Run comprehensive validation
php artisan migrate:validate-all

# Review validation log
mysql -u root -p CC_CACLV_DATA -e "SELECT * FROM migration_validation_log WHERE status = 'error';"
```

---

## 🔍 TESTING & VERIFICATION

### Pre-Migration Checklist
- [ ] Backup current CC_CACLV_DATA database
- [ ] Backup legacy Contracts database
- [ ] Test legacy database connection: `php artisan migrate:legacy-funders --dry-run`
- [ ] Verify Programs exist in CAP TRACK or decide creation strategy
- [ ] Confirm storage path accessibility on legacy server

### Post-Migration Validation
```bash
# Run validation command
php artisan migrate:validate-all

# Check specific entities
mysql -u root -p CC_CACLV_DATA <<EOF
SELECT 'Funders' as entity, COUNT(*) as count FROM funders WHERE legacy_id IS NOT NULL
UNION ALL
SELECT 'Contracts', COUNT(*) FROM contracts WHERE legacy_id IS NOT NULL
UNION ALL
SELECT 'Due Dates', COUNT(*) FROM contract_report_due_dates WHERE legacy_id IS NOT NULL
UNION ALL
SELECT 'Reports', COUNT(*) FROM contract_reports WHERE metadata->'$.legacy_report_id' IS NOT NULL
UNION ALL
SELECT 'Media Files', COUNT(*) FROM media WHERE created_at >= (SELECT MIN(last_synced_at) FROM contracts WHERE legacy_id IS NOT NULL);
EOF
```

### Expected Counts
- Funders: 170
- Contracts: 452
- Report Due Dates: 419
- Reports: 188
- Media Files: 1,656 (excluding 203 CKEditor files)
- Audit Logs: 1,781 (in archive table)

---

## 🚨 TROUBLESHOOTING

### Common Issues

**1. "Funder not found" warnings:**
- Check if funders migration ran successfully
- Verify legacy_id mapping: `SELECT id, legacy_id FROM funders;`

**2. Program mapping failures:**
- Review program names for typos or mismatches
- May need manual mapping table for edge cases

**3. Media files not accessible:**
- Verify rsync completed successfully
- Check file permissions: `chmod -R 755 storage/app/public`
- Confirm Spatie Media Library disk configuration

**4. Microsoft Calendar integration issues:**
- Verify microsoft_event_id values preserved
- Test with a sample event in Outlook calendar

---

## 📞 SUPPORT

**Migration Lead:** Your Team
**Technical Contact:** Claude Code Implementation
**Legacy System:** 10.55.0.10 (DO NOT MODIFY)
**Database:** Contracts (read-only access during migration)

**Important Notes:**
- Legacy system remains UNTOUCHED
- All changes are to CAP TRACK (CC_CACLV_DATA) only
- Parallel run period: 1-2 months for team validation
- Team will manually take legacy system offline after confirmation

---

## ✅ FINAL CHECKLIST

Before declaring migration complete:
- [ ] All 8 migration commands executed successfully
- [ ] `migrate:validate-all` shows 100% completion
- [ ] 20 random contract spot-checks verified
- [ ] Media files downloadable through CAP TRACK UI
- [ ] No broken foreign key relationships
- [ ] Archived contracts properly marked (archived=true)
- [ ] Microsoft Calendar integration tested
- [ ] Team notified of migration completion
- [ ] Parallel run period begins
- [ ] Migration completion report generated

**Status:** ⏳ IN PROGRESS
**Next Step:** Create remaining migration commands (Programs, Due Dates, Reports, Media, Audit, Validation)
