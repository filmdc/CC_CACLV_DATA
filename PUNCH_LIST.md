# CC_CACLV_DATA — Housing Module Improvement Punch List

| Field | Value |
|---|---|
| **Source** | Working session, April 21, 2026 |
| **Repo** | `github.com/filmdc/CC_CACLV_DATA` |
| **Live** | `10.55.0.43` / `data.caclv.org` |
| **Last updated** | April 27, 2026 |

---

## Executive summary

The housing project module is in active use but the manager is hitting friction in three areas:

1. Navigating and finding records as the dataset grows.
2. Keeping fund-code assignments and their downstream requirements visible.
3. Handing work off to and from Construction (Peter, Tony) without manually re-checking every file.

This punch list captures every actionable improvement raised in the working session, organized by area and prioritized for staged delivery. Personal/side conversations and the unrelated Max Way records search have been excluded. A small number of items (P-34, P-35) are process/input dependencies rather than code work, but are listed because they unblock the rest of the list.

## Themes

- Reduce data-entry friction (search, filters, duplicate detection, tighter rows, mobile intake).
- Make fund codes a first-class concept with their own requirements and reminders.
- Move from a single project list to a milestone-driven personal work queue (Encompass-style).
- Decouple the housing module from org-wide lists (Contracts, CSBG Programs) where they are blocking the manager.
- Add a mobile-first intake form so applicants without printers/scanners can apply directly.
- Bake in a regression-test pass and a weekly check-in so usability issues surface early.

## Punch list

Priority: **High** = blocks daily work or unblocks other items. **Medium** = clear quality-of-life gain. **Low** = nice-to-have. Effort: **S** ≈ <½ day, **M** ≈ 1–3 days, **L** ≈ 3+ days.

