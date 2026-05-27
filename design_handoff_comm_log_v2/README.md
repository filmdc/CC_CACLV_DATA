# Handoff: Communication Log v2 (Project-First) — CACLV Housing

## Overview

This is a **UX redesign** of the Communication Log module's **Edit** and **View** modals for CACLV's
internal `CC_CACLV_DATA` app, plus a **new Convert-to-Project promotion flow**. It is recommended that
implementation follow **Direction 02 · Project-First** out of three explored directions — the others
are in the bundle for context only.

The Communication Log is used by Kaitlyn (housing program manager) to record interactions
(phone calls, emails, in-person, etc.) with clients, contractors, and partners across CACLV's
housing programs. Each entry has metadata (date, type, direction, category, priority), a contact,
an optional address, a Summary, optional Program Service tags, optional Fund Code tags, free-form
Tags, a thread of **Activity Updates / Follow-ups**, and a checklist of **Application
Requirements** (templated per program service). Entries are optionally attached to a housing
Project; when attached, the form composition reshapes so fund codes and program services are
scoped to that project. **Convert-to-Project** promotes a general-intake entry into a real
project, but is gated on the entry's required application requirements being met.

---

## About the Design Files

**The HTML files in this bundle are design references.** They are prototypes built in static HTML +
React-via-Babel to communicate the intended look and behavior. **Do not ship them as-is.**

Your job is to **recreate these designs in the project's existing stack** —
`Laravel 12 + Livewire 3 + Alpine + Tailwind + MySQL` — using its established patterns, Blade
components, and styles. The mockups encode the visual + interaction design; the implementation
is up to you.

If the project happens to lack a usable component library yet, the **Component Inventory**
section in `Direction 02 Dev Handoff.html` (§7) lists the eleven Blade components this design
expects to use — they can be created as part of this work.

---

## Fidelity

**High-fidelity.** Colors, type, spacing, radii, and shadows are all final and ground in the
**CALV Design System**. The full token set is shipped as `calv.css` (custom-property `--calv-*`
tokens for color and `--space-*` / `--radius-*` / `--shadow-*` for layout).

Recreate the visuals pixel-accurately in the project's Tailwind theme. The CALV tokens map to
plausible Tailwind extensions (see **Design Tokens** below).

---

## Recommended reading order

1. **`Direction 02 Dev Handoff.html`** — the authoritative spec. Open in a browser. It has:
   - Data model deltas
   - State machine (project null vs set)
   - Six surfaces, each rendered as a scaled mockup beside a 3-column behavior table
     (Area · Behavior · Stack)
   - Six interaction specs (picker → reshape, fund-code rules, priority, composer,
     validation, subject derivation)
   - Keyboard shortcuts table
   - Component inventory
   - Livewire vs Alpine vs island implementation breakdown
   - 12 open questions sorted by priority (Q1 answered; **Q3, Q9, Q12 are high priority
     and should be resolved before scoping the build**)

2. **`Comm Log Redesign.html`** — the design canvas with all three directions explored, plus
   the long-form rationale page. Use this for context on *why* Direction 02 was chosen; the
   build spec lives in #1.

3. **`reference/`** — screenshots of the **current** form and viewer for visual diff.

---

## Screens / Views

The full per-surface specification lives in **`Direction 02 Dev Handoff.html` §4** (six surfaces,
each with a mockup + behavior table). The short list:

