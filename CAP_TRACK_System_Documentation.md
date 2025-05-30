# CAP TRACK - Data Management System for Community Action Partnership Agencies

## Overview

CAP TRACK is a comprehensive web-based data management platform specifically designed for Community Action Partnership (CAP) agencies. These are community service nonprofits typically funded through Community Service Block Grant (CSBG) funding along with various other funding sources.

System is build on Laravel 11, Livewire and Volt

### Core Purpose
The system helps organizations:
- Manage participant and household data
- Track program services and work plan goals
- Monitor custom data requirements by fund
- Generate reports on clients and program outcomes
- Maintain compliance with CSBG reporting requirements

### Key Features
- Centralized data management system
- User authentication and role-based permissions
- Dashboard for program progress tracking
- Participant intake and enrollment workflows
- Administrative backend for system management
- Self-service kiosk for participant registration
- Customizable branding (logo, name, color scheme)

## System Modules

### Core Modules (Required)

#### 1. User Management & Dashboard
- Secure user login system
- Dashboard showing overall program progress
- Work plan goal tracking
- Participant service metrics
- New participant record entry

#### 2. Administrative Backend
- System setup and configuration
- User management and permissions
- Reference data management
- Household and participant management
- Data import/export functionality
- Custom CSV import mapping
- Poverty guidelines management system

### Optional Module

#### 3. Participant Self-Service
- Intake kiosk for iPads/kiosk PCs
- Self-service participant registration
- Easy-to-use interface for walk-in clients

## Data Dimensions

### Programs
Programs are funded initiatives that provide specific services to meet community needs.

**Key Features:**
- Individual service lists per program
- Annual work plan management
- Service level goal tracking
- Program manager access controls
- Participant enrollment tracking

**Work Plans:**
- Created annually for each program
- Support final report uploads
- Multiple work plan goals per plan
- Goals mapped to specific services
- Target vs. actual number tracking

### Services
Services represent what programs provide to participants or partners.

**Characteristics:**
- Program-specific service offerings
- Configurable start and end dates
- Funding contract alignment
- Work plan goal association
- Service delivery tracking

### Contracts
Funding sources with specific terms enabling program operations.

**Contract Management:**
- Detailed contract information storage
- PDF document uploads
- Program manager guidance
- Reporting submission functionality
- Email notification system for deadlines

**Contract Database Fields:**
- ID
- Title
- Contract Number
- CFDA Number
- Purpose (rich text)
- Program (dropdown)
- Funder (dropdown)
- Amount
- Start Date
- End Date
- Documents (multiple attachments)
- Notes (chronological)
- Fund Code

### Participants
Individual records for household members receiving program services.

**Core Information:**
- Demographic details (CSBG Module 4 Section C compliant)
- Address history
- Financial assessments
- Household relationships
- Service eligibility tracking
- Duplicate prevention system

**Participant Fields:**
- ID
- Household ID
- Personal Information:
  - First name, Middle name, Last name, Suffix
  - Date of birth, Calculated age
  - Gender, Telephone, Email
- Demographics:
  - Education level
  - Disability status
  - Health insurance status and sources
  - Ethnicity and race
  - Military status
  - Work status
- Financial:
  - Income sources and amounts
  - Non-cash benefits
- Documentation and notes

**Registration Workflows:**
- All-in-one new registration process
- Head of household creation
- Family member addition
- CSBG compliance data collection
- Service enrollment

### Households
Family units comprising one head of household and family members.

**Household Characteristics:**
- Household type classification
- Family size tracking
- Combined income calculation
- Federal poverty level determination
- Address management

**Household Fields:**
- ID
- Household code (7-digit alphanumeric)
- Household type
- Household size
- Housing status
- Income tracking (monthly/annual)
- HHS poverty percentage
- Address history

### Poverty Guidelines Management
Built-in system for managing federal poverty guidelines.

**Features:**
- Annual guideline updates
- Complete poverty matrix display
- Automatic poverty level calculation
- Calendar year configurations
- Monthly and annual income support

## Federal Poverty Guidelines (2025)

### Monthly Guidelines - 48 Contiguous States

