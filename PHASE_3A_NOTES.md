# Phase 3A Notes — Requirements model + housing dashboard (P-17, P-18)

Branch: `phase-3a/requirements-and-dashboard` cut from `main` at
`efa482f3` (Phase 2.7 merge).

## Scope shipped

Two punch-list items plus a stakeholder-driven mid-implementation
addition (Step 7.5):

- **P-18** — unified per-project Requirement model with three sources
  (milestone, fund_code, ad_hoc), copy-on-instantiate snapshots,
  override-completion path for documents, lifecycle prompts on detach
  / milestone-delete / global fund-code-delete. Replaces the legacy
  `milestone_tasks` + `milestone_documents` write paths on the
  milestone tab; legacy tables stay populated for audit and are
  retired in a later phase.
- **P-17** — personal housing dashboard with two cuts (per-project /
  per-requirement), in-app notification bell in the global header,
  database-channel notification dispatch through Laravel's native
  Notifiable.
- **Mid-phase Step 7.5** — fund-code start/expiration dates, milestone
  due dates (consolidated onto the existing `target_date` column —
  see decision below), milestone-to-milestone dependency graph with
  cycle detection and a precomputed blocked-set strategy that keeps
  the dashboard render bounded.

## Discovery summary

- Regression suite green at start: **121 passing, 356 assertions** in
  ~90s on `main`@`efa482f3`.
- 16 non-template housing jobs on dev; 8 milestones; 3
  milestone_documents (all `not_uploaded`, none with `requires_approval`
  or `pdf_template_id`); 1 milestone_task (`pending`).
- 0 of 3 production milestone_documents had `pdf_template_id` set; 1
  stranded `pdf_form_generations` row with empty doc id; the PDF
  template path is dormant. Locked option (c) — drop the regenerate-
  PDF UI from the new milestone tab without retaining a shim.
- `User` already used `Notifiable`; the framework `notifications`
  table was absent. Existing 8 Notification classes use `'mail'` only.

## Decisions

### Five open calls confirmed at Plan stage

1. **Architecture (i) — unified `requirements` table.** A single table
   with `type` (document/task) + polymorphic `source_type`/`source_id`
   pointers. Existing `milestone_tasks`/`milestone_documents` tables
   stay populated for audit but are write-frozen on the milestone tab.
2. **`project_manager_id` is the canonical project owner.** Backfilled
   from `created_by` for all rows where it was null.
   `CommunicationsLogManagement::convertToProject` and
   `HousingJob::createFromTemplate` both populate it explicitly going
   forward (the latter overrides the template's stale value
   unconditionally).
3. **Default owner of fund-code-generated requirements = the project
   manager.** Not the user attaching the fund code.
4. **Ad-hoc requirement creation surface = project detail page only**
   in 3A. No dashboard quick-add; defer until usage shows demand.
5. **Notification scaffolding = Laravel's built-in database channel.**
   Scaffolded `notifications` table via `php artisan
   notifications:table`. Phase 3B adds `'mail'` to each via() in a
   one-line change per class.

### Phase 2 fund-code requirement schema gains a `type` column

Option (b) over (a): a `nullable type enum('document','task') default
'task'` lives on `fund_code_requirements`. The document-vs-task
distinction is intrinsic to a fund-code requirement template (a
contract obligation is a document for every project that attaches the
fund code), not a per-project flip. Cost of (a) was 39 manual flips
for one fund code on 13 projects; (b) is an additive nullable column.

### `target_date` is the canonical hard deadline; no separate `due_date`

David's call after weighing dev's proposal of two columns
(`target_date` = soft estimate, `due_date` = hard deadline). The
soft-vs-hard distinction was rejected as user-confusion surface — most
users don't distinguish in practice, and one date field is simpler.
**`project_milestones.target_date` is the column the
`deadlineIndicator()` helper reads** (overdue / approaching / null).
A future phase may rename the column if a soft-estimate field
re-emerges; that's a one-line rename, no migration churn.

