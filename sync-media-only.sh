#!/bin/bash

################################################################################
# Media-Only Sync Script for CAP TRACK
#
# This script syncs only media files to production, without touching
# the database. Useful for incremental updates after initial migration.
#
# Usage: ./sync-media-only.sh
################################################################################

set -e  # Exit on error

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
PROD_HOST="10.55.0.43"
PROD_USER="dchevalier"
PROD_PATH="/var/www/CC_CAP_TRACK"
LOCAL_PATH="/var/www/CC_CAP_TRACK"

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Header
print_status "==================================================================="
print_status "CAP TRACK Media Files Sync to Production"
print_status "==================================================================="

# Check if media directory exists
if [ ! -d "$LOCAL_PATH/storage/app/public" ]; then
    print_error "Local media directory not found: $LOCAL_PATH/storage/app/public"
    exit 1
fi

# Get local media info
LOCAL_SIZE=$(du -sh "$LOCAL_PATH/storage/app/public" | cut -f1)
LOCAL_FILES=$(find "$LOCAL_PATH/storage/app/public" -type f | wc -l)

print_status "Local media: $LOCAL_SIZE ($LOCAL_FILES files)"

# Test SSH connection
print_status "Testing SSH connection..."
if ! ssh -o ConnectTimeout=5 "${PROD_USER}@${PROD_HOST}" "echo 'Connected'" >/dev/null 2>&1; then
    print_error "Cannot connect to production server"
    exit 1
fi
print_success "Connected to production"

# Confirmation
echo ""
print_warning "This will sync media files to production at 10.55.0.43"
print_warning "Only new/modified files will be transferred"
echo ""
read -p "Continue? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    print_error "Sync cancelled"
    exit 0
fi

# Perform sync with progress
print_status "Syncing media files..."
echo ""

rsync -avz --progress \
    --stats \
    --exclude='.gitignore' \
    "$LOCAL_PATH/storage/app/public/" \
    "${PROD_USER}@${PROD_HOST}:${PROD_PATH}/storage/app/public/"

RSYNC_EXIT=$?

if [ $RSYNC_EXIT -eq 0 ]; then
    echo ""
    print_success "Media sync completed successfully"

    # Fix permissions
    print_status "Setting permissions on production..."
    ssh "${PROD_USER}@${PROD_HOST}" "sudo chown -R www-data:www-data ${PROD_PATH}/storage/app/public && sudo chmod -R 775 ${PROD_PATH}/storage/app/public"

    print_success "Permissions updated"

    # Get production size
    PROD_SIZE=$(ssh "${PROD_USER}@${PROD_HOST}" "du -sh ${PROD_PATH}/storage/app/public | cut -f1")
    print_status "Production media size: $PROD_SIZE"

    print_status "==================================================================="
    print_success "Media sync completed!"
    print_status "==================================================================="
else
    print_error "Media sync failed with exit code: $RSYNC_EXIT"
    exit 1
fi
