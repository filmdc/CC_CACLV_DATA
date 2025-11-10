#!/bin/bash

# Sync contract documents from dev server to production
# Uses rsync to copy files while preserving structure

echo "================================================================"
echo "  Syncing Contract Documents from Dev Server"
echo "================================================================"
echo ""

# Configuration
DEV_SERVER="10.55.0.51"
DEV_USER="dchevalier"
DEV_PATH="/var/www/CC_CAP_TRACK/storage/app/public"
LOCAL_PATH="/var/www/CC_CAP_TRACK/storage/app/public"

# Test SSH connection first
echo "Testing SSH connection to dev server..."
if ! ssh -o ConnectTimeout=5 -o BatchMode=yes ${DEV_USER}@${DEV_SERVER} 'echo Connection successful' 2>/dev/null; then
    echo ""
    echo "ERROR: Cannot connect to dev server!"
    echo ""
    echo "Please ensure:"
    echo "1. You've added the SSH public key to the dev server"
    echo "2. Run: ./scripts/01-setup-ssh-key.sh for instructions"
    echo "3. Test connection: ssh ${DEV_USER}@${DEV_SERVER}"
    echo ""
    exit 1
fi

echo "✓ SSH connection successful"
echo ""

# Count files on dev server
echo "Checking how many files need to be synced..."
DEV_FILE_COUNT=$(ssh ${DEV_USER}@${DEV_SERVER} "find ${DEV_PATH} -type f -name '*.pdf' -o -name '*.doc*' -o -name '*.xls*' 2>/dev/null | wc -l")
echo "Found $DEV_FILE_COUNT files on dev server"
echo ""

# Confirm before proceeding
echo "This will:"
echo "  - Sync files from: ${DEV_USER}@${DEV_SERVER}:${DEV_PATH}"
echo "  - To local path: ${LOCAL_PATH}"
echo "  - Estimated files: $DEV_FILE_COUNT"
echo ""
read -p "Proceed with sync? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Sync cancelled."
    exit 0
fi

echo ""
echo "================================================================"
echo "  Starting rsync..."
echo "================================================================"
echo ""

# Create log directory
mkdir -p /var/www/CC_CAP_TRACK/storage/logs

# Run rsync with progress
rsync -avz \
    --progress \
    --stats \
    --log-file=/var/www/CC_CAP_TRACK/storage/logs/contract-sync-$(date +%Y%m%d-%H%M%S).log \
    ${DEV_USER}@${DEV_SERVER}:${DEV_PATH}/ \
    ${LOCAL_PATH}/

RSYNC_EXIT=$?

echo ""
echo "================================================================"

if [ $RSYNC_EXIT -eq 0 ]; then
    echo "  ✓ Sync completed successfully!"
    echo "================================================================"
    echo ""
    echo "Verifying files..."

    # Count local files
    LOCAL_FILE_COUNT=$(find ${LOCAL_PATH} -type f -name '*.pdf' -o -name '*.doc*' -o -name '*.xls*' 2>/dev/null | wc -l)
    echo "Local files now: $LOCAL_FILE_COUNT"
    echo ""

    # Check a few random directories
    echo "Sample verification (checking first 5 media IDs):"
    head -6 /var/www/CC_CAP_TRACK/storage/app/public/contract_documents_mapping.csv | tail -5 | while IFS=',' read -r media_id filename rest; do
        # Remove quotes from filename
        filename=$(echo "$filename" | tr -d '"')
        FILE_PATH="${LOCAL_PATH}/${media_id}/${filename}"

        if [ -f "$FILE_PATH" ]; then
            FILE_SIZE=$(ls -lh "$FILE_PATH" | awk '{print $5}')
            echo "  ✓ Media $media_id: $filename ($FILE_SIZE)"
        else
            echo "  ✗ Media $media_id: $filename (NOT FOUND)"
        fi
    done

    echo ""
    echo "================================================================"
    echo "  Next Steps:"
    echo "================================================================"
    echo "1. Check the log file for details:"
    echo "   tail -50 storage/logs/contract-sync-*.log"
    echo ""
    echo "2. Test contract attachments in the web interface"
    echo ""
    echo "3. If files are missing, check:"
    echo "   - Dev server file paths"
    echo "   - File permissions"
    echo "   - Disk space"
    echo "================================================================"
else
    echo "  ✗ Sync failed with error code: $RSYNC_EXIT"
    echo "================================================================"
    echo ""
    echo "Check the log file for details:"
    echo "  tail -50 storage/logs/contract-sync-*.log"
    echo ""
    exit $RSYNC_EXIT
fi
