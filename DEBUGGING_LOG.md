# CAP TRACK Debugging and Error Resolution Log

This log documents all bugs discovered and resolved in the CAP TRACK system, providing historical context for future debugging and development.

## Format
Each entry includes:
- **Date**: When the issue was discovered/fixed
- **Issue**: Description of the problem
- **Root Cause**: What caused the issue
- **Fix**: How it was resolved
- **Files Affected**: List of modified files
- **Status**: Fixed/In Progress

---

## January 16, 2025

### 1. Export Button Loading Animation on Page Load
**Issue**: The export button on the import/export page showed a spinning loader animation immediately on page load.
**Root Cause**: The `wire:loading.delay` directive was used without specifying a delay duration, causing the loader to appear during initial Livewire component lifecycle.
**Fix**: Added `.delay.longest` modifier to prevent spinner during initial load, which adds a 750ms delay before showing loading state.
**Files Affected**: 
- `/resources/views/livewire/participant-import-export.blade.php`
**Status**: Fixed

### 2. Household Edit Functionality Missing
**Issue**: Users could not edit household information or add family members from the household view pages.
**Root Cause**: 
- The `showEditHousehold` property existed but was never implemented
- Permission check used wrong permission name ('households.update' instead of 'households.edit')
- Session flash data was lost on redirect
**Fix**: 
- Implemented complete household editing functionality with form fields and methods
- Fixed permission check to use correct permission name
- Changed from `session()->flash()` to `session()` for persistent redirect data
**Files Affected**:
- `/app/Livewire/HouseholdManagement.php`
- `/app/Livewire/HouseholdShow.php`
- `/resources/views/livewire/household-management.blade.php`
- `/resources/views/livewire/household-show.blade.php`
**Status**: Fixed

### 3. Manage Button Not Working in Household Management
**Issue**: The "Manage" button in household management didn't show the management panel when clicked.
**Root Cause**: 
- Missing error handling in selectHousehold method
- Loading states were showing both normal and loading text simultaneously
**Fix**: 
- Added try-catch error handling
- Fixed loading states using `wire:loading.class` to toggle visibility
- Added visual feedback with loading indicators
**Files Affected**:
- `/app/Livewire/HouseholdManagement.php`
- `/resources/views/livewire/household-management.blade.php`
**Status**: Fixed

### 4. Household Address Data Not Saving Properly
**Issue**: Address information entered during participant intake was not fully saving or displaying in household management/edit forms.
**Root Cause**: 
- Database schema mismatch - intake form used `street_address` field while management forms expected `address`
- Missing database fields: `county`, `area`, and `primary_phone`
- Code tried to access non-existent fields
**Fix**: 
- Implemented address history system with new `household_addresses` table
- Created HouseholdAddress model with relationships
- Migrated existing address data to new structure
- Updated all components to use address history
- Removed old address fields from households table
**Files Affected**:
- Created: `/database/migrations/2025_07_16_014849_create_household_addresses_table.php`
- Created: `/database/migrations/2025_07_16_015006_migrate_existing_household_addresses.php`
- Created: `/database/migrations/2025_07_16_015243_remove_address_fields_from_households_table.php`
- Created: `/app/Models/HouseholdAddress.php`
- `/app/Models/Household.php`
- `/app/Livewire/ParticipantIntake.php`
- `/app/Livewire/HouseholdManagement.php`
- `/app/Livewire/HouseholdShow.php`
- `/resources/views/livewire/household-show.blade.php`
**Status**: Fixed

### 5. Search Error - Column Not Found
**Issue**: Search functionality in participant hub threw error: "Column not found: 1054 Unknown column 'street_address' in 'where clause'"
**Root Cause**: GeneralSearch component was still querying old address fields that were removed in the address history migration.
**Fix**: Updated search queries to use the new `currentAddress` relationship instead of direct household fields.
**Files Affected**:
- `/app/Livewire/GeneralSearch.php`
- `/app/Services/RXOfficeHousingImportService.php`
- `/app/Services/ParticipantExportService.php`
**Status**: Fixed

---

## Common Patterns and Lessons Learned

### 1. Livewire Loading States
- Always use specific delay modifiers (`.delay.shortest`, `.delay.short`, `.delay.long`, `.delay.longest`) rather than bare `.delay`
- Use `wire:loading.class` for toggling visibility instead of `wire:loading.remove` when dealing with initialization issues

### 2. Session Management in Livewire
- Use `session()` instead of `session()->flash()` when data needs to persist across redirects
- Remember to clean up session data after use

### 3. Database Schema Changes
- When adding new fields or restructuring data, search entire codebase for references to old fields
- Consider using relationship tables for data that might have history (like addresses)
- Always create data migration scripts when restructuring existing data

### 4. Permission Naming
- Maintain consistency in permission names across the application
- Common pattern: `resource.action` (e.g., `households.edit`, not `households.update`)

### 5. Error Handling
- Wrap database operations in try-catch blocks
- Provide meaningful error messages to users
- Log detailed error information for debugging

---

## Debugging Checklist

When encountering errors:

1. **Check Browser Console** - JavaScript errors often provide clues
2. **Check Laravel Logs** - `storage/logs/laravel.log`
3. **Check Database Schema** - Ensure fields exist and match code expectations
4. **Search Codebase** - Use grep to find all references to problematic fields/methods
5. **Check Relationships** - Ensure Eloquent relationships are properly defined and loaded
6. **Verify Permissions** - Check both database permissions and code permission checks
7. **Test Data Flow** - Trace data from form input through to database storage

---

## Future Improvements

1. **Add Database Field Validation**: Create a command to validate that all model fillable fields exist in database
2. **Implement Field Mapping Registry**: Central location for field name mappings to prevent schema mismatches
3. **Enhanced Error Logging**: Add more detailed error context in catch blocks
4. **Automated Testing**: Add tests for critical data flows like participant intake and household management