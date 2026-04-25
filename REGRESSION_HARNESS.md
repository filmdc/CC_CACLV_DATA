# Regression Test Harness (P-33)

A repeatable harness that pins the current behavior of the Housing module before
Phase 1 feature work begins. Every test is tagged `->group('regression')`.

## Running

```bash
# Fast path — run before every merge
./bin/run-regression.sh

# Equivalent explicit form
php artisan test --group=regression
```

Expected wall-clock: **~60–75 seconds** from cold (the first test pays for
`RefreshDatabase` migrating the `CC_CACLV_DATA_test` database; subsequent
tests are ~0.2s each).

### Useful flags

```bash
./bin/run-regression.sh --filter=SmokeRoutes         # one test file
./bin/run-regression.sh --stop-on-failure            # bail on first fail
REGRESSION_UPDATE_SNAPSHOTS=1 ./bin/run-regression.sh # rewrite all snapshots
```

## When to run

- **Before every merge** to `main`, especially PRs that touch the Housing
  module, its Livewire components, or its blade views.
- **After `composer update`** — framework upgrades can change Livewire render
  output (wire:* attribute formatting, etc.) in ways that don't show up
  anywhere else.
- **After every migration** that touches `housing_jobs`, `project_budgets`,
  `project_expenses`, `project_milestones`, `communication_logs`, `fund_codes`,
  `properties`, `contractors`, `contracts`, `participants`, `households`.
- **After pulling changes from another developer** — run once to confirm the
  baseline is still green on your box before layering new work.

## Test DB & safety

The test DB is `CC_CACLV_DATA_test` on the **same MySQL server** as prod's
`CC_CACLV_DATA`. Routing is handled by `phpunit.xml` — it overrides
`DB_DATABASE`, `APP_ENV`, etc. at PHPUnit/Pest level only. Other artisan
commands (`migrate:fresh`, `db:seed`, `db:wipe`) read `.env` directly and
**will hit the configured DB** — see `MEMORY.md` for the full safety rules
around destructive artisan commands. Using `./bin/run-regression.sh` is safe.

There is **no `.env.testing`** by deliberate design. Do not try to fake it;
`--env=testing` silently falls back to `.env` if the file is missing.

## Re-seeding the test DB

The suite uses `RefreshDatabase` globally (`tests/Pest.php`) — every feature
test runs inside a transaction that rolls back on completion. You do not need
to manually reset the test DB. The first test run migrates it; all subsequent
runs reuse the schema.

If something truly goes wrong (e.g. a migration added after the last run
fails mid-transaction), drop and recreate:

```bash
mysql -e "DROP DATABASE CC_CACLV_DATA_test; CREATE DATABASE CC_CACLV_DATA_test;"
```

The next test run will re-migrate.

## HTML snapshots

Three committed snapshots live under `tests/Support/__snapshots__/`:

| File | Test | Pre-image of |
| --- | --- | --- |
| `projects_list.html` | Projects/ListViewBaselineTest | P-11 |
| `milestone_display.html` | Milestones/DisplayBaselineTest | (structural baseline) |
| `budget_categories_with_contract.html` | Budget/CategoryListBaselineTest | P-22 |

### Updating a snapshot after an intentional UI change

```bash
REGRESSION_UPDATE_SNAPSHOTS=1 ./bin/run-regression.sh
# review the diff
git diff tests/Support/__snapshots__/
# commit if correct
git add tests/Support/__snapshots__/<file>.html
```

The env var force-rewrites every snapshot. If you only want to rewrite one,
delete that single file first then run the suite normally — the helper
auto-creates missing snapshots and passes.

### Snapshot normalization strip-list

The `SnapshotHelper::normalize()` method rewrites the following in both the
committed snapshot and the live render before comparison. Keep this list in
sync with any edits to `tests/Support/SnapshotHelper.php`.

- `wire:id="..."`, `wire:snapshot="..."`, `wire:effects="..."`,
  `wire:initial-data="..."`, `wire:socket="..."` — Livewire regenerates
  these on every render.
