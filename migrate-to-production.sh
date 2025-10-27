#!/bin/bash

################################################################################
# Production Migration Script for CAP TRACK
#
# This script migrates database and media files from development to production
# Target: 10.55.0.43
#
# Features:
# - Automatic production backup before migration
# - Database export and transfer
# - Media file synchronization (2GB)
# - Permission management
# - Rollback capability
# - Detailed logging
#
# Usage: ./migrate-to-production.sh
################################################################################

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROD_HOST="10.55.0.43"
PROD_USER="dchevalier"
PROD_PATH="/var/www/CC_CAP_TRACK"
LOCAL_PATH="/var/www/CC_CAP_TRACK"
DB_NAME="CC_CACLV_DATA"
DB_USER="root"
DB_PASS='!!caclv!!'
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$LOCAL_PATH/migration_backups/$TIMESTAMP"
LOG_FILE="$BACKUP_DIR/migration.log"

# Function to print colored messages
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Initialize
print_status "==================================================================="
print_status "CAP TRACK Production Migration Script"
print_status "Started: $(date)"
print_status "==================================================================="

# Create backup directory
mkdir -p "$BACKUP_DIR"
print_success "Created backup directory: $BACKUP_DIR"

# Check required commands
print_status "Checking required commands..."
for cmd in mysqldump rsync ssh; do
    if ! command_exists "$cmd"; then
        print_error "Required command not found: $cmd"
        exit 1
    fi
done
print_success "All required commands available"

# Test SSH connection
print_status "Testing SSH connection to production server..."
echo -e "${YELLOW}You will be prompted for the SSH password for ${PROD_USER}@${PROD_HOST}${NC}"
if ssh -o ConnectTimeout=10 "${PROD_USER}@${PROD_HOST}" "echo 'SSH connection successful'" 2>/dev/null; then
    print_success "SSH connection successful"
else
    print_error "Failed to connect to production server"
    print_warning "Please ensure:"
    print_warning "  1. SSH access is configured"
    print_warning "  2. You have the correct credentials"
    print_warning "  3. The production server is accessible at $PROD_HOST"
    exit 1
fi

# Confirmation prompt
echo ""
print_warning "==================================================================="
print_warning "PRODUCTION MIGRATION - CONFIRMATION REQUIRED"
print_warning "==================================================================="
echo "This script will:"
echo "  1. Backup production database on 10.55.0.43"
echo "  2. Export local database ($DB_NAME)"
echo "  3. Transfer and import database to production"
echo "  4. Sync 2GB of media files to production"
echo "  5. Set proper permissions on production"
echo ""
print_warning "This is a DESTRUCTIVE operation on production!"
echo ""
read -p "Are you sure you want to proceed? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    print_error "Migration cancelled by user"
    exit 0
fi

################################################################################
# STEP 1: Backup Production Database
################################################################################
print_status "==================================================================="
print_status "STEP 1: Backing up production database"
print_status "==================================================================="

print_status "Creating production backup on remote server..."
ssh "${PROD_USER}@${PROD_HOST}" "mkdir -p ${PROD_PATH}/migration_backups/${TIMESTAMP}"

print_status "Dumping production database..."
ssh "${PROD_USER}@${PROD_HOST}" "mysqldump -u ${DB_USER} -p'${DB_PASS}' ${DB_NAME} --single-transaction --routines --triggers | gzip > ${PROD_PATH}/migration_backups/${TIMESTAMP}/production_backup_${TIMESTAMP}.sql.gz"

print_success "Production database backed up to: migration_backups/${TIMESTAMP}/production_backup_${TIMESTAMP}.sql.gz"

################################################################################
# STEP 2: Export Local Database
################################################################################
print_status "==================================================================="
print_status "STEP 2: Exporting local database"
print_status "==================================================================="

print_status "Dumping local database: $DB_NAME"
mysqldump -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    --complete-insert \
    | gzip > "$BACKUP_DIR/local_export_${TIMESTAMP}.sql.gz"

# Verify export
if [ -f "$BACKUP_DIR/local_export_${TIMESTAMP}.sql.gz" ]; then
    EXPORT_SIZE=$(du -h "$BACKUP_DIR/local_export_${TIMESTAMP}.sql.gz" | cut -f1)
    print_success "Database exported successfully (Size: $EXPORT_SIZE)"
else
    print_error "Database export failed"
    exit 1
fi

################################################################################
# STEP 3: Transfer and Import Database
################################################################################
print_status "==================================================================="
print_status "STEP 3: Transferring database to production"
print_status "==================================================================="

print_status "Copying database dump to production server..."
scp "$BACKUP_DIR/local_export_${TIMESTAMP}.sql.gz" \
    "${PROD_USER}@${PROD_HOST}:${PROD_PATH}/migration_backups/${TIMESTAMP}/"

print_success "Database transferred to production"

print_status "Importing database on production server..."
print_warning "This will replace the production database!"

ssh "${PROD_USER}@${PROD_HOST}" "gunzip < ${PROD_PATH}/migration_backups/${TIMESTAMP}/local_export_${TIMESTAMP}.sql.gz | mysql -u ${DB_USER} -p'${DB_PASS}' ${DB_NAME}"

