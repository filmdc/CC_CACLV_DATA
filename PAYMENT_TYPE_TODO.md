# Payment Type / Gift Type Mapping Enhancement

**Issue Identified:** 2025-11-19
**Implemented:** 2025-11-19
**Priority:** Medium-High
**Status:** ✅ COMPLETED

## Implementation Summary

**Added 9 new payment method mappings** to correctly handle gift_type values:

**Credit Card Mappings (5):**
- VISAEFT (166 gifts) → Credit Card ✅
- MCEFT (88 gifts) → Credit Card ✅
- AMEXEFT (9 gifts) → Credit Card ✅
- DISCOVEREFT (13 gifts) → Credit Card ✅
- WEB (262 gifts) → Credit Card ✅

**EFT Mappings (2):**
- ACHEFT (34 gifts) → EFT ✅
- PAYPAL (78 gifts) → EFT ✅

**Conservative Mappings (2):**
- SC (72 gifts) → Cash (requires investigation)
- SN (13 gifts) → Cash (already existed)

**Impact:** ~650 gifts (43% of cache) now map to correct payment methods instead of defaulting to Cash.

**Files Updated:**
1. `database/seeders/PaymentMethodMappingSeeder.php` - Added 9 new mappings with documentation
2. `database/seeders/DatabaseSeeder.php` - Added PaymentMethodMappingSeeder to seeder list
3. Database - Mappings added and tested

**Testing Results:**
✅ All 13 test cases pass
✅ VISAEFT now maps to Credit Card (was Cash)
✅ Total active mappings: 25
✅ Fuzzy matching still works for variations

## Problem Summary

Many DonorPerfect transactions are syncing with incorrect payment methods to Sage Intacct because `gift_type` values are not properly mapped.

### Current Situation
- **438 CK** (checks) - ✅ Mapping exists
- **272 CC** (credit cards) - ✅ Mapping exists
- **262 WEB** (web donations) - ❌ NO MAPPING → Defaults to Cash
- **166 VISAEFT** (Visa EFT) - ❌ NO MAPPING → Defaults to Cash (WRONG!)
- **88 MCEFT** (MC EFT) - ❌ NO MAPPING → Defaults to Cash (WRONG!)
- **78 PAYPAL** - ❌ NO MAPPING → Defaults to Cash
- **72 SC** (?) - ❌ NO MAPPING → Defaults to Cash
- **34 ACHEFT** (ACH) - ❌ NO MAPPING → Defaults to Cash (WRONG!)
- **27 ACH** - ✅ Mapping exists (EFT)
- **13 DISCOVEREFT** - ❌ NO MAPPING → Defaults to Cash (WRONG!)
- **13 SN** (?) - ❌ NO MAPPING → Defaults to Cash
- **9 AMEXEFT** - ❌ NO MAPPING → Defaults to Cash (WRONG!)
- **6 CA** (cash) - ✅ Mapping exists

### Impact
**~560 out of 1,510 gifts (37%)** are using incorrect payment methods in Sage Intacct, primarily credit card and EFT transactions being recorded as Cash.

## Current System Behavior

### Sync Logic
**File:** `app/Services/SageIntacctSyncService.php` (Lines 656-667)

```php
$dpPaymentType = $giftData['PAYMENT_TYPE'] ?? $giftData['payment_type'] ?? null;

// Fallback to gift_type if payment_type is empty
if (empty($dpPaymentType) && !empty($giftData['gift_type'])) {
    $dpPaymentType = $giftData['gift_type'];
    Log::debug('Using gift_type as payment_type fallback');
}

$paymentMethod = DonorPerfectPaymentMethodMapping::getIntacctPaymentMethod($dpPaymentType);
```

**The fallback logic is correct** - it uses `gift_type` when `payment_type` is empty.

### Mapping Logic
**File:** `app/Models/DonorPerfectPaymentMethodMapping.php` (Lines 29-61)

The mapping system has:
1. ✅ Exact match lookup
2. ✅ Case-insensitive fuzzy matching
3. ✅ Default fallback (Cash)

**The mapping logic is correct** - it just needs more mappings configured.

### Example Failure Case

**Gift #196843:**
- `payment_type`: "" (blank)
- `gift_type`: "VISAEFT"

