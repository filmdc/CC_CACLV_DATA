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

#### Income eligibility (standing intake requirement)

Every Comm Log that graduates to an intake — i.e. gets a service or housing service attached, the same condition `CommunicationLog::computeAutoCategory()` uses — is stamped with a standing `income-eligibility` IntakeRequirement by `IntakeRequirementInstantiator::incomeEligibility()`. It is called from `forHousingService()` and `reconcileTaggedServices()`, is idempotent on `(communication_log_id, key)`, and is tagged `via: ['standard']` so detaching the service that triggered it never orphans it. The library atom is self-healing (`firstOrCreate` on the key), so no data migration is needed to adopt it; `2026_09_01_120100_backfill_income_eligibility_requirements` stamps the row onto already-open, unconverted intakes.

The figures live in `intake_income_assessments` (`App\Models\IntakeIncomeAssessment`), one row per log. Income can be entered as a lump total or itemized by source, in monthly or annual dollars — `App\Services\Intake\IncomeEligibilityCalculator` annualizes and places the household against the app's existing `poverty_guidelines` table. `guideline_year` / `poverty_threshold` / `poverty_percentage` / `poverty_bracket` are a **snapshot**, not a live view: HHS republishes every January and a determination must keep reading the way it read the year it was made. `PovertyGuideline::thresholdFor()` / `bracketFor()` carry the >4-person extrapolation the old `calculatePovertyPercentage()` had inline.

Housing intakes read a different table: any Comm Log with a housing service attached is assessed against the HUD HOME Program income limits — same `poverty_guidelines` table, region `home_abe_metro` (`PovertyGuideline::REGION_HOME_ABE`), seeded by `HomeIncomeLimitsSeeder` from HUD's FY table for the Allentown-Bethlehem-Easton, PA HUD Metro FMR Area. The LOW INCOME (80% of area median) row is the program's eligibility **ceiling** and sits in `poverty_100`; the other bracket columns are just multiples of it for the admin matrix. **The reported percentage is of AREA MEDIAN INCOME, not of that ceiling** — HUD bands HOME beneficiaries by % of AMI (ELI ≤30 / VLI ≤50 / 60 / LI ≤80, `PovertyGuideline::HOME_TIERS`), so measuring against the ceiling reported a household at 24% of AMI as "30.1%". `IncomeEligibilityCalculator::assess()` therefore divides by `PovertyGuideline::homeAmiFor()` and returns the ceiling separately as `threshold` plus an explicit `eligible` flag; `poverty_threshold` still snapshots the ceiling and the new `area_median_income` column snapshots what the percentage is of. Eligibility has never been gated in code — it is income ≤ ceiling, stated outright on screen now that "≤100%" no longer means "eligible". The region routing lives in one place — `ManagesIncomeEligibility::incomeGuidelineRegion()` — derived from the intake itself, so both front doors always agree. `PovertyGuideline::thresholdFor()` extrapolates HOME limits above 8 people by HUD's rule (+8% of the 4-person limit per person) and never falls back to the HHS increment; non-housing intakes keep reading the HHS `48_contiguous` guidelines. The area median itself comes from `PovertyGuideline::homeMedianFamilyIncome()`: HUD's published 4-person MFI when someone has entered it on the settings screen (stored as the `home_ami_mfi.<year>` site setting), otherwise derived as the 4-person ceiling ÷ 0.8 — exact unless HUD capped the 80% limit that year, which the screen warns about. Family sizes are adjusted by HUD's factors (`HOME_FAMILY_SIZE_FACTORS`, 70/80/90/100/108/116/124/132% of the 4-person figure). Determinations taken before this rebasing are fixed by `php artisan intake:recompute-home-determinations` (`--dry-run` first); it pins each row to the year it was snapshotted against and never touches `poverty_threshold`.

HUD reissues the limits annually — the portal's **Settings → HOME income limits** (`Homes\Settings\IncomeLimits`, `/settings/income-limits`) adds the new year by copying the last one forward, or add the block to `HomeIncomeLimitsSeeder` and re-run it. Both write through `PovertyGuideline::upsertHomeCeiling()` — the single writer that derives the monthly row and the bracket columns off the one real HUD figure — so a hand-edit and a re-seed cannot disagree (pinned by the parity test in `tests/Feature/Homes/IncomeLimitsTest.php`).

