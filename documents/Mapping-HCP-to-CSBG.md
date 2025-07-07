# Field Mapping Analysis: Housing Counseling Program to CSBG Participant Intake

This document provides a detailed field-by-field mapping between the Housing Counseling Program data collection system (RX Office exports) and the CSBG Participant Intake system.

## Data Structure Comparison

| RX Office (UTF-8Detailed) Column | CSBG System Field | Data Type | Required? | Notes |
|--------------------------------|-------------------------------------|-----------|-----------|-------|
| Case ID | Internal reference only | String/Number | N/A | Stored in notes field for reference |
| Case Type | program_name | String | Required | Maps to "Housing Counseling Program" |
| Case Open Date | enrollment_date | Date | Required | Format conversion needed: MM/DD/YYYY → YYYY-MM-DD |
| Borrower First Name | first_name | String | Required | Direct match |
| Borrower Last Name | last_name | String | Required | Direct match |
| Borrower Birthdate | date_of_birth | Date | Required | Format conversion needed: MM/DD/YYYY → YYYY-MM-DD |
| Borrower Family Size | household_size | Number | Optional | Direct mapping to household record's household_size field |
| Borrower Gender | gender_id (via reference data) | FK | Required | Maps to reference data values |
| Borrower Race | races (pivot table) | M2M | Optional | Maps to reference data values |
| Borrower Ethnicity | ethnicity_id (via reference data) | FK | Optional | Maps to reference data values |
| Borrower Dependents | N/A | Number | N/A | Not directly stored, but noted for household size |
| Military Status | military_status_id (via reference data) | FK | Optional | Maps to reference data values |
| Borrower Household Type | household_type_id (via reference data) | FK | Required | Maps to reference data values |
| Owner Occupied | housing_status_id (via reference data) | FK | Optional | Maps to "Own" vs "Rent" etc. |
| Disabled | health_status_id (via reference data) | FK | Optional | Maps to health status reference data |
| AMI | N/A | String | N/A | Not directly stored but can be calculated later |
| Assigned Agent | N/A | String | N/A | Stored in notes for reference |
| Age | N/A | Number | N/A | Calculated from date_of_birth |
| Counseling Type | notes | String | Optional | Stored in program enrollment notes |
| Household Income Gross | income_amount | Number | Optional | Stored with frequency="monthly" |
| Counseling Outcome | notes | String | Optional | Stored in notes field |
| Employment Status | work_status_id (via reference data) | FK | Optional | Maps to work status reference values |

## Value Mapping Reference

### Gender
- **RX Office**: Male, Female
- **CSBG System**: Male, Female, Other, Unknown/not reported

### Race
- **RX Office**: White, Black or African American, etc.
- **CSBG System**: American Indian or Alaska Native, Asian, Black or African American, Native Hawaiian and Other Pacific Islander, White, Other, Unknown/not reported

### Ethnicity
- **RX Office**: Hispanic or Latino, Not Hispanic or Latino
- **CSBG System**: Hispanic, Latino or Spanish Origins; Not Hispanic, Latino or Spanish Origins; Unknown/not reported

### Household Type
- **RX Office**: 
  - Single adult → Single Person
  - Married without dependents → Two Adults, No Children
  - Two or more unrelated adults → Two Adults, No Children
  - Female-headed single parent household → Single Parent, Female
  - Male-headed single parent household → Single Parent, Male
  - Married with dependents → Two-Parent Household

### Housing Status
- **RX Office**: 
  - Owner Occupied → Own
  - (Empty) → Rent (default)
  - Other values → Unknown/not reported

### Employment Status
- **RX Office**: 
  - 1=Currently Employed full time → Employed Full-Time
  - 2=Currently Employed part time → Employed Part-Time
  - 3=Unemployed and looking for work → Unemployed (Short-Term, six months or less)
  - 4=Unable to work due to disability → Unemployed (Not in Labor Force)
  - 6=Retired → Retired
  - 12=Self Employment → Employed Full-Time
  - 8=Employed full time AND Student → Employed Full-Time
  - 9=Employed part time AND Student → Employed Part-Time

### Health Status (Disabled)
- **RX Office**: 
  - Yes → Has Disabling Condition
  - No → No Disabling Condition
  - (Empty) → Unknown/not reported

## Database Operations

During import, the following operations occur:

1. **Household Creation**:
   - Creates a new household record with default address information
   - Maps household type and housing status to reference data values
   - Sets household_size from the Borrower Family Size field

2. **Participant Creation**:
   - Creates a primary participant record
   - Links to the household
   - Maps demographic information to reference data values
   - Creates income record if amount is provided

3. **Program Enrollment**:
   - Creates a program enrollment in "Housing Counseling Program" 
   - Uses Case Open Date as enrollment date
   - Stores Case ID and Counseling Type in notes

## Implementation Notes

1. **Default Values**: Address information is not available in RX Office exports, so default values are used:
   - Street: "Unknown - Import from RX Office"
   - City: "Unknown"
   - State: "PA" (assuming Pennsylvania is the default state)
   - Zip: "00000"

2. **Date Conversion**: Dates are converted from MM/DD/YYYY to YYYY-MM-DD format

3. **Data Cleanup**: Extra spaces are trimmed from names and other text fields

