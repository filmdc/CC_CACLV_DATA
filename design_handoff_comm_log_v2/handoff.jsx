// handoff.jsx — Developer handoff package for Direction 02 (Project-First).
// A single-page spec doc with sticky TOC. Renders embedded mockups beside
// behavior tables, keyboard tables, and stack-implementation notes.

// ─────────────────────────────────────────────────────────────
// TYPOGRAPHY + LAYOUT PRIMITIVES (doc-only)
// ─────────────────────────────────────────────────────────────

const TOC = [
  { id: "overview",      label: "Overview & scope" },
  { id: "stack",         label: "Stack posture" },
  { id: "data",          label: "Data model deltas" },
  { id: "state-machine", label: "State machine: project null vs set" },
  { id: "surfaces",      label: "Surfaces (annotated)" },
  { id: "s-edit-none",   label: "  · Edit · no project",  indent: true },
  { id: "s-edit-set",    label: "  · Edit · with project", indent: true },
  { id: "s-picker",      label: "  · Project picker (⌘P)", indent: true },
  { id: "s-view-set",    label: "  · Viewer · project-attached", indent: true },
  { id: "s-view-none",   label: "  · Viewer · general intake", indent: true },
  { id: "s-ctp",         label: "  · Convert-to-Project (new)", indent: true },
  { id: "s-reqs",        label: "  · Application requirements (new)", indent: true },
  { id: "interactions",  label: "Interaction specs" },
  { id: "i-reshape",     label: "  · Project picker → form reshape", indent: true },
  { id: "i-fund",        label: "  · Fund-code chip + expiry", indent: true },
  { id: "i-priority",    label: "  · Priority", indent: true },
  { id: "i-composer",    label: "  · Add-update composer", indent: true },
  { id: "i-validation",  label: "  · Validation & save", indent: true },
  { id: "i-subject",     label: "  · Subject auto-derivation", indent: true },
  { id: "i-reqs",        label: "  · Requirement lifecycle", indent: true },
  { id: "shortcuts",     label: "Keyboard shortcuts" },
  { id: "components",    label: "Component inventory" },
  { id: "implementation",label: "Implementation breakdown" },
  { id: "open",          label: "Open questions" },
];

