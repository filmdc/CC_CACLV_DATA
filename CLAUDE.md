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
- Uses **Pest PHP 3.8** (+ `pest-plugin-laravel`, extends PHPUnit)
- Tests run against **MySQL** database `CC_CACLV_DATA_test` on the local server, routed via `phpunit.xml` env overrides (there is deliberately NO `.env.testing` — overrides apply only to `php artisan test`)
- `RefreshDatabase` trait is applied **globally** to `tests/Feature` in `tests/Pest.php`
- Regression harness: `./bin/run-regression.sh` runs only `->group('regression')` tests — see `REGRESSION_HARNESS.md`

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

#### 5. Media authorization (P-39a)

Spatie media downloads route through `App\Http\Controllers\MediaController` (`GET /media/{media}/preview`, `/media/{media}/download`). Every `Media` row's parent — `$media->model` — must answer "can this user view this file?" via the `App\Contracts\HasMediaAuthorization` contract.

Any model that implements `Spatie\MediaLibrary\HasMedia` MUST also implement `HasMediaAuthorization`. The single seam is `canViewMedia(User $user, Media $media): bool`. Admin short-circuit and orphaned-parent default-deny are handled in `MediaPolicy::view`; the method body should encode the per-record rule only — typically a permission check (`$user->hasPermission('xxx.yyy')`) matching whatever gate the parent's UI uses. Models without per-record concepts (e.g., `Requirement`, `Contract`) check a single permission; models with an owner column (`Feedback.user_id`, `ApprovalSubmission.submitted_by`) accept either ownership or the relevant permission.

Models that forget to implement the contract default-deny silently — that is intentional fail-closed posture. A regression test in `tests/Feature/Regression/Media/MediaAuthorizationRuleTest.php` scans `app/Models` and fails if any `HasMedia` model is missing the contract.

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

#### Intake vs. Project requirements (P-39)

Two parallel requirement systems share the `fund_code_requirements` template table but maintain independent lifecycles:

- `intake_requirements` — intake-phase. Rooted on `CommunicationLog`. Templates filtered to `phase='eligibility'`. Tracked while qualifying an applicant; ends at promotion via `convertToProject()`. Statuses: `outstanding` / `received` / `waived`. Lifecycle managed by `App\Services\IntakeRequirementInstantiator`.
- `requirements` — project-execution-phase. Rooted on `HousingJob`. Templates filtered to `phase='project'`. Tracked while delivering the housing project. Statuses: `outstanding` / `complete` / `not_applicable`. Lifecycle managed by `App\Services\RequirementInstantiator`.

Both consume `fund_code_requirements` as templates. The `phase` column on that table determines which system instantiates which row. Intake requirements stay attached to the Comm Log after promotion as historical record; they do NOT migrate to the new `HousingJob`.

Fund-code association on `CommunicationLog` is via the `anticipatedFundCodes()` relation (table `communication_log_anticipated_fund_codes`). The legacy `communication_log_fund_code` tag pivot was dropped in P-39b.

#### Housing Module
**Status: UI complete, in daily production use. Work happens on `main`.**

Core Livewire components:
- `CommunicationsLogManagement` (`/communications-log`) — entry intake + multi-step `convertToProject()` flow
- `ProjectsManagement` (`/projects`) — list view with filters and template library modal
- `ProjectManagement` (`/project/{id}/manage`) — detail view: milestones, tasks, budget categories, expenses, documents
- `PropertyManagement` (`/property-management`), `ContractorManagement` (`/contractor-management`), `FundCodeManagement` (`/fund-codes`)

Models & Relationships:
- `Property` → belongsTo → `Participant`, `Household`, `Program`
- `Contractor` → hasMany → `HousingJobs`, `ContractorDocuments`
- `HousingJob` → belongsTo → `Property`, `Contractor`, `Participant`, `Household`, `Program`
- `HousingJob` → hasMany → `ProjectMilestones`, `ProjectBudgets`, `ProjectExpenses`, `JobContactLedgers`, `JobDocumentChecklists`
- `Property` → hasMany → `PropertyNotes`, `PropertyDocuments`, `HousingJobs`
- `CommunicationLog` → belongsToMany → `FundCodes` (pivot `communication_log_fund_code`)

Housing Module Features:
- Program-level activation flag (`housing_module_enabled`)
- Property management with types (rental, owner-occupied, facade)
- Contractor tracking with licensing and insurance
- Job tracking with auto-generated numbers (current format: `JOB-YYYY-#####`)
- Contact ledger for outreach tracking
- Document checklist for compliance
- Budget categories + expenses (`ProjectExpense` auto-updates `ProjectBudget.spent_amount` via a booted observer)
- Templates: implemented as `HousingJob` rows with `is_template=true` + the `HousingJob::templates()` scope + `HousingJob::createFromTemplate()` static (NOT a separate `ProjectTemplate` model)