**Processing:**
1. payment_type is empty
2. Falls back to gift_type: "VISAEFT"
3. Looks up "VISAEFT" in mappings → Not found
4. Falls back to default → **"Cash"** ❌

**Should be:** Credit Card ✓

## Required Mappings

### Credit Card Types (→ Credit Card)
- **VISAEFT** (166 gifts) - Visa Electronic Funds Transfer
- **MCEFT** (88 gifts) - MasterCard EFT
- **DISCOVEREFT** (13 gifts) - Discover EFT
- **AMEXEFT** (9 gifts) - American Express EFT
- **WEB** (262 gifts) - Web donations (likely credit card based)
- **CC** (272 gifts) - Already mapped ✓

### EFT Types (→ EFT)
- **ACHEFT** (34 gifts) - ACH Electronic Funds Transfer
- **ACH** (27 gifts) - Already mapped ✓

### PayPal (→ EFT or Credit Card)
- **PAYPAL** (78 gifts) - Needs decision: EFT or Credit Card?

### Unknown Types (Need Investigation)
- **SC** (72 gifts) - Stock Certificate? Scholarship?
- **SN** (13 gifts) - ?

## Implementation Plan

### Phase 1: Quick Fix - Add Missing Mappings
Add mappings for known credit card and EFT gift_type codes.

**File to update:** `database/seeders/DonorPerfectSeeder.php`

```php
// Credit Card gift_type codes
DonorPerfectPaymentMethodMapping::create([
    'dp_payment_type' => 'VISAEFT',
    'intacct_payment_method' => 'Credit Card',
    'is_active' => true,
    'order' => 31,
]);

DonorPerfectPaymentMethodMapping::create([
    'dp_payment_type' => 'MCEFT',
    'intacct_payment_method' => 'Credit Card',
    'is_active' => true,
    'order' => 32,
]);

// ... etc.
```

### Phase 2: Discovery & Documentation
Run analysis to discover ALL unique gift_type values:
- Current cache only has recent gifts
- Need historical analysis
- Document what each code means

### Phase 3: Validation Report
Create admin UI to show:
- Gifts with payment_type issues
- What they're currently mapping to
- Flag suspicious mappings (CC → Cash)
- Allow bulk correction

### Phase 4: Enhanced Seeder
Add comprehensive gift_type mappings to DonorPerfectSeeder based on discovery.

## Workaround (Immediate Fix)

Add mappings manually via Tinker or DonorPerfect Configuration UI:

```bash
php artisan tinker --execute="
use App\Models\DonorPerfectPaymentMethodMapping;

// Credit Cards
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'VISAEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true, 'order' => 31]);
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'MCEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true, 'order' => 32]);
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'AMEXEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true, 'order' => 33]);
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'DISCOVEREFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true, 'order' => 34]);
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'WEB', 'intacct_payment_method' => 'Credit Card', 'is_active' => true, 'order' => 35]);

// EFT
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'ACHEFT', 'intacct_payment_method' => 'EFT', 'is_active' => true, 'order' => 36]);
DonorPerfectPaymentMethodMapping::create(['dp_payment_type' => 'PAYPAL', 'intacct_payment_method' => 'EFT', 'is_active' => true, 'order' => 37]);

echo 'Mappings added for common gift_type values';
"
```

## Management UI

The DonorPerfect Configuration page already has a Payment Method Mappings section where admins can:
- View existing mappings
- Add new mappings
- Edit mappings
- Set default

**URL:** `/donor-perfect/configuration` → Payment Methods tab

## Testing After Fix

1. Add the missing mappings
2. Clear gift cache (force re-fetch)
3. Sync gifts with gift_type=VISAEFT
4. Verify they show "Credit Card" not "Cash"

## Notes

- The fuzzy matching algorithm already works well (case-insensitive, partial match)
- The fallback to gift_type already works
- Just need more mappings in the database
- Consider this when onboarding new DonorPerfect instances

## Related Files

- `app/Models/DonorPerfectPaymentMethodMapping.php` - Model with mapping logic
- `app/Services/SageIntacctSyncService.php:656-667` - Fallback logic
- `database/seeders/DonorPerfectSeeder.php` - Seed default mappings
- `app/Livewire/DonorPerfectConfiguration.php` - Admin UI for managing mappings
- `database/migrations/2025_10_19_000008_create_donor_perfect_payment_method_mappings_table.php` - Table structure
