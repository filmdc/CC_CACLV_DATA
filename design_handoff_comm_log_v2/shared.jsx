// shared.jsx — sample data + small shared UI atoms for the Comm Log redesign.
// Everything is plausible-but-fake CACLV data. Loaded before each direction file.

// ─────────────────────────────────────────────────────────────
// SAMPLE DATA
// ─────────────────────────────────────────────────────────────

const PROJECTS = [
  {
    id: "p-phare-walnutport",
    name: "PHARE Healthy Homes · Walnutport",
    code: "PHARE-26",
    status: "Active",
    fundCodes: ["F0621", "F0623"],
    contacts: 12,
    services: ["HCP-PHFA-HEMAP", "HCP-RentalHousing", "HCP-HousingCounseling"],
    starredContacts: ["Maria Pimentel-Fernandez", "Sandra Yannitelli"],
  },
  {
    id: "p-136-8th",
    name: "136 S. 8th St. Rehab",
    code: "F0530",
    status: "Active",
    fundCodes: ["F0530", "F4206"],
    contacts: 4,
    services: ["CAH-Insulation", "HCP-PrePurchase"],
    starredContacts: ["Jimmy McAllister", "Devon Caraballo"],
  },
  {
    id: "p-abh-block7",
    name: "Allentown Better Homes · Block 7",
    code: "F0531",
    status: "Active",
    fundCodes: ["F0531", "F4205"],
    contacts: 18,
    services: ["HCP-FirstTimeHomebuyer", "HCP-PreClosing", "HCP-PrePurchase"],
    starredContacts: ["Pastor Reginald Booker", "Jasmin Ortiz"],
  },
  {
    id: "p-lc-urgent",
    name: "LC Urgent HVAC · 519 Turner",
    code: "F4205",
    status: "Closing soon",
    fundCodes: ["F4205", "F4206", "F7000"],
    contacts: 6,
    services: ["CAH-Insulation"],
    starredContacts: ["Jimmy McAllister"],
  },
  {
    id: "p-6th-fy26",
    name: "Sixth Street Shelter · Op Support FY26",
    code: "SSS-26",
    status: "Active",
    fundCodes: ["FC-7373"],
    contacts: 9,
    services: ["SSS-SixthStreet", "SSS-EmergencyShelter", "SSS-Transitional"],
    starredContacts: ["Pastor Reginald Booker"],
  },
];

// Fund codes with status. Dates are 2026-relative to give us live "expiring" UI.
// "today" = 2026-05-23 per the system clock.
const FUND_CODES = [
  { code: "F0530",   name: "136 S. 8th St.",                    startsAt: "2024-01-01", expiresAt: "2027-06-30", balance: 142800 },
  { code: "F0531",   name: "Allentown Better Homes",            startsAt: "2024-07-01", expiresAt: "2026-08-15", balance:  88420 }, // <90d
  { code: "F0621",   name: "PHARE Healthy Homes",               startsAt: "2025-01-01", expiresAt: "2026-06-15", balance:  61200 }, // <30d
  { code: "F0623",   name: "Thriving Health (PHARE)",           startsAt: "2025-01-01", expiresAt: null,         balance: 210000 },
  { code: "F4205",   name: "LC Urgent roof & HVAC (CDBG-CV)",   startsAt: "2023-06-01", expiresAt: "2026-05-30", balance:  12400 }, // <30d
  { code: "F4206",   name: "LC Urgent roof & HVAC (CDBG)",      startsAt: "2024-06-01", expiresAt: "2027-03-01", balance:  72100 },
  { code: "F7000",   name: "WHR",                               startsAt: null,         expiresAt: null,         balance: 305000 },
  { code: "FC-7373", name: "Fixture Fund qui unde",             startsAt: "2025-09-01", expiresAt: "2026-12-31", balance:  18600 },
];