function HandoffDoc() {
  return (
    <div style={{
      display: "grid", gridTemplateColumns: "240px 1fr",
      minHeight: "100vh", background: "#fff", color: "var(--calv-slate)",
      fontFamily: "var(--font-body)",
    }}>
      {/* Sticky sidebar TOC */}
      <aside style={{
        position: "sticky", top: 0, alignSelf: "start",
        height: "100vh", overflowY: "auto",
        background: "var(--calv-slate)", color: "#fff",
        padding: "28px 18px", boxSizing: "border-box",
      }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
          letterSpacing: ".14em", textTransform: "uppercase",
          color: "var(--calv-amber)", marginBottom: 6,
        }}>CACLV · Housing · v2 spec</div>
        <h1 style={{
          fontFamily: "var(--font-h1)", fontSize: 26, fontWeight: 700,
          letterSpacing: ".02em", textTransform: "uppercase",
          color: "#fff", margin: 0, lineHeight: 1,
        }}>Comm Log · Direction 02</h1>
        <div style={{ fontSize: 11, color: "rgba(255,255,255,.6)", marginTop: 6 }}>
          Developer handoff package · rev 1 · May 23 2026
        </div>

        <nav style={{ marginTop: 28 }}>
          {TOC.map(item => (
            <a key={item.id} href={`#${item.id}`} style={{
              display: "block", textDecoration: "none",
              fontFamily: "var(--font-body)",
              fontSize: 12.5, lineHeight: 1.5,
              padding: "5px 4px",
              paddingLeft: item.indent ? 18 : 4,
              color: "rgba(255,255,255,.78)",
              borderRadius: 3,
              transition: "background .12s",
            }}>{item.label}</a>
          ))}
        </nav>

        <div style={{
          marginTop: 30, paddingTop: 18,
          borderTop: "1px solid rgba(255,255,255,.12)",
          fontSize: 11, color: "rgba(255,255,255,.6)", lineHeight: 1.55,
        }}>
          Audience: <strong style={{ color: "#fff" }}>David</strong> + engineering.<br />
          Owner: Kaitlyn.<br />
          Scope: redesign of the Communication Log Edit + View modals + Convert-to-Project.<br /><br />
          Out of scope: schema migrations, integrations with Sage/MIP/DonorPerfect.
        </div>
      </aside>

      {/* Main column */}
      <main style={{
        padding: "48px 56px 80px",
        maxWidth: 1200, fontSize: 14, lineHeight: 1.6,
      }}>
        <Cover />
        <Section id="overview" num="0" title="Overview & scope">
          <Overview />
        </Section>
        <Section id="stack" num="1" title="Stack posture">
          <StackPosture />
        </Section>
        <Section id="data" num="2" title="Data model deltas">
          <DataModel />
        </Section>
        <Section id="state-machine" num="3" title="State machine: project null vs set">
          <StateMachine />
        </Section>

        <Section id="surfaces" num="4" title="Surfaces — annotated mockups">
          <p>
            Each surface below is the canonical reference. Behavior in the table to the right
            of each mockup overrides any visual ambiguity in the mockup itself.
          </p>
        </Section>

        <SurfaceBlock
          id="s-edit-none" num="4.1" title="Edit form · no project (general intake)"
          mockup={<D2NoProject />}
          height={1100} width={1120}
          intro={(<>
            Used when Kaitlyn is logging an entry that doesn't (yet) belong to a project — general intake,
            partner outreach, miscellaneous follow-ups. Composition is intentionally lighter than the
            project-attached state.
          </>)}
          rows={[
            ["Anchor selector",     "Two big radio cards at top: 'General intake' (default) and 'Attach to project'.", "Livewire · wire:model on $anchor"],
            ["Meta row",            "Date / Type / Direction / Category / Priority / Subject. Priority defaults to Normal.", "Livewire form"],
            ["Subject behavior",    "Empty by default. Persisted Subject = address if any, else 'COMM-{id}' fallback.", "Computed on save (model accessor)"],
            ["Fund codes section",  "HIDDEN. Single inline link reveals on demand.", "Conditional partial render"],
            ["Program services",    "HIDDEN under same disclosure as fund codes.", "Conditional partial render"],
            ["Tags",                "Always visible. Searchable + free-form create.", "Existing tags index + Alpine combobox"],
            ["Save",                "⌘⏎ creates entry. Subject auto-derived if blank.", "Livewire submit"],
          ]}
        />

        <SurfaceBlock
          id="s-edit-set" num="4.2" title="Edit form · with project attached"
          mockup={<D2WithProject />}
          height={1100} width={1120}
          intro={(<>
            The same form, reshaped around an attached project. The teal banner is sticky at top. Fund codes
            and services are scoped to the project. Contact pulls from project roster suggestions.
          </>)}
          rows={[
            ["Project banner",      "Teal, sticky. Shows project name, code, contact count, fund-code count, last-entry recency.", "Livewire view binding"],
            ["Change project",      "Re-opens the picker (⌘P). On reselect, fund-code panel re-renders.", "Alpine sprinkle"],
            ["Meta row",            "Same 6-col grid. Subject prefilled from project's working address pattern.", "Livewire form"],
            ["Contact suggester",   "Strip of project-roster contacts above the name fields. Click pre-fills.", "Alpine sprinkle"],
            ["Fund codes",          "Pre-narrowed to the project's funds. Status dot + days-left rendered live.", "Backend: project.funds()"],
            ["Out-of-project funds","Hidden behind a dashed 'Use a fund code not on this project' row.", "Click → expand list"],
            ["Fund expiry warning", "Inline red callout when any selected fund's expiresAt is ≤30 days.", "Computed in Livewire mount"],
            ["Program services",    "3 project-suggested chips. 17+ others behind a search affordance.", "Project.suggestedServices()"],
          ]}
        />

        <SurfaceBlock
          id="s-picker" num="4.3" title="Project picker (⌘P)"
          mockup={<D2Picker />}
          height={620} width={680}
          intro={(<>
            Centerpiece of the keyboard story. Triggered by ⌘P, the "Attach to project" radio, or the
            'change project' link in the banner. Always includes 'General intake' as the first row.
          </>)}
          rows={[
            ["Trigger",            "⌘P from anywhere in the form; click on either CTA.", "Alpine global keydown handler"],
            ["Search",             "Debounced fuzzy match on project.name + .code + .lead.", "Livewire wire:model.debounce.150"],
            ["Top row",            "'General intake' is always the first option.", "Hard-coded leading row"],
            ["Project rows",       "Code chip (teal) + name + meta line. Recent-first; sort by lastEntryAt desc.", "ORDER BY last_entry_at DESC LIMIT 20"],
            ["Status dot in meta", "Expiry warning ⚠ inline when project.funds has any ≤30d.", "Computed once at index time"],
            ["Keyboard",           "↑/↓ navigate; ⏎ select; Esc dismiss; N opens 'new project' modal.", "Alpine keymap"],
            ["Empty state",        "If no match: 'Create new project named …' affordance.", "Inline action"],
          ]}
        />

        <SurfaceBlock
          id="s-view-set" num="4.4" title="Viewer · project-attached entry"
          mockup={<ViewerProjectAttached />}
          height={1180} width={1180}
          intro={(<>
            Read-only state. Same teal banner as the edit form for visual continuity. Activity Updates &
            Follow-ups thread is the heart of the page; everything else is reference data.
          </>)}
          rows={[
            ["Title",              "Address (Subject) is the page H1. Falls back to 'COMM-{id} · Untitled'.", "model.subject"],
            ["Chip row",           "Direction · Type · Category · Priority + counter chip (e.g. '3 follow-ups · 1 open').", "Computed counts in Livewire"],
            ["Summary",            "Read-only render. Edit button takes you to §4.2.", "Markdown render"],
            ["Activity thread",    "Composer at top. Existing updates oldest→newest. See §5.4.", "Livewire collection"],
            ["Linked Records",     "Project + sibling entries. See §6 for link types.", "Polymorphic many-to-many"],
            ["Convert-to-project", "NOT shown here — only on intake viewer.", "—"],
          ]}
        />

        <SurfaceBlock
          id="s-view-none" num="4.5" title="Viewer · general intake"
          mockup={<ViewerGeneralIntake />}
          height={1080} width={1180}
          intro={(<>
            The same viewer template, but with the sand 'no project' banner. The promotion CTAs
            (Convert-to-project, Attach to existing) are the affordance Kaitlyn uses to graduate an
            intake into a real project.
          </>)}
          rows={[
            ["Banner",                  "Sand background; 'General intake' chip; copy explains promotion path.", "Static template"],
            ["Convert-to-project CTA",  "Secondary (teal) button. Opens the modal in §4.6.", "Alpine modal open"],
            ["Attach-to-existing CTA",  "Quiet button. Opens the project picker (§4.3) with 'no general-intake row' variant.", "Reuses §4.3"],
            ["Fund codes panel",        "Renders explanatory empty state ('hidden by default'). NEVER lists out-of-project funds here.", "Static"],
            ["Service suggestions",     "Inferred from summary keywords (server-side classifier on save).", "Job + ml.suggest_services()"],
            ["Priority",                "Surfaced same as project viewer; defaults to Normal on creation.", "—"],
          ]}
        />

        <SurfaceBlock
          id="s-ctp" num="4.6" title="Convert-to-Project flow (NEW)"
          mockup={<ConvertToProjectModal />}
          height={1280} width={760}
          intro={(<>
            Opened from §4.5's banner CTA. Creates a new project anchored at this entry's contact + address,
            with optional carry-overs. <strong>Gated on the entry's required application requirements</strong>
            (§4.7) — the Create button is disabled unless all required items are Received / Waived / N/A,
            OR Kaitlyn ticks the audit-logged override with a written reason.
          </>)}
          rows={[
            ["Requirements gate",     "At the top. Green if all required met; red with unmet checklist + override if not. Disables Create button when not passed.", "Reuses RequirementsGate"],
            ["Project name",          "Required. Suggested from contact + address; editable.", "POST /projects {name,…}"],
            ["Project code",          "Suggested HSG-{year}-{seq}. Editable. Must be unique.", "Unique constraint on code"],
            ["Lead staff",            "Required. Defaults to current user (Kaitlyn).", "—"],
            ["Fund codes",            "Multi-select from ALL available funds. Expiry warnings shown inline.", "Same component as §4.2"],
            ["Program services",      "Pre-checked from summary inference; user can adjust before create.", "—"],
            ["Carry-over checkboxes", "Contact / Address / Tags / Open follow-up — all default on. Distribution + partner notify default off.", "—"],
            ["On submit",             "Transaction: create project → attach this entry → COPY REQUIREMENTS FORWARD → write Activity Update → close modal → reload viewer.", "Single Livewire action"],
            ["Override path",         "Checkbox + reason textarea. Reason is REQUIRED when override is ticked. Both saved to an audit row.", "Validation rule"],
            ["Cancel",                "Closes modal; no state change.", "—"],
          ]}
        />

        <SurfaceBlock
          id="s-reqs" num="4.7" title="Application requirements panel (NEW)"
          mockup={(
            <div style={{ padding: 20, background: "#fff", width: "100%", minHeight: "100%" }}>
              <RequirementsPanel
                items={mergeWithInstances(
                  aggregateTemplates(["HCP-PHFA-HEMAP", "HCP-RentalHousing"]),
                  SAMPLE_REQS_MARIA
                )}
                hint="from 2 tagged services · dedup'd"
              />
            </div>
          )}
          height={820} width={640}
          intro={(<>
            Embedded in both viewers between Summary and Activity Updates. Aggregates requirement templates
            from every tagged program service, dedup'd by <MonoCode>key</MonoCode>. Each row shows the
            requirement's kind icon, label + hint, source-service chip(s), status, and a single primary
            action that adapts to the current status.
          </>)}
          rows={[
            ["Source",               "Aggregated from program_service_requirements WHERE program_service_id IN (entry.services). Dedup'd by key; via[] tracks attribution.", "Computed at mount + on service toggle"],
            ["Progress",             "Header line ('N of M required met') + 8px bar. Green if all required met; amber otherwise.", "Derived in component"],
            ["Row order",            "Unmet first → optional → met. Stable sort within each bucket.", "Sort in mount"],
            ["Kind icon",            "doc / form / data / verify / external — 1.5px stroke line icons, monochrome.", "Lucide subset"],
            ["Status chip",          "Pending / Requested / Received / Waived / N/A / Blocked. See §5.7 for transitions.", "Enum on instance row"],
            ["Source-service chips", "Small teal chips below label showing which service(s) introduced this requirement.", "From via[] on instance"],
            ["Primary action",       "Button label adapts: Request / Mark received / Edit / Check status. Opens an action drawer (spec TBD).", "Livewire wire:click → drawer"],
            ["Ad-hoc row",           "Tinted amber. Tagged 'ad-hoc'. Created via the '+ ad-hoc requirement' link.", "Instance with ad_hoc=true"],
            ["Optional row",         "Tagged 'optional'. Does NOT block the Convert-to-Project gate.", "required=false on instance"],
            ["+ ad-hoc affordance",  "Top-right of panel. Opens inline form: label + kind + required toggle.", "Alpine inline form"],
          ]}
        />


        <Section id="interactions" num="5" title="Interaction specs">
          <p>
            The five high-leverage interactions, broken down. Each has a state diagram or rule set
            that should match the implementation 1:1.
          </p>
        </Section>

        <SubSection id="i-reshape" num="5.1" title="Project picker → form reshape">
          <PickerReshape />
        </SubSection>

        <SubSection id="i-fund" num="5.2" title="Fund-code chip + expiry rules">
          <FundRules />
        </SubSection>

        <SubSection id="i-priority" num="5.3" title="Priority field">
          <PriorityRules />
        </SubSection>

        <SubSection id="i-composer" num="5.4" title="Add-update composer">
          <ComposerSpec />
        </SubSection>

        <SubSection id="i-validation" num="5.5" title="Validation & blocking save">
          <ValidationSpec />
        </SubSection>

        <SubSection id="i-subject" num="5.6" title="Subject auto-derivation">
          <SubjectSpec />
        </SubSection>

        <SubSection id="i-reqs" num="5.7" title="Requirement lifecycle">
          <RequirementLifecycle />
        </SubSection>

        <Section id="shortcuts" num="6" title="Keyboard shortcuts">
          <Shortcuts />
        </Section>

        <Section id="components" num="7" title="Component inventory">
          <ComponentInventory />
        </Section>

        <Section id="implementation" num="8" title="Implementation breakdown — Livewire vs Alpine vs island">
          <ImplBreakdown />
        </Section>

        <Section id="open" num="9" title="Open questions (read before scoping)">
          <OpenQs />
        </Section>

        <footer style={{
          marginTop: 60, paddingTop: 22,
          borderTop: "2px solid var(--calv-slate)",
          display: "flex", justifyContent: "space-between",
          fontSize: 12, color: "var(--calv-slate-65)",
        }}>
          <span>End of spec · rev 1 · May 23 2026</span>
          <span>Questions → Kaitlyn or design@</span>
        </footer>
      </main>
    </div>
  );
}