4. **Notes Field**: The Case ID, Counseling Type, and Counseling Outcome are stored in the notes field for reference

# Mapping HCP to CSBG Dashboard

This document outlines the mapping between HCP (Housing Counseling Program) data and the CSBG Dashboard system.

## Overview

The Housing Counseling Program (HCP) uses different terminology and data structures compared to the CSBG system. This mapping document helps translate between the two systems to ensure accurate data imports and reporting.

## Case Type Mapping

| HCP Case Type | CSBG Service Category |
|---------------|----------------------|
| PHFA Default And Delinquency Counseling | Housing |
| PHFA Pre-Closing Seminar | Housing |
| PHFA Pre-Purchase One on One counseling | Housing |
| Reverse Mortgage Counseling | Housing |
| PHFA Rental Counseling | Housing |
| Financial Education Workshop | Financial Literacy/Education |
| Credit Counseling | Financial Literacy/Education |

## Demographic Mapping

| HCP Field | CSBG Field | Notes |
|-----------|------------|-------|
| Borrower First Name | First Name | Trimmed to remove extra spaces |
| Borrower Last Name | Last Name | Trimmed to remove extra spaces |
| Borrower Birthdate | DOB | Converted from MM/DD/YYYY to YYYY-MM-DD |
| Borrower Family Size | Household Size | |
| Borrower Gender | Gender | |
| Borrower Race | Race | May require multiple selections in CSBG |
| Borrower Ethnicity | Ethnicity | |
| Borrower Dependents | Number of Children | |
| Military Status | Military Status | |
| Borrower Household Type | Household Type | |
| Borrower Monthly Income Gross | Monthly Income | |
| Disabled | Disabled | |
| AMI | Income Bracket | Mapped to CSBG income brackets |
| Borrower Total Gross Income | Annual Income | May need to calculate from monthly |
| Case Open Date | Enrollment Date | Converted from MM/DD/YYYY to YYYY-MM-DD |

## Income Bracket Mapping

| HCP AMI Value | CSBG Poverty Level |
|---------------|-------------------|
| < 30% of Area Median Income (AMI) | Up to 50% |
| 30-49% of AMI | 51% to 75% |
| 50-79% of AMI | 76% to 100% |
| 80-100% of AMI | 101% to 125% |
| Greater than 100% AMI | 126% to 150% |
| | 151% to 175% |
| | 176% to 200% |
| | 201% and above |

## Household Type Mapping

| HCP Household Type | CSBG Household Type |
|--------------------|--------------------|
| Single adult | Single Person |
| Two or more unrelated adults | Multi-person (non-family) |
| Married with dependents | Two Parents/Caregivers with Children |
| Married without dependents | Two Adults (no children) |
| Female-headed single parent household | Single Parent (female) |
| Male-headed single parent household | Single Parent (male) |
| Other | Other |

## Service Goal Mapping

| HCP Counseling Type | CSBG Service Goal |
|---------------------|-------------------|
| Default and Delinquency counseling | Housing Stability Counseling |
| Foreclosure Prevention | Foreclosure Prevention Counseling |
| Pre-Purchase one on one counseling | Homebuyer Education |
| Pre-Closing Seminar | Homebuyer Education |
| Financial Education | Financial Literacy Workshop |
| Credit Counseling | Credit Counseling |

## Special Considerations

1. **Duplicate Records**: HCP may have multiple records for the same case ID. In CSBG, these should be consolidated.
2. **Missing Data**: Some required CSBG fields may not have direct HCP equivalents.
3. **Date Formatting**: HCP uses MM/DD/YYYY, while CSBG uses YYYY-MM-DD.
4. **Income Calculation**: Monthly income may need to be multiplied by 12 for annual reporting.
5. **Race Categories**: HCP may use different race categories than CSBG.

## RX Office Import Feature

The CSBG system includes a specialized import tool for integrating data from the RX Office case management system (used by HCP). This allows for automated data transfer between the systems.

### Import Process

1. **File Upload**: Users can upload CSV exports from the RX Office system through the admin interface
2. **Data Parsing**: The system automatically parses the CSV file and extracts relevant case information
3. **Field Mapping**: Data fields are mapped according to the definitions in this document
4. **Validation**: Imported records are validated against CSBG data requirements
5. **Review Interface**: Users can review imported records before finalizing the import
6. **Conflict Resolution**: The system identifies duplicate records and other data conflicts
7. **Import Finalization**: After review, records can be imported into the CSBG system

### Import File Format

The RX Office export should be in CSV format with the following columns:
- Case ID
- Case Type
- Case Open Date
- Borrower First Name
- Borrower Last Name
- Borrower Birthdate
- Borrower Family Size
- Borrower Gender
- Borrower Race
- Borrower Ethnicity
- Borrower Dependents
- Military Status
- Borrower Household Type
- Borrower Monthly Income Gross
- Owner Occupied
- Disabled
- AMI
- Assigned Agent
- Age
- FHH
- Counseling Type
- Borrower Total Gross Income
- Household Income Gross
- Counseling Outcome
- Employment Status
- Occupation

### Handling Data Review

The import process includes a review step where each record is displayed with:
- Mapped data fields
- Validation warnings or errors
- Matching service goals
- Potential duplicates

Administrators can approve or reject individual records, fix data issues, and assign the correct service goals before finalizing the import.