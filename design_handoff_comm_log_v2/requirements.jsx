// requirements.jsx — Application Requirements per intake/log entry.
//
// New concept. Requirements are TEMPLATED on program services; when Kaitlyn
// tags a service on a comm-log entry, that service's requirement template
// items become "requirement instances" on the entry. Requirements gate
// Convert-to-Project: all required ones must be Met (or Waived / N/A)
// before the entry can be promoted into a project.
//
// Defaults applied (questions timed out, decide-for-me):
//   - Kinds: Document, Signed form, Data field, Verification, External check
//   - Convert strictness: HARD BLOCK with override (writ reason + audit)
//   - Optional vs required: per-template flag (some optional, don't gate)
//   - When introduced: BOTH compact in edit form + full in viewer + gate in convert
//   - Overlap: DEDUPE by requirement key (e.g. "photo-id" counts once)
//   - Ad-hoc: YES, Kaitlyn can add a one-off
//   - Who can mark: any housing-module staff
//   - After promote: LIVE LINK (carries forward, editable on project)

// ─────────────────────────────────────────────────────────────
// SAMPLE DATA — requirement templates per program service
// (Plausible-but-fake; tagged for client confirmation)
// ─────────────────────────────────────────────────────────────

// kinds: doc · form · data · verify · external
const REQ_TEMPLATES = {
  "HCP-FirstTimeHomebuyer": [
    { key: "photo-id",        kind: "doc",      label: "Photo ID",                            required: true,  hint: "Driver's license, state ID, or passport" },
    { key: "proof-income",    kind: "doc",      label: "Proof of income (last 2 years)",      required: true },
    { key: "tax-returns",     kind: "doc",      label: "Federal tax returns (last 2 years)",  required: false, hint: "Optional but speeds underwriting prep" },
    { key: "credit-consent",  kind: "form",     label: "Credit report consent",               required: true,  hint: "CALV form FR-12; we send to client to sign" },
    { key: "household-size",  kind: "data",     label: "Household size & member ages",        required: true },
    { key: "pre-purchase-class", kind: "verify",label: "Pre-purchase counseling class",       required: true,  hint: "8-hour HUD-approved; we verify attendance" },
  ],
  "HCP-PHFA-HEMAP": [
    { key: "photo-id",        kind: "doc",      label: "Photo ID",                            required: true },
    { key: "delinquency-notice", kind: "doc",   label: "Mortgage delinquency notice",         required: true },
    { key: "hardship",        kind: "form",     label: "Hardship statement",                  required: true,  hint: "CALV form FR-04" },
    { key: "annual-income",   kind: "data",     label: "Annual household income",             required: true },
    { key: "phfa-eligibility",kind: "external", label: "PHFA HEMAP eligibility",              required: true,  hint: "Waits on PHFA review (5-7 business days)" },
  ],
  "HCP-RentalHousing": [
    { key: "photo-id",        kind: "doc",      label: "Photo ID",                            required: true },
    { key: "current-lease",   kind: "doc",      label: "Current lease",                       required: true },
    { key: "income-verif-60d",kind: "doc",      label: "Income verification (last 60 days)",  required: true },
    { key: "landlord-contact",kind: "data",     label: "Landlord contact info",               required: true },
    { key: "lha-status",      kind: "external", label: "Local housing authority status",      required: false },
  ],
  "CAH-Insulation": [
    { key: "photo-id",        kind: "doc",      label: "Photo ID",                            required: true },
    { key: "homeownership",   kind: "doc",      label: "Proof of homeownership",              required: true,  hint: "Deed or current mortgage statement" },
    { key: "annual-income",   kind: "data",     label: "Annual household income",             required: true },
    { key: "utility-bill",    kind: "doc",      label: "Recent utility bill",                 required: true },
    { key: "lead-safe-cert",  kind: "verify",   label: "Lead-safe contractor approval",       required: true,  hint: "Required for homes built pre-1978" },
  ],
  "SSS-SixthStreet": [
    { key: "photo-id",        kind: "doc",      label: "Photo ID",                            required: true,  hint: "Or housing-status attestation if no ID" },
    { key: "need-attestation",kind: "form",     label: "Verification of need (intake-signed)",required: true },
    { key: "hmis-consent",    kind: "form",     label: "HMIS data-sharing consent",           required: true },
    { key: "income-self",     kind: "data",     label: "Income status (self-declared OK)",    required: true },
  ],
};