// ─────────────────────────────────────────────────────────────
// LAYOUT PRIMITIVES
// ─────────────────────────────────────────────────────────────

function Cover() {
  return (
    <header style={{
      paddingBottom: 32, borderBottom: "2px solid var(--calv-slate)", marginBottom: 36,
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
        letterSpacing: ".14em", textTransform: "uppercase",
        color: "var(--calv-red)", marginBottom: 12,
      }}>Developer handoff · Direction 02 · Project-First</div>
      <h1 style={{
        fontFamily: "var(--font-h1)", fontSize: 64, lineHeight: .95,
        letterSpacing: ".01em", textTransform: "uppercase",
        color: "var(--calv-slate)", margin: 0, maxWidth: 900,
      }}>
        Communication Log v2 · <span style={{ color: "var(--calv-red)" }}>build spec</span>
      </h1>
      <p style={{
        fontSize: 17, color: "var(--calv-slate-65)", margin: "16px 0 0",
        maxWidth: 820, lineHeight: 1.55,
      }}>
        Seven surfaces · seven interactions · one promotion flow with a requirements gate. Below:
        enough detail to scope and schedule the work, with mockups and behavior tables side by side.
        Read top-to-bottom; the TOC on the left is sticky.
      </p>

      <div style={{
        display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 14,
        marginTop: 24, padding: "18px 20px",
        background: "var(--calv-sand-15)", border: "1px solid var(--calv-sand-35)",
        borderRadius: 4,
      }}>
        <Stat label="Surfaces" value="7" />
        <Stat label="Interactions" value="7" />
        <Stat label="New tables" value="3" sub="requirements (templates + instances) + 1 field (priority)" />
        <Stat label="Stack shift" value="None" sub="Ships on Laravel 12 + Livewire 3" />
      </div>
    </header>
  );
}

function Stat({ label, value, sub }) {
  return (
    <div>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".1em", textTransform: "uppercase",
        color: "var(--calv-slate-65)", marginBottom: 4,
      }}>{label}</div>
      <div style={{
        fontFamily: "var(--font-h1)", fontSize: 30, lineHeight: 1,
        color: "var(--calv-slate)", letterSpacing: ".01em", textTransform: "uppercase",
      }}>{value}</div>
      {sub && <div style={{ fontSize: 11, color: "var(--calv-slate-65)", marginTop: 4 }}>{sub}</div>}
    </div>
  );
}

function Section({ id, num, title, children }) {
  return (
    <section id={id} style={{ marginTop: 56, scrollMarginTop: 24 }}>
      <header style={{ marginBottom: 14 }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
          letterSpacing: ".14em", textTransform: "uppercase",
          color: "var(--calv-red)", marginBottom: 4,
        }}>§{num}</div>
        <h2 style={{
          fontFamily: "var(--font-h1)", fontSize: 36, fontWeight: 700,
          letterSpacing: ".02em", textTransform: "uppercase",
          color: "var(--calv-slate)", margin: 0, lineHeight: 1,
        }}>{title}</h2>
      </header>
      <div style={{ fontSize: 14.5, color: "var(--calv-slate)", lineHeight: 1.65 }}>
        {children}
      </div>
    </section>
  );
}

function SubSection({ id, num, title, children }) {
  return (
    <section id={id} style={{ marginTop: 36, scrollMarginTop: 24 }}>
      <header style={{ marginBottom: 12 }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
          letterSpacing: ".12em", textTransform: "uppercase",
          color: "var(--calv-teal)", marginBottom: 4,
        }}>§{num}</div>
        <h3 style={{
          fontFamily: "var(--font-h1)", fontSize: 22, fontWeight: 700,
          letterSpacing: ".02em", textTransform: "uppercase",
          color: "var(--calv-slate)", margin: 0, lineHeight: 1.1,
        }}>{title}</h3>
      </header>
      {children}
    </section>
  );
}

// Embedded scaled mockup — sized so it lives next to the spec table.
function MockupFrame({ children, width, height, scale = 0.6 }) {
  return (
    <div style={{
      width: width * scale, height: height * scale,
      overflow: "hidden",
      border: "1px dashed var(--calv-slate-35)",
      borderRadius: 4, background: "var(--calv-sand-15)",
      flex: "0 0 auto", position: "relative",
    }}>
      <div style={{
        width, height,
        transform: `scale(${scale})`, transformOrigin: "top left",
      }}>
        {children}
      </div>
    </div>
  );
}

