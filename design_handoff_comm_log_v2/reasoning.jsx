// reasoning.jsx — Rationale, trade-off matrix, recommendation, open questions.
// Editorial-leaning layout; the long-form companion to the three direction artboards.

function ReasoningPage() {
  return (
    <div style={{
      padding: "44px 56px 56px", background: "#fff",
      fontFamily: "var(--font-body)", color: "var(--calv-slate)", lineHeight: 1.55,
    }}>

      {/* ── Header ── */}
      <header style={{ marginBottom: 36, paddingBottom: 24, borderBottom: "2px solid var(--calv-slate)" }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
          letterSpacing: ".14em", textTransform: "uppercase",
          color: "var(--calv-red)", marginBottom: 12,
        }}>Communication Log · UX Redesign · 23 May 2026</div>
        <h1 style={{
          fontFamily: "var(--font-h1)", fontWeight: 700,
          fontSize: 56, letterSpacing: ".01em", textTransform: "uppercase",
          color: "var(--calv-slate)", margin: 0, lineHeight: .98,
          maxWidth: 980,
        }}>
          Three directions for Kaitlyn's form. <span style={{ color: "var(--calv-red)" }}>One recommendation.</span>
        </h1>
        <p style={{
          fontSize: 17, color: "var(--calv-slate-65)", maxWidth: 820,
          margin: "16px 0 0", lineHeight: 1.5,
        }}>
          The current form treats every field as equal. The redesign starts from a single premise: the
          form does five jobs (log meta · contact · address · program tagging · fund allocation), and they
          don't deserve equal real estate.
        </p>
      </header>

      {/* ── The three directions, side by side ── */}
      <section style={{ marginBottom: 44 }}>
        <SectionHead kicker="Section 1">The three directions</SectionHead>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 18, marginTop: 22 }}>
          <DirectionCard
            number="01" accent="var(--calv-red)"
            name="The Dense Sheet"
            tag="Single-page · keyboard-first"
            philosophy="Every field is visible. Hierarchy is typographic, not structural. Designed for the power user who types and tabs without looking."
            optimizes={["Daily-use speed (Kaitlyn 10×/day)", "Glanceability of all data at once", "Keyboard shortcuts as first-class affordances"]}
            sacrifices={["Visual breathing room", "Novice-friendly hand-holding", "The wall-of-fields impression for casual users"]}
            stack="Trivially Livewire 3. Two Alpine sprinkles: ⌘K command-jump + the autosave indicator. No island needed."
          />
          <DirectionCard
            number="02" accent="var(--calv-teal)"
            name="Project-First"
            tag="Composition reshapes around the project"
            philosophy='"Related to a Project?" stops being a buried dropdown and becomes the first decision. Fund codes only exist when a project does. Same fields — wildly different shape.'
            optimizes={["The architectural truth: project scopes everything", "Removing irrelevant fields by default", "Fund-code accuracy (no more wrong-project assignments)"]}
            sacrifices={["A 2-state mental model for new users", "Direct entry without first picking a project", "Some screen real-estate to the project banner"]}
            stack="Mostly Livewire — wire:model the project selector and let the back-end re-render fund/service partials. The ⌘K project picker is one well-scoped Alpine component."
          />
          <DirectionCard
            number="03" accent="var(--calv-sand-65)" accentText="var(--calv-slate)"
            name="Notes-First"
            tag="Two-pane: meta rail + case-notes hero"
            philosophy="The summary is the real artifact. Treat it like a doc, not a form field. All other inputs collapse into a vertical rail that stays out of the way."
            optimizes={["Substantive case notes (the real product of a call)", "Editor affordances: paragraphs, lists, templates, mentions", "Long entries with structure — currently impossible"]}
            sacrifices={["Field density per inch — only 1 column of meta now", "Pure form-filling workflows (more chrome around editing)", "A meaningful chunk of complexity in the editor itself"]}
            stack="Editor needs a real component — Tiptap-on-Vue/React or Lexical island. Meta rail is still Livewire. This is the only direction where a stack shift is worth pricing."
          />
        </div>
      </section>

      {/* ── Trade-off matrix ── */}
      <section style={{ marginBottom: 44 }}>
        <SectionHead kicker="Section 2">Trade-off matrix</SectionHead>
        <p style={{ fontSize: 14, color: "var(--calv-slate-65)", maxWidth: 720, marginTop: 6, marginBottom: 16 }}>
          Scored against the criteria that matter for Kaitlyn-as-power-user. ● strong · ◐ partial · ○ weak.
          Bold cells are the deciding edges between directions.
        </p>

        <div style={{
          border: "1px solid var(--calv-slate-15)", borderRadius: 4, overflow: "hidden",
          background: "#fff",
        }}>
          <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 13 }}>
            <thead>
              <tr style={{ background: "var(--calv-slate)", color: "#fff" }}>
                <th style={th}>Criterion</th>
                <th style={th}>Weight</th>
                <th style={th}>01 · Dense Sheet</th>
                <th style={th}>02 · Project-First</th>
                <th style={th}>03 · Notes-First</th>
                <th style={th}>Why this matters</th>
              </tr>
            </thead>
            <tbody>
              <Row crit="Daily-use speed" weight="High" a="●" b="●" c="◐"
                why="Kaitlyn opens this form 10×/day. Two-pane chrome costs keystrokes per entry." />
              <Row crit="Keyboard fluency" weight="High" a="●" b="●" c="◐"
                why="Tab order, ⌘K, ⌘Enter — D1/D2 are designed around them. D3 is more mouse-friendly." />
              <Row crit="Fund-code accuracy" weight="High" a="◐" b="●" c="◐" hi="b"
                why="Only D2 makes wrong-project fund assignments structurally impossible by default." />
              <Row crit="Expiry visibility" weight="Med" a="●" b="●" c="●"
                why="All three use the color-dot + days-left pattern called out in the brief." />
              <Row crit="Case-notes quality" weight="High" a="◐" b="◐" c="●" hi="c"
                why="The Summary is the artifact. D3 treats it as a real document; the others as a textarea." />
              <Row crit="General-intake fit" weight="Med" a="◐" b="●" c="◐" hi="b"
                why="Entries unrelated to a project are first-class in D2; D1/D3 hide them in an opt-out." />
              <Row crit="Onboarding for non-Kaitlyn users" weight="Low" a="○" b="●" c="◐"
                why="D2's two-state structure is self-teaching. D1's density is hostile to first-time use." />
              <Row crit="Implementation cost" weight="Med" a="●" b="●" c="◐"
                why="D1/D2 ship in Livewire + light Alpine. D3 needs a real editor component." />
              <Row crit="Stack risk" weight="Med" a="●" b="●" c="◐"
                why="Tiptap/Lexical adds a build target. Worth it only if Kaitlyn's notes warrant it." />
              <Row crit="Mobile bonus" weight="Low" a="○" b="◐" c="◐"
                why="Two-pane collapses cleanly; D1's density is desktop-only." />
            </tbody>
          </table>
        </div>
      </section>

      {/* ── Recommendation ── */}
      <section style={{ marginBottom: 44 }}>
        <SectionHead kicker="Section 3">Recommendation</SectionHead>

        <div style={{
          marginTop: 16, display: "grid", gridTemplateColumns: "260px 1fr", gap: 28,
          background: "var(--calv-sand-15)", border: "1px solid var(--calv-sand-35)",
          borderRadius: 4, padding: "24px 28px",
        }}>
          <div>
            <div style={{
              fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
              letterSpacing: ".12em", textTransform: "uppercase",
              color: "var(--calv-slate-65)", marginBottom: 6,
            }}>Ship</div>
            <div style={{
              fontFamily: "var(--font-h1)", fontSize: 44, letterSpacing: ".02em",
              textTransform: "uppercase", color: "var(--calv-red)", lineHeight: .95,
            }}>Direction 02</div>
            <div style={{
              fontFamily: "var(--font-h1)", fontSize: 18, letterSpacing: ".02em",
              textTransform: "uppercase", color: "var(--calv-slate)", marginTop: 4,
            }}>Project-First</div>
            <div style={{ marginTop: 12, fontSize: 12, color: "var(--calv-slate-65)" }}>
              with one borrowed pattern from <strong>D1</strong> (dense top-row meta)
              and an opt-in <strong>D3</strong> notes-zoom mode behind <Kbd>⌘.</Kbd>
            </div>
          </div>

          <div style={{ fontSize: 15, lineHeight: 1.65 }}>
            <p style={{ margin: 0 }}>
              <strong>Direction 02 reflects the architecture that's actually true</strong> — the project is the
              spine of housing work; the rest is its scaffolding. Making project the first input (with
              <em> General intake</em> as a one-click peer) turns fund-code accuracy from a discipline problem
              into a design property. Kaitlyn cannot pick a fund code that doesn't belong to her project,
              because by default she cannot see fund codes that don't belong to her project.
            </p>
            <p style={{ margin: "12px 0 0" }}>
              D2 also costs the least to ship. The form composition swap is wire:model + a re-rendered
              partial — exactly what Livewire 3 was built for. The only Alpine piece is the ⌘P project
              picker. No island, no stack shift.
            </p>
            <p style={{ margin: "12px 0 0" }}>
              <strong>Borrow from D1:</strong> the single dense top-row (Date · Type · Direction · Category ·
              Subject) is faster than any column layout. Use it inside D2 once the project is chosen.
            </p>
            <p style={{ margin: "12px 0 0" }}>
              <strong>Borrow from D3:</strong> case notes deserve more than a textarea. Add a <Kbd>⌘.</Kbd>
              shortcut that expands the Summary into a focus-mode editor over the rest of the form. Not the
              full Tiptap install — just <em>autosize + autosave + a /-menu for templates</em>. Quick win.
            </p>
            <p style={{ margin: "12px 0 0", color: "var(--calv-slate)", fontWeight: 600 }}>
              The sequence: ship D2, watch Kaitlyn for a month, then decide whether the editor in D3 earns
              its complexity.
            </p>
          </div>
        </div>
      </section>

      {/* ── New findings from the viewer screenshot ── */}
      <section style={{ marginBottom: 44 }}>
        <SectionHead kicker="Section 4">What the viewer screenshot changed</SectionHead>
        <p style={{ fontSize: 14, color: "var(--calv-slate-65)", maxWidth: 760, marginTop: 6, marginBottom: 16 }}>
          After the first pass, we got a look at the read-only view modal. It surfaced four concepts that weren't
          in the original brief — and three of them <em>strongly</em> reinforce the Direction 02 recommendation.
        </p>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
          <FindingCard kicker="Finding 1" accent="var(--calv-teal)" title="Activity Updates & Follow-ups">
            Each entry has a thread of typed updates (Note / Follow-up / Action / Email / Status). This is the
            mechanism for “track application requirements” Kaitlyn mentioned. The viewer artboards add a typed
            composer at the top of the thread and a follow-up reminder toggle. <strong>Implication for edit:</strong>
            keep the edit form lean — the entry's <em>Summary</em> is its opening statement; everything else lives in the
            update thread. Don't bloat the edit form with a thread editor.
          </FindingCard>
          <FindingCard kicker="Finding 2" accent="var(--calv-red)" title="Convert to Project">
            General-intake entries can be promoted into projects. This validates Direction 02 even more strongly:
            <em> the project IS the spine</em>, and the system already has a workflow to climb from intake to project.
            D2 makes that promotion path visible from the start (general-intake state → “Convert to project” CTA in
            the banner of the viewer). D1 and D3 don't model this concept at all.
          </FindingCard>
          <FindingCard kicker="Finding 3" accent="var(--calv-amber)" title="Priority">
            New field. Trivial to add to all three directions as a fifth meta chip (Urgent / High / Normal / Low,
            color-dot indicator). Added to D2's edit row in this revision. <strong>Open Q9 below</strong> asks whether
            this drives any list-view ranking or notifications, which would change its weight.
          </FindingCard>
          <FindingCard kicker="Finding 4" accent="var(--calv-sage)" title="Linked Records">
            Entries can link to other entries or projects. The current viewer has the panel but it's empty. Treat
            this as a <em>view-mode-only</em> surface for now — the edit form doesn't need to expose it until we learn
            what kinds of records link. See Q10 below.
          </FindingCard>
        </div>
      </section>

      {/* ── Stack implications callout ── */}
      <section style={{ marginBottom: 44 }}>
        <SectionHead kicker="Section 5">Stack implications</SectionHead>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 12, marginTop: 18 }}>
          <StackCell label="Trivial in Livewire 3">
            Project selector + composition swap (wire:model + partial render). Fund-code & service
            scoping. Tag autocomplete. Validation + autosave. Address-derived Subject.
          </StackCell>
          <StackCell label="Light Alpine sprinkles" accent="var(--calv-teal)">
            ⌘P / ⌘K command palette. ⌘. notes-zoom toggle. Hover-reveals on fund-code expiry.
            "Pull contact from project roster" inline suggester.
          </StackCell>
          <StackCell label="React/Vue island — only if" accent="var(--calv-amber)">
            We commit to a real notes editor (Tiptap/Lexical) with /-menu, mentions, paste-cleanup,
            and saved templates. Justifies an island only if Kaitlyn's typical entry &gt; ~300 words.
          </StackCell>
          <StackCell label="Does NOT justify a stack shift" accent="var(--calv-slate-65)">
            None of D1/D2/D3 requires moving off Laravel + Livewire as a baseline. Even D3's editor
            can live as a single React component mounted into one form pane.
          </StackCell>
        </div>
      </section>

      {/* ── Open questions ── */}
      <section style={{ marginBottom: 24 }}>
        <SectionHead kicker="Section 6">Open questions for Kaitlyn & David</SectionHead>

        <div style={{ marginTop: 18, display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
          <QCard num="Q1" status="answered" answer={(
            <>Per David: <em>new entries set Subject to the address, or to an iterative title if no address is provided.</em> All three directions treat Subject as a derived field shown in the chrome, editable on hover. The "Auto-populates Subject" hint in the existing form is just stale label text.</>
          )}>
            The "Auto-populates Subject" hint references a Subject field nobody can see. Where does it live?
          </QCard>

          <QCard num="Q2">
            Should program services be project-scoped the same way fund codes are? D2 currently surfaces 3
            project-suggested services and hides the other 17 behind a search. Confirm that's the right
            default — or that some services genuinely cross-cut projects and should stay always-visible.
          </QCard>

          <QCard num="Q3">
            Kaitlyn's typical entry: how long is it? If &gt;300 words is common, the D3 editor pays for
            itself. If most entries are 1–2 sentences, a textarea is fine.
          </QCard>

          <QCard num="Q4">
            "Contact" right now is a flat name+role+phone+email. Should we tie an entry to a <em>persistent
            contact record</em> (with prior-call history) or keep it as transient text? Affects how aggressive
            the "Pull from project roster" suggester should be.
          </QCard>

          <QCard num="Q5">
            Are there sub-types of <em>Category</em> we should surface as keyboard letters? (e.g. C=Case work,
            G=General, R=Referral.) Cheap power-user win if so.
          </QCard>

          <QCard num="Q6">
            Fund codes with <em>nullable</em> dates: confirm a missing <code>starts_at</code> means "perpetual"
            (rendered as a gray dot, no warning) rather than "unknown" (which would deserve different UI).
          </QCard>

          <QCard num="Q7">
            Should this be a modal at all? D3's two-pane layout begs to be a full-page route — better for
            long notes, better for permalinking. The current modal feels like a holdover.
          </QCard>

          <QCard num="Q8">
            Do other staff (intake, other PMs) actually use this form? If yes — at what frequency? It
            changes whether we'd add a second "guided" mode behind a Tweaks-style toggle.
          </QCard>

          <QCard num="Q9">
            <strong>Priority</strong> — does it drive anything beyond a chip in the viewer? List ranking, daily
            queue, notification thresholds? Cheap to add visually; expensive only if it implies a workflow.
          </QCard>

          <QCard num="Q10">
            <strong>Linked records</strong> — what types can be linked? Other comm entries, projects, contacts,
            external case-mgmt records? Affects whether “+ link” is a search-over-everything affordance or a
            scoped picker per type.
          </QCard>

          <QCard num="Q11">
            <strong>Activity updates</strong> — are the kinds (Note / Follow-up / Action / Email / Status) the right
            set? Should “Follow-up” automatically schedule a reminder, or is the date opt-in like in the
            mock? Should “Status” be system-only?
          </QCard>

          <QCard num="Q12">
            <strong>Convert to project</strong> — what does the promotion modal look like in this app today? If it
            already exists, the D2 general-intake viewer banner can deep-link to it. If not, we'd want to
            design that flow as a follow-on.
          </QCard>
        </div>
      </section>

      {/* ── Foot ── */}
      <footer style={{
        marginTop: 36, paddingTop: 20, borderTop: "1px solid var(--calv-slate-15)",
        display: "flex", alignItems: "center", justifyContent: "space-between",
        fontSize: 12, color: "var(--calv-slate-65)",
      }}>
        <span>CACLV · Housing module · Communication Log v2 design proposal</span>
        <span>Three artboards per direction · this page is the companion rationale</span>
      </footer>
    </div>
  );
}