// statuses
//   pending   — never requested
//   requested — we've asked the client
//   received  — we have it / verified / complete
//   waived    — staff decision, with reason
//   na        — does not apply, with reason
//   blocked   — external check waiting

const REQ_STATUS = {
  pending:   { label: "Pending",   bg: "var(--calv-slate-15)", fg: "var(--calv-slate-65)", dot: "var(--calv-slate-35)" },
  requested: { label: "Requested", bg: "var(--calv-amber-15)", fg: "#8A6410",              dot: "var(--calv-amber)" },
  received:  { label: "Received",  bg: "var(--calv-sage-15)",  fg: "#2F5A41",              dot: "var(--calv-sage)" },
  waived:    { label: "Waived",    bg: "var(--calv-sand-35)",  fg: "var(--calv-slate)",    dot: "var(--calv-slate-65)" },
  na:        { label: "N/A",       bg: "var(--calv-sand-35)",  fg: "var(--calv-slate-65)", dot: "var(--calv-slate-35)" },
  blocked:   { label: "Blocked",   bg: "var(--calv-red-15)",   fg: "var(--calv-red)",      dot: "var(--calv-red)" },
};

// Default instance set for the Maria / PHARE sample
const SAMPLE_REQS_MARIA = [
  { key: "photo-id",         status: "received",  via: ["HCP-PHFA-HEMAP", "HCP-RentalHousing"], on: "Apr 23", who: "K. Wise" },
  { key: "delinquency-notice",status:"received",  via: ["HCP-PHFA-HEMAP"], on: "Apr 24", who: "K. Wise" },
  { key: "hardship",         status: "requested", via: ["HCP-PHFA-HEMAP"], on: "May 18", who: "K. Wise", note: "Emailed form, awaiting signature" },
  { key: "annual-income",    status: "received",  via: ["HCP-PHFA-HEMAP"], on: "Apr 23", who: "K. Wise", value: "$47,000" },
  { key: "phfa-eligibility", status: "blocked",   via: ["HCP-PHFA-HEMAP"], on: "Apr 30", who: "System",  note: "Submitted to PHFA · day 4 of 5–7" },
  { key: "current-lease",    status: "pending",   via: ["HCP-RentalHousing"] },
  { key: "income-verif-60d", status: "pending",   via: ["HCP-RentalHousing"] },
  { key: "landlord-contact", status: "pending",   via: ["HCP-RentalHousing"] },
];

// Default instance set for the Esperanza / intake sample — fewer met,
// good for showing the convert-to-project gate
const SAMPLE_REQS_ESPERANZA = [
  { key: "photo-id",         status: "pending",   via: ["HCP-RentalHousing"] },
  { key: "current-lease",    status: "waived",    via: ["HCP-RentalHousing"], note: "She is doubled up; no current lease (per intake)", on: "May 22", who: "K. Wise" },
  { key: "income-verif-60d", status: "requested", via: ["HCP-RentalHousing"], on: "May 23", who: "K. Wise" },
  { key: "landlord-contact", status: "na",        via: ["HCP-RentalHousing"], note: "Not yet placed; will collect on landlord ID", on: "May 22", who: "K. Wise" },
  // ad-hoc requirement Kaitlyn added
  { key: "ad-hoc-spanish",   status: "pending",   adHoc: true, label: "Spanish translation of HEMAP packet", kind: "doc" },
];

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────

