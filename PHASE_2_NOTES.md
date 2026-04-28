# Phase 2 Notes — Fund Code System

Branch: `phase-2/fund-code-system` cut from `main` after Phase 1
(housing quick wins, PR #9) merged.

## Discovery summary

- Stack unchanged from Phase 1: **Laravel 12, Livewire 3, TailwindCSS v4,
  MySQL, Pest 3.8**. No Volt for the housing module, no Flux components in
  production. Same patterns apply.
- Regression suite was green at the start of this branch
  (`74 passed / 1 skipped / 0 failed` in 77.23s on `main`@8f3333ca). The
  one skipped test is the documented `/fund-codes` 500 from `PHASE_0_NOTES.md`
  — un-skipped here as part of P-05 hardening.
- `App\Models\FundCode` was already structured as a standalone reference
  list independent of `Contract` (the `contract_id` FK is nullable and
  optional in both schema and form). P-05 in this phase is hardening, not
  decoupling.
- `App\Livewire\FundCodeManagement` had complete CRUD already, plus an
  `is_active` toggle, search, and a delete-guard for in-use codes. The
  one bug from PHASE_0 lived at `resources/views/livewire/fund-code-management.blade.php:92`
  — `route('contracts.show', …)` instead of the registered `contract.show`
  (singular). Production is protected today by all 7 production fund codes
  having `contract_id=null`. Fixed in commit 7 (P-05).
- `App\Models\HousingJob::getDerivedFundCodesAttribute()` (Phase 1, P-22)
  unioned `fundCodes` across `communicationLogs` and
  `originatingCommunicationLogs` to give projects derived fund codes.
  Three reader sites and two eager-load sites; deleted in commit 6 once
  the pivot replaces it.
- `App\Models\CommunicationLog` already had `fundCodes(): belongsToMany`
  via a `communication_log_fund_code` pivot. P-27's "structured field"
  already exists — the migration in commit 11 just sweeps the small
  population of free-form tags into it.
- `App\Traits\HasTags` provides polymorphic morph-to-many tags on many
  models. Tags are free-form; matching to FundCode.code is case-insensitive
  string comparison.
- No `fund_code_housing_job` (or `housing_job_fund_code`) pivot exists on
  `main` — confirmed by listing `database/migrations/*fund_code*` and
  `*housing_job*`. Clean schema add.

### P-24 audit — fiscal coupling check

Strict word-boundary grep for `Sage|Intacct|DonorPerfect|MIP` across
`ProjectBudget`, `ProjectExpense`, `HousingJob`, `ProjectManagement`,
`ProjectsManagement`, `CommunicationLog`, `FundCode`,
`CommunicationsLogManagement`, `FundCodeManagement`, plus the two project
view files: **zero substantive matches**. Every hit was a false positive
on the substring "sage" inside `usage`, `getMessage()`, or `{{ $message }}`.

The Housing budget module is, by design, a standalone tracker. P-24 is
documentation + tripwire, not a remediation. Commit 2 makes that explicit
in code (docblocks), in CLAUDE.md (design intent section), and in the
regression harness (`BudgetIsStandaloneTest`).

### P-08 backfill projection (queried on dev DB)

| Class of job | Count | Notes |
| --- | --- | --- |
| Active (non-template) housing jobs | 16 | denominator |
| Jobs with 0 derived fund codes today | 4 | inventory in commit 5 |
| Jobs with exactly 1 derived fund code today | 12 | becomes pivot row + primary trivially |
| Jobs with 2+ derived fund codes today | 0 | oldest-comm-log primary rule degenerate but documented |
| Total `(housing_job_id, fund_code_id)` rows the pivot will hold post-backfill | 12 | one per non-zero job |

### P-27 prevalence count (queried on dev DB)

| Metric | Count |
| --- | --- |
| Comm logs total | 74 |
| Fund codes total | 7 |
| `tags` table rows total | 2 |
| Comm logs with any tag at all | 4 |
| Comm logs with structured fund codes via `communication_log_fund_code` pivot | 20 |
| Tag rows whose name matches an existing `FundCode.code` (case-insensitive) | 2 of 2 |
| Distinct comm logs with a matched-fund-code tag | 4 |
| Suspicious fund-code-shaped tags that don't match a real FundCode | 0 |

The structured pivot is already 5x more common than the tag form. P-27's
migration is essentially defensive scaffolding that sweeps a tiny tail
(≤4 attachments) into the structured form and stays in place against
future ad-hoc tag re-introduction.

## Decisions

| ID  | Decision | One-line rationale |
| --- | --- | --- |
| P-06 | Requirements live in a separate `fund_code_requirements` table, not as JSON on FundCode. `cascadeOnDelete` on the FK; no uniqueness constraint on `sort_order` (ties break by id). | P-07's panel needs reactive add/remove/reorder + per-flag queries; JSON forces full-array rewrite on every reorder and resists indexing. |
| P-08 | Pivot named `fund_code_housing_job` (alphabetical, matches `communication_log_fund_code` precedent in this repo). Composite primary key on `(fund_code_id, housing_job_id)`. | Laravel convention is alphabetical and existing pivot in the repo follows it; staying consistent. |
| P-08 | Primary fund code lives as `primary_fund_code_id` FK on `housing_jobs`, not as `is_primary` on the pivot. **Superseded by Phase 2.7 / P-38** — primary fund code concept removed; see PHASE_2_7_NOTES.md. | MySQL has no partial unique index, so an `is_primary` boolean would only be enforceable at the app layer; the FK on the parent table makes "zero-or-one primary per job" structural. |
| P-08 | App-layer guard on `HousingJob` saving event: throws if `primary_fund_code_id` is set but `(housing_job_id, primary_fund_code_id)` is not in the pivot. Helper `setPrimaryFundCode(FundCode)` does attach-then-set. **Superseded by Phase 2.7 / P-38** — guard, helper, and the FK column itself all removed. | Cheap invariant prevents drift if pivot is mutated independently. Detach-side enforcement is in the Livewire layer (commented in HousingJob.php so console/tinker users see it). |
| P-08 | Backfill primary rule: oldest associated Comm Log's first attached fund code wins. **Superseded by Phase 2.7 / P-38** — backfill logic remains in the historical migration but writes nothing once the column drops. | Empirically degenerate at this snapshot, but the rule still has to exist; chronologically first record is the most defensible answer if questioned later. |
| P-08 | `getDerivedFundCodesAttribute()` is **deleted** after backfill + call-site swap. No "kept as suggestion feed" or "kept as sanity-check." | Single source of truth from day one; blast radius is 3 source edits + 2 eager-load cleanups + 2 test refreshes — small enough for a clean break. |
| P-08 | Backfill migration `down()` truncates the new pivot and nulls `primary_fund_code_id` across all jobs. **Destructive of Phase 2 data only — documented in the migration docblock.** **Superseded by Phase 2.7 / P-38** — primary FK no longer exists, so the down()'s nulling step is now a no-op against a missing column; the pivot truncation step still applies. | At the time `down()` runs, the migration is the only writer of pivot rows and primary FK. "Re-derive and remove matching rows" is brittle if any operator activity sat between up and down. |
| P-22 | Phase 1's no-contract budget fallback rendering switches from comm-log-derived codes to the new project pivot. Same intent (no-contract row falls back to the project's fund codes), different source. | Single source of truth shift — must ship in the same phase as P-08, not later. |
| P-24 | Housing budget module is a standalone tracker by design. NOT integrated with Sage/Intacct/MIP/DonorPerfect/any external fiscal connector. Documentation (docblocks + CLAUDE.md section) + grep-based tripwire test enforce this. | Two-years-from-now signal so future contributors don't "helpfully" wire Sage in thinking they're filling in missing functionality. |
| P-24 | Tripwire grep scopes to `use` statements + FQN class refs only (e.g., `use\s+[^;]*\b(Sage\|Intacct\|Mip\|DonorPerfect)\b` and `\\(Sage\|Intacct\|Mip\|DonorPerfect)\\`). NOT free-text. | Otherwise comments like "// not connected to Sage" trip the tripwire and force docblock contortions to satisfy the test, defeating the point. |
| P-27 | Migration is idempotent (re-run is a no-op) and reversible. Leaves the original tags in place — coexistence until manager sign-off. Cleanup of legacy tag values filed as P-27b for a later phase. | User explicit: do not delete tag data this phase. |
| /fund-codes 500 | Phase 0–noted typo at `fund-code-management.blade.php:92` (`contracts.show` → `contract.show`) is rolled into the P-05 hardening commit and un-skips the SmokeRoutesTest assertion. | One-character fix; standalone PR would be over-ceremony. |

