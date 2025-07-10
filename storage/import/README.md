# Legacy Contract Database Import

This directory contains the import system for migrating data from your old contract database system into CAP TRACK.

## Quick Start Guide

### 1. Export Data from Old System

Run the provided MySQL export script on your old database:

```bash
# Copy the export script to your old database server
cp mysql-export-script.sql /path/to/old/database/server/

# Run the export (adjust connection details as needed)
mysql -u username -p old_database_name < mysql-export-script.sql

# Copy the generated files to this directory
cp /tmp/*.json /var/www/CC_CAP_TRACK/storage/import/
# OR if using CSV
cp /tmp/*.csv /var/www/CC_CAP_TRACK/storage/import/
```

### 2. Configure Program Mappings

Edit the configuration file to map your old program names to new program IDs:

```bash
# Edit the config file
nano /var/www/CC_CAP_TRACK/config/legacy-import.php

# Update the program_mapping section
'program_mapping' => [
    'Energy Assistance' => 1,  // Map to your actual program IDs
    'CSBG' => 2,
    'Multi' => 3,  // Contract management only program
    // Add all your program mappings here
],

# Set default program ID for unmapped contracts
'default_program_id' => 3,  // Your "Multi" or contract-only program ID
```

### 3. Validate Configuration

Check that everything is configured correctly:

```bash
# Check configuration and files
php artisan contract:import-legacy-data --config-check

# Validate program mappings
php artisan contract:import-legacy-data --validate-mappings

# Get mapping suggestions
php artisan contract:import-legacy-data --show-suggestions
```

### 4. Run Import

First run in dry-run mode to see what would happen:

```bash
# Dry run - no changes made
php artisan contract:import-legacy-data --dry-run
```

If everything looks good, run the actual import:

```bash
# Live import - makes actual database changes
php artisan contract:import-legacy-data
```

## Expected Files

The import system expects these files (either JSON or CSV format):

- `users.json` / `users.csv` - User accounts from old system
- `programs.json` / `programs.csv` - Program data (for mapping validation)
- `funders.json` / `funders.csv` - Funder information
- `contracts.json` / `contracts.csv` - Contract data
- `contract_programs.json` / `contract_programs.csv` - Contract-program relationships
- `report_due_dates.json` / `report_due_dates.csv` - Report due dates
- `due_date_emails.json` / `due_date_emails.csv` - Email addresses for notifications
- `due_date_email_assignments.json` / `due_date_email_assignments.csv` - User assignments

## Import Process

The import follows this order to maintain referential integrity:

1. **Users** - Import user accounts (inactive users will need to reset passwords)
2. **Funders** - Import and standardize funder information
3. **Contracts** - Import contracts with program and funder relationships
4. **Report Due Dates** - Import report requirements
5. **Email Assignments** - Assign users to receive reminders

## Key Differences Handled

### Old System → New System Mappings

- **Programs**: Many-to-many → Single program per contract
- **Users**: Basic fields → Enhanced with active status
- **Funders**: Simple name → Full contact information structure
- **Report Due Dates**: Basic → Enhanced with reminder intervals
- **Email Notifications**: Email addresses → User-based assignments

### Data Transformations

- Old program names mapped to new program IDs
- Funder names standardized (e.g., "US Dept" → "U.S. Department")
- Status values mapped ("incomplete" → "pending")
- Default reminder intervals added to all imported due dates
- User approval status converted to active/inactive

## Troubleshooting

### Common Issues

1. **Missing Files**: Make sure all export files are in this directory
2. **Unmapped Programs**: Update `config/legacy-import.php` with program mappings
3. **Invalid Dates**: Check date formats in source data
4. **Missing Funders**: Funders will be created automatically if missing

### Getting Help

```bash
# Check what programs are available in your system
php artisan contract:import-legacy-data --show-suggestions

# Validate your mappings
php artisan contract:import-legacy-data --validate-mappings

# See detailed configuration status
php artisan contract:import-legacy-data --config-check
```

### Log Files

Import logs are saved to:
- `storage/logs/legacy-import.log` - General import log
- `storage/import/import-errors-{timestamp}.json` - Failed records (if any)

## Command Options

```bash
# Dry run (no database changes)
php artisan contract:import-legacy-data --dry-run

# Import specific entity only
php artisan contract:import-legacy-data --entity=users

# Validate mappings without importing
php artisan contract:import-legacy-data --validate-mappings

# Show program mapping suggestions
php artisan contract:import-legacy-data --show-suggestions

# Check configuration
php artisan contract:import-legacy-data --config-check
```

## Post-Import Tasks

After successful import:

1. **Verify Data**: Check that contracts, users, and due dates imported correctly
2. **User Notifications**: Imported users will need to reset their passwords
3. **Permission Assignment**: Assign roles/permissions to imported users
4. **Test Reminders**: Verify email reminder system works with imported data
5. **Archive Old System**: Once verified, you can safely archive the old database

## Support

If you encounter issues during import:

1. Check the logs in `storage/logs/legacy-import.log`
2. Review failed records in `storage/import/import-errors-*.json`
3. Use the validation commands to diagnose configuration issues
4. Run with `--dry-run` to test changes safely