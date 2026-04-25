# Phase 0 Notes (P-33) — Discovery, Pre-existing Failures, Risk Areas

## Discovery summary

The Housing module's "UI not started" wording in `CLAUDE.md` is stale. Reality
as of 2026-04-24 on `main`:

- Communications Log (`/communications-log`) — `CommunicationsLogManagement`,
  1940 LOC. Includes a multi-step conversion flow into a project (`HousingJob`).
- Projects (`/projects`, `/project/{id}/manage`) — `ProjectsManagement` list +
  `ProjectManagement` detail. Templates live on `HousingJob` rows flagged
  `is_template=true`, not a separate model.
- Budget + Expenses — inside `ProjectManagement`, with an `ContractFullyUtilizedException`
  guard and admin override path.
- Milestones + Tasks + Documents — `ProjectMilestone` with `MilestoneCommunication`,
  `MilestoneDocument`, `MilestoneTask`.
- Fund Codes — `FundCode`, `FundCodeManagement` at `/fund-codes`.
- Properties, Contractors — `PropertyManagement`, `ContractorManagement`.

`CLAUDE.md` is refreshed in a separate commit to match (see the
`docs: refresh CLAUDE.md …` commit in this branch).

## Pre-existing test failures (NOT caused by P-33)

Three clusters. Log-and-leave per scope rules.

### 1. ParticipantImportTest — 9 failures

Source: `tests/Feature/ParticipantImportTest.php`
Nature: validation error-string drift. Asserting literal messages that the
framework or the project's validator config has rewritten since the tests
were written.
Suggested next-step: a focused cleanup PR that either updates the expected
strings to match current output or switches the assertions to validator
key checks (`assertValidationError('email')` rather than message text).

### 2. ServiceManagementTest — 3 failures

Source: `tests/Feature/ServiceManagementTest.php`
Nature: `Service` model drift. Tests reference fields or relationships
that have been renamed / removed.
Suggested next-step: re-read `app/Models/Service.php` and realign the test
expectations.

### 3. Settings/ProfileUpdateTest — 3 failures

Source: `tests/Feature/Settings/ProfileUpdateTest.php`
Nature: `Livewire\Exceptions\RootTagMissingFromViewException`. The view the
test mounts has either been wrapped incorrectly (nested root elements) or
refactored out.
Suggested next-step: inspect `resources/views/livewire/settings/profile.blade.php`
for a single top-level wrapper.

## Latent issues surfaced by the harness (NEW, out of scope to fix)

### `/fund-codes` 500 when any fund code has `contract_id`

- File: `resources/views/livewire/fund-code-management.blade.php:92`
- Broken reference: `route('contracts.show', $fundCode->contract_id)`
- Actual route name: `contract.show` (singular, `routes/web.php:64`).
- Production impact today: none, because all 7 production fund codes have
  `contract_id = NULL`. The broken branch never fires.
- Test impact: the regression seeder creates 2 fund codes linked to
  Contract A, which makes the `/fund-codes` GET return 500 in the test DB.
  The smoke test for that route is `->skip()`'d with a clear breadcrumb.
- Fix cost: one character — `s/contracts.show/contract.show/` in the view.
  Belongs in a standalone PR, tagged as a bug fix.

## Known-entangled areas — Phase 1 should tread carefully

### `CommunicationsLogManagement::convertToProject` (925 LOC function)

- Wraps everything in `DB::beginTransaction()`. Failures along the way
  branch into four participant-handling paths (already-linked, select-existing,
  create-new, no-name) and four property-handling paths. Test covers the
  happy-path (participant-already-linked) only.
- Phase 1 refactors here should be broken into small PRs with each path
  covered by its own dedicated test before landing changes.

### `ProjectBudget` / `ProjectExpense` observer cascade

- `ProjectExpense` has a booted observer that updates
  `ProjectBudget.spent_amount` on any create/update/delete (see
  `app/Models/ProjectExpense.php`). Changing expense categories or moving
  expenses between jobs may leak running-total drift into budgets. The
  regression harness covers the straightforward add-expense case; bulk
  operations and category-rename are uncovered.

### `CommunicationsLog` pivot side-effects

- `syncTags`, `fundCodes()->sync()`, `services()->sync()` all fire on every
  save (`CommunicationsLogManagement::save`). Any Phase 1 change that
  touches the save path should confirm the sync calls remain idempotent.

### Auto-increment ID leakage into the view

- MySQL's auto-increment counter does not reset between test transactions.
  HTML snapshots are defensively normalized to replace DB IDs with
  `__ID__`. If Phase 1 adds new URL patterns like `/milestone-task/{id}`,
  the normalizer in `tests/Support/SnapshotHelper.php` needs a matching
  entry or snapshot determinism will break.

### Tinyint vs boolean casting

- `HousingJob.is_template` is `tinyint(1)` on the DB and is NOT cast on the
  model today. Tests wrap the read in `(bool)` — works whether or not
  Phase 1 adds an eloquent cast. If you rely on strict `true`/`false` in
  new code, add the cast deliberately rather than accidentally.

## CLAUDE.md items needing David's input

None as of this writing. The refresh commit in this branch updates only
the factually-stale claims about the Housing module, Pest 3.8 usage, and
`HousingModuleSeeder` wiring. Items in the original stub that could not
be verified from code alone were left untouched.