The determination is **read-only on the project**: `Homes\ProjectDetail` reads it back through `HousingJob::originatingCommunicationLogs()` so a converted household's qualification travels with the work, with a link back to the intake to change it. Intakes converted before the feature existed carry none — the backfill migration deliberately skips converted logs — and the card says so rather than rendering blank.

Proof documents (pay stubs, tax return, benefit letters) hang off the requirement's existing Spatie `documents` collection, tagged with an `income_doc_type` custom property, so media authorization and downloads are unchanged.

Both front doors share `App\Livewire\Concerns\ManagesIncomeEligibility` — only the modal markup differs (`resources/views/components/comm/income-eligibility-modal.blade.php` for the main app, `resources/views/livewire/homes/partials/income-eligibility.blade.php` for the portal). Saving a complete assessment marks the requirement received, which feeds the Phase-1 conversion gate. Tests: `tests/Feature/Regression/IntakeRequirements/IncomeEligibilityTest.php`.

Fund-code association on `CommunicationLog` is via the `anticipatedFundCodes()` relation (table `communication_log_anticipated_fund_codes`). The legacy `communication_log_fund_code` tag pivot was dropped in P-39b.

#### Today card: Projects by fund

`HousingPortalData::projectsByFund()` regroups the active project list under the funds paying for it, rendered as a collapsible card on Today (widget key `funds`, on by default, toggled at Settings → Dashboard widgets like every other card).

**Grouped off the fund↔project pivot, NOT off budget dollars.** The standalone budget module only knows what she has typed into it — in production *zero* `project_budgets` rows carry a `fund_code_id`, which is why Reports → "Fund spending" renders empty — while 8 funds carry 35 active projects between them on the pivot. Grouping on the assignment shows the real work; grouping on money would show nothing.

The card reports **one number per fund** — how many projects are on it — and sorts busiest-first, then by code, so the ordering is always the number the row shows. An earlier revision split the count into needs-a-hand / active / finished; that was removed as noise (the status split belongs on the Projects list, where it can be filtered). Ordering *inside* a fund still leads with anything needing a hand, so expanding a fund surfaces the problems first. Each group is capped at 6 rows with an "N more" link to the Projects list. The header deliberately spells out "8 funds" rather than using the numeric badge the other cards carry — the badge reads as an item count, and here the items are funds, not projects.

A project on two funds appears under both. That is not double counting: the question is per fund, and until its funding is narrowed the project genuinely rides on both.

Because this is the one Today card needing no intake access, the blade's `@unless($canViewIntakes)` early exit was widened — a `projects.view`-only user now gets this card instead of a dead-end "no access to the communications log" message, and the intake cards carry their own `$canViewIntakes` check. Tests: `tests/Feature/Homes/ProjectsByFundTest.php`.

#### Project funding: narrowing to one fund code

A project inherits its fund codes wholesale — the county decides which codes are in play, ALL anticipated codes carry over at conversion (`IntakeRequirementInstantiator::carryForwardToProject`). That is right at conversion, when nobody knows yet which fund will pay, and wrong once the award lands and the project is restricted to a single code of that county's set.

`App\Services\ProjectFundingService` is the one place a live project's funding changes. It is deliberately **narrowing-shaped**: `availableFor()` is the county's codes (`FundCode::housingServices()`, the new inverse of `HousingService::fundCodes()`) plus whatever the originating intake anticipated — so nothing here can widen a project past what its county covers, and `add()` refuses a code outside that pool. The intake half of the pool exists for projects converted before counties carried fund codes; without it, narrowing one of those would be a one-way door.

Two things hang off a project's fund codes and both are handled:

- **Budget lines** (`project_budgets.fund_code_id`) — a code a budget line spends from cannot be dropped. This is the existing P-2.5 invariant in `HousingJob::detachFundCode()`; `blockedReason()` pre-checks it so the screen names the categories in the way *before* she commits, rather than failing on confirm.
- **Requirements** (`source_type='fund_code'`) — split by `isUntouched()`. Ones never started (outstanding, no attachment, no note) came from a premise that turned out wrong and are offered for removal, default on. Ones carrying **any** progress — complete, not-applicable, or holding a file — are **never** removed by this path. This is a deliberate divergence from the main app's P-18 keep/delete modal (`ProjectManagement::confirmDetachFundCode`), which will hard-delete a completed requirement: `Requirement` does not soft-delete, so that path can destroy uploaded evidence. Kept requirements stay on the project as-is and come back into view if the fund code is added back — `RequirementInstantiator::forFundCode` skips templates that already have a row, so re-adding never duplicates.

