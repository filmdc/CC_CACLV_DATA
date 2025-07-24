# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
## Feature Implementation System Guidelines

### Feature Implementation Priority Rules
- IMMEDIATE EXECUTION: Launch parallel Tasks immediately upon feature requests
- NO CLARIFICATION: Skip asking what type of implementation unless absolutely critical
- PARALLEL BY DEFAULT: Always use 7-parallel-Task method for efficiency

### Parallel Feature Implementation Workflow
1. **Component**: Create main component file
2. **Styles**: Create component styles/CSS
3. **Tests**: Create test files  
4. **Types**: Create type definitions
5. **Hooks**: Create custom hooks/utilities
6. **Integration**: Update routing, imports, exports
7. **Remaining**: Update package.json, documentation, configuration files
8. **Review and Validation**: Coordinate integration, run tests, verify build, check for conflicts

### Context Optimization Rules
- Strip out all comments when reading code files for analysis
- Each task handles ONLY specified files or file types
- Task 7 combines small config/doc updates to prevent over-splitting

### Feature Implementation Guidelines
- **CRITICAL**: Make MINIMAL CHANGES to existing patterns and structures
- **CRITICAL**: Preserve existing naming conventions and file organization
- Follow project's established architecture and component patterns
- Use existing utility functions and avoid duplicating functionality
## Project Overview

**CAP TRACK** - A comprehensive data management platform for Community Action Partnership agencies. The system handles participant/household data, program services, and CSBG (Community Services Block Grant) reporting requirements.

## Development Commands

### Initial Setup
```bash
composer install          # Install PHP dependencies
npm install              # Install Node.js dependencies
php artisan migrate      # Run database migrations
php artisan db:seed      # Seed initial data (includes InitialSetupSeeder)
```

### Development Environment
```bash
composer dev             # Start full dev environment (server, queue, logs, vite)
```
This runs 4 concurrent processes with color-coded output:
- **Server** (blue): Laravel development server
- **Queue** (purple): Queue worker for background jobs
- **Logs** (pink): Real-time log monitoring with Pail
- **Vite** (orange): Asset compilation with HMR

### Testing
```bash
composer test                              # Run full test suite with config clearing
php artisan test                          # Run all tests
php artisan test --filter TestClassName   # Run specific test class
php artisan test tests/Feature/file.php   # Run specific test file
```
- Uses **Pest PHP** testing framework (extends PHPUnit)
- Tests run against in-memory SQLite database
- RefreshDatabase trait is commented out in `tests/Pest.php` (uncomment if needed)

### Code Quality
```bash
php artisan pint         # Format PHP code using Laravel Pint
```

### Database Management
```bash
php artisan migrate              # Run pending migrations
php artisan migrate:fresh        # Drop all tables and re-migrate
php artisan migrate:fresh --seed # Fresh migration with seeding
php artisan db:seed             # Run all seeders
php artisan db:seed --class=InitialSetupSeeder  # Run specific seeder
```

### Custom Commands
```bash
php artisan user:assign-role {email} {role}    # Assign role to user
php artisan setup:admin-permissions             # Setup admin permissions
php artisan contract:send-reminders             # Send contract report reminders
```

### Contract Notification System
```bash
# Send reminders for all contracts (scheduled to run daily at 8 AM PST)
php artisan contract:send-reminders

# Test mode options
php artisan contract:send-reminders --dry-run                    # Preview without sending
php artisan contract:send-reminders --days=7                     # Only send 7-day reminders
php artisan contract:send-reminders --contract-id=123            # Test specific contract
php artisan contract:send-reminders --test-mode --test-email=test@example.com  # Send all to test email
```

**Features:**
- Global reminder intervals (default: 30, 15, 7, 3, 1, 0 days)
- Custom email templates with variable substitution
- Test mode to prevent accidental mass emails
- Notification test dashboard at `/admin/contract-notification-test`
- Tracks sent reminders to prevent duplicates
- Scheduled to run daily at 8:00 AM PST (configure in bootstrap/app.php)

## Architecture & Patterns

### Tech Stack
- **Laravel 12** (PHP 8.2+)
- **Livewire 3** for reactive UI components
- **Livewire Volt** for simplified component syntax
- **TailwindCSS v4** with Vite plugin
- **MySQL** as default database
- **Pest PHP** for testing

### Key Architectural Decisions

#### 1. Database-First Design
- MySQL is the default connection
- Database name: `CC_CACLV_DATA`
- Sessions, cache, and queue all use database drivers
- Foreign key constraints enabled by default

#### 2. Livewire-Centric Architecture
- Controllers are minimal - business logic lives in Livewire components
- All management interfaces are full-page Livewire components
- Standard component location: `app/Livewire/` with views in `resources/views/livewire/`
- **IMPORTANT**: Although Flux is installed, DO NOT use Flux components - use standard HTML/Tailwind

#### 3. Role-Based Access Control (RBAC)
- Complete permission system with roles and granular permissions
- Custom middleware: `CheckPermission` and `CheckRole`
- `WithPermissionChecks` trait provides helper methods:
  ```php
  $this->can('participants.view')        # Check single permission
  $this->canAny(['perm1', 'perm2'])    # Check if has any permission
  $this->canAll(['perm1', 'perm2'])    # Check if has all permissions
  $this->hasRole('admin')               # Check role
  $this->isAdmin()                      # Check if admin
  $this->authorizeAction('permission')  # Abort with 403 if unauthorized
  ```

#### 4. Routing Patterns
- Management interfaces: Direct Livewire routing
  ```php
  Route::get('/path', ComponentClass::class)->middleware(['auth']);
  ```
- Settings pages: Volt routing
  ```php
  Volt::route('/settings/profile', 'settings.profile');
  ```

### Livewire Component Standards

All management components follow this pattern:

