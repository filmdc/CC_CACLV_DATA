# Phase 2.7 Notes — Punch-list-v2 Alignment (P-36, P-37, P-38)

Branch: `phase-2.7/punch-list-v2-alignment` cut from `main` after PR #15
(comms-log fund filter + projects housing scope hotfix) and the v2.20.7
version bump merged.

## Scope

Three punch-list items, all small, all alignment work for the refreshed
punch list (PUNCH_LIST.md, last updated 2026-04-27):

- **P-36** — remove the regular-vs-PM-enabled filter UI from the housing
  project list. Backend scope retention: not applicable (the dropdown was
  the only consumer; the underlying `whereHas('milestones')` is stock
  Eloquent and always available).
- **P-37** — restructure the project detail header into a three-line
  layout: address + head-of-household name (prominent), assigned fund
  codes (equal treatment, no primary callout), job number (small subtitle).
- **P-38** — drop the "primary fund code" concept system-wide. Schema,
  model, Livewire, blade, tests, seeder, snapshots, and historical
  Phase 2 / Phase 2.5 docs are all in scope.

## Discovery summary

- Regression suite green at start: **133 / 0 / 385 assertions** in 93s
  on `main`@`62b9fcf3`.
- P-36 audit found the toggle still live: `filterPmEnabled` Livewire
  property (`app/Livewire/ProjectsManagement.php:40`), two `when()`
  clauses in `render()` (lines 597–598), and the three-option dropdown
  in `resources/views/livewire/projects-management.blade.php:88–95`.
  The recent housing-scope hotfix closed program-flag scope but left
  this UI intact — P-36 finishes that work.
- P-38 call-site inventory (29 items across schema, model, Livewire,
  blade, tests, seeder, snapshots, and four docs files) captured in the
  Discover map. Nothing escaped grep.
- `HousingJob::detachFundCode()` shrinks but stays — the budget-row
  guard from Phase 2.5 is the surviving reason to route detach through
  the model helper. Docblock will be revised accordingly in commit 4.
- Production data check: the 12 prod jobs with `primary_fund_code_id`
  set lose those values when the column drops. No backfill, no sidecar
  table. Operator-visible impact is zero (all assigned codes already
  render via `fundCodes()` — the only change is that none is starred).

## Decisions

| ID | Decision | One-line rationale |
| --- | --- | --- |
| P-36 | Per-housing-program refinement dropdown (`filterProgram`) stays. Only the regular-vs-PM `filterPmEnabled` dropdown goes. | Manager-resolved decision. Program refinement is a useful filter; regular-vs-PM is the punch-list target. |
| P-36 | Drop the `filterPmEnabled` property + the two `when()` clauses + the dropdown in one commit. No "leave the controller method intact" — the property only existed to drive the dropdown. | The "preserve backend capability" line in PUNCH_LIST.md is honored by the fact that `whereHas('milestones')` / `whereDoesntHave('milestones')` are stock Eloquent and always available. To re-introduce: re-add as a Livewire property + `when()` clauses in `ProjectsManagement::render()`. |
| P-37 | Three stacked lines exactly as specified — no tabs, no collapsibles. | Manager-resolved decision. The header is small; keep it small. |
| P-38 | Schema rollback `down()` re-adds the column nullable; no data restore. The 12 prior values are gone by design. | Manager-resolved decision. No sidecar preservation table; if primary is ever wanted again, build it fresh against the new requirements. |
| P-38 | Backfill: none. `RegressionTestSeeder` drops the `setPrimaryFundCode()` marking only. HJ-1 retains both fund-code attachments via the pivot. | Manager-resolved decision. Seeder scope is exactly the primary marking, not the pivot membership. |
| P-38 | Code-first commit ordering (remove model/Livewire/blade surface in commit 4, drop column in commit 5). | The deprecation pattern is "stop referencing the thing, then drop the thing." Schema-first would put the saving guard against a missing column on every save — catastrophic suite state. Code-first scopes the red zone to tests that explicitly assert primary behavior, which are exactly the tests being deleted. |
| P-38 | `RegressionTestSeeder` line `$hj1->setPrimaryFundCode($fcStandalone);` removed in commit 4 alongside the model/Livewire/blade cleanup, NOT in the test-cleanup commit 6. | The seeder is part of the code surface. Leaving it in commit 6 means commit 4's red zone is global (every seeded test crashes). Moving it to commit 4 keeps the red zone precisely on the four primary-only tests deleted in commit 6. |
| P-38 | `HousingJob::detachFundCode()` shrinks but stays. The Livewire `ProjectManagement::detachFundCode()` continues routing through it for the P-2.5 budget-row invariant. | The method's reason for existing collapses to "budget-row guard"; docblock revised to match. |

## P-36 audit result (recorded for future reference)

