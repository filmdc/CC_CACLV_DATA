# Participant Import Troubleshooting Guide

## Common Import Issues and Solutions

### 1. Template Validation Errors

**Problem**: The downloaded template fails validation when uploaded.

**Solution**: Ensure you're using the latest template version. The template has been updated to match CSBG-compliant values exactly.

**Example Values That Work**:
```csv
ethnicity: "Not Hispanic, Latino or Spanish Origins"
work_status: "Employed Full-Time"
military_status: "Never Served in the Military"
education_level: "High School Graduate"
```

### 2. "Invalid Value" Errors

**Problem**: Import fails with "Invalid value for field" errors.

**Solution**: 
1. Check the error message which now shows valid options
2. Use the Reference Data Set dropdown to select appropriate validation rules for historical data
3. Enable "Skip records with errors and continue" to process valid records

**Example Error Message**:
```
Row 5: Invalid value for 'ethnicity': 'Hispanic'. 
Valid options are: Hispanic, Latino or Spanish Origins; Not Hispanic, Latino or Spanish Origins
```

### 3. Large File Performance Issues

**Problem**: Import times out or fails for large CSV files.

**Solution**: The system now uses chunked processing. Files are processed in batches of 1,000 records. If timeouts still occur:
1. Split your file into smaller chunks (< 50,000 records each)
2. Contact your administrator to increase timeout limits

### 4. Character Encoding Issues

**Problem**: Special characters appear garbled or cause import failures.

**Solution**: 
1. Save your CSV file with UTF-8 encoding
2. In Excel: Save As > CSV UTF-8 (Comma delimited)
3. In Google Sheets: File > Download > Comma-separated values (.csv)

### 5. Date Format Issues

**Problem**: Birth dates or other dates fail validation.

**Solution**: Use ISO format: YYYY-MM-DD (e.g., 1990-01-15)

**Common incorrect formats**:
- 01/15/1990 ❌
- 15-Jan-1990 ❌
- 1990-01-15 ✅

### 6. Missing Required Fields

**Problem**: Import fails due to missing required fields.

**Required Fields for Participants**:
- household_code (only required for HEAD_OF_HOUSEHOLD, optional for FAMILY_MEMBER)
- participant_type
- first_name
- last_name
- date_of_birth
- gender
- race
- ethnicity

**Solution**: Ensure all required fields have values. Use the template as a guide.

**Household Code Behavior**:
- HEAD_OF_HOUSEHOLD: Must have a household_code
- FAMILY_MEMBER: Can leave household_code empty - will inherit from the preceding head of household
- Export: All participants will have their household_code included for clarity

### 7. Duplicate Records

**Problem**: Import creates duplicate participants.

**Solution**: 
1. Enable "Update existing records if found" option
2. System matches on: first_name + last_name + date_of_birth
3. For households: matches on household_code

### 8. Reference Data Set Selection

**Problem**: Historical data uses different enum values than current system.

**Solution**: 
1. Before validating, select the appropriate Reference Data Set from the dropdown
2. Choose "Historical Import (Pre-2023)" for older data formats
3. The system will validate against the selected set's values

### 9. Transformation Preview

**Problem**: Unsure how data will be transformed during import.

**Solution**: 
1. After successful validation, click "Preview Transformations"
2. Review how values will be mapped to CSBG-compliant formats
3. Common transformations:
   - "Not Hispanic or Latino" → "Not Hispanic, Latino or Spanish Origins"
   - "Employed full-time" → "Employed Full-Time"
   - "High school diploma/GED" → "High School Graduate"

### 10. Rate Limiting

**Problem**: "Too many import attempts" error.

**Solution**: 
1. System allows 5 imports per hour per user
2. Wait for the cooldown period
3. Contact administrator for increased limits if needed

## Import Process Checklist

1. **Download Latest Template** ✓
2. **Fill Data Following Examples** ✓
3. **Save as UTF-8 CSV** ✓
4. **Select Reference Data Set** (if using historical data) ✓
5. **Upload and Validate** ✓
6. **Review Validation Results** ✓
7. **Preview Transformations** (optional) ✓
8. **Configure Import Options** ✓
9. **Process Import** ✓
10. **Review Import Results** ✓

## Advanced Tips

### Using Custom Import Templates
For HMIS or RX Office imports, use the Custom Import tab:
1. Select the appropriate template
2. Upload your file
3. System automatically maps fields

### Monitoring Import Progress
- Check Import History tab for detailed logs
- Click "View Errors" for specific issues
- All imports are audited for compliance

### Performance Optimization
- Import during off-peak hours for faster processing
- Files under 10,000 records process fastest
- Consider splitting very large files

## Getting Help

If issues persist:
1. Check the Import History for specific error details
2. Download a fresh template to ensure format compliance
3. Contact your system administrator with:
   - Import audit log ID
   - Screenshot of error message
   - Sample of problematic data (anonymized)

## Security Notes

- All imports are logged with user, timestamp, and IP address
- Files are scanned for malicious content
- Temporary files are automatically deleted after processing
- PII is never logged in error messages