UI is portal-only (`Homes\ProjectDetail` → "Narrow funding" beside the fund chips; the budget empty-state links to it too). Step 1 lists what is on the project and what its county still offers; step 2 previews exactly what dropping the rest does. Gated on **`projects.manage`** — matching the main app's `attachFundCode`/`detachFundCode` posture rather than the portal's looser `projects.view`, because it moves money and requirements. `project_manager` and `Administrator` carry it; `housing_coordinator` deliberately does not. Tests: `tests/Feature/Homes/ProjectFundingTest.php`.

#### Timeline entries ("Every touch")

`communication_log_updates` rows are correctable and removable from the portal (`Homes\CallDetail` — Edit / Remove on each timeline row). Two rules carry it:

- **What a person typed is hers to fix or remove.** Editing rewrites `update_content` / `update_type` / `update_date` and stamps `edited_at` / `edited_by` — kept deliberately separate from `updated_at`, which moves for writes nobody typed (a merge re-pointing the row, a backfill). `created_at` / `created_by` never move, so the entry keeps its place in the chronology and its author.
- **What the SYSTEM wrote about the record is never reworded** — requirement transitions, conversions, merges (`update_type = 'status_change'`, `CommunicationLogUpdate::TYPE_SYSTEM`). Rewriting one would make the record claim something that never happened. An admin may remove one; the project manager may not. `AUTHORED_TYPES` is the kind list offered on edit and deliberately excludes `status_change`.

Removal is a **soft delete** (`SoftDeletes` + `TracksSoftDeleter` on the model, migration `2026_09_16_090000`), matching the posture the call log and the project already take. The entry leaves the timeline and every count taken off the `updates()` relation, but stays on the record under "Show N removed entries" — visible to anyone who can read the call, restorable in place by anyone with `communications_log.edit`. It is not a recycle-bin item: it is put back where it was taken from.

Gate is `communications_log.edit` throughout, matching the main app's existing `CommunicationsLogManagement::deleteUpdate` (which now soft-deletes too, for free, via the model trait). The root row of the timeline is the log itself, not a row — its Edit opens the existing `openEdit()` modal, and it has no Remove (deleting it means deleting the call, which carries its own ordering rule in `HousingRecordDeleter`). Tests: `tests/Feature/Homes/TimelineEntryTest.php`.

#### Uploaded file names

