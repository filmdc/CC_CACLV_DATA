# Production Migration Scripts - Quick Reference

## Overview

This directory contains automated scripts for migrating the CAP TRACK application database and media files to the production server at **10.55.0.43**.

**Total Data to Migrate:**
- Database: CC_CACLV_DATA (~varies by content)
- Media Files: ~2GB (1800+ directories with contract documents)

---

## Available Scripts

### 1. `pre-migration-check.sh` - Pre-Flight Verification
**Purpose:** Verify all prerequisites before migration
**Run First:** ✓ Always run this before migration

```bash
./pre-migration-check.sh
```

**What it checks:**
- Required commands (mysqldump, rsync, ssh, gzip)
- Local database accessibility
- Local media files existence
- Local disk space
- Network connectivity to production
- SSH access to production
- Production disk space
- Production MySQL access
- Production directory structure
- Data integrity (users, contracts, media)

**Expected output:** Pass/Fail report with recommendations

---

### 2. `migrate-to-production.sh` - Full Migration
**Purpose:** Complete database and media migration to production
**Run When:** After pre-flight check passes

```bash
./migrate-to-production.sh
```

**What it does:**
1. Creates automatic backup of production database
2. Exports local database with complete structure and data
3. Transfers database to production server
4. Imports database on production (replaces existing)
5. Syncs all media files (2GB) with progress indicator
6. Sets proper permissions (www-data ownership)
7. Clears all Laravel caches on production
8. Creates automatic rollback script
9. Verifies migration success

**Duration:** 10-20 minutes (depends on network speed)
**Safety:** Creates automatic backup + rollback script

---

### 3. `sync-media-only.sh` - Incremental Media Sync
**Purpose:** Sync only new/modified media files without touching database
**Run When:** After initial migration, for incremental updates

```bash
./sync-media-only.sh
```

**What it does:**
1. Syncs only changed files (uses rsync)
2. Much faster than full migration
3. Sets proper permissions
4. Shows transfer statistics

**Duration:** 1-5 minutes (only new files)
**Safety:** Non-destructive, additive only

---

## Quick Start Guide

### First Time Migration (Complete)

```bash
# Step 1: Pre-flight check
./pre-migration-check.sh

# Step 2: Review output, fix any failures

# Step 3: Run full migration
./migrate-to-production.sh

# Step 4: Follow on-screen prompts
# - Confirm migration when prompted
# - Wait for completion (10-20 min)
# - Review success summary

# Step 5: Verify production site
# - Test: http://10.55.0.43
# - Login and verify functionality
# - Check media file access
```

### Subsequent Updates (Incremental)

```bash
# For media files only
./sync-media-only.sh

# For database changes, re-run full migration
./migrate-to-production.sh
```

---

## Rollback Procedure

If migration fails or issues are detected:

```bash
# Automatic rollback (created during migration)
cd migration_backups/[TIMESTAMP]/
./rollback.sh

# Manual rollback (if automatic fails)
ssh dchevalier@10.55.0.43
cd /var/www/CC_CAP_TRACK/migration_backups/[TIMESTAMP]
gunzip < production_backup_[TIMESTAMP].sql.gz | mysql -u root -p'!!caclv!!' CC_CACLV_DATA
```

---

## Script Details

### Configuration (Edit if needed)

All scripts use these default values:

```bash
PROD_HOST="10.55.0.43"
PROD_USER="dchevalier"
PROD_PATH="/var/www/CC_CAP_TRACK"
DB_NAME="CC_CACLV_DATA"
DB_USER="root"
DB_PASS='!!caclv!!'
```

To change: Edit the variables at the top of each script

### SSH Authentication

Scripts require SSH access to production:
- Password authentication (prompted)
- OR SSH key authentication (recommended)

**To set up SSH keys:**
```bash
# Generate key if not exists
ssh-keygen -t rsa -b 4096

# Copy to production
ssh-copy-id dchevalier@10.55.0.43

# Test
ssh dchevalier@10.55.0.43 "echo 'Connected'"
```

