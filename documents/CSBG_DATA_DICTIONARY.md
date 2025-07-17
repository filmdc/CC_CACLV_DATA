# CSBG Module 4 Section C Data Dictionary

This document provides the official reference for all data fields and values used in the CAP TRACK system, aligned with CSBG Module 4 Section C reporting requirements.

## Table of Contents
1. [Individual Level Characteristics](#individual-level-characteristics)
2. [Household Level Characteristics](#household-level-characteristics)
3. [Import Field Reference](#import-field-reference)
4. [Data Transformation Mappings](#data-transformation-mappings)

---

## Individual Level Characteristics

### Gender
**Field Name**: `gender`  
**Required**: Yes  
**Valid Values**:
- `Male`
- `Female`
- `Other`
- `Unknown/not reported`

### Age
Age is calculated from `date_of_birth` field and categorized as:
- 0-5
- 6-13
- 14-17
- 18-24
- 25-44
- 45-54
- 55-59
- 60-64
- 65-74
- 75+
- Unknown/not reported

### Education Level
**Field Name**: `education_level`  
**Required**: No  
**Valid Values**:
- `Grades 0-8`
- `Grades 9-12/Non-Graduate`
- `High School Graduate`
- `GED/Equivalency Diploma`
- `12th Grade + Some Post-Secondary`
- `2 or 4 Year College Graduate`
- `Graduate of Other Post-Secondary School`
- `Unknown/not reported`

### Ethnicity
**Field Name**: `ethnicity`  
**Required**: No  
**Valid Values**:
- `Hispanic, Latino or Spanish Origins`
- `Not Hispanic, Latino or Spanish Origins`
- `Unknown/not reported`

### Race
**Field Name**: `race`  
**Required**: No  
**Type**: Array (multiple values allowed, pipe-delimited in imports)  
**Valid Values**:
- `American Indian or Alaska Native`
- `Asian`
- `Black or African American`
- `Native Hawaiian and Other Pacific Islander`
- `White`
- `Other`
- `Multi-race (two or more of the above)`
- `Unknown/not reported`

### Military Status
**Field Name**: `military_status`  
**Required**: No  
**Valid Values**:
- `Veteran`
- `Active Military`
- `Never Served in the Military`
- `Unknown/not reported`

### Work Status
**Field Name**: `work_status`  
**Required**: No  
**Valid Values**:
- `Employed Full-Time`
- `Employed Part-Time`
- `Migrant Seasonal Farm Worker`
- `Unemployed (Short-Term, 6 months or less)`
- `Unemployed (Long-Term, more than 6 months)`
- `Unemployed (Not in Labor Force)`
- `Retired`
- `Unknown/not reported`

### Disability Status
**Field Name**: `disability_status`  
**Required**: No  
**Valid Values**:
- `Yes`
- `No`
- `Unknown/not reported`

### Health Insurance Status
**Field Name**: `health_insurance_status`  
**Required**: No  
**Valid Values**:
- `Yes`
- `No`
- `Unknown/not reported`

### Health Insurance Sources
**Field Name**: `health_insurance_sources`  
**Required**: No (only if health_insurance_status is Yes)  
**Type**: Array (multiple values allowed, pipe-delimited in imports)  
**Valid Values**:
- `Medicaid`
- `Medicare`
- `State Children's Health Insurance Program`
- `State Health Insurance for Adults`
- `Military Health Care`
- `Direct-Purchase`
- `Employment Based`
- `Unknown/not reported`

---

## Household Level Characteristics

### Household Type
**Field Name**: `household_type`  
**Required**: Yes  
**Valid Values**:
- `Single Person`
- `Two Adults NO Children`
- `Single Parent Female`
- `Single Parent Male`
- `Two Parent Household`
- `Non-related Adults with Children`
- `Multigenerational Household`
- `Other`
- `Unknown/not reported`

### Household Size
**Field Name**: `household_size`  
**Required**: Yes  
**Type**: Integer (1 or greater)

### Housing Status
**Field Name**: `housing_status`  
**Required**: Yes  
**Valid Values**:
- `Own`
- `Rent`
- `Other permanent housing`
- `Homeless`
- `Other`
- `Unknown/not reported`

### Income Sources
**Field Name**: `income_sources`  
**Required**: No  
**Type**: Array (multiple values allowed, pipe-delimited in imports)  
**Valid Values**:
- `Employment`
- `TANF`
- `SSI`
- `SSDI`
- `VA Benefits`
- `Unemployment Insurance`
- `Child Support`
- `Other`

### Other Income Sources
**Field Name**: `other_income_sources`  
**Required**: No  
**Type**: Array (multiple values allowed, pipe-delimited in imports)  
**Valid Values**:
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

### Non-Cash Benefits
**Field Name**: `non_cash_benefits`  
**Required**: No  
**Type**: Array (multiple values allowed, pipe-delimited in imports)  
**Valid Values**:
- `SNAP`
- `WIC`
- `LIHEAP`
- `Housing Choice Voucher`
- `Public Housing`
- `Permanent Supportive Housing`
- `HUD-VASH`
- `Childcare Voucher`
- `Affordable Care Act Subsidy`
- `Other`
- `Unknown/not reported`

---

## Import Field Reference

### Required Fields for Import
1. `household_code` - Unique 7-character alphanumeric code
2. `participant_type` - Must be `HEAD_OF_HOUSEHOLD` or `FAMILY_MEMBER`
3. `first_name` - Participant's first name
4. `last_name` - Participant's last name
5. `date_of_birth` - Format: YYYY-MM-DD
6. `gender` - See valid values above

### Household Fields (Required for HEAD_OF_HOUSEHOLD only)
- `household_type` - See valid values above
- `household_size` - Integer, must match actual participant count
- `housing_status` - See valid values above
- `monthly_income` - Decimal (optional)
- `annual_income` - Decimal (optional)
- `street_address` - Text (optional)
- `city` - Text (optional)
- `state` - 2-letter abbreviation (optional)
- `zip_code` - 5 or 9 digit format (optional)

### Boolean Fields
Use `TRUE/FALSE` or `1/0` for:
- `currently_enrolled_in_education`
- `has_chronic_health_conditions`
- `is_seeking_employment`
- `is_underemployed`

### Array Fields
Use pipe delimiter (|) for multiple values:
- `race` - Example: `White|Asian`
- `disability_types` - Example: `Physical|Mental`
- `health_insurance_sources` - Example: `Medicare|Medicaid`
- `income_sources` - Example: `Employment|Child Support`
- `non_cash_benefits` - Example: `SNAP|WIC`

---

## Data Transformation Mappings

The system automatically transforms common variations to CSBG-compliant values:

### Gender Transformations
- `m`, `male`, `man`, `boy` → `Male`
- `f`, `female`, `woman`, `girl` → `Female`
- `transgender`, `trans`, `non-binary`, `nonbinary` → `Other`
- `unknown`, `not reported`, `prefer not to answer` → `Unknown/not reported`

### Ethnicity Transformations
- `hispanic`, `latino`, `latina`, `hispanic/latina/e/o` → `Hispanic, Latino or Spanish Origins`
- `non-hispanic`, `not hispanic`, `non-latino` → `Not Hispanic, Latino or Spanish Origins`

### Work Status Transformations
- `employed full-time`, `full-time`, `ft` → `Employed Full-Time`
- `employed part-time`, `part-time`, `pt` → `Employed Part-Time`
- `unemployed`, `not working` → `Unemployed (Short-Term, 6 months or less)`
- `retired`, `retirement` → `Retired`

### Military Status Transformations
- `yes`, `veteran`, `vet` → `Veteran`
- `active`, `active duty`, `currently serving` → `Active Military`
- `no`, `never served`, `n/a`, `not applicable` → `Never Served in the Military`

### Yes/No Field Transformations
For disability_status and health_insurance_status:
- `yes`, `y`, `true`, `1` → `Yes`
- `no`, `n`, `false`, `0` → `No`
- `unknown`, `not reported` → `Unknown/not reported`

---

## Import Tips

1. **Header Row**: The first row must contain exact field names as listed above
2. **Case Sensitivity**: Field values are case-sensitive - use exact values shown
3. **Empty Values**: Leave fields blank rather than using "N/A" or similar
4. **Household Structure**: 
   - First participant in a household must be HEAD_OF_HOUSEHOLD
   - Subsequent participants with same household_code are FAMILY_MEMBER
   - Household fields only needed for first participant
5. **Date Format**: Always use YYYY-MM-DD format for dates
6. **Reference Data Sets**: When importing historical data, select the appropriate reference data set that matches the time period of your data

## Validation Rules

1. **Household Code**: Must be unique across the system
2. **Participant Type**: First participant per household must be HEAD_OF_HOUSEHOLD
3. **Age Restrictions**: 
   - Education level collected for ages 14+
   - Work status collected for ages 18+
4. **Income**: 
   - If provided, monthly_income and annual_income should align (annual ≈ monthly × 12)
   - Poverty percentage is auto-calculated based on income and household size
5. **Required Fields**: System will reject records missing required fields

## Common Import Errors

1. **"Invalid value for 'ethnicity'"**: Check exact spelling including commas
2. **"Invalid value for 'work_status'"**: Ensure proper capitalization (Full-Time, not full-time)
3. **"Missing required field"**: Verify all required fields have values
4. **"Invalid date format"**: Use YYYY-MM-DD format
5. **"Duplicate household code"**: Each household must have unique code

---

*Last Updated: [Current Date]*  
*Version: 1.0*  
*Compliant with CSBG Module 4 Section C Reporting Requirements*