function SurfaceBlock({ id, num, title, intro, mockup, rows, width, height }) {
  // Choose scale to fit ~560px width
  const scale = Math.min(560 / width, 0.62);
  return (
    <section id={id} style={{ marginTop: 44, scrollMarginTop: 24 }}>
      <header style={{ marginBottom: 14 }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
          letterSpacing: ".12em", textTransform: "uppercase",
          color: "var(--calv-teal)", marginBottom: 4,
        }}>§{num} · surface</div>
        <h3 style={{
          fontFamily: "var(--font-h1)", fontSize: 22, fontWeight: 700,
          letterSpacing: ".02em", textTransform: "uppercase",
          color: "var(--calv-slate)", margin: 0, lineHeight: 1.1,
        }}>{title}</h3>
      </header>
      <p style={{ margin: "0 0 16px", maxWidth: 880 }}>{intro}</p>

      <div style={{ display: "flex", gap: 22, alignItems: "flex-start" }}>
        <MockupFrame width={width} height={height} scale={scale}>{mockup}</MockupFrame>

        <table style={{
          flex: 1, minWidth: 0, borderCollapse: "collapse", fontSize: 12.5,
          alignSelf: "stretch",
        }}>
          <thead>
            <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
              <th style={cellHead}>Area</th>
              <th style={cellHead}>Behavior</th>
              <th style={cellHead}>Stack</th>
            </tr>
          </thead>
          <tbody>
            {rows.map(([area, behavior, stack], i) => (
              <tr key={i}>
                <td style={cellBody}>
                  <strong style={{
                    fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 11,
                    letterSpacing: ".04em", textTransform: "uppercase",
                    color: "var(--calv-slate)",
                  }}>{area}</strong>
                </td>
                <td style={cellBody}>{behavior}</td>
                <td style={{ ...cellBody, fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, color: "var(--calv-slate-65)" }}>
                  {stack}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}

const cellHead = {
  fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
  letterSpacing: ".08em", textTransform: "uppercase",
  textAlign: "left", padding: "9px 11px", verticalAlign: "bottom",
};

const cellBody = {
  padding: "9px 11px", borderBottom: "1px solid var(--calv-slate-15)",
  verticalAlign: "top",
  fontSize: 12.5, lineHeight: 1.5, color: "var(--calv-slate)",
};

// ─────────────────────────────────────────────────────────────
// SECTION BODIES
// ─────────────────────────────────────────────────────────────

function Overview() {
  return (
    <>
      <p>
        <strong>What we're building.</strong> A redesign of the Edit Communication and View
        Communication modals (and a new Convert-to-Project flow) under the philosophy that the project is
        the architectural anchor for housing work. The form composition reshapes around whether an entry
        is attached to a project.
      </p>
      <p>
        <strong>Why this direction.</strong> Wrong-project fund-code assignments become structurally
        impossible (rather than a discipline problem) because by default the user cannot see fund codes
        that don't belong to their project. The promotion path from intake → project is first-class.
      </p>
      <Callout kind="ok" title="In scope">
        Edit modal (no project + with project), View modal (no project + with project), Project picker
        (⌘P), Convert-to-Project flow with <strong>requirements gate</strong>, Priority field rendering,
        Activity Updates thread (composer + feed), Linked Records read-only panel,{" "}
        <strong>Application Requirements</strong> (templates per service · instances per intake ·
        lifecycle · ad-hoc · carry-forward on promote).
      </Callout>
      <Callout kind="warn" title="Out of scope (do not touch)">
        Existing schema for fund codes / projects / contacts / tags. Integrations with Sage Intacct, MIP,
        DonorPerfect. Mobile-first responsive (desktop is the primary target). The Communication Log
        list/index page (separate ticket).
      </Callout>
    </>
  );
}

function Callout({ kind = "note", title, children }) {
  const map = {
    ok:    { bg: "var(--calv-sage-15)",  bd: "var(--calv-sage)",  fg: "#2F5A41" },
    warn:  { bg: "var(--calv-red-15)",   bd: "var(--calv-red)",   fg: "var(--calv-red)" },
    info:  { bg: "var(--calv-teal-15)",  bd: "var(--calv-teal)",  fg: "var(--calv-teal)" },
    note:  { bg: "var(--calv-sand-15)",  bd: "var(--calv-sand-65)", fg: "var(--calv-slate)" },
    danger:{ bg: "var(--calv-amber-15)", bd: "var(--calv-amber)", fg: "#8A6410" },
  };
  const p = map[kind] || map.note;
  return (
    <div style={{
      margin: "14px 0", padding: "12px 16px",
      background: p.bg, borderLeft: `3px solid ${p.bd}`, borderRadius: 3,
      fontSize: 13.5, lineHeight: 1.55, color: "var(--calv-slate)",
    }}>
      {title && (
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 10.5, fontWeight: 700,
          letterSpacing: ".08em", textTransform: "uppercase",
          color: p.fg, marginBottom: 4,
        }}>{title}</div>
      )}
      {children}
    </div>
  );
}

function StackPosture() {
  return (
    <>
      <p>
        <strong>Baseline: Laravel 12 + Livewire 3 + Tailwind + MySQL.</strong> Nothing in this spec requires
        a stack shift. The work is mostly Livewire components + a small handful of Alpine sprinkles for
        keyboard-driven behavior.
      </p>
      <Callout kind="info" title="When you'd reach for Alpine">
        Three places only: (a) the ⌘P / ⌘K / ⌘. global keymap, (b) the project-picker combobox keyboard
        navigation, (c) the inline contact-suggester strip on the edit form. Everything else is
        Livewire wire:model + wire:click.
      </Callout>
      <Callout kind="note" title="When you'd reach for a React island (DO NOT in v1)">
        Only if Q3 (typical entry length) comes back &gt;300 words AND we decide to ship a Tiptap-style
        editor for the Summary field. v1 keeps the textarea + autosave. Defer the editor to a follow-on
        ticket.
      </Callout>
    </>
  );
}

function DataModel() {
  return (
    <>
      <p>
        Existing fields untouched. One new field (<MonoCode>priority</MonoCode>) and one reinterpreted
        field (<MonoCode>subject</MonoCode>). The Activity Updates thread is an existing relation we'll
        surface; same for Linked Records.
      </p>
      <table style={tableStyle}>
        <thead>
          <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
            <th style={cellHead}>Field</th>
            <th style={cellHead}>Type</th>
            <th style={cellHead}>Status</th>
            <th style={cellHead}>Notes</th>
          </tr>
        </thead>
        <tbody>
          <DRow f="communications.date" t="date" s="existing" n="No change." />
          <DRow f="communications.type" t="enum" s="existing" n="Existing dropdown values: Phone Call, Email, Meeting, Letter, Other." />
          <DRow f="communications.direction" t="enum" s="existing" n="Incoming / Outgoing." />
          <DRow f="communications.category" t="enum" s="existing" n="Confirm options w/ David. Suggest first-letter keyboard select if &gt;3 values." />
          <DRow f="communications.priority" t="enum" s="new" n="urgent / high / normal / low. Default 'normal'. ADD MIGRATION." accent="red" />
          <DRow f="communications.subject" t="string" s="reinterpreted" n="Derived on save: address-string if any, else 'COMM-{id} · Untitled'. User-editable post-create." accent="amber" />
          <DRow f="communications.summary" t="text" s="existing" n="No editor change in v1. Confirm Q3 typical length first." />
          <DRow f="communications.project_id" t="fk?" s="existing" n="Nullable. Drives form composition." />
          <DRow f="communications → contact" t="relation" s="existing" n="Inline name+role+phone+email today. Keep flat. See Q4." />
          <DRow f="communications → address" t="json/cols" s="existing" n="Street/City/State/ZIP. Feeds Subject derivation." />
          <DRow f="communications → fund_codes" t="m:m" s="existing" n="All equal (no primary). Scope to project.fund_codes when project is set." />
          <DRow f="communications → services" t="m:m" s="existing" n="20+ across CAH/HCP/SSS. Project.suggestedServices() narrows the chip list." />
          <DRow f="communications → tags" t="m:m" s="existing" n="Free-form, user-created. No vocabulary change." />
          <DRow f="communications → updates" t="1:m" s="existing" n="Activity thread. Existing relation; rendered with typed chip. See §5.4 for type set." />
          <DRow f="communications.priority_changed_at" t="timestamp?" s="optional" n="Only if Q9 confirms priority drives list ordering / SLA. Otherwise skip." />
          <DRow f="program_service_requirements" t="table" s="new" n="Templates: program_service_id, key (dedupe slug, e.g. 'photo-id'), label, kind (doc/form/data/verify/external), required (bool), hint, position. One row per requirement per program service." accent="red" />
          <DRow f="communication_requirements" t="table" s="new" n="Instances: communication_id, key, label, kind, required, status (enum), value, note, file_id?, via (json array of source service IDs for dedupe attribution), ad_hoc (bool), status_changed_at, status_changed_by." accent="red" />
          <DRow f="project_requirements" t="copy / link" s="new" n="On Convert-to-Project: copy instances forward, linked to the new project. Met requirements carry through; pending ones remain pending. See §5.7 for lifecycle." accent="red" />
        </tbody>
      </table>
      <Callout kind="warn" title="Migration needed">
        Adding <MonoCode>priority</MonoCode> requires a migration. Default 'normal' for all existing rows.
        No backfill from existing data — David confirms whether high/urgent should be inferred from any
        legacy category before we drop in defaults.
      </Callout>
    </>
  );
}

function DRow({ f, t, s, n, accent }) {
  const sColor = s === "new" ? "var(--calv-red)" :
                 s === "reinterpreted" ? "var(--calv-amber)" :
                 s === "optional" ? "var(--calv-teal)" :
                 "var(--calv-slate-65)";
  return (
    <tr>
      <td style={{ ...cellBody, fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11.5 }}>{f}</td>
      <td style={{ ...cellBody, fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, color: "var(--calv-slate-65)" }}>{t}</td>
      <td style={{ ...cellBody, fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700, letterSpacing: ".06em", textTransform: "uppercase", color: sColor }}>{s}</td>
      <td style={cellBody}>{n}</td>
    </tr>
  );
}

function MonoCode({ children }) {
  return (
    <code style={{
      fontFamily: "ui-monospace, Menlo, monospace", fontSize: "0.92em",
      background: "var(--calv-sand-15)", padding: "1px 5px", borderRadius: 2,
      color: "var(--calv-slate)",
    }}>{children}</code>
  );
}

function StateMachine() {
  return (
    <>
      <p>
        The Edit form has two composition states gated by{" "}
        <MonoCode>communication.project_id</MonoCode>. Switching between them is the single most important
        interaction in this redesign.
      </p>

      <div style={{
        display: "grid", gridTemplateColumns: "1fr 80px 1fr", gap: 14,
        marginTop: 18, alignItems: "stretch",
      }}>
        <StateBox
          title="state · INTAKE"
          subtitle="project_id IS NULL"
          color="var(--calv-sand)"
          fields={[
            "anchor selector visible",
            "fund codes: HIDDEN",
            "program services: HIDDEN",
            "subject: auto from address if any",
            "save: creates entry",
          ]}
        />
        <StateArrow />
        <StateBox
          title="state · PROJECT"
          subtitle="project_id IS NOT NULL"
          color="var(--calv-teal-15)"
          colorText="var(--calv-teal)"
          fields={[
            "project banner sticky at top",
            "fund codes: project.funds() — scoped",
            "program services: project.suggestedServices() + search",
            "subject: same rule (address → fallback)",
            "save: updates entry",
          ]}
        />
      </div>

      <Callout kind="info" title="Two valid transitions">
        <strong>NULL → set:</strong> via the picker (⌘P) or the 'Attach to project' anchor radio. Re-renders
        the fund-code + service partials.<br />
        <strong>set → NULL:</strong> requires explicit 'Detach from project' action (NOT shown in v1; needs
        Q12 first — what happens to existing fund-code tags? Re-confirm or strip?).
      </Callout>
    </>
  );
}

function StateBox({ title, subtitle, color, colorText = "var(--calv-slate)", fields }) {
  return (
    <div style={{
      border: `1.5px solid ${color}`,
      background: color === "var(--calv-sand)" ? "var(--calv-sand-15)" : "var(--calv-teal-15)",
      borderRadius: 4, padding: "14px 16px",
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
        letterSpacing: ".08em", textTransform: "uppercase",
        color: colorText, marginBottom: 2,
      }}>{title}</div>
      <div style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, color: "var(--calv-slate-65)", marginBottom: 10 }}>
        {subtitle}
      </div>
      <ul style={{ margin: 0, paddingLeft: 18, fontSize: 13, lineHeight: 1.5, color: "var(--calv-slate)" }}>
        {fields.map((f, i) => <li key={i} style={{ marginBottom: 3 }}>{f}</li>)}
      </ul>
    </div>
  );
}