### Milestone-delete keep-branch sets `source_id = NULL`

The conceptual model originally said "retain the reference." With the
milestone hard-deleted, the reference is unresolvable anyway —
`source_type='milestone'` with `source_id=NULL` is functionally
equivalent to retaining a stale id, with cleaner null-handling
semantics in the UI. Lineage info (`source_type='milestone'`,
`source_label_snapshot`) is preserved; the unresolvable id is dropped.
Documented as a refinement of the conceptual-model wording, not a
deviation from intent.

### PDF template integration removed from new milestone tab

`milestone_documents.pdf_template_id` is a Phase 6 feature that was
never adopted in production. Verified empirically: prod has 0
documents with `pdf_template_id` set, 1 stranded `pdf_form_generations`
row with no recent activity. The new milestone tab does NOT surface
the regenerate-PDF flow; legacy table + relation stay in place for
any audit pathway. If a future phase adopts PDF templates, the
`requirements` table can grow a nullable `pdf_template_id` column then.

### Lifecycle modal three-button shape

Cancel / Keep / Delete (or Cascade for the global fund-code delete).
Three-way choice maps to three buttons. Color coding: gray Cancel,
amber Keep (audit-preserving), red Delete (destructive). Confirmed in
Step 7 review.

### Cut B default sort: fund_code → milestone → ad_hoc

Within each group, alphabetical by fund code (or by title for
non-fund-code rows). The three-tier ordering is deterministic and
no-hierarchy-implying. There is a UX risk that fund-code-first
ordering could be read as implying hierarchy among sources; we're
deliberately not preempting that. **If Kaitlyn comments, the
alternative is flat alphabetical-by-title across all rows, or
alphabetical-by-source-name within source-grouped sections — both are
one-line switches in `HousingDashboard::render` Cut B branch.**

### Subscriber semantics for completion notifications: "project manager
only" in 3A

When a requirement closes, the project manager
(`housing_jobs.project_manager_id`) gets the in-app
`RequirementCompletedNotification` unless they were the completer.
That's the simplification. Phase 3B can refine to true subscriber
semantics if Kaitlyn requests it (e.g., "anyone watching this
project" via a watchers pivot).

## Backfill row counts (one-shot on dev; production numbers run
separately via the audit log file)

`database/seeders/phase_3a_backfill_ids.json` is committed for audit.
Each section logs the row IDs touched so `migrate:rollback` reverses
by ID — no temporary columns, no fragile timestamp matching.

| Section | Rows touched on dev |
|---|---|
| `housing_jobs.project_manager_id` set from `created_by` | 17 |
| `milestone_documents` → `requirements` | 3 (all outstanding; 0 approved, 0 rejected — no `[REJECTED:]` annotations or `[BACKFILL-3A]` sentinels stamped on dev) |
| `milestone_tasks` → `requirements` | 1 |
| Fund-code instantiation across `fund_code_housing_job` | 2 |

