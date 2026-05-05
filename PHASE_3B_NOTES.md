# Phase 3B notes — notifications, Comm Log filters/views, budget hardening

Phase 3B closes out four punch-list items:

| ID | Area | What |
|----|------|------|
| P-20 | Workflow | Automatic notifications on requirement state changes and upstream uploads. In-app + email. |
| P-03 | Comm Log | Multi-select filters (fund code, follow-up status, conversion status) + full URL persistence. |
| P-04 | Comm Log | Two view modes (table / card) with URL-only persistence. |
| P-23 | Budget | Math hardening + over-budget banner + audit ledger + admin recipient list + modification email. |

Implemented in 7 sequenced commits on `phase-3b/notifications-and-quality-of-life`:

- Step 1 — `infra:` queue worker systemd unit + ShouldQueue retrofit (P-20)
- Step 2 — `feat:` mail channel + opt-outs + 3A subscriber refinement (P-20)
- Step 3 — `feat:` upstream-upload trigger via milestone graph (P-20)
- Step 4 — `feat:` Comm Log multi-select filters + full URL persistence (P-03)
- Step 5 — `feat:` Comm Log table/card view modes (P-04)
- Step 6 — `fix:` budget math observer recomputes both old and new tuples (P-23 part A)
- Step 7 — `feat:` over-budget banner + ledger + recipients + email (P-23 part B)

Regression suite: **185 → 255 passing (+70 cases, +190 assertions)** across the seven steps.

---

## 1. Deploy story — queue worker

The single biggest deploy item. Without the queue worker running, every Phase 3B notification will block the user-facing request on Office 365 SMTP latency (typically 1–4s, sometimes 10s+).

### One-time enable on prod

```bash
sudo cp deploy/systemd/cc_caclv_data-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now cc_caclv_data-queue.service
```

Verify with `sudo systemctl status cc_caclv_data-queue.service`. Logs stream via `sudo journalctl -u cc_caclv_data-queue.service -f`.

### Pre-deploy queue inspection (do this BEFORE the first enable)

The Phase 3A audit found a latent issue: existing notification classes (`TaskAssignedNotification`, `DocumentAssignedNotification`, `ApprovalRequestedNotification`, `ContractReportReminderNotification`) already implemented `ShouldQueue` but no worker has been running. Pending jobs may have piled up in `jobs`, and failed-to-process jobs may sit in `failed_jobs`.

```bash
# Inspect before enabling. Don't blindly flush — report state to David first.
mysql -e 'SELECT COUNT(*) FROM jobs;' CC_CACLV_DATA
php artisan queue:failed | head
```

If there's a meaningful backlog, decisions are: drain naturally (the worker will process pending jobs once enabled — fine for a small queue), retry failed (`php artisan queue:retry all`), or flush failed (`php artisan queue:flush`). David's call.

### After every code deploy

Workers cache the bootstrapped Laravel application. New code does not take effect until the worker restarts.

```bash
# Either of these is fine; pick one and stay consistent.
sudo systemctl restart cc_caclv_data-queue.service
# OR
php artisan queue:restart
```

Add this to the standard deploy runbook. Forgetting it produces "the new feature seems half-deployed" symptoms that are confusing to debug.

### Mailer characterization

`MAIL_HOST=smtp.office365.com:587` (external SMTP). `QUEUE_CONNECTION=database`. The worker drains the `jobs` table via the database driver — no Redis or extra infrastructure required.

---

## 2. 80% threshold heads-up for Kaitlyn

**Yellow indicator on budget categories now triggers at >=80% spent (was >90%).** Deliberate UX change; it's easier to act on a soft warning when there's still 20% of the budget to spend than when only 10% remains.

Worth mentioning in the deploy announcement to Kaitlyn so the new yellow on previously-green categories doesn't generate "did something break?" tickets. Categories at 80%–89% will switch from green to yellow at deploy time; nothing's wrong, the threshold is just earlier.

If usage suggests 80% is too noisy or too late, the threshold is one line in `resources/views/livewire/project-management.blade.php` (look for `>= 80`); could also be moved to `config/housing.php` if it gets contested.

---

## 3. Pre-existing queryString-loss bug fix (Step 4 side effect)

Step 4 incidentally fixed a pre-existing UX bug: `dateFrom`, `dateTo`, `directionFilter`, and `followUpFilter` were not in the Comm Log component's `queryString` array before this phase. Refreshing or sharing a filtered view silently lost them. All four are now bookmarkable as part of P-03's queryString expansion.