// Aggregate templates across a set of tagged services, dedupe by key.
function aggregateTemplates(serviceIds) {
  const seen = new Map();
  for (const sid of serviceIds) {
    const tpl = REQ_TEMPLATES[sid] || [];
    for (const t of tpl) {
      if (!seen.has(t.key)) {
        seen.set(t.key, { ...t, via: [sid] });
      } else {
        seen.get(t.key).via.push(sid);
      }
    }
  }
  return [...seen.values()];
}

// Merge a list of templates with instance overrides (by key)
function mergeWithInstances(templates, instances) {
  const byKey = new Map(instances.map(i => [i.key, i]));
  // for instance entries that are ad-hoc and not in any template, surface them too
  const merged = templates.map(t => ({ ...t, ...(byKey.get(t.key) || { status: "pending" }) }));
  for (const inst of instances) {
    if (inst.adHoc && !merged.find(m => m.key === inst.key)) {
      merged.push({ ...inst });
    }
  }
  return merged;
}

// "5 of 7 required met · 1 optional pending"
function progressLine(items) {
  const required = items.filter(i => i.required !== false);
  const requiredMet = required.filter(i => isMet(i)).length;
  const optional = items.filter(i => i.required === false);
  const optionalPending = optional.filter(i => !isMet(i)).length;

  return {
    required: required.length,
    requiredMet,
    optional: optional.length,
    optionalPending,
    pct: required.length ? Math.round((requiredMet / required.length) * 100) : 100,
    allRequiredMet: required.length === requiredMet,
  };
}
function isMet(item) {
  return item.status === "received" || item.status === "waived" || item.status === "na";
}

// ─────────────────────────────────────────────────────────────
// UI ATOMS
// ─────────────────────────────────────────────────────────────

// kind glyph — small line icon, monochrome
function ReqKindIcon({ kind, color = "currentColor" }) {
  const common = { width: 14, height: 14, viewBox: "0 0 16 16", fill: "none", style: { color, flex: "0 0 auto" } };
  const stroke = { stroke: color, strokeWidth: 1.4, strokeLinecap: "round", strokeLinejoin: "round" };
  switch (kind) {
    case "doc":
      return (
        <svg {...common}>
          <path d="M3.5 2h6l3 3v9H3.5V2Z" {...stroke}/>
          <path d="M9.5 2v3h3" {...stroke}/>
          <path d="M5.5 8h5M5.5 10.5h4" {...stroke}/>
        </svg>
      );
    case "form":
      return (
        <svg {...common}>
          <path d="M3 2.5h10v11H3z" {...stroke}/>
          <path d="M5.5 5h5M5.5 7.5h5M5.5 10h3" {...stroke}/>
          <path d="M11.5 11.5l1.5 1.5" {...stroke}/>
        </svg>
      );
    case "data":
      return (
        <svg {...common}>
          <rect x="2.5" y="3" width="11" height="10" rx="1" {...stroke}/>
          <path d="M2.5 6.5h11M6 3v10M10 3v10" {...stroke}/>
        </svg>
      );
    case "verify":
      return (
        <svg {...common}>
          <path d="M8 1.5l5 2v4c0 3-2 5.5-5 7-3-1.5-5-4-5-7v-4l5-2Z" {...stroke}/>
          <path d="M5.5 7.5l2 2 3-3.5" {...stroke}/>
        </svg>
      );
    case "external":
      return (
        <svg {...common}>
          <circle cx="8" cy="8" r="5.5" {...stroke}/>
          <path d="M2.5 8h11M8 2.5c2 2 2 9 0 11M8 2.5c-2 2-2 9 0 11" {...stroke}/>
        </svg>
      );
    default:
      return null;
  }
}