**Off-by-one on project-manager backfill (17 vs Discover's predicted
16):** the migration's predicate (`project_manager_id IS NULL AND
created_by IS NOT NULL`) didn't filter on `is_template`, so one
template row got its manager populated alongside the 16 real projects.
Per Step 3 review: leave as-is. Templates never surface manager UI; the
data noise is harmless. The audit log has the full ID set if a future
phase wants to scrub.

`createFromTemplate()` propagation: the `is_template=true` row may
carry a stale `project_manager_id` post-backfill, but Step 4 defends
both creation paths against that —
`CommunicationsLogManagement::convertToProject` and
`HousingJob::createFromTemplate` both populate `project_manager_id =
Auth::id()` on the new project unconditionally. The template's stale
value is therefore irrelevant by construction.

## Threshold values (hardcoded — config targets for a future phase)

| Indicator | Bucket | Threshold (days) |
|---|---|---|
| Fund expiration | urgent | ≤ 30 |
| Fund expiration | warning | 31–90 |
| Fund expiration | expired | past |
| Milestone deadline | overdue | past |
| Milestone deadline | approaching | ≤ 7 |

Different thresholds for fund vs milestone are intentional: milestone
deadlines run a tighter cadence than fund deadlines. If Kaitlyn's
real cadence differs, these are one-line changes in
`FundCode::expirationIndicator` and
`ProjectMilestone::deadlineIndicator`. A future phase may move them
into a settings table; not in 3A scope.

## Snapshot machinery

### New baselines

| File | What it captures | Why |
|---|---|---|
| `tests/Support/__snapshots__/housing_dashboard_cut_a.html` | Cut A render with the rich-indicator + quiet-project fixture (Project A: urgent fund 15d, overdue milestone, blocked milestone; Project B: no indicators, single ad-hoc) | Locks the visual structure and indicator-pill ordering for both the multi-indicator branch (Project A) and the empty-indicator branch (Project B) in one render |
| `tests/Support/__snapshots__/housing_dashboard_cut_b.html` | Cut B render with same fixture (3 reqs on Project A: fund/milestone/ad-hoc, 1 on Project B) | Captures the three-tier sort, the per-row indicator column, and the filter-bar HTML; locks the fund-code-first ordering decision until Kaitlyn surfaces a preference |

### Refreshed baselines (intentional)

| File | What changed | Why |
|---|---|---|
| `tests/Support/__snapshots__/milestone_display.html` | `showCreateTask` / `showCreateDocument` → `showCreateRequirement('task'/'document')` (3 occurrences each); section-comment updates referencing P-18 | Step 6 milestone-tab rewire: reads + writes now go through the requirements table |
| `tests/Support/__snapshots__/budget_categories_with_contract.html` | (Step 6 added 2 modal HTML comments; Step 8 cleanup removed them by moving inside the @if) | Net of Step 6 + Step 8: zero new modal-comment leakage, snapshot stable |

### `data-*-id` normalization rule

Added a single normalization pattern to `tests/Support/SnapshotHelper`:

```
/data-(project|requirement|milestone|task|document|fund-code)-id="\d+"/
  → data-$1-id="__ID__"
