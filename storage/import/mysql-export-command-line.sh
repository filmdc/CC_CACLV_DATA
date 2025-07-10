#!/bin/bash

# Command Line MySQL Export Script
# This script connects to your old database and exports data directly to JSON files
# 
# Usage:
# 1. Update the database connection details below
# 2. Make the script executable: chmod +x mysql-export-command-line.sh
# 3. Run: ./mysql-export-command-line.sh

# =============================================================================
# DATABASE CONNECTION SETTINGS - UPDATE THESE WITH YOUR DATABASE DETAILS
# =============================================================================
DB_HOST="10.55.0.10"
DB_USER="root"
DB_PASSWORD="Socrates1982!"
DB_NAME="Contracts"

# Export directory (should be the import directory)
EXPORT_DIR="/var/www/CC_CAP_TRACK/storage/import"

# =============================================================================
# EXPORT FUNCTIONS
# =============================================================================

echo "🚀 Starting Legacy Contract Database Export..."
echo "📁 Export directory: $EXPORT_DIR"
echo "🔗 Database: $DB_NAME@$DB_HOST"
echo ""

# Function to run MySQL query and save to file
run_export() {
    local query="$1"
    local output_file="$2"
    local description="$3"
    
    echo "📊 Exporting $description..."
    
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
        --silent --raw --skip-column-names \
        -e "$query" > "$output_file"
    
    if [ $? -eq 0 ]; then
        local count=$(wc -c < "$output_file")
        echo "   ✅ Saved to $output_file ($count bytes)"
    else
        echo "   ❌ Failed to export $description"
        exit 1
    fi
}

# =============================================================================
# EXPORT USERS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'name', name,
            'email', email,
            'email_verified_at', email_verified_at,
            'approved', CASE WHEN approved = 1 THEN true ELSE false END,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') 
FROM users 
WHERE deleted_at IS NULL;
" "$EXPORT_DIR/users.json" "Users"

# =============================================================================
# EXPORT PROGRAMS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'program_name', program_name,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') 
FROM programs 
WHERE deleted_at IS NULL;
" "$EXPORT_DIR/programs.json" "Programs"

# =============================================================================
# EXPORT FUNDERS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'funder_name', funder_name,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') 
FROM funders 
WHERE deleted_at IS NULL;
" "$EXPORT_DIR/funders.json" "Funders"

# =============================================================================
# EXPORT CONTRACTS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', c.id,
            'title', c.title,
            'contract_number', c.contract_number,
            'cfda_number', c.cfda_number,
            'purpose', c.purpose,
            'funder_id', c.funder_id,
            'funder_name', f.funder_name,
            'date_of_award', c.date_of_award,
            'amount', c.amount,
            'start_date', c.start_date,
            'end_date', c.end_date,
            'grant_code', c.grant_code,
            'notes', c.notes,
            'archived', CASE WHEN c.archived = 1 THEN true ELSE false END,
            'date_archived', c.date_archived,
            'created_at', c.created_at,
            'updated_at', c.updated_at,
            'deleted_at', c.deleted_at
        ) SEPARATOR ','
    ),
']') 
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
WHERE c.deleted_at IS NULL;
" "$EXPORT_DIR/contracts.json" "Contracts"

# =============================================================================
# EXPORT CONTRACT-PROGRAM RELATIONSHIPS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'contract_id', cp.contract_id,
            'program_id', cp.program_id,
            'contract_title', c.title,
            'program_name', p.program_name
        ) SEPARATOR ','
    ),
']') 
FROM contract_program cp
INNER JOIN contracts c ON cp.contract_id = c.id
INNER JOIN programs p ON cp.program_id = p.id
WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL;
" "$EXPORT_DIR/contract_programs.json" "Contract-Program Relationships"

# =============================================================================
# EXPORT REPORT DUE DATES
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', rdd.id,
            'due_date', rdd.due_date,
            'status', rdd.status,
            'description', rdd.description,
            'contract_id', rdd.contract_id,
            'contract_title', c.title,
            'created_at', rdd.created_at,
            'updated_at', rdd.updated_at,
            'deleted_at', rdd.deleted_at
        ) SEPARATOR ','
    ),
']') 
FROM report_due_dates rdd
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE rdd.deleted_at IS NULL AND c.deleted_at IS NULL;
" "$EXPORT_DIR/report_due_dates.json" "Report Due Dates"

# =============================================================================
# EXPORT DUE DATE EMAILS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'id', id,
            'email', email,
            'created_at', created_at,
            'updated_at', updated_at,
            'deleted_at', deleted_at
        ) SEPARATOR ','
    ),
']') 
FROM due_date_emails
WHERE deleted_at IS NULL;
" "$EXPORT_DIR/due_date_emails.json" "Due Date Emails"

# =============================================================================
# EXPORT DUE DATE EMAIL ASSIGNMENTS
# =============================================================================
run_export "
SELECT CONCAT('[',
    GROUP_CONCAT(
        JSON_OBJECT(
            'report_due_date_id', dderdd.report_due_date_id,
            'due_date_email_id', dderdd.due_date_email_id,
            'email', dde.email,
            'report_description', rdd.description,
            'due_date', rdd.due_date,
            'contract_title', c.title
        ) SEPARATOR ','
    ),
']') 
FROM due_date_email_report_due_date dderdd
INNER JOIN due_date_emails dde ON dderdd.due_date_email_id = dde.id
INNER JOIN report_due_dates rdd ON dderdd.report_due_date_id = rdd.id
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE dde.deleted_at IS NULL 
  AND rdd.deleted_at IS NULL 
  AND c.deleted_at IS NULL;
" "$EXPORT_DIR/due_date_email_assignments.json" "Due Date Email Assignments"

echo ""
echo "🎉 Export completed successfully!"
echo "📋 Files created in: $EXPORT_DIR"
echo ""
echo "📁 Exported files:"
ls -la "$EXPORT_DIR"/*.json 2>/dev/null | grep -E '\.(json)$' || echo "   No JSON files found"
echo ""
echo "🚀 Next steps:"
echo "   1. Verify the exported files contain data"
echo "   2. Update program mappings in config/legacy-import.php"
echo "   3. Run: php artisan contract:import-legacy-data --config-check"
echo "   4. Run: php artisan contract:import-legacy-data --dry-run"