| § | Surface | Purpose | Key behavior |
|---|---|---|---|
| §4.1 | **Edit · no project** (general intake) | New entry not tied to a project | Anchor selector at top; fund codes + program services **hidden** by default |
| §4.2 | **Edit · with project** | Editing an entry attached to a project | Teal project banner sticky at top; fund codes & services **scoped** to project; contact suggester pulls from project roster |
| §4.3 | **Project picker (⌘P)** | Attach / change / detach project | Command-palette overlay; first row is always "General intake"; ↑↓⏎ keyboard nav |
| §4.4 | **Viewer · project-attached** | Read-only with Activity Updates thread + Requirements panel | Address as page title; chip row (Direction · Type · Category · Priority); Requirements panel; Activity Updates composer + feed |
| §4.5 | **Viewer · general intake** | Read-only intake entry | Sand "no project" banner with **Convert to project** + **Attach to existing** CTAs; Requirements panel gates the promotion |
| §4.6 | **Convert-to-Project** *(new)* | Promote intake → project | Modal with **requirements gate** at top (blocks Create unless met or override + reason); project name/code, lead, fund-code multi-select, carry-over checkboxes |
| §4.7 | **Requirements panel** *(new)* | Track unmet application requirements | Aggregated from program services (dedup'd by key); kind icons (doc/form/data/verify/external); status chips (pending/requested/received/waived/na/blocked); ad-hoc requirement support |

All six are rendered live in the dev-handoff HTML at the sizes used in the canvas, so you can
zoom in for any visual detail.

---

## Interactions & Behavior

See **`Direction 02 Dev Handoff.html` §5** for full specs. Key rules:

- **Project picker → form reshape** (§5.1). Selecting a project sets `communication.project_id`,
  re-fetches `availableFunds` and `suggestedServices`, and re-renders the right-rail partials.
  Picking "General intake" returns the form to INTAKE state (`project_id = null`).
- **Fund-code expiry** (§5.2). 5 states: Soon (≤30d → red dot + red label + inline warning callout
  when checked), Warning (≤90d → amber dot), OK (>90d → sage dot), Perpetual (`expires_at IS NULL`
  → gray dot), Expired (slate dot + strikethrough; cannot newly check).
- **Priority** (§5.3). New enum field: `urgent / high / normal / low`, default `normal`. Defer
  *what* it drives (list ordering / SLA / notifications) until Q9 is answered.
- **Add-update composer** (§5.4). Five typed kinds: Note, Follow-up, Action, Email, Status
  (Status is system-only). Follow-up requires a date; ⌘⏎ submits.
- **Validation** (§5.5). Blocking vs warning errors are listed per-field; check that table
  before writing custom rules.
- **Subject auto-derivation** (§5.6). Confirmed by David: `subject = user_provided || address ||
  "COMM-{id} · Untitled"`. Once a user touches the field, set `subject_overridden_at` so the
  auto-derive never clobbers a rename.
- **Requirement lifecycle** (§5.7). Requirements move through 6 states: `pending → requested →
  received | waived | na | blocked`. Every transition writes a *Status* activity update on the
  parent entry (audit trail). "Met" (= received/waived/na) counts toward the Convert-to-Project
  gate; only `required = true` instances are counted.
- **Keyboard shortcuts** (§6). ⌘P picker, ⌘⏎ save, ⌘⏎N save+new, E edit (in viewer), N focus
  composer (in viewer), Esc cancel, ↑↓⏎ in picker.

---

## Application Requirements (new feature)

A first-class concept introduced in this redesign. Tracks the document / form / data / verification
items that each program service requires before an intake can be promoted into a real project.

**Concept:**
- Each program service has a **template** of requirements (e.g. HCP First-Time Homebuyer requires
  Photo ID, Proof of income, Credit-report consent, Household size, Pre-purchase class attendance).
- When Kaitlyn tags a service on an intake, that template's items become **instances** on the
  intake.
- Templates are **dedup'd by key** across services (so "Photo ID" tagged via two services counts
  once, with `via[]` tracking both sources).
- Each instance moves through the 6-state lifecycle (`pending → requested → received | waived | na
  | blocked`). The status chip and the kind icon (doc / form / data / verify / external) are how
  Kaitlyn scans status at a glance.
- **Convert-to-Project is hard-blocked** unless every `required = true` instance is met
  (received / waived / na). An **audit-logged override** lets Kaitlyn force through with a written
  reason, recorded as `override_reason` + `override_by` on the new project.
- **Ad-hoc requirements** can be added per-intake (one-off items not on any service template),
  tinted amber in the UI and tagged `ad_hoc = true`.
- After Convert-to-Project, all instances are **copied forward** to a `project_requirements` set;
  the project's list is the live, editable copy from then on.

**Sample data caveat:** the per-service requirement templates in the mocks are *plausible-but-fake*.
Kaitlyn + David need to provide / approve the real seed list before the migration runs (Q13).

**Surfaces involved:**
- `§4.4 Viewer · project-attached` — embeds the Requirements panel between Summary and Activity Updates.
- `§4.5 Viewer · general intake` — same placement; the panel is the gate target for the Convert CTA.
- `§4.6 Convert-to-Project` — the **Requirements Gate** sits at the top of the modal; Create button
  disabled when not passed.
- `§4.7 Requirements panel` — the standalone surface; same component used in all three places above.

**Files in this bundle:** see `requirements.jsx`. It contains the templates (seed data),
six-state lifecycle, dedupe helper, and the three React components (`RequirementsPanel`,
`RequirementsSummaryPill`, `RequirementsGate`) that all the surfaces above use.

---

## State Management

The form has **two composition states** gated by `communication.project_id`:

```
state INTAKE  (project_id IS NULL):
  - anchor selector visible at top
  - fund codes hidden
  - program services hidden
  - subject auto-derived from address on save

state PROJECT (project_id IS NOT NULL):
  - sticky teal project banner
  - fund codes: filtered to project.fund_codes
  - program services: 3 project-suggested chips + 17+ behind search
  - subject auto-derived from address on save
```

Transitions: `null → set` via picker (re-render right rail). `set → null` requires explicit
"detach" — **NOT** spec'd for v1 until Q12 (what happens to fund-code tags from the project)
is resolved.

---

## Design Tokens

Shipped in `calv.css` — these are the **CALV Design System** tokens. Mirror them in
the project's Tailwind config under `theme.extend`.

### Colors (primary)
```
--calv-red:    #D14124   /* CTAs, action red */
--calv-slate:  #54585A   /* body text, secondary UI */
--calv-teal:   #006269   /* links, project accent, data */
--calv-sage:   #6FA287   /* success, nature, housing */
--calv-amber:  #F1B434   /* warning */
--calv-sand:   #D5CB9F   /* muted backgrounds, callouts */
```
Each has 65/35/15 tint variants (e.g. `--calv-red-65`, `--calv-red-15`). See `calv.css` for the
full set.

### Semantic aliases (use these in code, not the raw values)
```
--color-bg            (white)
--color-surface       (white)
--color-surface-warm  (sand-15, #F8F6EE — alternate-stripe surface)
--color-text          (slate)
--color-text-soft     (slate-65)
--color-accent        (red)
--color-accent-press  (#B73719 — red darkened ~8%)
--color-link          (teal)
--color-success       (sage)
--color-warning       (amber)
--color-border        (slate-35)
--color-border-soft   (slate-15)
```

### Typography
```
--font-h1:   "DIN Condensed", "Oswald", "Barlow Condensed", sans-serif
             — 95% of all display. ALL CAPS, +20 tracking.
--font-sub:  same family — section/sub-heads + buttons.
--font-body: "DIN 2014", "Barlow", "Inter", system-ui
             — body weight 300.
```
DIN Condensed Bold ships as a TTF; DIN 2014 falls back to **Barlow 300** until CACLV provides
the licensed file. Tracking on caps: `0.02em` (called `--tracking-wide`).

### Spacing
```
--space-1: 4px    --space-5: 24px
--space-2: 8px    --space-6: 32px
--space-3: 12px   --space-7: 48px
--space-4: 16px   --space-8: 64px
```

### Radius
```
--radius-xs: 2px    --radius-md: 6px
--radius-sm: 4px    --radius-lg: 8px      (default is 4px — squareness echoes the stamped wordmark)
--radius-pill: 999px (only for status pills / tags)
```

### Shadows
```
--shadow-sm: 0 1px 2px rgba(84,88,90,0.08)
--shadow-md: 0 4px 10px rgba(84,88,90,0.10)
--shadow-lg: 0 12px 28px rgba(84,88,90,0.14)
--shadow-focus: 0 0 0 3px var(--calv-red-35)
```

### Motion
- Ease: `cubic-bezier(.2,.8,.2,1)`
- Durations: `.15s` hover, `.25s` enter, `.40s` scene
- Press state: `transform: translateY(1px)` + darker bg
- **No bounces. No auto-rotating anything. No backdrop-filter blur in marketing surfaces.**

---

## Data Model Deltas

Full table in `Direction 02 Dev Handoff.html` §2. Summary:

| Field / Table | Status | Notes |
|---|---|---|
| `communications.priority` | **NEW** | enum: urgent/high/normal/low, default normal. Migration required. |
| `communications.subject` | **REINTERPRETED** | Derived on save: `address ?? "COMM-{id} · Untitled"`. Editable post-create; set `subject_overridden_at` to prevent clobber. |
| `program_service_requirements` | **NEW TABLE** | Templates per service. Columns: `program_service_id`, `key` (dedupe slug), `label`, `kind` (doc/form/data/verify/external), `required` (bool), `hint`, `position`. |
| `communication_requirements` | **NEW TABLE** | Instances per intake. Columns: `communication_id`, `key`, `label`, `kind`, `required`, `status` (enum), `value`, `note`, `file_id?`, `via[]` (json), `ad_hoc` (bool), `status_changed_at`, `status_changed_by`. |
| `project_requirements` | **NEW** (carry-forward) | On Convert-to-Project: copy instances forward; gates remain editable on the project. |
| `projects.override_reason` / `override_by` | **NEW (audit)** | Set when Convert-to-Project is invoked with unmet required requirements + user-ticked override. Surface visibly on project page. |
| `communications.subject_overridden_at` | **OPTIONAL** | Timestamp; only set when human edits Subject. |
| `communications.priority_changed_at` | **OPTIONAL** | Only add if Q9 confirms priority drives list ordering / SLA. |