function StateArrow() {
  return (
    <div style={{
      display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center",
      gap: 6, color: "var(--calv-slate-65)",
    }}>
      <svg width="48" height="20" viewBox="0 0 48 20" fill="none">
        <path d="M2 10h40m-6-5l6 5-6 5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 9, fontWeight: 700,
        letterSpacing: ".08em", textTransform: "uppercase",
      }}>⌘P</div>
      <svg width="48" height="20" viewBox="0 0 48 20" fill="none">
        <path d="M46 10H6m6 5l-6-5 6-5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 9, fontWeight: 700,
        letterSpacing: ".08em", textTransform: "uppercase",
      }}>detach</div>
    </div>
  );
}

const tableStyle = {
  width: "100%", borderCollapse: "collapse", marginTop: 10, fontSize: 13,
  border: "1px solid var(--calv-slate-15)", borderRadius: 4, overflow: "hidden",
};

// ─────────────────────────────────────────────────────────────
// Interaction specs
// ─────────────────────────────────────────────────────────────

function PickerReshape() {
  return (
    <>
      <p>
        The killer interaction. When project changes, the right-rail content swaps.
      </p>
      <ol style={{ paddingLeft: 22 }}>
        <li><strong>Open picker</strong> · ⌘P / 'Attach to project' / 'Change project' link.</li>
        <li><strong>User selects.</strong> Livewire dispatches <MonoCode>projectSelected(id|null)</MonoCode>.</li>
        <li><strong>Form mounts:</strong> sets <MonoCode>$this-&gt;project_id</MonoCode> and re-fetches{" "}
          <MonoCode>$this-&gt;project</MonoCode>, <MonoCode>$this-&gt;availableFunds</MonoCode>,{" "}
          <MonoCode>$this-&gt;suggestedServices</MonoCode>.</li>
        <li><strong>Re-render:</strong> the project banner, fund-code panel, service panel partials all
          re-paint. Contact suggester strip pulls from <MonoCode>project.contacts()</MonoCode>.</li>
        <li><strong>If user picks 'General intake'</strong> from the picker, <MonoCode>project_id</MonoCode>{" "}
          becomes <MonoCode>null</MonoCode> and the form drops back to INTAKE state.</li>
      </ol>
      <Callout kind="info" title="Edge case">
        If fund codes were already checked on the entry and they're not in the newly-selected project's
        fund list, surface them under the 'Use a fund code not on this project' row (still checked) and
        flag with an amber dot until the user resolves.
      </Callout>
    </>
  );
}