If users had bookmarks pointing at filtered comm-log views before this deploy, those bookmarks worked partially (some filters captured, some lost). After this deploy, the same bookmark URLs will round-trip the full filter state, but new bookmarks made post-deploy will need to be re-saved if they want the new fields included.

---

## 4. URL-only view-mode persistence (Step 5 decision)

The Comm Log table/card toggle persists in the URL only — every fresh login starts at table view. **Explicit choice with a cheap upgrade path.**

If Kaitlyn requests per-user persistence, it slots cleanly into the `notification_preferences` JSON column already created by Step 2 (Phase 3B Step 2 added that column with the `AsArrayObject` cast). Add a fourth key (e.g., `default_comm_log_view`) and read it in `mount()`. One-line change to the dispatch read; no schema work, no new migration.

The pragma is "every fresh tab starts known-state, sharable URLs work everywhere" — appropriate for an internal app where multi-user use of a single device isn't unusual.

---

## 5. Upstream-upload trigger site discipline

The Phase 3B Step 3 trigger fires from exactly **one** site: `ProjectManagement::saveRequirementCompletion`. It's the only path in the codebase that attaches files to a `Requirement` (verified via grep against `addMedia` + `toMediaCollection('attachments')`).

> **If a future phase adds an upload-without-completion flow** (e.g., "attach progress photos to an outstanding requirement"), the trigger must be inserted there too. Otherwise downstream owners silently miss notifications.
>
> Grep for `notifyDownstreamOfUpload` to find all trigger sites — there is currently exactly one.

The Spatie `MediaHasBeenAdded` event listener pattern was deliberately rejected for fragility (per-file fire on multi-file uploads, dedup at listener level too risky under refactor). The Livewire-action-level `notifyDownstreamOfUpload($actor)` call is single-fire by construction.

---

## 6. Spatie test-helper gotcha

`UploadedFile::fake()->create($name, $sizeKb, $mime)` produces a **zero-byte** fake file that Spatie's media-library rejects with `mime: application/x-empty`, regardless of the `$mime` argument. The argument is metadata; Spatie sniffs the actual bytes.

Use `UploadedFile::fake()->createWithContent($name, $bytes)` for tests that need media surviving Spatie's MIME validation. A 1-line PDF magic header (`"%PDF-1.4\n" . str_repeat('x', 1024)`) is enough.

Step 7's budget tests don't directly handle media, but if any Phase 4+ test fixture needs an attached document (e.g., proof-of-purchase), it'll hit this same wall.

---

## 7. Step 6 audit findings — three deferred items

While auditing the budget math paths in Step 6:

- **`ProjectBudget::addExpense(float $amount)` is dead code.** Zero callers anywhere (verified via grep across `app/`, `tests/`, `database/`). Marked `@deprecated` in PHPDoc with a Phase 3B Step 6 note. **Deletion is deferred to a follow-up cleanup PR** for blast-radius separation. The method does in-memory delta math — exactly the drift pattern `recalculateSpent()` exists to prevent — so leaving it in place even @deprecated is mildly risky if a future dev calls it.

- **No `SoftDeletes` trait on `ProjectExpense`.** Confirmed via migration audit. The expense observer doesn't need `restoring` or `forceDeleted` hooks today. **If a future phase ever adds soft deletes to ProjectExpense**, a `restored` observer hook is required: it should call `recomputeBudgetForCurrentTuple()` to undo the under-counting that the original `deleted` hook performed.

- **Fund-code-shared-tuple aggregation limitation.** When two `ProjectBudget` rows on the same project share a `(housing_job_id, category)` tuple but different `fund_code_id`, both pull the same expense sum (because `ProjectExpense` has no `fund_code_id` column — it joins by category name only). This is by-design today; the budget category name is the canonical join key. Re-flagged from earlier phases. Fix would be schema work + UX rethink for how expenses pick a fund — out of Phase 3B scope.

---

## 8. Strict vs. loose follow-up semantics — don't unify

`scopePendingFollowUp` on `CommunicationLog` defines "pending" loosely: any future-or-past follow-up that's not yet completed. The dashboard count badge uses this scope and depends on the loose definition (it counts both pending and overdue as "still outstanding").

Phase 3B Step 4's multi-select filter needs **stricter** semantics: "pending" means future-or-today AND not done; "overdue" means past AND not done; "completed" means done. Three orthogonal options.