```php
class ComponentManagement extends Component
{
    use WithPagination;
    
    // UI State
    public $showCreateForm = false;
    public $showEditForm = false;
    public $search = '';
    
    // Form fields as public properties
    public $name;
    public $description;
    
    // Standard methods
    public function showCreateForm() { }
    public function showEditForm($id) { }
    public function save() { }
    public function update() { }
    public function delete($id) { }
    public function resetForm() { }
    public function updatedSearch() { } // Real-time search
}
```

### UI/UX Standards

#### Container Structure
```blade
<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6">
        <!-- Content -->
    </div>
</div>
```

#### Dark Mode Support
- Background: `bg-white dark:bg-zinc-800`
- Text: `text-gray-900 dark:text-white`
- Borders: `border-gray-300 dark:border-zinc-600`
- Secondary text: `text-gray-600 dark:text-gray-300`

#### Form Elements
```blade
<input type="text" 
       wire:model="fieldName"
       class="w-full rounded-md border-gray-300 dark:border-zinc-600 
              dark:bg-zinc-700 dark:text-white focus:border-indigo-500 
              focus:ring-indigo-500">
```

## Database Schema

### Core Models & Relationships

#### Participant/Household System
- `Participant` → belongsTo → `Household`
- `Household` → hasMany → `Participants`
- Automatic household member count tracking
- CSBG Module 4 Section C compliant fields

#### Program/Service Structure
- `Program` → hasMany → `Services`, `WorkPlans`, `ProgramEnrollments`
- `Service` → belongsTo → `Program`
- `WorkPlan` → hasMany → `WorkPlanGoals`

#### Enrollment Tracking
- `ProgramEnrollment` → Links `Participant` ↔ `Program`
- `ServiceEnrollment` → Links `Participant` ↔ `Service`

#### Access Control
- `User` → belongsToMany → `Role` (via pivot)
- `Role` → belongsToMany → `Permission` (via pivot)
- Program-specific access via `UserProgram`

#### Housing Module (Branch: housing-rehab-job-tracking)
**Status: Database Layer Complete, UI Layer Not Started**

Models & Relationships:
- `Property` → belongsTo → `Participant`, `Household`, `Program`
- `Contractor` → hasMany → `HousingJobs`, `ContractorDocuments`
- `HousingJob` → belongsTo → `Property`, `Contractor`, `Participant`, `Household`
- `HousingJob` → hasMany → `JobContactLedgers`, `JobDocumentChecklists`, `ServiceEnrollments`
- `Property` → hasMany → `PropertyNotes`, `PropertyDocuments`, `HousingJobs`

Housing Module Features:
- Program-level activation flag (`housing_module_enabled`)
- Property management with types (rental, owner-occupied, facade)
- Contractor tracking with licensing and insurance
- Job tracking with auto-generated numbers (format: YYYYMMDD-XXXX)
- Contact ledger for outreach tracking
- Document checklist for compliance
- Integration with existing services and work plan goals

**TODO: Add HousingModuleSeeder to DatabaseSeeder before running migrations**

### Key Features
- 7-digit alphanumeric household codes (auto-generated)
- 2025 Federal poverty guidelines calculations
- JSON fields for complex data (income sources, benefits)
- Comprehensive audit fields (created_by, updated_by)
- Housing job numbers (YYYYMMDD-XXXX format)

## Development Workflow

### Starting New Feature
1. Create/update database migration if needed
2. Create Livewire component: `php artisan make:livewire ComponentName`
3. Add route in `routes/web.php`
4. Follow existing component patterns (use `ServiceManagement` as reference)
5. Add to sidebar navigation if applicable
6. Write Pest tests for new functionality

### Testing Approach
- Feature tests for user flows
- Unit tests for business logic
- Use factories for test data generation
- Test files mirror source structure

### Common Patterns

#### Modal Forms
```blade
@if($showCreateForm)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full">
            <!-- Form content -->
        </div>
    </div>
@endif
```

#### Data Tables
```blade
<table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
    <!-- Standard table structure with dark mode support -->
</table>
```

#### Success Messages
```blade
@if (session()->has('message'))
    <div class="rounded-md bg-green-50 dark:bg-green-900 p-4 mb-4">
        <!-- Message content -->
    </div>
@endif
```

## Important Notes

1. **NO Flux Components**: Despite Flux being installed, use standard HTML/Tailwind patterns
2. **MySQL by Default**: Database connection configured in `.env` file
3. **Seeder Data**: InitialSetupSeeder creates admin users and sample data
4. **Permission Naming**: Follow pattern `resource.action` (e.g., `participants.create`)
5. **Dark Mode First**: All new UI must include dark mode variants
6. **CSBG Compliance**: Participant data must maintain CSBG field requirements
7. **Housing Module**: Currently on `housing-rehab-job-tracking` branch with database layer complete but UI not implemented

## Current Implementation Status

### Apache Configuration
- DocumentRoot: `/var/www/CC_CAP_TRACK` (not using /public subdirectory)
- `.htaccess` and `index.php` moved to root directory
- MySQL database: `CC_CACLV_DATA`

### Pending Tasks
1. **Housing Module UI Implementation**:
   - PropertyManagement Livewire component (empty stub exists)
   - ContractorManagement component (not created)
   - HousingJobManagement component (not created)
   - Document upload functionality
   - Job workflow with phase management
   - Routes and navigation items

2. **Integration Tasks**:
   - Add `HousingModuleSeeder::class` to DatabaseSeeder
   - Add housing navigation items to sidebar (conditional on `housing_module_enabled`)
   - Implement permission checks in housing components

3. **Testing**:
   - No tests written for housing module yet
   - Need feature tests for all housing components

## Memories

- Do not try to run bash mysql commands that require a password, as the terminal prevents that approach