```

Phase 3A row tagging (`data-project-id`, `data-requirement-id`, etc.)
is a deliberate test affordance — selectable, accessible, stable
across re-runs. **Phase 3+ snapshots can rely on this rule**; new
data-id attributes following the same naming pattern stabilize
automatically.

## Architectural gotchas / future-phase tripwires

### Date-vs-dependency consistency NOT enforced

If milestone A is due before milestone B, and A depends on B, that's
logically inconsistent. Phase 3A neither enforces nor warns. Tracking
the contradiction cleanly would require its own UI surface and rule
layer; deferred. **Phase 4 candidate**: a dashboard pre-flight check
or milestone-form validation surface for this. If you find yourself
wanting to add this check inline, write a ticket for `PHASE_4_NOTES.md`
instead.

### Lifecycle interaction: milestone-delete keep-branch + dependency edges

The Step 7 milestone-delete keep branch sets `requirements.source_id =
NULL`. The Step 7.5 `milestone_dependencies` table cascades on
milestone deletion via FK. The two cleanups are independent: keeping
the requirements does not keep the dependency edges. Documented here
so a Phase 4 reviewer doesn't read the cascade as a bug.

### Backfill template-row noise

The `is_template=true` row got `project_manager_id` populated by the
backfill alongside the 16 real projects. Templates never surface the
manager UI, so this is data noise rather than a behavioural defect.
The audit log retains the exact ID set for any future scrub.

## Phase 4 / 5 follow-ups identified

1. **Date-vs-dependency consistency** — see above.
2. **PDF template re-adoption (if it ever happens)** — add nullable
   `pdf_template_id` to `requirements`, restore regenerate-PDF UI.
3. **Subscriber-semantics refinement** — true watchers via a pivot;
   currently project-manager-only.
4. **Soft-estimate column on milestones** — if a soft "we're aiming
   for" date re-emerges as a need, `target_date` gets renamed and a
   new `target_date` (or similar) lands. Not anticipated.
5. **Cut B sort UX feedback loop** — watch Kaitlyn's reaction to the
   fund-code-first ordering. If she comments, switch to flat
   alphabetical or alphabetical-by-source-name (one-line change).
6. **Threshold config table** — if Kaitlyn or another agency cohort
   needs different urgent/warning windows, lift the constants out of
   the model methods.
7. **Inline mark-complete on the dashboard** — Cut B currently links
   out to `project.management`. If Kaitlyn wants to close items
   without navigating, add a per-row mark-complete control.
8. **Cut B pagination** — bounded query test asserts ≤40 queries
   regardless of result-set size, but at >500 requirements per user,
   page weight gets noticeable. Add `paginate()` if usage demands.
9. **Notification email channel** — TODO(3B) markers on
   `RequirementAssignedNotification::via`,
   `RequirementCompletedNotification::via`, and the date-driven
   trigger marker on `App\Services\DashboardIndicatorSupport` are the
   integration points.
10. **Template-level milestone dependencies** — Phase 3A is project-
    level only. Phase 4 may want template-level edges that propagate
    on `createFromTemplate()`.

## Intentionally deferred to Phase 3B

- Email channel on `RequirementAssignedNotification` and
  `RequirementCompletedNotification` (one-line `via()` change per class).
- Date-driven scheduled-job dispatch for fund-expiration and
  milestone-overdue notifications.
- Subscriber-semantics refinement on completion notifications
  (currently project-manager-only).
- Inline mark-complete affordance on Cut B rows.
- Cut B pagination if requirement counts grow.

## Test counts

- Regression suite at start of phase: **121 passing, 356 assertions**.
- Regression suite on PR submission: **178 passing, 509 assertions**.
- Net new in regression group: **+57 tests, +153 assertions** across
  requirements model behavior, lifecycle modals, milestone-tab
  rewire, fund-code/milestone indicator boundaries, blocked-set
  computation, dashboard render + filters + indicators, query bounds,
  and snapshot baselines.
- Feature-suite (non-regression): **80+ passing** covering
  instantiation paths, copy-on-instantiate immutability, completion
  gating, reassignment, milestone-tab Livewire round-trips, dependency
  validator, and template-clone semantics.

## How to verify (manual walkthrough)

Per the original Phase 3A prompt's verification step:

1. Create a project, attach a fund code with two requirements (one
   hard, one informational from Phase 2). See requirement rows appear
   on the project, owned by default per the project-manager rule.
2. Add a milestone with a document requirement and a task
   requirement. Set the milestone's assignee to a test user. See
   requirements appear with that assignee as default owner.
3. Add an ad-hoc requirement to the project. Confirm it appears as
   `source_type='ad_hoc'`.
4. Mark a task requirement complete with a note. Audit fields populate.
5. Mark a document requirement complete without uploading a file. The
   completion modal requires either a file or an override reason.
   Provide an override reason. Audit fields populate including
   `override_reason` and `overridden_by_user_id`.
6. Reassign a requirement to a different user. The new user gets an
   in-app notification. The old user does not.
7. Complete a requirement on the project owner's project (as a
   different user). The project manager gets an in-app notification.
8. Visit `/dashboard/housing`. See the per-project view by default.
   Switch to per-requirement view. Filter by owner = a different user
   to see "assigned to others."
9. Lifecycle: detach a fund code with no budget references → confirm
   the prompt fires and both keep/delete branches behave correctly.
   Confirm the budget-row guard still blocks detach when references
   exist. Delete a milestone → confirm prompt and both branches.
   Edit a fund-code-requirement template after instantiation →
   confirm existing project rows do NOT change.
10. Notification bell appears in the global header with unread count;
    open dropdown shows recent notifications; click a notification or
    "Mark all read" updates the count.