The strict logic lives **inline in `CommunicationsLogManagement::render()`** for the multi-select filter only. The loose `scopePendingFollowUp` is **left intact** for the dashboard count.

> Do not unify. Two distinct purposes deserve two distinct semantics. Mutating the scope to satisfy the multi-select would silently break the count badge.

---

## 9. `modified_by_user_id` is RESTRICT, not CASCADE

The `budget_modifications.modified_by_user_id` foreign key uses Laravel's default RESTRICT behavior. **Hard-deleting a user with budget-ledger entries is blocked** at the database level.

This is intentional for audit-trail preservation. Use **deactivation** (`users.active = false`) instead of hard delete — that path always works and preserves the audit history.

For comparison: `over_budget_email_recipients.user_id` is CASCADE because the recipient list is administrative state, not historical record. Deactivating a user removes them from the list automatically; no audit value is lost.

Heads-up for David: if a deactivated user's name in past ledger entries needs to disappear from view (e.g., privacy request), the path is to anonymize the user record (rename to "Removed user") rather than delete it.

---

## 10. `budget_categories_with_contract.html` — no refresh needed

The Plan called for refreshing this snapshot to capture the new 80% threshold + the "Modified (N)" indicator markup. **In practice no refresh was needed.**

The HJ-4 fixture in `RegressionTestSeeder` has Roofing budgeted=15000 / spent=7000 (47%) and Plumbing budgeted=8000 / spent=0 (0%). Neither hits the 80% threshold, neither is over-budget, neither has any `BudgetModification` rows. All three new visual states are dormant for this fixture.

The snapshot still passes against the committed baseline. Honest reporting — the data shape didn't require the refresh that the markup change implied.

If a future test wants to pin the new visual states, build a separate snapshot fixture rather than retrofitting HJ-4 (which serves a different purpose: pinning the running-total math).

---

## 10b. Snapshot stability across midnight rollover (CI lesson)

The first CI run on PR #18 surfaced an instructive snapshot failure on `communications_log_card_view.html`. The test captured the snapshot locally on May 4, then CI ran at 11:48 UTC on May 5; the rendered "Created by Unknown on May 04, 2026" line on each log entry shifted to "May 05, 2026" and the snapshot diff'd by exactly that text.

The cause: the snapshot test seeded fixtures whose `created_at` defaulted to `now()`, and that timestamp leaked through into a rendered date format that the SnapshotHelper's `__TIMESTAMP__` regex didn't match (the rule targets ISO 8601, not human-formatted "May 04, 2026").

**Lesson:** any snapshot that captures a rendered timestamp — even a date-only one — needs `Carbon::setTestNow()` (or fixture date pinning via `->state(['created_at' => '...'])` if the timestamp comes from a model column rather than `now()` at render time). The Phase 3A "indicator math reads now()" pattern only handles relative-date math (the buckets stay stable across runs once `now()` is pinned), not displayed dates that fall through to a human-readable strftime call.