## Migrations introduced

Filled in as commits land. All reversible.

| File | Concept | Reversible | Notes |
| --- | --- | --- | --- |
| `2026_04_26_000001_create_fund_code_requirements_table.php` | P-06 | Yes | (commit 3) |
| `2026_04_26_000002_create_fund_code_housing_job_table.php` | P-08 schema | Yes | (commit 4) |
| `2026_04_26_000003_add_primary_fund_code_id_to_housing_jobs_table.php` | P-08 schema | Yes | (commit 4) — **dropped in Phase 2.7 / P-38** by `2026_04_27_120000_drop_primary_fund_code_id_from_housing_jobs_table.php` |
| `2026_04_26_000004_backfill_housing_job_fund_codes_from_communication_logs.php` | P-08 backfill | Yes (destructive of Phase 2 data only) | (commit 5) |
| `2026_04_26_000005_migrate_communication_log_fund_code_tags_to_structured_attachments.php` | P-27 | Yes (idempotent up + selective down) | (commit 11) |

## Zero-fund-code jobs inventory (P-08 backfill manual-review list)

Captured from the dev DB at backfill time. Each row is one of the 4
non-template housing jobs that had no derivable fund code at backfill —
either because the job has no associated comm logs, or because its
associated comm logs had no fund codes attached. The housing manager
will review and assign codes manually (or mark abandoned) in the
project assignment UI from commit 9.

