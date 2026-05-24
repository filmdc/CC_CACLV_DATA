# P-39 — Intake Requirements & Comm Log Document Workflow

| Field | Value |
|---|---|
| **Source** | Kaitlyn stakeholder review + prior Comm Log promotion discovery report |
| **Repo** | `github.com/filmdc/CC_CAP_TRACK` |
| **Status** | Scope — implementation prompts to follow per sub-item |
| **Last updated** | 2026-05-23 |

---

## 1. Executive summary

Kaitlyn has no system support for tracking which qualification documents prospective applicants owe her before a Comm Log entry can be promoted to a housing project. She runs the workflow today out of memory and email; it is the largest single cost in her day. P-39 closes that gap with a new, intake-phase requirement model bolted to `CommunicationLog`, a per-entry document upload path, a dedicated intake dashboard with a progress gauge, and per-user pinning. It is a parallel, intake-phase analogue of the Phase 3A `Requirement` system on `HousingJob` — they share fund-code requirement *templates* (`fund_code_requirements`) but maintain independent instances and lifecycles, with promotion as the handoff seam.

Sub-items in delivery order: **39a (S)** MediaController per-record authorization — security prerequisite; **39b (M)** `intake_requirements` table, anticipated-fund-code pivot, instantiator; **39c (M)** document upload UI on Comm Log entries; **39d (L)** intake dashboard with progress gauge; **39e (S)** per-user Comm Log pinning; **39f (S)** promotion integration warnings and audit. Total effort: **M–L**, dominated by 39d. Critical path: **39a → 39b → 39c → 39d**; 39e parallelizes after 39d; 39f gates on 39b but is otherwise independent.

---

## 2. Sub-item scope

### P-39a — MediaController per-record authorization

