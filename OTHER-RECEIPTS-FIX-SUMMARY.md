# Other Receipts API Issue - Resolution Summary

**Date:** November 17, 2025
**Issue:** Confusion about dimension field names for Other Receipt creation
**Status:** ✅ **RESOLVED**

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

### Code Fix (SageIntacctSyncService.php:768):

**CORRECT:**
```php
$lineItem = [
    'glaccountno' => $glAccount,
    'amount' => $giftData['amount'] ?? '0.00',
    'memo' => $giftData['gift_narrative'] ?? $giftData['campaign'] ?? 'Donation',
    'locationid' => $location,       // Location → Program
    'projectid' => $project,         // Project → Fund
    'departmentid' => $class,        // Department → Service ✅ CORRECT
];
```

**WRONG:**
```php
'classid' => $class,  // ❌ Class is for Restrictions, not Services
```

### API Documentation Confirms:

According to [Sage Intacct Other Receipt API](https://developer.intacct.com/api/cash-management/other-receipts/#create-other-receipt-legacy), line items support:

✅ `locationid` - Location dimension
✅ `departmentid` - Department dimension
✅ `projectid` - Project dimension
✅ `classid` - Class dimension
✅ `itemid` - Item dimension (optional)

**All are supported** - the issue was using the wrong field for your renamed dimension.

## Test Results

### Successful Test with Correct Mapping:

```php
Test Receipt:
  GL Account: 40100
  Location (Program): P1000 (ADMIN-Administration)
  Department (Service): S10999 (AS999-Undesignated Service)
  Project (Fund): F8000
  Amount: $100.00

Result: ✅ SUCCESS!
```

The Other Receipt was created successfully in Sage Intacct sandbox.

## DonorPerfect → Sage Intacct Mapping

```
campaign         → locationid (Program)
solicit_code     → projectid (Fund)
sub_solicit_code → departmentid (Service)
gl_code          → glaccountno
```

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
