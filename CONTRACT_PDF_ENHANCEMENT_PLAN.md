# Contract PDF Export Enhancement Plan

## Overview
The contract PDF export system already exists and includes:
- ✅ Export PDF button on contract view page
- ✅ Route configured: `contract.export-pdf`
- ✅ PDF export service with document conversion
- ✅ Controller handling the export

**What's Missing:** The PDF export currently does NOT include report due date template materials (the templates/requirements attached to the due dates themselves).

## Current PDF Export Structure

The existing PDF includes:
1. **Summary Page** - Contract details (title, number, amount, dates, etc.)
2. **Contract Documents** - Files attached to the contract
3. **Due Date Overview** - List of report due dates with status
4. **Report Submissions** - Final submitted reports with their files

## Required Enhancement

Add a new section between "Due Date Overview" and "Report Submissions":

**Due Date Template Materials** - Templates and requirement documents attached to each report due date

### Example Flow:
```
1. Contract Summary (page 1)
2. Contract Documents (pages 2-5)
3. Due Date Overview (page 6)
4. → NEW: Due Date Template Materials (pages 7-12)
   - Templates for "Monthly Report" due date
   - Templates for "Quarterly Report" due date
   - Templates for "Final Report" due date
5. Report Submissions (pages 13-20)
```

## Technical Implementation Plan

### File to Modify: `app/Services/ContractPdfExportService.php`

#### Change 1: Add new section to generate() method

**Location:** Around line 58-72

**Current code:**
```php
// Due date overview
$dueDateSummaries = $this->buildDueDateSummaries($contract);
if ($dueDateSummaries->isNotEmpty()) {
    $segments[] = $this->renderViewToPdf('exports.contract-pdf-due-dates', [
        'generatedAt' => $this->generationTimestamp,
        'contract' => $contract,
        'dueDateSummaries' => $dueDateSummaries,
    ]);
}

// Report submissions
$segments = array_merge($segments, $this->prepareReportSubmissionDocuments($contract));
```

**New code:**
```php
// Due date overview
$dueDateSummaries = $this->buildDueDateSummaries($contract);
if ($dueDateSummaries->isNotEmpty()) {
    $segments[] = $this->renderViewToPdf('exports.contract-pdf-due-dates', [
        'generatedAt' => $this->generationTimestamp,
        'contract' => $contract,
        'dueDateSummaries' => $dueDateSummaries,
    ]);
}

// Due date template materials (requirements/templates attached to due dates)
$segments = array_merge($segments, $this->prepareDueDateTemplateDocuments($contract));

// Report submissions
$segments = array_merge($segments, $this->prepareReportSubmissionDocuments($contract));
```

#### Change 2: Add new method prepareDueDateTemplateDocuments()

**Location:** After line 206 (after prepareReportSubmissionDocuments method)

**New method:**
```php
/**
 * Prepare report due date template/requirement documents for inclusion.
 *
 * @return array<int, string>
 */
protected function prepareDueDateTemplateDocuments(Contract $contract): array
{
    $segments = [];
    $counter = 1;

    $contract->reportDueDates
        ->sortBy('due_date')
        ->each(function ($dueDate) use (&$segments, &$counter) {
            // Get templates attached to this due date
            $templates = $dueDate->getMedia('templates');

            if ($templates->isEmpty()) {
                return; // Skip if no templates
            }

            $dueDateLabel = sprintf(
                '%s (%s)',
                $dueDate->report_type ? Str::headline($dueDate->report_type) : 'Report',
                $dueDate->due_date ? $dueDate->due_date->format('M j, Y') : 'No due date'
            );

            foreach ($templates as $media) {
                $label = sprintf('Template %02d - %s', $counter++, $dueDateLabel);

                $details = [
                    'Due Date' => $dueDate->due_date ? $dueDate->due_date->format('M j, Y') : 'N/A',
                    'Report Type' => $dueDateLabel,
                    'Template Description' => $dueDate->description ?: 'No description provided',
                    'Status' => Str::headline($dueDate->status ?? 'Unknown'),
                ];

                $conversion = $this->convertMediaContentToPdf($media, $label);

                if (! empty($conversion['paths'])) {
                    $segments[] = $this->renderDocumentCoverPage($label, $media, $details);
                    $segments = array_merge($segments, $conversion['paths']);
                } else {
                    $segments[] = $this->renderPlaceholderPage(
                        $label,
                        $media,
                        $conversion['placeholder'] ?? 'This template cannot be embedded in the PDF export.',
                        $details
                    );
                }
            }
        });

    return $segments;
}
```