The fix in `tests/Feature/Regression/CommunicationsLog/ViewModeTest.php` was a one-line `Carbon::setTestNow('2026-05-04 12:00:00')` at the top of the snapshot test plus a `Carbon::setTestNow()` reset at the end (so other tests in the file aren't time-pinned). Mirror this pattern in any future snapshot test that includes "Created by/on" or similar rendered date lines.

---

## 11. Snapshot scoping via preg_match

Used in `BudgetModifyModalSnapshotTest` to capture **just the modal block**, not the full project-management page render which includes faker-generated participant address, name, and job description text that drifts every run.

```php
$html = $component->html();
if (preg_match('#<div [^>]*data-budget-mod-modal[^>]*>.*?</div>\s*</div>\s*</div>#s', $html, $m)) {
    $html = $m[0];
}
SnapshotHelper::assertHtmlMatchesSnapshot('budget_modify_modal', $html);
```

The pattern is available for future tests when full-page faker-driven fixture drift is the issue and the actual artifact-of-interest is a self-contained block. Pin to a stable `data-*` attribute on the wrapping element.

---

## 12. 3A subscriber-semantics rewrite — behavior change Kaitlyn will notice

Phase 3A's `RequirementCompletedNotification` was a placeholder: it pinged the project manager on every requirement completion across her project. Phase 3B Step 2 refines that to **the requirement's owner-of-record at completion time**, not the project manager.

Three `CompletionGatingTest` cases were rewritten to match. The project-owner-on-every-completion mechanic is **intentionally removed**, not extended.

Behavior change Kaitlyn will see post-deploy:

- **Fewer notifications**: she stops getting pinged when Tony (or any non-her owner) completes their own requirements.
- **More relevant notifications**: she still gets pinged on requirements she personally owns when someone else completes them, AND on the new over-budget modification events when she's on the recipient list.

The net effect should be a quieter, more meaningful bell icon. Worth surfacing in the deploy announcement so the change isn't perceived as "the system stopped working."

---

## Configuration David handles post-deploy

Three discrete tasks after the migrations run:

1. **Enable + start the queue worker** — see Section 1 above. Without this, every notification queues and never delivers.

2. **Populate the over-budget recipient list** at `/admin/over-budget-recipients`. Empty post-migration — until at least one recipient is added, budget modifications are recorded to the audit ledger but no emails go out.

3. **Grant `over_budget_recipients.manage` permission** to the admin role explicitly. The migration creates the permission row but does NOT auto-attach to any role (mirrors the 3A `housing_dashboard.view` pattern). Without this grant, even the admin role can't reach `/admin/over-budget-recipients`.

Optional fourth item: **announce the 80% threshold change** to Kaitlyn so previously-green categories that flip to yellow on deploy don't generate confused tickets.

---

## Configuration left for future phases (intentionally deferred)

Items noticed during 3B implementation that are NOT in Phase 3B scope and have no current plan:

- **`ProjectBudget::addExpense()` deletion.** Marked `@deprecated`; deletion stays in a follow-up PR for blast-radius separation. (See Section 7.)

- **Per-user view-mode default for the Comm Log toggle.** Slot into `notification_preferences` JSON if requested; one-line dispatch change. (See Section 4.)

- **80% threshold configurability.** Currently hardcoded; move to `config/housing.php` if usage warrants. (See Section 2.)

- **`tagFilter` multi-select** on the Comm Log. Step 4 deliberately left it single-select (not in P-03's explicit scope). One-line change to convert if Kaitlyn requests it.

- **Transitive milestone-dependency traversal** for the upstream-upload trigger. Phase 3B does direct dependents only. One-line BFS swap if a use case emerges. (See Section 5.)

- **`milestone_communications` table activation.** Dormant pre-3B and still dormant. Activation requires deliberate scoping (UI to populate, query path to surface). Don't activate in passing.

- **Per-requirement dependency model** (separate from milestone-graph dependencies). Phase 3B's upstream-upload trigger uses the existing milestone graph. If a real per-requirement use case emerges (e.g., "this requirement waits on that requirement specifically, regardless of milestones"), surface before building schema.

---

## Open follow-ups worth tracking

- Audit `jobs` and `failed_jobs` tables on prod a few days post-worker-enable. If `failed_jobs` is growing, investigate; if not, the worker is healthy.

- After 1–2 weeks of use, ask Kaitlyn if the 80% threshold is the right friction point. If she's silently ignoring yellow indicators, the threshold may be too noisy; if she's surprised by red banners with no prior warning, it may be too late.

- After 1–2 weeks of use, ask Kaitlyn whether per-user view-mode persistence on the Comm Log would be useful. If yes, slot it into the existing JSON column.

- The dead-code `ProjectBudget::addExpense()` deletion — whoever cleans it up should also grep for the `@deprecated` PHPDoc comment in case anyone else added a marker.

---

## Notes relevant to Phase 4 (templates) and Phase 5 (intake forms / Tony-Peter forms)

Three things noticed during Phase 3B implementation that future phases will care about:

- **Notification preferences scaffolding is in place.** The `notification_preferences` JSON column on `users` (Step 2) can absorb additional categories trivially. Phase 4 / 5 should add new categories there rather than building a parallel preference system.

- **The milestone-graph traversal helper (`MilestoneGraph`) is single-hop today.** If a Phase 4 templates feature needs transitive dependents (e.g., "warn the project manager if she's saving a template that creates a dependency cycle"), the graph helper should grow rather than spawning a duplicate.

- **The upstream-upload trigger has exactly one trigger site today.** Phase 5's intake forms (per the punch-list) may add new file-attach paths to a `Requirement`. **Insert `notifyDownstreamOfUpload($actor)` at every new attach site** or downstream owners silently miss notifications. (See Section 5.)
