# Claude Code Implementation: P-39b — `intake_requirements` schema + instantiation

**Revision 2** — incorporates corrections 1–4 from the prompt review (addendum decisions D-1, D-2, D-4, plus Phase 3A regression protection).

## Context

Second implementation sub-item of the P-39 (Intake Requirements & Comm Log Document Workflow) epic. Source documents:

- `P39_SCOPE.md` — full epic scope.
- `P39_SCOPE_ADDENDUM.md` — David's decisions; authoritative. **Note:** as of this revision, the addendum has not been committed to the repo. Confirm it lands in the same PR-merge window so future contributors can reconcile D-1, D-2, D-4 against the scope without archaeology.
- `P39B_PROMPT.md` — this file.

P-39a (MediaController per-record authorization, PR #20) is the security prerequisite. 39b can be drafted in parallel with 39a but must not merge before 39a, because 39c (upload UI, depends on 39b) needs 39a's authorization layer in place. Confirm 39a is merged before opening 39b's PR.

39b is the **backend / data layer** for intake requirements, plus the cleanup forced by addendum D-2 (replacing the existing tag pivot). No new UI surfaces, no upload, no dashboard. The PR may touch existing UI references to the removed pivot — see Implementation Step 8.

## What 39b delivers

1. A new `intake_requirements` table, rooted on `communication_logs`.
2. A new `communication_log_anticipated_fund_codes` pivot table.
3. A `phase` column on `fund_code_requirements` (`eligibility` | `project`), with existing rows backfilled to `project`.
4. Backfill of the existing `communication_log_fund_code` tag pivot into the new `anticipated` pivot, **followed by dropping the tag pivot**.
5. An `App\Models\IntakeRequirement` Eloquent model + `IntakeRequirementFactory`.
6. An `App\Services\IntakeRequirementInstantiator` service with `baseline()`, `forFundCode()` (filtered to `phase='eligibility'`), `adHoc()`, and `reconcileAnticipatedFundCodes()`.
7. A one-line modification to the existing `App\Services\RequirementInstantiator::forFundCode()` to filter `phase='project'`, accompanied by an explicit Phase 3A regression test.
8. `CommunicationLog` model: the existing `fundCodes()` relation is **removed**; the new `anticipatedFundCodes()` is the only fund-code relation.
9. Granular permission seeding for the full P-39 permission set, ahead of consumer wiring in 39c/39d.
10. No new controller, no new Livewire component, no new route. The PR may modify existing UI files only insofar as required to keep the build green after the tag-pivot removal.

## Locked decisions baked into this prompt

These are inputs from `P39_SCOPE.md` plus the addendum corrections. Do not relitigate:

1. **Separate table**, not a polymorphic extension of `requirements`. Templates are shared via `fund_code_requirements`; instances are not.
2. **`received_at` only** — no `sent_at`, no outbound delivery tracking.
3. **OQ-1 reconciliation**: orphan-and-keep. Add `is_orphaned` boolean (default false) on `intake_requirements`. De-anticipating a fund code flips the boolean to true on every sourced requirement. Re-anticipating the same code flips it back to false (un-orphans existing rows; does not duplicate). Progress denominator filters `is_orphaned = false`.
4. **OQ-2 type column**: v1 emits documents only. The `type` enum accepts both `document` and `task` so v2 can enable tasks without a migration. Fund-code-sourced rows inherit the template's `type`.
5. **OQ-3 baseline trigger**: instantiate on first anticipated fund code, with an explicit "Add to intake without a fund code" escape hatch. **This call needs Kaitlyn input before 39d ships, but 39b just provides the mechanism — no triggering code lives in 39b.** The `baseline()` method exists; *when* it gets called is 39d's concern.
6. **OQ-4 status enum**: `outstanding`, `received`, `waived`. No `pending_review` or `rejected`.
7. **OQ-5 `source_label_snapshot`**: copy on instantiate, matching the existing `Requirement.source_label_snapshot` convention.
8. **OQ-6 notifications**: none in v1 from this sub-item. The single "ready to promote" notification lives in 39d/39f, not here.
9. **OQ-7 cascade**: hard delete via `cascadeOnDelete` on the `communication_log_id` FK, matching `requirements.housing_job_id`'s posture.
10. **OQ-9 (addendum D-2): anticipated is authoritative.** The new `communication_log_anticipated_fund_codes` pivot replaces the existing `communication_log_fund_code` tag pivot. Existing tag data is migrated into the new pivot in this PR. The old pivot table is dropped and the `fundCodes()` relation on `CommunicationLog` is removed. **No coexistence period.**
11. **Addendum D-1: phase split on fund-code requirement templates.** `fund_code_requirements` gains a `phase` enum (`eligibility` | `project`). Intake and project draw from disjoint template subsets on the same fund. Default for existing rows: `project` (preserves current behavior).
12. **Addendum D-4: granular permissions.** Six permissions are seeded in this PR, even though consumers land in 39c/39d.

## Discover phase — required before implementation

Before writing any migration or model code, document the following in your session output and include it in the PR description:

### 1. Re-read the existing `Requirement` system end-to-end

This sub-item is a deliberate mirror of Phase 3A. Anything 39b's `IntakeRequirementInstantiator` does, the existing `RequirementInstantiator` (`app/Services/RequirementInstantiator.php`) probably does in a similar shape. Read it and the existing `Requirement` model (`app/Models/Requirement.php`) end-to-end. Document any divergence you're proposing and why — if you find yourself diverging without a clear reason, default back to the existing pattern.

### 2. Confirm `fund_code_requirements` schema state and document the phase migration

Confirm:
- The `type` column exists (added by `2026_04_28_120002_add_type_to_fund_code_requirements_table.php`). Document its enum members.
- **No `phase` column exists yet.** This PR's `add_phase_to_fund_code_requirements_table` migration introduces it.

Confirm against the test DB by reading the migrations directory and (optionally) running `php artisan migrate:status` — never `migrate:fresh`.

### 3. `CommunicationLog` relation audit AND removal-impact grep

The existing `fundCodes()` relation on `CommunicationLog` (line 121-125 in the file at PR-#20 time) is being **removed**, not preserved. Audit the codebase before writing any code:

```bash
grep -rn "communication_log_fund_code\|->fundCodes\(\)\|CommunicationLog.*fundCodes\b" \
  app/ resources/ tests/ routes/ database/ \
  --include='*.php' --include='*.blade.php'
```

Catalogue every result in the PR description. For each result, classify it as one of:

- **Migrate**: code references the tag pivot and should be redirected to `anticipatedFundCodes()`. Most production references will be in this bucket.
- **Remove**: code is UI for the tag relation specifically (e.g., a "Tag this Comm Log with fund codes" form distinct from anticipation) and no longer makes sense. Remove with documented rationale.
- **Retain**: explicitly justified retention (e.g., a migration file, a test fixture, a historical audit log entry). Should be rare; default is migrate-or-remove.

The "no UI" boundary on 39b means **no new UI surfaces** — but it does NOT mean "leave the codebase broken." Existing call sites that touch the removed relation must be fixed in this PR. Treat that as data-layer-driven cleanup, not feature work.

### 4. Permission seeder choice

Decide whether to add the six new permissions to `ProjectHousingPermissionsSeeder` or stand up a new `IntakeRequirementsPermissionsSeeder` and chain it from `RegressionTestSeeder`. Recommendation: **new dedicated seeder.** Reasoning: `ProjectHousingPermissionsSeeder` is already 280+ lines and seeds permissions for multiple concerns; a dedicated seeder keeps P-39's permission ownership legible. Document your choice and the rationale in the PR.

### 5. Factory defaults

`IntakeRequirementFactory` defaults should support downstream sub-items' tests:

- `type = 'document'`
- `status = 'outstanding'`
- `source_type = 'baseline'`
- `label = 'Application'`
- `is_orphaned = false`

Document your chosen defaults. State methods to provide: `document()`, `task()`, `received()`, `waived()`, `orphaned()`, `baseline()`, `fundCode()`, `adHoc()`.

### 6. Confirm regression seeder coverage for the migrated tag data

`RegressionTestSeeder` does not currently insert rows into `communication_log_fund_code`. Confirm by reading the seeder. If it does (or starts inserting into the new pivot after migration), document the path. Recommendation: factory-only fixtures for intake-related test data; the seeder is already crowded.

## Implementation — once Discover is complete

### Step 1: Migration — `add_phase_to_fund_code_requirements_table`

```php
Schema::table('fund_code_requirements', function (Blueprint $table) {
    $table->enum('phase', ['eligibility', 'project'])
        ->default('project')
        ->after('is_hard_requirement');
});

// Backfill existing rows. The default-value clause above already covers
// new rows; this is belt-and-braces for the legacy fleet.
DB::table('fund_code_requirements')->update(['phase' => 'project']);
```

This must be the **first** of 39b's migrations so the column exists before `IntakeRequirementInstantiator` reads it. The default of `project` ensures the existing admin form (which posts without a `phase` field) continues to work — new templates created through that form land as project-phase. The admin form gains a phase selector in a follow-up sub-item (39d, or a dedicated small follow-up before 39d). Document this hand-off in the PR.

### Step 2: Migration — `create_intake_requirements_table`

```php
Schema::create('intake_requirements', function (Blueprint $table) {
    $table->id();

    $table->foreignId('communication_log_id')
        ->constrained('communication_logs')
        ->cascadeOnDelete();

    $table->enum('type', ['document', 'task']);

    $table->string('label', 255);

    $table->enum('status', ['outstanding', 'received', 'waived'])
        ->default('outstanding');

    $table->timestamp('received_at')->nullable();
    $table->foreignId('received_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->enum('source_type', ['baseline', 'fund_code', 'ad_hoc']);
    $table->unsignedBigInteger('source_template_id')->nullable();
    $table->string('source_label_snapshot', 255)->nullable();

    $table->boolean('is_orphaned')->default(false);

    $table->text('notes')->nullable();

    $table->foreignId('created_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();
    $table->foreignId('updated_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamps();

    $table->index(['communication_log_id', 'status', 'is_orphaned'], 'intake_req_log_status_orphan_idx');
    $table->index(['source_type', 'source_template_id'], 'intake_req_source_idx');
});
```

Notes:
- No FK on `source_template_id`. Matches `requirements.source_template_id` lifecycle decision: templates may be deleted without cascading the intake instance; the global-delete prompt handles cleanup.
- No `deleted_at` — hard delete, matching the housing stack.
- `is_orphaned` lives in the composite index because every dashboard query filters on it.

### Step 3: Migration — `create_communication_log_anticipated_fund_codes_table`

```php
Schema::create('communication_log_anticipated_fund_codes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('communication_log_id')
        ->constrained('communication_logs')
        ->cascadeOnDelete();
    $table->foreignId('fund_code_id')
        ->constrained('fund_codes')
        ->cascadeOnDelete();
    $table->foreignId('created_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();
    $table->timestamp('created_at')->nullable();

    $table->unique(['communication_log_id', 'fund_code_id'], 'comm_log_anticipated_fc_uniq');
});
```

Notes:
- No `updated_at`. Reconciliation deletes and recreates rather than updating in place.
- Surrogate `id` because the per-row `created_by_user_id` makes a natural composite PK unwieldy and the unique constraint covers data integrity.

### Step 4: Migration — `migrate_communication_log_fund_code_to_anticipated_and_drop`

This is the cutover migration. Sequence-critical: it must run **after** Step 3 (the destination table must exist).

```php
public function up(): void
{
    // 1. Backfill into the new pivot. Use a single INSERT-SELECT for
    //    speed and transactional safety. Preserve created_at; rely on
    //    the unique constraint to suppress any (unexpected) duplicates.
    $sourceCount = DB::table('communication_log_fund_code')->count();

    DB::statement("
        INSERT IGNORE INTO communication_log_anticipated_fund_codes
            (communication_log_id, fund_code_id, created_at)
        SELECT communication_log_id, fund_code_id, created_at
          FROM communication_log_fund_code
    ");

    $migratedCount = DB::table('communication_log_anticipated_fund_codes')->count();

    // 2. Parity assertion. If the new pivot already had rows before
    //    this migration ran, that violates the "anticipated is being
    //    introduced by this PR" assumption. Bail rather than silently
    //    drop the source.
    if ($migratedCount < $sourceCount) {
        throw new \RuntimeException(sprintf(
            'Backfill row-count mismatch: source=%d, destination=%d. ' .
            'Aborting before drop to preserve source data.',
            $sourceCount,
            $migratedCount
        ));
    }

    // 3. Drop the source pivot.
    Schema::dropIfExists('communication_log_fund_code');
}

public function down(): void
{
    Schema::create('communication_log_fund_code', function (Blueprint $table) {
        $table->foreignId('communication_log_id')->constrained('communication_logs')->cascadeOnDelete();
        $table->foreignId('fund_code_id')->constrained('fund_codes')->cascadeOnDelete();
        $table->timestamps();
        $table->primary(['communication_log_id', 'fund_code_id']);
    });

    DB::statement("
        INSERT IGNORE INTO communication_log_fund_code
            (communication_log_id, fund_code_id, created_at, updated_at)
        SELECT communication_log_id, fund_code_id, created_at, COALESCE(created_at, NOW())
          FROM communication_log_anticipated_fund_codes
    ");
}
```

Notes:
- `INSERT IGNORE` (MySQL) is intentional — the unique constraint on the destination silently suppresses duplicates rather than failing the whole migration on a corner case (e.g., a row already inserted by 39b's test fixtures running before the migration in some test paths).
- The `down()` re-creates the tag pivot and back-migrates the rows. It does NOT delete data from the new pivot — the `down()` is for rollback in dev, not for round-tripping.
- The parity check must use a strict `<` comparison (not `!=`) to allow for the (unlikely) case where the new pivot has additional rows from test fixtures during a multi-step migration apply.

**Migration order summary:**

1. `add_phase_to_fund_code_requirements_table`
2. `create_intake_requirements_table`
3. `create_communication_log_anticipated_fund_codes_table`
4. `migrate_communication_log_fund_code_to_anticipated_and_drop`

### Step 5: Model — `App\Models\IntakeRequirement`

`app/Models/IntakeRequirement.php`. Modeled closely on `app/Models/Requirement.php`. Key elements:

- `use HasFactory`. No `InteractsWithMedia` here — that's added in 39c. Leave `IntakeRequirement` non-`HasMedia` for now; 39c implements both `HasMedia` and `HasMediaAuthorization` (per the contract introduced in P-39a).
- Status constants: `STATUS_OUTSTANDING`, `STATUS_RECEIVED`, `STATUS_WAIVED`.
- Source constants: `SOURCE_BASELINE`, `SOURCE_FUND_CODE`, `SOURCE_AD_HOC`.
- Type constants: `TYPE_DOCUMENT`, `TYPE_TASK`.
- `$fillable` covering every column except `id`/timestamps.
- `$casts`: `received_at => datetime`, `is_orphaned => boolean`.
- Relationships: `communicationLog()`, `receiver()` (alias for `received_by_user_id`), `createdBy()`, `updatedBy()`.
- Scopes: `outstanding()`, `received()`, `waived()`, `notOrphaned()`, `forCommunicationLog(int $id)`.
- State helpers: `isOutstanding()`, `isReceived()`, `isWaived()`, `isOrphaned()`.
- Mutating actions (each takes a `User $actor` parameter for audit, matching `Requirement` convention):
  - `markReceived(User $actor, ?string $notes = null)` — sets status, stamps `received_at` and `received_by_user_id` ONLY IF not already received (preserve first-receipt time on re-mark).
  - `markWaived(User $actor, string $reason)` — sets status to waived, stamps `received_at` to `now()` (waiving is conceptually closure-with-rationale; the receiver stamp marks who closed it), appends the reason to `notes`.
  - `reopen(User $actor)` — sets status back to outstanding, clears `received_at` and `received_by_user_id`, appends a `[REOPENED by X on Y]` line to `notes` (matches `Requirement::reopen()` audit pattern).
  - `markOrphaned(User $actor)` / `unmarkOrphaned(User $actor)` — flip the `is_orphaned` boolean; stamp `updated_by_user_id`. Used by the instantiator's reconciliation, not by user-facing code.

### Step 6: Factory — `IntakeRequirementFactory`

`database/factories/IntakeRequirementFactory.php`. Defaults per Discover Step 5. State methods listed there. Pattern after `RequirementFactory`.

### Step 7: Service — `IntakeRequirementInstantiator`

`app/Services/IntakeRequirementInstantiator.php`. Mirror `RequirementInstantiator`.

```php
class IntakeRequirementInstantiator
{
    /**
     * Idempotent: if a baseline (Application) intake requirement already
     * exists for this log, return it. Otherwise create one as a document-
     * type baseline. If the existing baseline is orphaned (rare —
     * baseline doesn't get orphaned by fund-code reconciliation, but
     * future logic may flip it), un-orphan and return.
     */
    public function baseline(CommunicationLog $log, User $actor): IntakeRequirement
    {
        $existing = IntakeRequirement::query()
            ->where('communication_log_id', $log->id)
            ->where('source_type', IntakeRequirement::SOURCE_BASELINE)
            ->first();

        if ($existing !== null) {
            if ($existing->is_orphaned) {
                $existing->unmarkOrphaned($actor);
            }
            return $existing;
        }

        return $this->insert($log, [
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => 'Application',
            'source_type' => IntakeRequirement::SOURCE_BASELINE,
            'source_template_id' => null,
            'source_label_snapshot' => null,
        ], $actor);
    }

    /**
     * For every FundCodeRequirement template on the fund code WITH
     * phase='eligibility', create one IntakeRequirement on the log.
     * Idempotent on (communication_log_id, source_template_id).
     * Re-anticipating a previously-de-anticipated code un-orphans
     * existing rows instead of duplicating.
     *
     * The phase filter is critical: without it, this instantiator would
     * pull project-execution templates (final inspection sign-off, lien
     * waivers, etc.) into the intake set. The filter is one line but
     * it's the single most important behavior of this method.
     *
     * Note: the baseline is NOT auto-created here. Callers decide
     * whether instantiating fund-code templates also instantiates the
     * baseline — see OQ-3 (39d's job to wire that up).
     */
    public function forFundCode(CommunicationLog $log, FundCode $fundCode, User $actor): Collection
    {
        $templates = $fundCode->requirements()
            ->where('phase', 'eligibility')
            ->ordered()
            ->get();

        // ... idempotency-by-template-id + un-orphan-or-create pattern ...
        // ... copies template->label into source_label_snapshot ...
        // ... reads template->type for the row's type ...
    }

    /**
     * Free-form. source_type=ad_hoc, all source_* nulled. Returns the
     * new row.
     */
    public function adHoc(
        CommunicationLog $log,
        string $label,
        string $type,
        User $actor,
        ?string $notes = null
    ): IntakeRequirement {
        // ...
    }

    /**
     * Reconcile the log's anticipated_fund_codes pivot against a target
     * array of fund_code_ids. For each newly-added code: call
     * forFundCode(). For each newly-removed code: mark every sourced
     * requirement orphaned (do NOT delete — orphan-and-keep policy).
     *
     * Operates inside a transaction so partial reconciliation never
     * leaves the pivot and the requirements drift apart.
     */
    public function reconcileAnticipatedFundCodes(
        CommunicationLog $log,
        array $newFundCodeIds,
        User $actor
    ): void {
        DB::transaction(function () use ($log, $newFundCodeIds, $actor) {
            // ... sync pivot ...
            // ... for added codes: forFundCode() ...
            // ... for removed codes: mark all source_template_id-matching rows orphaned ...
        });
    }

    protected function insert(CommunicationLog $log, array $attributes, User $actor): IntakeRequirement
    {
        // Shared insert path. Sets default status, stamps created_by_user_id
        // and updated_by_user_id from the actor.
    }
}
```

No notification dispatch in 39b — `IntakeRequirementInstantiator` does not call any notification path. That's a 39d/39f decision per OQ-6.

### Step 8: Modify `RequirementInstantiator::forFundCode()` for phase filter

In `app/Services/RequirementInstantiator.php` (line 37-68 at PR-#20 time), add `->where('phase', 'project')` to the template fetch inside `forFundCode()`. One line. The Phase 3A regression test in Step 11 below proves this is behavior-preserving for the existing fleet.

This is the **only** modification to existing Phase 3A code in 39b. Flag it explicitly in the PR description.

### Step 9: `CommunicationLog` model — remove `fundCodes()`, add `intakeRequirements()` and `anticipatedFundCodes()`

In `app/Models/CommunicationLog.php`:

- **Remove** the existing `fundCodes(): BelongsToMany` relation (lines 121-125 at PR-#20 time) along with its docblock if any.
- **Remove** any use-statement that becomes orphaned (likely none — `FundCode` is still referenced by the new relation).
- **Add** two new relations:

```php
public function intakeRequirements(): HasMany
{
    return $this->hasMany(IntakeRequirement::class);
}

public function anticipatedFundCodes(): BelongsToMany
{
    return $this->belongsToMany(
        FundCode::class,
        'communication_log_anticipated_fund_codes',
    )->withPivot('created_by_user_id')->withTimestamps();
}
```

The `scopeSearch` on `CommunicationLog` (lines 422-442 at PR-#20 time) references `fundCodes` via `whereHas('fundCodes', ...)`. **This must be updated** to use `anticipatedFundCodes`. Search behavior is preserved — same fund-code rows, accessed through the new pivot.

### Step 10: Migrate all other call sites of the removed pivot

Use the grep output from Discover Step 3 to drive this. Every reference catalogued as "Migrate" gets its call updated from `->fundCodes()` to `->anticipatedFundCodes()`. Every reference catalogued as "Remove" gets removed with a one-line PR-description note.

The PR may modify Livewire components, Blade views, and tests as part of this cleanup. That is the price of D-2's "no coexistence period" — explicitly authorized despite the "no UI" boundary, because the alternative is a broken build.

### Step 11: Permission seeder (Addendum D-4)

Per Discover Step 4, create or update the chosen seeder to seed:

| Permission | Granted to (v1) |
|---|---|
| `intake_dashboard.view` | `housing_manager`, `admin` |
| `intake_requirements.create` | `housing_manager`, `admin` |
| `intake_requirements.update` | `housing_manager`, `admin` |
| `intake_requirements.delete` | `admin` only |
| `intake_requirements.upload` | `housing_manager`, `admin` |
| `comm_log_pins.manage` | all authenticated |

The `comm_log_pins.manage` grant to "all authenticated" is implemented by attaching it to every non-system role at seed time. Document the role list you walked in the PR.

If you stand up a new `IntakeRequirementsPermissionsSeeder`, chain it from both `DatabaseSeeder` and `RegressionTestSeeder` so test and dev DBs stay in lockstep.

The permissions are seeded **ahead of their consumers**. 39c/39d will wire them into `authorize()` calls and Policy checks. Document this hand-off in the PR.

### Step 12: No controller, no Livewire, no route

Reaffirming the boundary. The cleanup work in Step 10 may *modify* existing UI files; it must not *create* new ones.

## Regression test surface

New directory: `tests/Feature/Regression/IntakeRequirements/`

### Required test files

#### `IntakeRequirementInstantiatorTest.php`

- `baseline()` is idempotent.
- `baseline()` un-orphans an existing orphaned baseline rather than creating a duplicate.
- `forFundCode()` is idempotent on `(log, template_id)`.
- `forFundCode()` copies `template->label` into `source_label_snapshot` at instantiate time, NOT at read time (mutate the template label after instantiate, then read — snapshot must still reflect the original).
- `forFundCode()` reads `template->type` for the row's type.
- **`forFundCode()` filters to `phase='eligibility'`**: instantiate a fund code with both `phase='project'` and `phase='eligibility'` templates; assert only the eligibility templates produce intake rows.
- `adHoc()` creates a free-form row with `source_type=ad_hoc`.
- `reconcileAnticipatedFundCodes()` adding a code instantiates requirements.
- `reconcileAnticipatedFundCodes()` removing a code marks sourced requirements orphaned (does NOT delete).
- `reconcileAnticipatedFundCodes()` re-adding a previously-removed code un-orphans existing rows without duplicating.
- `reconcileAnticipatedFundCodes()` runs in a transaction (rollback test: induce a downstream failure mid-reconcile, assert pivot and requirements are both untouched).

#### `IntakeRequirementModelTest.php`

- `markReceived()` stamps once and preserves on re-mark.
- `markWaived()` requires a reason and appends to notes.
- `reopen()` clears received_at, appends `[REOPENED ...]` audit line.
- `markOrphaned()` / `unmarkOrphaned()` flip the boolean and stamp updated_by.

#### `SchemaInvariantsTest.php`

- Deleting a `CommunicationLog` cascade-deletes its `intake_requirements` rows.
- Deleting a `CommunicationLog` cascade-deletes its `communication_log_anticipated_fund_codes` rows.
- Deleting a `FundCode` cascade-deletes pivot rows but does NOT touch the `intake_requirements.source_template_id` column (no FK there by design; template snapshot survives template deletion).
- `(communication_log_id, fund_code_id)` unique constraint on the pivot table rejects duplicates.
- `communication_log_fund_code` table no longer exists in the schema (use `Schema::hasTable` to assert false).

#### `RequirementInstantiatorPhase3ARegressionTest.php` (Correction 4)

Protects existing Phase 3A behavior against the one-line modification in Step 8.

- **`forFundCode_returns_only_project_phase_templates`**: instantiate a fund code with both `phase='project'` and `phase='eligibility'` templates. Assert `RequirementInstantiator::forFundCode()` produces only project-phase Requirement rows. Cross-verify: count of returned rows equals count of project-phase templates on the fund code.
- **`forFundCode_excludes_eligibility_phase_templates`**: same setup; explicitly assert no Requirement was created from an eligibility-phase template (verify by template-id lookup against the produced rows).
- **`forFundCode_unchanged_when_all_templates_are_project_phase`**: backwards-compatibility case. Set up a fund code with only project-phase templates (mirrors what every existing fund code looks like after Step 1's backfill). Assert `forFundCode()` produces identical row count and labels to the pre-migration baseline.

#### `PermissionSeedingTest.php` (Addendum D-4)

- Each of the six permissions exists in the database after seeding (assert presence by name).
- Role grants match the table in Step 11 (assert the `housing_manager` role has 5 of the 6; assert `admin` has all 6; assert `comm_log_pins.manage` is granted to every non-system role).
- Re-running the seeder is idempotent: row counts on `permissions`, `role_has_permissions`, and `roles` do not change after a second run.

#### `TagPivotMigrationTest.php`

- Pre-migration: insert N rows into `communication_log_fund_code` (use the pre-Step-4 schema by running migrations up to Step 3 only, then inserting fixture data).
- Apply migration Step 4.
- Post-migration: assert N rows exist in `communication_log_anticipated_fund_codes`, every `(communication_log_id, fund_code_id)` pair matches, `created_at` is preserved, the old table no longer exists.
- This test is the hardest to write because it requires partial-migration control. Use `Artisan::call('migrate', ['--path' => ...])` to step through. If that proves too brittle, fall back to asserting the production migration's `up()` method by direct invocation against a manually-prepared schema.

### Test group

All tests carry the `->group('regression')` annotation. They run under `./bin/run-regression.sh`.

### What NOT to test in 39b

- No upload behavior (39c).
- No dashboard behavior (39d).
- No promotion behavior (39f).
- No notifications.
- No HTTP / Livewire layer for new components — though tests for existing call-site cleanup in Step 10 should run as part of the existing component test suites where they exist.

## CLAUDE.md updates

After implementation, add a short subsection under `## Database Schema` → `### Core Models & Relationships` documenting the two-layer requirement system:

> **Intake vs. Project requirements (P-39).** Two parallel requirement
> systems share the `fund_code_requirements` template table but maintain
> independent lifecycles:
>
> - `intake_requirements` — intake-phase. Rooted on `CommunicationLog`.
>   Templates filtered to `phase='eligibility'`. Tracked while qualifying
>   an applicant; ends at promotion via `convertToProject()`.
>   Statuses: outstanding / received / waived.
>   Lifecycle managed by `App\Services\IntakeRequirementInstantiator`.
> - `requirements` — project-execution-phase. Rooted on `HousingJob`.
>   Templates filtered to `phase='project'`. Tracked while delivering
>   the housing project. Statuses: outstanding / complete /
>   not_applicable. Lifecycle managed by
>   `App\Services\RequirementInstantiator`.
>
> Both consume `fund_code_requirements` as templates. The `phase` column
> on that table determines which system instantiates which row. Intake
> requirements stay attached to the Comm Log after promotion as
> historical record; they do NOT migrate to the new `HousingJob`.
>
> Fund-code association on `CommunicationLog` is via the
> `anticipatedFundCodes()` relation (table:
> `communication_log_anticipated_fund_codes`). The legacy
> `communication_log_fund_code` tag pivot was dropped in P-39b.

Do NOT document open questions in CLAUDE.md — those belong in the PR description and the scope doc.

## PR description

The PR description must include:

1. **Discover phase output**: divergence-vs-Phase-3A summary, `fund_code_requirements.type`/`phase` confirmation, the full grep catalogue of `communication_log_fund_code` / `->fundCodes()` references (Discover Step 3), permission seeder choice rationale (Discover Step 4), factory defaults (Discover Step 5).
2. **Migration sequence list** with the four migrations in order, and confirmation that `php artisan migrate --pretend` applies cleanly to the test DB.
3. **Test count**: cases per test file.
4. **Explicit Phase 3A modification call-out**: the one-line `->where('phase', 'project')` addition in `RequirementInstantiator::forFundCode()`, with a pointer to `RequirementInstantiatorPhase3ARegressionTest.php` as the behavior-preservation proof.
5. **Permission seeding ahead of consumers**: state that 39c/39d will consume the permissions; this PR only seeds.
6. **`communication_log_fund_code` migration**: row-count parity from the production-style migration. Acknowledge the `INSERT IGNORE` behavior and the parity assertion.
7. **`./bin/run-regression.sh --filter=Intake` result.**
8. **`./bin/run-regression.sh --filter=Phase3A` result** (for the new `RequirementInstantiatorPhase3ARegressionTest`).
9. **`./bin/run-regression.sh` full result.** Acknowledge the known `ViewModeTest` date-snapshot drift unless someone has fixed it.
10. **`P39_SCOPE_ADDENDUM.md` reminder**: note in the PR description that the addendum needs to land in the repo. Either include it in this PR (preferred) or call out the follow-up.

## Acceptance criteria

- All four migrations land cleanly and `php artisan migrate --pretend` against the test DB shows no errors.
- `fund_code_requirements.phase` column exists with default `'project'` and existing rows backfilled.
- `intake_requirements` and `communication_log_anticipated_fund_codes` tables exist with the schemas above.
- `communication_log_fund_code` table no longer exists in the schema.
- Every grep-catalogued reference to the old pivot has been migrated, removed (with rationale), or retained (with rationale).
- `IntakeRequirement` model has every column in `$fillable`, sensible `$casts`, all status/source/type constants, and the documented relations / scopes / mutating actions.
- `IntakeRequirementFactory` exists with sensible defaults and state methods.
- `IntakeRequirementInstantiator::forFundCode()` filters templates by `phase='eligibility'`.
- `IntakeRequirementInstantiator` is idempotent on `baseline()` and `forFundCode()`.
- Reconciliation orphans on removal and un-orphans on re-addition — never deletes.
- Reconciliation runs inside a transaction.
- `RequirementInstantiator::forFundCode()` filters templates by `phase='project'`.
- All six P-39 permissions are seeded with the documented role grants.
- `CommunicationLog` has `intakeRequirements()` and `anticipatedFundCodes()` relations. The old `fundCodes()` relation is removed.
- Cascade deletes work for both new tables on `CommunicationLog` deletion.
- All regression tests pass, including the new Phase 3A regression suite.
- No new Livewire / route / controller files. Existing UI call-site updates in Step 10 are limited to the minimum required to keep the build green.

## Out of scope (explicit)

- **Upload UI** (39c).
- **Dashboard** (39d), including the admin-form selector for the new `phase` column on `fund_code_requirements`. Hand off to 39d or a small dedicated follow-up; document the hand-off in the PR.
- **Pinning** (39e).
- **Promotion integration** (39f).
- **Notifications.** Including the OQ-6 "ready to promote" notification.
- **Baseline-instantiation triggering.** `baseline()` is a method; *when* it fires is 39d (per OQ-3 / recommended option b).
- **Spatie media on `IntakeRequirement`.** 39c adds `HasMedia` and `HasMediaAuthorization`. Leave the model media-free in 39b.
- **Any new UI rendering of intake data on the Comm Log entry detail.** That's 39c/39d.
- **Backfill of intake requirements from existing CommunicationLog entries.** Greenfield only — no applicant has an intake requirement until something explicitly instantiates one. (Distinct from the tag-pivot backfill in Step 4, which IS in scope.)
- **The duplicate `private_media` disk key in `config/filesystems.php`.** This was fixed as an incidental in P-39a (PR #20). Do NOT re-apply.

## Constraints

- **Discover phase must complete before implementation.** Particularly Step 1 (re-read Phase 3A), Step 3 (full grep catalogue), Step 4 (seeder choice). Document findings in the PR.
- **Mirror Phase 3A patterns unless you have a documented reason to diverge.** Every divergence appears in the PR description with rationale.
- **No new file storage / Spatie media work.** That's 39c.
- **No notifications.** That's 39d/39f.
- **Single PR.** All 39b artifacts ship together, including the four migrations, the model, the service, the seeder, the cleanup, and the tests.
- **Branch naming.** `p39b/intake-requirements-data-layer` off `main`. Do not chain onto `p39a/media-authorization` — that PR may still be in review and chaining creates a merge dependency.
- **DB safety.** Per CLAUDE.md, NEVER run `migrate:fresh`, `migrate:reset`, or `db:wipe` against any DB other than the test DB. Verify `.env` and `phpunit.xml` before running migrations. The four new migrations include one DESTRUCTIVE step (the `dropIfExists('communication_log_fund_code')` in migration 4). Stick to the test DB for verification during 39b. Production rollout is a separate concern — flag it in the PR description so it's not forgotten.
- **Respect locked architectural decisions** from `CLAUDE.md` and the OQ resolutions baked into "Locked decisions" above. Flag any apparent conflict before silently resolving it.
- **The addendum must land in the repo.** Either include `P39_SCOPE_ADDENDUM.md` in this PR or call out the follow-up explicitly in the PR description. Do not let the addendum stay un-versioned past this sub-item — D-1/D-2/D-4 are load-bearing decisions and need a citable on-disk source for future contributors.
