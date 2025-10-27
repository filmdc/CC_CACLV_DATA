#!/bin/bash

################################################################################
# Pre-Migration Check Script for CAP TRACK
#
# This script verifies that all prerequisites are met before running
# the production migration.
#
# Usage: ./pre-migration-check.sh
################################################################################

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROD_HOST="10.55.0.43"
PROD_USER="dchevalier"
PROD_PATH="/var/www/CC_CAP_TRACK"
DB_NAME="CC_CACLV_DATA"
DB_USER="root"
DB_PASS='!!caclv!!'

# Track results
CHECKS_PASSED=0
CHECKS_FAILED=0
WARNINGS=0

print_header() {
    echo ""
    echo -e "${BLUE}================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================================================${NC}"
}

print_check() {
    echo -n "  $1 ... "
}

print_pass() {
    echo -e "${GREEN}✓ PASS${NC}"
    ((CHECKS_PASSED++))
}

print_fail() {
    echo -e "${RED}✗ FAIL${NC}"
    if [ -n "$1" ]; then
        echo -e "    ${RED}→ $1${NC}"
    fi
    ((CHECKS_FAILED++))
}

print_warning() {
    echo -e "${YELLOW}⚠ WARNING${NC}"
    if [ -n "$1" ]; then
        echo -e "    ${YELLOW}→ $1${NC}"
    fi
    ((WARNINGS++))
}

print_info() {
    echo -e "    ${BLUE}→ $1${NC}"
}

# Header
echo -e "${GREEN}"
cat << "EOF"
╔═══════════════════════════════════════════════════════════════╗
║                                                               ║
║   CAP TRACK Production Migration - Pre-Flight Check          ║
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

################################################################################
# 1. Check Local Environment
################################################################################
print_header "1. Local Environment Checks"

# Check required commands
print_check "mysqldump command available"
if command -v mysqldump >/dev/null 2>&1; then
    print_pass
else
    print_fail "mysqldump not found - install mysql-client"
fi

print_check "rsync command available"
if command -v rsync >/dev/null 2>&1; then
    print_pass
else
    print_fail "rsync not found - install rsync"
fi

print_check "ssh command available"
if command -v ssh >/dev/null 2>&1; then
    print_pass
else
    print_fail "ssh not found - install openssh-client"
fi

print_check "gzip command available"
if command -v gzip >/dev/null 2>&1; then
    print_pass
else
    print_fail "gzip not found - install gzip"
fi