// Program services as they appear in the existing form — three programs.
const SERVICES = {
  CAH: [
    { id: "CAH-Insulation",         label: "Insulation" },
  ],
  HCP: [
    { id: "HCP-DDCounseling",       label: "Default & Delinquency Counseling" },
    { id: "HCP-DefaultAndDelinq",   label: "Default And Delinquency" },
    { id: "HCP-FirstTimeHomebuyer", label: "First-Time Homebuyer Education" },
    { id: "HCP-Foreclosure",        label: "Foreclosure Prevention" },
    { id: "HCP-SpanishSeminar",     label: "HOCP Spanish Seminar" },
    { id: "HCP-VirtualNovember",    label: "HOCP VIRTUAL November Seminar" },
    { id: "HCP-HousingCounseling",  label: "Housing Counseling Service" },
    { id: "HCP-PHFA-HEMAP",         label: "PHFA HEMAP" },
    { id: "HCP-PostPurchase11",     label: "Post-Purchase One on One" },
    { id: "HCP-PreClosing",         label: "Pre-Closing Seminar" },
    { id: "HCP-PrePurchase",        label: "Pre-Purchase Counseling" },
    { id: "HCP-PrePurchase11",      label: "Pre-Purchase One on One" },
    { id: "HCP-RentalHousing",      label: "Rental Housing" },
  ],
  SSS: [
    { id: "SSS-FerryStreet",        label: "CACLV Ferry Street TH Program" },
    { id: "SSS-SixthStreet",        label: "CACLV Sixth Street Shelter Project" },
    { id: "SSS-EmergencyShelter",   label: "Emergency Shelter – Entry Exit" },
    { id: "SSS-TestHousing",        label: "Test Housing Service" },
    { id: "SSS-TestService",        label: "Test Service From HMIS" },
    { id: "SSS-Transitional",       label: "Transitional Housing" },
  ],
};

const TAGS_EXISTING = [
  "lead paint", "blood lead level", "spanish-speaking", "elderly tenant",
  "callback needed", "left voicemail", "code violation", "404 form",
  "rehab in progress", "intake", "follow-up", "winter heat",
];

const CONTACT_ROLES = [
  "Homeowner", "Tenant", "Landlord", "Contractor",
  "Partner", "Funder", "Family member", "Other",
];

// Recent contacts surfaced as suggestions in the project-first direction.
const RECENT_CONTACTS = [
  { first: "Maria",    last: "Pimentel-Fernandez", role: "Homeowner",  phone: "484-522-4989", email: "yosky2227@gmail.com", lastAt: "2026-05-21", address: { st: "627 S Lincoln Ave.", city: "Walnutport", state: "PA", zip: "18088" }},
  { first: "Sandra",   last: "Yannitelli",         role: "Homeowner",  phone: "610-435-1102", email: "syann@verizon.net",    lastAt: "2026-05-19" },
  { first: "Jimmy",    last: "McAllister",         role: "Contractor", phone: "484-880-4421", email: "jimmy@mcallisterhvac.com", lastAt: "2026-05-18" },
  { first: "Devon",    last: "Caraballo",          role: "Tenant",     phone: "610-820-0093", email: "devon.c@gmail.com",    lastAt: "2026-05-15" },
  { first: "Reginald", last: "Booker",             role: "Partner",    phone: "610-432-7766", email: "rev.booker@stpaulsallentown.org", lastAt: "2026-05-12" },
  { first: "Jasmin",   last: "Ortiz",              role: "Landlord",   phone: "484-201-1199", email: "jortiz.realty@gmail.com", lastAt: "2026-05-09" },
];

// "Today" for relative date math in mocks. Mirrors the system date.
const TODAY = new Date("2026-05-23T12:00:00");

function daysUntil(iso) {
  if (!iso) return Infinity;
  const d = new Date(iso + "T00:00:00");
  return Math.round((d - TODAY) / (1000 * 60 * 60 * 24));
}
function fundExpiryStatus(fund) {
  const d = daysUntil(fund.expiresAt);
  if (fund.expiresAt == null) return { kind: "perpetual", label: "No expiry", days: null };
  if (d < 0)   return { kind: "expired",   label: `Expired ${Math.abs(d)}d ago`, days: d };
  if (d <= 30) return { kind: "soon",      label: `Expires in ${d}d`,            days: d };
  if (d <= 90) return { kind: "warning",   label: `Expires in ${d}d`,            days: d };
  return        { kind: "ok",       label: `Expires ${fund.expiresAt}`,   days: d };
}
function fmtMoney(n) { return "$" + n.toLocaleString("en-US"); }

