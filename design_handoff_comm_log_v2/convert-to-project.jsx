// convert-to-project.jsx — Convert-to-Project promotion flow.
// Surfaced from the general-intake VIEWER (sand banner CTA) — promotes
// this entry into a real project, carries data forward, and switches
// the entry's banner to teal afterwards.

function ConvertToProjectModal() {
  return (
    <div style={{
      width: "100%", height: "100%", minHeight: "100%",
      background: "rgba(40,42,44,.55)",
      display: "flex", alignItems: "flex-start", justifyContent: "center",
      padding: "40px 0",
    }}>
      <div style={{
        width: 760, background: "#fff",
        borderRadius: 6, boxShadow: "var(--shadow-lg)",
        overflow: "hidden",
        display: "flex", flexDirection: "column",
      }}>
        {/* Header */}
        <header style={{
          padding: "16px 22px",
          background: "var(--calv-teal)", color: "#fff",
          display: "flex", alignItems: "center", gap: 12,
        }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 10,
            letterSpacing: ".12em", textTransform: "uppercase",
            background: "rgba(255,255,255,.18)", padding: "3px 8px", borderRadius: 3,
          }}>Promote</div>
          <div>
            <div style={{
              fontFamily: "var(--font-h1)", fontSize: 22, letterSpacing: ".02em",
              textTransform: "uppercase", lineHeight: 1,
            }}>Convert to project</div>
            <div style={{ fontSize: 12, marginTop: 4, opacity: .85 }}>
              COMM-7191 · Esperanza Villarroel-Cruz · general intake → new project
            </div>
          </div>
          <span style={{ flex: 1 }} />
          <button style={{
            width: 28, height: 28, border: 0,
            background: "rgba(255,255,255,.12)", color: "#fff",
            borderRadius: 3, cursor: "pointer", fontSize: 14,
          }}>✕</button>
        </header>

        <div style={{ padding: "20px 24px", display: "flex", flexDirection: "column", gap: 18 }}>
          {/* Intro */}
          <p style={{ margin: 0, fontSize: 14, color: "var(--calv-slate)", lineHeight: 1.55 }}>
            Create a new project anchored at this entry's contact and address. The entry stays in place
            and gets re-tagged to the new project; the project page becomes the home for future follow-ups.
          </p>

          {/* REQUIREMENTS GATE — blocks Create unless met (or override) */}
          <RequirementsGate
            items={mergeWithInstances(
              aggregateTemplates(["HCP-RentalHousing"]),
              SAMPLE_REQS_ESPERANZA
            )}
          />

          {/* Project name + code */}
          <CTPField label="Project name" required hint="Suggested from address — edit freely">
            <TI value="519 N New St · Villarroel-Cruz family" />
          </CTPField>

          <div style={{ display: "grid", gridTemplateColumns: "200px 1fr", gap: 12 }}>
            <CTPField label="Project code" hint="Auto-numbered">
              <TI value="HSG-2026-118" mono />
            </CTPField>
            <CTPField label="Lead staff" required>
              <Sel value="Kaitlyn Wise (you)" />
            </CTPField>
          </div>

          {/* Fund codes */}
          <CTPField
            label="Associate fund codes"
            hint="Pick the funds this project will draw from"
          >
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 6 }}>
              <CheckChip kind="teal" label={(
                <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
                  <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F7000</span>
                  WHR
                </span>
              )} sub={<span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}><FundStatusDot status="perpetual" /><span style={{ fontSize: 10 }}>No expiry</span></span>} />
              <CheckChip checked kind="teal" label={(
                <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
                  <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F0623</span>
                  Thriving Health (PHARE)
                </span>
              )} sub={<span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}><FundStatusDot status="perpetual" /><span style={{ fontSize: 10 }}>No expiry</span></span>} />
              <CheckChip checked kind="teal" label={(
                <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
                  <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F0531</span>
                  Allentown Better Homes
                </span>
              )} sub={<span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}><FundStatusDot status="warning" /><span style={{ fontSize: 10 }}>84d</span></span>} />
              <CheckChip kind="teal" label={(
                <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
                  <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600, opacity: .7 }}>F4205</span>
                  <span style={{ opacity: .7 }}>LC Urgent (CDBG-CV)</span>
                </span>
              )} sub={<span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}><FundStatusDot status="soon" /><span style={{ fontSize: 10, color: "var(--calv-red)" }}>7d ⚠</span></span>} />
            </div>
            <div style={{
              marginTop: 8, padding: "8px 10px",
              background: "var(--calv-amber-15)",
              borderLeft: "3px solid var(--calv-amber)",
              borderRadius: 3,
              fontSize: 12, color: "var(--calv-slate)",
              display: "flex", alignItems: "flex-start", gap: 8,
            }}>
              <span style={{ color: "#8A6410", flex: "0 0 auto", marginTop: 1 }}>{Ic.warn}</span>
              <span>
                <strong>F4205 expires in 7 days.</strong> Tagging a new project to it commits any work to closing by 5/30.
                Skip unless that's intentional.
              </span>
            </div>
          </CTPField>

          {/* Program services */}
          <CTPField label="Program services" hint="Suggested from summary keywords — adjust before creating">
            <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
              <CheckChip checked kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> Rental Housing</>)} />
              <CheckChip checked kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> Pre-Purchase Counseling</>)} />
              <CheckChip kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> First-Time Homebuyer Ed.</>)} />
              <CheckChip kind="teal" label={(<><Tag label="SSS" kind="sage" style={{ marginRight: 4 }} /> Emergency Shelter</>)} />
              <a style={{ alignSelf: "center", fontSize: 12, color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>
                + search 16 more
              </a>
            </div>
          </CTPField>

          {/* Carry-over */}
          <CTPField label="Carry over from this entry" hint="Uncheck to skip">
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 6 }}>
              <CarryRow checked label="Contact" sub="Esperanza Villarroel-Cruz · Tenant · 484-712-0418" />
              <CarryRow checked label="Address" sub="519 N New St, Bethlehem, PA 18018" />
              <CarryRow checked label="Tags" sub="spanish-speaking, intake, rent burden, children in household" />
              <CarryRow checked label="Open follow-up" sub="Due May 24 · move to project timeline" />
              <CarryRow label="Add to PHARE distribution" sub="Auto-notify David & Sandra" />
              <CarryRow label="Notify county partners" sub="Lehigh Valley Planning Comm." />
            </div>
          </CTPField>
        </div>

        {/* Footer */}
        <footer style={{
          padding: "14px 24px", borderTop: "1px solid var(--calv-slate-15)",
          background: "var(--calv-sand-15)",
          display: "flex", alignItems: "center", gap: 12,
        }}>
          <div style={{ fontSize: 12, color: "var(--calv-slate-65)" }}>
            On create: this entry is re-tagged to the new project · an Activity Update is auto-logged · the
            viewer banner switches to teal · met requirements carry forward.
          </div>
          <span style={{ flex: 1 }} />
          <Btn kind="ghost" size="md">Cancel</Btn>
          <Btn kind="secondary" size="md" icon={Ic.arrow} style={{ opacity: .5, cursor: "not-allowed" }}>
            Create project (unmet)
          </Btn>
        </footer>
      </div>
    </div>
  );
}