`HousingModuleSeeder` and `ProjectHousingPermissionsSeeder` are wired up in `DatabaseSeeder`.

##### Housing budget module — design intent

The Housing project budget module (`ProjectBudget`, `ProjectExpense`, and the budget-related portions of `HousingJob` / `ProjectManagement`) is, by design, a **standalone tracker**. It is intentionally NOT integrated with Sage Intacct, MIP, DonorPerfect, or any other fiscal system of record.

Its purpose is narrow:
- Let the housing project manager record a project's funding sources (the fund codes assigned to that project).
- Let her record the allocated amount per line item.
- Let her record expenses against those line items, so total expense vs. budgeted amount is visible at a glance per project.

That is the entire scope. It exists so the housing project manager doesn't have to ask Fiscal "how much have we spent on 123 Main Street?" — she can see it herself, in her own tool, sourced from her own data entry. Fiscal teams use their own systems; those remain authoritative for accounting.

This is NOT a gap to be closed in a future phase. Do not propose Sage integration, fiscal writes, or external-fund-system reads on this module. If a future requirement seems to call for that, escalate before building. The `tests/Feature/Regression/Budget/BudgetIsStandaloneTest.php` tripwire guards this contract via grep-based import scanning of housing budget files.

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
7. **Housing Module**: UI complete and in daily production use; work happens on `main`. Templates are `HousingJob` rows flagged `is_template=true`, not a separate model. Regression harness lives in `tests/Feature/Regression/` — run with `./bin/run-regression.sh` before every merge.

## Current Implementation Status

### Apache Configuration
- DocumentRoot: `/var/www/CC_CAP_TRACK` (not using /public subdirectory)
- `.htaccess` and `index.php` moved to root directory
- MySQL database: `CC_CACLV_DATA`

### Recent work areas
- **Fund View** (tuple-level / fund-keyed dashboards, GL account exclusions)
- **Sage Intacct sync** (gifts, budgets, expenses, deposit dates)
- **DonorPerfect sync** (code discovery, batch feeds, cache invalidation)
- **Approvals portal** (`approvals.caclv.org` — see the Mobile Approvals section below)
- **Regression harness** (`tests/Feature/Regression/` — Phase 0 / P-33; see `REGRESSION_HARNESS.md` and `PHASE_0_NOTES.md`)

## Mobile Approvals Portal (approvals.caclv.org)

A responsive, mobile-first approval interface served at `approvals.caclv.org`. Lives within this project using Laravel domain routing — no separate codebase.

### Architecture
- **Domain routing**: `routes/approvals.php` scoped to `APPROVALS_DOMAIN` env var
- **Mobile layout**: `resources/views/components/layouts/approvals.blade.php` (bottom tab nav, no sidebar)
- **Lightweight bundle**: `resources/css/approvals.css` + `resources/js/approvals.js` (no Chart.js/Flux/Jodit)
- **Shared auth**: Session cookie domain set to `.caclv.org` for cross-subdomain SSO
- **Components**: `app/Livewire/Mobile/` namespace with 6 components:
  - `MobileLogin` — login page (uses `approvals-guest` layout)
  - `ApprovalsDashboard` — stats, quick actions, activity feed
  - `PendingReview` — approver card queue with filters
  - `ApprovalDetail` — full detail view with approve/reject action bar
  - `SubmitRequest` — 4-step submission wizard
  - `MySubmissions` — requester's submission list

### Setup After Pull (Required Steps)

1. **Add env vars** to `.env`:
   ```
   SESSION_DOMAIN=.caclv.org
   APPROVALS_DOMAIN=approvals.caclv.org
   ```

2. **Install Apache VirtualHost**:
   ```bash
   sudo cp approvals.conf.apache /etc/apache2/sites-available/approvals.conf
   sudo a2ensite approvals.conf
   sudo systemctl reload apache2
   ```

3. **DNS**: Ensure `approvals.caclv.org` A record points to this server

4. **Build assets** (if not using pre-built):
   ```bash
   npm install && npx vite build
   ```

5. **Clear caches**:
   ```bash
   php artisan config:clear && php artisan route:clear && php artisan view:clear
   ```

### Development Notes
- Bottom nav tabs are permission-aware (Review requires `approvals.view_dashboard`, Submit requires `approvals.submit`)
- Auth redirect is domain-aware in `bootstrap/app.php` — approvals domain redirects to `mobile.login`
- All existing approval models, observers, and notifications are reused
- NO Flux components in mobile views — standard HTML/Tailwind only

## Housing Portal (homes.caclv.org)

A task-first, CALV-branded front-end for the housing project manager, served at `homes.caclv.org`. Same Laravel app, **same database, same models** as CAP TRACK — a separate front door, not a separate application. Built by cloning the Approvals-portal pattern; the underlying UI redesign came from a Claude Design handoff (task-first "Today" home; comm-log reframed as person-first "Calls & Intakes"; 8 modules collapsed to Today / Calls & Intakes / Projects / Reports + Settings).

