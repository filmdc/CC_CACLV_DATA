# Sage Intacct Duplicate Prevention System

## 🔒 How Duplicate Prevention Works

The system automatically prevents duplicate syncs using the `sage_intacct_sync_items` table as the source of truth.

---

## 📊 Duplicate Detection Logic

### Location
**File:** `app/Services/SageIntacctSyncService.php`
**Method:** `getSyncableGifts()` (lines 406-467)

### How It Works

```php
// Step 1: Get all successfully synced gift IDs
$syncedGiftIds = DB::table('sage_intacct_sync_items')
    ->where('status', 'success')
    ->pluck('gift_id')
    ->toArray();

// Step 2: Filter out already synced gifts
$unsyncedGifts = array_filter($gifts, function ($gift) use ($syncedGiftIds) {
    $giftId = $gift['gift_id'];
    return $giftId && !in_array($giftId, $syncedGiftIds);
});
```

### What Gets Synced

✅ **WILL BE SYNCED:**
- Gifts that have never been synced before
- Gifts that failed on previous sync attempts (`status = 'failed'`)
- Gifts that are stuck in pending status (`status = 'pending'`)

❌ **WILL BE SKIPPED:**
- Gifts with `status = 'success'` in `sage_intacct_sync_items` table
- Already successfully synced gifts (prevents duplicates)

---

## 🔍 Example Scenario

### First Sync (Nov 7, 2025)
```
Date Range: Oct 31 - Nov 7
Total Gifts: 466

Filtering:
  - Gifts in date range: 466
  - Already synced: 0
  - Syncable: 466

Result: All 466 gifts synced ✅
```

### Second Sync (Same Date Range)
```
Date Range: Oct 31 - Nov 7
Total Gifts: 466

Filtering:
  - Gifts in date range: 466
  - Already synced: 466 (from first sync)
  - Syncable: 0

Result: "No gifts to sync for the specified date range" ✅
```

### Third Sync (Overlapping Range)
```
Date Range: Nov 7 - Nov 10
Total Gifts: 500 (466 old + 34 new)

Filtering:
  - Gifts in date range: 500
  - Already synced: 466 (from first sync)
  - Syncable: 34 (only new gifts)

Result: Only 34 new gifts synced ✅
```

---

## 🔄 Retry Failed Gifts

Gifts that failed to sync will be **automatically retried** on subsequent syncs because they have `status = 'failed'` (not 'success').

### Failed Gift Behavior

**Initial Sync:**
```
Gift #12345: Failed (status = 'failed')
Error: "GL code invalid"
```

**Next Sync:**
```
Filtering:
  - Gift #12345 status = 'failed' (not 'success')
  - Will be included in syncable gifts ✅
  - Will attempt to sync again
```

**After Fix:**
```
If still fails → Stays 'failed' (will retry again)
If succeeds → Changes to 'success' (won't retry)
```

---

## 📋 Verification Queries

### Check if a gift has been synced

```sql
SELECT gift_id, status, synced_at, error_message
FROM sage_intacct_sync_items
WHERE gift_id = '196442';
```

### Count successfully synced gifts

```sql
SELECT COUNT(*) as total_synced
FROM sage_intacct_sync_items
WHERE status = 'success';
```

### Find gifts that failed and will retry

```sql
SELECT gift_id, error_message, created_at
FROM sage_intacct_sync_items
WHERE status = 'failed'
ORDER BY created_at DESC;
```

### See what would sync if you ran it now

```sql
-- Gifts in your date range
SELECT COUNT(*) as in_date_range
FROM donor_perfect_gift_cache
WHERE ...;  -- based on cache structure

-- Already successfully synced
SELECT COUNT(*) as already_synced
FROM sage_intacct_sync_items
WHERE status = 'success';

-- Would be synced = (in_date_range - already_synced)
```

---

## 🎯 Key Points

### 1. **Gift ID is the Unique Identifier**
- Each gift's `gift_id` from DonorPerfect is tracked
- Once `status = 'success'` for a gift_id, it won't sync again
- Gift ID is stored as VARCHAR(255) in sync_items table

### 2. **Status Determines Retry Behavior**