function ReqStatusChip({ status }) {
  const s = REQ_STATUS[status] || REQ_STATUS.pending;
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: 5,
      fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
      letterSpacing: ".06em", textTransform: "uppercase",
      padding: "3px 8px", borderRadius: 999,
      background: s.bg, color: s.fg, lineHeight: 1.2,
    }}>
      <span style={{ width: 6, height: 6, borderRadius: 999, background: s.dot, display: "inline-block" }} />
      {s.label}
    </span>
  );
}

// Progress bar — strip with required-met / required-unmet / optional segments
function ReqProgressBar({ items }) {
  const p = progressLine(items);
  return (
    <div>
      <div style={{
        display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 6,
      }}>
        <div style={{
          fontFamily: "var(--font-h1)", fontSize: 22, letterSpacing: ".01em",
          textTransform: "uppercase", color: "var(--calv-slate)", lineHeight: 1,
        }}>
          {p.requiredMet} <span style={{ color: "var(--calv-slate-65)" }}>of {p.required}</span> required met
        </div>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
          letterSpacing: ".06em", textTransform: "uppercase",
          color: p.allRequiredMet ? "var(--calv-sage)" : "var(--calv-amber)",
        }}>
          {p.allRequiredMet
            ? "Ready to promote"
            : `${p.required - p.requiredMet} unmet${p.optionalPending ? ` · ${p.optionalPending} optional` : ""}`}
        </div>
      </div>
      <div style={{
        height: 8, borderRadius: 999, background: "var(--calv-slate-15)",
        overflow: "hidden", display: "flex",
      }}>
        <div style={{
          width: `${p.pct}%`,
          background: p.allRequiredMet ? "var(--calv-sage)" : "var(--calv-amber)",
        }} />
      </div>
    </div>
  );
}

// Single requirement row — used in viewer + gate
function ReqRow({ item, dense, onAct }) {
  const s = REQ_STATUS[item.status] || REQ_STATUS.pending;
  const isOptional = item.required === false;
  return (
    <div style={{
      display: "grid",
      gridTemplateColumns: dense ? "20px 1fr auto auto" : "22px 1fr auto auto",
      gap: 10, alignItems: "center",
      padding: dense ? "7px 10px" : "9px 12px",
      borderBottom: "1px solid var(--calv-slate-15)",
      background: item.adHoc ? "var(--calv-amber-15)" : "transparent",
    }}>
      <span style={{ color: isOptional ? "var(--calv-slate-65)" : "var(--calv-slate)" }}>
        <ReqKindIcon kind={item.kind} />
      </span>
      <div style={{ minWidth: 0 }}>
        <div style={{
          fontSize: dense ? 12.5 : 13.5, color: "var(--calv-slate)",
          lineHeight: 1.3, display: "flex", alignItems: "baseline", gap: 6, flexWrap: "wrap",
        }}>
          <span style={{ fontWeight: 500 }}>
            {item.label || templateLabel(item.key)}
          </span>
          {item.adHoc && <Tag label="ad-hoc" kind="amber" style={{ fontSize: 9 }} />}
          {isOptional && (
            <span style={{
              fontFamily: "var(--font-sub)", fontSize: 9, fontWeight: 700,
              letterSpacing: ".06em", textTransform: "uppercase",
              color: "var(--calv-slate-65)",
            }}>optional</span>
          )}
        </div>
        {!dense && (item.hint || item.note || item.value) && (
          <div style={{ fontSize: 11.5, color: "var(--calv-slate-65)", marginTop: 2, lineHeight: 1.4 }}>
            {item.note || item.hint}
            {item.value && <span style={{ marginLeft: 6, fontFamily: "ui-monospace, Menlo, monospace", color: "var(--calv-slate)" }}>{item.value}</span>}
            {item.on && item.who && (
              <span style={{ marginLeft: 8, color: "var(--calv-slate-35)" }}>
                · {item.who}, {item.on}
              </span>
            )}
          </div>
        )}
        {!dense && item.via && item.via.length > 0 && (
          <div style={{ display: "flex", gap: 4, marginTop: 4, flexWrap: "wrap" }}>
            {item.via.map(v => (
              <span key={v} style={{
                fontFamily: "var(--font-sub)", fontSize: 9, fontWeight: 700,
                letterSpacing: ".06em", textTransform: "uppercase",
                color: "var(--calv-teal)", background: "var(--calv-teal-15)",
                padding: "1px 5px", borderRadius: 2,
              }}>{serviceShort(v)}</span>
            ))}
          </div>
        )}
      </div>
      <ReqStatusChip status={item.status || "pending"} />
      <button style={{
        background: "transparent", border: "1px solid var(--calv-slate-35)",
        borderRadius: 4, padding: "3px 8px",
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".06em", textTransform: "uppercase",
        color: "var(--calv-slate)", cursor: "pointer",
      }}>
        {item.status === "pending" ? "Request" :
         item.status === "requested" ? "Mark received" :
         item.status === "received" ? "Edit" :
         item.status === "blocked" ? "Check status" :
         "Edit"}
      </button>
    </div>
  );
}

