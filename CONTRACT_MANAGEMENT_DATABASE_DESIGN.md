# Contract Management System - Database Design

**CAP TRACK - Contract & Grant Tracking Module**

**Version:** 1.0
**Last Updated:** October 22, 2025
**Database Engine:** MySQL
**Database Name:** CC_CACLV_DATA

---

## Table of Contents

1. [Overview](#overview)
2. [Module Purpose](#module-purpose)
3. [Core Tables](#core-tables)
4. [Entity Relationships](#entity-relationships)
5. [Contract Lifecycle](#contract-lifecycle)
6. [Report Reminder System](#report-reminder-system)
7. [Document Management](#document-management)
8. [Program Association Architecture](#program-association-architecture)
9. [Data Flow Diagrams](#data-flow-diagrams)
10. [Business Logic & Rules](#business-logic--rules)
11. [Query Examples](#query-examples)
12. [Security & Permissions](#security--permissions)
13. [Legacy System Migration](#legacy-system-migration)
14. [Performance Optimization](#performance-optimization)
15. [API Integration Points](#api-integration-points)

---

## Overview

The Contract Management System is a comprehensive module within CAP TRACK designed to track funding contracts, grants, reporting requirements, and compliance deadlines for Community Action Partnership agencies. The system provides automated reminder notifications, document management, and multi-program contract tracking.

**Key Capabilities:**
- Track unlimited contracts/grants with detailed metadata
- Associate contracts with one or more programs
- Manage reporting due dates with automated reminders
- Store and version contract documents
- Track funder information and relationships
- Monitor contract status (Draft, Upcoming, Active, Expired, Archived)
- Audit trail for all contract activities
- Legacy system compatibility and data migration support

**Database Objects:**
- **7 Primary Tables:** funders, contracts, contract_report_due_dates, contract_reports, due_date_emails, contract_report_due_date_emails, contract_program
- **3 Related Tables:** programs, users, media
- **90+ Foreign Key Relationships** in contract module
- **Soft Deletes:** Enabled on critical tables for data retention
- **Audit Fields:** created_by, updated_by, created_at, updated_at on all tables

---

## Module Purpose

### Business Requirements

The Contract Management module addresses the following organizational needs:

1. **Centralized Contract Repository**
   - Single source of truth for all funding agreements
   - Historical record of past and current contracts
   - Easy retrieval of contract documents and details

2. **Compliance Management**
   - Track multiple reporting requirements per contract
   - Automated reminder system to prevent missed deadlines
   - Status tracking for report submissions

3. **Funder Relationship Management**
   - Maintain funder contact information
   - Track all contracts by funder
   - Historical funding analysis

4. **Multi-Program Coordination**
   - Support contracts funding multiple programs
   - Program managers see only relevant contracts
   - Cross-program contract reporting

5. **Financial Tracking**
   - Contract amounts and award dates
   - Budget period tracking (start/end dates)
   - CFDA number tracking for federal grants

### User Roles

- **Administrator:** Full access to all contracts and settings
- **Contract Manager:** Manage all contracts, configure reminders, run reports
- **Program Manager:** View/edit contracts for assigned programs only
- **Program Staff:** View contracts for assigned programs (read-only)
- **Contract Dashboard:** View-only role for contract status dashboard

---

## Core Tables

### funders

**Purpose:** Stores information about funding organizations (federal agencies, state departments, foundations, etc.)

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Organization name |
| code | varchar(255) | NULLABLE | Short code/abbreviation |
| description | text | NULLABLE | Organization description |
| contact_person | varchar(255) | NULLABLE | Primary contact name |
| contact_email | varchar(255) | NULLABLE | Contact email address |
| contact_phone | varchar(255) | NULLABLE | Contact phone number |
| address | text | NULLABLE | Mailing address |
| website | varchar(255) | NULLABLE | Organization website |
| active | boolean | DEFAULT true | Is funder active? |
| created_by | bigint unsigned | FK → users.id | User who created record |
| updated_by | bigint unsigned | FK → users.id | User who last updated |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

#### Indexes
- `(active, name)` - Optimize active funder listings

#### Relationships
- `hasMany(Contract)` - One funder can have many contracts
- `belongsTo(User)` as created_by
- `belongsTo(User)` as updated_by

#### Business Rules
- Funder names should be unique (enforced at application level)
- Cannot delete funder if active contracts exist
- Soft delete preserves historical data
- Code field often used for accounting system integration

#### Example Data
```sql
INSERT INTO funders (name, code, contact_person, contact_email, active) VALUES
('Department of Health & Human Services', 'HHS', 'Jane Smith', 'jane.smith@hhs.gov', true),
('Community Development Block Grant', 'CDBG', 'John Doe', 'john.doe@hud.gov', true),
('State Housing Authority', 'SHA', 'Maria Garcia', 'mgarcia@state.gov', true);
```

---

### contracts

**Purpose:** Central table storing all contract/grant information, terms, and metadata.

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| title | varchar(255) | NOT NULL | Contract title/name |
| contract_number | varchar(255) | NULLABLE | Official contract/grant number |
| cfda_number | varchar(255) | NULLABLE | Catalog of Federal Domestic Assistance # |
| purpose | text | NULLABLE | Contract purpose/description |
| program_id | bigint unsigned | FK → programs.id, NULLABLE | Primary program (current system) |
| funder_id | bigint unsigned | FK → funders.id, NULLABLE | Funding organization |
| date_of_award | date | NULLABLE | Date contract was awarded |
| amount | decimal(15,2) | NULLABLE | Total contract amount |
| start_date | date | NULLABLE | Contract start date |
| end_date | date | NULLABLE | Contract end date |
| grant_code | varchar(255) | NULLABLE | Internal grant/fund code |
| notes | text | NULLABLE | Additional notes |
| archived | boolean | DEFAULT false | Is contract archived? |
| date_archived | datetime | NULLABLE | When contract was archived |
| created_by | bigint unsigned | FK → users.id | User who created contract |
| updated_by | bigint unsigned | FK → users.id | User who last updated |
| legacy_id | varchar(255) | NULLABLE | ID from legacy system |
| last_synced_at | timestamp | NULLABLE | Last sync with legacy system |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

#### Computed/Virtual Columns

**status** (computed, not stored):
- **Draft:** No start_date or end_date set
- **Upcoming:** start_date is in the future
- **Active:** Today is between start_date and end_date, not archived
- **Expired:** end_date is in the past, not archived
- **Archived:** archived flag is true

**documents** (via Spatie Media Library):
- Returns collection of attached documents
- Accessed via `$contract->documents` or `$contract->getMedia('documents')`

#### Indexes
- `program_id` - Foreign key index
- `funder_id` - Foreign key index
- `archived` - Status filtering
- `start_date, end_date` - Date range queries
- `contract_number` - Quick lookup by contract number

#### Relationships

**Direct Relationships:**
- `belongsTo(Program)` - Current system: single program via FK
- `belongsTo(Funder)` - Funding organization
- `belongsToMany(Program)` via `contract_program` - Legacy: multiple programs
- `hasMany(ContractReportDueDate)` - Reporting requirements
- `hasMany(ContractReport)` - Submitted reports
- `belongsTo(User)` as created_by
- `belongsTo(User)` as updated_by
- `morphMany(Media)` - Document attachments via Spatie Media Library

**Aggregated Relationships:**
- `getAllPrograms()` - Returns all programs (both FK and many-to-many)
- `getAllContracts()` on Program - Returns contracts via both relationship types

#### Business Rules

1. **Date Validation:**
   - end_date must be >= start_date
   - date_of_award should be <= start_date

2. **Amount Validation:**
   - Must be positive number
   - Maximum 15 digits with 2 decimal places

3. **Status Logic:**
   - Draft contracts can be edited freely
   - Active contracts should warn before major changes
   - Expired contracts should suggest archiving
   - Archived contracts are read-only (must unarchive first)

4. **Program Association:**
   - Contract can have program_id (single program) OR
   - Contract can have multiple programs via contract_program pivot OR
   - Contract can have both (for migration scenarios)

5. **Archiving:**
   - Sets archived = true and date_archived = now()
   - Hides from default contract listings
   - Preserves all data and relationships
   - Can be unarchived if needed

6. **Soft Deletes:**
   - Sets deleted_at timestamp
   - Excluded from queries by default
   - Can be restored if needed
   - Permanent deletion requires explicit action

#### Example Data
```sql
INSERT INTO contracts (
    title,
    contract_number,
    cfda_number,
    program_id,
    funder_id,
    amount,
    start_date,
    end_date,
    grant_code
) VALUES (
    'Community Services Block Grant FY2025',
    'CSBG-2025-001',
    '93.569',
    1,
    1,
    500000.00,
    '2025-01-01',
    '2025-12-31',
    'CSBG-25'
);
```

---

### contract_program (Pivot Table)

**Purpose:** Many-to-many relationship between contracts and programs. Supports legacy data where single contract funded multiple programs.

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_id | bigint unsigned | FK → contracts.id, NOT NULL | Contract reference |
| program_id | bigint unsigned | FK → programs.id, NOT NULL | Program reference |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |

#### Indexes
- `(contract_id, program_id)` - Composite lookup
- UNIQUE constraint on (contract_id, program_id) - Prevent duplicates

#### Relationships
- `belongsTo(Contract)`
- `belongsTo(Program)`

#### Business Rules

1. **Legacy Compatibility:**
   - Used for historical data where contracts funded multiple programs
   - Current system prefers single program via contract.program_id
   - Both methods supported for backwards compatibility

2. **No Duplicates:**
   - Same contract cannot be associated with same program twice

3. **Display Logic:**
   - UI shows all programs for a contract (both FK and pivot)
   - Program managers see contract if ANY of their programs are associated

#### Migration Strategy
```sql
-- Convert many-to-many to single program FK
-- Run after verifying each contract has only one program
UPDATE contracts c
SET program_id = (
    SELECT program_id
    FROM contract_program
    WHERE contract_id = c.id
    LIMIT 1
)
WHERE c.program_id IS NULL
AND EXISTS (
    SELECT 1 FROM contract_program WHERE contract_id = c.id
);
```

---

### contract_report_due_dates

**Purpose:** Tracks required report submission dates for contracts. Supports automated reminder system.

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_id | bigint unsigned | FK → contracts.id, NOT NULL | Parent contract |
| due_date | date | NOT NULL | Report due date |
| report_name | varchar(255) | NULLABLE | Name/title of report |
| status | varchar(255) | DEFAULT 'pending' | Current status |
| description | text | NULLABLE | Report description/requirements |
| report_type | varchar(255) | NULLABLE | Report category |
| reminder_sent | boolean | DEFAULT false | Has any reminder been sent? |
| reminder_sent_at | timestamp | NULLABLE | Last reminder sent timestamp |
| reminder_intervals | json | NULLABLE | Days before due date for reminders |
| active | boolean | DEFAULT true | Is this due date active? |
| notes | text | NULLABLE | Additional notes |
| legacy_id | varchar(255) | NULLABLE | Legacy system ID |
| legacy_contract_id | varchar(255) | NULLABLE | Legacy contract reference |
| last_synced_at | timestamp | NULLABLE | Last sync timestamp |
| created_by | bigint unsigned | FK → users.id | User who created |
| updated_by | bigint unsigned | FK → users.id | User who last updated |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

#### Enum Values

**status:**
- `pending` - Report not yet submitted
- `completed` - Report submitted and accepted
- `overdue` - Past due date and not submitted
- `cancelled` - Due date cancelled/not required

**report_type:**
- `quarterly` - Quarterly report
- `semi-annual` - Semi-annual report
- `annual` - Annual report
- `final` - Final report
- `progress` - Progress/interim report
- `financial` - Financial report only
- `narrative` - Narrative report only
- `custom` - Custom report type

#### Indexes
- `(contract_id, due_date)` - Contract's due dates chronologically
- `(status, active)` - Active pending/overdue reports
- `(due_date, reminder_sent)` - Upcoming reminders
- `due_date` - Date-based queries

#### Relationships
- `belongsTo(Contract)` - Parent contract
- `hasMany(ContractReportDueDateEmail)` - Sent reminder emails
- `hasOne(ContractReport)` - Submitted report (if any)
- `belongsTo(User)` as created_by
- `belongsTo(User)` as updated_by

#### Business Rules

1. **Status Transitions:**
   ```
   pending → completed (when report submitted)
   pending → overdue (when due_date passes)
   pending → cancelled (when no longer required)
   overdue → completed (late submission)
   ```

2. **Reminder Intervals:**
   - Stored as JSON array: `[30, 15, 7, 3, 1, 0]`
   - Represents days before due date
   - 0 = day-of reminder
   - Negative values = days after (for overdue)
   - Can be customized per due date

3. **Automatic Status Updates:**
   - System should run daily job to mark overdue reports
   - Query: `WHERE due_date < CURDATE() AND status = 'pending'`

4. **Reminder Tracking:**
   - `reminder_sent` = true if ANY reminder sent
   - `reminder_sent_at` = timestamp of LAST reminder
   - Detailed history in `contract_report_due_date_emails`

5. **Active Flag:**
   - Set to false to disable without deleting
   - Inactive due dates don't trigger reminders
   - Still visible in history/reports

#### Example Data
```sql
INSERT INTO contract_report_due_dates (
    contract_id,
    due_date,
    report_name,
    status,
    report_type,
    reminder_intervals,
    active
) VALUES (
    1,
    '2025-03-31',
    'Q1 2025 Progress Report',
    'pending',
    'quarterly',
    '[30, 15, 7, 3, 1, 0]',
    true
);
```

---

### contract_reports

**Purpose:** Tracks submitted reports against contract reporting requirements.

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_id | bigint unsigned | FK → contracts.id, NOT NULL | Parent contract |
| contract_report_due_date_id | bigint unsigned | FK → contract_report_due_dates.id | Associated due date |
| title | varchar(255) | NOT NULL | Report title |
| description | text | NULLABLE | Report description |
| submitted_date | date | NULLABLE | Date report was submitted |
| status | varchar(255) | DEFAULT 'draft' | Report status |
| notes | text | NULLABLE | Additional notes |
| metadata | json | NULLABLE | Additional report data |
| created_by | bigint unsigned | FK → users.id | User who created |
| updated_by | bigint unsigned | FK → users.id | User who last updated |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |
| deleted_at | timestamp | NULLABLE | Soft delete timestamp |

#### Enum Values

**status:**
- `draft` - Report being prepared
- `submitted` - Report submitted to funder
- `approved` - Report accepted by funder
- `rejected` - Report rejected, needs revision
- `revision` - Revisions in progress

#### Indexes
- `(contract_id, status)` - Contract's reports by status
- `(submitted_date, status)` - Recent submissions
- `contract_report_due_date_id` - FK index

#### Relationships
- `belongsTo(Contract)` - Parent contract
- `belongsTo(ContractReportDueDate)` - Related due date (if applicable)
- `belongsTo(User)` as created_by
- `belongsTo(User)` as updated_by
- Could integrate with Spatie Media Library for report documents

#### Business Rules

1. **Status Workflow:**
   ```
   draft → submitted → approved
                    ↓
                  rejected → revision → submitted
   ```

2. **Due Date Association:**
   - Can be linked to specific due date
   - Or can be unscheduled report
   - When linked, updates due date status to 'completed'

3. **Metadata Field:**
   - Flexible JSON storage for:
     - Performance metrics
     - Budget actuals
     - Participant counts
     - Custom fields

4. **Submission Validation:**
   - submitted_date required when status = 'submitted'
   - Should be >= contract start_date
   - Warning if submitted_date > due_date (late)

#### Example Data
```sql
INSERT INTO contract_reports (
    contract_id,
    contract_report_due_date_id,
    title,
    submitted_date,
    status,
    metadata
) VALUES (
    1,
    1,
    'Q1 2025 Progress Report - CSBG',
    '2025-03-28',
    'submitted',
    '{"participants_served": 450, "budget_spent": 125000.00}'
);
```

---

### due_date_emails

**Purpose:** Email template library for automated contract report reminders.

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Template name |
| subject | varchar(255) | NOT NULL | Email subject line |
| body | text | NOT NULL | Email body content |
| days_before | integer | NOT NULL | Days before due date to send |
| is_active | boolean | DEFAULT true | Is template active? |
| user_id | bigint unsigned | FK → users.id | Template creator |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |

#### Variable Substitution

Email templates support variable placeholders:
- `{contract_title}` - Contract title
- `{contract_number}` - Contract number
- `{report_name}` - Report name
- `{due_date}` - Report due date (formatted)
- `{days_until_due}` - Number of days until due
- `{funder_name}` - Funder organization name
- `{program_name}` - Program name(s)
- `{contract_amount}` - Contract amount (formatted)
- `{recipient_name}` - Email recipient name
- `{report_type}` - Type of report

#### Indexes
- `(days_before, is_active)` - Active templates by interval
- `is_active` - Filter active templates

#### Relationships
- `hasMany(ContractReportDueDateEmail)` - Sent emails using this template
- `belongsTo(User)` - Template creator

#### Business Rules

1. **Template Activation:**
   - System uses active templates matching reminder intervals
   - Multiple templates can have same days_before (sends multiple emails)
   - Inactive templates preserved but not used

2. **Variable Replacement:**
   - Performed at send time, not storage time
   - Invalid variables left as-is (not replaced)
   - Null values replaced with "N/A" or "(Not specified)"

3. **Days Before Logic:**
   - Positive values = days before due date
   - 0 = day of due date
   - Negative values = days after due date (overdue reminders)

4. **Template Versioning:**
   - Edits update existing template
   - Sent emails store rendered content (not affected by template changes)

#### Example Data
```sql
INSERT INTO due_date_emails (
    name,
    subject,
    body,
    days_before,
    is_active
) VALUES (
    '30-Day Reminder',
    'Contract Report Due in 30 Days - {contract_title}',
    'Dear {recipient_name},

This is a reminder that the {report_name} for {contract_title} (Contract #{contract_number}) is due on {due_date}.

You have {days_until_due} days remaining to submit this report.

Report Type: {report_type}
Funder: {funder_name}
Program: {program_name}

Please ensure all required documentation is prepared and submitted on time.

Thank you,
CAP TRACK System',
    30,
    true
);
```

---

### contract_report_due_date_emails

**Purpose:** Audit trail of sent reminder emails. Tracks what was sent, when, and to whom.

#### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint unsigned | PK, AI | Primary key |
| contract_report_due_date_id | bigint unsigned | FK → contract_report_due_dates.id | Related due date |
| due_date_email_id | bigint unsigned | FK → due_date_emails.id | Template used |
| sent_at | timestamp | NOT NULL | When email was sent |
| days_before_due | integer | NOT NULL | Days before/after due date |
| recipient_email | varchar(255) | NOT NULL | Recipient email address |
| email_subject | varchar(255) | NOT NULL | Rendered email subject |
| email_body | text | NOT NULL | Rendered email body |
| status | varchar(255) | DEFAULT 'sent' | Email delivery status |
| error_message | text | NULLABLE | Error if delivery failed |
| created_at | timestamp | NULLABLE | Record creation time |
| updated_at | timestamp | NULLABLE | Last update time |

#### Enum Values

**status:**
- `sent` - Successfully sent
- `delivered` - Confirmed delivered (if tracking available)
- `failed` - Send failed
- `bounced` - Email bounced back
- `spam` - Marked as spam by recipient
- `opened` - Recipient opened email (if tracking available)

#### Indexes
- `(contract_report_due_date_id, due_date_email_id)` - Composite unique constraint (prevents duplicate sends)
- `sent_at` - Chronological queries
- `status` - Filter by delivery status

#### Relationships
- `belongsTo(ContractReportDueDate)` - Related due date
- `belongsTo(DueDateEmail)` - Template used

#### Business Rules

1. **Duplicate Prevention:**
   - Unique constraint on (contract_report_due_date_id, due_date_email_id)
   - Prevents sending same reminder twice
   - Daily reminder job checks for existing records

2. **Content Storage:**
   - Stores rendered content (after variable substitution)
   - Preserves exact email sent (templates may change later)
   - Compliance and audit trail

3. **Recipient Determination:**
   - Contract created_by user
   - Program manager(s) for associated program(s)
   - Custom recipient list (if configured)
   - BCC to contract administrator (optional)

4. **Retry Logic:**
   - Failed sends can be retried
   - Max 3 retry attempts
   - Exponential backoff (5min, 30min, 2hr)

5. **Status Tracking:**
   - Initial status = 'sent' after successful send
   - Updated if delivery confirmation received
   - Error_message populated on failure

#### Example Data
```sql
INSERT INTO contract_report_due_date_emails (
    contract_report_due_date_id,
    due_date_email_id,
    sent_at,
    days_before_due,
    recipient_email,
    email_subject,
    email_body,
    status
) VALUES (
    1,
    1,
    '2025-03-01 08:00:00',
    30,
    'programmanager@agency.org',
    'Contract Report Due in 30 Days - CSBG FY2025',
    'Dear Program Manager,

This is a reminder that the Q1 2025 Progress Report for Community Services Block Grant FY2025 (Contract #CSBG-2025-001) is due on 2025-03-31.

You have 30 days remaining to submit this report.

Report Type: quarterly
Funder: Department of Health & Human Services
Program: Community Services

Please ensure all required documentation is prepared and submitted on time.

Thank you,
CAP TRACK System',
    'sent'
);
```

---

## Entity Relationships

### ER Diagram (Text Representation)

```
┌─────────────────┐
│     funders     │
│  (Funding Orgs) │
└────────┬────────┘
         │ 1
         │
         │ N
┌────────┴────────────────────────────┐
│           contracts                 │
│  (Grants & Funding Agreements)      │
└─────────┬───────────────────┬───────┘
          │ N                 │ 1
          │                   │
          │ N                 │ N
┌─────────┴─────────┐  ┌─────┴──────────────┐
│contract_report_   │  │  contract_program  │
│  due_dates        │  │     (pivot)        │
│(Report Deadlines) │  └─────┬──────────────┘
└──────┬────────────┘        │ N
       │ 1                   │
       │                     │ 1
       │ N            ┌──────┴────────┐
┌──────┴─────────┐    │   programs    │
│contract_report_│    │ (Agency Progs)│
│ due_date_emails│    └───────────────┘
│(Sent Reminders)│
└──────┬─────────┘
       │ N
       │
       │ 1
┌──────┴─────────┐
│ due_date_emails│
│   (Templates)  │
└────────────────┘

       │ 1
       │
       │ N
┌──────┴─────────┐
│contract_reports│
│  (Submissions) │
└────────────────┘
```

### Relationship Details

#### Funder → Contract (One-to-Many)
- **Cardinality:** One funder can have many contracts
- **Foreign Key:** contracts.funder_id → funders.id
- **On Delete:** SET NULL (preserve contract if funder deleted)
- **Business Rule:** Cannot delete funder with active contracts

#### Contract → Program (One-to-One, Current System)
- **Cardinality:** One contract belongs to one program
- **Foreign Key:** contracts.program_id → programs.id
- **On Delete:** SET NULL
- **Business Rule:** Preferred method for new contracts

#### Contract ↔ Program (Many-to-Many, Legacy)
- **Cardinality:** Contract can have multiple programs, program can have multiple contracts
- **Pivot Table:** contract_program
- **Foreign Keys:**
  - contract_program.contract_id → contracts.id
  - contract_program.program_id → programs.id
- **On Delete:** CASCADE (remove pivot records)
- **Business Rule:** Legacy compatibility, both relationships can exist

#### Contract → ContractReportDueDate (One-to-Many)
- **Cardinality:** One contract can have many due dates
- **Foreign Key:** contract_report_due_dates.contract_id → contracts.id
- **On Delete:** CASCADE (remove due dates with contract)
- **Business Rule:** Due dates inherit program access from contract

#### ContractReportDueDate → ContractReportDueDateEmail (One-to-Many)
- **Cardinality:** One due date can have many sent emails
- **Foreign Key:** contract_report_due_date_emails.contract_report_due_date_id → contract_report_due_dates.id
- **On Delete:** CASCADE
- **Business Rule:** Complete audit trail of reminders

#### DueDateEmail → ContractReportDueDateEmail (One-to-Many)
- **Cardinality:** One template can be used for many sent emails
- **Foreign Key:** contract_report_due_date_emails.due_date_email_id → due_date_emails.id
- **On Delete:** RESTRICT (cannot delete template with sent emails)
- **Business Rule:** Templates preserved for audit trail

#### Contract → ContractReport (One-to-Many)
- **Cardinality:** One contract can have many submitted reports
- **Foreign Key:** contract_reports.contract_id → contracts.id
- **On Delete:** CASCADE
- **Business Rule:** Reports are specific to contract

#### ContractReportDueDate → ContractReport (One-to-One)
- **Cardinality:** One due date relates to one submitted report
- **Foreign Key:** contract_reports.contract_report_due_date_id → contract_report_due_dates.id
- **On Delete:** SET NULL (preserve report if due date deleted)
- **Business Rule:** Optional relationship, reports can exist without due date

#### Contract → Media (Polymorphic One-to-Many)
- **Cardinality:** One contract can have many documents
- **Polymorphic Keys:** media.model_type = 'App\Models\Contract', media.model_id = contracts.id
- **On Delete:** CASCADE
- **Business Rule:** Documents managed via Spatie Media Library

---

## Contract Lifecycle

### Status Flow

```
┌─────────┐
│  DRAFT  │  No dates set, initial creation
└────┬────┘
     │ Set start_date and end_date
     ↓
┌─────────┐
│UPCOMING │  start_date in future, not yet active
└────┬────┘
     │ Reaches start_date
     ↓
┌─────────┐
│ ACTIVE  │  Between start_date and end_date
└────┬────┘
     │ Reaches end_date OR Manual Archive
     ↓
┌─────────┐    Manual Unarchive
│EXPIRED  │ ←──────────────┐
└────┬────┘                │
     │ Manual Archive      │
     ↓                     │
┌─────────┐                │
│ARCHIVED │ ───────────────┘
└─────────┘
```

### Lifecycle Stages

#### 1. Draft Stage
**Status:** Draft
**Characteristics:**
- Incomplete information
- No start/end dates
- Not visible in active contract lists
- Full edit access

**Typical Actions:**
- Enter contract details
- Upload contract documents
- Associate with program(s)
- Set reporting due dates

**Database State:**
```sql
WHERE (start_date IS NULL OR end_date IS NULL)
  AND archived = false
```

#### 2. Upcoming Stage
**Status:** Upcoming
**Characteristics:**
- Complete information entered
- Start date in future
- Visible in contract listings with "Upcoming" badge
- Limited edits (dates, amounts typically locked)

**Typical Actions:**
- Review contract details
- Prepare reporting templates
- Notify program staff

**Database State:**
```sql
WHERE start_date > CURDATE()
  AND archived = false
```

#### 3. Active Stage
**Status:** Active
**Characteristics:**
- Contract currently in effect
- Between start and end dates
- Report reminders active
- Primary focus for users

**Typical Actions:**
- Track report submissions
- Monitor compliance
- Record expenditures (external system)
- Submit required reports

**Database State:**
```sql
WHERE start_date <= CURDATE()
  AND end_date >= CURDATE()
  AND archived = false
```

#### 4. Expired Stage
**Status:** Expired
**Characteristics:**
- End date has passed
- May have outstanding reports
- Candidate for archiving
- Read-mostly access

**Typical Actions:**
- Submit final reports
- Close out financials
- Archive when complete

**Database State:**
```sql
WHERE end_date < CURDATE()
  AND archived = false
```

#### 5. Archived Stage
**Status:** Archived
**Characteristics:**
- Contract complete and closed
- Historical reference only
- Hidden from default views
- Read-only (unless unarchived)

**Typical Actions:**
- Reference historical data
- Run reports
- Unarchive if needed

**Database State:**
```sql
WHERE archived = true
```

### Automatic Status Updates

**Daily Cron Job:**
```php
// Pseudo-code for daily contract status update
Schedule::daily()->call(function () {
    // Mark overdue report due dates
    ContractReportDueDate::where('due_date', '<', now())
        ->where('status', 'pending')
        ->update(['status' => 'overdue']);

    // Optional: Auto-archive expired contracts after grace period
    Contract::where('end_date', '<', now()->subDays(90))
        ->where('archived', false)
        ->where('has_pending_reports', false)
        ->update([
            'archived' => true,
            'date_archived' => now()
        ]);
});
```

---

## Report Reminder System

### System Architecture

The automated reminder system consists of:
1. **Email Templates** (due_date_emails table)
2. **Reminder Intervals** (stored per due date)
3. **Daily Scheduler** (checks for upcoming due dates)
4. **Email Queue** (sends notifications)
5. **Audit Log** (tracks sent emails)

### Reminder Flow

```
Daily at 8:00 AM PST:
┌──────────────────────────────────────┐
│ 1. Query Active Due Dates            │
│    WHERE active = true                │
│    AND status IN ('pending','overdue')│
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 2. For Each Due Date:                │
│    - Get reminder_intervals           │
│    - Calculate days_until_due         │
│    - Check if reminder needed today   │
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 3. Check if Already Sent:            │
│    - Query contract_report_due_date_  │
│      emails for this combo            │
│    - Skip if found                    │
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 4. Get Email Template:               │
│    - Match days_before from template │
│    - Use active templates             │
│    - Can have multiple templates      │
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 5. Determine Recipients:             │
│    - Contract creator                 │
│    - Program manager(s)               │
│    - Custom recipients (if set)       │
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 6. Render Email:                     │
│    - Replace template variables       │
│    - Generate subject and body        │
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 7. Send Email:                       │
│    - Queue email job                  │
│    - Log in audit table               │
│    - Update reminder_sent flags       │
└───────────────┬──────────────────────┘
                │
┌───────────────┴──────────────────────┐
│ 8. Handle Failures:                  │
│    - Log error_message                │
│    - Status = 'failed'                │
│    - Retry logic (max 3 attempts)     │
└──────────────────────────────────────┘
```

### Reminder Interval Configuration

**Global Default:** `[30, 15, 7, 3, 1, 0]`

Intervals can be customized per due date:
```json
{
  "reminder_intervals": [30, 15, 7, 3, 1, 0]
}
```

**Interpretation:**
- `30` = Send reminder 30 days before due date
- `15` = Send reminder 15 days before due date
- `7` = Send reminder 7 days before due date
- `3` = Send reminder 3 days before due date
- `1` = Send reminder 1 day before due date
- `0` = Send reminder on due date
- `-1` = Send reminder 1 day overdue (optional)
- `-7` = Send reminder 7 days overdue (optional)

### Test Mode

**Purpose:** Prevent accidental mass emails during testing

**Features:**
- `--dry-run`: Show what would be sent without sending
- `--days=N`: Only send N-day reminders
- `--contract-id=X`: Test specific contract only
- `--test-mode --test-email=X`: Send all reminders to test email

**Example Commands:**
```bash
# Preview what would be sent
php artisan contract:send-reminders --dry-run

# Test 7-day reminders only
php artisan contract:send-reminders --days=7

# Test specific contract
php artisan contract:send-reminders --contract-id=123

# Send all reminders to test email
php artisan contract:send-reminders --test-mode --test-email=test@example.com
```

### Notification Test Dashboard

**URL:** `/admin/contract-notification-test`

**Features:**
- View all active due dates
- See when next reminder would send
- Preview email content
- Send test email immediately
- Configure reminder intervals

---

## Document Management

### Spatie Media Library Integration

Contracts use [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary) for document management.

#### Media Collections

**Collection Name:** `documents`

**Accepted MIME Types:**
- application/pdf
- application/msword
- application/vnd.openxmlformats-officedocument.wordprocessingml.document
- application/vnd.ms-excel
- application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
- application/vnd.oasis.opendocument.text
- application/vnd.oasis.opendocument.spreadsheet
- text/plain
- image/jpeg
- image/png
- image/gif

**Storage Disk:** `public`

**Media Conversions:**
- `thumb`: 150x150 crop
- `preview`: 300x300 crop

#### Usage Examples

**Upload Document:**
```php
$contract = Contract::find(1);
$contract->addMedia($request->file('document'))
    ->usingName('CSBG FY2025 Award Letter')
    ->toMediaCollection('documents');
```

**Get All Documents:**
```php
$documents = $contract->getMedia('documents');
// or via attribute
$documents = $contract->documents;
```

**Get Document URL:**
```php
$url = $contract->getFirstMediaUrl('documents');
$thumbUrl = $contract->getFirstMediaUrl('documents', 'thumb');
```

**Delete Document:**
```php
$media = $contract->getMedia('documents')->first();
$media->delete();
```

#### Document Types

Common contract documents:
- **Award Letter** - Official funding notification
- **Budget** - Approved budget breakdown
- **Scope of Work** - Project deliverables
- **Amendment** - Contract modifications
- **Closeout** - Final report and documentation

**Metadata Storage:**
```php
$contract->addMedia($file)
    ->withCustomProperties([
        'document_type' => 'award_letter',
        'fiscal_year' => '2025',
        'version' => '1.0'
    ])
    ->toMediaCollection('documents');
```

---

## Program Association Architecture

### Current System (Single Program FK)

**Model:** `contracts.program_id` → `programs.id`

**Advantages:**
- Simple relationship
- Clear ownership
- Efficient queries
- Standard Laravel conventions

**Usage:**
```php
// Get contract's program
$program = $contract->program;

// Get program's contracts
$contracts = $program->contracts;

// Query contracts by program
$contracts = Contract::where('program_id', $programId)->get();
```

### Legacy System (Many-to-Many)

**Model:** `contracts` ↔ `contract_program` ↔ `programs`

**Advantages:**
- Supports multi-program contracts
- Historical data preservation
- Flexible reporting

**Usage:**
```php
// Get contract's programs (many-to-many)
$programs = $contract->programs;

// Sync programs
$contract->programs()->sync([1, 2, 3]);

// Get program's contracts (via pivot)
$contracts = $program->contractsViaMany;
```

### Hybrid Approach

**Best Practice:** Use both for complete picture

```php
// Model method to get ALL associated programs
public function getAllPrograms()
{
    $programs = collect();

    // Add single program if exists (current system)
    if ($this->program_id && $this->program) {
        $programs->push($this->program);
    }

    // Add many-to-many programs (legacy compatibility)
    $programs = $programs->merge($this->programs);

    return $programs->unique('id');
}

// Usage
$allPrograms = $contract->getAllPrograms();
$programNames = $allPrograms->pluck('name')->join(', ');
```

### Display Logic

**Single Program:**
```
Contract: CSBG FY2025
Program: Community Services
```

**Multiple Programs:**
```
Contract: Multi-Program Grant
Programs: Community Services, Housing Assistance, Energy Programs
```

**UI Helper:**
```php
public function getDisplayProgramNames(): string
{
    $allPrograms = $this->getAllPrograms();

    if ($allPrograms->isEmpty()) {
        return 'N/A';
    }

    if ($allPrograms->count() === 1) {
        return $allPrograms->first()->name;
    }

    return $allPrograms->pluck('name')->join(', ');
}

// Compact version with count
public function getDisplayProgramNamesWithCount(): string
{
    $allPrograms = $this->getAllPrograms();

    if ($allPrograms->isEmpty()) {
        return 'N/A';
    }

    if ($allPrograms->count() === 1) {
        return $allPrograms->first()->name;
    }

    $count = $allPrograms->count();
    $firstProgram = $allPrograms->first()->name;
    return "{$firstProgram} (+{$count} programs)";
}
```

### Migration from Many-to-Many to Single FK

If transitioning fully to single program model:

```sql
-- Step 1: Identify contracts with multiple programs
SELECT c.id, c.title, COUNT(cp.program_id) as program_count
FROM contracts c
INNER JOIN contract_program cp ON c.contract_id = cp.contract_id
GROUP BY c.id
HAVING program_count > 1;

-- Step 2: For contracts with single program, migrate to FK
UPDATE contracts c
SET program_id = (
    SELECT program_id
    FROM contract_program
    WHERE contract_id = c.id
    LIMIT 1
)
WHERE c.program_id IS NULL
AND (
    SELECT COUNT(*)
    FROM contract_program
    WHERE contract_id = c.id
) = 1;

-- Step 3: Handle multi-program contracts
-- Option A: Keep in pivot table
-- Option B: Create duplicate contracts (one per program)
-- Option C: Choose primary program for FK, keep others in pivot
```

---

## Data Flow Diagrams

### Contract Creation Flow

```
User Interface
      ↓
┌─────────────────────────────┐
│  1. Create Contract Form    │
│     - Title, Number         │
│     - Funder Selection      │
│     - Program Association   │
│     - Amount, Dates         │
└──────────┬──────────────────┘
           ↓
┌──────────────────────────────┐
│  2. Validate Input           │
│     - Required fields        │
│     - Date logic             │
│     - Amount format          │
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  3. Insert into contracts    │
│     - status = Draft         │
│     - created_by = user.id   │
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  4. Associate with Program   │
│     - Set program_id FK, OR  │
│     - Insert contract_program│
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  5. Create Report Due Dates  │
│     - Insert multiple rows   │
│     - Set reminder_intervals │
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  6. Upload Documents         │
│     - Media library          │
│     - collection='documents' │
└──────────┬───────────────────┘
           ↓
      Success
      Redirect to Contract View
```

### Report Reminder Process

```
Daily Cron (8:00 AM PST)
      ↓
┌─────────────────────────────┐
│  Query Active Due Dates     │
│  - WHERE active = true      │
│  - AND status != 'completed'│
│  - AND contract not archived│
└──────────┬──────────────────┘
           ↓
      For Each Due Date
           ↓
┌─────────────────────────────┐
│  Calculate Days Until Due   │
│  days = due_date - today    │
└──────────┬──────────────────┘
           ↓
┌─────────────────────────────┐
│  Check Reminder Intervals   │
│  Is 'days' in intervals?    │
└──────────┬──────────────────┘
       Yes │         No → Skip
           ↓
┌─────────────────────────────┐
│  Check Already Sent         │
│  Query audit table          │
└──────────┬──────────────────┘
    Found │         Not Found
      ↓   │             ↓
    Skip  │   ┌──────────────────────┐
          └─→ │  Get Email Template  │
              │  Match days_before   │
              └─────────┬────────────┘
                        ↓
              ┌──────────────────────┐
              │  Render Email        │
              │  Replace variables   │
              └─────────┬────────────┘
                        ↓
              ┌──────────────────────┐
              │  Queue Email Job     │
              └─────────┬────────────┘
                        ↓
              ┌──────────────────────┐
              │  Log in Audit Table  │
              │  - Rendered content  │
              │  - Recipient         │
              │  - Status = 'sent'   │
              └─────────┬────────────┘
                        ↓
              ┌──────────────────────┐
              │  Update Due Date     │
              │  - reminder_sent=true│
              │  - reminder_sent_at  │
              └──────────────────────┘
```

### Report Submission Flow

```
User Interface
      ↓
┌─────────────────────────────┐
│  1. Create Report Form      │
│     - Link to due date      │
│     - Title, description    │
│     - Upload documents      │
└──────────┬──────────────────┘
           ↓
┌──────────────────────────────┐
│  2. Validate Report          │
│     - Required fields        │
│     - Submission date        │
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  3. Insert into              │
│     contract_reports         │
│     - status = 'draft'       │
│     - link to due_date_id    │
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  4. Mark Due Date Complete   │
│     UPDATE                   │
│     contract_report_due_dates│
│     SET status = 'completed' │
│     WHERE id = due_date_id   │
└──────────┬───────────────────┘
           ↓
┌──────────────────────────────┐
│  5. Stop Reminders           │
│     - No more emails sent    │
│     - Due date now complete  │
└──────────────────────────────┘
```

---

## Business Logic & Rules

### Contract Business Rules

#### Rule 1: Date Validation
```php
// End date must be after start date
if ($end_date <= $start_date) {
    throw new ValidationException('End date must be after start date');
}

// Award date should be before or equal to start date
if ($date_of_award > $start_date) {
    // Warning, not error
    session()->flash('warning', 'Award date is after start date');
}

// Warn if end date is in the past for new contracts
if ($end_date < now() && $contract->isNew()) {
    session()->flash('warning', 'End date is in the past');
}
```

#### Rule 2: Amount Validation
```php
// Amount must be positive
if ($amount <= 0) {
    throw new ValidationException('Amount must be greater than zero');
}

// Warning for unusually large amounts
if ($amount > 10000000) { // 10 million
    session()->flash('warning', 'Amount exceeds $10M - please verify');
}
```

#### Rule 3: Archive Protection
```php
// Cannot edit archived contracts
if ($contract->archived && !$request->unarchiving) {
    throw new AuthorizationException('Cannot edit archived contract');
}

// Cannot archive with pending reports
if ($request->archive && $contract->hasPendingReports()) {
    throw new ValidationException('Cannot archive with pending reports');
}
```

#### Rule 4: Deletion Protection
```php
// Soft delete only for contracts with activity
if ($contract->hasReports() || $contract->hasSentReminders()) {
    $contract->delete(); // Soft delete
} else {
    $contract->forceDelete(); // Hard delete if no activity
}
```

### Report Due Date Business Rules

#### Rule 1: Overdue Status
```php
// Automatic status update (daily cron)
ContractReportDueDate::where('due_date', '<', now())
    ->where('status', 'pending')
    ->update(['status' => 'overdue']);
```

#### Rule 2: Reminder Sending Rules
```php
// Only send reminders for active, non-completed due dates
$dueDate->active == true &&
$dueDate->status != 'completed' &&
$dueDate->contract->archived == false &&
!$dueDate->alreadySentReminder($daysUntilDue)
```

#### Rule 3: Completion Rules
```php
// Due date marked complete when report submitted
if ($report->status == 'submitted' && $report->contract_report_due_date_id) {
    ContractReportDueDate::find($report->contract_report_due_date_id)
        ->update(['status' => 'completed']);
}
```

### Email Template Business Rules

#### Rule 1: Variable Substitution
```php
$variables = [
    '{contract_title}' => $contract->title,
    '{contract_number}' => $contract->contract_number ?? 'N/A',
    '{report_name}' => $dueDate->report_name ?? 'Report',
    '{due_date}' => $dueDate->due_date->format('F j, Y'),
    '{days_until_due}' => abs($daysUntilDue),
    '{funder_name}' => $contract->funder->name ?? 'N/A',
    '{program_name}' => $contract->getDisplayProgramNames(),
    '{contract_amount}' => '$' . number_format($contract->amount, 2),
];

$subject = str_replace(array_keys($variables), array_values($variables), $template->subject);
$body = str_replace(array_keys($variables), array_values($variables), $template->body);
```

#### Rule 2: Recipient Determination
```php
$recipients = collect();

// Always include contract creator
if ($contract->created_by) {
    $recipients->push($contract->createdBy->email);
}

// Include program managers for all associated programs
foreach ($contract->getAllPrograms() as $program) {
    if ($program->programManager) {
        $recipients->push($program->programManager->email);
    }
}

// Include custom recipients if configured
if ($dueDate->custom_recipients) {
    $recipients = $recipients->merge($dueDate->custom_recipients);
}

// Remove duplicates
$recipients = $recipients->unique()->values();
```

---

## Query Examples

### Common Contract Queries

#### Get All Active Contracts
```sql
SELECT c.*, f.name as funder_name, p.name as program_name
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
LEFT JOIN programs p ON c.program_id = p.id
WHERE c.start_date <= CURDATE()
  AND c.end_date >= CURDATE()
  AND c.archived = false
  AND c.deleted_at IS NULL
ORDER BY c.end_date ASC;
```

#### Get Contracts Expiring Soon
```sql
SELECT c.*,
       DATEDIFF(c.end_date, CURDATE()) as days_remaining,
       f.name as funder_name
FROM contracts c
LEFT JOIN funders f ON c.funder_id = f.id
WHERE c.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
  AND c.archived = false
  AND c.deleted_at IS NULL
ORDER BY c.end_date ASC;
```

#### Get Contracts by Funder
```sql
SELECT c.*,
       SUM(c.amount) as total_funding,
       COUNT(*) as contract_count
FROM contracts c
WHERE c.funder_id = 1
  AND c.deleted_at IS NULL
GROUP BY YEAR(c.start_date)
ORDER BY YEAR(c.start_date) DESC;
```

### Report Due Date Queries

#### Get Upcoming Due Dates
```sql
SELECT crd.*,
       c.title as contract_title,
       c.contract_number,
       DATEDIFF(crd.due_date, CURDATE()) as days_until_due
FROM contract_report_due_dates crd
INNER JOIN contracts c ON crd.contract_id = c.id
WHERE crd.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  AND crd.status = 'pending'
  AND crd.active = true
  AND c.archived = false
ORDER BY crd.due_date ASC;
```

#### Get Overdue Reports
```sql
SELECT crd.*,
       c.title as contract_title,
       f.name as funder_name,
       DATEDIFF(CURDATE(), crd.due_date) as days_overdue
FROM contract_report_due_dates crd
INNER JOIN contracts c ON crd.contract_id = c.id
LEFT JOIN funders f ON c.funder_id = f.id
WHERE crd.due_date < CURDATE()
  AND crd.status IN ('pending', 'overdue')
  AND crd.active = true
  AND c.archived = false
ORDER BY crd.due_date ASC;
```

#### Get Reminders Due Today
```sql
SELECT crd.*,
       c.title as contract_title,
       DATEDIFF(crd.due_date, CURDATE()) as days_until_due
FROM contract_report_due_dates crd
INNER JOIN contracts c ON crd.contract_id = c.id
WHERE crd.active = true
  AND crd.status != 'completed'
  AND c.archived = false
  AND JSON_CONTAINS(
        crd.reminder_intervals,
        CAST(DATEDIFF(crd.due_date, CURDATE()) AS CHAR)
      )
  AND NOT EXISTS (
        SELECT 1
        FROM contract_report_due_date_emails crdde
        WHERE crdde.contract_report_due_date_id = crd.id
          AND crdde.days_before_due = DATEDIFF(crd.due_date, CURDATE())
      );
```

### Reporting Queries

#### Contract Summary by Status
```sql
SELECT
    CASE
        WHEN c.archived = true THEN 'Archived'
        WHEN c.start_date > CURDATE() THEN 'Upcoming'
        WHEN c.start_date <= CURDATE() AND c.end_date >= CURDATE() THEN 'Active'
        WHEN c.end_date < CURDATE() THEN 'Expired'
        ELSE 'Draft'
    END as status,
    COUNT(*) as count,
    SUM(c.amount) as total_amount
FROM contracts c
WHERE c.deleted_at IS NULL
GROUP BY status
ORDER BY
    CASE status
        WHEN 'Active' THEN 1
        WHEN 'Upcoming' THEN 2
        WHEN 'Expired' THEN 3
        WHEN 'Draft' THEN 4
        WHEN 'Archived' THEN 5
    END;
```

#### Compliance Report (Due Dates vs Submissions)
```sql
SELECT
    c.title as contract,
    COUNT(DISTINCT crd.id) as total_due_dates,
    COUNT(DISTINCT cr.id) as submitted_reports,
    COUNT(DISTINCT CASE WHEN crd.status = 'overdue' THEN crd.id END) as overdue,
    COUNT(DISTINCT CASE WHEN crd.status = 'completed' THEN crd.id END) as completed,
    ROUND(
        COUNT(DISTINCT CASE WHEN crd.status = 'completed' THEN crd.id END) * 100.0 /
        NULLIF(COUNT(DISTINCT crd.id), 0),
        2
    ) as completion_rate
FROM contracts c
LEFT JOIN contract_report_due_dates crd ON c.id = crd.contract_id AND crd.active = true
LEFT JOIN contract_reports cr ON c.id = cr.contract_id
WHERE c.archived = false
  AND c.deleted_at IS NULL
GROUP BY c.id, c.title
ORDER BY completion_rate ASC;
```

### Program-Based Access Queries

#### Get Contracts for User's Programs
```php
// Eloquent query with program access control
$contracts = Contract::query()
    ->when(!$user->isAdmin(), function ($query) use ($user) {
        $programIds = $user->programs->pluck('id');

        $query->where(function ($q) use ($programIds) {
            // Include contracts with program_id in user's programs
            $q->whereIn('program_id', $programIds)
              // OR contracts with many-to-many programs in user's programs
              ->orWhereHas('programs', function ($sq) use ($programIds) {
                  $sq->whereIn('programs.id', $programIds);
              });
        });
    })
    ->where('archived', false)
    ->with(['program', 'funder', 'programs'])
    ->orderBy('end_date', 'desc')
    ->get();
```

---

## Security & Permissions

### Role-Based Access Control

#### Permission Structure

**Permission Naming:** `contracts.{action}`

| Permission | Description |
|------------|-------------|
| contracts.view | View contract list and details |
| contracts.create | Create new contracts |
| contracts.edit | Edit existing contracts |
| contracts.delete | Delete contracts |
| contracts.archive | Archive/unarchive contracts |
| contracts.manage_reminders | Configure reminder settings |
| contracts.view_all_programs | View contracts across all programs |

#### Role Definitions

**Administrator:**
- All permissions
- Access to all contracts regardless of program
- Can configure global reminder settings
- Can manage funders

**Contract Manager:**
- All contract permissions
- Access to all contracts regardless of program
- Can configure reminder templates
- Can manage funders
- Cannot delete or modify user permissions

**Program Manager:**
- contracts.view, contracts.create, contracts.edit
- Access ONLY to contracts for their assigned programs
- Cannot archive or delete
- Cannot configure reminders (uses global settings)

**Program Staff:**
- contracts.view
- Access ONLY to contracts for their assigned programs
- Read-only access

**Contract Dashboard:**
- contracts.view (read-only)
- View-only dashboard access
- No create/edit capabilities

### Data Access Rules

#### Rule 1: Program-Based Filtering
```php
// In Contract model
public function scopeForUserPrograms($query, $user)
{
    // Admins and Contract Managers see all contracts
    if ($user->isAdmin() || $user->hasRole('Contract Manager')) {
        return $query;
    }

    $userProgramIds = $user->programs->pluck('id')->toArray();

    if (empty($userProgramIds)) {
        return $query->whereRaw('1 = 0'); // No results
    }

    // Filter by program_id OR many-to-many programs
    return $query->where(function ($q) use ($userProgramIds) {
        $q->whereIn('program_id', $userProgramIds)
          ->orWhereHas('programs', function ($sq) use ($userProgramIds) {
              $sq->whereIn('programs.id', $userProgramIds);
          });
    });
}

// Usage in controller
$contracts = Contract::forUserPrograms(auth()->user())->get();
```

#### Rule 2: Audit Trail
```php
// Automatically track who creates/updates contracts
protected static function boot()
{
    parent::boot();

    static::creating(function ($contract) {
        $contract->created_by = auth()->id();
    });

    static::updating(function ($contract) {
        $contract->updated_by = auth()->id();
    });
}
```

#### Rule 3: Sensitive Data Protection
```php
// Cannot view contract details without permission
if (!$user->can('contracts.view')) {
    abort(403, 'Unauthorized');
}

// Cannot view contract if not in user's programs
if (!$user->canAccessContract($contract)) {
    abort(403, 'You do not have access to this contract');
}
```

### Middleware Examples

**Route Protection:**
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/contracts', [ContractController::class, 'index'])
        ->middleware('permission:contracts.view');

    Route::get('/contracts/create', [ContractController::class, 'create'])
        ->middleware('permission:contracts.create');

    Route::post('/contracts', [ContractController::class, 'store'])
        ->middleware('permission:contracts.create');

    Route::get('/contracts/{contract}', [ContractController::class, 'show'])
        ->middleware('permission:contracts.view');

    Route::get('/contracts/{contract}/edit', [ContractController::class, 'edit'])
        ->middleware('permission:contracts.edit');

    Route::put('/contracts/{contract}', [ContractController::class, 'update'])
        ->middleware('permission:contracts.edit');

    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])
        ->middleware('permission:contracts.delete');
});
```

---

## Legacy System Migration

### Legacy ID Tracking

The contract system supports migration from legacy systems through dedicated fields:

**contracts table:**
- `legacy_id` - Original contract ID from legacy system
- `last_synced_at` - Last synchronization timestamp

**contract_report_due_dates table:**
- `legacy_id` - Original due date ID
- `legacy_contract_id` - Reference to legacy contract (if different)
- `last_synced_at` - Last synchronization timestamp

### Migration Strategy

#### Phase 1: Data Import
```sql
-- Import funders
INSERT INTO funders (name, code, legacy_id, created_at)
SELECT funder_name, funder_code, id, created_date
FROM legacy_funders;

-- Import contracts
INSERT INTO contracts (
    title,
    contract_number,
    funder_id,
    amount,
    start_date,
    end_date,
    legacy_id,
    created_at
)
SELECT
    c.title,
    c.contract_num,
    f.id as funder_id,
    c.amount,
    c.start_date,
    c.end_date,
    c.id as legacy_id,
    c.created_date
FROM legacy_contracts c
LEFT JOIN funders f ON f.legacy_id = c.funder_id;
```

#### Phase 2: Program Association
```sql
-- Import many-to-many program associations
INSERT INTO contract_program (contract_id, program_id)
SELECT
    c.id as contract_id,
    p.id as program_id
FROM legacy_contract_programs lcp
INNER JOIN contracts c ON c.legacy_id = lcp.contract_id
INNER JOIN programs p ON p.legacy_id = lcp.program_id;
```

#### Phase 3: Report Due Dates
```sql
-- Import report due dates
INSERT INTO contract_report_due_dates (
    contract_id,
    due_date,
    report_name,
    report_type,
    status,
    legacy_id,
    legacy_contract_id
)
SELECT
    c.id as contract_id,
    lrd.due_date,
    lrd.report_name,
    lrd.report_type,
    CASE
        WHEN lrd.submitted_date IS NOT NULL THEN 'completed'
        WHEN lrd.due_date < CURDATE() THEN 'overdue'
        ELSE 'pending'
    END as status,
    lrd.id as legacy_id,
    lrd.contract_id as legacy_contract_id
FROM legacy_report_due_dates lrd
INNER JOIN contracts c ON c.legacy_id = lrd.contract_id;
```

#### Phase 4: Validation
```sql
-- Verify record counts match
SELECT
    'contracts' as table_name,
    (SELECT COUNT(*) FROM legacy_contracts) as legacy_count,
    (SELECT COUNT(*) FROM contracts WHERE legacy_id IS NOT NULL) as imported_count;

-- Check for orphaned records
SELECT c.*
FROM contracts c
WHERE c.program_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM contract_program WHERE contract_id = c.id
  );
```

### Ongoing Sync

**For bi-directional sync with legacy system:**

```php
// Sync changes from CAP TRACK to legacy system
Contract::where('last_synced_at', '<', now()->subHours(1))
    ->orWhereNull('last_synced_at')
    ->chunk(100, function ($contracts) {
        foreach ($contracts as $contract) {
            // Send to legacy API
            LegacySystem::updateContract([
                'id' => $contract->legacy_id,
                'title' => $contract->title,
                'amount' => $contract->amount,
                // ... other fields
            ]);

            $contract->update(['last_synced_at' => now()]);
        }
    });
```

---

## Performance Optimization

### Index Strategy

**Existing Indexes:**
```sql
-- contracts table
INDEX idx_program_id (program_id)
INDEX idx_funder_id (funder_id)
INDEX idx_archived (archived)
INDEX idx_dates (start_date, end_date)
INDEX idx_contract_number (contract_number)

-- contract_report_due_dates table
INDEX idx_contract_due (contract_id, due_date)
INDEX idx_status_active (status, active)
INDEX idx_due_reminder (due_date, reminder_sent)

-- contract_report_due_date_emails table
INDEX idx_due_date_email (contract_report_due_date_id, due_date_email_id)
INDEX idx_sent_at (sent_at)
```

**Recommended Additional Indexes:**
```sql
-- For status-based queries
CREATE INDEX idx_status_dates ON contracts(archived, start_date, end_date);

-- For program-filtered queries
CREATE INDEX idx_program_archived ON contracts(program_id, archived);

-- For reminder processing
CREATE INDEX idx_reminder_processing ON contract_report_due_dates(active, status, due_date);
```

### Query Optimization

**Eager Loading:**
```php
// Bad - N+1 query problem
$contracts = Contract::all();
foreach ($contracts as $contract) {
    echo $contract->funder->name; // Queries for each contract
    echo $contract->program->name; // Queries for each contract
}

// Good - Eager load relationships
$contracts = Contract::with(['funder', 'program', 'programs'])->get();
foreach ($contracts as $contract) {
    echo $contract->funder->name; // No additional queries
    echo $contract->getDisplayProgramNames(); // No additional queries
}
```

**Chunk Large Datasets:**
```php
// Process large result sets in chunks
Contract::where('archived', false)
    ->chunk(100, function ($contracts) {
        foreach ($contracts as $contract) {
            // Process each contract
        }
    });
```

**Select Only Needed Columns:**
```php
// Bad - Selects all columns
$contracts = Contract::all();

// Good - Select only needed columns
$contracts = Contract::select('id', 'title', 'contract_number', 'amount')->get();
```

### Caching Strategy

**Cache Contract Counts:**
```php
// Cache for 1 hour
$activeCount = Cache::remember('contracts.active.count', 3600, function () {
    return Contract::active()->count();
});

$expiringSoonCount = Cache::remember('contracts.expiring_soon.count', 3600, function () {
    return Contract::where('end_date', '<=', now()->addDays(90))
        ->where('archived', false)
        ->count();
});
```

**Cache Dashboard Data:**
```php
$dashboardData = Cache::remember('contracts.dashboard', 3600, function () {
    return [
        'active_contracts' => Contract::active()->count(),
        'upcoming_due_dates' => ContractReportDueDate::upcoming(30)->count(),
        'overdue_reports' => ContractReportDueDate::overdue()->count(),
        'total_funding' => Contract::active()->sum('amount'),
    ];
});
```

### Database Optimization

**Partitioning (for large datasets):**
```sql
-- Partition contracts by year
ALTER TABLE contracts
PARTITION BY RANGE (YEAR(start_date)) (
    PARTITION p2020 VALUES LESS THAN (2021),
    PARTITION p2021 VALUES LESS THAN (2022),
    PARTITION p2022 VALUES LESS THAN (2023),
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

**Archive Old Data:**
```sql
-- Move very old archived contracts to archive table
CREATE TABLE contracts_archive LIKE contracts;

INSERT INTO contracts_archive
SELECT * FROM contracts
WHERE archived = true
  AND date_archived < DATE_SUB(CURDATE(), INTERVAL 5 YEAR);

DELETE FROM contracts
WHERE archived = true
  AND date_archived < DATE_SUB(CURDATE(), INTERVAL 5 YEAR);
```

---

## API Integration Points

### External System Endpoints

While this document focuses on the database design, here are integration points for external systems:

#### Contract Data Export
```json
GET /api/contracts
GET /api/contracts/{id}

Response:
{
  "id": 1,
  "title": "CSBG FY2025",
  "contract_number": "CSBG-2025-001",
  "funder": {
    "id": 1,
    "name": "Department of Health & Human Services"
  },
  "programs": [
    {
      "id": 1,
      "name": "Community Services"
    }
  ],
  "amount": 500000.00,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "status": "active"
}
```

#### Report Due Dates Export
```json
GET /api/contracts/{id}/due-dates
GET /api/due-dates/upcoming

Response:
{
  "data": [
    {
      "id": 1,
      "contract_id": 1,
      "due_date": "2025-03-31",
      "report_name": "Q1 2025 Progress Report",
      "status": "pending",
      "days_until_due": 15
    }
  ]
}
```

#### Webhook Notifications
```json
POST /webhooks/contract-events

Payload:
{
  "event": "report.overdue",
  "contract_id": 1,
  "due_date_id": 5,
  "timestamp": "2025-04-01T00:00:00Z",
  "data": {
    "contract_title": "CSBG FY2025",
    "report_name": "Q1 2025 Progress Report",
    "days_overdue": 1
  }
}
```

---

## Conclusion

The Contract Management database design provides a robust, flexible foundation for tracking funding agreements, compliance requirements, and reporting obligations. Key strengths include:

**Comprehensive Tracking:**
- Full contract lifecycle management
- Automated reminder system
- Document version control
- Audit trails

**Flexible Architecture:**
- Supports both single and multi-program contracts
- Legacy system compatibility
- Extensible through JSON metadata fields

**Compliance Focus:**
- Automated due date tracking
- Configurable reminder intervals
- Complete email audit trail
- Soft deletes for data retention

**Security & Access Control:**
- Role-based permissions
- Program-level data segregation
- Audit fields on all tables

**Performance:**
- Strategic indexes
- Caching opportunities
- Efficient query patterns
- Scalable architecture

The design balances immediate needs with future flexibility, ensuring the system can adapt as organizational requirements evolve.

---

**Document Version:** 1.0
**Created:** October 22, 2025
**Next Review:** January 2026
**Database Schema Version:** See migrations timestamp
