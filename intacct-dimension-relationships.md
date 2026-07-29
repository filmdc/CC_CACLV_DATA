# Sage Intacct Dimension Relationships & Mappings

_Generated on: 2025-11-17 16:30:02_

This document shows how DonorPerfect codes map to Sage Intacct dimensions and known working combinations.

---

## Dimension Mapping Overview

```
DonorPerfect Field    →  Sage Intacct Dimension  →  API Field
──────────────────────────────────────────────────────────────────
campaign              →  Location (Program)      →  locationid
sub_solicit_code      →  Department (Service)    →  departmentid
solicit_code          →  Project (Fund)          →  projectid
(hardcoded)           →  Class (Restriction)     →  classid = '4'
gl_code               →  GL Account              →  glaccountno
```

---

## Campaign → Location (Program) Mappings

| DonorPerfect Campaign | Sage Intacct Location | Program Name |
|---|---|---|
| `AD` | `P1000` | ADMIN-Administration |
| `CA` | `P2000` | CADCA-Allentown |
| `CB` | `P3000` | CADCB-Bethlehem |
| `FB` | `P1400` | Second Harvest Food Bank |
| `GN` | `P1300` | REJ-Racial and Ethnic Justice |
| `HC` | `P1100` | CAFS-Housing Counseling Program |
| `RJ` | `P1300` | REJ-Racial and Ethnic Justice |
| `RT` | `P5000` | RTCLF-Rising Tide |
| `SF` | `P1400` | Second Harvest Food Bank |
| `SHFB_FY22-23` | `P1400` | Second Harvest Food Bank |
| `SS` | `P1600` | SSS-Sixth Street Shelter |
| `SSS_FY22-23` | `P1600` | SSS-Sixth Street Shelter |
| `SSS_FY23-24` | `P1600` | SSS-Sixth Street Shelter |

**Unmapped Campaigns:**
- `CACLV_FY22-23` → (no mapping)
- `SH` → (no mapping)

---

## Solicit Code → Project (Fund) Mappings

**All active solicit codes map to:** `F8000`

| DonorPerfect Solicit Code | Sage Intacct Fund |
|---|---|
| `23YEDM07` | `F8000` |
| `24AFDM07` | `F8000` |
| `24MMDI` | `F8000` |
| `24MMDM01` | `F8000` |
| `24MMDM03` | `F8000` |
| `24MMDM05` | `F8000` |
| `24SPDI` | `F8000` |
| `ANNLFUND` | `F8000` |
| `ANNUFUND` | `F8000` |
| `CACLV_WEBDONATIONSFY22-23` | `F8000` |
| `EVENTS` | `F8000` |
| `EVENTSPONSORSHIP` | `F8000` |
| `NEWSLETTER` | `F8000` |
| `NMSOL` | `F8000` |
| `SHFB_WEBDONATIONS_FY22-23` | `F8000` |
| `SSS_UW_FY22-23` | `F8000` |
| `SSSWEB_FY22-23` | `F8000` |
| `SSSWEB_FY23-24` | `F8000` |
| `SUMMITDIG` | `F8000` |
| `WEBDONATIONS` | `F8000` |

**Unmapped:**
- `GRANTS` → (no mapping)

---

## Sub-Solicit Code → Department (Service) Mappings

### Food Bank Services (S14999)

All SHFB-related sub-solicits map to: **S14999** (SHFB999-Undesignated Service)

| DonorPerfect Sub-Solicit | Service Code |
|---|---|
| `24BSDMDI` | `S14999` |
| `24CHDI` | `S14999` |
| `24FLDI` | `S14999` |
| `24HAAQBN` | `S14999` |
| `24HADM03` | `S14999` |
| `24HADM05` | `S14999` |
| `24HADM06` | `S14999` |
| `24HADMDI` | `S14999` |
| `24HNDM05` | `S14999` |
| `24MMDI` | `S14999` |
| `24NDDM04` | `S14999` |
| `24NDDM05` | `S14999` |
| `24NDDM06` | `S14999` |
| `24PADI` | `S14999` |
| `24THDM04` | `S14999` |
| `25MMDI` | `S14999` |
| `FBHPAPPL` | `S14999` |
| `FBPPAPPL` | `S14999` |
| `FBSUNEWS` | `S14999` |
| `SHFBHPAPPL` | `S14999` |
| `TAKACSPONS` | `S14999` |
| `TAKACTDONS` | `S14999` |
| `TAKACTTIXS` | `S14999` |