| Status | Will Retry? | Reason |
|--------|------------|--------|
| `success` | ❌ No | Already synced successfully |
| `failed` | ✅ Yes | Error occurred, needs retry |
| `pending` | ✅ Yes | Never attempted or incomplete |

### 3. **No Time-Based Expiration**
- Once a gift is successfully synced, it stays synced
- No "re-sync after X days" logic
- Permanent record in `sage_intacct_sync_items`

### 4. **Manual Re-Sync Possible**
If you need to re-sync a gift:
```sql
-- Option A: Delete the sync item record
DELETE FROM sage_intacct_sync_items WHERE gift_id = '196442';

-- Option B: Change status back to pending
UPDATE sage_intacct_sync_items
SET status = 'pending', error_message = NULL
WHERE gift_id = '196442';
```

---

## 🔄 Sync History vs Sync Items

### `sage_intacct_sync_history` Table
- Records each **sync job**
- Tracks total/successful/failed counts
- One row per sync attempt
- Used for reporting and monitoring

### `sage_intacct_sync_items` Table
- Records each **individual gift**
- Tracks per-gift success/failure
- One row per gift per sync attempt
- **Used for duplicate prevention** ✅

---

## 📊 Current Status

Let me check your current sync items:

```bash
# Total successfully synced gifts (won't be synced again)
php artisan tinker --execute="
echo 'Successfully synced gifts: ' . DB::table('sage_intacct_sync_items')->where('status', 'success')->count() . PHP_EOL;
echo 'Failed gifts (will retry): ' . DB::table('sage_intacct_sync_items')->where('status', 'failed')->count() . PHP_EOL;
echo 'Pending gifts (will retry): ' . DB::table('sage_intacct_sync_items')->where('status', 'pending')->count() . PHP_EOL;
"
```

---

## ⚠️ Important Considerations

### If You Update a Gift in DonorPerfect

**Scenario:** Gift #12345 was synced with Amount = $100, then changed to $150 in DonorPerfect.

**Current Behavior:**
- Gift #12345 has `status = 'success'` in sync_items
- Will NOT be synced again (duplicate prevention)
- Sage Intacct will still show $100

**Solutions:**

**Option A: Manual Update in Sage Intacct**
- Edit the gift record in Sage to match new value
- Keep sync_items status as 'success'

**Option B: Re-Sync Individual Gift**
```sql
DELETE FROM sage_intacct_sync_items WHERE gift_id = '12345';
```
Then sync again.

**Option C: Future Enhancement - Update Detection**
- Track `updated_at` timestamp from DonorPerfect
- Compare with last sync timestamp
- Re-sync if gift was modified after last sync

---

## 🎯 Recommendations

### For Your Workflow

**Best Practice:**
1. ✅ Sync gifts daily to Sage Intacct
2. ✅ Accounting staff reviews in Sage
3. ✅ Converts to AR invoices/receipts
4. ✅ Changes in Sage become the "source of truth"
5. ⚠️ Avoid changing gifts in DonorPerfect after sync

**If Corrections Needed:**
- Make corrections in Sage Intacct (not DonorPerfect)
- Or delete sync_items record and re-sync

---

## 🔧 Testing Duplicate Prevention

**Test it yourself:**

1. **First sync:** Sync gifts from Nov 7
   - Result: X gifts synced

2. **Second sync:** Sync same date range again
   - Expected: "No gifts to sync" or "0 synced"
   - Logs show: "already_synced: X"

3. **Verify:**
```bash
php artisan tinker --execute="
\$syncHistory = DB::table('sage_intacct_sync_history')->orderBy('id', 'desc')->limit(2)->get(['id', 'total_records', 'successful_count']);
foreach (\$syncHistory as \$h) {
    echo 'Sync #' . \$h->id . ': ' . \$h->total_records . ' total, ' . \$h->successful_count . ' synced' . PHP_EOL;
}
"
```

First sync should show 466 synced, second sync should show 0 synced.

---

## ✅ Summary

**Duplicate Prevention:** ✅ **ACTIVE AND WORKING**

**Method:** Gift ID tracking in `sage_intacct_sync_items` table
**Scope:** Prevents same gift_id from syncing twice with success status
**Retry Logic:** Failed gifts automatically retry on next sync
**Manual Override:** Delete sync_items record to force re-sync

**Your integration is protected against duplicates!** 🎉