#### Change 3: Update loadMissing() relationships

**Location:** Line 36-44

**Current code:**
```php
$contract->loadMissing([
    'funder',
    'program',
    'programs',
    'reportDueDates.contractReports.media',
    'media',
    'createdBy',
    'updatedBy',
]);
```

**New code:**
```php
$contract->loadMissing([
    'funder',
    'program',
    'programs',
    'reportDueDates.contractReports.media',
    'reportDueDates.media', // Add this to load due date templates
    'media',
    'createdBy',
    'updatedBy',
]);
```

## How It Works

### For Each Report Due Date:
1. Check if it has templates attached (stored in 'templates' media collection)
2. If templates exist, loop through each one
3. For each template:
   - Generate a cover page with metadata (due date, report type, description)
   - Convert the template file to PDF format (if possible)
   - If conversion fails (Excel, unusual file types), create a placeholder page with a link message
4. Add all pages to the PDF export

### Supported File Types:
- ✅ **PDF** - Embedded directly
- ✅ **Images** (PNG, JPG, GIF, BMP) - Converted to PDF pages
- ✅ **Word Docs** (DOC, DOCX, RTF, ODT) - Text extracted and rendered
- ✅ **Text Files** (TXT) - Formatted and rendered
- ✅ **Email Files** (EML) - Headers and body extracted
- ⚠️ **Excel/Spreadsheets** (XLS, XLSX, CSV) - Placeholder with download message
- ⚠️ **Outlook Messages** (MSG) - Placeholder with download message
- ⚠️ **Other types** - Placeholder with download message

### Placeholder Pages:
For files that can't be converted (Excel, MSG, etc.), the system will include a placeholder page that says:
> "Spreadsheet files such as 'Budget_Template.xlsx' should be downloaded separately to view the full workbook."

This ensures users know the document exists and where to find it, even if it can't be embedded.

## Testing Plan

### Test Cases:

1. **Contract with no due dates**
   - PDF should generate normally with just contract info and documents

2. **Contract with due dates but no templates**
   - PDF should include due date overview but skip template section

3. **Contract with PDF templates**
   - PDF templates should be embedded after due date overview

4. **Contract with Word doc templates**
   - Word docs should be converted to text and included

5. **Contract with Excel templates**
   - Should show placeholder page with message about downloading separately

6. **Contract with mixed file types**
   - Should handle all types appropriately (embed or placeholder)

7. **Large contracts**
   - Test with 10+ documents, 5+ due dates, 20+ templates to ensure performance

## File Changes Summary

### Modified Files:
1. `app/Services/ContractPdfExportService.php`
   - Add new method `prepareDueDateTemplateDocuments()`
   - Update `generate()` method to include template section
   - Update relationship loading to include due date media

### No New Files Required:
- All blade templates already exist
- All helper methods already exist
- Route and controller already configured

## Benefits

1. **Complete Contract Package** - All materials in one PDF
2. **Organized Structure** - Templates appear before submissions (logical flow)
3. **Graceful Degradation** - Unsupported files show helpful messages with links
4. **No Breaking Changes** - Existing functionality remains intact
5. **Consistent Design** - Uses existing PDF rendering patterns

## Estimated Impact

- **Code Changes:** ~60 lines of new code
- **Breaking Changes:** None
- **Performance Impact:** Minimal (uses existing conversion methods)
- **Database Changes:** None (uses existing media library structure)

## Questions to Consider

1. **Ordering:** Should templates be grouped by due date (current plan) or listed chronologically by due date?
2. **Section Header:** Should we add a section header page that says "Report Due Date Templates" before listing them?
3. **Empty Templates:** Current plan skips due dates with no templates. Is this correct, or should we show "No templates attached"?
4. **Cover Pages:** Each template gets a cover page with metadata. Is this too verbose, or just right?

## Ready to Implement?

Once you approve this plan, I will:
1. Make the changes to `ContractPdfExportService.php`
2. Test the export with a sample contract
3. Verify all file types handle correctly
4. Confirm the PDF generates successfully

---

**Current Status:** ✅ Plan complete, awaiting your approval to proceed with implementation.
