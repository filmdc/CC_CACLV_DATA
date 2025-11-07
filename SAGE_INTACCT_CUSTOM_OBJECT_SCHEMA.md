# Sage Intacct Custom Object Schema Reference
## "Donor Perfect Gift" Object Definition

**Created:** October 19, 2025
**Environment:** CACLV-sandbox
**Integration Name:** `gifts`
**Purpose:** A feed of available DP gifts to import as an invoice to AR or to cash management as other receipts.

---

## Object Properties

| Property | Value |
|----------|-------|
| **Singular Name** | Donor Perfect Gift |
| **Plural Name** | Donor Perfect Gifts |
| **Record Name** | Gift |
| **Name Template** | {firstName} {lastName} |
| **Integration Name** | `gifts` |
| **Default Process** | Default |
| **Audit Trail** | ✅ Enabled |
| **Flagging** | ❌ Not enabled |
| **Views/Dashboard Tracking** | ✅ Enabled |
| **GL Dimension** | ❌ Not enabled |
| **Deployed** | ✅ Yes |
| **Is Custom Object** | ✅ Yes |
| **Number of Records** | 0 (as of 10/19/2025) |

---

## Field Schema (42 Fields Total)

### Financial Fields

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Amount | `amount` | Currency | Gift amount | ✅ |
| Gift Payment Type | `gift_payment_type` | Text | Payment method (Check, Cash, Credit, etc.) | |
| Gift Type | `gift_type` | Text | Type of gift | |

### Gift Identification

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Gift | `name` | Record name | Display name (firstName lastName) | ✅ |
| ID | `id` | Integer | Sage Intacct record ID (auto) | System |
| Donor Perfect Gift ID | `donor_perfect_gift_id` | Integer | Original DP gift ID | ✅ |
| Link to DP Gift Page | `dp_gift_page` | Integration link | URL to DP gift record | |

### Dates

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Gift Date | `gift_date` | Date | Date gift was received | ✅ |
| Created at | `createdAt` | Date/time | Record creation timestamp | System |
| Updated at | `updatedAt` | Date/time | Last modified timestamp | System |

### Donor Information

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Donor ID | `donor_id` | Integer | DonorPerfect donor ID | ✅ |
| First name | `firstName` | Text (50) | Donor first name | |
| Middle name | `middleName` | Text (50) | Donor middle name | |
| Last name | `lastName` | Text (50) | Donor last name | |
| Title | `title` | Text (50) | Salutation/title | |
| Email address | `email` | Email address | Contact email | |
| Phone | `phone` | Text (50) | Primary phone | |
| Mobile phone | `mobilePhone` | Text (50) | Mobile/cell phone | |
| Fax | `fax` | Text (50) | Fax number | |

### Donor Address

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Street address 1 | `streetAddr1` | Text (100) | Address line 1 | |
| Street address 2 | `streetAddr2` | Text (100) | Address line 2 | |
| City | `city` | Text (100) | City | |
| State or territory | `state` | Dependent picklist | State/province | |
| ZIP or postal code | `zip` | Text (20) | Postal code | |
| Country | `country` | Picklist | Country | |

### Coding/Classification

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Gift Campaign | `gift_campaign` | Text | Campaign code | |
| Gift GL Code | `gift_gl_code` | Text (5) | General Ledger code | ✅ |
| Gift Solicitation Code | `gift_solicitation_code` | Text | Solicitation code | |
| Gift Sub-Solicitation Code | `gift_sub_solicitation_code` | Text | Sub-solicitation code | |

### Attachments & Documents

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Check Image Path | `check_image_path` | Text | Path/URL to check image | |
| Document file | `file` | File upload | Attached documents | |
| vCard file | `vCard` | vCard | Contact vCard | |

### System/Metadata

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Created by | `createdBy` | User link | User who created record | System |
| Updated by | `updatedBy` | User link | User who last modified | System |
| Comments | `comment` | Text area | General comments/notes | |
| Description | `description` | Text area | Detailed description | |
| Record URL | `RECORD_URL` | Text | URL to this record | System |

### Workflow

| Field Label | Integration Name | Data Type | Description | Required |
|-------------|------------------|-----------|-------------|----------|
| Workflow actions | `actions` | Workflow actions | Available workflow actions | System |
| Workflow process | `process` | Process | Current workflow process | System |
| Workflow status | `status` | Status | Current status in workflow | System |

---

## Relationships

**Currently Defined:** None

**Potential Future Relationships:**
- Link to AR Invoice (when converted)
- Link to Cash Receipt (when converted)
- Link to GL Transaction
- Link to Customer/Donor entity

---

## Pages/Forms Available

| Page Name | Type | Purpose |
|-----------|------|---------|
| Donor Perfect Gifts list | Object | Main list view |
| Donor Perfect Gifts selector list | Selector | Selection interface |
| Edit Donor Perfect Gift | Edit | Edit individual record |
| Import Donor Perfect Gifts | Import | Bulk import interface |
| Mass update | Mass update | Bulk editing |
| New Donor Perfect Gift | New | Create new record |
| Quick create | Quick create | Fast record creation |
| Status change | Status | Change workflow status |
| View Donor Perfect Gift | View | Read-only view |

