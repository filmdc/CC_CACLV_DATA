# Contract Documents Migration Guide

This guide will help you sync **3,469 contract documents** from the dev server (10.55.0.51) to production.

## Overview

- **Source**: Dev server at 10.55.0.51 (dchevalier@10.55.0.51)
- **Destination**: This production server
- **Total Files**: 3,469 documents
- **Directory Structure**: Files organized by Media ID (1175, 1176, etc.)

## Quick Start

Run these scripts in order:

```bash
cd /var/www/CC_CAP_TRACK/scripts

# Step 1: Setup SSH keys (one-time)
./01-setup-ssh-key.sh

# Step 2: Create directory structure
./02-create-directories.sh

# Step 3: Sync files from dev
./03-sync-documents.sh
```

## Detailed Instructions

### Step 1: SSH Key Setup

This is a **one-time setup** to allow passwordless SSH from production to dev.

```bash
./01-setup-ssh-key.sh
```

This will display your SSH public key. Follow the on-screen instructions to:
1. SSH to the dev server
2. Add the public key to `~/.ssh/authorized_keys`
3. Test the connection

**Test SSH access:**
```bash
ssh dchevalier@10.55.0.51 'echo Connection successful'
```

If this works without asking for a password, you're ready!

### Step 2: Create Directories

This creates 3,469 directories (one for each media ID):

```bash
./02-create-directories.sh
```

This will create:
- `storage/app/public/1175/`
- `storage/app/public/1176/`
- `storage/app/public/1177/`
- ... and 3,466 more

**Verify:**
```bash
ls -la storage/app/public/1175/
ls -la storage/app/public/1176/
```

### Step 3: Sync Documents

This uses `rsync` to copy all files from dev to production:

```bash
./03-sync-documents.sh
```

The script will:
- Test SSH connection
- Count files on dev server
- Ask for confirmation
- Sync all files with progress display
- Verify sample files
- Create a log file

**What gets synced:**
- All PDFs, Word docs, Excel files
- Preserves file structure
- Only copies new/changed files (safe to re-run)

## File Structure

The mapping file shows which files go where:

```csv
media_id,filename,directory,contract_id,contract_title
1175,"6411e10485464_CSBG-Master-Agreement.pdf",storage/app/public/1175/,1655,"2022-2026CALV_CSBG"
1176,"64a6e808cd81c_C000082084---FR5-LPC.pdf",storage/app/public/1176/,1655,"2022-2026CALV_CSBG"
```

**View the full mapping:**
```bash
cat storage/app/public/contract_documents_mapping.csv
```

Or download it: `/storage/contract_documents_mapping.csv`

## Troubleshooting

### SSH Connection Fails

**Problem:** Cannot connect to dev server

**Solution:**
```bash
# Test connection manually
ssh dchevalier@10.55.0.51

# If it asks for a password, the SSH key isn't set up correctly
# Re-run: ./01-setup-ssh-key.sh and follow instructions
```

### Rsync Permission Denied

**Problem:** "Permission denied" during sync

**Solution:**
```bash
# Make sure local directories are writable
chmod -R 775 storage/app/public/

# Make sure you're running as the correct user
whoami  # Should show: dchevalier
```

### Files Not Showing in Web Interface

**Problem:** Files copied but contract attachments still show errors

**Solution:**
```bash
# Clear Laravel caches
php artisan view:clear
php artisan route:clear
php artisan cache:clear

# Check file permissions
find storage/app/public -type f -name "*.pdf" | head -5 | xargs ls -la

# Files should be readable by www-data group
```

### Want to Sync Only Specific Contracts

**Problem:** Don't want to sync all 3,469 files

**Solution:** Use rsync with a filter:
```bash
# Example: Sync only contract 1655
rsync -avz \
  --include='1175/**' \
  --include='1176/**' \
  --include='1177/**' \
  --exclude='*' \
  dchevalier@10.55.0.51:/var/www/CC_CAP_TRACK/storage/app/public/ \
  storage/app/public/
```

## Verification

After syncing, verify files are accessible:

```bash
# Count synced files
find storage/app/public -type f \( -name "*.pdf" -o -name "*.doc*" -o -name "*.xls*" \) | wc -l

# Check specific files
ls -lh storage/app/public/1175/
ls -lh storage/app/public/1176/

# Test in browser
# Go to: Contract Management → Select a contract → View documents
```

## Logs

Sync logs are saved to:
```
storage/logs/contract-sync-YYYYMMDD-HHMMSS.log
```

View the latest log:
```bash
ls -lt storage/logs/contract-sync-*.log | head -1
tail -50 storage/logs/contract-sync-*.log
```

## Re-running the Sync

It's safe to re-run the sync script. Rsync will:
- Only copy new or changed files
- Skip files that already exist and match
- Be much faster on subsequent runs

```bash
# Safe to run multiple times
./03-sync-documents.sh
```

## Manual Alternative

If you prefer to copy files manually:

```bash
# From your local machine or dev server:
scp -r dchevalier@10.55.0.51:/var/www/CC_CAP_TRACK/storage/app/public/* \
       production-server:/var/www/CC_CAP_TRACK/storage/app/public/

# Or using rsync from dev server:
rsync -avz storage/app/public/ \
      production-server:/var/www/CC_CAP_TRACK/storage/app/public/
```

## Support

If you encounter issues:

1. Check the sync log files
2. Verify SSH connectivity
3. Check disk space: `df -h`
4. Verify file permissions
5. Review Laravel error logs: `tail -50 storage/logs/laravel.log`

## Summary

| Step | Script | Purpose | Duration |
|------|--------|---------|----------|
| 1 | `01-setup-ssh-key.sh` | Setup SSH access | 5 min |
| 2 | `02-create-directories.sh` | Create folder structure | 1 min |
| 3 | `03-sync-documents.sh` | Copy files from dev | 10-30 min |

Total time: ~15-40 minutes depending on network speed and file sizes.