const th = {
  fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
  letterSpacing: ".08em", textTransform: "uppercase",
  textAlign: "left", padding: "10px 12px", verticalAlign: "bottom",
};

function Row({ crit, weight, a, b, c, why, hi }) {
  const cell = (val, key) => (
    <td style={{
      padding: "10px 12px", borderBottom: "1px solid var(--calv-slate-15)",
      textAlign: "center", fontSize: 18, color: "var(--calv-slate)",
      fontWeight: 600,
      background: hi === key ? "var(--calv-amber-15)" : "transparent",
    }}>
      {val}
    </td>
  );
  return (
    <tr>
      <td style={{
        padding: "10px 12px", borderBottom: "1px solid var(--calv-slate-15)",
        fontWeight: 600, color: "var(--calv-slate)",
      }}>{crit}</td>
      <td style={{
        padding: "10px 12px", borderBottom: "1px solid var(--calv-slate-15)",
        fontSize: 11, color: "var(--calv-slate-65)", textTransform: "uppercase", letterSpacing: ".04em",
      }}>{weight}</td>
      {cell(a, "a")}
      {cell(b, "b")}
      {cell(c, "c")}
      <td style={{
        padding: "10px 12px", borderBottom: "1px solid var(--calv-slate-15)",
        fontSize: 12, color: "var(--calv-slate-65)", lineHeight: 1.5,
      }}>{why}</td>
    </tr>
  );
}