function CTPField({ label, hint, required, children }) {
  return (
    <div>
      <div style={{ display: "flex", alignItems: "baseline", gap: 8, marginBottom: 6 }}>
        <label style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
          letterSpacing: ".08em", textTransform: "uppercase",
          color: "var(--calv-slate)",
        }}>
          {label}
          {required && <span style={{ color: "var(--calv-red)", marginLeft: 4 }}>*</span>}
        </label>
        {hint && <span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>{hint}</span>}
      </div>
      {children}
    </div>
  );
}

function CarryRow({ checked, label, sub }) {
  return (
    <div style={{
      display: "flex", alignItems: "flex-start", gap: 8,
      padding: "8px 10px",
      background: checked ? "var(--calv-sand-15)" : "#fff",
      border: checked ? "1px solid var(--calv-sand-35)" : "1px solid var(--calv-slate-15)",
      borderRadius: 4, cursor: "pointer",
    }}>
      <span style={{
        flex: "0 0 auto", width: 14, height: 14, borderRadius: 3,
        marginTop: 2,
        background: checked ? "var(--calv-slate)" : "transparent",
        border: checked ? "0" : "1.5px solid var(--calv-slate-35)",
        display: "inline-flex", alignItems: "center", justifyContent: "center",
      }}>
        {checked && <svg width="9" height="9" viewBox="0 0 12 12" fill="none">
          <path d="M2.5 6.5L5 9L9.5 3.5" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>}
      </span>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ fontSize: 13, color: "var(--calv-slate)", fontWeight: 600, lineHeight: 1.2 }}>{label}</div>
        <div style={{ fontSize: 11, color: "var(--calv-slate-65)", marginTop: 2, lineHeight: 1.35 }}>{sub}</div>
      </div>
    </div>
  );
}

Object.assign(window, { ConvertToProjectModal });
