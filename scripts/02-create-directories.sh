#!/bin/bash

# Create directory structure for contract documents
# Based on media IDs from the database

echo "================================================================"
echo "  Creating Directory Structure for Contract Documents"
echo "================================================================"
echo ""

# Change to project root
cd /var/www/CC_CAP_TRACK

# Check if mapping file exists
if [ ! -f "storage/app/public/contract_documents_mapping.csv" ]; then
    echo "ERROR: Mapping file not found!"
    echo "Expected: storage/app/public/contract_documents_mapping.csv"
    exit 1
fi

echo "Reading mapping file..."
TOTAL_DIRS=$(tail -n +2 storage/app/public/contract_documents_mapping.csv | wc -l)
echo "Found $TOTAL_DIRS media IDs to process"
echo ""

# Create base directory
mkdir -p storage/app/public
echo "✓ Base directory ready: storage/app/public"
echo ""

echo "Creating media ID directories..."
CREATED=0
EXISTED=0

# Read CSV and create directories (skip header line)
tail -n +2 storage/app/public/contract_documents_mapping.csv | while IFS=',' read -r media_id rest; do
    DIR="storage/app/public/$media_id"

    if [ ! -d "$DIR" ]; then
        mkdir -p "$DIR"
        ((CREATED++))

        # Show progress every 100 directories
        if [ $((CREATED % 100)) -eq 0 ]; then
            echo "  Created $CREATED directories..."
        fi
    else
        ((EXISTED++))
    fi
done

echo ""
echo "================================================================"
echo "  Directory Creation Complete!"
echo "================================================================"
echo ""
echo "You can verify by checking a few directories:"
echo "  ls -la storage/app/public/1175/"
echo "  ls -la storage/app/public/1176/"
echo ""
echo "Next step: Run ./scripts/03-sync-documents.sh to copy files from dev"
echo "================================================================"