function SectionHead({ kicker, children }) {
  return (
    <header>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".14em", textTransform: "uppercase",
        color: "var(--calv-red)", marginBottom: 6,
      }}>{kicker}</div>
      <h2 style={{
        fontFamily: "var(--font-h1)", fontSize: 32, fontWeight: 700,
        letterSpacing: ".02em", textTransform: "uppercase",
        color: "var(--calv-slate)", margin: 0, lineHeight: 1,
      }}>{children}</h2>
    </header>
  );
}

function DirectionCard({ number, accent, accentText = "#fff", name, tag, philosophy, optimizes, sacrifices, stack }) {
  return (
    <article style={{
      border: "1px solid var(--calv-slate-15)", borderRadius: 4,
      background: "#fff", overflow: "hidden",
      display: "flex", flexDirection: "column",
    }}>
      <header style={{
        background: accent, color: accentText, padding: "16px 18px",
      }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
          letterSpacing: ".12em", textTransform: "uppercase",
          opacity: .7, marginBottom: 6,
        }}>Direction {number}</div>
        <div style={{
          fontFamily: "var(--font-h1)", fontSize: 26, letterSpacing: ".02em",
          textTransform: "uppercase", lineHeight: 1,
        }}>{name}</div>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 600,
          letterSpacing: ".04em", textTransform: "uppercase",
          marginTop: 6, opacity: .85,
        }}>{tag}</div>
      </header>
      <div style={{ padding: "16px 18px", flex: 1, display: "flex", flexDirection: "column", gap: 12 }}>
        <p style={{ margin: 0, fontSize: 14, lineHeight: 1.55 }}>{philosophy}</p>

        <div>
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-sage)", marginBottom: 4,
          }}>Optimizes for</div>
          <ul style={{ margin: 0, paddingLeft: 18, fontSize: 13, lineHeight: 1.5 }}>
            {optimizes.map((o, i) => <li key={i} style={{ marginBottom: 2 }}>{o}</li>)}
          </ul>
        </div>

        <div>
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-red)", marginBottom: 4,
          }}>Sacrifices</div>
          <ul style={{ margin: 0, paddingLeft: 18, fontSize: 13, lineHeight: 1.5 }}>
            {sacrifices.map((s, i) => <li key={i} style={{ marginBottom: 2 }}>{s}</li>)}
          </ul>
        </div>

        <div style={{
          marginTop: "auto", paddingTop: 12, borderTop: "1px dashed var(--calv-slate-15)",
        }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate-65)", marginBottom: 4,
          }}>Stack</div>
          <p style={{ margin: 0, fontSize: 12, lineHeight: 1.5, color: "var(--calv-slate-65)" }}>{stack}</p>
        </div>
      </div>
    </article>
  );
}

