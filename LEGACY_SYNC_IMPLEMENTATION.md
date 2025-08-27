# Legacy Contract API Integration - Implementation Complete

## Overview
This implementation provides one-way synchronization from a legacy contract management system to the current CAP TRACK system. The legacy system serves as the source of truth, with the current system pulling data from it.

## Key Features Implemented

### 1. Database Schema Enhancements
- **Contract-Program Many-to-Many**: Added `contract_program` pivot table for legacy compatibility
- **Legacy Sync Tracking**: Added `legacy_id` and `last_synced_at` to all relevant tables
- **Report Due Date Fields**: Added all legacy-specific fields for full feature parity
- **Backward Compatibility**: Existing functionality preserved during enhancement

### 2. Model Updates
All models updated with legacy sync capabilities:
- `Contract`: Many-to-many programs relationship + legacy sync methods
- `ContractReportDueDate`: Legacy reminder fields + calendar integration fields
- `Program`: Many-to-many contracts relationship + sync tracking
- `Funder`: Legacy sync tracking and helper methods

### 3. API Service Implementation
`LegacyContractsApiService` provides:
- **Authentication**: Token-based auth with caching (23-hour cache)
- **syncFunders()**: Pull all funders from legacy system
- **syncPrograms()**: Pull all programs from legacy system  
- **syncContracts()**: Pull contracts with pagination support
- **Field Mapping**: Handle differences between legacy and current schemas
- **Error Handling**: Comprehensive logging and graceful failure recovery

### 4. Console Command
`php artisan legacy:sync` with options:
- `--type=all|funders|programs|contracts`: Choose what to sync
- `--page=N`: Starting page for contracts (useful for large datasets)
- `--dry-run`: Preview mode (placeholder for testing)

## Schema Mapping Summary

### Contract Fields
| Legacy Field | Current Field | Notes |
|--------------|---------------|-------|
| `grant_code` | `fund_code` | **Preserved current naming** |
| `date_of_award` | `date_of_award` | ✅ Already exists |
| `funder_id` | `funder_id` | ✅ Already exists |
| `programs` (array) | Many-to-many via `contract_program` | **Added pivot table** |

### Report Due Date Fields
| Legacy Field | Current Field | Implementation |
|--------------|---------------|----------------|
| `time` | `due_time` | **Added time field** |
| `first_reminder` | `first_reminder` | **Added individual fields** |
| `second_reminder` | `second_reminder` | **Added individual fields** |
| `final_reminder` | `final_reminder` | **Added individual fields** |
| `invite_sent` | `invite_sent` | **Added calendar tracking** |
| `is_recurring` | `is_recurring` | **Added recurrence support** |
| `recurrence_pattern` | `recurrence_pattern` | **Added pattern storage** |
| `reminder_emails` | `reminder_emails` | **Added legacy email field** |
| `microsoft_event_id` | `microsoft_event_id` | **Added calendar integration** |

## Usage Examples

### Full Sync (Recommended for initial setup)
```bash
php artisan legacy:sync
```

### Sync Specific Data Types
```bash
# Sync only funders
php artisan legacy:sync --type=funders

# Sync only programs  
php artisan legacy:sync --type=programs

# Sync only contracts
php artisan legacy:sync --type=contracts

# Resume contract sync from specific page
php artisan legacy:sync --type=contracts --page=5
```

### Configuration Required
Update `.env` with actual legacy API credentials:
```env
LEGACY_API_URL=http://[actual-legacy-server-ip]/api/v1
LEGACY_API_EMAIL=actual-api-user@example.com
LEGACY_API_PASSWORD=actual-api-password
LEGACY_API_DEVICE_NAME=New_Contracts_System
```

## Backward Compatibility

### Contract-Program Relationships
- **Current System**: Single program via `program_id` foreign key
- **Legacy System**: Multiple programs via many-to-many
- **Solution**: Both relationships coexist, with helper methods to access all programs

### Reminder Systems
- **Current System**: `reminder_intervals` array (e.g., `[30, 15, 7]`)
- **Legacy System**: Individual fields (`first_reminder`, `second_reminder`, `final_reminder`)
- **Solution**: Both formats stored, with conversion methods between them

## Key Implementation Details

### 1. Authentication & Caching
- Tokens cached for 23 hours to minimize API calls
- Automatic token refresh on failure
- Comprehensive error logging

### 2. Data Integrity
- Uses `updateOrCreate()` for idempotent syncing
- Legacy IDs tracked for relationship mapping
- `last_synced_at` timestamps for monitoring

### 3. Error Handling
- Graceful failure for individual records (continues processing)
- Detailed logging for troubleshooting
- Retry logic with exponential backoff

### 4. Performance Considerations
- Pagination support for large datasets
- Batch processing to minimize memory usage
- Indexed foreign keys and sync tracking fields

## Migration Files Created
1. `2025_08_26_141451_create_contract_program_table.php`
2. `2025_08_26_141505_add_legacy_fields_to_contract_report_due_dates_table.php`  
3. `2025_08_26_141524_add_legacy_sync_tracking_fields.php`

## Next Steps

### For Production Deployment
1. **Update Environment Variables**: Set actual legacy API credentials
2. **Initial Sync**: Run `php artisan legacy:sync` for full data import
3. **Schedule Regular Syncs**: Add to task scheduler (e.g., hourly for contracts)
4. **Monitor Logs**: Watch for sync failures and API issues
5. **Test Integration**: Verify data accuracy and relationship integrity

### Optional Enhancements
- Scheduled sync via Laravel's task scheduler
- Sync status dashboard for monitoring
- Webhook support for real-time updates (if legacy system supports)
- Document attachment synchronization (if needed)

## Architecture Benefits

- **Maintainable**: Clear separation between legacy and current systems
- **Flexible**: Easy to modify sync frequency and data selection
- **Robust**: Handles API failures gracefully without data corruption
- **Scalable**: Pagination and batch processing for large datasets
- **Auditable**: Comprehensive logging and sync tracking