| ID | Item | Area | Priority | Effort |
|---|---|---|---|---|
| P-01 | Add duplicate-entry detection on the Communications Log: warn the user when a property/address being added already exists. | Comm Log | High | S |
| P-02 | Add full-text search on the Communications Log so any record can be located by address, name, or fund code without scrolling pages. | Comm Log | High | S |
| P-03 | Add filters on the Communications Log (by fund code, milestone, status, date range). | Comm Log | High | M |
| P-04 | Add multiple view modes (compact table view + card/detail view) so the user can fit more rows on screen and drill in when needed. | Comm Log | Medium | M |
| P-05 | Build a dedicated Fund Code reference list (independent of the Contracts database, since not all required fund codes exist there, e.g. MA1000, F013). | Fund Codes | High | M |
| P-06 | Allow each Fund Code record to store its requirements/checklist (special intake forms, environmental review steps, etc.). | Fund Codes | High | M |
| P-07 | When a fund code is selected on a project, surface its requirements to the user as an in-app reminder/prompt. | Fund Codes | High | M |
| P-08 | Support multiple fund codes per project (many-to-many). All assigned fund codes are treated equally; no primary designation. | Fund Codes | High | M |
| P-09 | Remove the auto-generated Project Number column from the project list — it is unused and visually noisy. | Projects | Medium | S |
| P-10 | Make the Property/Address the primary identifier column in the housing project list. | Projects | High | S |
| P-11 | Add a Fund Code(s) column to the project list (showing all assigned codes; equal visual treatment). | Projects | High | S |
| P-12 | Tighten row density / reduce row height on the housing project list so more records are visible per screen. | Projects | Medium | S |
| P-13 | Add an optional "Next Milestone" column alongside the existing Current Milestone. | Projects | Medium | S |
| P-14 | Finalize and document the Project Template feature: pre-configured milestones, budget categories, and tasks per program type. | Templates | Medium | M |
| P-15 | Allow saving any existing project as a template for reuse. | Templates | Medium | S |
| P-16 | Tie templates to fund codes / programs so the right milestone set is suggested when a fund code is chosen. | Templates | Medium | M |
| P-17 | Build a personal home dashboard surfacing only items currently assigned to the logged-in user (Encompass-style work queue). | Workflow | High | L |
| P-18 | Implement milestone-based handoff: when a milestone is changed, the project moves off the previous owner's dashboard and onto the next owner's dashboard. | Workflow | High | L |
| P-19 | Auto-stamp date/time when a milestone is changed (eliminating manual date-entry into project tracking). | Workflow | High | S |
| P-20 | Add automatic notification when an upstream artifact (e.g. scope of work) is uploaded, so the manager doesn't have to manually check each file. | Workflow | High | M |
| P-21 | Decouple Budget Categories from the Contracts database — make the contract reference optional on budget category creation. | Budget | High | S |
| P-22 | Display the assigned fund code prominently in the Budget Categories view (currently only shown after re-selection). | Budget | Medium | S |
| P-23 | Verify and harden expense-tracking math (running totals, deductions from category budgets, over-budget warnings). | Budget | Medium | M |
| P-24 | Confirm the Budget module is reporting-only and never writes to Sage / fiscal systems. The Housing budget module is, by design, a standalone tracker for the project manager — funding sources, allocated amounts, and expenses vs. budgeted limit. It was never built to integrate with Sage Intacct, MIP, or DonorPerfect, and is not intended to be. P-24 is a documentation + tripwire task to lock that design intent in. | Budget | High | S |
| P-25 | Build a dedicated Program Services list under the Housing module, separate from the org-wide CSBG Programs list (which other staff don't maintain). | Services | Medium | M |
| P-26 | Allow the manager to add/edit her own program-service options without touching shared CSBG configuration. | Services | Medium | S |
| P-27 | Replace ad-hoc fund-code tagging on the Communications Log with a structured fund-code field once Fund Code list (P-05) is in place; preserve existing tag data during migration. | Comm Log | Medium | M |
| P-28 | Build a mobile-first web intake form (replaces the current PDF that requires Adobe Reader / printer / scanner). | Intake | High | L |
| P-29 | Wire the intake form submission directly into the housing module so a new Communications Log record is auto-created with the applicant's data and any uploaded photos. | Intake | High | L |
| P-30 | Use the manager's existing general application PDF as the source-of-truth content for the new web form's fields. | Intake | High | S |
| P-31 | Build internal-network web forms (no login required) for Peter (site scope) and Tony (construction) to submit deliverables and uploads back to the manager. | Handoff | Medium | M |
| P-32 | Route those form submissions into a dedicated mailbox or SharePoint folder structure so new submissions are easy to spot. | Handoff | Medium | M |
| P-33 | Establish an automated regression-testing pass (AI-driven UI walkthrough with 10–15 sample case files) before each release of the housing module. | QA | Medium | M |
| P-34 | Set a recurring weekly check-in on the manager's calendar throughout the build phase to catch usability/stress points early. | Process | High | S |
| P-35 | Collect from manager: full services list, full fund-code list with per-code requirements, and the current general application PDF (inputs needed before P-05/P-06/P-25/P-30 can start). | Inputs | High | S |
| P-36 | Remove the regular-programs vs. PM-enabled-programs filter from the housing project list UI (and any other housing views that expose it). Leave the underlying scope/method in the controller intact for possible future use; do not delete the backend capability. | Projects | Medium | S |
| P-37 | Restructure the project detail header. Line 1 (prominent title): property address + head-of-household name. Line 2 (secondary): assigned fund codes, listed equally with no primary callout. Line 3 (small subtitle): the job's unique ID for reference. | Projects | Medium | S |
| P-38 | Drop the "primary fund code" concept from the housing module entirely. No primary designation in schema (no `primary_fund_code_id` on `housing_jobs`, no `is_primary` on the pivot), no primary visual treatment in any UI, no primary-selection rule in P-08 backfill. All assigned fund codes are equal. If any reference to a primary fund code exists in current production code, remove it. | Fund Codes | High | S |

## Suggested sequencing

Recommended ordering for staged delivery, not a commitment. Each phase is sized so the module remains usable in production throughout the work.

### Phase 0 — Inputs & guardrails (week 1)
- **P-35** — collect services list, fund-code list with per-code requirements, and current intake PDF.
- **P-34** — schedule recurring weekly check-in.
- **P-33** — stand up the regression-test harness against current state so we have a baseline before changes.

### Phase 1 — Quick wins on the existing list (week 1–2)
- **P-09, P-10, P-11, P-12** — project list cleanup (remove project number, promote address, add fund-code column, tighten rows). P-13 (Next Milestone column) deferred.
- **P-36** — remove the regular vs. PM-enabled program filter from housing views (UI only; backend scope retained).
- **P-37** — restructure project detail header (address + HoH name as title, fund codes as subtitle, job ID as small reference).
- **P-01, P-02** — duplicate detection and search on Communications Log.
- **P-19** — auto-stamp milestone change dates.
- **P-21, P-22** — decouple Budget Categories from Contracts; surface fund code in the budget view.

### Phase 2 — Fund Code system (week 2–4)
- **P-05** — Fund Code reference list.
- **P-06** — per-code requirements/checklists.
- **P-07** — surface those requirements in the project UI when a code is assigned.
- **P-08** — many-to-many fund codes per project (no primary).
- **P-38** — drop the primary-fund-code concept system-wide (audit + remove any existing references).
- **P-27** — migrate ad-hoc fund-code tags to the structured field.
- **P-24** — document the Housing budget module as a standalone tracker with tripwire test.

### Phase 3 — Workflow & dashboard (week 3–5)
- **P-17** — personal home dashboard.
- **P-18** — milestone-based handoff between owners.
- **P-20** — upstream-artifact notifications (e.g. scope uploaded).
- **P-03, P-04** — filters and view modes on the Comm Log.
- **P-23** — harden budget math and over-budget warnings.

### Phase 4 — Templates & program services (week 4–6)
- **P-14, P-15, P-16** — project templates, save-as-template, fund-code-aware templates.
- **P-25, P-26** — housing-owned Program Services list.

### Phase 5 — Intake & contractor handoff (week 5–8)
- **P-30** — convert manager's general application PDF into form spec.
- **P-28, P-29** — mobile-first web intake form wired into the Comm Log.
- **P-31, P-32** — internal forms for Peter / Tony with routing to a dedicated submissions area.

## Open questions to resolve before starting

- For per-code requirements (P-06): freeform checklist, or structured fields tied to milestones? *(Phase 2 prompt locks this to boolean hard/informational; milestone linkage deferred.)*
- For the personal dashboard (P-17): is there a single "assigned to" user per milestone, or does it depend on milestone type?
- For the intake form (P-28): public on caclv.org, or behind a token-link sent to applicants?
- Backfill plan for the structured fund-code field (P-27) — bulk script, or manual cleanup over time?

## Explicitly out of scope (from the source transcript)

- Anything touching the global Contracts database UI (Katherine owns it and prefers it as-is).
- Anything that asks Peter or Tony to log into a new system — only no-login forms or existing SharePoint flows.
- Anything that writes to Sage / fiscal systems from the housing module.
