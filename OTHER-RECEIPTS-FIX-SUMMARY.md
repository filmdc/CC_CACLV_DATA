# Other Receipts API Issue - Resolution Summary

**Date:** November 17, 2025
**Issue:** Other Receipt creation failing with dimension errors
**Status:** ✅ **RESOLVED AND TESTED**
**Test Record:** Reference ID `COMPLETE-1763414550` created in Sage Intacct

---

## The Problem

Multiple failed attempts to create Other Receipts in Sage Intacct with errors like:
```
"account 40100 requires a Service"
```

Git history showed back-and-forth changes between different dimension fields:
- Commit 084b71b9: Used `itemid` ❌
- Commit f29b9d02: Used `classid` ❌
- Uncommitted changes: Reverted to `departmentid` ✅

## Root Cause

**Confusion between UI labels and API field names** due to Sage Intacct's Terminology feature that allows renaming dimension labels.

### Your Sage Intacct Setup:

| Dimension | API Field Name | Your UI Label | Your Codes |
|-----------|---------------|---------------|------------|
| Location | `locationid` | **Program** | E1000-E6000, P1000-P6000 (32 total) |
| Department | `departmentid` | **Service** | S10000-S60999 (169 total) |
| Project | `projectid` | **Fund** | Various contract codes (373 total) |
| Class | `classid` | **Restriction** | Fund accounting (6 total) |

### The Confusion:

You renamed **Department** → **"Service"** in the UI, leading to confusion:
- Developers saw "Service" in UI and tried `classid` (wrong!)
- The actual API field for Services is `departmentid` (correct!)
- Classes (`classid`) are for Restrictions (fund accounting), not services

## The Solution

### Code Fix (SageIntacctSyncService.php:763-771):

**CORRECT - All 4 Dimensions Required:**
```php
$lineItem = [
    'glaccountno' => $glAccount,
    'amount' => $giftData['amount'] ?? '0.00',
    'memo' => $giftData['gift_narrative'] ?? $giftData['campaign'] ?? 'Donation',
    'locationid' => $location,       // Location → Program (from campaign)
    'departmentid' => $class,        // Department → Service (from sub_solicit_code) ✅
    'projectid' => $project,         // Project → Fund (from solicit_code)
    'classid' => '0',                // Class → Restriction (0 = Unrestricted) ✅
];
```

**CRITICAL:**
- Field ordering matters! `departmentid` must come BEFORE `projectid` in array (affects XML ordering)
- GL account 40100 requires BOTH `departmentid` AND `classid`
- Class IDs: 0=Unrestricted, 1=Temp Restricted, 2=Board Designated, 3=Perm Restricted, 4=Donor Restricted

### API Documentation Confirms:

According to [Sage Intacct Other Receipt API](https://developer.intacct.com/api/cash-management/other-receipts/#create-other-receipt-legacy), line items support:

✅ `locationid` - Location dimension
✅ `departmentid` - Department dimension
✅ `projectid` - Project dimension
✅ `classid` - Class dimension
✅ `itemid` - Item dimension (optional)

**All are supported** - the issue was using the wrong field for your renamed dimension.

## Test Results

### Final Successful Test (All 4 Dimensions):

```php
Test Receipt:
  GL Account: 40100
  Location (Program): P1000 (ADMIN-Administration)
  Department (Service): S10999 (AS999-Undesignated Service)
  Project (Fund): F8000
  Class (Restriction): 0 (Unrestricted)
  Amount: $100.00

Result: ✅ SUCCESS!
Record: COMPLETE-1763414550 (viewable in Sage Intacct)
Date: 2025-11-17
```

The Other Receipt was created successfully in Sage Intacct sandbox with all 4 dimensions validated.

### Failed Test Attempts (for documentation):

1. ❌ Without `departmentid` after `projectid` → "Element not expected" error
2. ❌ Without `classid` → "account 40100 requires a Restriction" error
3. ✅ With correct field order and all dimensions → **SUCCESS**

## DonorPerfect → Sage Intacct Mapping

```
campaign         → locationid (Program)
sub_solicit_code → departmentid (Service)
solicit_code     → projectid (Fund)
(hardcoded)      → classid = '0' (Restriction: Unrestricted)
gl_code          → glaccountno
```

**Note:** `classid` is hardcoded to '0' (Unrestricted) for all donations. Update this if you need to support restricted funds.

### Code Mappings in Database:

| Field Type | Mapped Count |
|-----------|--------------|
| `gl_code` | 16 |
| `campaign` | 15 |
| `solicit_code` | 21 |
| `sub_solicit_code` | 46 |

## Files Changed

### Commits Made:

1. **eca31b37** - Use departmentid for Service dimension in Other Receipts
2. **e57db0ae** - Add Sage Intacct dimensions reference documentation

### New Files:

- `intacct-dimensions.md` - Complete dimension reference (auto-generated)
- `app/Console/Commands/QueryIntacctDimensions.php` - Command to query dimensions

### Modified Files:

- `app/Services/SageIntacctSyncService.php` - Fixed line 768 to use `departmentid`

## How to Query Dimensions

Run this command anytime to refresh the dimension reference:

```bash
php artisan intacct:query-dimensions
```

This will:
- Query all active Locations, Departments, Projects, and Classes
- Generate `intacct-dimensions.md` with current values
- Show code mapping counts from database

## Lessons Learned

1. **Never confuse UI labels with API field names**
   - What you see in the UI ≠ What you use in code
   - Always check API documentation for actual field names

2. **Trust the API documentation**
   - Sage Intacct clearly documents supported fields
   - Don't assume fields are unsupported without testing

3. **Document your dimension setup**
   - The QueryIntacctDimensions command prevents future confusion
   - Keep `intacct-dimensions.md` updated when dimensions change

4. **Test with minimal examples**
   - Single record tests isolate issues faster than batch operations
   - Use known-good dimension codes first

## Next Steps

1. ✅ Test with production DonorPerfect data
2. ✅ Verify campaign override system works correctly
3. ✅ Run full sync and monitor for dimension errors
4. ✅ Update documentation if you add new dimensions

## Important Notes

- **Project IDs:** The `get_list` API doesn't return PROJECTID field - only internal keys
  - Use actual project codes like `F8000` (verify in Sage Intacct UI)
  - Campaign override system uses F8000 for P1400 location

- **Service Codes:** All S-codes (S10999, S14999, S16999, etc.) are Departments
  - Use `departmentid` in API
  - Shows as "Service" in your UI

- **Classes vs Services:**
  - Classes = Fund accounting restrictions (6 total)
  - Services = Departments (169 total)
  - Don't mix them up!

---

**Resolution Time:** ~2 hours
**Complexity:** Medium (terminology confusion)
**Impact:** Critical (blocking gift sync)
**Status:** ✅ Resolved and tested