function FundRules() {
  return (
    <>
      <p>
        Each fund code has nullable <MonoCode>starts_at</MonoCode> / <MonoCode>expires_at</MonoCode> dates.
        We surface expiry as a glanceable dot + days-left line. No new field.
      </p>
      <table style={tableStyle}>
        <thead>
          <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
            <th style={cellHead}>State</th>
            <th style={cellHead}>Condition</th>
            <th style={cellHead}>Dot</th>
            <th style={cellHead}>Label</th>
            <th style={cellHead}>Side effect</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style={cellBody}><strong>Soon</strong></td><td style={cellBody}>expires_at within 30 days</td><td style={cellBody}><FundStatusDot status="soon" /></td><td style={cellBody}>Expires in Nd</td><td style={cellBody}>Red dot · red label · inline warning callout when checked</td></tr>
          <tr><td style={cellBody}><strong>Warning</strong></td><td style={cellBody}>expires_at within 90 days</td><td style={cellBody}><FundStatusDot status="warning" /></td><td style={cellBody}>Expires in Nd</td><td style={cellBody}>Amber dot · plain label · no callout</td></tr>
          <tr><td style={cellBody}><strong>OK</strong></td><td style={cellBody}>expires_at &gt; 90 days</td><td style={cellBody}><FundStatusDot status="ok" /></td><td style={cellBody}>Expires YYYY-MM-DD</td><td style={cellBody}>Sage dot · plain label</td></tr>
          <tr><td style={cellBody}><strong>Perpetual</strong></td><td style={cellBody}>expires_at IS NULL</td><td style={cellBody}><FundStatusDot status="perpetual" /></td><td style={cellBody}>No expiry</td><td style={cellBody}>Gray dot · plain label</td></tr>
          <tr><td style={cellBody}><strong>Expired</strong></td><td style={cellBody}>expires_at &lt; today</td><td style={cellBody}><FundStatusDot status="expired" /></td><td style={cellBody}>Expired Nd ago</td><td style={cellBody}>Slate dot · strikethrough label · cannot newly check · existing checks remain (with warning)</td></tr>
        </tbody>
      </table>
      <Callout kind="warn" title="Inline warning callout">
        When ANY checked fund is in the 'Soon' state, render the red callout immediately under the
        fund-code panel: <em>"FCODE expires in Nd. Any work tagged to it must close by YYYY-MM-DD."</em>
      </Callout>
    </>
  );
}

function PriorityRules() {
  return (
    <>
      <p>
        New enum field. Defaults to <MonoCode>normal</MonoCode>. Renders as a chip everywhere — in the chip
        row on the viewer, in the meta row on the edit form, in the comm-log list (separate ticket).
      </p>
      <div style={{ display: "flex", gap: 12, flexWrap: "wrap", margin: "12px 0" }}>
        <PriorityBadge level="urgent" />
        <PriorityBadge level="high" />
        <PriorityBadge level="normal" />
        <PriorityBadge level="low" />
      </div>
      <p>
        <strong>Sort effect</strong> (pending Q9): if priority drives list ordering, the comm-log index
        page sorts urgent &gt; high &gt; normal &gt; low within the same date bucket. Each priority change
        writes an Activity Update of kind <em>Status</em> ("Priority set to High by K. Wise · 2:14 PM").
      </p>
      <Callout kind="note" title="Defaults">
        New from intake → <MonoCode>normal</MonoCode>. On Convert-to-Project, the entry's priority carries
        to the new project's <MonoCode>open_priority</MonoCode> field (or skip if Q9 says it's chip-only).
      </Callout>
    </>
  );
}

function ComposerSpec() {
  return (
    <>
      <p>
        The Activity Updates thread is the heart of the viewer. Composer at top, oldest-first feed below.
        Composer is a typed compose box: pick a kind, write the body, optionally schedule a follow-up
        reminder, submit.
      </p>
      <table style={tableStyle}>
        <thead>
          <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
            <th style={cellHead}>Kind</th>
            <th style={cellHead}>When to use</th>
            <th style={cellHead}>Visual</th>
            <th style={cellHead}>Side effect on submit</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style={cellBody}><strong>Note</strong></td><td style={cellBody}>General comment, no follow-up implied</td><td style={cellBody}><MetaChip kind="neutral">Note</MetaChip></td><td style={cellBody}>Append to thread</td></tr>
          <tr><td style={cellBody}><strong>Follow-up</strong></td><td style={cellBody}>Action needed by a date</td><td style={cellBody}><MetaChip kind="teal">Follow-up</MetaChip></td><td style={cellBody}>Append + create reminder (date field required)</td></tr>
          <tr><td style={cellBody}><strong>Action</strong></td><td style={cellBody}>Concrete action taken</td><td style={cellBody}><MetaChip kind="amber">Action</MetaChip></td><td style={cellBody}>Append to thread</td></tr>
          <tr><td style={cellBody}><strong>Email</strong></td><td style={cellBody}>Email sent / received / forwarded</td><td style={cellBody}><MetaChip kind="sage">Email</MetaChip></td><td style={cellBody}>Append; allow attachment</td></tr>
          <tr><td style={cellBody}><strong>Status</strong></td><td style={cellBody}>System-only (priority changes, project link, etc.)</td><td style={cellBody}><MetaChip kind="red">Status</MetaChip></td><td style={cellBody}>System-written; user cannot pick</td></tr>
        </tbody>
      </table>
      <Callout kind="info" title="Behavior">
        ⌘⏎ submits. If kind=Follow-up and date is empty, the form blocks save with inline error. After
        submit: clear composer body, keep kind preselected, scroll thread to bottom.
      </Callout>
    </>
  );
}

function ValidationSpec() {
  return (
    <>
      <p>Blocking errors prevent save. Soft errors are warnings only. All errors render inline + a single summary chip in the footer.</p>
      <table style={tableStyle}>
        <thead>
          <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
            <th style={cellHead}>Field</th>
            <th style={cellHead}>Rule</th>
            <th style={cellHead}>Kind</th>
            <th style={cellHead}>Message</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style={cellBody}>Date</td><td style={cellBody}>Required · valid date · not future &gt; +30d</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>Invalid date / Date can't be more than 30 days in the future</td></tr>
          <tr><td style={cellBody}>Type</td><td style={cellBody}>Required</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>Required</td></tr>
          <tr><td style={cellBody}>Direction</td><td style={cellBody}>Required</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>Required</td></tr>
          <tr><td style={cellBody}>Category</td><td style={cellBody}>Required</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>Required</td></tr>
          <tr><td style={cellBody}>First name</td><td style={cellBody}>Required</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>Required</td></tr>
          <tr><td style={cellBody}>Email</td><td style={cellBody}>If provided, must be valid email</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>Not a valid email</td></tr>
          <tr><td style={cellBody}>Phone</td><td style={cellBody}>If provided, US format (10 digits)</td><td style={cellBody}><MetaChip kind="amber">Warn</MetaChip></td><td style={cellBody}>Phone doesn't look like a 10-digit US number — save anyway?</td></tr>
          <tr><td style={cellBody}>Fund code (any selected)</td><td style={cellBody}>If &lt;30d to expiry, warn but allow</td><td style={cellBody}><MetaChip kind="amber">Warn</MetaChip></td><td style={cellBody}>FCODE expires in Nd. Work must close by …</td></tr>
          <tr><td style={cellBody}>Fund code (expired)</td><td style={cellBody}>Cannot newly check an expired fund</td><td style={cellBody}><MetaChip kind="red">Block</MetaChip></td><td style={cellBody}>This fund expired Nd ago and can no longer accept new tags</td></tr>
          <tr><td style={cellBody}>Summary</td><td style={cellBody}>None (no min/max)</td><td style={cellBody}><MetaChip kind="neutral">—</MetaChip></td><td style={cellBody}>—</td></tr>
        </tbody>
      </table>
    </>
  );
}