function StackCell({ label, accent = "var(--calv-sage)", children }) {
  return (
    <div style={{
      border: "1px solid var(--calv-slate-15)",
      borderTop: `4px solid ${accent}`,
      borderRadius: 4, padding: "14px 16px",
      fontSize: 13, lineHeight: 1.55, color: "var(--calv-slate)",
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".1em", textTransform: "uppercase",
        color: accent, marginBottom: 6,
      }}>{label}</div>
      {children}
    </div>
  );
}

function FindingCard({ kicker, accent, title, children }) {
  return (
    <article style={{
      border: "1px solid var(--calv-slate-15)",
      borderTop: `4px solid ${accent}`,
      borderRadius: 4, padding: "14px 16px", background: "#fff",
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".12em", textTransform: "uppercase",
        color: accent, marginBottom: 6,
      }}>{kicker}</div>
      <h4 style={{
        fontFamily: "var(--font-h1)", fontSize: 22, letterSpacing: ".02em",
        textTransform: "uppercase", color: "var(--calv-slate)",
        margin: "0 0 8px", lineHeight: 1.05,
      }}>{title}</h4>
      <p style={{ margin: 0, fontSize: 14, lineHeight: 1.55, color: "var(--calv-slate)" }}>{children}</p>
    </article>
  );
}

