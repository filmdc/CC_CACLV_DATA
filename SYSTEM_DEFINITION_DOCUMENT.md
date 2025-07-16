# CAP TRACK System Definition Document
## Data Specifications and Import/Export Standards

**Version:** 1.0  
**Last Updated:** January 2025  
**System:** CAP TRACK - Community Action Partnership Data Management Platform

---

## Table of Contents

1. [Overview](#overview)
2. [RX Office Housing Counseling Import](#rx-office-housing-counseling-import)
3. [HMIS Pennsylvania Import](#hmis-pennsylvania-import)
4. [CSBG Data Collection and Reporting](#csbg-data-collection-and-reporting)
5. [Data Transformation Rules](#data-transformation-rules)
6. [Validation Requirements](#validation-requirements)

---

## Overview

This document defines the data specifications for three critical components of the CAP TRACK system:
- **RX Office Housing Counseling Import**: Integration with PHFA's RX Office system for housing counseling data
- **HMIS Pennsylvania Import**: Integration with Pennsylvania's ClientTrack HMIS for homeless services data
- **CSBG Data Collection**: Standardized data collection for Community Services Block Grant reporting

All imports support both manual CSV uploads and automated processing, with comprehensive validation and duplicate detection.

---

## RX Office Housing Counseling Import

### Overview
The RX Office import supports two distinct file formats from PHFA's RX Office system:
1. **Detailed Case Information**: Client-level counseling case data
2. **Borrower Class Registration**: Group education class attendance data

### Format 1: Detailed Case Information

#### Required Fields
| Field Name | Data Type | Format | Description | Validation |
|------------|-----------|--------|-------------|------------|
| Case ID | String | Alphanumeric | Unique case identifier | Required, unique |
| Case Type | String | Text | Type of counseling service | Required |
| Case Open Date | Date | MM/DD/YYYY | Service enrollment date | Required, valid date |
| Borrower First Name | String | Text | Client's first name | Required, max 255 chars |
| Borrower Last Name | String | Text | Client's last name | Required, max 255 chars |
| Borrower Birthdate | Date | MM/DD/YYYY | Date of birth | Optional, valid date |
| Borrower Family Size | Integer | Number | Household member count | Optional, min 1 |
| Borrower Gender | String | Text | Gender identity | Optional, see mappings |
| Borrower Race | String | Text | Racial identity | Optional, see mappings |
| Borrower Ethnicity | String | Text | Hispanic/Latino status | Optional, see mappings |
| Borrower Monthly Income Gross | Decimal | Number | Monthly gross income | Optional, min 0 |
| Military Status | String | Text | Veteran/military status | Optional, see mappings |
| Disabled | String | Yes/No | Disability status | Optional |
| Owner Occupied | String | Yes/No | Housing ownership status | Optional |

#### Optional Fields
| Field Name | Data Type | Format | Description |
|------------|-----------|--------|-------------|
| Borrower Dependents | Integer | Number | Number of dependents |
| Borrower Household Type | String | Text | Household composition |
| AMI | String | Percentage | Area Median Income level |
| Assigned Agent | String | Text | Counselor name |
| Counseling Type | String | Text | Specific counseling category |
| Borrower Total Gross Income | Decimal | Number | Annual gross income |
| Household Income Gross | Decimal | Number | Total household income |
| Counseling Outcome | String | Text | Service outcome |
| Employment Status | String | Text | Current employment status |
| Occupation | String | Text | Job title/field |

### Format 2: Borrower Class Registration

#### Required Fields
| Field Name | Data Type | Format | Description | Validation |
|------------|-----------|--------|-------------|------------|
| Class Name | String | Text | Education class title | Required |
| Client Name | String | Text | Full name of attendee | Required |
| Client Registration Date | Date | MM/DD/YYYY | Enrollment date | Required, valid date |
| Class Date | Date | MM/DD/YYYY | Date class was held | Required, valid date |

#### Optional Fields
| Field Name | Data Type | Format | Description |
|------------|-----------|--------|-------------|
| Class ID | String | Alphanumeric | Unique class identifier |
| Class Status | String | Text | Active/Completed/Cancelled |
| Class Hours | Decimal | Number | Duration in hours |
| Instructor | String | Text | Teacher/facilitator name |
| HUD Category | String | Text | HUD education category |
| Attendance | String | Text | Attended/No Show/Excused |
| Location | String | Text | Class venue |
| Intake Client ID | String | Alphanumeric | Client system ID |
| Client Address | String | Text | Full address |
| Client Phone Number | String | Phone | Contact number |
| Client Email | String | Email | Email address |
| Birth Date | Date | MM/DD/YYYY | Date of birth |
| Family Size | Integer | Number | Household size |
| Monthly Income | Decimal | Number | Monthly gross income |
| Race | String | Text | Racial identity |
| Ethnicity | String | Text | Hispanic/Latino status |
| Gender | String | Text | Gender identity |
| Disabled | String | Yes/No | Disability status |
| Veteran | String | Yes/No | Veteran status |
| Referral Source | String | Text | How client found service |

### Service Type Mappings
- `PHFA Default And Delinquency Counseling` → `Default & Delinquency Counseling`
- `PHFA Pre-Closing Seminar` → `Pre-Closing Seminar`
- `PHFA Pre-Purchase One on One counseling` → `Pre-Purchase Counseling`

---

## HMIS Pennsylvania Import

### Overview
Imports data from Pennsylvania's ClientTrack HMIS system using the "Active Client List" export format. This import is specifically configured for Sixth Street Shelter program enrollments.

### Required Fields
| Field Name | Data Type | Format | Description | Validation |
|------------|-----------|--------|-------------|------------|
| CaseID | String | Alphanumeric | Household identifier | Required, creates household code |
| ClientName | String | "Last, First Middle" | Full name in specific format | Required, parsed into components |
| EnrollDate | DateTime | M/D/YYYY H:i:s A | Program enrollment timestamp | Required, valid datetime |

### Optional Fields
| Field Name | Data Type | Format | Description | Transformation |
|------------|-----------|--------|-------------|---------------|
| OrganizationName | String | Text | Service provider name | Reference only |
| ProgramName | String | Text | Specific program/service | Maps to service enrollment |
| DOB | Date | M/D/YYYY | Date of birth | Converted to YYYY-MM-DD |
| Gender | String | Text | Gender identity | See gender mappings |
| RaceAndEthnicity | String | Text | Combined race/ethnicity | Parsed into separate fields |
| VeteranStatus | String | Text | Military service status | See military mappings |
| Relationship | String | Text | Household relationship | Determines head of household |
| HouseholdType | String | Text | Household composition | See household mappings |
| HealthInsurance | String | Yes/No/Unknown | Insurance coverage | Mapped to status field |
| DisablingCondition | String | Yes/No/Unknown | Disability status | Mapped to boolean |
| CashIncome | Decimal | Number | Monthly income amount | Numeric validation |
| ExitDate | DateTime | M/D/YYYY H:i:s A | Program exit timestamp | Valid datetime or null |
| ClientID | String | Alphanumeric | HMIS client identifier | Stored for reference |
| EnrollID | String | Alphanumeric | HMIS enrollment ID | Stored for reference |

### Special Processing Rules
1. **Name Parsing**: ClientName in "Last, First Middle" format is parsed into separate fields
2. **Race/Ethnicity Parsing**: Combined field is split based on standard HMIS categories
3. **Head of Household**: Determined by "Relationship" field value (e.g., "Self" = head)
4. **Program Assignment**: All records import to Program ID 4 (Sixth Street Shelter)

---

## CSBG Data Collection and Reporting

### Overview
The Community Services Block Grant (CSBG) requires standardized demographic and characteristic reporting following federal guidelines. This data is collected at both individual and household levels.

### Individual-Level Characteristics

#### 1. Gender Categories
- `Male`
- `Female`
- `Other`
- `Unknown/not reported`

#### 2. Age Groups (CSBG Standard)
- `0-5` (Ages 0-5)
- `6-13` (Ages 6-13)
- `14-17` (Ages 14-17)
- `18-24` (Ages 18-24)
- `25-44` (Ages 25-44)
- `45-54` (Ages 45-54)
- `55-59` (Ages 55-59)
- `60-64` (Ages 60-64)
- `65-74` (Ages 65-74)
- `75+` (Ages 75 and over)
- `Unknown/not reported`

#### 3. Education Levels
- `Grades 0-8`
- `Grades 9-12/Non-Graduate`
- `High School Graduate`
- `GED/Equivalency Diploma`
- `12 grade + Some Post-Secondary`
- `2 or 4 years College Graduate`
- `Graduate of other post-secondary school`
- `Unknown/not reported`

#### 4. Disconnected Youth
- Youth ages 14-24 who are neither working nor in school
- Calculated field based on age, work_status, and education enrollment

#### 5. Health Status
**Disabling Condition:**
- `Yes`
- `No`
- `Unknown`

**Health Insurance Status:**
- `Yes`
- `No`
- `Unknown`

**Health Insurance Sources (if Yes):**
- `Medicaid`
- `Medicare`
- `State Children's Health Insurance Program`
- `State Health Insurance for Adults`
- `Military Health Care`
- `Direct-Purchase`
- `Employment Based`
- `Unknown/not reported`

#### 6. Ethnicity
- `Hispanic, Latino or Spanish Origins`
- `Not Hispanic, Latino or Spanish Origins`
- `Unknown/not reported`

#### 7. Race (Multiple Selection Allowed)
- `American Indian or Alaska Native`
- `Asian`
- `Black or African American`
- `Native Hawaiian and Other Pacific Islander`
- `White`
- `Other`
- `Multi-race` (two or more of the above)
- `Unknown/not reported`

#### 8. Military Status
- `Veteran`
- `Active Military`
- `Never Served in the Military`
- `Unknown/not reported`

#### 9. Work Status (Individuals 18+)
- `Employed Full-Time`
- `Employed Part-Time`
- `Migrant Seasonal Farm Worker`
- `Unemployed (Short-Term, 6 months or less)`
- `Unemployed (Long-Term, more than 6 months)`
- `Unemployed (Not in Labor Force)`
- `Retired`
- `Unknown/not reported`

### Household-Level Characteristics

#### 10. Household Type
- `Single Person`
- `Two Adults NO Children`
- `Single Parent Female`
- `Single Parent Male`
- `Two Parent Household`
- `Non-related Adults with Children`
- `Multigenerational Household`
- `Other`
- `Unknown/not reported`

#### 11. Household Size
- `Single Person` (1)
- `Two` (2)
- `Three` (3)
- `Four` (4)
- `Five` (5)
- `Six or more` (6+)
- `Unknown/not reported`

#### 12. Housing Status
- `Own`
- `Rent`
- `Other permanent housing`
- `Homeless`
- `Other`
- `Unknown/not reported`

#### 13. Income Level (% of HHS Poverty Guidelines)
- `Up to 50%`
- `51% to 75%`
- `76% to 100%`
- `101% to 125%`
- `126% to 150%`
- `151% to 175%`
- `176% to 200%`
- `201% to 250%`
- `250% and over`
- `Unknown/not reported`

#### 14. Income Source Categories
- `Income from Employment Only`
- `Income from Employment and Other Income Source`
- `Income from Employment, Other Income Source, and Non-Cash Benefits`
- `Income from Employment and Non-Cash Benefits`
- `Other Income Source Only`
- `Other Income Source and Non-Cash Benefits`
- `No Income`
- `Non-Cash Benefits Only`
- `Unknown/not reported`

#### 15. Other Income Sources (Multiple Selection)
- `TANF`
- `Supplemental Security Income (SSI)`
- `Social Security Disability Income (SSDI)`
- `VA Service-Connected Disability Compensation`
- `VA Non-Service Connected Disability Pension`
- `Private Disability Insurance`
- `Worker's Compensation`
- `Retirement Income from Social Security`
- `Pension`
- `Child Support`
- `Alimony or other Spousal Support`
- `Unemployment Insurance`
- `EITC`
- `Other`
- `Unknown/not reported`

#### 16. Non-Cash Benefits (Multiple Selection)
- `SNAP` (Food Stamps)
- `WIC`
- `LIHEAP` (Energy Assistance)
- `Housing Choice Voucher` (Section 8)
- `Public Housing`
- `Permanent Supportive Housing`
- `HUD-VASH`
- `Childcare Voucher`
- `Affordable Care Act Subsidy`
- `Other`
- `Unknown/not reported`

### CSBG Reporting Requirements
1. **Unduplicated Counts**: Each individual and household counted only once per reporting period
2. **Complete Demographics**: All characteristics must be collected for accurate reporting
3. **Federal Poverty Level**: Calculated using HHS guidelines for reporting year
4. **Age Group Assignment**: Based on age at time of service or intake
5. **Multiple Response Fields**: Race, income sources, and benefits allow multiple selections

---

## Data Transformation Rules

### Standard Transformations

#### Date Formats
- **Input**: `MM/DD/YYYY` or `M/D/YYYY`
- **Storage**: `YYYY-MM-DD`
- **DateTime**: `M/D/YYYY H:i:s A` → `YYYY-MM-DD HH:mm:ss`

#### Name Processing
- **Title Case**: Applied to first, middle, last names
- **Trim**: Remove leading/trailing spaces
- **Parse Full Names**: "Last, First Middle" → separate fields

#### Gender Mappings
| Input Value | Mapped To |
|------------|-----------|
| M, Male, MALE | Male |
| F, Female, FEMALE | Female |
| O, Other, OTHER | Other |
| U, Unknown, blank | Unknown/not reported |

#### Race Mappings
| Input Value | CSBG Category |
|------------|---------------|
| White, Caucasian | White |
| Black, African American | Black or African American |
| Asian | Asian |
| American Indian, Native American | American Indian or Alaska Native |
| Pacific Islander, Native Hawaiian | Native Hawaiian and Other Pacific Islander |
| Multiple, Multi-racial | Multi-race |
| Other | Other |
| Unknown, blank | Unknown/not reported |

#### Ethnicity Mappings
| Input Value | CSBG Category |
|------------|---------------|
| Hispanic, Latino, Spanish | Hispanic, Latino or Spanish Origins |
| Non-Hispanic, Not Hispanic | Not Hispanic, Latino or Spanish Origins |
| Unknown, blank | Unknown/not reported |

#### Military Status Mappings
| Input Value | CSBG Category |
|------------|---------------|
| Yes, Veteran, Y | Veteran |
| Active, Active Duty | Active Military |
| No, Never Served, N | Never Served in the Military |
| Unknown, blank | Unknown/not reported |

#### Boolean Conversions
| Input Value | Boolean |
|------------|---------|
| Yes, Y, 1, true | true |
| No, N, 0, false | false |
| Unknown, blank | null |

---

## Validation Requirements

### General Validation Rules

#### Required Fields
- Must be present and non-empty
- Whitespace-only values treated as empty
- Error message: "Field [name] is required"

#### String Length
- First/Last Name: Maximum 255 characters
- Email: Valid email format, max 255 characters
- Phone: 10-digit format acceptable

#### Numeric Validation
- Income: Non-negative decimal values
- Household Size: Positive integers only
- Age calculations: Based on date of birth

#### Date Validation
- Past dates only for birth dates
- Service dates cannot be future dates
- Exit date must be after enrollment date

### Import-Specific Validation

#### RX Office Import
1. **File Detection**: Automatically detects format based on header row
2. **Case ID Uniqueness**: Prevents duplicate case imports
3. **Name Requirements**: Both first and last name required
4. **Date Logic**: Case open date required and valid

#### HMIS Import
1. **Name Format**: Must match "Last, First Middle" pattern
2. **Case ID Format**: Alphanumeric household code
3. **Enrollment Logic**: Valid program enrollment required
4. **Relationship Mapping**: Determines household head

#### CSBG Validation
1. **Age Group Assignment**: Automatic based on birth date
2. **Income Classification**: Based on federal poverty guidelines
3. **Multiple Selections**: Validated against allowed values
4. **Household Integrity**: All members linked to valid household

### Duplicate Detection
The system implements sophisticated duplicate detection:

1. **Exact Match**: Same first name, last name, and date of birth
2. **Fuzzy Match**: Similar names with same birth date (80% similarity threshold)
3. **Household Match**: Same household code with similar participant data
4. **Case ID Match**: Prevents reimporting same case/enrollment

### Error Handling

#### Validation Errors (Fatal)
- Missing required fields
- Invalid date formats
- Invalid numeric values
- Duplicate primary keys

#### Warnings (Non-Fatal)
- Potential duplicate participants
- Missing optional demographic data
- Unmapped categorical values
- Future dates in non-critical fields

#### Import Results
Each import generates:
- Total records processed
- Successful imports count
- Updated records count
- Skipped records with reasons
- Detailed error log with row numbers

---

## System Integration Notes

### Database Considerations
- All imports use database transactions
- Rollback on critical errors
- Batch processing for large files (1000 records per batch)
- Automatic audit trail creation

### Performance Optimization
- Indexed lookups for duplicate detection
- Cached poverty guideline calculations
- Bulk insert operations where possible
- Memory-efficient CSV streaming

### Security Requirements
- File upload size limit: 10MB
- Allowed formats: CSV, TXT
- Virus scanning on upload
- Secure temporary file storage
- User permission validation

### Audit Trail
All imports maintain:
- User who performed import
- Timestamp of import
- Original filename
- Record counts
- Error/warning logs
- Success/failure status

---

This document serves as the authoritative reference for all data import and CSBG reporting specifications within the CAP TRACK system. For implementation details, refer to the respective service classes in the codebase.