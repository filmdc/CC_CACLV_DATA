#!/bin/bash

################################################################################
# Simple SSH Connection Test
#
# This script tests SSH connectivity to production server
# Usage: ./test-ssh-connection.sh
################################################################################

# Configuration
PROD_HOST="10.55.0.43"
PROD_USER="dchevalier"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=========================================="
echo "Testing SSH Connection to Production"
echo "=========================================="
echo ""
echo -e "${YELLOW}Server:${NC} ${PROD_USER}@${PROD_HOST}"
echo ""
echo -e "${YELLOW}You will be prompted for the SSH password...${NC}"
echo ""

# Test basic SSH
echo "Testing basic SSH connectivity..."
if ssh -o ConnectTimeout=10 "${PROD_USER}@${PROD_HOST}" "echo 'Connection successful!'"; then
    echo ""
    echo -e "${GREEN}✓ SSH connection works!${NC}"
    echo ""

    # Get some info
    echo "Getting production server info..."
    echo ""

    echo -n "Hostname: "
    ssh "${PROD_USER}@${PROD_HOST}" "hostname"

    echo -n "Current directory exists: "
    ssh "${PROD_USER}@${PROD_HOST}" "[ -d /var/www/CC_CAP_TRACK ] && echo 'Yes' || echo 'No'"

    echo -n "Disk space available: "
    ssh "${PROD_USER}@${PROD_HOST}" "df -h /var/www/CC_CAP_TRACK | tail -1 | awk '{print \$4}'"

    echo ""
    echo -e "${GREEN}✓ All SSH tests passed!${NC}"
    echo "You can now run: ./pre-migration-check.sh"

else
    echo ""
    echo -e "${RED}✗ SSH connection failed!${NC}"
    echo ""
    echo "Troubleshooting tips:"
    echo "  1. Verify the server is accessible: ping ${PROD_HOST}"
    echo "  2. Check your SSH credentials"
    echo "  3. Ensure SSH service is running on production"
    echo "  4. Try manually: ssh ${PROD_USER}@${PROD_HOST}"
    exit 1
fi