- `<input type="hidden" name="_token" value="...">` — CSRF token input.
- `<meta name="csrf-token" content="...">` — CSRF meta tag.
- `data-csrf="..."`, `x-csrf-token="..."` — attribute-form CSRF refs.
- ISO-8601 timestamps (`YYYY-MM-DDTHH:MM:SS[.fff][Z|±HH:MM]`) → `__TIMESTAMP__`.
- Resource URLs with auto-increment IDs:
  - `/project/\d+/` → `/project/__ID__/`
  - `/participant/\d+\b` → `/participant/__ID__`
  - `/property/\d+\b` → `/property/__ID__`
  - `/contractor/\d+\b` → `/contractor/__ID__`
  - `/contract/\d+\b` → `/contract/__ID__`
- `<option value="\d+">` — numeric FK dropdown values → `__ID__`. String
  enum values like `<option value="pending">` are preserved.
- `wire:click="method(\d+)"` — first numeric arg is almost always a DB id.
- URL fragments `#word-\d+` (e.g. `#milestone-42`) → `#word-__ID__`.

After normalization, trailing whitespace is stripped from each line and runs
of 3+ blank lines collapsed to 2.

### Why normalization is needed

MySQL's auto-increment counter does NOT reset between test transactions.
Each test runs inside a `RefreshDatabase` transaction that rolls back, but
the counter keeps advancing. As a result, the `projects_list.html` snapshot
captured in isolation would have Program ID = 1, but when run as part of the
full suite (after other tests have inserted and rolled back rows) it may be
Program ID = 17. Normalization strips DB-id-bearing markup so the snapshot
compares only the structure.

## Fixture dataset

`database/seeders/RegressionTestSeeder.php` produces a deterministic fixture
exercised by every regression test via `$this->seed(RegressionTestSeeder::class)`
in the per-file `beforeEach` hook. All names / addresses are obviously
fictional (`1 Test Lane`, `Jane Fixture`, etc.).

Shape:

- 1 Program, 2 Contracts (A linked to 2 fund codes + B), 3 Fund Codes.
- 3 Contractors (Acme / Beta / Gamma Fixture …).
- 12 Households, 12 head-of-household Participants, 12 Properties (`1 Test Lane`
  through `12 Test Lane`).
- 5 Communication Logs with variation: no fund codes / single fund code /
  multiple fund codes / converted-to-project / already-converted-no-fund-code.
- 5 Housing Jobs: HJ-1 (pending, just converted), HJ-2 (in_progress, 3
  milestones mid-workflow), HJ-3 (completed, 4 milestones done), HJ-4
  (in_progress, 2 budget categories + 3 Roofing expenses summing to 7000.00
  for running-total math verification), HJ-Template (is_template=true,
  "Standard Roofing Workflow").

## Pre-existing latent issues

Surfaced during P-33 construction, not in scope to fix:

- `/fund-codes` returns HTTP 500 when any fund code has `contract_id` set —
  `resources/views/livewire/fund-code-management.blade.php:92` references
  `route('contracts.show', …)` but the registered route name is
  `contract.show` (singular). Production has 7 fund codes all with
  `contract_id=null` so the broken branch never fires there; the P-33
  seeder is the first place a contract-linked fund code exists, which is
  why this surfaces now. Tracked in `PHASE_0_NOTES.md`.

See `PHASE_0_NOTES.md` for the full list of pre-existing test failures
(ParticipantImportTest, ServiceManagementTest, Settings/ProfileUpdateTest)
which are NOT part of P-33's scope.

## Adding a new regression test

1. Drop the file under `tests/Feature/Regression/<Area>/<Something>Test.php`.
2. Tag every `it`/`test` with `->group('regression')`.
3. If the test needs DB state, seed with
   `$this->seed(RegressionTestSeeder::class)` in `beforeEach`.
4. If the test acts as an admin user, use
   `Tests\Support\RegressionHelpers::adminUser()` — it grants every
   permission the housing module's mount-level `authorizeAction` checks.
5. For HTML snapshots, call
   `Tests\Support\SnapshotHelper::assertHtmlMatchesSnapshot('my_thing', $html)`
   — the file is auto-created on first run and committed afterward.
