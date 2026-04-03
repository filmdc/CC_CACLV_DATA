# DonorPerfect Sync - Quick Reference for Accounting

## How the Gift Feed Works

The DonorPerfect Gifts Feed pulls gift data directly from the DonorPerfect API. To keep page interactions fast (filtering, sorting, paging), data is briefly cached for **5 minutes** after each fetch. After 5 minutes the next page load automatically pulls fresh data from DonorPerfect.

## Getting Fresh Data

### Automatic Refresh
- Data refreshes automatically every 5 minutes.
- Changing the **start date** or **end date** immediately fetches fresh data for the new range.

### Manual Refresh
Click the **"Refresh Data"** button (blue button, top-right of the Gifts tab) at any time to force an immediate pull from DonorPerfect. Use this when:
- You just entered or edited gifts in DonorPerfect and want to see them right away.
- A batch was just posted and isn't showing yet.
- You need to confirm that a code change in DP took effect.

## When Changes Show Up

| What Changed | When It Appears in the Feed |
|---|---|
| New gift entered in DP | Within 5 min, or immediately after clicking Refresh Data |
| Gift amount/code edited in DP | Within 5 min, or immediately after clicking Refresh Data |
| New batch posted in DP | Within 5 min, or immediately after clicking Refresh Data |
| Code mapping updated (Intacct codes tab) | Immediately - no refresh needed |
| Conditional mapping updated | Immediately - no refresh needed |
| Date range changed | Immediately - always fetches fresh data |

## Manual Batch Sync Page

The Manual Batch Sync page always requests fresh data from DonorPerfect when you:
- Select or change a date
- Click "Load Preview"

No extra refresh step is needed on this page.

## Tips

- **Don't see a batch?** Click "Refresh Data" first. If it still doesn't appear, verify the gift date in DonorPerfect matches the date range you have selected in the feed.
- **Code mapping change not reflected in the sync?** Code mappings (the Intacct code column) update instantly when you save them. If the underlying DP code itself was changed in DonorPerfect, click "Refresh Data" to pull the updated DP code.
- **Filters hiding your data?** Check the filter dropdowns above the table. Click "Clear All Filters" to reset everything.
- **Need data for a different date range?** Just change the start/end dates - fresh data loads automatically.

## Contact

If the feed is not returning data at all or you see an error message, contact your system administrator. Common issues include an expired DonorPerfect API key or a network connectivity problem.