### Backup Locations

**Local backups:**
```
/var/www/CC_CAP_TRACK/migration_backups/[TIMESTAMP]/
├── migration.log                       # Complete log
├── local_export_[TIMESTAMP].sql.gz    # Database export
└── rollback.sh                        # Rollback script
```

**Production backups:**
```
/var/www/CC_CAP_TRACK/migration_backups/[TIMESTAMP]/
├── production_backup_[TIMESTAMP].sql.gz  # Pre-migration backup
└── local_export_[TIMESTAMP].sql.gz       # Imported database
```

---

## Important Notes

### Before Running Migration

1. **Notify Users:** Schedule maintenance window
2. **Backup Verification:** Ensure production has working backups
3. **Network Stability:** Use stable, fast connection (2GB transfer)
4. **Testing:** Consider testing in staging first
5. **Timing:** Run during off-peak hours

### During Migration

- **Don't interrupt:** Let scripts complete fully
- **Monitor output:** Watch for errors or warnings
- **Network:** Maintain stable connection
- **Time:** Be patient with 2GB file transfer

### After Migration

1. **Test immediately:** Verify site functionality
2. **Check logs:** Review Laravel logs for errors
3. **Verify data:** Confirm database records are correct
4. **Test media:** Ensure files are accessible
5. **Monitor:** Watch for issues first 24-48 hours

---

## Troubleshooting

### SSH Connection Fails
```bash
# Test connectivity
ping 10.55.0.43
ssh -v dchevalier@10.55.0.43

# Check SSH keys
ls -la ~/.ssh/
```

### Database Import Fails
```bash
# Check production MySQL
ssh dchevalier@10.55.0.43 "systemctl status mysql"

# Verify credentials
ssh dchevalier@10.55.0.43 "mysql -u root -p'!!caclv!!' -e 'SHOW DATABASES;'"
```

### Rsync Slow/Stalls
```bash
# Check network
ping 10.55.0.43

# Test transfer speed
ssh dchevalier@10.55.0.43 "dd if=/dev/zero bs=1M count=100 | ssh $(whoami)@$(hostname) 'cat > /dev/null'"
```

### Permission Errors After Migration
```bash
# Fix on production
ssh dchevalier@10.55.0.43
cd /var/www/CC_CAP_TRACK
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Support and Documentation

- **Detailed Guide:** See `PRODUCTION_MIGRATION_GUIDE.md`
- **Laravel Docs:** https://laravel.com/docs
- **Project README:** See main `README.md`

---

## Migration Checklist

- [ ] Pre-flight check passes
- [ ] Users notified of maintenance
- [ ] Network connection stable
- [ ] SSH access verified
- [ ] Production backup verified
- [ ] Run migration script
- [ ] Verify database import
- [ ] Verify media sync
- [ ] Test production site
- [ ] Check error logs
- [ ] Clear maintenance mode
- [ ] Monitor for issues

---

## Common Commands

```bash
# Check migration logs
tail -f migration_backups/*/migration.log

# List all backups
ls -lah migration_backups/

# Check production database
ssh dchevalier@10.55.0.43 "mysql -u root -p'!!caclv!!' -e 'SELECT COUNT(*) FROM CC_CACLV_DATA.users;'"

# Check production storage size
ssh dchevalier@10.55.0.43 "du -sh /var/www/CC_CAP_TRACK/storage/app/public"

# View production Laravel logs
ssh dchevalier@10.55.0.43 "tail -f /var/www/CC_CAP_TRACK/storage/logs/laravel.log"

# Clear production caches
ssh dchevalier@10.55.0.43 "cd /var/www/CC_CAP_TRACK && php artisan optimize:clear"
```

---

**Created:** October 27, 2025
**Version:** 1.0
**Maintainer:** System Administrator
