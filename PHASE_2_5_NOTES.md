# Phase 2.5 Notes — Per-row Fund Code on Project Budgets

Branch: `phase-2.5/budget-fund-code` cut from `main` after Phase 2
(PR #10) and the subsequent housing hotfixes (PRs #11 and #12) merged.

## Discovery summary

Phase 2 wired fund codes to the project (the `fund_code_housing_job`
pivot + `primary_fund_code_id` on `housing_jobs` — **the latter
dropped in Phase 2.7 / P-38**), but the budget category form was
left with the legacy Contract dropdown. The
`// TODO(Phase 2: P-05)` marker for per-row `fund_code_id` on
`project_budgets` was acknowledged in Phase 2 Discover but the schema
and form change never landed.

This phase closes that gap.

- Regression suite green at start: **116 / 0 / 0**.
- `project_budgets` schema today: id, housing_job_id (FK cascade),
  contract_id (nullable FK, nullOnDelete), category, budgeted_amount,
  spent_amount, created_by, updated_by, timestamps. UNIQUE
  (housing_job_id, category). No fund_code_id column today.
- Budget create form lives at `resources/views/livewire/project-management.blade.php`
  starting at L898 (the `@if($showBudgetForm)` modal). Contract picker
  is an Alpine.js combo-box at L919–993, bound via `@entangle('budget_contract_id')`.
- Budget row's Fund Code cell at L583–614 had a 4-step fallback chain:
  contract grant_code → contract title (truncated) → project-wide
  derived fund codes (Phase 2 fallback) → em-dash.
- `saveBudget()` at `app/Livewire/ProjectManagement.php:873` validates
  `budget_contract_id` and persists `contract_id`. No edit method
  existed today — `editingBudget` was declared but never loaded by
  any code path. The Actions column had only Delete.
- Detach call site is `ProjectManagement::detachFundCode()` (L1179),
  which in Phase 2 simply called `$this->job->fundCodes()->detach($id)`
  inline.

## The four firm decisions (set by David before any code was written)

| # | Decision | Rationale |
| --- | --- | --- |
| 1 | **Backfill: existing budget rows stay NULL.** Operator sets the fund code per row on next edit. No automatic inference from contract or from project primary. | Operator's call; the system shouldn't guess. |
| 2 | **Required on new rows.** Form validation requires `fund_code_id` when creating or editing. NULL is permitted only for legacy rows that have not been edited since this phase landed. | The whole point of the change. |
| 3 | **Hide the dropdown — UI-only.** `project_budgets.contract_id` and the `belongsTo(Contract)` relation stay in the schema. Contract input gone from the form; contract display preserved on existing rows (the soft path — David's literal request). | "the contract drop down should be hidden for now" — David. |
| 4 | **Detach guard.** If any budget row references a fund code, block detaching that code from the project. Operator gets an explicit error listing the affected categories. Implement on `HousingJob` (the detach path Livewire calls). | Cheap invariant; avoids orphaning `project_budgets.fund_code_id`. |

## Migrations introduced

| File | Concept | Reversible | Notes |
| --- | --- | --- | --- |
| `2026_04_26_000006_add_fund_code_id_to_project_budgets_table.php` | P-2.5 schema | Yes (`down()` drops the constrained foreign id) | Nullable FK on `project_budgets`, nullOnDelete. Existing rows stay NULL on purpose. |

## What shipped (commit-by-commit)

| Commit | Punch list | Summary |
| --- | --- | --- |
| 1 | P-2.5 | `add_fund_code_id_to_project_budgets` migration. Nullable FK, nullOnDelete. No backfill. |
| 2 | P-2.5 | `ProjectBudget::fundCode()` belongsTo + saving-event guard. Throws if `fund_code_id` is set to a code not in the project's `fund_code_housing_job` pivot. ~~Mirrors Phase 2's `primary_fund_code_id` guard on `HousingJob`.~~ **Phase 2.7 / P-38: that mirror reference is gone — Phase 2's primary guard was removed; the budget-row guard described here is now standalone.** |
| 3 | P-2.5 | `HousingJob::detachFundCode()` helper with budget-row guard. Pre-checks `ProjectBudget` rows; throws `\InvalidArgumentException` listing affected category names if any reference the code being detached. Otherwise detaches and clears `primary_fund_code_id` if it matched. The Livewire layer routes through the helper and surfaces the error via `session()->flash('error', ...)`. **Phase 2.7 / P-38: primary clear branch removed.** The helper still exists and the budget-row guard remains; it just no longer touches primary state. |
| 4 | P-2.5 | Form + row display + edit flow. Contract picker (Alpine combo-box) replaced with a simple Fund Code `<select>` populated from `$job->fundCodes`. New `editBudget($id)` method + Edit action button per row + form modal title and save button text toggle by `$editingBudget`. Row cell collapses to the soft 3-step fallback (fund_code → contract grant_code → contract title → em-dash). Phase 2's project-wide fallback removed. Phase 1's three contract-form-specific tests deleted (`Phase1OptionalContractUiTest`, `CreateCategoryWithContractTest`, `CreateCategoryWithoutContractTest` — they assert on a UI surface that no longer exists). The eager-load probe in `Phase1FundCodeProminenceTest` rewritten to point at the new `fundCode` relation. |
| 5 | P-2.5 | `BudgetFundCodeAssignmentTest` (10 tests covering required-on-new, must-be-in-pivot, edit flow, detach-block, primary-clear-on-detach). `Phase1FundCodeProminenceTest` "falls back" rewrite — the old test was passing for the wrong reason (codes appeared in the Fund Codes assignment section above the tabs, not in the budget row), now carves to `<tbody>` to exercise the actual row cell. |
| 6 | P-2.5 | This notes file + REGRESSION_HARNESS.md update. |

## Phase 1 contract-form tests cleanup

The brief's contract-dropdown hide left several Phase 1 tests asserting
on a UI surface that no longer exists. Soft path: delete tests that are
purely about the now-hidden picker; preserve any test that exercises a
still-meaningful invariant.

| Phase 1 test | Disposition | Reason |
| --- | --- | --- |
| `Phase1OptionalContractUiTest::labels selector Optional` | DELETED | Asserts the Contract picker label "— Optional" is visible. Picker is hidden. Test obsolete. |
| `Phase1OptionalContractUiTest::clearing the contract on save persists NULL` | DELETED | Asserts the form's "clear contract" path persists NULL. The form has no contract picker; new rows always get NULL. Test obsolete. |
| `CreateCategoryWithContractTest::creates a budget category with a contract attached` | DELETED | Exercises the form's contract-attach path. No UI for that anymore. |
| `CreateCategoryWithoutContractTest::a category with no contract is created successfully (P-21)` | DELETED | Exercises the form's empty-contract submit. The form no longer surfaces contract; NULL is implicit on every new row. The new `BudgetFundCodeAssignmentTest::saveBudget creates a budget row with the assigned fund code` covers the same shape (asserts contract_id is null on new rows). |
| `Phase1FundCodeProminenceTest::eager-loads budgets.contract` | REWRITTEN → `eager-loads budgets.fundCode` | Same intent (eager-load + immediate render without page refresh), updated to point at the new relation. |
| `Phase1FundCodeProminenceTest::falls back to project-assigned fund codes` | REWRITTEN → `row shows its own fund code via the new per-row column` | The old assertion was passing for the wrong reason — code strings appeared in the project-level Fund Codes section, not in the budget row. The rewrite carves the assertion to `<tbody>` so it actually exercises the row cell. |

The functional invariant the deleted tests were guarding (contract is
permitted to be NULL on a budget row) is preserved at the schema /
model level: `project_budgets.contract_id` is still nullable, and the
new `BudgetFundCodeAssignmentTest::saving-event guard accepts NULL
fund_code_id` test exercises the legacy-row path.

## Snapshot baselines — Phase 2.5 expectations vs reality

| Baseline | Predicted | Actual |
| --- | --- | --- |
| `tests/Support/__snapshots__/budget_categories_with_contract.html` | Will diff (form + row cell changed) | NO diff. The baseline captures HJ-4's view with `activeTab='milestones'` (the default). The form modal isn't rendered (`$showBudgetForm` is false), and the budget tab body isn't rendered. The form swap and the Edit-button addition don't appear in this snapshot. |
| `tests/Support/__snapshots__/projects_list.html` | NO diff | Confirmed unchanged — Phase 2.5 doesn't touch the projects list view. |
| `tests/Support/__snapshots__/milestone_display.html` | NO diff | Confirmed unchanged — Phase 2.5 doesn't touch the milestone display. |
| `tests/Support/__snapshots__/project_detail_requirements_panel.html` | NO diff | Confirmed unchanged — Phase 2.5 doesn't touch the requirements panel. |

The brief anticipated a snapshot refresh in commit 5 that turned out
not to be needed. If a future contributor extends `CategoryListBaselineTest`
to set `activeTab='budget'` before snapshotting, that's a one-line
change that would expose the Phase 2.5 row + Edit-button changes — at
which point a single intentional refresh covers them.

## Future enhancements (parked — do NOT widen Phase 2.5 schema)

These ideas surfaced and were deliberately deferred. If a future
session is tempted to expand schema mid-phase, this list is the
rejection record.

- **Auto-default fund_code_id from primary on create.** Pre-populate
  `$budget_fund_code_id` with `$job->primary_fund_code_id` when the
  Add Budget Category modal opens, so the manager confirms-or-changes
  rather than picks-from-blank. Decision 1 explicitly rejected this:
  the operator picks the code; the system doesn't guess. **Superseded
  by Phase 2.7 / P-38** — primary fund code no longer exists, so this
  parked enhancement is moot.
- **Migration of existing rows to fund_code_id.** A backfill that
  reads each legacy budget's contract → looks up a corresponding
  fund code → writes to fund_code_id. Decision 1 explicitly rejected
  this: legacy rows stay NULL until edited.
- **Per-pivot allocated amount on `fund_code_housing_job`.** Still
  parked from Phase 2.
- **Drop `project_budgets.contract_id`.** The column and
  `belongsTo(Contract)` are kept post-Phase-2.5 by design. Removing
  them would lose contract-bearing legacy rows' visual signal AND
  forfeit the "for now" semantics of David's hide-the-dropdown
  request.
- **Add an Edit button + edit flow for ProjectExpense rows.** The
  Phase 2.5 edit work was scoped to Budget categories. Expenses still
  follow the create-and-delete-only pattern.

## Manual test checklist (for staging)

- [ ] (decision 1, decision 2) Visit a project with no fund codes
      attached, click "+ Add Category". Confirm the form shows the
      empty-state amber box telling the manager to attach a fund code
      first, AND the Save button is disabled.
- [ ] (decision 2, decision 3) Visit a project with two attached
      fund codes. Click "+ Add Category". Confirm: no Contract picker,
      Fund Code dropdown is required (asterisk), submit fails until
      a code is picked. Save with code A. Confirm the row renders
      with the FC-A code badge in the Fund Code cell.
- [ ] (commit 4 edit flow) Click Edit on the row. Confirm the form
      pre-fills (category, amount, fund code). Switch to code B,
      bump the amount, Update. Confirm the row updates without a
      page refresh.
- [ ] (decision 4) Try to detach code A (still attached) from the
      project's Fund Codes section above the tabs while the row from
      the previous step is still using code B. Code A detaches
      cleanly (no row references it). Now try to detach code B.
      Confirm the detach is BLOCKED with a red error block listing
      the affected category name and the "Re-assign or delete those
      rows first" remediation hint.
- [ ] (legacy fallback) On a project with a legacy budget row that
      has contract_id set but fund_code_id NULL (HJ-4's seeded rows
      qualify if you migrate dev with the seeder), confirm the row's
      Fund Code cell still renders the contract grant_code or title
      via the level-2/3 fallback. Edit the row, pick a fund code,
      save. Confirm the cell now shows the fund code badge instead
      of the contract one (level-1 wins).
- [ ] (saving guard) From `php artisan tinker`, try
      `\App\Models\ProjectBudget::create(['housing_job_id' => N, 'fund_code_id' => M, 'category' => 'Test', 'budgeted_amount' => 1])`
      where M is NOT in N's `fund_code_housing_job` pivot. Confirm
      the saving-event guard throws `\InvalidArgumentException` with
      "not assigned to the project" in the message.

## Operational papercuts surfaced during the comms-log/projects-housing hotfix

(Hotfix branch: `hotfix/comms-log-fund-filter-projects-housing-scope`,
merged into `main` on top of Phase 2.5's start.)

- **View-cache permission drift.** `storage/framework/views/`
  accumulates Apache-owned (`www-data:www-data`, mode 644) compiled
  blade files that block CLI test runs by other group members.
  Symptom: `php artisan test` from a non-`www-data` user returns
  dozens of `file_put_contents(...): Failed to open stream: Permission denied`
  ViewException failures even though the directory itself is 775
  group-writable. Workaround for now: `php artisan view:clear`
  before each CLI run. Long-term fix is `chmod g+s` on the views
  directory plus a consistent `umask 002` on the Apache process
  so newly-compiled views inherit group-write. ~15-30 min standalone
  infrastructure session — could be batched with the
  `node_modules/`/`vendor/` git-tracking cleanup that's also pending.

- **`RegressionTestSeeder` "Fixture Housing Program" now carries
  `housing_module_enabled = true`.** Added during the hotfix so the
  new housing-only filter on `/projects` continues to surface fixture
  jobs. The fixture's previous (absent / false) state did not match
  prod reality, where the one housing program does have the flag set.
  All 133 existing regression tests pass against the updated fixture,
  so empirically nothing depends on the old state. **For future
  tests that need a non-housing fixture program**, add a separate
  `Program::factory()->create(['housing_module_enabled' => false, ...])`
  inline in the test (see `tests/Feature/Regression/Projects/HousingScopeTest.php`'s
  `beforeEach` for the pattern) rather than leaning on the old
  fixture default.