// ─────────────────────────────────────────────────────────────
// SHARED UI ATOMS
// ─────────────────────────────────────────────────────────────

// Slate icon-button style row used inside artboards' top chrome
function ModalChrome({ title, subtitle, idLabel, breadcrumb, children, footer, width }) {
  return (
    <div style={{
      width: width || "100%", background: "#fff",
      border: "1px solid var(--calv-slate-15)",
      borderRadius: 4, boxShadow: "var(--shadow-md)",
      display: "flex", flexDirection: "column", overflow: "hidden",
    }}>
      <header style={{
        padding: "14px 22px 12px",
        borderBottom: "1px solid var(--calv-slate-15)",
        display: "flex", alignItems: "center", justifyContent: "space-between",
      }}>
        <div>
          {breadcrumb && (
            <div style={{
              fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
              letterSpacing: ".08em", textTransform: "uppercase",
              color: "var(--calv-slate-65)", marginBottom: 4,
            }}>{breadcrumb}</div>
          )}
          <div style={{ display: "flex", alignItems: "baseline", gap: 12 }}>
            <h2 style={{
              fontFamily: "var(--font-h1)", fontWeight: 700,
              fontSize: 22, letterSpacing: ".02em", textTransform: "uppercase",
              color: "var(--calv-slate)", margin: 0, lineHeight: 1,
            }}>{title}</h2>
            {idLabel && (
              <span style={{
                fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
                letterSpacing: ".06em", color: "var(--calv-slate-65)",
              }}>{idLabel}</span>
            )}
          </div>
          {subtitle && (
            <div style={{ fontSize: 12, color: "var(--calv-slate-65)", marginTop: 4 }}>{subtitle}</div>
          )}
        </div>
        <button aria-label="Close" style={{
          width: 32, height: 32, border: "0", background: "transparent",
          color: "var(--calv-slate-65)", cursor: "pointer", borderRadius: 4,
          fontFamily: "var(--font-h1)", fontSize: 20, lineHeight: 1,
        }}>✕</button>
      </header>
      <div style={{ flex: 1, minHeight: 0 }}>{children}</div>
      {footer && (
        <footer style={{
          padding: "12px 22px", borderTop: "1px solid var(--calv-slate-15)",
          background: "var(--calv-sand-15)",
          display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12,
        }}>{footer}</footer>
      )}
    </div>
  );
}

// Tiny label
function FieldLabel({ children, required, hint, kbd }) {
  return (
    <div style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 4 }}>
      <label style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".08em", textTransform: "uppercase",
        color: "var(--calv-slate)",
      }}>
        {children}
        {required && <span style={{ color: "var(--calv-red)", marginLeft: 4 }}>*</span>}
        {hint && <span style={{ color: "var(--calv-slate-65)", fontWeight: 400, marginLeft: 6, textTransform: "none", letterSpacing: 0 }}>{hint}</span>}
      </label>
      {kbd && <Kbd>{kbd}</Kbd>}
    </div>
  );
}

// Generic small text input
function TI({ value, placeholder, required, error, type = "text", iconRight, mono, style }) {
  return (
    <div style={{ position: "relative" }}>
      <input
        type={type} defaultValue={value} placeholder={placeholder}
        style={{
          fontFamily: mono ? "ui-monospace, SFMono-Regular, Menlo, monospace" : "var(--font-body)",
          fontWeight: 400, fontSize: 14,
          color: "var(--calv-slate)", background: "#fff",
          border: error ? "1.5px solid var(--calv-red)" : "1px solid var(--calv-slate-35)",
          borderRadius: 4, padding: "9px 12px", lineHeight: 1.3,
          width: "100%", boxSizing: "border-box",
          boxShadow: error ? "0 0 0 3px var(--calv-red-15)" : "none",
          ...style,
        }}
      />
      {iconRight && (
        <span style={{ position: "absolute", right: 10, top: "50%", transform: "translateY(-50%)", color: "var(--calv-slate-65)" }}>
          {iconRight}
        </span>
      )}
    </div>
  );
}