Uploads keep the name the file already had. Spatie stores two: `file_name` (on disk) and `name` (the uploader's own). Several paths used to write Livewire's random temp hash into `file_name`, so screens reading that column surfaced `8f2c…c1a9.pdf`. All upload sites now pass `MediaValidationService::sanitizeFileName($file->getClientOriginalName())` — the convention `ProjectManagement` / `Homes\ProjectDetail` / `PdfFormService` already used — and every display path plus `MediaController` preview/download go through **`MediaValidationService::displayName($media)`**, which prefers `name` and carries the extension over from `file_name`. That second half repairs everything already uploaded (those rows all have the real name in `name`) without moving a single file on disk, so no backfill is needed. Tests: `tests/Feature/Homes/UploadedFileNamingTest.php`.

#### Duplicate intake merge

The project manager sometimes logs the same homeowner twice — a second call arrives before she finds the first entry — and the person's history ends up split across two Comm Logs. `App\Services\CommunicationLogMergeService` folds one into the other.

It is a true record↔record merge in the same spirit as `ParticipantMergeService`: rows are **re-pointed, not copied**, so attachments, uploaders and timestamps keep their original identity and the survivor reads as one continuous history. Everything happens in one transaction — a half-merged pair is worse than either duplicate.

What moves onto the survivor: `communication_log_updates`; the duplicate's own root call, written as one more timeline entry **backdated to `communication_date`** so the newest-first chronology does not lie; `intake_requirements`; the `intake_income_assessments` row; the services / housing-services / anticipated-fund-code pivots; both pin tables; and `taggables`. The survivor wins every field it already has a value for and its **blanks** are filled from the duplicate.

- **Requirement dedupe** is by `requirementSignature()` — `key`, else `source_definition_id`, else `source_template_id`, else lowercased label. When both sides carry the same requirement the survivor's row is kept and reconciled: a met status beats an unmet one (never the reverse), un-orphaned beats orphaned, `via` sources are **unioned** so later detach reconciliation still knows every reason the row exists, and the duplicate's files are re-pointed onto the kept row. Spatie paths key off media id, not model id, so no files move on disk.
- **Income determination**: one row per log (unique index), so when both sides have one the survivor's stands and the duplicate's is left in the snapshot rather than silently overwriting a determination.
- **Open follow-up** is the one piece of forward-looking state a merge could silently drop. It transfers only when the survivor has nothing open of its own; otherwise it becomes a `follow_up` timeline entry so the commitment stays visible.
- **`activity_log` is deliberately NOT re-pointed** — rewriting it would claim edits were made to a record they were never made to. It stays on the soft-deleted row.

The duplicate is then soft-deleted with a tombstone: `merged_into_id` / `merged_at` / `merged_by` on `communication_logs` (added in `2026_09_15_150000`, kept out of `$fillable` on purpose — only the service writes them). `HousingRecordDeleter::restoreCallNote()` reads it so the recycle bin says "merged into COMM-123 … restoring this one brings back the empty entry, not its contents" instead of looking like an ordinary delete. A JSON snapshot lands in `merge-snapshots/` on the local disk, mirroring the participant-merge and delete paths.

**Ordering rule, mirroring `HousingRecordDeleter`**: a log promoted into a live project cannot be merged away — that project's origin is this record. `blockedReason()` is the single gate and is phrased for the project manager, not a developer; the UI shows it inline rather than hiding the option.

UI is portal-only (`Homes\CallDetail` → "Merge duplicate"): step 1 picks the entry, seeded by `findCandidates()` (last name + first initial, punctuation-insensitive phone, exact email, address + zip) with free search behind it; step 2 previews exactly what will move before she commits. Gated on `communications_log.edit` **and** `communications_log.delete` — a record disappears, so it carries the delete posture. Tests: `tests/Feature/Homes/MergeDuplicateTest.php`.

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
- DocumentRoot differs per vhost — check before adding any static file:
  - `data.caclv.org` (`laravel-app-ssl.conf`): `/var/www/CC_CAP_TRACK/public` — the conventional Laravel docroot.
  - `homes.caclv.org` / `approvals.caclv.org`: `/var/www/CC_CAP_TRACK` (the project root), with `.htaccess` + `index.php` copied there and `Alias /build` pointing back at `public/build`.
  - A static asset that must serve on ALL hosts therefore needs a copy in both the project root and `public/` (see `bin/build-homes-favicon.php`, which writes both).
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
- **Components** (`app/Livewire/Homes/`): `Login`, `Today`, `CallsIntakes`, `CallDetail`, `Projects`, `ProjectDetail`, `Reports`, `SettingsHub` + `Settings\{FundCodes,RequirementsLibrary,Contractors,Properties,PdfTemplates}` (portal-native admin surfaces), and `QuickLog` (modal, embedded in the layout, opened via the `homes-open-quick-log` browser event).
- **Read model**: `App\Services\Homes\HousingPortalData` centralizes the "stage" and "N of M docs" logic and the Today-card queries — thin wrappers over the SAME scopes/constants the main app uses (`CommunicationLog::pendingFollowUp`/`overdueFollowUp`, `IntakeRequirement::MET_STATUSES`, `RequirementDefinition::PHASE_1_CATEGORIES`, `FundCode` `expiry_state`, `HousingJob::active`).

### Status / phasing
- **Phase 1 (`feat/homes-portal`, PR #43)**: portal shell + login + Today home + Calls & Intakes list (person-language pills + Expanded-view toggle restoring the full table) + quick-log. Additive — the existing in-app housing UI stays live and unchanged.
- **Phase 2/3 (`feat/homes-portal-phase2`)**: call detail (`/calls/{log}` — "Every touch" timeline, document checklist with Received/waive/undo + attachments, follow-up card, convert modal), Projects (`/projects` list + `/projects/{job}` detail — milestones→requirements union, budget bars, add-expense) and Reports (`/reports` — 4 canned reports + CSV export via the native stream idiom). Charts are pure CSS; no Chart.js in the portal bundle.
- **Convert-to-project is now a shared service**: `App\Services\IntakeConversion\ConvertIntakeToProject` (+ `ConversionInput`/`ConversionResult`/`ConversionBlocked` reason codes) holds the full flow — guards, participant/property resolution, duplicate scan (`findDuplicates`), Phase-1 gate (`gate`), job creation, carry-forward, default milestones. `CommunicationsLogManagement` and `Homes\CallDetail` are both thin wrappers over it with identical semantics (parity: ALL anticipated fund codes carry over; program auto-derived). The convert regression tests (`ConvertToProjectTest`/`ConvertGateTest`/`ConvertCarryForwardTest`) pin the wrapper surface — keep error keys `requirementOverride`/`requirementOverrideReason` intact.
- **Nudge is hidden (2026-09-01)**: the portal offers no system nudge — the project manager chases homeowners and contractors herself. `CallDetail::nudge()` is kept dormant with its tests (first nudge = `markRequested()` writing a `status_change` entry; repeat = `addUpdate(..., 'follow_up')`; never homeowner-facing, per OQ-6) so re-enabling is a one-line change in the checklist row. Recording that contact was made lives on the requirement actions modal as "Asked for it".
- "Fund spending" report sources ONLY from `ProjectBudget` grouped by `fund_code_id` (standalone-budget contract — no Sage/fiscal paths). It reads empty until budget lines carry fund codes.
- **Settings are portal-native** (`/settings/*`): fund codes (CRUD + expiry dates + atom links; conservative delete — in-use codes deactivate only), requirements library (atoms-only, soft delete, auto-slugged keys), contractors (CRUD + services chips, delete guarded by jobs), properties (CRUD + notes; `participant_id` NOT NULL — derived from the household head), PDF templates (upload via `PdfFormService`/pdftk + metadata; **field mapping links out to the main app**), and counties/housing-services (`Settings\Counties` — CRUD + per-county fund/atom link syncing, soft delete, gated `fund_codes.*` like the main app's Housing Services tab), and HOME income limits (`Settings\IncomeLimits` — one editable annual ceiling per household size, year copy-forward, gated on the hyphenated `poverty-guidelines.*` permissions; the HHS/FPL guidelines stay in the main app's `PovertyGuidelineManagement`). Still main-app-only by design: contractor/property document approval, PDF field mapping.
- **Configurable list views**: Calls & Intakes and Projects share the `HasSavedViews` trait (`app/Livewire/Homes/Concerns/`) + `homes_saved_views` table — per-user named views (visible columns + default filter pill + expanded flag on calls + sort), one flagged default applied on mount; explicit URL filter params always win over the saved default. Built-in defaults: Calls = Everything + Expanded on; Projects = Open. Shared UI partial: `resources/views/livewire/homes/partials/saved-views.blade.php`.
- **Column sorting**: both list screens share `HasSortableColumns` (`app/Livewire/Homes/Concerns/`) — `$sort` / `$sortDir` in the URL, a header click cycling natural direction → reversed → back to the screen's default, plus the toolbar dropdown (`partials/sort-control.blade.php`) that also covers the views with no clickable header. `$sort = ''` means the screen's own default ordering, so nothing about the previous behaviour changed unless a column is picked. The two screens sort in different places on purpose: **Calls is paginated, so it orders in SQL** (`CallsIntakes::applySort()`/`sortExpressions()`; the derived Stage and Docs columns order against correlated subqueries counting the same Phase-1 requirements `HousingPortalData::phase1Progress()` counts in PHP), while **Projects already holds its whole active set, so it orders in memory** (`Projects::sortRows()`, reading raw values `projectRow()` carries alongside the display strings). Pinned rows keep their pin order — a pin ignores sort as it already ignores the pills. Sortable headers: the calls `<thead>` and `partials/project-sort-header.blade.php`.
- Tests: `tests/Feature/Homes/` (HousingPortalTest, ConvertServiceTest, CallDetailTest, ProjectsTest, ReportsTest, SavedViewsTest, SortingTest, MergeDuplicateTest).

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