### Shelter Services (S16999)

SSS-related sub-solicits map to: **S16999** (SSS999-Undesignated Service)

| DonorPerfect Sub-Solicit | Service Code |
|---|---|
| `SSSFALLNWSL` | `S16999` |
| `SSSPNGNWSL` | `S16999` |
| `SSSWEBPAGE` | `S16999` |

### Admin/General Services (S10999)

General and admin sub-solicits map to: **S10999** (AS999-Undesignated Service)

| DonorPerfect Sub-Solicit | Service Code |
|---|---|
| `ALE` | `S10999` |
| `CALVHMAP` | `S10999` |
| `CALVHMAPP` | `S10999` |
| `CAUSEMARK` | `S10999` |
| `CORPBUSTGRT` | `S10999` |
| `CORPMATCGFT` | `S10999` |
| `FNDTRUSTGRT` | `S10999` |
| `ONLINEFALLNEWSLETTER` | `S10999` |
| `UNSOLICIT` | `S10999` |
| `USOLFAITHBCONTB` | `S10999` |
| `USOLFNDCONTRIB` | `S10999` |
| `UWEEGIVNG` | `S10999` |

### Annual Meeting (S10160)

| DonorPerfect Sub-Solicit | Service Code |
|---|---|
| `AMO` | `S10160` |
| `ANLMTGDO` | `S10160` |
| `ANLMTGSPONS` | `S10160` |
| `ANLMTGTIX` | `S10160` |
| `ANNLFUND` | `S10160` |

### Other Services

| DonorPerfect Sub-Solicit | Service Code |
|---|---|
| `CABSTMREVNT` | `S20999` |

**Unmapped:**
- `GRANT` → (no mapping)
- `SEE_SPLIT` → (no mapping - likely split gifts)

---

## Campaign-Based Dimension Overrides

The `campaign_service_overrides` table forces specific dimension combinations for certain locations.

| Location | Override Service | Override Fund | Notes |
|---|---|---|---|
| `P1400` | `S14999` | `F8000` | Second Harvest Food Bank - Always use SHFB service |

**How It Works:**
1. Campaign (e.g., `FB`) maps to Location (`P1400`)
2. System detects override exists for `P1400`
3. Forces Service = `S14999` and Fund = `F8000`
4. Ignores normal solicit_code and sub_solicit_code mappings

**Purpose:** Ensures dimensional compatibility for programs with strict accounting requirements.

---

## Known Working Dimension Combinations

Based on successful test transactions:

### Test 1: Admin Program
```
Location: P1000 (ADMIN-Administration)
Service:  S10999 (AS999-Undesignated Service)
Fund:     F8000
Class:    0 (Unrestricted)
Result:   ✅ Success
```

### Test 2: Food Bank with Override
```
Location: P1400 (Second Harvest Food Bank)
Service:  S14999 (SHFB999-Undesignated Service) ← Override
Fund:     F8000 ← Override
Class:    0 (Unrestricted)
Result:   Expected to work (override enforces compatible combo)
```

---

## Practical Guidance for Dimensional Compatibility

### Rule 1: Location-Service Hierarchy

Services are often organized under specific program locations:
- **P1400** (Food Bank) → S14xxx services (Food Bank services)
- **P1600** (Shelter) → S16xxx services (Shelter services)
- **P1000** (Admin) → S10xxx services (Admin services)
- **P2000** (CADCA) → S20xxx services (Allentown services)
- **P3000** (CADCB) → S30xxx services (Bethlehem services)

### Rule 2: Use "Undesignated" Services for General Donations

Each program area has an "Undesignated" or "999" service code:
- **S10999** - Admin Undesignated
- **S14999** - Food Bank Undesignated
- **S16999** - Shelter Undesignated
- **S20999** - CADCA Undesignated
- **S30999** - CADCB Undesignated

These are safe defaults when specific service isn't known.

### Rule 3: Campaign Overrides Take Precedence

If a location has an override configured, it will:
- ✅ Use override Service code (ignores sub_solicit_code mapping)
- ✅ Use override Fund code (ignores solicit_code mapping)
- ✅ Guarantee dimensional compatibility

### Rule 4: Missing Mappings Skip Sync

Gifts will be skipped if any dimension mapping is missing:
- Campaign with no Location mapping
- Solicit code with no Fund mapping
- Sub-solicit with no Service mapping