function QCard({ num, children, status, answer }) {
  const isAnswered = status === "answered";
  return (
    <div style={{
      border: "1px solid var(--calv-slate-15)",
      borderLeft: `3px solid ${isAnswered ? "var(--calv-sage)" : "var(--calv-amber)"}`,
      borderRadius: 4, padding: "14px 16px",
      background: "#fff",
    }}>
      <div style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 6 }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
          letterSpacing: ".1em", textTransform: "uppercase",
          color: isAnswered ? "var(--calv-sage)" : "var(--calv-amber)",
        }}>{num}</div>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
          letterSpacing: ".08em", textTransform: "uppercase",
          color: isAnswered ? "var(--calv-sage)" : "var(--calv-slate-65)",
        }}>{isAnswered ? "Answered" : "Open"}</div>
      </div>
      <p style={{ margin: 0, fontSize: 14, lineHeight: 1.55, color: "var(--calv-slate)" }}>{children}</p>
      {answer && (
        <p style={{ margin: "10px 0 0", padding: "10px 12px", background: "var(--calv-sage-15)", borderRadius: 4, fontSize: 13, lineHeight: 1.55, color: "var(--calv-slate)" }}>
          {answer}
        </p>
      )}
    </div>
  );
}

Object.assign(window, { ReasoningPage });
