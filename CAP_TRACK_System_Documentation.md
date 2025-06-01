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

### Core Modules (Implemented)

#### 1. User Management & Dashboard
- ✅ Secure user login system with email verification
- ✅ Dashboard showing overall program progress
- ✅ Work plan goal tracking
- ✅ Participant service metrics
- ✅ New participant record entry

#### 2. Administrative Backend
- ✅ System setup and configuration (Admin Panel)
- ✅ User management and permissions
- ✅ Reference data management
- ✅ Household and participant management interfaces
- ✅ Data import/export functionality
- ✅ Custom CSV import mapping
- ✅ Poverty guidelines management system
- ✅ Enhanced date field input with keyboard support
- ✅ Real-time form validation

#### 3. Complete Management Interface Suite (NEW)
- ✅ **Participant Management** - Full CRUD operations for participant records with CSBG compliance
- ✅ **Household Management** - Family composition and member management (previously implemented)
- ✅ **Program Management** - Program configuration, manager assignment, and lifecycle management
- ✅ **Service Management** - Service CRUD operations with program association (previously implemented)
- ✅ **Contract Management** - Funding contract management with document tracking
- ✅ **Work Plan Management** - Annual work plan creation and tracking
- ✅ **Work Plan Goal Management** - Goal setting with target vs actual progress monitoring
- ✅ **Role & Permission Management** - Complete RBAC system with role-permission assignment
- ✅ **Poverty Guidelines Management** - Federal poverty level data management with year/region support
- ✅ **Reference Data Management** - Configurable dropdown options and system values (NEW)
- ✅ **Reference Data Set Management** - Organized collections of reference data with categories (NEW)
- ✅ **Dark Mode Support** - All interfaces fully compatible with light and dark themes
- ✅ **Responsive Design** - Mobile-friendly layouts across all management interfaces
- ✅ **Consistent UI/UX** - Unified design language using HTML/Tailwind/Livewire patterns

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

**Management Interface Features:**
- ✅ Complete CRUD operations (Create, Read, Update, Delete)
- ✅ Real-time search across service names and descriptions
- ✅ Status toggle functionality (Active/Inactive)
- ✅ Program association with dropdown selection
- ✅ Date validation ensuring end dates are after start dates
- ✅ Pagination for large service lists
- ✅ Responsive design for mobile and desktop access
- ✅ Integration with main navigation under "Programs" section

### Contracts
Funding sources with specific terms enabling program operations.

**Contract Management:**
- Detailed contract information storage
- PDF document uploads
- Program manager guidance
- Reporting submission functionality
- Email notification system for deadlines

**Implementation Status:**
- ✅ Contract model with complete field definitions
- ✅ Contract management Livewire component created
- 🔄 Contract management interface (in development)
- 🔄 Navigation integration (pending completion)

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

### Reference Data Management System (NEW)
Comprehensive system for managing configurable system values and dropdown options.

**Reference Data Sets:**
- Organized collections of related reference data
- Category-based organization for easy management
- Bulk operations for efficient data management
- Hierarchical structure support

**Reference Data:**
- Individual configurable values within sets
- Display name and value differentiation
- Sort order customization
- Active/inactive status management
- Association with specific reference data sets

**Key Features:**
- ✅ **Complete CRUD Operations** - Create, read, update, delete for both sets and individual data
- ✅ **Category Organization** - Logical grouping of reference data by purpose/context
- ✅ **Search and Filtering** - Real-time search across data sets and individual values
- ✅ **Status Management** - Active/inactive toggles for dynamic content control
- ✅ **Sort Order Control** - Custom ordering for dropdown and display purposes
- ✅ **Relationship Management** - Proper linking between sets and individual data items
- ✅ **Dark Mode Compatible** - Full support for light/dark theme switching
- ✅ **Mobile Responsive** - Optimized for all device sizes

**Use Cases:**
- Dropdown options for participant forms
- System configuration values
- Custom field choices by program
- Compliance reporting categories
- Service classification systems

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

## Technical Implementation

### Framework & Technology Stack
- ✅ **Laravel 11** - Modern PHP framework with latest features
- ✅ **Livewire 3** - Dynamic frontend framework for real-time interactions
- ✅ **Livewire Volt** - Simplified component creation and routing
- ✅ **Livewire Flux** - Professional UI component library
- ✅ **TailwindCSS v4** - Utility-first CSS framework for responsive design
- ✅ **Vite** - Modern asset bundling and hot module replacement
- ✅ **SQLite** - Lightweight database for development and testing
- ✅ **Pest PHP** - Modern testing framework with comprehensive coverage

### Current Interface Status

#### Completed Interfaces
- ✅ **Authentication System** - Complete login, registration, password reset, email verification
- ✅ **Dashboard** - Program progress tracking and metrics overview
- ✅ **Admin Panel** - System configuration with enhanced date field functionality
- ✅ **Settings** - Profile, password, and appearance management

#### Complete Management Interface Suite
- ✅ **Participant Management** - Full CRUD operations, demographic fields, CSBG compliance, search/pagination
- ✅ **Household Management** - Family composition management, member addition/removal, household details
- ✅ **Program Management** - Program CRUD, manager assignment, status management, relationship tracking
- ✅ **Service Management** - Service CRUD, program association, date validation, status toggles
- ✅ **Contract Management** - Contract CRUD, funding tracking, program association, document management
- ✅ **Work Plan Management** - Annual work plans, program association, goal tracking overview
- ✅ **Work Plan Goal Management** - Goal CRUD, target vs actual tracking, progress visualization, service mapping
- ✅ **Role & Permission Management** - Complete RBAC system, role creation, permission assignment, tabbed interface
- ✅ **Poverty Guidelines Management** - Federal poverty level data, year/region filtering, comprehensive percentage matrix
- ✅ **Reference Data Management** - Configurable system values, dropdown options, form choices with CRUD operations
- ✅ **Reference Data Set Management** - Organized collections with categories, hierarchical organization, bulk management

#### Interface Features
- ✅ **Dark Mode Compatibility** - All interfaces support light/dark theme switching
- ✅ **Responsive Design** - Mobile-friendly layouts with proper breakpoints
- ✅ **Consistent Styling** - Unified HTML/Tailwind/Livewire patterns across all interfaces
- ✅ **Form Validation** - Real-time validation with error display
- ✅ **Search & Pagination** - Efficient data browsing across all list views
- ✅ **Relationship Management** - Proper handling of model associations and dependencies

### Quality Assurance & Testing
- ✅ **Comprehensive Test Coverage** - Feature and unit tests for all implemented interfaces
- ✅ **Date Field Validation** - Multiple input format support (MM/DD/YYYY, YYYY-MM-DD, etc.)
- ✅ **Real-time Form Validation** - Immediate feedback on form inputs
- ✅ **Cross-browser Compatibility** - Responsive design works across devices
- ✅ **Security Implementation** - Authentication, authorization, and data protection

### Development Commands
- `composer dev` - Complete development environment
- `composer test` - Full test suite execution
- `php artisan pint` - Code formatting and style consistency
- `npm run dev` - Asset compilation with hot reload

### Web-Based Platform
- ✅ Cross-platform compatibility
- ✅ Mobile-responsive design
- ✅ Secure data transmission
- ✅ Regular backup systems

### Integration Capabilities
- ✅ Custom data source integration
- ✅ API support for external systems
- ✅ Flexible import/export formats
- ✅ Third-party service compatibility

---

*This documentation provides a comprehensive overview of the CAP TRACK system. For specific implementation details or technical specifications, please refer to the system administration guide or contact the development team.* 