function RequirementLifecycle() {
  return (
    <>
      <p>
        Requirements move through a six-state lifecycle. Most transitions are user-driven; one
        (Blocked) is system-written. Every transition writes a <em>Status</em> activity update on the
        parent entry, so the thread reads as the audit trail.
      </p>
      <table style={tableStyle}>
        <thead>
          <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
            <th style={cellHead}>From</th>
            <th style={cellHead}>To</th>
            <th style={cellHead}>Who</th>
            <th style={cellHead}>Required input</th>
            <th style={cellHead}>Side effect</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style={cellBody}><strong>pending</strong></td><td style={cellBody}>requested</td><td style={cellBody}>Any housing staff</td><td style={cellBody}>— (optional note)</td><td style={cellBody}>Status update on entry</td></tr>
          <tr><td style={cellBody}><strong>requested</strong></td><td style={cellBody}>received</td><td style={cellBody}>Any housing staff</td><td style={cellBody}>file upload (doc/form) OR value (data) OR attestation (verify)</td><td style={cellBody}>Status update; counts toward gate</td></tr>
          <tr><td style={cellBody}><strong>pending</strong></td><td style={cellBody}>blocked</td><td style={cellBody}>System (external check kind only)</td><td style={cellBody}>External submission written</td><td style={cellBody}>Status update with "Submitted to partner" body</td></tr>
          <tr><td style={cellBody}><strong>blocked</strong></td><td style={cellBody}>received</td><td style={cellBody}>System (external check kind only)</td><td style={cellBody}>External returns positive</td><td style={cellBody}>Status update; counts toward gate</td></tr>
          <tr><td style={cellBody}><strong>*</strong></td><td style={cellBody}>waived</td><td style={cellBody}>Any housing staff</td><td style={cellBody}><strong>reason</strong> (required)</td><td style={cellBody}>Status update; counts toward gate</td></tr>
          <tr><td style={cellBody}><strong>*</strong></td><td style={cellBody}>na</td><td style={cellBody}>Any housing staff</td><td style={cellBody}><strong>reason</strong> (required)</td><td style={cellBody}>Status update; counts toward gate</td></tr>
          <tr><td style={cellBody}><strong>received / waived / na</strong></td><td style={cellBody}>requested or pending</td><td style={cellBody}>Any housing staff</td><td style={cellBody}>— ("this was wrong, redo")</td><td style={cellBody}>Status update with delta. DOES NOT undo prior file uploads — those remain attached.</td></tr>
        </tbody>
      </table>

      <Callout kind="ok" title="Met = received | waived | na">
        These three statuses count toward the Convert-to-Project gate. <strong>Pending</strong>,
        <strong> requested</strong>, and <strong>blocked</strong> do NOT. The gate only considers
        items where <MonoCode>required = true</MonoCode> on the instance.
      </Callout>

      <Callout kind="info" title="On Convert-to-Project">
        All instances are copied forward to a new <MonoCode>project_requirements</MonoCode> set,
        preserving status, kind, value, and via[]. The original instances on the comm-log entry
        remain in place; the project's list is the live, editable copy from here on. Pending
        requirements continue accumulating status updates on the project.
      </Callout>

      <Callout kind="warn" title="Override audit">
        If Convert-to-Project is invoked with unmet required requirements and the user ticks
        Override, the project is created AND an <MonoCode>override_reason</MonoCode> +{" "}
        <MonoCode>override_by</MonoCode> is recorded on the project. Surface this on the project
        page somewhere visible — do not hide it in logs.
      </Callout>

      <Callout kind="note" title="Ad-hoc requirements">
        Kaitlyn can add a one-off requirement to a specific intake via the '+ ad-hoc requirement'
        link in the panel. Ad-hoc rows are tinted amber and tagged. Fields: label (required), kind
        (required), required toggle (default on). Ad-hoc requirements DO count toward the gate if{" "}
        <MonoCode>required = true</MonoCode>.
      </Callout>
    </>
  );
}

function SubjectSpec() {
  return (
    <>
      <p>Per David's clarification: Subject is derived on save, editable post-create.</p>
      <pre style={{
        fontFamily: "ui-monospace, Menlo, monospace", fontSize: 12,
        background: "var(--calv-slate)", color: "#fff",
        padding: "14px 18px", borderRadius: 4, overflow: "auto",
        lineHeight: 1.55,
      }}>{`# pseudo:
subject = user_provided
       || (address ? "{street} · {city}" : null)
       || "COMM-{id} · Untitled"

# user can override anytime; once overridden, NEVER auto-rewritten.
# track \`subject_overridden_at\` so the system knows not to clobber.`}</pre>
      <Callout kind="info" title="Why the override flag">
        Without it, if Kaitlyn renames a Subject and then later fills in the address, the rename would
        get clobbered on next save. Set <MonoCode>subject_overridden_at</MonoCode> whenever the field is
        touched by a human.
      </Callout>
    </>
  );
}

function Shortcuts() {
  return (
    <table style={tableStyle}>
      <thead>
        <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
          <th style={cellHead}>Key</th>
          <th style={cellHead}>Where</th>
          <th style={cellHead}>Action</th>
        </tr>
      </thead>
      <tbody>
        <Sc k="⌘ P" where="Anywhere in Edit form" what="Open project picker" />
        <Sc k="⌘ K" where="Anywhere in Edit form" what="Open command jump (find any field by name) — defer to v1.1 unless cheap" />
        <Sc k="⌘ ⏎" where="Edit form / Composer" what="Save & close · or submit update" />
        <Sc k="⌘ ⏎ N" where="Edit form" what="Save & open a new blank entry" />
        <Sc k="Esc" where="Edit form / Viewer / Picker" what="Cancel / close" />
        <Sc k="E" where="Viewer (when focused)" what="Open Edit form" />
        <Sc k="N" where="Viewer (when focused)" what="Focus the Activity composer" />
        <Sc k="↑ / ↓" where="Project picker" what="Navigate list" />
        <Sc k="⏎" where="Project picker" what="Select highlighted" />
        <Sc k="N" where="Project picker" what="Create new project" />
        <Sc k="Tab / Shift-Tab" where="Edit form" what="Natural field order (left→right, top→bottom)" />
      </tbody>
    </table>
  );
}
function Sc({ k, where, what }) {
  return (
    <tr>
      <td style={{ ...cellBody, fontFamily: "ui-monospace, Menlo, monospace", fontWeight: 600 }}>{k}</td>
      <td style={cellBody}>{where}</td>
      <td style={cellBody}>{what}</td>
    </tr>
  );
}

function ComponentInventory() {
  return (
    <>
      <p>Reusable atoms. Most should live in the existing Housing module's Blade component library.</p>
      <ul style={{ paddingLeft: 22, lineHeight: 1.7 }}>
        <li><MonoCode>x-comm.modal-chrome</MonoCode> — modal title bar with breadcrumb + ID + close.</li>
        <li><MonoCode>x-comm.project-banner</MonoCode> — sticky teal banner with code chip, name, meta line, actions.</li>
        <li><MonoCode>x-comm.meta-chip</MonoCode> — pill in slate/teal/red/sage/amber/sand variants.</li>
        <li><MonoCode>x-comm.priority-badge</MonoCode> — colored dot + priority label.</li>
        <li><MonoCode>x-comm.fund-chip</MonoCode> — checkable chip with code, name, status dot, days-left.</li>
        <li><MonoCode>x-comm.service-chip</MonoCode> — program-tagged chip (CAH/HCP/SSS variants).</li>
        <li><MonoCode>x-comm.update-item</MonoCode> — Activity Updates feed row (kind chip + body + meta).</li>
        <li><MonoCode>x-comm.followup-composer</MonoCode> — typed compose box + reminder toggle.</li>
        <li><MonoCode>x-comm.kbd</MonoCode> — keyboard chip; render inline in labels and footers.</li>
        <li><MonoCode>x-comm.tag-input</MonoCode> — Alpine combobox for tag search/create.</li>
        <li><MonoCode>x-comm.project-picker</MonoCode> — Alpine combobox for project search; ⌘P opens.</li>
        <li><MonoCode>x-comm.requirements-panel</MonoCode> — progress bar + grouped row list with kind icons + status chips.</li>
        <li><MonoCode>x-comm.requirements-summary-pill</MonoCode> — compact 'N of M met' pill for the edit form rail.</li>
        <li><MonoCode>x-comm.requirements-gate</MonoCode> — Convert-to-Project block surface with unmet list + audit-logged override.</li>
        <li><MonoCode>x-comm.requirement-row</MonoCode> — single requirement row used by the panel and the gate.</li>
        <li><MonoCode>x-comm.req-status-chip</MonoCode> / <MonoCode>x-comm.req-kind-icon</MonoCode> — small primitives shared across all the above.
      </ul>
    </>
  );
}