Check logs for: `"Skipping gift - missing required dimensions"`

---

## Dimension Relationship Errors

**Error Pattern:**
```
"Fund 'XXX' specified is not valid"
"Service 'SXXXX' is not valid for Program 'PXXXX'"
"Location-Department combination not allowed"
```

**Solutions:**

1. **Add Campaign Override:** Force compatible Service+Fund for that Location
2. **Update Code Mappings:** Map to compatible dimension codes
3. **Check Sage Intacct Setup:** Verify dimension exists and is active
4. **Verify Restrictions:** Go to Company → Setup → Dimension Relationships

---

## How to Add Campaign Overrides

For programs that need specific Service/Fund combinations:

```sql
INSERT INTO campaign_service_overrides
(intacct_location_code, service_code, project_code, campaign_code, notes, is_active)
VALUES
('P1400', 'S14999', 'F8000', 'P1400', 'Food Bank - forced compatible dimensions', 1);
```

**Example Use Cases:**
- Food Bank campaigns (FB, SF, SHFB) → Always use P1400 + S14999 + F8000
- Shelter campaigns (SS, SSS) → Always use P1600 + S16999 + F8000
- Ensures accounting rules are followed regardless of DonorPerfect coding

---

## Class (Restriction) Dimension

**Hardcoded Value:** `classid = '4'` (Donor Restricted) — set per Accounting, 2026-07-29

All synced gifts use the Donor Restricted class, for both manual batches and regular
check-scan batches (both go through `transformGiftToOtherReceipt()`). Available class codes:

| ID | Name | Use Case |
|---|---|---|
| `0` | Unrestricted | General donations |
| `1` | Temporarily Restricted | Grant funds with time restrictions |
| `2` | Board Designated | Board-allocated funds |
| `3` | Permanently Restricted | Endowments |
| `4` | Donor Restricted | Donor-specified use (current default) |

**Deliberately hardcoded, not configurable.** Accounting confirmed (2026-07-29) they will only
ever need the single value. The unused `class_id` column on `sage_intacct_settings` (exposed as
"Class ID (Optional)" in the Sage config UI) was considered as a global setting and rejected: a
single settable value adds a way to break the sync without buying anything accounting asked for.

**If the restriction ever needs to vary,** the requirement will be per-record — certain DP coding
maps to a specific class — not a global toggle. That means:
- A mapping layer keyed on DP fields (campaign / solicit / sub-solicit), in the spirit of the
  existing `DonorPerfectCodeMapping` + `campaign_service_overrides` pattern
- Resolve the class per gift inside `transformGiftToOtherReceipt()` in
  `app/Services/SageIntacctSyncService.php`, replacing the hardcoded `classid` line
- Confirm each new Location + Service + Fund + Class combination is valid in Intacct's
  dimension relationships before syncing real gifts — GL 40100 rejects incompatible combos

---

## Summary

✅ **Total Mappings:**
- 15 Campaign → Location mappings
- 21 Solicit → Fund mappings (most → F8000)
- 46 Sub-Solicit → Service mappings

✅ **Campaign Overrides:**
- 1 active override (P1400 → S14999 + F8000)

✅ **Default Dimensions:**
- Fund: F8000 (most donations)
- Class: 4 (Donor Restricted)

✅ **Service Distribution:**
- S10999 (Admin): 12 sub-solicits
- S14999 (Food Bank): 23 sub-solicits
- S16999 (Shelter): 3 sub-solicits
- S10160 (Annual Meeting): 5 sub-solicits
- S20999 (CADCA): 1 sub-solicit

---

## Recommendations

1. **Add more overrides** for program-specific campaigns to prevent dimensional errors
2. **Test each major campaign** to verify dimensional compatibility
3. **Monitor sync logs** for "Skipping gift - missing required dimensions"
4. **Document any additional restrictions** discovered during testing
5. **Keep this document updated** when adding new mappings or overrides

---

**Query Commands:**
```bash
# Refresh dimension reference
php artisan intacct:query-dimensions

# Refresh this relationships document
php artisan intacct:query-relationships

# View current code mappings
php artisan tinker --execute="DB::table('donor_perfect_code_mappings')->get()"

# View campaign overrides
php artisan tinker --execute="DB::table('campaign_service_overrides')->where('is_active', true)->get()"
```