function templateLabel(key) {
  // best-effort label fallback for ad-hoc instances
  return key.split("-").map(s => s[0].toUpperCase() + s.slice(1)).join(" ");
}

function serviceShort(sid) {
  // E.g. "HCP-PHFA-HEMAP" → "HCP · PHFA HEMAP"
  if (!sid) return "—";
  const [prog, ...rest] = sid.split("-");
  return `${prog} · ${rest.join(" ")}`;
}

// ─────────────────────────────────────────────────────────────
// VIEW PANELS
// ─────────────────────────────────────────────────────────────

// Full requirements panel — used in the viewer.
function RequirementsPanel({ items, title = "Application requirements", hint, addAction = true }) {
  const sorted = items.slice().sort((a, b) => {
    // unmet first, then optional
    const aMet = isMet(a) ? 1 : 0;
    const bMet = isMet(b) ? 1 : 0;
    if (aMet !== bMet) return aMet - bMet;
    if ((a.required === false) !== (b.required === false)) return (a.required === false) ? 1 : -1;
    return 0;
  });
  return (
    <section style={{
      background: "#fff",
      border: "1px solid var(--calv-slate-15)",
      borderRadius: 4, padding: "14px 16px", marginBottom: 12,
    }}>
      <header style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 12 }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 8 }}>
          <h3 style={{
            fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate)", margin: 0,
          }}>{title}</h3>
          {hint && <span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>{hint}</span>}
        </div>
        {addAction && (
          <a style={{
            fontSize: 11, color: "var(--calv-teal)",
            textDecoration: "underline", cursor: "pointer",
          }}>+ ad-hoc requirement</a>
        )}
      </header>

      <div style={{ marginBottom: 14 }}>
        <ReqProgressBar items={items} />
      </div>

      <div style={{
        border: "1px solid var(--calv-slate-15)", borderRadius: 4, overflow: "hidden",
      }}>
        {sorted.map((it, i) => <ReqRow key={`${it.key}-${i}`} item={it} />)}
      </div>
    </section>
  );
}

// Compact "X of Y" pill suitable for edit-form rail
function RequirementsSummaryPill({ items, onClick }) {
  const p = progressLine(items);
  return (
    <button onClick={onClick} style={{
      display: "flex", alignItems: "center", gap: 10,
      width: "100%", padding: "10px 12px",
      background: p.allRequiredMet ? "var(--calv-sage-15)" : "var(--calv-amber-15)",
      border: `1px solid ${p.allRequiredMet ? "var(--calv-sage)" : "var(--calv-amber)"}`,
      borderRadius: 4, cursor: "pointer", textAlign: "left",
      fontFamily: "var(--font-body)",
    }}>
      <span style={{
        fontFamily: "var(--font-h1)", fontSize: 22, letterSpacing: ".01em",
        color: "var(--calv-slate)", lineHeight: 1,
      }}>{p.requiredMet}<span style={{ color: "var(--calv-slate-65)" }}>/{p.required}</span></span>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{
          fontFamily: "var(--font-sub)", fontSize: 10.5, fontWeight: 700,
          letterSpacing: ".08em", textTransform: "uppercase",
          color: "var(--calv-slate)",
        }}>Requirements met</div>
        <div style={{ fontSize: 12, color: "var(--calv-slate-65)", marginTop: 2 }}>
          {p.allRequiredMet ? "Ready to promote to project →" : `${p.required - p.requiredMet} unmet${p.optionalPending ? ` · ${p.optionalPending} optional pending` : ""}`}
        </div>
      </div>
    </button>
  );
}