function TA({ value, placeholder, rows = 4, style }) {
  return (
    <textarea
      defaultValue={value} placeholder={placeholder} rows={rows}
      style={{
        fontFamily: "var(--font-body)", fontWeight: 400, fontSize: 14,
        color: "var(--calv-slate)", background: "#fff",
        border: "1px solid var(--calv-slate-35)", borderRadius: 4,
        padding: "10px 12px", lineHeight: 1.55,
        width: "100%", boxSizing: "border-box", resize: "vertical",
        ...style,
      }}
    />
  );
}

// Fake-select cell — visually the same as TI but with a caret
function Sel({ value, placeholder, error, style }) {
  return (
    <div style={{
      fontFamily: "var(--font-body)", fontSize: 14,
      color: value ? "var(--calv-slate)" : "var(--calv-slate-65)",
      background: "#fff",
      border: error ? "1.5px solid var(--calv-red)" : "1px solid var(--calv-slate-35)",
      borderRadius: 4, padding: "9px 12px", lineHeight: 1.3,
      display: "flex", alignItems: "center", justifyContent: "space-between",
      ...style,
    }}>
      <span>{value || placeholder}</span>
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    </div>
  );
}

// Keyboard chip
function Kbd({ children }) {
  return (
    <kbd style={{
      fontFamily: "ui-monospace, SFMono-Regular, Menlo, monospace",
      fontSize: 10, fontWeight: 600,
      padding: "2px 6px", borderRadius: 3,
      background: "var(--calv-slate-15)",
      color: "var(--calv-slate-65)",
      border: "1px solid var(--calv-slate-35)",
      borderBottomWidth: 2,
      letterSpacing: 0, textTransform: "none",
    }}>{children}</kbd>
  );
}

// Tag pill
function Tag({ label, kind = "neutral", removable, onClick, style }) {
  const palettes = {
    neutral: { bg: "var(--calv-slate-15)", fg: "var(--calv-slate)" },
    teal:    { bg: "var(--calv-teal-15)",  fg: "var(--calv-teal)" },
    red:     { bg: "var(--calv-red-15)",   fg: "var(--calv-red)" },
    sage:    { bg: "var(--calv-sage-15)",  fg: "#2F5A41" },
    amber:   { bg: "var(--calv-amber-15)", fg: "#8A6410" },
    sand:    { bg: "var(--calv-sand-35)",  fg: "var(--calv-slate)" },
  };
  const p = palettes[kind] || palettes.neutral;
  return (
    <span onClick={onClick} style={{
      display: "inline-flex", alignItems: "center", gap: 4,
      fontFamily: "var(--font-body)", fontWeight: 500, fontSize: 12,
      padding: "3px 8px", borderRadius: 999,
      background: p.bg, color: p.fg, lineHeight: 1.3,
      cursor: onClick ? "pointer" : "default",
      ...style,
    }}>
      {label}
      {removable && <span style={{ opacity: .6, marginLeft: 2 }}>✕</span>}
    </span>
  );
}

