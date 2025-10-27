# Contract Database Migration - EXECUTION GUIDE

**Last Updated:** October 23, 2025
**Status:** Ready for Execution
**Approach:** Clean Slate + Fresh Migration

---

## ⚠️ CRITICAL SITUATION

**Current System State (BOTCHED DATA):**
- 2,031 Contracts (should be 452)
- 1,003 Funders (should be 170)
- 3,469 Media Files (should be 1,859)
- Data has mixed-up fields, lost attachments, confused relationships

**Decision:** **START FRESH** - Clean all contract data and do proper migration

---

## 🎯 EXECUTION PLAN

### **PHASE 0: PRE-FLIGHT** (5 minutes)

✅ **Already Complete:**
- [x] Legacy database connection configured
- [x] Migration infrastructure tables created
- [x] Cleanup command created
- [x] Funders migration command created and tested
- [x] Contracts migration command created

**Verify Legacy Connection:**
```bash
php artisan migrate:legacy-funders --dry-run
```
Expected: "Found 170 funders in legacy system"

---

### **PHASE 1: CLEANUP** (5-10 minutes)

**STEP 1.1: Test Cleanup (DRY RUN)**
```bash
php artisan migrate:clean-contract-data --dry-run
```

Expected Output:
```
Current Data Counts:
   Contracts: 2031
   Funders: 1003
   Due Date Emails: 37
   Report Due Dates: 20
   Contract Reports: 0
   Contract Media Files: 3469
```

**STEP 1.2: Execute Cleanup**
```bash
php artisan migrate:clean-contract-data
```