function ImplBreakdown() {
  return (
    <>
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 12 }}>
        <ImplCol kind="livewire" title="Livewire 3" items={[
          "Edit form (both states)",
          "Viewer (both states)",
          "Project banner",
          "Fund-code panel (re-renders on project change)",
          "Service chip panel",
          "Activity Updates feed",
          "Activity composer submit",
          "Convert-to-Project modal submit",
          "Validation + flash",
          "Subject derivation on save",
          "Requirements panel (read + status chips)",
          "Requirement status transitions (writes Status activity updates)",
          "Requirements gate computation",
          "Carry-forward to project_requirements on promote",
        ]} />
        <ImplCol kind="alpine" title="Alpine 3" items={[
          "Project picker combobox + ⌘P global keymap",
          "Tag input (search + create)",
          "Inline contact suggester (project roster)",
          "Keyboard shortcuts for the form (Tab, ⌘⏎, ⌘⏎N, Esc)",
          "Viewer E / N hotkeys",
          "Hover-reveals on fund expiry tooltip",
          "Composer kind picker",
          "Fund-code expiry callout show/hide",
          "+ ad-hoc requirement inline form",
          "Requirement action drawer (Request / Mark received / Waive)",
        ]} />
        <ImplCol kind="island" title="React/Vue island" items={[
          "(nothing required in v1)",
          "Defer until Q3 says editor needed",
          "If added later: Tiptap on the Summary field only · mount via wire:ignore",
        ]} />
      </div>
    </>
  );
}

function ImplCol({ kind, title, items }) {
  const color = kind === "livewire" ? "var(--calv-teal)" :
                kind === "alpine" ? "var(--calv-amber)" :
                "var(--calv-slate-65)";
  return (
    <div style={{
      border: "1px solid var(--calv-slate-15)",
      borderTop: `4px solid ${color}`,
      borderRadius: 4, padding: "14px 16px",
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
        letterSpacing: ".1em", textTransform: "uppercase",
        color, marginBottom: 8,
      }}>{title}</div>
      <ul style={{ paddingLeft: 16, margin: 0, fontSize: 13, lineHeight: 1.6 }}>
        {items.map((s, i) => <li key={i}>{s}</li>)}
      </ul>
    </div>
  );
}

function OpenQs() {
  const qs = [
    { n: "Q1", status: "answered", q: "The 'Auto-populates Subject' mystery in the legacy form.", a: "Resolved: Subject = address-if-any, else 'COMM-{id} · Untitled'. Tracked via subject_overridden_at flag." },
    { n: "Q2", status: "open", priority: "med", q: "Are program services genuinely project-scoped? D2 surfaces 3 project-suggested and hides the other 17. Confirm before locking the suggested-only default." },
    { n: "Q3", status: "open", priority: "high", q: "Typical Summary length? If &gt;300 words, ship the Tiptap editor in v1.1. If 1–2 sentences, the textarea is fine forever." },
    { n: "Q4", status: "open", priority: "med", q: "Contact identity — is the inline name+role+phone+email transient text, or do we tie an entry to a persistent contact record with prior-call history?" },
    { n: "Q5", status: "open", priority: "low", q: "Category sub-types as first-letter shortcuts? (C=Case work, G=General, R=Referral) Cheap win if values are stable." },
    { n: "Q6", status: "open", priority: "low", q: "Fund-code nullable starts_at: confirm null = 'perpetual' (gray dot) rather than 'unknown' (which would deserve different UI)." },
    { n: "Q7", status: "open", priority: "med", q: "Should the Comm Log be a modal at all? D3's two-pane begged to be a full route. Decide before v2 ships, even if v1 stays modal." },
    { n: "Q8", status: "open", priority: "low", q: "Other staff usage frequency — intake / other PMs. Affects whether a 'guided' mode behind a Tweaks toggle is worth building." },
    { n: "Q9", status: "open", priority: "high", q: "Priority semantics — chip-only or does it drive list ordering, daily queue, notifications, SLA? Affects whether priority_changed_at is needed." },
    { n: "Q10", status: "open", priority: "med", q: "Linked Records — what record types can be linked? Other comm entries, projects, contacts, external case-mgmt? Drives '+ link' picker scope." },
    { n: "Q11", status: "open", priority: "low", q: "Activity Update kinds — is Note/Follow-up/Action/Email/Status the right set? Should Follow-up auto-create a reminder, or stay opt-in like the mock?" },
    { n: "Q12", status: "open", priority: "high", q: "Detaching a project from an entry — what happens to fund-code tags that came from the project? Re-confirm by user, or strip silently?" },
    { n: "Q13", status: "open", priority: "high", q: "Requirement templates per program service — confirm the seed list. The mocks use plausible-but-fake items per service (Photo ID, Proof of income, Hardship statement, PHFA HEMAP eligibility, etc.). Kaitlyn + David need to provide / approve the real list before seed migration." },
    { n: "Q14", status: "open", priority: "med", q: "Requirement lifecycle — confirm the six states (pending / requested / received / waived / na / blocked) are right. Are there service-specific states (e.g. 'expired' for time-bound docs like 60-day income verification)?" },
    { n: "Q15", status: "open", priority: "med", q: "Override audit — where should override_reason surface on the project page? Banner? Activity feed? Both? Confirm with David before scoping the project-page changes." },
    { n: "Q16", status: "open", priority: "low", q: "File uploads for doc/form requirements — do we have an existing upload pipeline and storage convention in the housing module? If not, what's the bucket / virus-scan / size-limit story?" },
    { n: "Q17", status: "open", priority: "low", q: "Service untagging — when Kaitlyn removes a service from an intake, what happens to the requirement instances that came from it? Strip if still pending? Leave as orphans tagged with via=[removed]? Likely the latter; confirm." },
  ];
  const sorted = qs.slice().sort((a, b) => {
    const order = { high: 0, med: 1, low: 2, undefined: 3 };
    return (order[a.priority] || 3) - (order[b.priority] || 3);
  });
  return (
    <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
      {sorted.map(q => (
        <div key={q.n} style={{
          border: "1px solid var(--calv-slate-15)",
          borderLeft: q.status === "answered" ? "3px solid var(--calv-sage)" :
                      q.priority === "high" ? "3px solid var(--calv-red)" :
                      q.priority === "med" ? "3px solid var(--calv-amber)" :
                      "3px solid var(--calv-slate-35)",
          borderRadius: 4, padding: "12px 14px", background: "#fff",
        }}>
          <div style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 6 }}>
            <span style={{
              fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
              letterSpacing: ".08em", textTransform: "uppercase",
              color: q.status === "answered" ? "var(--calv-sage)" : "var(--calv-slate)",
            }}>{q.n}</span>
            <span style={{
              fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
              letterSpacing: ".08em", textTransform: "uppercase",
              color: q.status === "answered" ? "var(--calv-sage)" :
                     q.priority === "high" ? "var(--calv-red)" :
                     q.priority === "med" ? "#8A6410" :
                     "var(--calv-slate-65)",
            }}>{q.status === "answered" ? "Answered" : `Open · ${q.priority}`}</span>
          </div>
          <p style={{ margin: 0, fontSize: 13, lineHeight: 1.55 }}>{q.q}</p>
          {q.a && (
            <p style={{
              margin: "10px 0 0", padding: "8px 10px",
              background: "var(--calv-sage-15)", borderRadius: 3,
              fontSize: 12.5, color: "var(--calv-slate)", lineHeight: 1.5,
            }}>{q.a}</p>
          )}
        </div>
      ))}
    </div>
  );
}

Object.assign(window, { HandoffDoc });
