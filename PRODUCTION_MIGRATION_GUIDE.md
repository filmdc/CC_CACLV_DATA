# Production Migration Guide
## CAP TRACK Database and Media Migration to 10.55.0.43

**Created:** October 27, 2025
**Author:** Migration Script Generator
**Target Server:** 10.55.0.43

---

## Overview

This guide covers the complete process for migrating the CAP TRACK application database and media files from the development/staging environment to the production server at 10.55.0.43.

### What Gets Migrated

- **Database:** Complete MySQL database `CC_CACLV_DATA` (~size varies)
- **Media Files:** All uploaded files in `storage/app/public/` (~2GB)
- **Permissions:** Proper ownership and permissions set automatically

### What Does NOT Get Migrated

- Application code (must be deployed separately via git)
- Configuration files (.env)
- Composer dependencies (vendor/)
- Node modules (node_modules/)

---

## Prerequisites

Before running the migration, ensure:

1. **SSH Access:** You have SSH access to 10.55.0.43 as user `dchevalier`
2. **Database Access:** MySQL credentials are correct on both servers
3. **Disk Space:** Production server has at least 3GB free space (2GB media + 1GB database buffer)
4. **Backup:** Current production data is backed up (script creates automatic backup)
5. **Network:** Stable network connection for 2GB file transfer
6. **Apache/Web Server:** Production web server should be temporarily in maintenance mode

---

## Pre-Migration Checklist

- [ ] Verify SSH access to production: `ssh dchevalier@10.55.0.43`
- [ ] Confirm database credentials in `.env` are correct
- [ ] Check production disk space: `ssh dchevalier@10.55.0.43 "df -h"`
- [ ] Notify users of upcoming maintenance window
- [ ] Put production site in maintenance mode (if applicable)
- [ ] Close all production application processes accessing the database

---

## Migration Process

### Step 1: Review the Migration Script

The migration script is located at: `/var/www/CC_CAP_TRACK/migrate-to-production.sh`

Review the configuration variables:
- `PROD_HOST`: 10.55.0.43
- `PROD_USER`: dchevalier
- `PROD_PATH`: /var/www/CC_CAP_TRACK
- `DB_NAME`: CC_CACLV_DATA
- `DB_USER`: root
- `DB_PASS`: !!caclv!!

### Step 2: Run Pre-Flight Checks

```bash
# Test SSH connectivity
ssh dchevalier@10.55.0.43 "echo 'Connection successful'"

# Check production disk space
ssh dchevalier@10.55.0.43 "df -h /var/www/CC_CAP_TRACK"

# Verify production database is accessible
ssh dchevalier@10.55.0.43 "mysql -u root -p'!!caclv!!' -e 'SHOW DATABASES;'"

# Check local database
mysql -u root -p'!!caclv!!' -e "SELECT COUNT(*) FROM CC_CACLV_DATA.users;"

# Verify media files size
du -sh storage/app/public/
```

### Step 3: Execute Migration

```bash
cd /var/www/CC_CAP_TRACK
./migrate-to-production.sh
```

The script will:
1. Prompt for confirmation
2. Backup production database on remote server
3. Export local database
4. Transfer database to production
5. Import database on production
6. Sync media files (with progress indicator)
7. Set proper permissions
8. Clear production caches
9. Create rollback script
10. Verify migration success

**Expected Duration:** 10-20 minutes (depending on network speed)

### Step 4: Post-Migration Verification

After migration completes:

```bash
# Test production site access
curl -I http://10.55.0.43

# SSH to production and verify
ssh dchevalier@10.55.0.43

# On production server, verify database
mysql -u root -p'!!caclv!!' -e "SELECT COUNT(*) FROM CC_CACLV_DATA.users;"
mysql -u root -p'!!caclv!!' -e "SELECT COUNT(*) FROM CC_CACLV_DATA.contracts;"

# Verify media files
ls -la /var/www/CC_CAP_TRACK/storage/app/public/ | head -20
du -sh /var/www/CC_CAP_TRACK/storage/app/public/

# Check Laravel application
cd /var/www/CC_CAP_TRACK
php artisan --version
php artisan migrate:status

# Test file permissions
ls -la storage/
ls -la storage/app/public/
```

---

## Rollback Procedure

If issues are detected after migration, use the automatic rollback script:

### Automatic Rollback

```bash
# The rollback script is created in the backup directory
cd /var/www/CC_CAP_TRACK/migration_backups/[TIMESTAMP]/
./rollback.sh
```

This will:
- Restore the production database from the pre-migration backup
- Clear all caches
- Restore production to pre-migration state

**Note:** Rollback only affects the database. Media files are not rolled back (they are additive, not destructive).

### Manual Rollback (if automatic fails)

