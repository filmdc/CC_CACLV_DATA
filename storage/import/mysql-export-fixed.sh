#!/bin/bash

# Fixed Command Line MySQL Export Script
# This version avoids GROUP_CONCAT truncation by using individual record exports
# 
# Usage:
# 1. Update the database connection details below
# 2. Make the script executable: chmod +x mysql-export-fixed.sh
# 3. Run: ./mysql-export-fixed.sh

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

echo "🚀 Starting Legacy Contract Database Export (Fixed Version)..."
echo "📁 Export directory: $EXPORT_DIR"
echo "🔗 Database: $DB_NAME@$DB_HOST"
echo ""

# Function to run MySQL query and convert to proper JSON
run_export_fixed() {
    local table="$1"
    local query="$2"
    local output_file="$3"
    local description="$4"
    
    echo "📊 Exporting $description..."
    
    # Create temporary file for raw data
    local temp_file="${output_file}.tmp"
    
    # Export raw data to temp file
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
        --silent --raw --skip-column-names \
        -e "$query" > "$temp_file"
    
    if [ $? -ne 0 ]; then
        echo "   ❌ Failed to export $description"
        rm -f "$temp_file"
        exit 1
    fi
    
    # Convert to proper JSON using PHP
    php -r "
    \$lines = file('$temp_file', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    \$data = [];
    
    foreach (\$lines as \$line) {
        \$json = json_decode(\$line, true);
        if (\$json !== null) {
            \$data[] = \$json;
        }
    }
    
    \$output = json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents('$output_file', \$output);
    
    echo '   ✅ Exported ' . count(\$data) . ' records to $output_file (' . filesize('$output_file') . ' bytes)' . PHP_EOL;
    "
    
    # Clean up temp file
    rm -f "$temp_file"
}

# =============================================================================
# EXPORT USERS
# =============================================================================
run_export_fixed "users" "
SELECT JSON_OBJECT(
    'id', id,
    'name', name,
    'email', email,
    'email_verified_at', email_verified_at,
    'approved', CASE WHEN approved = 1 THEN true ELSE false END,
    'created_at', created_at,
    'updated_at', updated_at,
    'deleted_at', deleted_at
) as json_data
FROM users 
WHERE deleted_at IS NULL
ORDER BY id;
" "$EXPORT_DIR/users.json" "Users"

# =============================================================================
# EXPORT PROGRAMS
# =============================================================================
run_export_fixed "programs" "
SELECT JSON_OBJECT(
    'id', id,
    'program_name', program_name,
    'created_at', created_at,
    'updated_at', updated_at,
    'deleted_at', deleted_at
) as json_data
FROM programs 
WHERE deleted_at IS NULL
ORDER BY id;
" "$EXPORT_DIR/programs.json" "Programs"

# =============================================================================
# EXPORT FUNDERS
# =============================================================================
run_export_fixed "funders" "
SELECT JSON_OBJECT(
    'id', id,
    'funder_name', funder_name,
    'created_at', created_at,
    'updated_at', updated_at,
    'deleted_at', deleted_at
) as json_data
FROM funders 
WHERE deleted_at IS NULL
ORDER BY id;
" "$EXPORT_DIR/funders.json" "Funders"

# =============================================================================
# EXPORT CONTRACTS
# =============================================================================
run_export_fixed "contracts" "
SELECT JSON_OBJECT(
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
) as json_data
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
WHERE c.deleted_at IS NULL
ORDER BY c.id;
" "$EXPORT_DIR/contracts.json" "Contracts"

# =============================================================================
# EXPORT CONTRACT-PROGRAM RELATIONSHIPS
# =============================================================================
run_export_fixed "contract_program" "
SELECT JSON_OBJECT(
    'contract_id', cp.contract_id,
    'program_id', cp.program_id,
    'contract_title', c.title,
    'program_name', p.program_name
) as json_data
FROM contract_program cp
INNER JOIN contracts c ON cp.contract_id = c.id
INNER JOIN programs p ON cp.program_id = p.id
WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
ORDER BY cp.contract_id, cp.program_id;
" "$EXPORT_DIR/contract_programs.json" "Contract-Program Relationships"

# =============================================================================
# EXPORT REPORT DUE DATES
# =============================================================================
run_export_fixed "report_due_dates" "
SELECT JSON_OBJECT(
    'id', rdd.id,
    'due_date', rdd.due_date,
    'status', rdd.status,
    'description', rdd.description,
    'contract_id', rdd.contract_id,
    'contract_title', c.title,
    'created_at', rdd.created_at,
    'updated_at', rdd.updated_at,
    'deleted_at', rdd.deleted_at
) as json_data
FROM report_due_dates rdd
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE rdd.deleted_at IS NULL AND c.deleted_at IS NULL
ORDER BY rdd.id;
" "$EXPORT_DIR/report_due_dates.json" "Report Due Dates"

# =============================================================================
# EXPORT DUE DATE EMAILS
# =============================================================================
run_export_fixed "due_date_emails" "
SELECT JSON_OBJECT(
    'id', id,
    'email', email,
    'created_at', created_at,
    'updated_at', updated_at,
    'deleted_at', deleted_at
) as json_data
FROM due_date_emails
WHERE deleted_at IS NULL
ORDER BY id;
" "$EXPORT_DIR/due_date_emails.json" "Due Date Emails"

# =============================================================================
# EXPORT DUE DATE EMAIL ASSIGNMENTS
# =============================================================================
run_export_fixed "due_date_email_assignments" "
SELECT JSON_OBJECT(
    'report_due_date_id', dderdd.report_due_date_id,
    'due_date_email_id', dderdd.due_date_email_id,
    'email', dde.email,
    'report_description', rdd.description,
    'due_date', rdd.due_date,
    'contract_title', c.title
) as json_data
FROM due_date_email_report_due_date dderdd
INNER JOIN due_date_emails dde ON dderdd.due_date_email_id = dde.id
INNER JOIN report_due_dates rdd ON dderdd.report_due_date_id = rdd.id
INNER JOIN contracts c ON rdd.contract_id = c.id
WHERE dde.deleted_at IS NULL 
  AND rdd.deleted_at IS NULL 
  AND c.deleted_at IS NULL
ORDER BY dderdd.report_due_date_id, dderdd.due_date_email_id;
" "$EXPORT_DIR/due_date_email_assignments.json" "Due Date Email Assignments"

# =============================================================================
# EXPORT MEDIA (CONTRACT DOCUMENTS)
# =============================================================================
run_export_fixed "media" "
SELECT JSON_OBJECT(
    'id', m.id,
    'model_type', m.model_type,
    'model_id', m.model_id,
    'uuid', m.uuid,
    'collection_name', m.collection_name,
    'name', m.name,
    'file_name', m.file_name,
    'mime_type', m.mime_type,
    'disk', m.disk,
    'conversions_disk', m.conversions_disk,
    'size', m.size,
    'manipulations', m.manipulations,
    'custom_properties', m.custom_properties,
    'generated_conversions', m.generated_conversions,
    'responsive_images', m.responsive_images,
    'order_column', m.order_column,
    'created_at', m.created_at,
    'updated_at', m.updated_at,
    'contract_title', c.title,
    'contract_number', c.contract_number
) as json_data
FROM media m
INNER JOIN contracts c ON m.model_id = c.id
WHERE m.model_type = 'App\\\\Models\\\\Contract'
  AND c.deleted_at IS NULL
ORDER BY m.model_id, m.order_column, m.id;
" "$EXPORT_DIR/media.json" "Contract Documents (Media)"

echo ""
echo "🎉 Export completed successfully!"
echo "📋 Files created in: $EXPORT_DIR"
echo ""

# Show file information
echo "📁 Exported files:"
for file in "$EXPORT_DIR"/*.json; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        size=$(du -h "$file" | cut -f1)
        
        # Count records using PHP
        record_count=$(php -r "
            \$content = file_get_contents('$file');
            \$data = json_decode(\$content, true);
            echo is_array(\$data) ? count(\$data) : 0;
        ")
        
        echo "   • $filename - $size ($record_count records)"
    fi
done

echo ""
echo "🔍 Quick validation:"
for file in "$EXPORT_DIR"/*.json; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        
        # Validate JSON
        php -r "
            \$content = file_get_contents('$file');
            \$data = json_decode(\$content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array(\$data)) {
                echo '   ✅ $filename is valid JSON' . PHP_EOL;
            } else {
                echo '   ❌ $filename has JSON errors' . PHP_EOL;
            }
        "
    fi
done

echo ""
echo "🚀 Next steps:"
echo "   1. Verify the exported files contain complete data"
echo "   2. Update program mappings in config/legacy-import.php"
echo "   3. Run: php artisan contract:import-legacy-data --config-check"
echo "   4. Run: php artisan contract:import-legacy-data --dry-run"