---

## Views Available

| View Name | Order | Hidden | Private |
|-----------|-------|--------|---------|
| All Donor Perfect Gifts | 1 | No | No |
| Default Feed | 2 | No | No |
| Unselected | 3 | No | No |

---

## Integration Details

**Integration Name:** `gifts`
**API Access:** Available via REST API at `/v1/companies/{companyId}/objects/gifts`

**XML Web Services Access:**
```xml
<create>
    <gifts>
        <amount>150.00</amount>
        <gift_date>2025-11-07</gift_date>
        <donor_perfect_gift_id>196421</donor_perfect_gift_id>
        <!-- additional fields -->
    </gifts>
</create>
```

---

## Field Mapping Recommendations

Based on DonorPerfect gift data structure, here are the recommended field mappings:

| DonorPerfect Field | Sage Intacct Field | Notes |
|-------------------|-------------------|-------|
| `gift_id` | `donor_perfect_gift_id` | Primary identifier ✅ CRITICAL |
| `gift_date` | `gift_date` | Transaction date ✅ CRITICAL |
| `amount` | `amount` | Gift amount ✅ CRITICAL |
| `donor_id` | `donor_id` | Donor identifier ✅ CRITICAL |
| `first_name` | `firstName` | Donor first name |
| `last_name` | `lastName` | Donor last name |
| `email` | `email` | Contact email |
| `phone` | `phone` | Primary phone |
| `address` | `streetAddr1` | Street address |
| `city` | `city` | City |
| `state` | `state` | State/province |
| `zip` | `zip` | Postal code |
| `campaign` | `gift_campaign` | Campaign code |
| `gl_code` | `gift_gl_code` | GL code ✅ CRITICAL |
| `solicit_code` | `gift_solicitation_code` | Solicitation |
| `sub_solicit_code` | `gift_sub_solicitation_code` | Sub-solicitation |
| `payment_type` | `gift_payment_type` | Payment method |
| `gift_type` | `gift_type` | Gift type |
| `IMAGE_CHECK` | `check_image_path` | Check image URL/path |

---

## Data Validation Notes

### Required Fields (Minimum for Successful Sync)
1. ✅ `donor_perfect_gift_id` - Must be unique
2. ✅ `gift_date` - Valid date format
3. ✅ `amount` - Numeric, positive value
4. ✅ `gift_gl_code` - Must match valid GL codes in Sage Intacct

### Field Length Limits
- Text fields: Vary (5-100 characters)
- GL Code: Max 5 characters
- State: Must match Sage Intacct state picklist values
- Country: Must match Sage Intacct country picklist values

### Picklist Fields (Must Match Sage Values)
- `state` - Must use Sage Intacct state codes
- `country` - Must use Sage Intacct country codes

---

## Workflow Process

**Default Process:** Default
**Current Status Values:** (Not shown in screenshots - check Sage Intacct UI)

**Recommended Workflow:**
1. **Pending Review** - Initial sync status
2. **Reviewed** - Accounting staff has reviewed
3. **Converted to Invoice** - Moved to AR
4. **Converted to Receipt** - Moved to cash management
5. **Archived** - Processing complete

---

## Usage Notes

### Creating Records via API

**Required Fields for API Creation:**
- `donor_perfect_gift_id` (Integer)
- `gift_date` (Date: YYYY-MM-DD format)
- `amount` (Decimal: 123.45)
- `gift_gl_code` (Text: 5 chars max)

**Optional but Recommended:**
- `donor_id`, `firstName`, `lastName` (for identification)
- `gift_campaign`, `gift_solicitation_code` (for tracking)
- `gift_payment_type` (for processing)

### Special Considerations

**Check Image Path:**
- Stores URL or path to check image
- Consider using Sage Intacct's document attachment feature for actual files
- Currently just stores path as text

**Link to DP Gift Page:**
- Integration link field
- Can create hyperlink back to DonorPerfect

**Workflow Status:**
- Use to track which gifts have been processed
- Accounting staff can change status as they convert records

---

## Current Configuration in CAP TRACK

**Custom Object Name Setting:** `Donor Perfect Gifts`
**Auth Method:** XML Web Services
**Company:** CACLV-sandbox

**Next Steps:**
1. Configure field mappings in Field Mappings tab
2. Test sync with single record
3. Verify data appears correctly in Sage Intacct
4. Establish workflow for accounting staff review
5. Document conversion process (Gift → Invoice/Receipt)

---

## Record Count & Status

**Current Records:** 0 (sandbox is empty)
**Last Updated:** October 19, 2025
**Updated By:** dchevalier

---

## Support & References

**Sage Intacct Path:** Platform Services → Custom Objects → "Donor Perfect Gift"
**API Endpoint:** `/v1/companies/CACLV-sandbox/objects/gifts`
**XML Element:** `<gifts>...</gifts>`
