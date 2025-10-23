# Database Design Documentation

## Overview
This document provides a comprehensive overview of the database design for the Contracts Database Laravel application. The system manages contracts, programs, funders, users, and reporting requirements with full audit trails and media handling capabilities.

## Database Schema Overview

The application uses MySQL as the primary database with the following core entities:
- **Contracts**: Central entity managing grant/contract information
- **Users**: System users with role-based permissions
- **Programs**: Categorization for contracts
- **Funders**: Organizations that provide funding
- **Report Due Dates**: Tracking reporting requirements
- **Reports**: Actual submitted reports
- **Audit Logs**: Complete audit trail for all changes
- **Media**: File attachments and documents

## Table Definitions

### Core Tables

#### `users`
User management and authentication table.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| name | VARCHAR(255) | NULLABLE | User's full name |
| email | VARCHAR(255) | NULLABLE, UNIQUE | Email address for login |
| email_verified_at | DATETIME | NULLABLE | Email verification timestamp |
| password | VARCHAR(255) | NULLABLE | Hashed password |
| approved | BOOLEAN | DEFAULT 0, NULLABLE | User approval status |
| remember_token | VARCHAR(100) | NULLABLE | Remember me token |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY: id
- UNIQUE: email

**Features:**
- Soft deletes enabled
- Email verification support
- User approval workflow
- Auto-assignment to default role on registration

#### `contracts`
Central table for managing grants and contracts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique contract identifier |
| title | VARCHAR(255) | NOT NULL | Contract title |
| contract_number | VARCHAR(255) | NULLABLE | Official contract number |
| cfda_number | VARCHAR(255) | NULLABLE | CFDA (Catalog of Federal Domestic Assistance) number |
| purpose | LONGTEXT | NULLABLE | Contract purpose description |
| date_of_award | DATE | NULLABLE | Date contract was awarded |
| amount | DECIMAL(15,2) | NULLABLE | Contract amount |
| start_date | DATE | NULLABLE | Contract start date |
| end_date | DATE | NULLABLE | Contract end date |
| notes | LONGTEXT | NULLABLE | Additional notes |
| grant_code | VARCHAR(255) | NULLABLE | Internal grant code |
| funder_id | BIGINT UNSIGNED | NULLABLE | Foreign key to funders table |
| archived | VARCHAR(255) | NULLABLE | Archive status |
| date_archived | DATE | NULLABLE | Date contract was archived |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Foreign Keys:**
- funder_id → funders.id

**Features:**
- Soft deletes enabled
- Media attachments supported (documents)
- Full audit trail via Auditable trait
- Archiving functionality
- Global search capabilities

#### `programs`
Program categorization for contracts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique program identifier |
| program_name | VARCHAR(255) | NULLABLE | Program name |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Features:**
- Soft deletes enabled
- Many-to-many relationship with contracts
- Many-to-many relationship with users

#### `funders`
Organizations that provide funding.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique funder identifier |
| funder_name | VARCHAR(255) | UNIQUE | Funder organization name |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Indexes:**
- UNIQUE: funder_name

**Features:**
- Soft deletes enabled
- One-to-many relationship with contracts

#### `report_due_dates`
Tracking of reporting requirements and deadlines.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique due date identifier |
| due_date | DATE | NOT NULL | Report due date |
| status | VARCHAR(255) | NOT NULL | Status (incomplete/completed) |
| description | LONGTEXT | NULLABLE | Report description |
| microsoft_event_id | VARCHAR(255) | NULLABLE | Microsoft Calendar event ID |
| invite_sent | BOOLEAN | DEFAULT FALSE | Whether calendar invite was sent |
| contract_id | BIGINT UNSIGNED | NULLABLE | Foreign key to contracts table |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Foreign Keys:**
- contract_id → contracts.id

**Features:**
- Soft deletes enabled
- Media attachments supported (documents)
- Microsoft Graph Calendar integration
- Status tracking with predefined values
- Many-to-many relationship with due date emails

#### `reports`
Actual submitted reports.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique report identifier |
| contract_id | BIGINT UNSIGNED | NULLABLE | Foreign key to contracts table |
| due_date_id | BIGINT UNSIGNED | NULLABLE | Foreign key to report_due_dates table |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Foreign Keys:**
- contract_id → contracts.id
- due_date_id → report_due_dates.id

**Features:**
- Soft deletes enabled
- Media attachments supported (upload)
- Full audit trail via Auditable trait

#### `due_date_emails`
Email addresses for report due date notifications.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique email identifier |
| email | VARCHAR(255) | UNIQUE | Email address |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Indexes:**
- UNIQUE: email

**Features:**
- Soft deletes enabled
- Many-to-many relationship with report due dates

### Authorization Tables

#### `roles`
User roles for permissions management.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique role identifier |
| title | VARCHAR(255) | NULLABLE | Role title |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Features:**
- Soft deletes enabled
- Many-to-many relationship with users
- Many-to-many relationship with permissions