// Chip-style checkbox for fund codes / services
function CheckChip({ checked, label, sub, status, onClick, kind = "neutral", style, children }) {
  const palettes = {
    neutral: { off: { bg: "#fff", bd: "var(--calv-slate-35)", fg: "var(--calv-slate)" },
               on:  { bg: "var(--calv-slate)", bd: "var(--calv-slate)", fg: "#fff" } },
    red:     { off: { bg: "#fff", bd: "var(--calv-slate-35)", fg: "var(--calv-slate)" },
               on:  { bg: "var(--calv-red)", bd: "var(--calv-red)", fg: "#fff" } },
    teal:    { off: { bg: "#fff", bd: "var(--calv-slate-35)", fg: "var(--calv-slate)" },
               on:  { bg: "var(--calv-teal)", bd: "var(--calv-teal)", fg: "#fff" } },
  };
  const p = palettes[kind] || palettes.neutral;
  const s = checked ? p.on : p.off;
  return (
    <button onClick={onClick} style={{
      display: "inline-flex", alignItems: "center", gap: 8,
      fontFamily: "var(--font-body)", fontSize: 13,
      background: s.bg, color: s.fg, border: `1.25px solid ${s.bd}`,
      borderRadius: 4, padding: "7px 10px", textAlign: "left", cursor: "pointer",
      lineHeight: 1.2,
      ...style,
    }}>
      <span style={{
        width: 14, height: 14, borderRadius: 3,
        border: checked ? "1.5px solid currentColor" : `1.5px solid var(--calv-slate-35)`,
        background: checked ? "currentColor" : "transparent",
        display: "inline-flex", alignItems: "center", justifyContent: "center",
        flex: "0 0 auto",
      }}>
        {checked && (
          <svg width="9" height="9" viewBox="0 0 12 12" fill="none">
            <path d="M2.5 6.5L5 9L9.5 3.5" stroke={s.bg} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        )}
      </span>
      <span style={{ flex: 1 }}>{label}</span>
      {sub && <span style={{ fontSize: 11, color: checked ? "rgba(255,255,255,.8)" : "var(--calv-slate-65)" }}>{sub}</span>}
      {status && <FundStatusDot status={status} />}
      {children}
    </button>
  );
}

// Color dot for fund-code expiry status
function FundStatusDot({ status }) {
  const map = {
    soon:    { color: "var(--calv-red)",   tip: "Expires within 30 days" },
    warning: { color: "var(--calv-amber)", tip: "Expires within 90 days" },
    ok:      { color: "var(--calv-sage)",  tip: "Active" },
    perpetual: { color: "var(--calv-slate-35)", tip: "No expiry" },
    expired: { color: "var(--calv-slate)", tip: "Expired" },
  };
  const m = map[status] || map.ok;
  return (
    <span title={m.tip} style={{
      width: 8, height: 8, borderRadius: 999, background: m.color, flex: "0 0 auto",
    }} />
  );
}

// Section header used inside form panes
function FormSection({ title, hint, right, children, dense, style }) {
  return (
    <section style={{ padding: dense ? "10px 0 14px" : "14px 0 18px", borderTop: "1px dashed var(--calv-slate-15)", ...style }}>
      <header style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 10 }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 10 }}>
          <h3 style={{
            fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate)", margin: 0,
          }}>{title}</h3>
          {hint && <span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>{hint}</span>}
        </div>
        {right}
      </header>
      {children}
    </section>
  );
}

// Tiny rounded button matching brand
function Btn({ children, kind = "primary", size = "md", icon, kbd, style, onClick }) {
  const pad = size === "sm" ? "6px 10px" : size === "lg" ? "12px 18px" : "9px 14px";
  const fs = size === "sm" ? 11 : size === "lg" ? 14 : 12;
  const palette = {
    primary:  { bg: "var(--calv-red)",     fg: "#fff", bd: "transparent" },
    secondary:{ bg: "var(--calv-teal)",    fg: "#fff", bd: "transparent" },
    ghost:    { bg: "transparent",         fg: "var(--calv-slate)", bd: "var(--calv-slate-35)" },
    quiet:    { bg: "var(--calv-slate-15)", fg: "var(--calv-slate)", bd: "transparent" },
    danger:   { bg: "var(--calv-red-15)",  fg: "var(--calv-red)", bd: "transparent" },
    dark:     { bg: "var(--calv-slate)",   fg: "#fff", bd: "transparent" },
  };
  const p = palette[kind] || palette.primary;
  return (
    <button onClick={onClick} style={{
      display: "inline-flex", alignItems: "center", gap: 6,
      fontFamily: "var(--font-sub)", fontWeight: 700,
      fontSize: fs, letterSpacing: ".06em", textTransform: "uppercase",
      background: p.bg, color: p.fg, border: `1px solid ${p.bd}`,
      borderRadius: 4, padding: pad, cursor: "pointer",
      ...style,
    }}>
      {icon}
      {children}
      {kbd && <Kbd>{kbd}</Kbd>}
    </button>
  );
}