### Architecture
- **Domain routing**: `routes/homes.php` scoped to the `HOMES_DOMAIN` env var, loaded before `web.php` in `bootstrap/app.php`; `redirectGuestsTo` is host-aware (homes domain → `homes.login`).
- **Layouts**: `resources/views/components/layouts/homes.blade.php` (light slate sidebar shell) + `homes-guest.blade.php` (login). **Light CALV brand theme, intentionally NOT the app's dark mode** (approvals is intentionally dark; this is intentionally light).
- **Asset bundle**: `resources/css/homes.css` (standalone Tailwind v4 entry embedding the CALV design tokens + `.calv-*` component classes) + `resources/js/homes.js`. Registered in `vite.config.js`. Fonts via bunny.net (Anton/Oswald/Barlow — CALV's licensed faces aren't shipped).
- **Shared auth**: same `.caclv.org` session cookie as the main app (`SESSION_DOMAIN`), own login page (`App\Livewire\Homes\Login`).
- **Components** (`app/Livewire/Homes/`): `Login`, `Today`, `CallsIntakes`, `CallDetail`, `Projects`, `ProjectDetail`, `Reports`, `Settings` (launcher linking the five quarterly-touched admin surfaces in the main app), and `QuickLog` (modal, embedded in the layout, opened via the `homes-open-quick-log` browser event).
- **Read model**: `App\Services\Homes\HousingPortalData` centralizes the "stage" and "N of M docs" logic and the Today-card queries — thin wrappers over the SAME scopes/constants the main app uses (`CommunicationLog::pendingFollowUp`/`overdueFollowUp`, `IntakeRequirement::MET_STATUSES`, `RequirementDefinition::PHASE_1_CATEGORIES`, `FundCode` `expiry_state`, `HousingJob::active`).

### Status / phasing
- **Phase 1 (`feat/homes-portal`, PR #43)**: portal shell + login + Today home + Calls & Intakes list (person-language pills + Expanded-view toggle restoring the full table) + quick-log. Additive — the existing in-app housing UI stays live and unchanged.
- **Phase 2/3 (`feat/homes-portal-phase2`)**: call detail (`/calls/{log}` — "Every touch" timeline, document checklist with Received/Nudge, follow-up card, convert modal), Projects (`/projects` list + `/projects/{job}` detail — milestones→requirements union, budget bars, add-expense) and Reports (`/reports` — 4 canned reports + CSV export via the native stream idiom). Charts are pure CSS; no Chart.js in the portal bundle.
- **Convert-to-project is now a shared service**: `App\Services\IntakeConversion\ConvertIntakeToProject` (+ `ConversionInput`/`ConversionResult`/`ConversionBlocked` reason codes) holds the full flow — guards, participant/property resolution, duplicate scan (`findDuplicates`), Phase-1 gate (`gate`), job creation, carry-forward, default milestones. `CommunicationsLogManagement` and `Homes\CallDetail` are both thin wrappers over it with identical semantics (parity: ALL anticipated fund codes carry over; program auto-derived). The convert regression tests (`ConvertToProjectTest`/`ConvertGateTest`/`ConvertCarryForwardTest`) pin the wrapper surface — keep error keys `requirementOverride`/`requirementOverrideReason` intact.
- **Nudge semantics**: first nudge on a pending intake requirement = `markRequested()` (writes its own `status_change` timeline entry); repeat nudge = `addUpdate(..., 'follow_up')`. No homeowner-facing notification (worker-side record only, per OQ-6).
- "Fund spending" report sources ONLY from `ProjectBudget` grouped by `fund_code_id` (standalone-budget contract — no Sage/fiscal paths). It reads empty until budget lines carry fund codes.
- Tests: `tests/Feature/Homes/` (HousingPortalTest, ConvertServiceTest, CallDetailTest, ProjectsTest, ReportsTest).

### Setup after pull
1. `.env`: add `HOMES_DOMAIN=homes.caclv.org` (ensure `SESSION_DOMAIN=.caclv.org` in production for shared SSO).
2. `sudo cp homes.conf.apache /etc/apache2/sites-available/homes.conf && sudo a2ensite homes.conf && sudo systemctl reload apache2`.
3. DNS: `homes.caclv.org` A record → this server; confirm the TLS cert covers the host (add SAN if needed).
4. `npx vite build`.
5. `php artisan config:clear && php artisan route:clear && php artisan view:clear`.

## Memories

- Do not try to run bash mysql commands that require a password, as the terminal prevents that approach
- This is an apache hosted project, so all work on this project must work as a standard apache virtual hosted web application
- Do not use artisan to serve the site. It only is to be configured so the site and its assets work right from apache