#### `permissions`
System permissions for granular access control.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique permission identifier |
| title | VARCHAR(255) | NULLABLE | Permission title |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Features:**
- Soft deletes enabled
- Many-to-many relationship with roles

### System Tables

#### `audit_logs`
Complete audit trail for all model changes.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique audit log identifier |
| description | TEXT | NOT NULL | Description of the change |
| subject_id | BIGINT UNSIGNED | NULLABLE | ID of the affected model |
| subject_type | VARCHAR(255) | NULLABLE | Class name of the affected model |
| user_id | BIGINT UNSIGNED | NULLABLE | ID of user who made the change |
| properties | TEXT | NULLABLE | JSON of old/new values |
| host | VARCHAR(46) | NULLABLE | IP address of the user |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |

**Features:**
- Tracks all changes to auditable models
- Stores old and new values as JSON
- Links to user who made the change
- Records IP address for security

#### `media`
File attachments and media handling (Spatie Media Library).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique media identifier |
| model_type | VARCHAR(255) | NOT NULL | Model class that owns the media |
| model_id | BIGINT UNSIGNED | NOT NULL | Model ID that owns the media |
| uuid | CHAR(36) | NULLABLE, UNIQUE | Unique UUID for the media |
| collection_name | VARCHAR(255) | NOT NULL | Media collection name |
| name | VARCHAR(255) | NOT NULL | Original filename |
| file_name | VARCHAR(255) | NOT NULL | Stored filename |
| mime_type | VARCHAR(255) | NULLABLE | MIME type |
| disk | VARCHAR(255) | NOT NULL | Storage disk |
| conversions_disk | VARCHAR(255) | NULLABLE | Disk for conversions |
| size | BIGINT UNSIGNED | NOT NULL | File size in bytes |
| manipulations | JSON | NOT NULL | Image manipulations |
| custom_properties | JSON | NOT NULL | Custom properties |
| generated_conversions | JSON | NOT NULL | Generated conversions |
| responsive_images | JSON | NOT NULL | Responsive image data |
| order_column | INT UNSIGNED | NULLABLE | Order within collection |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |

**Indexes:**
- PRIMARY KEY: id
- INDEX: model_type, model_id
- UNIQUE: uuid
- INDEX: order_column

**Features:**
- Polymorphic relationship to any model
- Support for image conversions
- Collections for organizing media
- Responsive image support

#### `password_resets`
Password reset tokens.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| email | VARCHAR(255) | INDEXED | Email address |
| token | VARCHAR(255) | NOT NULL | Reset token |
| created_at | TIMESTAMP | NULLABLE | Token creation time |

**Features:**
- Standard Laravel password reset functionality
- Tokens expire after configured time

#### `personal_access_tokens`
API tokens for Sanctum authentication.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique token identifier |
| tokenable_type | VARCHAR(255) | NOT NULL | Model type that owns token |
| tokenable_id | BIGINT UNSIGNED | NOT NULL | Model ID that owns token |
| name | VARCHAR(255) | NOT NULL | Token name |
| token | VARCHAR(64) | UNIQUE | Hashed token value |
| abilities | TEXT | NULLABLE | Token abilities (JSON) |
| last_used_at | TIMESTAMP | NULLABLE | Last usage timestamp |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |

**Features:**
- Laravel Sanctum token system
- Polymorphic relationship to any model
- Ability-based permissions
- Usage tracking

### Pivot Tables

#### `role_user`
Many-to-many relationship between roles and users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| user_id | BIGINT UNSIGNED | NOT NULL | Foreign key to users table |
| role_id | BIGINT UNSIGNED | NOT NULL | Foreign key to roles table |

**Foreign Keys:**
- user_id → users.id (CASCADE DELETE)
- role_id → roles.id (CASCADE DELETE)

#### `permission_role`
Many-to-many relationship between permissions and roles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| role_id | BIGINT UNSIGNED | NOT NULL | Foreign key to roles table |
| permission_id | BIGINT UNSIGNED | NOT NULL | Foreign key to permissions table |

**Foreign Keys:**
- role_id → roles.id (CASCADE DELETE)
- permission_id → permissions.id (CASCADE DELETE)

#### `program_user`
Many-to-many relationship between programs and users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| user_id | BIGINT UNSIGNED | NOT NULL | Foreign key to users table |
| program_id | BIGINT UNSIGNED | NOT NULL | Foreign key to programs table |

**Foreign Keys:**
- user_id → users.id (CASCADE DELETE)
- program_id → programs.id (CASCADE DELETE)

#### `contract_program`
Many-to-many relationship between contracts and programs.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| contract_id | BIGINT UNSIGNED | NOT NULL | Foreign key to contracts table |
| program_id | BIGINT UNSIGNED | NOT NULL | Foreign key to programs table |

