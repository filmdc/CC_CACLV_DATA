# Phase 1 Notes — Housing Quick Wins

Branch: `phase-1/housing-quick-wins` cut from `main` after Phase 0 (P-33,
PR #8) landed.

## Discovery summary

- Stack: **Laravel 12, Livewire 3, TailwindCSS v4, MySQL, Pest 3.8**.
  Page-level Livewire components (no Volt for the housing module),
  no Flux components in production (despite Flux being installed —
  CLAUDE.md confirms and `init`-style discovery on the views verifies it).
- Tests: `RefreshDatabase` is global on `tests/Feature` via `tests/Pest.php`.
  Phase 0 added the regression harness in `tests/Feature/Regression/**`,
  runnable via `./bin/run-regression.sh` (`--group=regression`).
- Existing duplicate-detection logic on the convertToProject flow at
  `CommunicationsLogManagement::checkForDuplicates()` had inline
  property-matching using `LOWER()` + `LIKE`. There was no shared
  AddressNormalizer.
- `HousingJob` had no relation to `CommunicationLog`. The two columns
  on comm logs (`housing_job_id`, `converted_to_job_id`) were not
  exposed via a model relationship until this branch.
- `ProjectBudget` already had nullable `contract_id` (added 2025-12-06).
  The "make contract optional" punch-list item was a UI clarity fix
  rather than a schema change.
- `ProjectMilestone` had no `started_at` column or any explicit timestamp
  for "moment a milestone became current." `getCurrentMilestone()` was
  derived at runtime from status. Added column + `markAsInProgress()`
  helper that stamps once and preserves on re-transition.

## Decisions

| ID  | Decision | One-line rationale |
| --- | --- | --- |
| P-01 | Two-pass match: normalized exact, then street-number + first-non-direction-word in same city. Warning, not block. | Mirrors existing `checkForDuplicates` shape; covers the 95% case (suffix variants, casing, punctuation, light typos) without LIKE-bombing the DB. |
| P-01 | Single `App\Support\AddressNormalizer` used by both the form-time check and the convertToProject duplicate scan. | User explicitly required: do not ship two divergent normalizers. |
| P-02 | LIKE-based extension to the existing `scopeSearch` — `orWhereHas('fundCodes')`. No FULLTEXT, no Scout. | Data volume is small; existing chain was already wrapped in a closure so the new branch composes safely. Easy to swap later. |
| P-09–P-12 | Project list reorganized in a single Blade edit (one commit) per the user's "all touch the same view" guidance. | Minimizes review surface for related changes. |
| P-11 | Fund codes derived from CommunicationLog rows attached to the project (via `housing_job_id` or `converted_to_job_id`). No new schema. | Per Q1 confirmation: surface what already exists, do not pre-build Phase 2 (P-05) schema. UI label deliberately drops "(from comm logs)" suffix — these are the project's fund codes once the project exists. |
| P-19 | New `started_at` column on `project_milestones`. NULL for existing rows; never backfilled. Stamp once and preserve on re-transition. | User explicit: fabricated timestamps lie to operators. UI shows "Started: —" until next transition. |
| P-21 | UI clarity fix only — schema and validation were already correct. Updated the misleading PHASE_0 comment in `CreateCategoryWithoutContractTest`. | Phase 0 author predicted P-21 would make contract mandatory; the actual punch list specifies the opposite. Correcting the test's narrative. |
| P-22 | Eager-load `budgets.contract` in `refreshJob()` (root cause of the "only reappears after re-selection" symptom). Promote the badge from text-xs/font-medium to text-sm/font-semibold/rounded-md. | Single root-cause fix, plus prominence per the punch-list outcome. |
| P-22 | When a budget has no contract, fall back to derived comm-log fund codes. No "(from comm log)" provenance suffix. | Per user: codes are the project's fund codes, full stop. Provenance label leaks implementation detail. |
| Branching | Cut Phase 1 from `main` *after* PR #8 merged, not from PR #8's branch. CI fixes for the harness landed in PR #8 (MySQL service, lint-on-PR-diff, snapshot portability, `--group=regression` scope). | Keeps Phase 1 free of harness-infrastructure concerns and gives Phase 1 a clean baseline to build on. |

## Migrations introduced

| File | Reversible | Notes |
| --- | --- | --- |
| `database/migrations/2026_04_25_000001_add_started_at_to_project_milestones.php` | Yes (`down()` drops the column) | Nullable timestamp. No data backfill on purpose. Verified clean with `php artisan migrate --pretend`. |

## Pre-existing failures observed during this branch

The Phase 0-documented "ParticipantImportTest x9, ServiceManagementTest x3,
Settings/ProfileUpdateTest x3" failures persist and are explicitly out of
scope for Phase 1 (the regression harness is the gate, not the full suite).
CI on `main` runs `--group=regression` for exactly this reason.

## Follow-ups & risks for Phase 2

- **P-05 (Phase 2 fund-code work)**: replace the derived-from-comm-logs
  fallback in two places with a managed `project↔fund_code` link
  supporting a primary designation. Markers in:
  - `resources/views/livewire/projects-management.blade.php` (Fund Codes column)
  - `resources/views/livewire/project-management.blade.php` (budget row fallback)
- **PropertyFactory flake fixed in this branch**: `year_built` was rolling
  randomly between 1900 and 2020, but MySQL `YEAR(4)` rejects 1900 (range
  is 1901–2155). FactorySanityTest hit this roughly once per 100 runs.
  Patched the factory bound to `1901` here; not a Phase 1 feature, included
  to keep CI green on every Phase 1 PR.
- **Permission consolidation runs on every test setup**: each test seeds
  takes ~58s for the first run because the Phase 0 seeder runs
  permission-consolidation logic via DB-mutating commands. Consider
  whether that consolidation can move to a one-shot setup in `Pest.php`.
- **ProjectManagement::refreshJob() now eager-loads more relations**:
  `communicationLogs.fundCodes`, `originatingCommunicationLogs.fundCodes`,
  and `budgets.contract`. If this view ever serves a job with hundreds of
  attached comm logs the cost could spike. Not an issue at current data
  volume.
- **Auto-increment ID leakage on the projects list snapshot**: the new
  derived-fund-codes accessor pulls IDs into the rendered HTML's URLs.
  `SnapshotHelper::normalize()` already replaces `/property/N`,
  `/participant/N`, etc., but watch the snapshot diff if you add new
  resource URL patterns.

## Manual test checklist (for staging)

- [ ] Visit `/projects`. Confirm: no "Project #" column header, Property
      is leftmost identifier (large + bold), Fund Codes column shows
      project's derived codes, rows are visibly tighter (py-2).
- [ ] Open `/communications-log`, click "+ New Entry", type an address
      that matches an existing property (e.g., reuse a real one). Confirm
      the amber warning panel lists matching properties/logs and that
      "Save anyway" persists the entry.
- [ ] Edit an existing comm log with a known address. Confirm the
      record does not appear as its own duplicate.
- [ ] Search by a fund code (e.g., the code of any active fund) on
      `/communications-log`. Confirm logs that have that code attached
      show up.
- [ ] Open a project's `/project/{id}/manage` page, advance a milestone
      to in_progress. Confirm "Started: {today's date}" appears beneath
      the milestone heading. Toggle to not_started and back — the
      timestamp must not change.
- [ ] On the same page, switch to Budget tab, click "Add Budget Category",
      leave the contract field blank, save. Confirm the category is
      created and the row's Fund Code cell shows either the project's
      derived comm-log codes (if any) or "—".
- [ ] Add another budget *with* a contract that has a `grant_code`.
      Confirm the grant_code badge renders prominently in the row
      immediately on save (no refresh).