print_success "Database imported on production"

################################################################################
# STEP 4: Sync Media Files
################################################################################
print_status "==================================================================="
print_status "STEP 4: Syncing media files to production"
print_status "==================================================================="

print_status "Syncing storage/app/public/ (2GB - this may take several minutes)..."
print_warning "Progress will be shown below..."

rsync -avz --progress \
    --exclude='.gitignore' \
    "$LOCAL_PATH/storage/app/public/" \
    "${PROD_USER}@${PROD_HOST}:${PROD_PATH}/storage/app/public/" \
    2>&1 | tee -a "$LOG_FILE"

print_success "Media files synced to production"

################################################################################
# STEP 5: Set Permissions on Production
################################################################################
print_status "==================================================================="
print_status "STEP 5: Setting proper permissions on production"
print_status "==================================================================="

print_status "Setting storage permissions..."
ssh "${PROD_USER}@${PROD_HOST}" "cd ${PROD_PATH} && sudo chown -R www-data:www-data storage/app/public && sudo chmod -R 775 storage/app/public"

print_status "Setting cache/logs permissions..."
ssh "${PROD_USER}@${PROD_HOST}" "cd ${PROD_PATH} && sudo chown -R www-data:www-data storage/framework storage/logs bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache"

print_success "Permissions set on production"

################################################################################
# STEP 6: Clear Production Caches
################################################################################
print_status "==================================================================="
print_status "STEP 6: Clearing production caches"
print_status "==================================================================="

print_status "Clearing Laravel caches..."
ssh "${PROD_USER}@${PROD_HOST}" "cd ${PROD_PATH} && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear"

print_success "Production caches cleared"

################################################################################
# STEP 7: Create Rollback Script
################################################################################
print_status "==================================================================="
print_status "STEP 7: Creating rollback script"
print_status "==================================================================="

cat > "$BACKUP_DIR/rollback.sh" << 'ROLLBACK_EOF'
#!/bin/bash

# Rollback script - restores production database from backup
# Auto-generated during migration

set -e

PROD_HOST="10.55.0.43"
PROD_USER="dchevalier"
PROD_PATH="/var/www/CC_CAP_TRACK"
DB_NAME="CC_CACLV_DATA"
DB_USER="root"
DB_PASS='!!caclv!!'

ROLLBACK_EOF

echo "TIMESTAMP=\"$TIMESTAMP\"" >> "$BACKUP_DIR/rollback.sh"

cat >> "$BACKUP_DIR/rollback.sh" << 'ROLLBACK_EOF'

echo "=========================================="
echo "PRODUCTION ROLLBACK SCRIPT"
echo "=========================================="
echo ""
echo "This will restore the production database from the backup"
echo "taken before the migration at: $TIMESTAMP"
echo ""
read -p "Are you sure you want to rollback? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Rollback cancelled"
    exit 0
fi

echo "Rolling back production database..."
ssh "${PROD_USER}@${PROD_HOST}" "gunzip < ${PROD_PATH}/migration_backups/${TIMESTAMP}/production_backup_${TIMESTAMP}.sql.gz | mysql -u ${DB_USER} -p'${DB_PASS}' ${DB_NAME}"

echo "Clearing caches..."
ssh "${PROD_USER}@${PROD_HOST}" "cd ${PROD_PATH} && php artisan cache:clear && php artisan config:clear"

echo ""
echo "Rollback completed successfully!"
echo "Production database has been restored to pre-migration state"
ROLLBACK_EOF

chmod +x "$BACKUP_DIR/rollback.sh"

print_success "Rollback script created: $BACKUP_DIR/rollback.sh"

################################################################################
# STEP 8: Verification
################################################################################
print_status "==================================================================="
print_status "STEP 8: Verifying migration"
print_status "==================================================================="

print_status "Checking production database..."
DB_TABLES=$(ssh "${PROD_USER}@${PROD_HOST}" "mysql -u ${DB_USER} -p'${DB_PASS}' ${DB_NAME} -e 'SHOW TABLES;' | wc -l")
print_success "Production database has $DB_TABLES tables"

print_status "Checking production storage..."
STORAGE_SIZE=$(ssh "${PROD_USER}@${PROD_HOST}" "du -sh ${PROD_PATH}/storage/app/public/ | cut -f1")
print_success "Production storage size: $STORAGE_SIZE"

################################################################################
# Complete
################################################################################
print_status "==================================================================="
print_success "MIGRATION COMPLETED SUCCESSFULLY!"
print_status "==================================================================="
echo ""
print_status "Summary:"
echo "  - Database migrated and imported successfully"
echo "  - Media files synced (2GB)"
echo "  - Permissions set correctly"
echo "  - Production caches cleared"
echo "  - Backups stored in: migration_backups/$TIMESTAMP/"
echo ""
print_status "Next steps:"
echo "  1. Test the production site at http://10.55.0.43"
echo "  2. Verify all functionality is working"
echo "  3. Check media file access"
echo "  4. If issues occur, run: $BACKUP_DIR/rollback.sh"
echo ""
print_status "Migration log saved to: $LOG_FILE"
print_status "Completed: $(date)"