All four are currently `pending` status, which is interesting — could be
mid-intake projects that hadn't yet been linked to comm logs, or
abandoned drafts. Either is informative for the manager.

| job_number | status | created_at | property |
| --- | --- | --- | --- |
| JOB-2025-00004 | pending | 2025-12-16 | 47 S. Franklin Street, Allentown |
| JOB-2025-00005 | pending | 2025-12-16 | 233 N. Jordan Street, Allentown |
| JOB-2026-00009 | pending | 2026-04-08 | 2105 Schoenersville Rd, Bethlehem |
| JOB-2026-00010 | pending | 2026-04-20 | 236.5 N 11th St., Allentown |

## P-27 backfill counts (dry-run against dev DB, read-only)

Captured by running the migration's logic without writing — same join,
same matching, same pre-existence check, just no INSERT. These are the
numbers David will see when he applies the migration on dev/prod.

| Metric | Count |
| --- | --- |
| Tag rows scanned | 4 |
| Distinct comm logs with a matched fund-code tag | 4 |
| Pivot attachments the migration WILL write | 2 |
| Pivot attachments skipped (already present) | 2 |
| Unmatched free-form tags | 0 |
| Suspicious fund-code-shaped tags (no FundCode match) | 0 |

Two of the four tag-bearing comm logs already have their fund code in
the structured pivot (manager added it both ways at some point); the
other two will pick up their attachment from this migration.

## Snapshot baselines — Phase 2 expectations vs reality

Walked through before commit 6, then re-checked after commits 6, 9, 10:

| Baseline | Expected at commit 6 | Actual at each commit |
| --- | --- | --- |
| `tests/Support/__snapshots__/projects_list.html` | NO diff | Commit 6: ZERO diff (✓). Commits 9/10: still ZERO diff — Phase 2's UI additions are on the project DETAIL view, not the list. |
| `tests/Support/__snapshots__/milestone_display.html` | NO diff | Commit 6: ZERO diff (✓). Commit 9: refreshed (+26 lines for the Fund Codes assignment section, additive only). Commit 10: refreshed (+2 lines for the panel-region marker comments). |
| `tests/Support/__snapshots__/budget_categories_with_contract.html` | NO diff | Commit 6: ZERO diff (✓). Commit 9: refreshed (+26 lines, same reason). Commit 10: refreshed (+2 lines, same reason). |
| `tests/Support/__snapshots__/project_detail_requirements_panel.html` | NEW baseline | Created in commit 10 with a deterministic mixed-requirements fixture (HJ-1 + FC-FIX-003 with hard+informational reqs; FC-PANEL-EXTRA with one hard req). |

The "should be zero diffs except the new baseline" prediction held for
commit 6 (the call-site swap, where same codes render in same DOM
positions, only the source changed). Diffs in commits 9/10 are net-
additive markup with no rearrangement of pre-existing structure;
refreshes were called out in each commit message.

## P-08 accessor disposition

`HousingJob::getDerivedFundCodesAttribute()` was **deleted** in commit
6 along with its 3 reader sites and 2 eager-load sites. The
`communicationLogs()` and `originatingCommunicationLogs()` relations
themselves remain (they're harmless and may be useful for any future
code that wants to walk back to the draft project outline). The
`fund_code_housing_job` pivot is now the single source of truth for
project↔fund-code linkage from this point forward.

## Pre-existing failures observed