| Family Size | 50% | 75% | 100% | 125% | 130% | 150% | 200% | 250% | 300% | 400% | 500% |
|-------------|-----|-----|------|------|------|------|------|------|------|------|------|
| 1 | $652 | $978 | $1,304 | $1,630 | $1,695 | $1,956 | $2,608 | $3,260 | $3,913 | $5,217 | $6,521 |
| 2 | $881 | $1,322 | $1,763 | $2,203 | $2,291 | $2,644 | $3,525 | $4,406 | $5,288 | $7,050 | $8,813 |
| 3 | $1,110 | $1,666 | $2,221 | $2,776 | $2,887 | $3,331 | $4,442 | $5,552 | $6,663 | $8,883 | $11,104 |
| 4 | $1,340 | $2,009 | $2,679 | $3,349 | $3,483 | $4,019 | $5,358 | $6,698 | $8,038 | $10,717 | $13,396 |

*Note: Complete table available in system with all family sizes and percentage levels*

### Annual Guidelines - 48 Contiguous States

| Family Size | 50% | 75% | 100% | 125% | 130% | 150% | 200% | 250% | 300% | 400% | 500% |
|-------------|-----|-----|------|------|------|------|------|------|------|------|------|
| 1 | $7,825 | $11,738 | $15,650 | $19,563 | $20,345 | $23,475 | $31,300 | $39,125 | $46,950 | $62,600 | $78,250 |
| 2 | $10,575 | $15,863 | $21,150 | $26,438 | $27,495 | $31,725 | $42,300 | $52,875 | $63,450 | $84,600 | $105,750 |
| 3 | $13,325 | $19,988 | $26,650 | $33,313 | $34,645 | $39,975 | $53,300 | $66,625 | $79,950 | $106,600 | $133,250 |
| 4 | $16,075 | $24,113 | $32,150 | $40,188 | $41,795 | $48,225 | $64,300 | $80,375 | $96,450 | $128,600 | $160,750 |

*Source: U.S. Department of Health and Human Services, Office of the Assistant Secretary for Planning and Evaluation*

## CSBG Module 4 Section C Reporting

### Household Level Characteristics

#### Household Type Categories
- Single Person
- Two Adults NO Children
- Single Parent Female
- Single Parent Male
- Two Parent Household
- Non-related Adults with Children
- Multigenerational Household
- Other
- Unknown/not reported

#### Income Source Categories
- Income from Employment Only
- Income from Employment and Other Income Source
- Income from Employment, Other Income Source, and Non-Cash Benefits
- Income from Employment and Non-Cash Benefits
- Other Income Source Only
- Other Income Source and Non-Cash Benefits
- No Income
- Non-Cash Benefits Only
- Unknown/not reported

#### Other Income Sources
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

#### Non-Cash Benefits
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

#### Housing Status
- Own
- Rent
- Other permanent housing
- Homeless
- Other
- Unknown/not reported

## System Permissions & Security

### Role-Based Access Control
Permissions can be combined into roles allowing users to:
- Edit participants
- Manage households
- Administer programs
- Record services
- Manage work plans and goals

### Data Access Permissions
- Program-based data access control
- Enrollment-specific permissions
- Basic contact information visible to all users
- Restricted access to sensitive enrollment data

## Additional System Components

### Housing Rehabilitation Project Management (Optional Module)
A specialized system for managing housing rehabilitation projects including:

**Project Management Features:**
- Multiple contractor/trades project tracking
- Fund-specific project requirements
- Property and participant association
- Contractor database management
- Property database management

**Key Databases:**
- Participant database
- Contractor database  
- Property database
- Project tracking system

### Notification System
- Email reminders for contract deadlines
- Automated reporting notifications
- User-configurable alert preferences
- System-wide announcements

### Data Import/Export
- Custom CSV mapping functionality
- Bulk data import capabilities
- Report generation and export
- Data validation and error handling

## Technical Requirements

### Web-Based Platform
- Cross-platform compatibility
- Mobile-responsive design
- Secure data transmission
- Regular backup systems

### Integration Capabilities
- Custom data source integration
- API support for external systems
- Flexible import/export formats
- Third-party service compatibility

---

*This documentation provides a comprehensive overview of the CAP TRACK system. For specific implementation details or technical specifications, please refer to the system administration guide or contact the development team.* 