# Check local database
print_check "Local database accessible"
if mysql -u "${DB_USER}" -p"${DB_PASS}" -e "USE ${DB_NAME};" 2>/dev/null; then
    print_pass

    # Get table count
    TABLE_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" -s -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_NAME}';" 2>/dev/null)
    print_info "Database has $TABLE_COUNT tables"

    # Get approximate size
    DB_SIZE=$(mysql -u "${DB_USER}" -p"${DB_PASS}" -s -N -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = '${DB_NAME}';" 2>/dev/null)
    print_info "Database size: ~${DB_SIZE}MB"
else
    print_fail "Cannot connect to local database - check credentials"
fi

# Check local storage
print_check "Local media storage exists"
if [ -d "storage/app/public" ]; then
    print_pass

    STORAGE_SIZE=$(du -sh storage/app/public 2>/dev/null | cut -f1)
    FILE_COUNT=$(find storage/app/public -type f 2>/dev/null | wc -l)
    print_info "Storage size: $STORAGE_SIZE ($FILE_COUNT files)"
else
    print_fail "storage/app/public directory not found"
fi

# Check local disk space
print_check "Local disk space available"
LOCAL_FREE=$(df -BG . | tail -1 | awk '{print $4}' | sed 's/G//')
if [ "$LOCAL_FREE" -gt 5 ]; then
    print_pass
    print_info "Available: ${LOCAL_FREE}GB"
else
    print_warning "Low disk space: ${LOCAL_FREE}GB (recommend >5GB)"
fi

# Check for migration script
print_check "Migration script exists"
if [ -f "migrate-to-production.sh" ]; then
    print_pass
    if [ -x "migrate-to-production.sh" ]; then
        print_info "Script is executable"
    else
        print_warning "Script is not executable - run: chmod +x migrate-to-production.sh"
    fi
else
    print_fail "migrate-to-production.sh not found"
fi

################################################################################
# 2. Check Network and Production Connectivity
################################################################################
print_header "2. Network and Production Server Checks"

# Ping production
print_check "Production server reachable (ping)"
if ping -c 1 -W 2 "$PROD_HOST" >/dev/null 2>&1; then
    print_pass
else
    print_fail "Cannot ping $PROD_HOST - check network connectivity"
fi

# SSH connectivity
print_check "SSH connection to production"
echo -e "${YELLOW}  (You may be prompted for password)${NC}"
if timeout 10 ssh -o ConnectTimeout=5 -o BatchMode=yes "${PROD_USER}@${PROD_HOST}" "echo 'success'" 2>/dev/null | grep -q "success"; then
    print_pass
else
    # Try with password prompt
    if timeout 10 ssh -o ConnectTimeout=5 "${PROD_USER}@${PROD_HOST}" "echo 'success'" 2>/dev/null | grep -q "success"; then
        print_pass
        print_info "SSH requires password (consider setting up SSH keys)"
    else
        print_fail "Cannot connect via SSH - check credentials and access"
    fi
fi

# If SSH works, do remote checks
if timeout 10 ssh -o ConnectTimeout=5 "${PROD_USER}@${PROD_HOST}" "echo 'success'" 2>/dev/null | grep -q "success"; then

    # Check production directory exists
    print_check "Production directory exists"
    if ssh "${PROD_USER}@${PROD_HOST}" "[ -d ${PROD_PATH} ] && echo exists" 2>/dev/null | grep -q "exists"; then
        print_pass
    else
        print_fail "Directory ${PROD_PATH} not found on production"
    fi

    # Check production disk space
    print_check "Production disk space available"
    PROD_FREE=$(ssh "${PROD_USER}@${PROD_HOST}" "df -BG ${PROD_PATH} | tail -1 | awk '{print \$4}' | sed 's/G//'" 2>/dev/null)
    if [ -n "$PROD_FREE" ] && [ "$PROD_FREE" -gt 5 ]; then
        print_pass
        print_info "Available: ${PROD_FREE}GB"
    else
        print_fail "Insufficient disk space on production (need >5GB)"
    fi

    # Check production MySQL
    print_check "Production MySQL accessible"
    if ssh "${PROD_USER}@${PROD_HOST}" "mysql -u ${DB_USER} -p'${DB_PASS}' -e 'SELECT 1;' 2>/dev/null" | grep -q "1"; then
        print_pass

        # Check if database exists
        if ssh "${PROD_USER}@${PROD_HOST}" "mysql -u ${DB_USER} -p'${DB_PASS}' -e 'USE ${DB_NAME};' 2>/dev/null"; then
            print_info "Database ${DB_NAME} exists on production"

            # Get production table count
            PROD_TABLES=$(ssh "${PROD_USER}@${PROD_HOST}" "mysql -u ${DB_USER} -p'${DB_PASS}' -s -N -e \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_NAME}';\" 2>/dev/null")
            print_info "Production database has $PROD_TABLES tables"
        else
            print_warning "Database ${DB_NAME} does not exist on production (will be created)"
        fi
    else
        print_fail "Cannot connect to production MySQL - check credentials"
    fi

    # Check production web server
    print_check "Production web server user (www-data)"
    if ssh "${PROD_USER}@${PROD_HOST}" "id www-data >/dev/null 2>&1 && echo exists" 2>/dev/null | grep -q "exists"; then
        print_pass
    else
        print_warning "User www-data not found - check web server configuration"
    fi

    # Check sudo access
    print_check "Sudo access on production"
    if ssh "${PROD_USER}@${PROD_HOST}" "sudo -n true 2>/dev/null && echo 'success'" 2>/dev/null | grep -q "success"; then
        print_pass
        print_info "Passwordless sudo available"
    else
        print_warning "Sudo may require password - ensure you have sudo access"
    fi
fi

################################################################################
# 3. Data Integrity Checks
################################################################################
print_header "3. Data Integrity Checks"

# Check for critical data
print_check "Users table has data"
USER_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" -s -N -e "SELECT COUNT(*) FROM ${DB_NAME}.users;" 2>/dev/null)
if [ -n "$USER_COUNT" ] && [ "$USER_COUNT" -gt 0 ]; then
    print_pass
    print_info "$USER_COUNT users in database"
else
    print_warning "No users found in database"
fi

print_check "Contracts table has data"
CONTRACT_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" -s -N -e "SELECT COUNT(*) FROM ${DB_NAME}.contracts;" 2>/dev/null || echo "0")
if [ "$CONTRACT_COUNT" -gt 0 ]; then
    print_pass
    print_info "$CONTRACT_COUNT contracts in database"
else
    print_warning "No contracts found in database"
fi

print_check "Media files present"
if [ -n "$FILE_COUNT" ] && [ "$FILE_COUNT" -gt 0 ]; then
    print_pass
else
    print_warning "No media files found"
fi

################################################################################
# Summary
################################################################################
print_header "Pre-Flight Check Summary"

echo ""
echo -e "  ${GREEN}Passed:${NC}   $CHECKS_PASSED"
echo -e "  ${RED}Failed:${NC}   $CHECKS_FAILED"
echo -e "  ${YELLOW}Warnings:${NC} $WARNINGS"
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All critical checks passed!${NC}"
    echo ""
    echo -e "You are ready to proceed with migration:"
    echo -e "  ${BLUE}./migrate-to-production.sh${NC}"
    echo ""
    if [ $WARNINGS -gt 0 ]; then
        echo -e "${YELLOW}Note: There are $WARNINGS warnings to review before proceeding${NC}"
    fi
    exit 0
else
    echo -e "${RED}✗ $CHECKS_FAILED critical checks failed${NC}"
    echo ""
    echo "Please resolve the failed checks before proceeding with migration."
    echo "Review the output above for specific issues."
    exit 1
fi