Phase 0–documented `ParticipantImportTest x9`, `ServiceManagementTest x3`,
`Settings/ProfileUpdateTest x3` continue to be out of scope. CI on `main`
runs `--group=regression` for exactly this reason.

## Follow-ups & risks for Phase 3

- **P-27b** — clean up legacy tag values once the housing manager signs
  off on the structured form. Delete the now-redundant `tags` rows whose
  name matches an existing FundCode.code, after coexistence period proves
  reliable. Out of scope for Phase 2 by user direction.
- **Detach-side enforcement** — a console-level
  `$job->fundCodes()->detach($id)` that does not go through the Livewire
  `detachFundCode()` method can leave `primary_fund_code_id` dangling
  (the saving guard only catches the *attach* invariant). Comment in
  `HousingJob.php` notes this. If we ever expose pivot mutation outside
  Livewire (a console command, an admin API, etc.), promote the detach
  enforcement to the model. **Superseded by Phase 2.7 / P-38** — primary
  FK no longer exists, so this dangling concern is moot. The
  `detachFundCode()` helper still exists, but its only surviving
  responsibility is the P-2.5 budget-row guard.
- **Snapshot drift watch on commit 6** — the call-site swap should produce
  zero baseline diffs. If any of `projects_list.html` /
  `budget_categories_with_contract.html` / `milestone_display.html` diff,
  that's a real change to investigate, not a refresh-and-move-on.

## Future enhancements (parked — do NOT widen Phase 2 schema)

These are explicit "ideas surfaced and deliberately deferred." If a future
session is tempted to expand schema mid-phase, this list is the rejection
record.

- **Milestone-linked requirements** — add a
  `triggers_before_milestone_id` FK on `fund_code_requirements` so a
  requirement can be tied to a specific milestone (e.g., "must be done
  before construction"). For Phase 2, that detail belongs in the
  description text, not as a structural FK.
- **Per-pivot allocated amount** — add an `allocated_amount` column on
  `fund_code_housing_job` so each (job, code) attachment can carry its
  own dollar slice. Today's source of truth for allocations is
  `project_budgets` and we are not duplicating it.
- **Primary-fund-code propagation** — auto-default
  `ProjectBudget.contract_id` (or a future `primary_fund_code_id` on
  `ProjectBudget`) from `HousingJob.primary_fund_code_id`. Touches
  Phase 1 surface area; defer until the housing manager has worked with
  the new flow long enough to confirm it'd be a net win. **Superseded
  by Phase 2.7 / P-38** — primary fund code no longer exists; this
  propagation idea is now obsolete. If a fund-code defaulting behavior
  is ever wanted on the budget form, build it from the project's
  attached fund codes (the pivot), not from a primary designation.

## Manual test checklist (for staging)

Filled in as the UI lands across commits 7–10. Each row covers a
punch-list item the manager will hand-walk.

- [ ] (P-05) Visit `/fund-codes`. Confirm: 200 status, list renders, an
      existing fund code with `contract_id` set links to the contract
      detail page without 500ing.
- [ ] (P-05) Create a new fund code with `contract_id` deliberately left
      blank. Confirm save succeeds and the row renders with "No linked
      contract" placeholder.
- [ ] (P-06) Open a fund code's edit / requirements view, add three
      requirements (one hard, two informational), set sort_order. Confirm
      they persist and render in the chosen order.
- [ ] (P-06) Delete a fund code that has requirements. Confirm requirements
      cascade-delete with it (no orphans).
- [ ] (P-07) Open `/project/{id}/manage`. Confirm the requirements panel
      lists every requirement from every assigned fund code, grouped by
      code, with hard items visually distinct.
- [ ] (P-07) On the same page, attach a new fund code with requirements.
      Confirm the panel updates reactively without a page refresh.
- [ ] (P-08) ~~Attach two fund codes to a project. Mark one as primary.
      Confirm the primary indicator renders. Switch primary; confirm
      indicator moves.~~ **Superseded by Phase 2.7 / P-38** — no
      primary designation; all attached codes are equal.
- [ ] (P-08) ~~Detach the primary fund code. Confirm `primary_fund_code_id`
      auto-clears.~~ **Superseded by Phase 2.7 / P-38** — column gone.
- [ ] (P-22) Create a budget category on a project with no contract but
      with assigned fund codes. Confirm the row renders the project's
      assigned codes (from the new pivot) — same UI shape as Phase 1, new
      source.
- [ ] (P-27) Visit `/communications-log`. Confirm logs that previously
      had a `F0531` or `F4205` tag now also show those codes via the
      structured fund-code field. Original tags remain.