```bash
# SSH to production
ssh dchevalier@10.55.0.43

# Navigate to application
cd /var/www/CC_CAP_TRACK

# Find the backup
ls -lt migration_backups/

# Restore database manually
gunzip < migration_backups/[TIMESTAMP]/production_backup_[TIMESTAMP].sql.gz | \
  mysql -u root -p'!!caclv!!' CC_CACLV_DATA

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Troubleshooting

### Issue: SSH Connection Fails

**Symptoms:** Cannot connect to 10.55.0.43

**Solutions:**
1. Verify network connectivity: `ping 10.55.0.43`
2. Check SSH service: `ssh -v dchevalier@10.55.0.43`
3. Verify credentials
4. Check if SSH keys are set up: `ls -la ~/.ssh/`

### Issue: Database Import Fails

**Symptoms:** Error during database import step

**Solutions:**
1. Check production MySQL is running: `ssh dchevalier@10.55.0.43 "systemctl status mysql"`
2. Verify database exists: `ssh dchevalier@10.55.0.43 "mysql -u root -p'!!caclv!!' -e 'SHOW DATABASES;'"`
3. Check database user permissions
4. Review migration log for specific error messages

### Issue: Media Sync is Very Slow

**Symptoms:** rsync taking extremely long

**Solutions:**
1. This is normal for 2GB of files
2. Check network speed: `ssh dchevalier@10.55.0.43 "speedtest"`
3. Consider running migration during off-peak hours
4. Alternatively, use `--bwlimit` option in rsync to limit bandwidth

### Issue: Permission Denied Errors

**Symptoms:** Permission errors after migration

**Solutions:**
```bash
# SSH to production
ssh dchevalier@10.55.0.43

# Fix storage permissions
cd /var/www/CC_CAP_TRACK
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Fix media permissions
sudo chown -R www-data:www-data storage/app/public
sudo chmod -R 775 storage/app/public
```

### Issue: Website Shows Errors After Migration

**Symptoms:** 500 errors or blank pages

**Solutions:**
```bash
# SSH to production
ssh dchevalier@10.55.0.43
cd /var/www/CC_CAP_TRACK

# Check Laravel logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Verify .env configuration
cat .env | grep -E "^(APP_|DB_)"

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Migration Logs and Backups

All migration activities are logged and backed up:

### Location

```
/var/www/CC_CAP_TRACK/migration_backups/[TIMESTAMP]/
├── migration.log                          # Complete migration log
├── local_export_[TIMESTAMP].sql.gz       # Exported local database
└── rollback.sh                           # Automatic rollback script
```

### On Production Server

```
/var/www/CC_CAP_TRACK/migration_backups/[TIMESTAMP]/
├── production_backup_[TIMESTAMP].sql.gz  # Production backup
└── local_export_[TIMESTAMP].sql.gz       # Transferred database
```

### Retention Policy

- Keep migration backups for at least 30 days
- After successful migration and verification (1 week), older backups can be archived
- Always keep at least one known-good backup

---

## Post-Migration Tasks

After successful migration and verification:

1. **Update DNS/Load Balancers** (if applicable)
   - Point production domain to 10.55.0.43
   - Update any internal DNS records

2. **Remove Maintenance Mode**
   ```bash
   ssh dchevalier@10.55.0.43 "cd /var/www/CC_CAP_TRACK && php artisan up"
   ```

3. **Monitor Application**
   - Check error logs for 24-48 hours
   - Monitor database performance
   - Verify all features work correctly
   - Test user login and authentication
   - Verify file uploads/downloads work

4. **Update Documentation**
   - Update internal wiki with new production details
   - Document any issues encountered
   - Update disaster recovery procedures

5. **Schedule Regular Backups**
   - Set up automated database backups on production
   - Configure backup retention policy
   - Test backup restoration procedure

---

## Important Notes

### Database Credentials
- Current credentials are in .env file
- Ensure production .env has correct database credentials
- Never commit .env to version control

### Media Files Structure
- Media files are organized by numeric IDs (contract IDs)
- Each directory contains contract-related documents
- Preserve directory structure during migration
- Total size: ~2GB (verify before migration)

### Permissions
- Web server user: `www-data`
- Storage directories: 775
- Storage files: 664
- Apache should have read/write access to storage/

### Laravel Configuration
- After migration, clear all caches
- Verify APP_ENV is set to "production" in .env
- Ensure APP_DEBUG is false in production .env
- APP_KEY must be consistent (don't regenerate)

---

## Emergency Contacts

If you encounter critical issues during migration:

1. **Stop the migration** (Ctrl+C if still running)
2. **Run rollback immediately** to restore production
3. **Document the issue** with logs and error messages
4. **Review troubleshooting section** above
5. **Test in staging** before attempting migration again

---

## Success Criteria

Migration is considered successful when:

- [ ] Database import completed without errors
- [ ] All media files transferred successfully
- [ ] Production site is accessible
- [ ] User login works
- [ ] Database queries return expected data
- [ ] Media files are viewable/downloadable
- [ ] No permission errors in logs
- [ ] All Laravel caches cleared
- [ ] Rollback script created and tested (optional dry-run)

---

## Appendix: Manual Migration Steps

If the automated script fails, you can perform manual migration:

### Manual Database Migration

```bash
# On local/staging server
mysqldump -u root -p'!!caclv!!' CC_CACLV_DATA \
  --single-transaction --routines --triggers \
  | gzip > /tmp/manual_export.sql.gz

# Transfer to production
scp /tmp/manual_export.sql.gz dchevalier@10.55.0.43:/tmp/

# On production server
ssh dchevalier@10.55.0.43
gunzip < /tmp/manual_export.sql.gz | mysql -u root -p'!!caclv!!' CC_CACLV_DATA
```

### Manual Media Sync

```bash
# From local/staging
rsync -avz --progress \
  /var/www/CC_CAP_TRACK/storage/app/public/ \
  dchevalier@10.55.0.43:/var/www/CC_CAP_TRACK/storage/app/public/
```

### Manual Permission Fix

```bash
# On production
ssh dchevalier@10.55.0.43
cd /var/www/CC_CAP_TRACK
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

**End of Migration Guide**
