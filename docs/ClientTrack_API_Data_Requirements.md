# ClientTrack REST API - Data Requirements for CAP TRACK Integration

**Prepared by:** Community Action Partnership of Clark, Nye, Lincoln, & Esmeralda Counties (CACLV)
**Date:** April 1, 2026
**Purpose:** Define the data fields CACLV requires from the ClientTrack REST API to import participants and generate federally required CSBG (Community Services Block Grant) reports.

---

## Overview

CACLV operates **CAP TRACK**, an internal data management platform for participant tracking and CSBG reporting. We need to ingest participant, household, enrollment, and service data from ClientTrack via REST API so we can produce the following federal reports:

- **CSBG Annual Report (Module 4)** - Demographics, characteristics, and outcomes for all individuals and households served
- **Section C - Individual Characteristics** - Age, gender, race, ethnicity, education, disability, health insurance, veteran status
- **Section D - Individual & Household Income/Poverty** - Income sources, poverty level classification, non-cash benefits
- **Section E - Housing** - Housing status for households served
- **Section F - Employment** - Work status, employment outcomes
- **Service Delivery Reports** - Programs and services delivered, enrollment counts, outcomes

---

## 1. Household Fields

Each household is the primary unit for income and poverty reporting.

| Field | Type | Required | Notes |
|---|---|---|---|
| `household_id` | string/int | Yes | Unique identifier in ClientTrack |
| `household_size` | integer | Yes | Total number of members in the household |
| `household_type` | enum | Yes | See accepted values below |
| `housing_status` | enum | Yes | See accepted values below |
| `monthly_income` | decimal | Yes | Total gross monthly household income |
| `annual_income` | decimal | Yes | Total gross annual household income |
| `address` | string | No | Street address |
| `city` | string | No | City |
| `state` | string(2) | No | Two-letter state code |
| `zip_code` | string(10) | No | ZIP or ZIP+4 |
| `county` | string | No | County name |

### Household Type - Accepted Values

- Single Person
- Two Adults NO Children
- Single Parent Female
- Single Parent Male
- Two Parent Household
- Non-related Adults with Children
- Multigenerational Household
- Other
- Unknown/not reported

### Housing Status - Accepted Values

- Own
- Rent
- Other permanent housing
- Homeless
- Other
- Unknown/not reported

---

## 2. Participant (Individual) Fields

Each participant belongs to one household. Demographics are reported at the individual level.

### 2a. Identity & Relationship

| Field | Type | Required | Notes |
|---|---|---|---|
| `participant_id` | string/int | Yes | Unique identifier in ClientTrack |
| `household_id` | string/int | Yes | Links participant to their household |
| `first_name` | string | Yes | |
| `last_name` | string | Yes | |
| `middle_name` | string | No | |
| `suffix` | string | No | Jr., Sr., III, etc. |
| `date_of_birth` | date | Yes | Format: YYYY-MM-DD. Used to compute CSBG age groups |
| `is_head_of_household` | boolean | Yes | One member per household must be head |
| `relationship_to_head` | enum | Yes | self, spouse, child, parent, sibling, grandchild, grandparent, other_relative, unrelated_individual |
| `telephone` | string | No | |
| `email` | string | No | |

### 2b. Demographics (CSBG Module 4, Section C)

| Field | Type | Required | Notes |
|---|---|---|---|
| `gender` | enum | Yes | See accepted values below |
| `ethnicity` | enum | Yes | See accepted values below |
| `race` | array of enum | Yes | One or more values. See accepted values below |
| `education_level` | enum | Yes | See accepted values below |
| `currently_enrolled_in_education` | boolean | No | Whether currently enrolled in any education program |
| `education_enrollment_type` | string | No | e.g., K-12, GED, College |
| `military_status` | enum | Yes | See accepted values below |
| `disability_status` | enum | Yes | See accepted values below |
| `health_insurance_status` | enum | Yes | See accepted values below |
| `health_insurance_sources` | array of enum | Conditional | Required if `health_insurance_status` = Yes. See accepted values below |

### 2c. Employment (CSBG Module 4, Section F)

| Field | Type | Required | Notes |
|---|---|---|---|
| `work_status` | enum | Yes | See accepted values below |
| `hours_worked_per_week` | decimal | Conditional | Required if employed |
| `is_seeking_employment` | boolean | No | |
| `is_underemployed` | boolean | No | Working but below skill/hour capacity |

### 2d. Income & Benefits (CSBG Module 4, Section D)

| Field | Type | Required | Notes |
|---|---|---|---|
| `income_sources` | enum | Yes | Overall income classification. See accepted values below |
| `employment_income` | decimal | Conditional | Annual employment income, if applicable |
| `other_income_sources` | array of enum | Conditional | Required if receiving non-employment income. See accepted values below |
| `non_cash_benefits` | array of enum | No | See accepted values below |

---

## 3. Accepted Enum Values for Participant Fields

All enum values must match exactly as listed (case-insensitive matching is acceptable). These are CSBG-standard values.

### Gender
- Male
- Female
- Other
- Unknown/not reported

### Ethnicity
- Hispanic, Latino or Spanish Origins
- Not Hispanic, Latino or Spanish Origins
- Unknown/not reported

### Race (one or more)
- American Indian or Alaska Native
- Asian
- Black or African American
- Native Hawaiian and Other Pacific Islander
- White
- Other
- Multi-race (two or more of the above)
- Unknown/not reported

### Education Level
- Grades 0-8
- Grades 9-12/Non-Graduate
- High School Graduate
- GED/Equivalency Diploma
- 12th Grade + Some Post-Secondary
- 2 or 4 Year College Graduate
- Graduate of Other Post-Secondary School
- Unknown/not reported

### Military Status
- Veteran
- Active Military
- Never Served in the Military
- Unknown/not reported