**Foreign Keys:**
- contract_id → contracts.id (CASCADE DELETE)
- program_id → programs.id (CASCADE DELETE)

#### `due_date_email_report_due_date`
Many-to-many relationship between due date emails and report due dates.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| report_due_date_id | BIGINT UNSIGNED | NOT NULL | Foreign key to report_due_dates table |
| due_date_email_id | BIGINT UNSIGNED | NOT NULL | Foreign key to due_date_emails table |

**Foreign Keys:**
- report_due_date_id → report_due_dates.id (CASCADE DELETE)
- due_date_email_id → due_date_emails.id (CASCADE DELETE)

## Entity Relationships

### Primary Relationships

1. **Contracts → Funders**: One-to-Many
   - Each contract belongs to one funder
   - Each funder can have multiple contracts

2. **Contracts → Programs**: Many-to-Many
   - Contracts can be associated with multiple programs
   - Programs can be associated with multiple contracts

3. **Contracts → Report Due Dates**: One-to-Many
   - Each contract can have multiple report due dates
   - Each report due date belongs to one contract

4. **Contracts → Reports**: One-to-Many
   - Each contract can have multiple reports
   - Each report belongs to one contract

5. **Report Due Dates → Reports**: One-to-Many
   - Each report due date can have multiple reports
   - Each report is linked to one due date

6. **Report Due Dates → Due Date Emails**: Many-to-Many
   - Report due dates can notify multiple emails
   - Email addresses can receive notifications for multiple due dates

### Authorization Relationships

1. **Users → Roles**: Many-to-Many
   - Users can have multiple roles
   - Roles can be assigned to multiple users

2. **Roles → Permissions**: Many-to-Many
   - Roles can have multiple permissions
   - Permissions can be assigned to multiple roles

3. **Users → Programs**: Many-to-Many
   - Users can be associated with multiple programs
   - Programs can have multiple users

### Media Relationships

1. **Contracts → Media**: Polymorphic One-to-Many
   - Contracts can have multiple document attachments
   - Collection name: "documents"

2. **Report Due Dates → Media**: Polymorphic One-to-Many
   - Report due dates can have multiple document attachments
   - Collection name: "documents"

3. **Reports → Media**: Polymorphic One-to-Many
   - Reports can have multiple file uploads
   - Collection name: "upload"

## Data Integrity Features

### Soft Deletes
All major entities support soft deletes:
- users
- contracts
- programs
- funders
- report_due_dates
- reports
- due_date_emails
- roles
- permissions

This ensures data integrity and allows for recovery of accidentally deleted records.

### Foreign Key Constraints
- All foreign key relationships are enforced at the database level
- Pivot table relationships use CASCADE DELETE for data consistency
- Main entity relationships allow NULL for flexibility

### Unique Constraints
- User emails are unique
- Funder names are unique
- Due date emails are unique
- Media UUIDs are unique

### Validation
- All dates use consistent Y-m-d format
- Decimal amounts use 15,2 precision
- Email addresses are validated
- Status fields use predefined values

## Audit Trail System

The application implements comprehensive audit logging:

1. **Auditable Models**: Contract, Report, User
2. **Tracked Information**:
   - What changed (description)
   - Who made the change (user_id)
   - When it changed (created_at)
   - Where it came from (host IP)
   - Old and new values (properties JSON)

3. **Polymorphic Design**: Supports auditing any model type through subject_type/subject_id

## Microsoft Integration Features

### Calendar Integration
- `microsoft_event_id` in report_due_dates table
- `invite_sent` boolean flag to track calendar invites
- Support for iCal meeting invitations

### Email Integration
- Microsoft Graph email sending
- Custom notification routing
- Due date reminder system

## Search and Performance Features

### Searchable Fields
Each model defines searchable fields for global search:
- **Contracts**: contract_number, date_of_award, amount, start_date, end_date, documents, purpose, grant_code, notes, title, cfda_number
- **Programs**: program_name
- **Funders**: funder_name
- **Report Due Dates**: due_date, description
- **Due Date Emails**: email

### Indexing Strategy
- Primary keys on all tables
- Foreign key indexes automatically created
- Unique constraints where appropriate
- Email indexing for password resets

## Data Migration History

The database evolved through several migration phases:

1. **Initial Schema** (2023-02-26): Core tables and relationships
2. **Archive Features** (2024-02-20): Contract archiving functionality
3. **Microsoft Integration** (2024-02-04): Calendar event tracking
4. **Notification Tracking** (2024-01-13): Invite sent flags

This migration history ensures backward compatibility and provides a clear evolution path for the database schema.

## Security Considerations

1. **Password Hashing**: Bcrypt hashing for user passwords
2. **Soft Deletes**: Prevent accidental data loss
3. **Audit Trails**: Complete change tracking
4. **IP Logging**: Track user actions by IP address
5. **Token Management**: Sanctum for API authentication
6. **Email Verification**: Optional user email verification
7. **User Approval**: Admin approval workflow for new users