// Convert-to-Project requirements gate
function RequirementsGate({ items }) {
  const p = progressLine(items);
  const unmet = items.filter(i => i.required !== false && !isMet(i));
  const ok = p.allRequiredMet;
  return (
    <div style={{
      border: `2px solid ${ok ? "var(--calv-sage)" : "var(--calv-red)"}`,
      borderRadius: 4, padding: "16px 18px",
      background: ok ? "var(--calv-sage-15)" : "var(--calv-red-15)",
    }}>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 8 }}>
        <span style={{ color: ok ? "var(--calv-sage)" : "var(--calv-red)", display: "inline-flex" }}>
          {ok ? Ic.check : Ic.warn}
        </span>
        <span style={{
          fontFamily: "var(--font-h1)", fontSize: 18, letterSpacing: ".02em",
          textTransform: "uppercase", color: "var(--calv-slate)", lineHeight: 1,
        }}>
          {ok ? "All required requirements met" : `${unmet.length} required requirement${unmet.length === 1 ? "" : "s"} unmet`}
        </span>
        <span style={{ flex: 1 }} />
        <span style={{
          fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
          letterSpacing: ".08em", textTransform: "uppercase",
          color: ok ? "var(--calv-sage)" : "var(--calv-red)",
        }}>{p.requiredMet}/{p.required}</span>
      </div>

      <div style={{ marginBottom: 10 }}>
        <ReqProgressBar items={items} />
      </div>

      {!ok && (
        <>
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate-65)", marginTop: 8, marginBottom: 4,
          }}>Unmet</div>
          <div style={{ background: "#fff", border: "1px solid var(--calv-red-35)", borderRadius: 4, overflow: "hidden" }}>
            {unmet.map((it, i) => <ReqRow key={`${it.key}-${i}`} item={it} dense />)}
          </div>

          <div style={{
            marginTop: 10, padding: "10px 12px",
            background: "#fff", borderRadius: 4, border: "1px solid var(--calv-slate-15)",
            display: "flex", alignItems: "flex-start", gap: 10, fontSize: 12.5, color: "var(--calv-slate)",
            lineHeight: 1.5,
          }}>
            <input type="checkbox" style={{ marginTop: 2 }} />
            <div style={{ flex: 1 }}>
              <strong>Override and create anyway.</strong> Any conversion with unmet required requirements is
              audit-logged with the reason below.
              <textarea rows={2} placeholder="Reason (required for override)…" style={{
                width: "100%", marginTop: 6, padding: "6px 8px", fontSize: 12,
                border: "1px solid var(--calv-slate-35)", borderRadius: 4,
                fontFamily: "var(--font-body)", resize: "vertical",
                boxSizing: "border-box",
              }} />
            </div>
          </div>
        </>
      )}
    </div>
  );
}

Object.assign(window, {
  // data
  REQ_TEMPLATES, REQ_STATUS,
  SAMPLE_REQS_MARIA, SAMPLE_REQS_ESPERANZA,
  // helpers
  aggregateTemplates, mergeWithInstances, progressLine, isMet,
  // atoms
  ReqKindIcon, ReqStatusChip, ReqProgressBar, ReqRow,
  // panels
  RequirementsPanel, RequirementsSummaryPill, RequirementsGate,
});