### Disability Status
- Yes
- No
- Unknown/not reported

### Health Insurance Status
- Yes
- No
- Unknown/not reported

### Health Insurance Sources (one or more)
- Medicaid
- Medicare
- State Children's Health Insurance Program
- State Health Insurance for Adults
- Military Health Care
- Direct-Purchase
- Employment Based
- Unknown/not reported

### Work Status
- Employed Full-Time
- Employed Part-Time
- Migrant Seasonal Farm Worker
- Unemployed (Short-Term, 6 months or less)
- Unemployed (Long-Term, more than 6 months)
- Unemployed (Not in Labor Force)
- Retired
- Unknown/not reported

### Income Sources (overall classification)
- Income from Employment Only
- Income from Employment and Other Income Source
- Income from Employment, Other Income Source, and Non-Cash Benefits
- Income from Employment and Non-Cash Benefits
- Other Income Source Only
- Other Income Source and Non-Cash Benefits
- No Income
- Non-Cash Benefits Only
- Unknown/not reported

### Other Income Sources (one or more)
- TANF
- Supplemental Security Income (SSI)
- Social Security Disability Income (SSDI)
- VA Service-Connected Disability Compensation
- VA Non-Service Connected Disability Pension
- Private Disability Insurance
- Worker's Compensation
- Retirement Income from Social Security
- Pension
- Child Support
- Alimony or other Spousal Support
- Unemployment Insurance
- EITC
- Other
- Unknown/not reported

### Non-Cash Benefits (one or more)
- SNAP
- WIC
- LIHEAP
- Housing Choice Voucher
- Public Housing
- Permanent Supportive Housing
- HUD-VASH
- Childcare Voucher
- Affordable Care Act Subsidy
- Other
- Unknown/not reported

---

## 4. Program & Service Enrollment Fields

These fields allow us to report on what services were delivered and to whom.

### Program Enrollment

| Field | Type | Required | Notes |
|---|---|---|---|
| `enrollment_id` | string/int | Yes | Unique identifier |
| `participant_id` | string/int | Yes | Links to participant |
| `program_name` | string | Yes | Name of the program |
| `program_id` | string/int | Yes | Unique program identifier |
| `enrollment_date` | date | Yes | Format: YYYY-MM-DD |
| `exit_date` | date | No | Null if still enrolled |
| `status` | enum | Yes | active, completed, withdrawn, transferred |
| `exit_reason` | enum | Conditional | Required on exit. Values: completed_successfully, moved_out_of_area, no_longer_eligible, non_compliance, participant_choice, other |

### Service Enrollment

| Field | Type | Required | Notes |
|---|---|---|---|
| `service_enrollment_id` | string/int | Yes | Unique identifier |
| `participant_id` | string/int | Yes | Links to participant |
| `service_name` | string | Yes | Name of the service |
| `service_id` | string/int | Yes | Unique service identifier |
| `program_id` | string/int | Yes | Parent program identifier |
| `enrollment_date` | date | Yes | Format: YYYY-MM-DD |
| `completion_date` | date | No | Null if still receiving service |
| `status` | enum | Yes | enrolled, in_progress, completed, withdrawn |
| `hours_received` | decimal | No | Total hours of service received |
| `sessions_attended` | integer | No | Total sessions attended |

---

## 5. Reports Generated from This Data

Below is a summary of what we produce from the ingested data and which fields drive each report.

| Report | Description | Key Fields Used |
|---|---|---|
| **CSBG Module 4 Section C** | Individual characteristics of all participants served | gender, date_of_birth (age groups), race, ethnicity, education_level, disability_status, health_insurance_status, military_status |
| **CSBG Module 4 Section D** | Household income and poverty levels | household_size, annual_income, income_sources, other_income_sources, non_cash_benefits |
| **CSBG Module 4 Section E** | Housing status of households served | housing_status, household_type |
| **CSBG Module 4 Section F** | Employment status and outcomes | work_status, hours_worked_per_week, is_seeking_employment |
| **Poverty Level Classification** | Households at 50%, 100%, 125%, 150%, 200%+ of federal poverty line | household_size, annual_income (compared against HHS Federal Poverty Guidelines) |
| **Disconnected Youth** | Youth ages 14-24 not working or in school | date_of_birth, work_status, currently_enrolled_in_education |
| **Service Delivery Summary** | Counts of services delivered by program | program enrollment + service enrollment fields |
| **Unduplicated Participant Count** | Total unique individuals and households served | participant_id, household_id |

### CSBG Age Groups (computed from date_of_birth)

| Age Group |
|---|
| 0-5 |
| 6-13 |
| 14-17 |
| 18-24 |
| 25-44 |
| 45-54 |
| 55-59 |
| 60-64 |
| 65-74 |
| 75+ |

---

## 6. API Integration Preferences

- **Format:** JSON
- **Date format:** YYYY-MM-DD (ISO 8601)
- **Array fields:** JSON arrays (e.g., `["White", "Asian"]` for race)
- **Pagination:** Preferred for large data sets
- **Incremental sync:** Ability to query by `last_modified` or `updated_since` timestamp is strongly preferred so we can pull only changed records
- **Unique identifiers:** All records must have stable, unique IDs that persist across API calls

---

## 7. Questions for ClientTrack

1. Does your REST API support filtering by `last_modified` date for incremental syncs?
2. Can enum/dropdown values be mapped to match our accepted values, or will a crosswalk/mapping table be needed?
3. Are household and participant records available as separate endpoints, or are they nested?
4. What authentication method does the API use (OAuth 2.0, API key, etc.)?
5. Are there rate limits we should be aware of?
6. Can service and program enrollment data be queried by date range?

---

*For questions about this document, contact CACLV IT.*