Removed: three-way `filterPmEnabled` dropdown ("All Projects / PM Enabled
Only / Regular Jobs Only") on `/projects`. The "PM Enabled Only"
criterion was `whereHas('milestones')`; the "Regular Jobs Only" criterion
was `whereDoesntHave('milestones')`. To re-add: reintroduce as a Livewire
property + `when()` clauses in `ProjectsManagement::render()`.

This note is the load-bearing detail. Months from now "the filter we
removed" will be a vague memory; the criterion is what matters if anyone
wants the capability back.

## Migrations introduced

| File | Concept | Reversible | Notes |
| --- | --- | --- | --- |
| `2026_04_27_120000_drop_primary_fund_code_id_from_housing_jobs_table.php` | P-38 | Structural only — `down()` re-adds the column nullable, no data restore | Lands in commit 5 (after the model/Livewire/blade no longer reference the column). |

## Snapshot expectations

| File | Expected diff | Verified at commit |
| --- | --- | --- |
| `tests/Support/__snapshots__/projects_list.html` | Maybe — the `filterPmEnabled` dropdown sits inside the filter form region of the snapshot | Commit 2 (P-36) |
| `tests/Support/__snapshots__/milestone_display.html` | **Yes** (corrected post-commit-3) — both snapshots capture the project detail page in full, header included. Diff is exactly the P-37 header change | Commit 3 follow-up |
| `tests/Support/__snapshots__/budget_categories_with_contract.html` | **Yes** (same reason as above) | Commit 3 follow-up |
| `tests/Support/__snapshots__/project_detail_requirements_panel.html` | Definite — the `★ Primary` chip on line 11 disappears | Commit 6 (P-38 cleanup) |

For each refresh, the diff IS the visual review. Don't suppress
unexpected diffs to keep snapshots green — investigate first.

## Out of scope (explicit non-goals)

- Anything from Phase 3 (P-17 dashboard, P-18 milestone handoff,
  P-20 artifact notifications, P-03/P-04, P-23).
- Templates, program services, intake forms — Phases 4 and 5.
- The `fund_code_housing_job` pivot itself stays as built in Phase 2.
- Removing `housing_module_enabled` flag handling — the implicit scope
  from PR #15 stays.

## Snapshot expectations — actual results

| File | Predicted | Actual at refresh time |
| --- | --- | --- |
| `tests/Support/__snapshots__/projects_list.html` | Maybe | **Yes** — refreshed in commit 2. Diff: dropdown removed + grid `md:grid-cols-4` → `md:grid-cols-3`. |
| `tests/Support/__snapshots__/milestone_display.html` | None expected (corrected post-fact) | **Yes** — refreshed in commit 3 follow-up. Both snapshots capture the project detail page in full, header included, so the P-37 header restructure cascades into them. |
| `tests/Support/__snapshots__/budget_categories_with_contract.html` | None expected (corrected post-fact) | **Yes** — same reason as above; refreshed in commit 3 follow-up. |
| `tests/Support/__snapshots__/project_detail_requirements_panel.html` | Definite | **Yes** — refreshed in commit 6. Diff: ★ Primary chip in the FC-FIX-003 group removed. No other rearrangement. |

Original prediction was 1 of 4 snapshots affected; actual was 4 of 4
(the milestone/budget snapshots were misclassified as "header-free"
in the original Discover map). Per manager addition #3, each diff
was reviewed before refresh — none were unexpected drift.

## Suite arc across commits

| Commit | Suite state |
| --- | --- |
| 1 — docs scaffold | 133 / 0 (pre-existing baseline) |
| 2 — P-36 dropdown removal | 1 expected fail → refresh `projects_list.html` → 133 / 0 |
| 3 — P-37 header restructure | 2 expected fails → refresh `milestone_display` + `budget_categories_with_contract` → 133 / 0 |
| 4 — P-38 code surface removal | 11 expected fails (intentional red zone) |
| 5 — P-38 schema drop | 13 (red zone unchanged in shape; +2 QueryException on PrimaryGuardTest's column-direct tests) |
| 6 — P-38 test cleanup + snapshot | **121 / 0 / 356 assertions** (net -12 tests deleted, -29 assertions) |
| 7 — docs finalize | 121 / 0 (this commit) |

## Follow-ups & risks

- **Phase 2 historical migrations are intentionally untouched.**
  `2026_04_26_000003_add_primary_fund_code_id_to_housing_jobs_table.php`
  and `2026_04_26_000004_backfill_housing_job_fund_codes_from_communication_logs.php`
  remain in the migrations directory unchanged. They run forward on a
  rebuilt test DB (column added, then backfilled, then dropped by the
  Phase 2.7 migration). Editing the historical migrations to be no-ops
  was deliberately out of scope — the chain works, and rewriting
  history is its own risk class.
- **Production primary FK values are gone.** The 12 prod jobs that had
  `primary_fund_code_id` set lost those values when commit 5's
  migration runs on prod. This was a manager-resolved decision; no
  sidecar table preserves them. If a primary concept is ever wanted
  again, build it fresh.
- **`HousingJob::detachFundCode()` survives.** The Livewire
  `ProjectManagement::detachFundCode()` continues to route through it
  for the P-2.5 budget-row guard. If a future change wants to bypass
  the helper, the budget-row invariant still has to be enforced
  somewhere.
- **Re-adding the regular-vs-PM filter** (if ever wanted): see the
  P-36 audit result section above. The criterion was
  `whereHas('milestones')` for "PM Enabled Only" and
  `whereDoesntHave('milestones')` for "Regular Jobs Only".
- **Phase 3 unblocked.** P-36, P-37, P-38 all land cleanly with the
  suite green; nothing in the workflow/dashboard work for Phase 3
  needs to wait on this branch.