Everything else is existing schema — fund codes (m:m), services (m:m), tags (m:m), activity updates
(1:m), contact (inline name+role+phone+email), address (cols), project (nullable fk). **Do not
reshape these.**

---

## Implementation Breakdown

See `Direction 02 Dev Handoff.html` §8 for the full breakdown. TL;DR:

- **Trivial in Livewire 3:** edit form (both states), viewer (both states), project banner,
  fund-code panel re-render, service chip panel, Activity feed, composer submit, Convert-to-Project
  submit, validation, Subject derivation.
- **Alpine sprinkles:** project picker combobox (⌘P), tag input search/create, inline contact
  suggester, form keyboard shortcuts (Tab order, ⌘⏎, ⌘⏎N, Esc), viewer E/N hotkeys, fund-expiry
  hover-reveals.
- **React/Vue island (DEFER):** nothing required in v1. If Q3 (typical Summary length) comes back
  >300 words, ship a Tiptap editor for the Summary field only, mounted via `wire:ignore`.

**No stack shift required.**

---

## Open Questions (do not start before resolving the high-priority ones)

Full list in `Direction 02 Dev Handoff.html` §9. The four that block scoping:

1. **Q3 — Typical Summary length.** If >300 words is common, ship a Tiptap editor in v1.1. If
   1–2 sentences, the textarea is fine. Decide before deciding effort.
2. **Q9 — Priority semantics.** Chip-only, or does it drive list ordering / daily queue /
   notifications / SLA? Affects whether `priority_changed_at` is needed and whether a
   "Status" activity update should be auto-logged on change.
3. **Q12 — Detach behavior.** What happens to fund-code tags that came from the project when an
   entry is detached? Re-confirm by user, or strip silently? Needed before implementing the
   `set → null` transition.
4. **Q13 — Requirement templates per program service.** The mocks use plausible-but-fake items
   per service (Photo ID, Proof of income, Hardship statement, PHFA HEMAP eligibility, etc.).
   **Kaitlyn + David need to provide / approve the real list before seed migration.** This is a
   blocker for the requirements feature shipping correctly.

Qs 14–17 (lifecycle states, override-audit surfacing, file-upload plumbing, service-untag
behavior) are non-blocking but worth resolving early.

---

## Assets

- `calv.css` — design tokens (see Design Tokens above for the gist)
- `reference/Screenshot 2026-05-23 193214.png` — current Edit Communication form (the "before")
- `reference/Screenshot 2026-05-23 201726.png` — current View Communication modal (the "before")

No new images or icons are required. The design uses **Lucide** as the icon set
(https://lucide.dev) — line icons, 1.5–2 px stroke, rounded caps, monochrome. Icons in the
mockups are inline SVG approximations; replace with Lucide imports in the implementation.

**No emoji** anywhere in the design. CALV brand: not part of the visual language.

---

## Files in this bundle

```
README.md                              ← this file
calv.css                               ← design tokens
Direction 02 Dev Handoff.html          ← AUTHORITATIVE spec doc (open in browser)
Comm Log Redesign.html                 ← design canvas (all 3 directions + rationale)

design-canvas.jsx                      ← canvas component (Figma-ish wrapper)
shared.jsx                             ← shared atoms + sample data
direction-2-project-first.jsx          ← Direction 02 edit-form mockups (the recommendation)
viewer.jsx                             ← viewer mockups (project-attached + intake)
convert-to-project.jsx                 ← Convert-to-Project flow mockup (NEW)
requirements.jsx                       ← Application Requirements module (NEW)
handoff.jsx                            ← dev-handoff doc shell + section content

direction-1-power-form.jsx             ← Direction 01 (context only; not for impl)
direction-3-notes-first.jsx            ← Direction 03 (context only; not for impl)
reasoning.jsx                          ← long-form rationale (context only)

reference/                             ← screenshots of the current (before) UI
  Screenshot 2026-05-23 193214.png       ← current Edit form
  Screenshot 2026-05-23 201726.png       ← current Viewer modal
```

The HTML files reference the JSX files via `<script type="text/babel" src="…">` — to view them
locally, serve the folder from a local web server (e.g. `python3 -m http.server`) and open the
HTML files in a browser. Opening directly from disk (`file://`) will fail due to CORS on the
script imports.