**Scope.** `app/Http/Controllers/MediaController.php:14-32` currently authorizes only on `Auth::check()` (line 39). Any authenticated user can hit `/media/{media}/preview` or `/media/{media}/download` (`routes/web.php:71-72`) and retrieve any media row by id. Introduce a `MediaPolicy` (or, equivalently, a `MediaAccessService` if we'd rather not wedge Spatie's Media into Laravel's gate registry) that:

1. Resolves `$media->model` — the morphable parent record (one of the models enumerated below).
2. Dispatches to a per-parent-type `canViewMedia(User $user, Media $media): bool` rule, expressed either as a method on each parent model or as a switch in the policy. Each rule reuses the parent's existing access predicate — e.g., `Requirement` views are gated on housing-job access via the same logic `ProjectManagement` already uses (`refreshJob()` access checks at `app/Livewire/ProjectManagement.php`); `ApprovalSubmission` views via submitter/approver membership; `Contract` views via the existing contract access rule; etc.
3. `MediaController::preview` and `MediaController::download` invoke the policy before calling `ensureAccessibleMedia()`.

**Parent models that must be covered** (per the discovery report and grep across `app/Models`):

- `Feedback` — `app/Models/Feedback.php:11`
- `ContractReportDueDate` — `app/Models/ContractReportDueDate.php:17`
- `MilestoneCommunication` — `app/Models/MilestoneCommunication.php:10`
- `MilestoneDocument` — `app/Models/MilestoneDocument.php:16`
- `ProjectMilestone` — `app/Models/ProjectMilestone.php:12`
- `ApprovalSubmission` — `app/Models/ApprovalSubmission.php:15`
- `ContractReport` — `app/Models/ContractReport.php:14`
- `Contract` — `app/Models/Contract.php:18`
- `Requirement` — `app/Models/Requirement.php:30`
- `IntakeRequirement` — added by 39b

**Signed URLs vs. policy.** Recommendation: **policy + auth middleware is sufficient for v1**. Adding signed URLs would let us link media into emails/notifications without re-auth, which is desirable but not in scope for P-39. The actual security gap is "any logged-in user can read any media"; per-record authorization closes that gap. Signed URLs are a worthwhile follow-up but should be scoped against a notification feature that needs them. If we add signed URLs later, the policy still applies after signature verification — they're complementary, not alternative.

**Acceptance criteria.**
- Hitting `/media/{id}/preview` or `/download` for a media record whose parent the user cannot access returns 403, regardless of authentication.
- Existing legitimate access paths (ProjectManagement, MilestoneDocument viewer, etc.) still return 200.
- Spatie's morphable resolution handles the case where `$media->model` is null (orphaned media): default-deny.
- An admin (`User::isAdmin()`) sees everything — explicit short-circuit at the top of the policy.

**Regression test surface.** Add `tests/Feature/Regression/Media/MediaAuthorizationTest.php` with: one passing and one failing path per parent model type listed above (so ≥20 cases). Use factories for each parent + a non-owner user. Group `regression`. The test must reach the controller (not just unit-test the policy) so route binding and Spatie model resolution are exercised.

**Dependencies.** None.

**Complexity.** **S** — single controller change, one policy class, mechanical test sweep. The risk is missing a parent type; mitigation is the enumerated checklist above plus a `grep -r "implements HasMedia"` review in code review.

**Risks.**
- *Models whose authorization rules are unclear today*: `Feedback` and `ContractReportDueDate` do not have obvious per-record access predicates in the current code (they ride on broader controller-level access). Flag both as **open questions for David** — see Section 3, Open Question 8 (new). Default for v1: admin-only download access until the rule is written.
- *Performance*: each request loads the parent model once. Negligible vs. the file IO.

---

### P-39b — `intake_requirements` schema + instantiation

**Scope.** A new `intake_requirements` table, modeled closely on `requirements` (`database/migrations/2026_04_28_120001_create_requirements_table.php`), but rooted on `communication_logs` rather than `housing_jobs`, and with the intake-phase status semantics ("received" not "complete"). A new `IntakeRequirementInstantiator` service mirrors `app/Services/RequirementInstantiator.php`. A new pivot `communication_log_anticipated_fund_codes` records which fund codes the intake worker expects to apply to this applicant.

**Schema specification — `intake_requirements`** (migration `create_intake_requirements_table`):

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `communication_log_id` | FK → `communication_logs.id` | `cascadeOnDelete`. Matches the housing stack's hard-delete posture; see Open Question 7. |
| `type` | enum | `document`, `task`. v1 only emits `document` — see Open Question 2 — but the column allows tasks to land in v2 without a migration. |
| `label` | string(255) | Display label. Distinct column from `source_label_snapshot` to allow ad-hoc rename. |
| `status` | enum | `outstanding`, `received`, `waived`. See Open Question 4. |
| `received_at` | timestamp nullable | Stamped on transition to `received`. No `sent_at`; outbound delivery is out of scope per Locked Decision 2. |
| `received_by_user_id` | FK → `users.id` nullable | `nullOnDelete`. |
| `source_type` | enum | `baseline`, `fund_code`, `ad_hoc`. Baseline = the universal Application; fund_code = templated from `fund_code_requirements`; ad_hoc = free-form. |
| `source_template_id` | unsigned bigint nullable | Set when `source_type = fund_code`. No FK constraint — matches the `requirements.source_template_id` lifecycle decision in `2026_04_28_120001` lines 22-24. |
| `source_label_snapshot` | string(255) nullable | Copy-on-instantiate. See Open Question 5 — recommendation is to match the existing `Requirement` convention. |
| `notes` | text nullable | Free-form notes (e.g., "Got the W-2 but missing the second page"). |
| `created_by_user_id` | FK → `users.id` nullable | `nullOnDelete`. |
| `updated_by_user_id` | FK → `users.id` nullable | `nullOnDelete`. |
| `created_at`, `updated_at` | timestamps | |

Indexes: `(communication_log_id, status)`, `(source_type, source_template_id)`. No `deleted_at` — hard delete to match the rest of the housing stack.

**Schema specification — `communication_log_anticipated_fund_codes`**:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `communication_log_id` | FK → `communication_logs.id` cascadeOnDelete | |
| `fund_code_id` | FK → `fund_codes.id` cascadeOnDelete | |
| `created_by_user_id` | FK → `users.id` nullOnDelete | |
| `created_at` | timestamp | |

Unique on `(communication_log_id, fund_code_id)`. No `updated_at` — pivot rows are immutable; reconciliation deletes and re-creates rather than updating in place.

Note: this is **distinct** from the existing `communication_log_fund_code` table (`database/migrations/2025_12_18_100002_create_communication_log_fund_code_table.php`), which stores ad-hoc fund-code tags on the entry. Anticipated codes are an intentional intake-phase declaration that drives requirement instantiation; tagged codes are a free-form note. The two relations can drift — Kaitlyn may tag F013 informally but only mark F013 as anticipated once she decides to pursue it. **Open Question 9 (new)**: should `anticipated` deprecate the tag relation, or live alongside it? Recommendation: live alongside for v1, revisit at end of P-39 once the intake workflow is exercised.

**Service specification — `IntakeRequirementInstantiator`** (`app/Services/IntakeRequirementInstantiator.php`):

```
baseline(CommunicationLog $log, User $actor): ?IntakeRequirement
    - Idempotent: returns existing baseline if already present.
    - Creates one IntakeRequirement with source_type=baseline,
      type=document, label='Application', source_template_id=null.

forFundCode(CommunicationLog $log, FundCode $fundCode, User $actor): Collection
    - Mirrors RequirementInstantiator::forFundCode (RequirementInstantiator.php:37-68).
    - Iterates $fundCode->requirements()->ordered()->get().
    - Skips templates already instantiated for this log.
    - Copies source_label_snapshot from template->label at instantiate time.

adHoc(CommunicationLog $log, string $label, string $type, User $actor): IntakeRequirement
    - Free-form. source_type=ad_hoc, all source_* nulled.

reconcileAnticipatedFundCodes(CommunicationLog $log, array $newFundCodeIds, User $actor): void
    - Diffs against current pivot rows.
    - For codes being added: runs forFundCode().
    - For codes being removed: applies the reconciliation policy from
      Open Question 1 — orphan-and-keep is the recommended default.
```

**Model specification — `IntakeRequirement`**:
- `belongsTo(CommunicationLog::class)`.
- `belongsTo(User::class, 'received_by_user_id')` as `receiver`.
- Same status constants pattern as `Requirement` (`Requirement.php:38-42`): `STATUS_OUTSTANDING`, `STATUS_RECEIVED`, `STATUS_WAIVED`.
- Same source constants pattern: `SOURCE_BASELINE`, `SOURCE_FUND_CODE`, `SOURCE_AD_HOC`.
- Mutating actions modeled on `Requirement::markComplete()` etc. (`Requirement.php:214-265`): `markReceived(User $actor, ?string $notes = null)`, `markWaived(User $actor, string $reason)`, `reopen(User $actor)`. No notification dispatch in v1 — see Open Question 6.

**Acceptance criteria.**
- `IntakeRequirementInstantiator::baseline()` is idempotent under repeat calls.
- `IntakeRequirementInstantiator::forFundCode()` is idempotent and copies the template label at instantiate time.
- Reconciliation across anticipated fund codes does not duplicate baseline.
- A unit-test demonstrates the lifecycle: anticipate code A → instantiate three reqs → mark one received → de-anticipate code A → reconciliation policy resolves cleanly per Open Question 1.

**Regression test surface.** New directory `tests/Feature/Regression/IntakeRequirements/` matching the existing `tests/Feature/Regression/Requirements/` convention. Cover: schema invariants, instantiator idempotency, reconciliation policy.

**Dependencies.** None — pure backend.

**Complexity.** **M** — two migrations, one model, one service, one trait-or-cast for status enum. Mostly mechanical mirror of existing Phase 3A code.

**Risks.**
- *Template drift between `fund_code_requirements` consumers*: both `Requirement` and `IntakeRequirement` now read the same template table. If template semantics evolve (e.g., a future `intake_only` flag), the templates table grows scope. Mitigation: a comment block at the top of `IntakeRequirementInstantiator` cross-referencing `RequirementInstantiator`, and a regression test that asserts a template added today flows to both consumers on instantiation.
- *Schema mistake risk*: enum members are notoriously hard to migrate later. Confirm Open Question 4 before the migration is written.

---

### P-39c — Document upload on intake requirements

**Scope.** Wire the existing Spatie media stack onto `IntakeRequirement` exactly as `Requirement` does it (`Requirement.php:30-32, 75-78`). Build a Livewire upload UI on the Comm Log entry detail view that mirrors `ProjectManagement::saveRequirementCompletion()` (`app/Livewire/ProjectManagement.php:1205-1259`), but with intake-phase state transitions.

**Model changes.**
- `IntakeRequirement implements HasMedia`.
- `use HasFactory, HasStandardMediaCollections, InteractsWithMedia` (matches `Requirement.php:32`).
- `registerMediaCollections()` → `$this->registerStandardDocumentCollection('attachments')` (matches `Requirement.php:77`).

**Incidental fix — duplicate `private_media` key.** `config/filesystems.php:41-57` declares `private_media` twice with identical configuration. PHP silently uses the second declaration, so the system behaves correctly today, but the duplication is a latent footgun. Delete lines 50-57. This is a one-line PR addition while the file is open; do not promote it to its own sub-item.

**UI specification.** New Livewire component `App\Livewire\Mobile\IntakeRequirementUpload` (or, more likely, a section embedded into `CommunicationsLogManagement`'s detail modal — current detail modal state is in `CommunicationsLogManagement.php:96-99`). Pattern after `ProjectManagement.php:1226-1235`:

```
foreach ($this->uploadFiles as $file) {
    MediaValidationService::validate(
        $file,
        MediaValidationService::DOCUMENT_MIME_TYPES,
        MediaValidationService::SIZE_100MB
    );
    $intakeReq->addMedia($file->getRealPath())
        ->usingName($file->getClientOriginalName())
        ->usingFileName(MediaValidationService::sanitizeFileName($file->getClientOriginalName()))
        ->withCustomProperties([
            'uploaded_by' => $actor->id,
            'uploaded_at' => now()->toISOString(),
        ])
        ->toMediaCollection('attachments');
}
```

**Upload → status transition.** **Recommendation: automatic.** When the first file lands on an `outstanding` intake requirement, flip status to `received` in the same transaction and stamp `received_at` / `received_by_user_id`. Subsequent uploads to a `received` requirement do not re-stamp `received_at` (preserve the first-receipt time). Reasoning: in the actual workflow Kaitlyn either receives the document or doesn't — there is no review step today. A manual confirmation click adds friction without surfacing a real decision. If review is added later (see Open Question 4's `pending_review` discussion), the auto-flip can be inverted into a `pending_review` landing then-promoted-to-received.

**Acceptance criteria.**
- Uploading any allowed MIME type flips status to `received` once.
- 39a's authorization fix is in place — confirmed by a test that uploads then attempts download as a non-owner user and gets 403.
- Files are written to the `private_media` disk.
- `MediaValidationService::validate()` rejects bad MIME / oversize uploads with the existing UX (mirror `ProjectManagement` failure path).
- Mark-as-received without an upload (waive flow) still works via a separate explicit "waive" action with a required reason — modeled on `markCompleteWithOverride` (`Requirement.php:237`).

**Regression test surface.** `tests/Feature/Regression/IntakeRequirements/UploadFlowTest.php` — happy path, validation rejection, auth-fails (cross-test with 39a).

**Dependencies.** **Hard dependency on P-39a.** 39c must not merge until 39a has shipped; otherwise we multiply the volume of unauthenticated-readable media.

**Complexity.** **M** — UI work is the bulk; backend is one trait declaration.

**Risks.**
- *Comm Log detail modal is already dense* (`CommunicationsLogManagement.php` lines 96-99 and beyond). Cramming the upload UI in may push the modal past usability. Mitigation: prototype on the Comm Log detail view inline (not modal) first, in the same way the convert-to-project wizard already breaks out of modal context.
- *Multi-file vs. single-file semantics*: a "received" requirement may need additional supporting documents added later (e.g., page 2 of the W-2). Confirm with Kaitlyn whether one intake requirement can hold multiple files. Default: yes, multiple files per requirement (matches `Requirement` pattern).

---

### P-39d — Intake dashboard with progress gauge

**Scope.** Surface every Comm Log entry that has at least one instantiated intake requirement as a "in-flight applicant" row, with progress (received / total) prominently visible. Sort and filter the queue. Provide a path into `convertToProject()` when progress hits 100%.

**Component placement decision.** **Recommendation: new sibling page `IntakeDashboard`, not a third tab on `HousingDashboard`.** Reasoning:
- `HousingDashboard` already operates on `housing_jobs` rows (`HousingDashboard.php:84-86`). Wedging a third tab that operates on `communication_logs` rows breaks the component's accessibility model (`accessibleHousingJobIds`) and forces every render to compute two disjoint result sets.
- Access scoping differs (see below): the intake dashboard's audience is narrower than the housing dashboard's.
- A separate route lets Kaitlyn pin the intake dashboard as her browser homepage if she chooses, which she will likely want.

Route: `GET /intake-dashboard` → `App\Livewire\IntakeDashboard`. Permission: new `intake_dashboard.view`. Add a sidebar nav entry conditioned on the permission.

**Data shape per row.**
- Applicant identity: `contact_full_name` (existing accessor on `CommunicationLog.php:240-243`) and, if linked, `participant->full_name`. Address from `contact_full_address` (`CommunicationLog.php:245-255`).
- Anticipated programs/fund codes: pivot via 39b.
- Date of first contact: `communication_date`.
- Days since last activity: max of `communication_date`, latest `CommunicationLogUpdate.created_at`, latest `received_at` across this entry's intake requirements.
- Progress: `received_or_waived_count / total_count` — both numbers visible.
- List-or-collapse of outstanding requirements, labeled and linked to the entry detail / upload UI.

**Sort options.** Longest waiting; closest to complete (highest non-100% progress); most outstanding (highest absolute outstanding count); alphabetical by applicant last name. Default: longest waiting.

**Filter options.** Anticipated program/fund code (multi-select); progress range (0%, 1–50%, 51–99%, 100%); has-pending-follow-up (reuses `scopePendingFollowUp` on `CommunicationLog.php:374-378`); show-promoted-entries toggle (default OFF — see 39f).

**Progress gauge — visual.** Recommendation: **CSS donut** built from a single conic-gradient. Cheap to render, scales cleanly, no SVG component dependency. Color thresholds — confirming the proposed breakpoints:
- 0%: gray (not red — red implies a problem; 0% just means we haven't received anything yet, which for a fresh entry is the expected state).
- 1–50%: amber.
- 51–99%: yellow-green.
- 100%: green with **"Ready to promote"** call-to-action that opens the existing `convertToProject()` wizard from `CommunicationsLogManagement.php:1138`.

*Counter-proposal accepted on the 0% color only.* The amber→yellow-green→green progression is otherwise sound.

**Performance.** Mirror the `DashboardIndicatorSupport` pattern (`app/Services/DashboardIndicatorSupport.php`). Add an `IntakeProgressSupport` service that takes an array of `communication_log_ids` and returns a map of `id => ['received' => int, 'total' => int]` in one query, using `groupBy` and `count(case when status in ('received','waived') then 1 end)`. Render loops do O(1) lookups, never N+1.

**Access scoping.**
- New permission `intake_dashboard.view`. Held by: Kaitlyn (housing program manager role), admins, and explicitly NOT held by Tony / Peter / construction roles. Add to `ProjectHousingPermissionsSeeder` (already wired in DatabaseSeeder per CLAUDE.md).
- Within the dashboard, no further per-row scoping: if you can see the dashboard, you can see all in-flight applicants. This matches Kaitlyn's actual mental model (she is the sole intake worker) and avoids the complexity of building a participant-ownership graph she doesn't actually need.

**Acceptance criteria.**
- Loads under 500ms on a fixture with 200 Comm Log entries × 8 intake requirements each.
- Rows show correct progress, including correct denominator after fund-code reconciliation (per Open Question 1).
- Sort defaults to longest waiting; sort/filter combinations all yield deterministic ordering.
- 100% rows surface "Ready to promote" and clicking it lands in the existing convert-to-project wizard with the correct entry preloaded.
- Non-permitted users get 403 on the route.

**Regression test surface.** New `tests/Feature/Regression/IntakeDashboard/` with: row data shape, sort correctness, filter combinatorics, the 100% CTA wiring, the show-promoted toggle (39f). Add a query-count assertion mirroring the dashboard regression test pattern.

**Dependencies.** **Hard on 39b** (data) and **39c** (the "Ready to promote" CTA needs functional upload to push entries past 0%). 39a is implicit (any UI touching media should ship behind the auth fix).

**Complexity.** **L** — significant UI surface, new component, new permission, performance support service, regression coverage. The largest single sub-item.

**Risks.**
- *Definition of "in-flight applicant"*: an entry is in-flight when it has at least one intake requirement. The trigger for instantiating the baseline is the gating mechanism — see Open Question 3. If we adopt option (a) "baseline on creation," the dashboard floods with tire-kicker calls. Strongly prefer option (b) or (c).
- *Sort by "days since last activity" requires a max across three sources*. Either compute it in the query (correlated subquery, ugly) or in `IntakeProgressSupport`. Recommend the latter for clarity.
- *Promoted entries*: filter-out-by-default avoids the dashboard becoming a permanent record; the toggle lets Kaitlyn look back when she wants. The audit trail lives on the Comm Log entry itself.

---

### P-39e — Per-user Comm Log pinning

**Scope.** Add a per-user pin relation on `CommunicationLog` and render a pinned-cards strip above the intake dashboard list.

**Schema specification — `comm_log_pins`**:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `communication_log_id` | FK → `communication_logs.id` cascadeOnDelete | |
| `user_id` | FK → `users.id` cascadeOnDelete | |
| `pinned_at` | timestamp | Sort key for pinned strip ordering. |

Unique on `(communication_log_id, user_id)`. No `updated_at` — pins are immutable until deleted.

**Model.** Lightweight `CommLogPin` model, plus `CommunicationLog::pins(): HasMany` and a `User::pinnedCommunicationLogs(): BelongsToMany` through pivot. The user side is the predominant query path (rendering Kaitlyn's strip).

**UI.** Above the intake dashboard's list, render up to 12 pinned-card tiles for the current user. Each card shows the same fields as a dashboard row but in card form: applicant name + address (large), anticipated codes (small), progress donut (prominent), one-line summary of outstanding requirements. Cards are visually distinct from the table rows below — recommend a different background (`bg-indigo-50 dark:bg-indigo-900/30`) and a slightly elevated shadow.

**Interactions.**
- Pin/unpin toggle accessible from both the pinned card and the table row.
- Optimistic UI on toggle (`wire:click.prefetch` or equivalent). Acceptable for v1; an in-flight failure simply reverts on next render.
- Soft cap: **12 pins**. Above 12, show a gentle inline warning ("You have 12 pinned entries — pinning more may make this strip hard to scan") but allow the action. No hard cap.
- Pins are private to the user; no shared / team pins in v1.

**Acceptance criteria.**
- Toggle from either surface persists across reload.
- Other users' pins are not visible.
- Deleting a Comm Log entry cleans up pins via cascade.
- Empty state: strip is hidden when the user has zero pins.

**Regression test surface.** `tests/Feature/Regression/IntakeDashboard/PinningTest.php` — pin/unpin, isolation by user, cap warning, cascade cleanup.

**Dependencies.** Depends on 39d for the surface it renders into. Schema and toggle can land before 39d's full dashboard is built; the strip just rides along.

**Complexity.** **S** — one migration, one model, one trait method, ~80 lines of Livewire.

**Risks.**
- *Mobile layout*: the pinned strip on a phone-width viewport will need to collapse to a horizontal scrolling carousel. Specify in the implementation prompt.

---

### P-39f — Promotion integration

**Scope.** Wire the existing `convertToProject()` flow (`CommunicationsLogManagement.php:1138-1340-ish` and the model method at `CommunicationLog.php:276-289`) to the new intake requirements.

**Behavioral spec.**

1. **Intake requirements stay attached to the Comm Log entry post-promotion.** They are NOT migrated to the new `HousingJob`. The new `HousingJob` gets its own project-execution `requirements` instantiated by the existing Phase 3A flow (`RequirementInstantiator::forFundCode`). The two sets may overlap by label but are distinct rows in distinct tables. This is the design intent of the locked decision "intake requirements end at promotion; project requirements begin at promotion."

2. **Non-blocking warning on promotion** if the entry's intake requirements are not all in `received` or `waived` status when promotion is attempted. UI: a yellow callout in the existing wizard's final step ("3 intake requirements are still outstanding. Promote anyway?"). The user can proceed. Reasoning: there are edge cases (an applicant whose first contact happened to be the contractor's site visit, where the docs flow through a different paper trail), and Kaitlyn knows them when she sees them.

3. **Audit entry on promotion.** `convertToProject()` already appends a `CommunicationLogUpdate` via `addUpdate()` (`CommunicationLog.php:283-288`). Extend that to include intake-requirement progress: `"Converted to Project: JOB-XXXX. Intake requirements at promotion: N/M received."` Single line, appended to the existing status_change update — do not create a second update row.

4. **Show-promoted-entries toggle on the intake dashboard.** Default OFF. When ON, promoted entries appear with a "Promoted to {job_number}" badge and are read-only in the dashboard (the entry detail remains accessible and editable through `CommunicationsLogManagement`, but the dashboard treats them as historical).

**Acceptance criteria.**
- Promoting with outstanding intake requirements emits the warning and is permitted.
- Promoting after marking all received does not emit the warning.
- The `CommunicationLogUpdate` row contains the intake-requirement count string.
- Intake requirements remain queryable on the original `CommunicationLog` after promotion.
- The dashboard filters promoted entries by default and shows them when toggled on.

**Regression test surface.** `tests/Feature/Regression/IntakeDashboard/PromotionIntegrationTest.php` — warning path, no-warning path, audit-entry content, dashboard filter behavior.

**Dependencies.** 39b for data. 39d for the dashboard filter toggle. Otherwise independent.

**Complexity.** **S** — narrow seam, mostly an extension to `convertToProject()`.

**Risks.**
- *`convertToProject()` is already a long method with several existing-vs-new entity branches* (`CommunicationsLogManagement.php:1138-1340-ish`). Adding the warning is fine; resist the urge to refactor the method as part of this sub-item.

---

## 3. Open questions — recommended resolutions

### OQ-1. Reconciliation policy when an anticipated fund code is removed

**Options:** (a) hard-delete all sourced requirements; (b) keep and mark orphaned (excluded from denominator); (c) per-requirement user prompt.

**Recommendation: (b) keep and mark orphaned.** Add an `is_orphaned` boolean (default false) on `intake_requirements`. Reconciliation flips it to true on the affected rows; the denominator query filters `is_orphaned = false`. Reasoning: (a) silently destroys received-status history, which is exactly the kind of audit gap CSBG-compliance work tends to come back asking about; (c) is too much friction for an action Kaitlyn will do casually (toggling an anticipated code on or off as the applicant's circumstances clarify). (b) preserves history, gives a one-click "un-orphan" if the code is re-added, and matches the Phase 3A `Requirement` model's soft-detach posture.

**Who decides?** Defensible by me alone, but **flag to Kaitlyn at first sighting** — she may have a strong preference, and the cost of getting this wrong is low (a one-line query change).

### OQ-2. Task-type intake requirements

**Recommendation: documents only for v1.** Tasks deferred to a follow-up. Reasoning: the entire intake workflow Kaitlyn described is about documents owed. A task like "Schedule eligibility interview" can be tracked today via the existing `follow_up_date` field on `CommunicationLog` (`CommunicationLog.php:41-45`). Adding tasks to intake would create two competing surfaces for the same kind of work. The `type` column is still in the schema so v2 can enable it without a migration.

**Who decides?** Defensible by me alone.

### OQ-3. The "this is a real applicant" trigger for baseline instantiation

**Options:** (a) on every Comm Log creation; (b) on first anticipated fund code; (c) explicit "promote to intake" button.

**Recommendation: (b) on first anticipated fund code.** Reasoning: (a) floods the intake dashboard with cold calls — most Comm Log entries never become applicants. (c) adds a button Kaitlyn must remember to click, which is exactly the kind of manual step P-39 exists to remove. (b) ties the trigger to a meaningful intent signal: the moment Kaitlyn says "this person plausibly qualifies for HOME funds," they enter the intake queue. If she's wrong, she un-anticipates the code; the baseline either stays (per OQ-1 policy) or gets orphaned cleanly.

**Edge case:** a Comm Log entry with zero anticipated fund codes but still in-intake (the applicant is qualified for "something we haven't figured out yet"). Handle this via an explicit "Add to intake without a fund code" button on the entry that calls `instantiator->baseline()` directly — escape hatch, not the default path.

**Who decides?** This is a meaningful UX call. **Recommend confirming with Kaitlyn** — the wrong choice here makes the intake dashboard either too noisy (option a) or too easy to forget about (option c).

### OQ-4. Status enum members

**Recommendation: `outstanding`, `received`, `waived` only for v1.** Defer `pending_review` and `rejected`.

Reasoning: `pending_review` would imply a two-step receipt (Kaitlyn confirms the upload satisfies the requirement). She doesn't operate that way today — she receives and files; if the document is wrong she emails the applicant back outside the system. `rejected` is the same story from the other direction. Both are reasonable v2 features once the basic intake loop is operational and we see whether the missing distinction actually bites.

**Who decides?** Defensible by me alone, but mention to David in scope review.

### OQ-5. `source_label_snapshot` semantics

**Recommendation: snapshot at instantiate time, matching `Requirement.source_label_snapshot`** (`requirements` migration lines 54-55 and `RequirementInstantiator.php:60`). Reasoning: consistency across the two systems is more valuable than the marginal simplicity of live rendering. If a fund code template gets renamed (e.g., "Lead Inspection" → "Lead Risk Assessment"), the historical intake requirements continue to display the label that was in effect when Kaitlyn instantiated them, which is what an audit reader expects.

**Who decides?** Defensible by me alone.

### OQ-6. Notification scope

**Recommendation: no notifications in v1, with one explicit exception.** The exception: when an intake requirement's status flips to `received` AND the entry's progress reaches 100%, fire **one** `IntakeReadyForPromotionNotification` to the entry's creator (typically Kaitlyn). Single fire — guard with a "have we already sent this for this entry?" check (a `ready_notified_at` timestamp on `communication_logs` is sufficient).

Reasoning: Kaitlyn is the only owner, so the Phase 3B notification stack (`RequirementAssignedNotification`, etc.) would only ever notify her about her own actions — pure noise. But "this applicant is now ready to promote" is the one event during intake worth pulling her attention to, because it shifts the entry's state in her queue.

**Who decides?** Defensible by me alone.

### OQ-7. Cascade behavior on Comm Log deletion

**Recommendation: cascade-delete intake requirements and their media** — matching the housing stack's hard-delete convention and the `Requirement.housing_job_id` cascadeOnDelete (`requirements` migration line 37). Reasoning: Comm Log entries are themselves hard-deletable today; if a manager deletes an entry, leaving orphaned intake requirements and uploaded files in the system would be worse for both data hygiene and any future privacy review.

**Caveat.** The discovery report flagged that hard deletes nuke audit trails — this concern is real but it's a *system-wide* concern, not unique to P-39. Adopting soft deletes for `intake_requirements` while the rest of the housing stack is hard-delete would create an inconsistent posture. If we want soft deletes here, we should want them everywhere; that conversation belongs in a separate item.

**Who decides?** Defensible by me alone; flag to David for endorsement.

### OQ-8 (new). Authorization rules for media-bearing models without obvious access predicates

`Feedback` and `ContractReportDueDate` (and possibly others) implement `HasMedia` but don't have clear per-record access rules in code. 39a needs an answer.

**Recommendation:** admin-only download for these parents until a specific access rule is written. Surface as a separate item to be resolved with the owners of those features (likely not Kaitlyn).

**Who decides?** **David.**

### OQ-9 (new). `anticipated_fund_codes` vs. existing `fund_codes` tag relation on Comm Log

The existing `communication_log_fund_code` pivot (`CommunicationLog.php:121-125`) is currently used for ad-hoc tagging per P-27. 39b introduces a distinct `communication_log_anticipated_fund_codes` pivot.

**Recommendation:** live alongside for v1. Revisit at end of P-39 once the intake workflow has been exercised — likely outcome is that `anticipated` becomes authoritative and the tag relation either deprecates or merges. Don't try to predict the right answer in advance.

**Who decides?** Defensible by me alone; revisit decision at P-39 retro with Kaitlyn.

---

## 4. Sequencing & milestones

```
Week 1     39a (Media auth) ─┐
                             │
Week 1–2   39b (Schema + instantiator) ─┐
                                        │
Week 2     39c (Upload UI) ─────────────┤── depends on both 39a, 39b
                                        │
Week 2–4   39d (Intake dashboard) ──────┤── depends on 39b, soft on 39c
                                        │
Week 3     39e (Pinning) ────────────── ┤── depends on 39d
                                        │
Week 3     39f (Promotion integration) ─┘── depends on 39b
```

**Parallelization opportunities.**
- 39a can begin immediately and ship independently — it's a security fix that benefits the whole codebase. There is no reason to gate it on the rest of P-39.
- 39b and 39c can be drafted in parallel; 39c's merge gates on both 39a and 39b being in.
- 39e and 39f can be developed in parallel once 39d's surface is stubbed (even before 39d is fully shipped).

**Hard dependencies that cannot parallelize.**
- 39a → 39c (security prerequisite to upload UI).
- 39b → all of 39c, 39d, 39e, 39f (they all read the intake_requirements table).

**Recommended PR cadence.** One PR per sub-item, except 39a should ship in its own PR ahead of the others. 39d is large enough to consider splitting into "dashboard data layer" and "dashboard UI" PRs.

---

## 5. Cross-cutting risks

1. **Media volume.** P-39 multiplies the volume of media stored against the `private_media` disk by an unknown factor (likely 5–10×, based on Kaitlyn's description of how many docs an intake involves). 39a's per-record auth is necessary but not sufficient — we should also confirm that `storage/app/private` has room and that the backup story (per `sync-database-from-remote.sh` and related infra) covers the disk, not just the database. **Flag to David before 39c lands.**

2. **`CommunicationLog` model growth.** The model is already 444 lines and growing (`app/Models/CommunicationLog.php`). Adding the `anticipatedFundCodes()`, `intakeRequirements()`, `pins()`, and `intake_progress` accessors will push it past 500. Not catastrophic but worth a follow-up extraction into a `CommunicationLogHasIntake` trait or a separate `IntakeEntry` view-model. Do not refactor as part of P-39; note for cleanup.

3. **Permission proliferation.** P-39 adds `intake_dashboard.view` and, transitively, will likely surface needs for `intake_requirements.create`, `.update`, `.delete`. Confirm with David whether all of these collapse to `housing_manager` role, or whether we need finer-grained per-action permissions. Default for v1: collapse to role checks, add granular permissions only if a real second user appears.

4. **UX coherence with `HousingDashboard`.** Two dashboards (intake + housing) with similar-looking-but-different data shapes risk confusing the user. Mitigation: explicit nav labeling ("Intake Queue" vs. "Housing Projects"), distinct visual treatment, and a cross-link from each into the other (an in-flight applicant card on the housing dashboard's "Recent Promotions" area; a "View housing project" link on a promoted intake entry).

5. **Regression harness drift.** P-39 adds substantial new regression coverage. Confirm with David that `./bin/run-regression.sh` runtime stays under the working-session-acceptable threshold (per `REGRESSION_HARNESS.md`); if not, we may need to shard the harness, which is itself a follow-up.

6. **Locked-decision tension — none currently identified.** Re-reading the eight locked decisions against the scope above, no genuine incompatibilities surfaced. The closest is locked decision 4 (per-user pinning, multi-user from day one) vs. the practical reality that Kaitlyn is the only user — but supporting multi-user in schema doesn't constrain implementation, so this is a cost-free hedge.

---

## 6. Explicitly out of scope

The following are NOT included in P-39 and should be deferred to separate items:

- **Outbound document delivery.** No `sent_at` tracking, no document-request emails, no SMS reminders to applicants. The locked decision is "intake requirements model only the inbound event"; respect it.
- **Notifications beyond v1 minimum.** Per OQ-6, only the single "ready to promote" notification is in scope. No daily digests, no overdue reminders, no per-requirement assignment notifications.
- **Any rework of the existing Phase 3A `Requirement` system.** The two systems share templates but maintain independent code paths. Changes to `Requirement`, `RequirementInstantiator`, or any project-execution behavior are out of scope.
- **Any change to `convertToProject()` mechanics beyond the warning and audit entry described in 39f.** No refactoring of the wizard, no changes to participant / property / household reconciliation logic.
- **Signed URLs for media.** Per 39a, signed URLs are a worthwhile follow-up but not required for the v1 security fix.
- **Soft deletes for `intake_requirements` or `communication_logs`.** Per OQ-7, hard delete matches the housing stack convention.
- **A `pending_review` or `rejected` status.** Per OQ-4, deferred to v2.
- **Task-type intake requirements.** Per OQ-2, deferred to v2.
- **Cross-user shared pins.** Per 39e, v1 is per-user only.
- **A standalone `intake_dashboard.export` flow** (CSV / PDF export of the queue). Out of scope.
- **Mobile (`approvals.caclv.org`) surfacing of the intake dashboard.** This is desktop-only for v1. The Comm Log detail upload UI may benefit from mobile rendering, but a dedicated mobile intake dashboard is a separate sub-item.
- **Sage / MIP / DonorPerfect integration of intake data.** Not in scope. Ever, for this module. (See CLAUDE.md's housing budget standalone-tracker note for the established pattern.)