// Inline icon set (just enough)
const Ic = {
  cal: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <rect x="2" y="3" width="12" height="11" rx="1.5" stroke="currentColor" strokeWidth="1.5"/>
      <path d="M2 6h12M5 1.5v3M11 1.5v3" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
    </svg>
  ),
  phone: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M3 3.5a1 1 0 0 1 1-1h1.7a1 1 0 0 1 1 .76l.7 2.5a1 1 0 0 1-.42 1.1l-1.3.85a9 9 0 0 0 4 4l.85-1.3a1 1 0 0 1 1.1-.42l2.5.7a1 1 0 0 1 .76 1V12a1 1 0 0 1-1 1A10 10 0 0 1 3 3.5Z" stroke="currentColor" strokeWidth="1.4"/>
    </svg>
  ),
  mail: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <rect x="2" y="3.5" width="12" height="9" rx="1.5" stroke="currentColor" strokeWidth="1.4"/>
      <path d="M2.5 4.5L8 9l5.5-4.5" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),
  people: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <circle cx="8" cy="6" r="2.5" stroke="currentColor" strokeWidth="1.4"/>
      <path d="M3 13.5c0-2.2 2.2-4 5-4s5 1.8 5 4" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round"/>
    </svg>
  ),
  pin: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M8 14s-5-4-5-8a5 5 0 1 1 10 0c0 4-5 8-5 8Z" stroke="currentColor" strokeWidth="1.4"/>
      <circle cx="8" cy="6" r="1.5" stroke="currentColor" strokeWidth="1.4"/>
    </svg>
  ),
  search: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <circle cx="7" cy="7" r="4.5" stroke="currentColor" strokeWidth="1.5"/>
      <path d="M10.5 10.5L14 14" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
    </svg>
  ),
  plus: (
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
      <path d="M6 1.5v9M1.5 6h9" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"/>
    </svg>
  ),
  warn: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M8 1.5L15 13.5H1L8 1.5Z" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round"/>
      <path d="M8 6v3.5M8 11.5v.01" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"/>
    </svg>
  ),
  check: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M3 8.5L6.5 12L13 4.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),
  arrow: (
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
      <path d="M2 6h8M7 3l3 3-3 3" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),
  link: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M6.5 9.5l3-3M7 4l1.5-1.5a3 3 0 0 1 4.2 4.2L11 8.5M9.5 12l-1.5 1.5a3 3 0 0 1-4.2-4.2L5 7.5" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round"/>
    </svg>
  ),
  cmd: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" strokeWidth="1.4"/>
    </svg>
  ),
  dollar: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M8 2v12M11 5H6.5a2 2 0 0 0 0 4h3a2 2 0 0 1 0 4H5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),
  tag: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M2 2h6l6 6-6 6-6-6V2Z" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round"/>
      <circle cx="5" cy="5" r="0.9" fill="currentColor"/>
    </svg>
  ),
  edit: (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
      <path d="M11.5 2.5l2 2-8 8H3.5v-2l8-8Z" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round"/>
    </svg>
  ),
};

// ─────────────────────────────────────────────────────────────
// PROGRAM-SERVICES menu helper — flatten with program prefix
// ─────────────────────────────────────────────────────────────
const ALL_SERVICES_FLAT = Object.entries(SERVICES).flatMap(
  ([prog, list]) => list.map(s => ({ ...s, program: prog }))
);

// Export everything to window for cross-script usage
Object.assign(window, {
  // data
  PROJECTS, FUND_CODES, SERVICES, ALL_SERVICES_FLAT,
  TAGS_EXISTING, CONTACT_ROLES, RECENT_CONTACTS, TODAY,
  // helpers
  daysUntil, fundExpiryStatus, fmtMoney,
  // ui atoms
  ModalChrome, FieldLabel, TI, TA, Sel, Kbd, Tag,
  CheckChip, FundStatusDot, FormSection, Btn, Ic,
});