⚠️ **This will prompt twice for confirmation**
⚠️ **Automatically creates backup to storage/backups/**

Expected: All contract data deleted, participants/users/housing preserved

**STEP 1.3: Verify Clean State**
```bash
php artisan tinker --execute="
echo 'Contracts: ' . \App\Models\Contract::count() . PHP_EOL;
echo 'Funders: ' . \App\Models\Funder::count() . PHP_EOL;
"
```
Expected: Both should be **0**

---

### **PHASE 2: MIGRATE FUNDERS** (2-3 minutes)

```bash
# Dry run first (optional)
php artisan migrate:legacy-funders --dry-run

# Execute migration
php artisan migrate:legacy-funders
```

Expected Output:
```
Found 170 funders in legacy system
Starting migration...
[Progress bar]
Migration Summary:
  Total Legacy Funders: 170
  Migrated: 170
  Skipped: 0
  Errors: 0
```

**Verify:**
```bash
php artisan tinker --execute="
echo 'Funders migrated: ' . \App\Models\Funder::whereNotNull('legacy_id')->count() . PHP_EOL;
"
```
Expected: **170**

---

### **PHASE 3: MIGRATE PROGRAMS** (when command ready)

**Note:** Need to check if Programs exist and whether they have `legacy_id` field

```bash
# Check current programs
php artisan tinker --execute="\App\Models\Program::all(['id', 'name'])->each(fn(\$p) => print \$p->name . PHP_EOL);"

# Execute migration (auto-maps by name)
php artisan migrate:legacy-programs
```

This will:
- Match legacy programs to existing CAP TRACK programs by name
- Update matched programs with `legacy_id`
- Warn about unmatched programs

---

### **PHASE 4: MIGRATE CONTRACTS** (5-10 minutes)

```bash
# Dry run first (optional)
php artisan migrate:legacy-contracts --dry-run

# Execute migration
php artisan migrate:legacy-contracts
```

Expected Output:
```
Found 452 contracts in legacy system
Starting migration...
[Progress bar]
Migration Summary:
  Total Legacy Contracts: 452
  Migrated: 452
  Skipped: 0
  Errors: 0
  Archived contracts: 184
```

**Verify:**
```bash
php artisan tinker --execute="
echo 'Contracts: ' . \App\Models\Contract::count() . PHP_EOL;
echo 'With legacy_id: ' . \App\Models\Contract::whereNotNull('legacy_id')->count() . PHP_EOL;
echo 'Archived: ' . \App\Models\Contract::where('archived', true)->count() . PHP_EOL;
"
```
Expected: 452 total, 452 with legacy_id, 184 archived

---

### **PHASE 5: MIGRATE DUE DATE EMAILS** (when command ready)

```bash
php artisan migrate:legacy-due-date-emails
```

Will migrate 76 email addresses including:
- 52 individual staff emails
- 23 role-based planner emails
- 1 distribution list

Expected: 76 due date emails created

---

### **PHASE 6: MIGRATE REPORT DUE DATES** (when command ready)

```bash
php artisan migrate:legacy-report-due-dates
```

Expected: 419 report due dates migrated with:
- Status mapped (incomplete → pending, completed → completed)
- Report types inferred from descriptions
- Microsoft event IDs preserved

---

### **PHASE 7: MIGRATE EMAIL ASSIGNMENTS** (when command ready)

```bash
php artisan migrate:legacy-due-date-email-assignments
```

Recreates pivot table relationships between due dates and email addresses

---

### **PHASE 8: MIGRATE REPORTS** (when command ready)

```bash
php artisan migrate:legacy-reports
```

Expected: 188 reports migrated with:
- Titles from due date descriptions
- Status set to 'submitted'
- Legacy report ID stored in metadata

---

### **PHASE 9: MIGRATE MEDIA FILES** (Day 2-3)

**STEP 9.1: Transfer Files from Legacy Server**
```bash
# Create staging directory
mkdir -p storage/app/public/migration_temp

# Transfer all files from legacy
rsync -avz --progress dchevalier@10.55.0.10:/var/www/Contracts_Database_Laravel_Gen/storage/app/public/ \
    storage/app/public/migration_temp/

# Verify count
find storage/app/public/migration_temp/ -type f | wc -l
```
Expected: ~1,859 files

**STEP 9.2: Migrate Media Database Records**
```bash
# Migrate contract documents (1,201 files)
php artisan migrate:legacy-media --type=contracts

# Migrate report due date templates (179 files)
php artisan migrate:legacy-media --type=due-dates

# Migrate report submissions (276 files)
php artisan migrate:legacy-media --type=reports

# Migrate CKEditor inline media (203 files)
php artisan migrate:legacy-media --type=ck-media
```

---

### **PHASE 10: ARCHIVE AUDIT LOGS** (when command ready)

```bash
php artisan migrate:legacy-audit-logs
```

Expected: 1,781 audit log entries archived to `legacy_audit_logs` table

---

### **PHASE 11: VALIDATION** (when command ready)

```bash
php artisan migrate:validate-all
```

This will check:
- Record counts match legacy
- No broken foreign key relationships
- Media files accessible
- Archive status conversions correct
- Microsoft Calendar integration preserved

Expected Output:
```
Validation Report:
==================
✓ Funders: 170/170 (100%)
✓ Contracts: 452/452 (100%)
✓ Due Dates: 419/419 (100%)
✓ Reports: 188/188 (100%)
✓ Media Files: 1,656/1,656 (100%)
✓ Audit Logs: 1,781 archived
✓ No foreign key issues
✓ All archived contracts have date_archived
```

---

## 🎯 QUICK EXECUTION (Once All Commands Ready)

```bash
# 1. Clean slate
php artisan migrate:clean-contract-data

# 2. Migrate data
php artisan migrate:legacy-funders
php artisan migrate:legacy-programs
php artisan migrate:legacy-contracts
php artisan migrate:legacy-due-date-emails
php artisan migrate:legacy-report-due-dates
php artisan migrate:legacy-due-date-email-assignments
php artisan migrate:legacy-reports

# 3. Transfer and migrate media (multi-step)
# ... rsync files first ...
php artisan migrate:legacy-media --type=contracts
php artisan migrate:legacy-media --type=due-dates
php artisan migrate:legacy-media --type=reports
php artisan migrate:legacy-media --type=ck-media

# 4. Archive audit logs
php artisan migrate:legacy-audit-logs

# 5. Validate everything
php artisan migrate:validate-all
```

---

## 🔍 TROUBLESHOOTING

### If Cleanup Fails:
```bash
# Manual cleanup via SQL (DANGEROUS!)
mysql -u root -p'!!caclv!!' CC_CACLV_DATA <<EOF
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE contract_reports;
TRUNCATE TABLE contract_report_due_date_emails;
TRUNCATE TABLE contract_report_due_dates;
TRUNCATE TABLE due_date_emails;
TRUNCATE TABLE contract_program;
DELETE FROM media WHERE model_type IN ('App\\Models\\Contract', 'App\\Models\\ContractReportDueDate', 'App\\Models\\ContractReport');
TRUNCATE TABLE contracts;
TRUNCATE TABLE funders;
SET FOREIGN_KEY_CHECKS = 1;
EOF
```

### If Migration Command Fails:
- Check `migration_validation_log` table for errors
- Review specific error messages
- Verify legacy database connection
- Check for missing dependencies (funders before contracts, etc.)

### If Media Transfer Fails:
- Verify SSH access to legacy server
- Check disk space on new server
- Use `--partial` flag with rsync for resume capability
- Transfer in smaller batches by date/type

---

## ✅ COMPLETION CHECKLIST

- [ ] Phase 0: Pre-flight checks passed
- [ ] Phase 1: Old data cleaned (0 contracts, 0 funders)
- [ ] Phase 2: 170 funders migrated
- [ ] Phase 3: Programs mapped
- [ ] Phase 4: 452 contracts migrated (184 archived)
- [ ] Phase 5: 76 due date emails migrated
- [ ] Phase 6: 419 report due dates migrated
- [ ] Phase 7: Due date email assignments created
- [ ] Phase 8: 188 reports migrated
- [ ] Phase 9: 1,656 media files migrated (+ 203 CK-media)
- [ ] Phase 10: 1,781 audit logs archived
- [ ] Phase 11: Validation passed 100%
- [ ] Team notified
- [ ] Parallel run period begins

---

## 📞 CURRENT STATUS

**Completed:**
- ✅ Cleanup command (`migrate:clean-contract-data`)
- ✅ Funders migration (`migrate:legacy-funders`) - tested successfully
- ✅ Contracts migration (`migrate:legacy-contracts`) - ready to test

**Pending:**
- ⏳ Programs migration command
- ⏳ Due date emails migration command
- ⏳ Report due dates migration command
- ⏳ Email assignments migration command
- ⏳ Reports migration command
- ⏳ Media migration command (4 types)
- ⏳ Audit logs archive command
- ⏳ Validation command

**Next Action:** Decide whether to continue creating remaining commands or test what exists first.

---

## 🚀 READY TO EXECUTE?

**Option A: Continue Implementation**
Build all remaining commands first, then execute full migration in one go.

**Option B: Incremental Execution**
Execute cleanup + funders + contracts now, then build/execute remaining commands.

**Recommendation:** Option A for completeness and safety. Have all commands ready and tested before executing on production data.
