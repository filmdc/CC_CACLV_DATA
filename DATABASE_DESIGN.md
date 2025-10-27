# CAP TRACK Database Design Documentation

**Version:** 1.0
**Last Updated:** October 22, 2025
**Database Engine:** MySQL
**Database Name:** CC_CACLV_DATA

## Table of Contents

1. [Overview](#overview)
2. [Core Modules](#core-modules)
   - [User Management & RBAC](#user-management--rbac)
   - [Participant & Household Management](#participant--household-management)
   - [Program & Service Management](#program--service-management)
   - [Enrollment Tracking](#enrollment-tracking)
3. [Contract Management Module](#contract-management-module)
4. [Housing Rehabilitation Module](#housing-rehabilitation-module)
5. [Reference Data System](#reference-data-system)
6. [Import & Export System](#import--export-system)
7. [External Integrations](#external-integrations)
8. [Support Tables](#support-tables)
9. [Key Relationships](#key-relationships)
10. [Design Patterns & Features](#design-patterns--features)

---

## Overview

CAP TRACK is a comprehensive data management platform for Community Action Partnership (CAP) agencies. The system manages participant/household data, program services, CSBG (Community Services Block Grant) reporting requirements, contract management, and housing rehabilitation job tracking.

**Key Design Principles:**
- **CSBG Compliance:** All participant and household data structures comply with CSBG Module 4 Section C requirements
- **Role-Based Access Control:** Comprehensive RBAC system with granular permissions
- **Program-Based Security:** Users have access to data based on program assignments
- **Audit Trail:** Most tables include `created_by` and `updated_by` fields
- **Soft Deletes:** Critical tables use soft deletes to preserve historical data
- **JSON Storage:** Complex data (arrays, nested structures) stored as JSON fields
- **Auto-Generation:** System generates unique codes (household codes, job numbers)

---

## Core Modules

### User Management & RBAC

#### users
Primary authentication and user management table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Full name |
| email | varchar(255) | NOT NULL, UNIQUE | Email address |
| email_verified_at | timestamp | NULLABLE | Email verification timestamp |
| password | varchar(255) | NOT NULL | Hashed password |
| active | boolean | DEFAULT true | Account status |
| remember_token | varchar(100) | NULLABLE | Remember me token |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsToMany(Role)` via `user_role` pivot table
- `belongsToMany(Program)` via `user_program` pivot table

#### roles
Defines user roles within the system.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL, UNIQUE | System role name (e.g., 'admin') |
| display_name | varchar(255) | NOT NULL | Human-readable name |
| description | text | NULLABLE | Role description |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsToMany(Permission)` via `role_permission` pivot table
- `belongsToMany(User)` via `user_role` pivot table

#### permissions
Defines granular permissions in the system.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL, UNIQUE | Permission name (e.g., 'participants.view') |
| display_name | varchar(255) | NOT NULL | Human-readable name |
| description | text | NULLABLE | Permission description |
| category | varchar(255) | NULLABLE | Permission category for grouping |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsToMany(Role)` via `role_permission` pivot table

#### user_role (Pivot Table)
Links users to their assigned roles.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| user_id | bigint unsigned | FK, NOT NULL | References users.id |
| role_id | bigint unsigned | FK, NOT NULL | References roles.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- UNIQUE(user_id, role_id)

#### role_permission (Pivot Table)
Links roles to their assigned permissions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| role_id | bigint unsigned | FK, NOT NULL | References roles.id |
| permission_id | bigint unsigned | FK, NOT NULL | References permissions.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- UNIQUE(role_id, permission_id)

#### user_program (Pivot Table)
Defines program access for users.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| user_id | bigint unsigned | FK, NOT NULL | References users.id |
| program_id | bigint unsigned | FK, NOT NULL | References programs.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- UNIQUE(user_id, program_id)

---

### Participant & Household Management

#### households
Stores household-level demographic and economic information.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| household_code | varchar(7) | NOT NULL, UNIQUE | Auto-generated 7-char alphanumeric code |
| household_type | enum | NOT NULL | Type of household composition |
| household_size | integer | NOT NULL | Number of members (manual + calculated) |
| housing_status | enum | NOT NULL | Housing status (own, rent, homeless, etc.) |
| monthly_income | decimal(10,2) | NULLABLE | Total monthly household income |
| annual_income | decimal(10,2) | NULLABLE | Total annual household income |
| hhs_poverty_percentage | decimal(5,2) | NULLABLE | Percentage of HHS poverty guideline |
| notes | text | NULLABLE | Additional notes |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `household_type`: Single Person, Two Adults NO Children, Single Parent Female, Single Parent Male, Two Parent Household, Non-related Adults with Children, Multigenerational Household, Other, Unknown/not reported
- `housing_status`: Own, Rent, Other permanent housing, Homeless, Other, Unknown/not reported

**Relationships:**
- `hasMany(Participant)`
- `hasOne(Participant)` - head of household (where is_head_of_household = true)
- `hasMany(HouseholdAddress)`
- `hasOne(HouseholdAddress)` - current address (where is_current = true)
- `hasMany(Property)`
- `hasMany(HousingJob)`

**Features:**
- Auto-generates unique 7-digit alphanumeric household code on creation
- Household size calculated from actual participant count but allows manual override
- Address history tracked separately in household_addresses table

#### household_addresses
Tracks household address history with effective dates.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| household_id | bigint unsigned | FK, NOT NULL | References households.id |
| address | varchar(255) | NULLABLE | Street address |
| city | varchar(255) | NULLABLE | City |
| state | varchar(2) | NULLABLE | State abbreviation |
| zip_code | varchar(10) | NULLABLE | ZIP code |
| county | varchar(255) | NULLABLE | County |
| area | varchar(255) | NULLABLE | Area designation |
| primary_phone | varchar(20) | NULLABLE | Primary phone number |
| is_current | boolean | DEFAULT true | Is this the current address? |
| effective_date | date | NULLABLE | Date address became effective |
| end_date | date | NULLABLE | Date address ended |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| reason_for_change | text | NULLABLE | Reason for address change |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (household_id, is_current)
- (effective_date)

**Relationships:**
- `belongsTo(Household)`
- `belongsTo(User)` - created_by

#### participants
Stores individual participant demographic, socioeconomic, and CSBG data.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| household_id | bigint unsigned | FK, NOT NULL | References households.id |
| reference_data_set_id | bigint unsigned | FK, NULLABLE | References reference_data_sets.id |
| is_head_of_household | boolean | DEFAULT false | Is this the head of household? |
| relationship_to_head | varchar(255) | NULLABLE | Relationship to head of household |
| first_name | varchar(255) | NOT NULL | First name |
| middle_name | varchar(255) | NULLABLE | Middle name |
| last_name | varchar(255) | NOT NULL | Last name |
| suffix | varchar(255) | NULLABLE | Name suffix (Jr, Sr, III, etc.) |
| date_of_birth | date | NOT NULL | Date of birth |
| gender | enum | NOT NULL | Gender identity |
| telephone | varchar(255) | NULLABLE | Phone number |
| email | varchar(255) | NULLABLE | Email address |
| education_level | enum | NULLABLE | Highest education level |
| currently_enrolled_in_education | boolean | NULLABLE | Currently in school/training? |
| education_enrollment_type | varchar(255) | NULLABLE | Type of education enrollment |
| disability_status | enum | NULLABLE | Has disability? |
| disability_types | json | NULLABLE | Array of disability types |
| assistance_needs | json | NULLABLE | Array of assistance needs |
| has_chronic_health_conditions | boolean | NULLABLE | Has chronic health conditions? |
| health_insurance_sources | json | NULLABLE | Array of insurance sources |
| health_insurance_status | enum | NULLABLE | Has health insurance? |
| ethnicity | enum | NULLABLE | Hispanic/Latino ethnicity |
| race | json | NULLABLE | Array of race selections |
| military_status | enum | NULLABLE | Military/veteran status |
| work_status | enum | NULLABLE | Employment status |
| hours_worked_per_week | decimal(5,2) | NULLABLE | Average hours worked per week |
| is_seeking_employment | boolean | NULLABLE | Actively seeking employment? |
| is_underemployed | boolean | NULLABLE | Underemployed? |
| income_sources | json | NULLABLE | Array of income sources |
| employment_income | decimal(10,2) | NULLABLE | Income from employment |
| other_income_sources | json | NULLABLE | Array of other income sources |
| non_cash_benefits | json | NULLABLE | Array of non-cash benefits |
| intake_date | date | NULLABLE | Date of intake |
| last_service_date | date | NULLABLE | Date of last service |
| is_disconnected_youth | boolean | NULLABLE | Disconnected youth (14-24, not working/in school) |
| csbg_age_group | varchar(255) | NULLABLE | CSBG age group classification (computed) |
| csbg_income_classification | varchar(255) | NULLABLE | CSBG income classification (computed) |
| documentation_notes | text | NULLABLE | Documentation/verification notes |
| notes | text | NULLABLE | General notes |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `gender`: Male, Female, Non-binary, Other, Prefer not to say
- `education_level`: Less than high school, High school diploma/GED, Some college, Associate degree, Bachelor degree, Graduate degree, Unknown/not reported
- `disability_status`: Yes, No, Unknown/not reported
- `health_insurance_status`: Yes, No, Unknown/not reported
- `ethnicity`: Hispanic or Latino, Not Hispanic or Latino, Unknown/not reported
- `military_status`: Currently serving, Veteran, Not applicable, Unknown/not reported
- `work_status`: Employed full-time, Employed part-time, Unemployed, Not in labor force, Unknown/not reported

**Relationships:**
- `belongsTo(Household)`
- `belongsTo(ReferenceDataSet)`
- `hasMany(ProgramEnrollment)`
- `hasMany(ServiceEnrollment)`
- `hasMany(Property)`
- `hasMany(HousingJob)`

**Computed Fields:**
- `full_name`: Concatenated first + middle + last + suffix
- `age`: Calculated from date_of_birth
- `csbg_age_group`: Auto-computed based on age (0-5, 6-13, 14-17, 18-24, 25-44, 45-54, 55-59, 60-64, 65-74, 75+)
- `is_disconnected_youth`: Auto-computed (age 14-24, not working, not in school)
- `csbg_income_classification`: Auto-computed based on household income sources

**Features:**
- CSBG-compliant data structure
- Program-based security (users can only access participants in their programs)
- Sensitive vs. public field segregation
- Auto-updates computed CSBG fields on save

---

### Program & Service Management

#### programs
Defines organizational programs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Program name |
| description | text | NULLABLE | Program description |
| program_manager_id | bigint unsigned | FK, NULLABLE | References users.id |
| active | boolean | DEFAULT true | Is program active? |
| housing_module_enabled | boolean | DEFAULT false | Enable housing rehab module? |
| contract_management_only | boolean | DEFAULT false | Hidden from users, contract tracking only |
| start_date | date | NULLABLE | Program start date |
| end_date | date | NULLABLE | Program end date |
| notes | text | NULLABLE | Program notes |
| legacy_id | varchar(255) | NULLABLE | Legacy system ID (for data migration) |
| last_synced_at | timestamp | NULLABLE | Last sync timestamp |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(User)` - program_manager
- `hasMany(Service)`
- `hasMany(Contract)` - direct FK relationship
- `belongsToMany(Contract)` via `contract_program` - legacy compatibility
- `hasMany(WorkPlan)`
- `hasMany(ProgramEnrollment)`
- `hasMany(HousingJob)`
- `belongsToMany(User)` via `user_program`

**Features:**
- Programs can enable housing module for housing rehab job tracking
- Contract-only programs hidden from end users
- Legacy compatibility with many-to-many contract relationships

#### services
Defines services within programs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Service name |
| description | text | NULLABLE | Service description |
| program_id | bigint unsigned | FK, NOT NULL | References programs.id |
| service_type | varchar(255) | NULLABLE | Type/category of service |
| start_date | date | NULLABLE | Service start date |
| end_date | date | NULLABLE | Service end date |
| active | boolean | DEFAULT true | Is service active? |
| notes | text | NULLABLE | Service notes |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(Program)`
- `hasMany(WorkPlanGoal)`
- `hasMany(ServiceEnrollment)`
- `belongsTo(User)` - created_by

#### work_plans
Defines annual work plans for programs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Work plan name |
| program_id | bigint unsigned | FK, NOT NULL | References programs.id |
| year | year | NOT NULL | Work plan year |
| description | text | NULLABLE | Work plan description |
| final_reports | json | NULLABLE | Final report data/documents |
| active | boolean | DEFAULT true | Is work plan active? |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(Program)`
- `hasMany(WorkPlanGoal)`

#### work_plan_goals
Defines goals and targets within work plans.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| work_plan_id | bigint unsigned | FK, NOT NULL | References work_plans.id |
| service_id | bigint unsigned | FK, NULLABLE | References services.id |
| goal_name | varchar(255) | NOT NULL | Goal name |
| description | text | NULLABLE | Goal description |
| target_number | integer | NOT NULL | Target number to achieve |
| actual_number | integer | DEFAULT 0 | Actual number achieved |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(WorkPlan)`
- `belongsTo(Service)`

**Features:**
- Service ID is nullable to allow program-level goals not tied to specific services

---

### Enrollment Tracking

#### program_enrollments
Tracks participant enrollment in programs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| participant_id | bigint unsigned | FK, NOT NULL | References participants.id |
| program_id | bigint unsigned | FK, NOT NULL | References programs.id |
| enrollment_date | date | NOT NULL | Enrollment date |
| exit_date | date | NULLABLE | Exit date |
| status | enum | DEFAULT 'active' | Enrollment status |
| exit_reason | enum | NULLABLE | Reason for exit |
| exit_notes | text | NULLABLE | Exit notes |
| notes | text | NULLABLE | General notes |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: active, completed, withdrawn, transferred
- `exit_reason`: completed_successfully, moved_out_of_area, no_longer_eligible, non_compliance, participant_choice, other

**Indexes:**
- (participant_id, program_id, status)

**Relationships:**
- `belongsTo(Participant)`
- `belongsTo(Program)`
- `hasMany(ServiceEnrollment)`

**Computed Fields:**
- `days_enrolled`: Days between enrollment_date and exit_date (or current date)
- `is_active`: Boolean check if status is 'active'

#### service_enrollments
Tracks participant enrollment in specific services.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| participant_id | bigint unsigned | FK, NOT NULL | References participants.id |
| service_id | bigint unsigned | FK, NOT NULL | References services.id |
| program_enrollment_id | bigint unsigned | FK, NOT NULL | References program_enrollments.id |
| enrollment_date | date | NOT NULL | Service enrollment date |
| completion_date | date | NULLABLE | Service completion date |
| status | enum | DEFAULT 'enrolled' | Enrollment status |
| outcomes | json | NULLABLE | Service-specific outcomes and measurements |
| hours_received | decimal(8,2) | NULLABLE | Hours of service received |
| sessions_attended | integer | NULLABLE | Number of sessions attended |
| completion_notes | text | NULLABLE | Completion notes |
| notes | text | NULLABLE | General notes |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: enrolled, in_progress, completed, withdrawn

**Indexes:**
- (participant_id, service_id)
- (program_enrollment_id, status)

**Relationships:**
- `belongsTo(Participant)`
- `belongsTo(Service)`
- `belongsTo(ProgramEnrollment)`

**Computed Fields:**
- `days_in_service`: Days between enrollment_date and completion_date (or current date)
- `is_active`: Boolean check if status is 'enrolled' or 'in_progress'
- `is_completed`: Boolean check if status is 'completed'

---

## Contract Management Module

#### funders
Tracks funding organizations.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Funder name |
| code | varchar(255) | NULLABLE | Funder code |
| description | text | NULLABLE | Funder description |
| contact_person | varchar(255) | NULLABLE | Contact person name |
| contact_email | varchar(255) | NULLABLE | Contact email |
| contact_phone | varchar(255) | NULLABLE | Contact phone |
| address | text | NULLABLE | Funder address |
| website | varchar(255) | NULLABLE | Funder website |
| active | boolean | DEFAULT true | Is funder active? |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

**Indexes:**
- (active, name)

**Relationships:**
- `hasMany(Contract)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by

**Features:**
- Soft deletes enabled

#### contracts
Tracks funding contracts and grants.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| title | varchar(255) | NOT NULL | Contract title |
| contract_number | varchar(255) | NULLABLE | Contract number |
| cfda_number | varchar(255) | NULLABLE | CFDA number (Catalog of Federal Domestic Assistance) |
| purpose | text | NULLABLE | Contract purpose |
| program_id | bigint unsigned | FK, NULLABLE | References programs.id |
| funder_id | bigint unsigned | FK, NULLABLE | References funders.id |
| date_of_award | date | NULLABLE | Date contract was awarded |
| amount | decimal(15,2) | NULLABLE | Contract amount |
| start_date | date | NULLABLE | Contract start date |
| end_date | date | NULLABLE | Contract end date |
| grant_code | varchar(255) | NULLABLE | Grant code |
| notes | text | NULLABLE | Contract notes |
| archived | boolean | DEFAULT false | Is contract archived? |
| date_archived | datetime | NULLABLE | Archive timestamp |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| legacy_id | varchar(255) | NULLABLE | Legacy system ID |
| last_synced_at | timestamp | NULLABLE | Last sync timestamp |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

**Relationships:**
- `belongsTo(Program)`
- `belongsTo(Funder)`
- `belongsToMany(Program)` via `contract_program` - legacy compatibility
- `hasMany(ContractReportDueDate)`
- `hasMany(ContractReport)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by
- Spatie Media Library integration for document attachments

**Computed Fields:**
- `status`: Derived from archived flag and dates (Draft, Upcoming, Active, Expired, Archived)

**Features:**
- Soft deletes enabled
- Document management via Spatie Media Library
- Supports both single program (FK) and multiple programs (many-to-many) for legacy compatibility
- Auto-computed status based on dates

#### contract_program (Pivot Table)
Legacy many-to-many relationship between contracts and programs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_id | bigint unsigned | FK, NOT NULL | References contracts.id |
| program_id | bigint unsigned | FK, NOT NULL | References programs.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Features:**
- Supports legacy data where contracts could be associated with multiple programs

#### contract_report_due_dates
Tracks report due dates for contracts.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_id | bigint unsigned | FK, NOT NULL | References contracts.id |
| due_date | date | NOT NULL | Report due date |
| report_name | varchar(255) | NULLABLE | Name of the report |
| status | varchar(255) | DEFAULT 'pending' | Status (pending, completed, overdue) |
| description | text | NULLABLE | Description of report |
| report_type | varchar(255) | NULLABLE | Type of report (quarterly, annual, final) |
| reminder_sent | boolean | DEFAULT false | Has reminder been sent? |
| reminder_sent_at | timestamp | NULLABLE | Last reminder sent timestamp |
| reminder_intervals | json | NULLABLE | Days before due date to send reminders [30,15,7,3,1,0] |
| active | boolean | DEFAULT true | Is due date active? |
| notes | text | NULLABLE | Notes |
| legacy_id | varchar(255) | NULLABLE | Legacy system ID |
| legacy_contract_id | varchar(255) | NULLABLE | Legacy contract ID |
| last_synced_at | timestamp | NULLABLE | Last sync timestamp |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

**Indexes:**
- (contract_id, due_date)
- (status, active)
- (due_date, reminder_sent)

**Relationships:**
- `belongsTo(Contract)`
- `hasMany(ContractReportDueDateEmail)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by

**Features:**
- Soft deletes enabled
- Reminder system with configurable intervals
- Tracks sent reminders to prevent duplicates

#### contract_reports
Tracks submitted contract reports.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_id | bigint unsigned | FK, NOT NULL | References contracts.id |
| contract_report_due_date_id | bigint unsigned | FK, NULLABLE | References contract_report_due_dates.id |
| title | varchar(255) | NOT NULL | Report title |
| description | text | NULLABLE | Report description |
| submitted_date | date | NULLABLE | Report submission date |
| status | varchar(255) | DEFAULT 'draft' | Status (draft, submitted, approved, rejected) |
| notes | text | NULLABLE | Report notes |
| metadata | json | NULLABLE | Additional report data |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

**Indexes:**
- (contract_id, status)
- (submitted_date, status)

**Relationships:**
- `belongsTo(Contract)`
- `belongsTo(ContractReportDueDate)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by

**Features:**
- Soft deletes enabled

#### due_date_emails
Email templates for contract report reminders.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Template name |
| subject | varchar(255) | NOT NULL | Email subject line |
| body | text | NOT NULL | Email body (supports variables) |
| days_before | integer | NOT NULL | Days before due date to send |
| is_active | boolean | DEFAULT true | Is template active? |
| user_id | bigint unsigned | FK, NULLABLE | References users.id (creator) |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `hasMany(ContractReportDueDateEmail)`
- `belongsTo(User)` - creator

**Features:**
- Supports variable substitution in subject and body
- Configurable reminder intervals

#### contract_report_due_date_emails
Tracks sent reminder emails.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_report_due_date_id | bigint unsigned | FK, NOT NULL | References contract_report_due_dates.id |
| due_date_email_id | bigint unsigned | FK, NOT NULL | References due_date_emails.id |
| sent_at | timestamp | NOT NULL | Timestamp email was sent |
| days_before_due | integer | NOT NULL | Days before due date when sent |
| recipient_email | varchar(255) | NOT NULL | Email recipient |
| email_subject | varchar(255) | NOT NULL | Email subject as sent |
| email_body | text | NOT NULL | Email body as sent |
| status | varchar(255) | DEFAULT 'sent' | Email status (sent, failed, bounced) |
| error_message | text | NULLABLE | Error message if failed |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (contract_report_due_date_id, due_date_email_id)
- (sent_at)

**Relationships:**
- `belongsTo(ContractReportDueDate)`
- `belongsTo(DueDateEmail)`

**Features:**
- Audit trail of all sent reminders
- Prevents duplicate reminders for same due date + interval combination

---

## Housing Rehabilitation Module

**Status:** Database layer complete, UI layer not implemented

#### properties
Tracks physical properties associated with participants.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| participant_id | bigint unsigned | FK, NOT NULL | References participants.id |
| household_id | bigint unsigned | FK, NULLABLE | References households.id |
| property_type_id | bigint unsigned | FK, NULLABLE | References reference_data.id |
| address_line_1 | varchar(255) | NOT NULL | Street address line 1 |
| address_line_2 | varchar(255) | NULLABLE | Street address line 2 |
| city | varchar(255) | NOT NULL | City |
| state | varchar(2) | NOT NULL | State abbreviation |
| zip_code | varchar(10) | NOT NULL | ZIP code |
| county | varchar(255) | NULLABLE | County |
| latitude | decimal(10,8) | NULLABLE | Latitude coordinate |
| longitude | decimal(11,8) | NULLABLE | Longitude coordinate |
| year_built | year | NULLABLE | Year property was built |
| bedrooms | integer | NULLABLE | Number of bedrooms |
| bathrooms | decimal(3,1) | NULLABLE | Number of bathrooms |
| square_footage | integer | NULLABLE | Property square footage |
| property_description | text | NULLABLE | Property description |
| status | enum | DEFAULT 'active' | Property status |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: active, inactive, pending

**Indexes:**
- (participant_id, household_id)
- (property_type_id)
- (status)

**Relationships:**
- `belongsTo(Participant)`
- `belongsTo(Household)`
- `belongsTo(ReferenceData)` - property_type
- `hasMany(PropertyNote)`
- `hasMany(PropertyDocument)`
- `hasMany(HousingJob)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by

#### contractors
Tracks contractors for housing rehabilitation jobs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| company_name | varchar(255) | NOT NULL | Company name |
| contact_first_name | varchar(255) | NULLABLE | Contact first name |
| contact_last_name | varchar(255) | NULLABLE | Contact last name |
| email | varchar(255) | NULLABLE | Contact email |
| phone | varchar(20) | NULLABLE | Phone number |
| fax | varchar(20) | NULLABLE | Fax number |
| address_line_1 | varchar(255) | NULLABLE | Street address line 1 |
| address_line_2 | varchar(255) | NULLABLE | Street address line 2 |
| city | varchar(255) | NULLABLE | City |
| state | varchar(2) | NULLABLE | State abbreviation |
| zip_code | varchar(10) | NULLABLE | ZIP code |
| license_number | varchar(255) | NULLABLE | License number |
| license_expiration | date | NULLABLE | License expiration date |
| insurance_provider | varchar(255) | NULLABLE | Insurance provider |
| insurance_policy_number | varchar(255) | NULLABLE | Insurance policy number |
| insurance_expiration | date | NULLABLE | Insurance expiration date |
| liability_coverage_amount | decimal(12,2) | NULLABLE | Liability coverage amount |
| ein | varchar(255) | NULLABLE | Employer Identification Number |
| services_offered | json | NULLABLE | Array of services offered |
| status | enum | DEFAULT 'active' | Contractor status |
| notes | text | NULLABLE | Contractor notes |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: active, inactive, suspended

**Indexes:**
- (company_name)
- (status)
- (license_number)

**Relationships:**
- `hasMany(HousingJob)`
- `hasMany(ContractorDocument)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by

#### housing_jobs
Tracks housing rehabilitation jobs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| job_number | varchar(255) | UNIQUE, NOT NULL | Auto-generated job number (JOB-YYYY-#####) |
| participant_id | bigint unsigned | FK, NOT NULL | References participants.id |
| household_id | bigint unsigned | FK, NULLABLE | References households.id |
| property_id | bigint unsigned | FK, NOT NULL | References properties.id |
| contractor_id | bigint unsigned | FK, NULLABLE | References contractors.id |
| program_id | bigint unsigned | FK, NOT NULL | References programs.id |
| service_id | bigint unsigned | FK, NULLABLE | References services.id |
| job_title | varchar(255) | NOT NULL | Job title |
| job_description | text | NULLABLE | Job description |
| job_type_id | bigint unsigned | FK, NULLABLE | References reference_data.id |
| status | enum | DEFAULT 'pending' | Job status |
| request_date | date | NOT NULL | Date job was requested |
| approval_date | date | NULLABLE | Date job was approved |
| start_date | date | NULLABLE | Job start date |
| target_completion_date | date | NULLABLE | Target completion date |
| actual_completion_date | date | NULLABLE | Actual completion date |
| estimated_cost | decimal(12,2) | NULLABLE | Estimated job cost |
| approved_amount | decimal(12,2) | NULLABLE | Approved amount |
| actual_cost | decimal(12,2) | NULLABLE | Actual job cost |
| funding_source | varchar(255) | NULLABLE | Funding source |
| work_order_number | varchar(255) | NULLABLE | Work order number |
| contract_number | varchar(255) | NULLABLE | Contract number |
| scope_of_work | json | NULLABLE | Detailed work items |
| priority | enum | DEFAULT 'medium' | Job priority |
| cancellation_reason | text | NULLABLE | Reason for cancellation |
| internal_notes | text | NULLABLE | Internal notes |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| approved_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: pending, approved, in_progress, on_hold, completed, cancelled, rejected
- `priority`: low, medium, high, emergency

**Indexes:**
- (participant_id, household_id)
- (property_id)
- (contractor_id)
- (program_id)
- (service_id)
- (status)
- (job_number)
- (request_date)

**Relationships:**
- `belongsTo(Participant)`
- `belongsTo(Household)`
- `belongsTo(Property)`
- `belongsTo(Contractor)`
- `belongsTo(Program)`
- `belongsTo(Service)`
- `belongsTo(ReferenceData)` - job_type
- `hasMany(JobContactLedger)`
- `hasMany(JobDocumentChecklist)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by
- `belongsTo(User)` - approved_by

**Features:**
- Auto-generates job number in format JOB-YYYY-##### on creation
- Can be linked to service enrollment for tracking
- Complete workflow from request through completion

#### job_contact_ledgers
Tracks communication and outreach related to housing jobs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| housing_job_id | bigint unsigned | FK, NOT NULL | References housing_jobs.id |
| contact_date | datetime | NOT NULL | Date/time of contact |
| contact_type | enum | NOT NULL | Type of contact |
| direction | enum | NOT NULL | Incoming or outgoing |
| contact_person | varchar(255) | NULLABLE | Person contacted |
| contact_method_details | varchar(255) | NULLABLE | Phone/email/etc. |
| contact_purpose | enum | NULLABLE | Purpose of contact |
| notes | text | NOT NULL | Contact notes |
| outcome | varchar(255) | NULLABLE | Contact outcome |
| follow_up_date | date | NULLABLE | Follow-up date |
| follow_up_action | varchar(255) | NULLABLE | Follow-up action needed |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `contact_type`: phone_call, email, in_person, site_visit, mail, text, other
- `direction`: incoming, outgoing
- `contact_purpose`: initial_inquiry, status_update, scheduling, inspection, approval, issue_resolution, documentation, payment, other

**Indexes:**
- (housing_job_id)
- (contact_date)
- (contact_type)

**Relationships:**
- `belongsTo(HousingJob)`
- `belongsTo(User)` - created_by

#### job_document_checklists
Tracks required and uploaded documents for housing jobs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| housing_job_id | bigint unsigned | FK, NOT NULL | References housing_jobs.id |
| document_name | varchar(255) | NOT NULL | Document name |
| document_description | text | NULLABLE | Document description |
| is_required | boolean | DEFAULT true | Is document required? |
| file_path | varchar(255) | NULLABLE | Path to uploaded file |
| original_filename | varchar(255) | NULLABLE | Original filename |
| mime_type | varchar(255) | NULLABLE | File MIME type |
| file_size | integer | NULLABLE | File size in bytes |
| uploaded_at | datetime | NULLABLE | Upload timestamp |
| uploaded_by | bigint unsigned | FK, NULLABLE | References users.id |
| verified_at | datetime | NULLABLE | Verification timestamp |
| verified_by | bigint unsigned | FK, NULLABLE | References users.id |
| status | enum | DEFAULT 'pending' | Document status |
| rejection_reason | text | NULLABLE | Reason for rejection |
| sort_order | integer | DEFAULT 0 | Display order |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: pending, uploaded, verified, rejected

**Indexes:**
- (housing_job_id)
- (status)
- (is_required)

**Relationships:**
- `belongsTo(HousingJob)`
- `belongsTo(User)` - uploaded_by
- `belongsTo(User)` - verified_by
- `belongsTo(User)` - created_by

#### property_notes
Notes associated with properties.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| property_id | bigint unsigned | FK, NOT NULL | References properties.id |
| note | text | NOT NULL | Note text |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(Property)`
- `belongsTo(User)` - created_by

#### property_documents
Documents associated with properties.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| property_id | bigint unsigned | FK, NOT NULL | References properties.id |
| document_name | varchar(255) | NOT NULL | Document name |
| file_path | varchar(255) | NOT NULL | File path |
| file_size | integer | NULLABLE | File size in bytes |
| mime_type | varchar(255) | NULLABLE | File MIME type |
| uploaded_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(Property)`
- `belongsTo(User)` - uploaded_by

#### contractor_documents
Documents associated with contractors.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contractor_id | bigint unsigned | FK, NOT NULL | References contractors.id |
| document_name | varchar(255) | NOT NULL | Document name |
| document_type | varchar(255) | NULLABLE | Document type (license, insurance, etc.) |
| file_path | varchar(255) | NOT NULL | File path |
| file_size | integer | NULLABLE | File size in bytes |
| mime_type | varchar(255) | NULLABLE | File MIME type |
| expiration_date | date | NULLABLE | Document expiration date |
| uploaded_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Relationships:**
- `belongsTo(Contractor)`
- `belongsTo(User)` - uploaded_by

---

## Reference Data System

The reference data system provides a flexible, versioned way to manage dropdown values and categorical data across the application.

#### reference_data_sets
Manages versions of reference data configurations.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Set name |
| description | text | NULLABLE | Set description |
| version | varchar(20) | NOT NULL, UNIQUE | Version identifier |
| is_active | boolean | DEFAULT false | Is this the active version? |
| is_draft | boolean | DEFAULT true | Is this a draft? |
| is_import_default | boolean | DEFAULT false | Default for imports? |
| activated_at | timestamp | NULLABLE | Activation timestamp |
| created_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (is_active, activated_at)
- UNIQUE(version)

**Relationships:**
- `hasMany(ReferenceData)`
- `hasMany(Participant)`
- `belongsTo(User)` - created_by

**Features:**
- Only one set can be active at a time
- Allows for versioning of reference data
- Can designate a default set for imports

#### reference_data
Stores reference data values (dropdown options, categories, etc.).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| reference_data_set_id | bigint unsigned | FK, NULLABLE | References reference_data_sets.id |
| category | varchar(255) | NOT NULL | Category (e.g., 'gender', 'ethnicity') |
| value | varchar(255) | NOT NULL | Option value |
| label | varchar(255) | NOT NULL | Display label |
| description | text | NULLABLE | Option description |
| sort_order | integer | DEFAULT 0 | Display order |
| active | boolean | DEFAULT true | Is option active? |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (category, active)
- (category, sort_order)
- UNIQUE(category, value)

**Relationships:**
- `belongsTo(ReferenceDataSet)`

**Common Categories:**
- `property_type`: rental, owner-occupied, facade, etc.
- `job_type`: weatherization, repair, renovation, etc.
- Custom categories can be added as needed

---

## Import & Export System

#### custom_import_templates
Defines reusable import templates for various data sources.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Template name |
| description | text | NULLABLE | Template description |
| type | enum | DEFAULT 'custom' | Template type |
| source_system | varchar(255) | NULLABLE | Source system name (e.g., 'HMIS_Pennsylvania') |
| active | boolean | DEFAULT true | Is template active? |
| field_mappings | json | NOT NULL | Maps CSV columns to database fields |
| transformation_rules | json | NULLABLE | Data transformation rules |
| validation_rules | json | NULLABLE | Custom validation rules |
| import_settings | json | NULLABLE | Additional settings (default program, etc.) |
| created_by | bigint unsigned | FK, NOT NULL | References users.id |
| updated_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `type`: preconfigured, custom

**Indexes:**
- (type, active)
- (source_system, active)

**Relationships:**
- `hasMany(CustomImportLog)`
- `belongsTo(User)` - created_by
- `belongsTo(User)` - updated_by

#### custom_import_logs
Tracks import operations using custom templates.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| template_id | bigint unsigned | FK, NOT NULL | References custom_import_templates.id |
| user_id | bigint unsigned | FK, NOT NULL | References users.id |
| original_filename | varchar(255) | NOT NULL | Original filename |
| status | varchar(255) | NOT NULL | Import status (success, partial, failed) |
| total_records | integer | DEFAULT 0 | Total records processed |
| successful_records | integer | DEFAULT 0 | Successfully imported records |
| failed_records | integer | DEFAULT 0 | Failed records |
| errors | json | NULLABLE | Error details |
| warnings | json | NULLABLE | Warning details |
| notes | text | NULLABLE | Import notes |
| started_at | timestamp | NOT NULL | Import start time |
| completed_at | timestamp | NULLABLE | Import completion time |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (template_id, status)
- (user_id, created_at)

**Relationships:**
- `belongsTo(CustomImportTemplate)`
- `belongsTo(User)`

#### import_audit_logs
Comprehensive audit log for all import operations.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| user_id | bigint unsigned | FK, NOT NULL | References users.id |
| import_type | varchar(255) | NOT NULL | Import type (participant, hmis, rx_office, etc.) |
| file_name | varchar(255) | NOT NULL | Uploaded filename |
| file_size | bigint | NOT NULL | File size in bytes |
| reference_data_set_id | bigint unsigned | FK, NULLABLE | References reference_data_sets.id |
| records_processed | integer | DEFAULT 0 | Records processed |
| records_created | integer | DEFAULT 0 | Records created |
| records_updated | integer | DEFAULT 0 | Records updated |
| records_skipped | integer | DEFAULT 0 | Records skipped |
| status | enum | DEFAULT 'processing' | Import status |
| started_at | timestamp | NOT NULL | Import start time |
| completed_at | timestamp | NULLABLE | Import completion time |
| error_summary | text | NULLABLE | Error summary |
| ip_address | varchar(45) | NOT NULL | User IP address |
| user_agent | text | NULLABLE | User agent string |
| options | json | NULLABLE | Import options used |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: processing, completed, failed, cancelled

**Indexes:**
- (user_id, created_at)
- (status)
- (import_type)

**Relationships:**
- `belongsTo(User)`
- `belongsTo(ReferenceDataSet)`

#### program_manager_uploads
Tracks uploads from external program managers.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| type | enum | NOT NULL | Upload type |
| program_manager_name | varchar(255) | NOT NULL | Program manager name |
| program_manager_email | varchar(255) | NOT NULL | Program manager email |
| organization | varchar(255) | NULLABLE | Organization |
| phone | varchar(255) | NULLABLE | Phone number |
| notes | text | NULLABLE | Upload notes |
| file_path | varchar(255) | NOT NULL | File path |
| original_filename | varchar(255) | NOT NULL | Original filename |
| file_size | bigint unsigned | NOT NULL | File size in bytes |
| ip_address | varchar(255) | NULLABLE | IP address |
| submitted_at | timestamp | DEFAULT CURRENT_TIMESTAMP | Submission timestamp |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `type`: general, hmis, rx_office

**Features:**
- Allows external program managers to upload data files
- Separate from authenticated user imports

#### potential_duplicates
Tracks potential duplicate participants detected during imports.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| source_type | varchar(255) | NOT NULL | Import source (manual, hmis_import, rx_office_import, etc.) |
| source_identifier | varchar(255) | NULLABLE | Import log ID, session ID, etc. |
| proposed_data | json | NOT NULL | The new participant data proposed |
| matched_participant_id | bigint unsigned | FK, NOT NULL | References participants.id |
| confidence_score | decimal(5,2) | NOT NULL | Match confidence (0.00 to 100.00) |
| matching_criteria | json | NOT NULL | Fields matched and their weights |
| status | enum | DEFAULT 'pending' | Review status |
| reviewed_by | bigint unsigned | FK, NULLABLE | References users.id |
| reviewed_at | timestamp | NULLABLE | Review timestamp |
| review_notes | text | NULLABLE | Review notes |
| merge_instructions | json | NULLABLE | How to merge if approved |
| created_by | bigint unsigned | FK, NOT NULL | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `status`: pending, approved_merge, approved_separate, rejected

**Indexes:**
- (status, created_at)
- (matched_participant_id, status)
- (source_type, created_at)

**Relationships:**
- `belongsTo(Participant)` - matched_participant
- `belongsTo(User)` - reviewed_by
- `belongsTo(User)` - created_by

**Features:**
- Fuzzy matching algorithm to detect duplicates
- Configurable confidence thresholds
- Review workflow for manual approval

---

## External Integrations

### DonorPerfect Integration

#### donor_perfect_settings
Configuration for DonorPerfect API integration.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| api_url | varchar(255) | NULLABLE | API endpoint URL |
| api_key | text | NULLABLE | API key (encrypted) |
| api_login | varchar(255) | NULLABLE | API login (encrypted) |
| api_password | text | NULLABLE | API password (encrypted) |
| selected_fields | json | NULLABLE | Selected donor/gift fields to display |
| cache_duration | integer | DEFAULT 15 | Cache duration in minutes |
| is_active | boolean | DEFAULT false | Is integration active? |
| last_connection_test | timestamp | NULLABLE | Last connection test timestamp |
| connection_status | varchar(255) | NULLABLE | Connection status |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Features:**
- Encrypted credential storage
- Configurable field selection
- Connection testing

#### donor_perfect_gift_cache
Caches DonorPerfect gift data to reduce API calls.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| cache_key | varchar(255) | UNIQUE, NOT NULL | Cache key |
| start_date | date | NULLABLE | Query start date |
| end_date | date | NULLABLE | Query end date |
| fields_hash | varchar(255) | NULLABLE | Hash of selected fields |
| gift_data | json | NOT NULL | Cached gift data |
| record_count | integer | DEFAULT 0 | Number of records |
| expires_at | timestamp | NOT NULL | Cache expiration timestamp |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (cache_key) - UNIQUE
- (start_date, end_date)
- (fields_hash)
- (expires_at)

**Features:**
- 15-minute cache by default
- Hash-based cache invalidation when fields change

#### donor_perfect_code_mappings
Maps DonorPerfect codes to internal contract/program codes.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| dp_field | varchar(255) | NOT NULL | DonorPerfect field name |
| dp_code | varchar(255) | NOT NULL | DonorPerfect code value |
| internal_code | varchar(255) | NOT NULL | Internal system code |
| description | text | NULLABLE | Mapping description |
| is_active | boolean | DEFAULT true | Is mapping active? |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Features:**
- Maps DonorPerfect gift codes to internal grant/contract codes
- Supports custom field mappings

### Sage Intacct Integration

#### sage_intacct_settings
Configuration for Sage Intacct API integration.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| company_id | varchar(255) | NOT NULL | Intacct company ID |
| sender_id | varchar(255) | NOT NULL | Sender ID |
| sender_password | text | NOT NULL | Sender password (encrypted) |
| web_services_user_id | varchar(255) | NOT NULL | Web services user ID |
| web_services_password | text | NOT NULL | Web services password (encrypted) |
| endpoint_url | varchar(255) | DEFAULT 'https://api.intacct.com/ia/xml/xmlgw.phtml' | API endpoint |
| custom_object_name | varchar(255) | DEFAULT 'Donor Perfect Gifts' | Custom object name |
| location_id | varchar(255) | NULLABLE | Intacct location ID |
| department_id | varchar(255) | NULLABLE | Intacct department ID |
| code_mapping_enabled | boolean | DEFAULT false | Enable code mapping? |
| sync_schedule | varchar(255) | NULLABLE | Cron expression for sync schedule |
| last_sync_at | timestamp | NULLABLE | Last sync timestamp |
| is_active | boolean | DEFAULT false | Is integration active? |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (is_active)
- (last_sync_at)

**Features:**
- Encrypted credential storage
- Configurable custom object name
- Scheduled sync capability

#### sage_intacct_field_mappings
Maps DonorPerfect fields to Sage Intacct custom object fields.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| dp_field_name | varchar(255) | NOT NULL | DonorPerfect field name |
| intacct_field_name | varchar(255) | NOT NULL | Intacct field name |
| field_type | varchar(255) | NOT NULL | Field data type |
| is_required | boolean | DEFAULT false | Is field required? |
| default_value | varchar(255) | NULLABLE | Default value if empty |
| transformation_rule | text | NULLABLE | Transformation rule/formula |
| is_active | boolean | DEFAULT true | Is mapping active? |
| sort_order | integer | DEFAULT 0 | Display order |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Features:**
- Flexible field mapping
- Transformation rules for data conversion
- Default values for missing data

#### sage_intacct_sync_history
Tracks synchronization history between systems.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| sync_type | varchar(255) | NOT NULL | Type of sync (manual, scheduled, etc.) |
| status | varchar(255) | NOT NULL | Sync status (pending, processing, completed, failed) |
| started_at | timestamp | NOT NULL | Sync start time |
| completed_at | timestamp | NULLABLE | Sync completion time |
| records_processed | integer | DEFAULT 0 | Total records processed |
| records_synced | integer | DEFAULT 0 | Successfully synced records |
| records_failed | integer | DEFAULT 0 | Failed records |
| error_summary | text | NULLABLE | Error summary |
| sync_details | json | NULLABLE | Detailed sync information |
| triggered_by | bigint unsigned | FK, NULLABLE | References users.id |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (status)
- (started_at)
- (sync_type)

**Relationships:**
- `hasMany(SageIntacctSyncItem)`
- `belongsTo(User)` - triggered_by

#### sage_intacct_sync_items
Individual records within a sync operation.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| sync_history_id | bigint unsigned | FK, NOT NULL | References sage_intacct_sync_history.id |
| donor_perfect_gift_id | varchar(255) | NOT NULL | DonorPerfect gift ID |
| intacct_record_no | varchar(255) | NULLABLE | Intacct record number |
| status | varchar(255) | NOT NULL | Item status (pending, synced, failed, skipped) |
| error_message | text | NULLABLE | Error message if failed |
| gift_data | json | NULLABLE | Gift data snapshot |
| sync_payload | json | NULLABLE | Payload sent to Intacct |
| intacct_response | json | NULLABLE | Response from Intacct |
| synced_at | timestamp | NULLABLE | Sync timestamp |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (sync_history_id)
- (status)
- (donor_perfect_gift_id)

**Relationships:**
- `belongsTo(SageIntacctSyncHistory)`

---

## Support Tables

#### site_settings
Application-wide settings stored as key-value pairs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| key | varchar(255) | UNIQUE, NOT NULL | Setting key |
| value | text | NULLABLE | Setting value |
| type | varchar(255) | DEFAULT 'text' | Value type (text, file, boolean, etc.) |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Default Settings:**
- `site_name`: CAP TRACK
- `site_logo`: NULL (file path)
- `site_favicon`: NULL (file path)

#### poverty_guidelines
Federal poverty guidelines for calculating eligibility.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| year | year | NOT NULL | Guideline year |
| region | enum | NOT NULL | Geographic region |
| family_size | integer | NOT NULL | Number of family members |
| poverty_50 | decimal(10,2) | NOT NULL | 50% of poverty level |
| poverty_75 | decimal(10,2) | NOT NULL | 75% of poverty level |
| poverty_100 | decimal(10,2) | NOT NULL | 100% of poverty level |
| poverty_125 | decimal(10,2) | NOT NULL | 125% of poverty level |
| poverty_130 | decimal(10,2) | NOT NULL | 130% of poverty level |
| poverty_150 | decimal(10,2) | NOT NULL | 150% of poverty level |
| poverty_200 | decimal(10,2) | NOT NULL | 200% of poverty level |
| poverty_250 | decimal(10,2) | NOT NULL | 250% of poverty level |
| poverty_300 | decimal(10,2) | NOT NULL | 300% of poverty level |
| poverty_400 | decimal(10,2) | NOT NULL | 400% of poverty level |
| poverty_500 | decimal(10,2) | NOT NULL | 500% of poverty level |
| period | enum | NOT NULL | Monthly or annual |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Enum Values:**
- `region`: 48_contiguous, alaska, hawaii
- `period`: monthly, annual

**Indexes:**
- UNIQUE(year, region, family_size, period)

**Features:**
- Used to calculate household poverty percentage
- Supports different poverty level thresholds
- Separate guidelines for different regions

#### media
Spatie Media Library table for file attachments.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| model_type | varchar(255) | NOT NULL | Polymorphic model type |
| model_id | bigint unsigned | NOT NULL | Polymorphic model ID |
| uuid | uuid | NULLABLE, UNIQUE | Unique identifier |
| collection_name | varchar(255) | NOT NULL | Collection name |
| name | varchar(255) | NOT NULL | File name |
| file_name | varchar(255) | NOT NULL | Actual filename |
| mime_type | varchar(255) | NULLABLE | MIME type |
| disk | varchar(255) | NOT NULL | Storage disk |
| conversions_disk | varchar(255) | NULLABLE | Conversions storage disk |
| size | bigint unsigned | NOT NULL | File size in bytes |
| manipulations | json | NOT NULL | Image manipulations |
| custom_properties | json | NOT NULL | Custom properties |
| generated_conversions | json | NOT NULL | Generated conversions |
| responsive_images | json | NOT NULL | Responsive image data |
| order_column | integer unsigned | NULLABLE | Sort order |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (model_type, model_id)
- (uuid)
- (order_column)

**Features:**
- Polymorphic relationship (can attach to any model)
- Image conversion support
- Responsive image generation
- Used by contracts for document management

#### check_image_audit_logs
Audit trail for check image access (financial security).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| user_id | bigint unsigned | FK, NOT NULL | References users.id |
| action | varchar(255) | NOT NULL | Action performed (view, export, etc.) |
| gift_id | varchar(255) | NULLABLE | DonorPerfect gift ID |
| check_number | varchar(255) | NULLABLE | Check number |
| image_path | varchar(255) | NULLABLE | Image file path |
| ip_address | varchar(45) | NOT NULL | User IP address |
| user_agent | text | NULLABLE | User agent string |
| metadata | json | NULLABLE | Additional metadata |
| created_at | timestamp | NULLABLE | Creation timestamp |
| updated_at | timestamp | NULLABLE | Last update timestamp |

**Indexes:**
- (user_id, created_at)
- (action)

**Relationships:**
- `belongsTo(User)`

**Features:**
- Complete audit trail for check image access
- Security and compliance tracking

#### cache
Laravel cache table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| key | varchar(255) | PK | Cache key |
| value | mediumtext | NOT NULL | Cached value |
| expiration | integer | NOT NULL | Expiration timestamp |

#### jobs
Laravel queue table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| queue | varchar(255) | NOT NULL | Queue name |
| payload | longtext | NOT NULL | Job payload |
| attempts | tinyint unsigned | NOT NULL | Attempt count |
| reserved_at | integer unsigned | NULLABLE | Reserved timestamp |
| available_at | integer unsigned | NOT NULL | Available timestamp |
| created_at | integer unsigned | NOT NULL | Creation timestamp |

**Indexes:**
- (queue)

#### sessions
Laravel session table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | varchar(255) | PK | Session ID |
| user_id | bigint unsigned | FK, NULLABLE | References users.id |
| ip_address | varchar(45) | NULLABLE | IP address |
| user_agent | text | NULLABLE | User agent |
| payload | longtext | NOT NULL | Session payload |
| last_activity | integer | NOT NULL | Last activity timestamp |

**Indexes:**
- (user_id)
- (last_activity)

#### password_reset_tokens
Password reset tokens.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| email | varchar(255) | PK | User email |
| token | varchar(255) | NOT NULL | Reset token |
| created_at | timestamp | NULLABLE | Creation timestamp |

---

## Key Relationships

### Core Entity Relationships

```
User
├── belongsToMany(Role) via user_role
├── belongsToMany(Program) via user_program
└── hasMany(ProgramEnrollment) - created enrollments

Role
├── belongsToMany(User) via user_role
└── belongsToMany(Permission) via role_permission

Household
├── hasMany(Participant)
├── hasOne(Participant) - head of household
├── hasMany(HouseholdAddress)
├── hasOne(HouseholdAddress) - current address
├── hasMany(Property)
└── hasMany(HousingJob)

Participant
├── belongsTo(Household)
├── belongsTo(ReferenceDataSet)
├── hasMany(ProgramEnrollment)
├── hasMany(ServiceEnrollment)
├── hasMany(Property)
└── hasMany(HousingJob)

Program
├── belongsTo(User) - program_manager
├── belongsToMany(User) via user_program
├── hasMany(Service)
├── hasMany(Contract) - direct FK
├── belongsToMany(Contract) via contract_program - legacy
├── hasMany(WorkPlan)
├── hasMany(ProgramEnrollment)
└── hasMany(HousingJob)

Service
├── belongsTo(Program)
├── hasMany(WorkPlanGoal)
└── hasMany(ServiceEnrollment)

ProgramEnrollment
├── belongsTo(Participant)
├── belongsTo(Program)
└── hasMany(ServiceEnrollment)

ServiceEnrollment
├── belongsTo(Participant)
├── belongsTo(Service)
└── belongsTo(ProgramEnrollment)

Contract
├── belongsTo(Program) - direct FK
├── belongsToMany(Program) via contract_program - legacy
├── belongsTo(Funder)
├── hasMany(ContractReportDueDate)
└── hasMany(ContractReport)

Property
├── belongsTo(Participant)
├── belongsTo(Household)
├── belongsTo(ReferenceData) - property_type
├── hasMany(PropertyNote)
├── hasMany(PropertyDocument)
└── hasMany(HousingJob)

HousingJob
├── belongsTo(Participant)
├── belongsTo(Household)
├── belongsTo(Property)
├── belongsTo(Contractor)
├── belongsTo(Program)
├── belongsTo(Service)
├── belongsTo(ReferenceData) - job_type
├── hasMany(JobContactLedger)
└── hasMany(JobDocumentChecklist)
```

### Security Model

```
User Program Access:
- Users belong to multiple Programs via user_program pivot
- Admins have access to ALL programs
- Non-admin users only see data for their assigned programs

Data Access Hierarchy:
User → Program → Service → ServiceEnrollment → Participant
                  ↓
            ProgramEnrollment → Participant → Household
```

**Access Rules:**
1. Admins can access all data
2. Contract Managers can access all programs
3. Regular users can only access:
   - Programs they're assigned to
   - Participants enrolled in their programs
   - Services within their programs
   - Households with members in their programs

---

## Design Patterns & Features

### 1. Auto-Generated Identifiers

**Household Code:**
- Format: 7-character alphanumeric (e.g., "A3K7M9P")
- Generated on household creation
- Unique constraint enforced

**Housing Job Number:**
- Format: JOB-YYYY-##### (e.g., "JOB-2025-00123")
- Generated on housing_job creation
- Sequence resets yearly

### 2. Audit Trail

Most tables include:
- `created_at` / `updated_at`: Automatic Laravel timestamps
- `created_by` / `updated_by`: User ID who performed action
- Some tables: `deleted_at` for soft deletes

### 3. Soft Deletes

Enabled on:
- contracts
- contract_reports
- contract_report_due_dates
- funders

### 4. JSON Fields

Used for flexible/complex data:
- **participants**: race, income_sources, other_income_sources, non_cash_benefits, health_insurance_sources, disability_types, assistance_needs
- **service_enrollments**: outcomes
- **housing_jobs**: scope_of_work
- **contractors**: services_offered
- **contracts**: documents (via media library)
- **custom_import_templates**: field_mappings, transformation_rules, validation_rules, import_settings

### 5. Computed/Derived Fields

**Participant:**
- `full_name`: Computed from first/middle/last/suffix
- `age`: Computed from date_of_birth
- `csbg_age_group`: Auto-computed age group classification
- `is_disconnected_youth`: Auto-computed based on age, work status, education
- `csbg_income_classification`: Auto-computed from household income sources

**Contract:**
- `status`: Derived from archived flag, start_date, end_date

**HousingJob:**
- `is_overdue`: Computed from target_completion_date and status
- `days_overdue`: Computed days past target_completion_date

### 6. Enum Fields

Extensive use of enum fields for data consistency:
- Participant demographics (gender, ethnicity, education_level, etc.)
- Enrollment statuses
- Housing job statuses and priorities
- Contract statuses

### 7. Foreign Key Constraints

All foreign keys have appropriate constraints:
- `onDelete('cascade')`: Child records deleted when parent deleted
- `onDelete('set null')`: FK set to null when parent deleted
- `onDelete('restrict')`: Prevents parent deletion if children exist

### 8. Indexes

Strategic indexes on:
- Foreign keys (automatic)
- Frequently queried fields (status, active, dates)
- Composite indexes for common query patterns
- Unique constraints where needed

### 9. Polymorphic Relationships

**media** table:
- `model_type` and `model_id` allow attachments to any entity
- Used by contracts for document management

### 10. Versioning & Configuration

**Reference Data Sets:**
- Versioned reference data
- Only one active set at a time
- Allows rollback to previous versions

**Import Templates:**
- Reusable configurations
- Preconfigured and custom types

### 11. Caching Strategy

**donor_perfect_gift_cache:**
- Reduces API calls
- Configurable expiration (default 15 minutes)
- Hash-based cache invalidation

### 12. Legacy Compatibility

**contract_program pivot:**
- Supports legacy many-to-many contract-program relationships
- Current system uses direct FK, but maintains compatibility

**legacy_id fields:**
- Tracks original IDs from legacy systems
- Supports data migration and synchronization

### 13. Multi-Tenancy Considerations

While not strictly multi-tenant, the system supports:
- Program-based data segregation
- User program assignments
- Permission-based access control

### 14. CSBG Compliance

**Module 4 Section C Fields:**
- All required CSBG demographic fields present
- Computed fields for CSBG reporting
- Income classification logic
- Disconnected youth identification

### 15. Address History

**household_addresses:**
- Complete address change history
- Effective dates and end dates
- Reasons for changes
- Current address flag

---

## Database Statistics

### Table Count by Module

- **Core (User/RBAC):** 5 tables + 3 pivots
- **Participant/Household:** 3 tables
- **Program/Service:** 4 tables
- **Enrollment:** 2 tables
- **Contract Management:** 7 tables
- **Housing Module:** 10 tables
- **Reference Data:** 2 tables
- **Import/Export:** 5 tables
- **External Integrations:** 8 tables (DonorPerfect + Sage Intacct)
- **Support:** 7 tables (settings, cache, jobs, etc.)

**Total: ~56 tables**

### JSON Field Usage

- 16 tables use JSON fields
- Most common uses: arrays of selections, metadata, configuration

### Enum Field Usage

- 12 tables use enum fields
- Ensures data consistency for categorical data

### Foreign Key Count

- Approximately 90+ foreign key relationships
- All properly indexed and constrained

---

## Migration Strategy

### Current State

- All migrations in `/database/migrations/`
- Chronologically ordered by timestamp
- Some migrations include data seeding

### Key Migration Features

1. **Conditional Creation:** Most migrations check `if (!Schema::hasTable())` before creating
2. **Foreign Key Ordering:** Migrations ordered to create parent tables before children
3. **Index Creation:** Indexes created alongside table definitions
4. **Data Fixes:** Several migrations fix historical data issues

### Running Migrations

```bash
# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed

# Standard migration
php artisan migrate

# Rollback last batch
php artisan migrate:rollback
```

---

## Performance Considerations

### Indexes

Strategic indexes on:
- All foreign keys (automatic)
- Status fields
- Date fields used in queries
- Composite indexes for common multi-field queries

### Potential Optimizations

1. **Participant queries** - Heavy JSON field usage may benefit from:
   - Generated columns for frequently queried JSON keys
   - Full-text indexes on text fields

2. **Enrollment queries** - Consider:
   - Materialized views for active enrollments
   - Cached counts for dashboard metrics

3. **Contract reports** - Consider:
   - Indexed views for overdue reports
   - Eager loading of related data

4. **Housing jobs** - Consider:
   - Status transition audit trail
   - Indexed job_number for quick lookups

---

## Security Considerations

### Sensitive Data

**Encrypted Fields:**
- DonorPerfect API credentials
- Sage Intacct API credentials

**Access Control:**
- Program-based data segregation
- Permission-based feature access
- Role hierarchy (Admin > Contract Manager > Program User)

**Audit Trails:**
- Import audit logs
- Check image audit logs
- Created_by/Updated_by on most tables

### Data Privacy

**Participant Data:**
- Segregated sensitive vs. public fields
- Program-based access control
- No direct deletion (use soft deletes for critical data)

**PII Protection:**
- Household addresses
- Participant contact information
- Financial information

---

## Backup & Recovery

### Recommended Strategy

1. **Daily backups** of entire database
2. **Transaction log backups** every 4 hours
3. **Weekly full backups** with longer retention
4. **Test restore** monthly

### Critical Tables (Priority 1)

- participants
- households
- program_enrollments
- service_enrollments
- contracts
- contract_report_due_dates

### Important Tables (Priority 2)

- users
- roles
- permissions
- programs
- services
- housing_jobs

### Recoverable Tables (Priority 3)

- cache
- sessions
- jobs
- donor_perfect_gift_cache

---

## Future Enhancements

### Potential Additions

1. **Housing Module UI:** Complete Livewire components for housing rehabilitation workflow
2. **Reporting Module:** Pre-built CSBG reports and dashboards
3. **Document Management:** Enhanced document versioning and approval workflows
4. **Notifications:** In-app notification system for reminders and alerts
5. **API Layer:** RESTful API for mobile apps or external integrations
6. **Advanced Analytics:** Data warehousing and business intelligence integration
7. **Multi-Language:** Support for Spanish language interface and data

### Schema Enhancements

1. **Participant notes history:** Track changes to participant notes
2. **Service outcomes tracking:** More structured outcome measurements
3. **Budget tracking:** Link contracts to detailed budgets and actuals
4. **Time tracking:** Staff time allocation to programs and services
5. **Document templates:** Reusable document templates with merge fields

---

## Conclusion

The CAP TRACK database design provides a comprehensive, flexible, and scalable foundation for Community Action Partnership agencies to manage participant data, program services, contract tracking, and housing rehabilitation projects. The design emphasizes:

- **CSBG Compliance:** Full support for required federal reporting
- **Security:** Role-based access control with program-level data segregation
- **Flexibility:** JSON fields and reference data system allow customization
- **Audit Trail:** Comprehensive tracking of data changes and user actions
- **Integration:** Support for external systems (DonorPerfect, Sage Intacct)
- **Scalability:** Indexed appropriately for growing data volumes

The modular design allows agencies to enable features as needed (housing module, contract management, external integrations) while maintaining a consistent core data model.

---

**Document Version:** 1.0
**Generated:** October 22, 2025
**Next Review:** January 2026
