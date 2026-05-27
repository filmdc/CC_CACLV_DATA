// viewer.jsx — Communication Log entry VIEWER (read-only) state.
// Mirrors the recommended Direction 02 (Project-First) vocabulary.
// Two flavors:
//   V1 · Entry attached to a project — the rich case
//   V2 · General intake — the "Convert to Project" promotion path is the hero CTA

// ─────────────────────────────────────────────────────────────
// Shared bits for the viewer
// ─────────────────────────────────────────────────────────────

function ChipRow({ children }) {
  return (
    <div style={{ display: "flex", flexWrap: "wrap", gap: 6, alignItems: "center" }}>
      {children}
    </div>
  );
}

function MetaChip({ kind = "neutral", icon, children, mono }) {
  const p = {
    neutral: { bg: "var(--calv-slate-15)", fg: "var(--calv-slate)" },
    teal:    { bg: "var(--calv-teal-15)",  fg: "var(--calv-teal)" },
    red:     { bg: "var(--calv-red-15)",   fg: "var(--calv-red)" },
    sage:    { bg: "var(--calv-sage-15)",  fg: "#2F5A41" },
    amber:   { bg: "var(--calv-amber-15)", fg: "#8A6410" },
    sand:    { bg: "var(--calv-sand-35)",  fg: "var(--calv-slate)" },
    slate:   { bg: "var(--calv-slate)",    fg: "#fff" },
  }[kind];
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: 5,
      fontFamily: mono ? "ui-monospace, Menlo, monospace" : "var(--font-sub)",
      fontSize: mono ? 11 : 10, fontWeight: 700,
      letterSpacing: mono ? 0 : ".08em", textTransform: mono ? "none" : "uppercase",
      padding: "4px 9px", borderRadius: 999,
      background: p.bg, color: p.fg, lineHeight: 1.2,
    }}>
      {icon && <span style={{ display: "inline-flex" }}>{icon}</span>}
      {children}
    </span>
  );
}

function PriorityBadge({ level = "normal" }) {
  const map = {
    urgent: { kind: "red",   label: "Urgent",  dot: "var(--calv-red)" },
    high:   { kind: "amber", label: "High",    dot: "var(--calv-amber)" },
    normal: { kind: "sage",  label: "Normal",  dot: "var(--calv-sage)" },
    low:    { kind: "neutral", label: "Low",   dot: "var(--calv-slate-65)" },
  }[level];
  return (
    <MetaChip kind={map.kind} icon={
      <span style={{ width: 7, height: 7, borderRadius: 999, background: map.dot, display: "inline-block" }} />
    }>{map.label} priority</MetaChip>
  );
}

// Read-only card label/value row
function KV({ label, value, mono }) {
  return (
    <div style={{
      display: "grid", gridTemplateColumns: "92px 1fr", gap: 12,
      padding: "6px 0", alignItems: "baseline",
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".08em", textTransform: "uppercase",
        color: "var(--calv-slate-65)",
      }}>{label}</div>
      <div style={{
        fontFamily: mono ? "ui-monospace, Menlo, monospace" : "var(--font-body)",
        fontSize: 13, color: "var(--calv-slate)",
      }}>{value}</div>
    </div>
  );
}

function ReadCard({ title, hint, right, children }) {
  return (
    <section style={{
      background: "#fff",
      border: "1px solid var(--calv-slate-15)",
      borderRadius: 4, padding: "14px 16px", marginBottom: 12,
    }}>
      <header style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginBottom: 8 }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 8 }}>
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

// Single update in the follow-up thread
function UpdateItem({ kind = "Note", who, when, body, attachments }) {
  const kindStyle = {
    Note:       { bg: "var(--calv-slate-15)", fg: "var(--calv-slate)" },
    Followup:   { bg: "var(--calv-teal-15)",  fg: "var(--calv-teal)" },
    Action:     { bg: "var(--calv-amber-15)", fg: "#8A6410" },
    Email:      { bg: "var(--calv-sage-15)",  fg: "#2F5A41" },
    Status:     { bg: "var(--calv-red-15)",   fg: "var(--calv-red)" },
  }[kind] || { bg: "var(--calv-slate-15)", fg: "var(--calv-slate)" };

  return (
    <article style={{
      display: "grid", gridTemplateColumns: "62px 1fr",
      gap: 12, padding: "10px 0",
      borderBottom: "1px solid var(--calv-slate-15)",
    }}>
      <div>
        <span style={{
          display: "inline-block",
          fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
          letterSpacing: ".08em", textTransform: "uppercase",
          padding: "3px 7px", borderRadius: 3,
          background: kindStyle.bg, color: kindStyle.fg,
        }}>{kind}</span>
      </div>
      <div>
        <div style={{ fontSize: 13.5, color: "var(--calv-slate)", lineHeight: 1.55 }}>{body}</div>
        {attachments && (
          <div style={{ marginTop: 6, display: "flex", gap: 6, flexWrap: "wrap" }}>
            {attachments.map((a, i) => (
              <span key={i} style={{
                fontSize: 11, color: "var(--calv-teal)",
                display: "inline-flex", alignItems: "center", gap: 4,
                padding: "2px 8px", borderRadius: 3,
                background: "var(--calv-teal-15)",
                fontFamily: "var(--font-body)",
              }}>📎 {a}</span>
            ))}
          </div>
        )}
        <div style={{
          marginTop: 5, fontSize: 11, color: "var(--calv-slate-65)",
          display: "flex", gap: 10, alignItems: "center",
        }}>
          <span>
            <strong style={{
              fontFamily: "var(--font-sub)", fontWeight: 700, letterSpacing: ".06em",
              textTransform: "uppercase", color: "var(--calv-slate)",
            }}>{who}</strong>
            <span> · {when}</span>
          </span>
          <a style={{ color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>reply</a>
          <a style={{ color: "var(--calv-slate-65)", cursor: "pointer" }}>edit</a>
          <a style={{ color: "var(--calv-red)", cursor: "pointer" }}>delete</a>
        </div>
      </div>
    </article>
  );
}

function FollowupComposer({ defaultKind = "Followup" }) {
  return (
    <div style={{
      background: "var(--calv-sand-15)",
      border: "1px solid var(--calv-sand-35)",
      borderRadius: 4, padding: 12, marginBottom: 14,
    }}>
      <div style={{
        display: "flex", alignItems: "center", gap: 6, marginBottom: 8,
        fontSize: 11, color: "var(--calv-slate-65)",
      }}>
        <span style={{
          fontFamily: "var(--font-sub)", fontWeight: 700, letterSpacing: ".1em",
          textTransform: "uppercase", color: "var(--calv-slate)",
        }}>Add update</span>
        <span>·</span>
        <span>Logs the update on this entry & re-opens it for follow-up</span>
        <span style={{ marginLeft: "auto" }}><Kbd>⌘⏎</Kbd></span>
      </div>

      <div style={{ display: "flex", gap: 6, marginBottom: 8, flexWrap: "wrap" }}>
        <KindPill label="Note" active={defaultKind === "Note"} />
        <KindPill label="Follow-up" active={defaultKind === "Followup"} accent="var(--calv-teal)" />
        <KindPill label="Action item" accent="var(--calv-amber)" />
        <KindPill label="Email" accent="var(--calv-sage)" />
        <KindPill label="Status change" accent="var(--calv-red)" />
      </div>

      <textarea
        rows={3}
        placeholder="Sent the lead-paint pamphlet by email; mother to confirm receipt by EOD Friday. If no reply, call sister Sandra (610-555-0188) per Maria's note."
        style={{
          fontFamily: "var(--font-body)", fontSize: 14,
          width: "100%", boxSizing: "border-box",
          border: "1px solid var(--calv-slate-35)", borderRadius: 4,
          padding: "9px 12px", lineHeight: 1.5,
          color: "var(--calv-slate)", background: "#fff", resize: "vertical",
        }}
      />

      <div style={{
        display: "flex", alignItems: "center", gap: 8, marginTop: 8, fontSize: 12,
      }}>
        <div style={{ display: "flex", alignItems: "center", gap: 6, color: "var(--calv-slate-65)" }}>
          <input type="checkbox" defaultChecked /> Set follow-up reminder
          <Sel value="Fri · May 29" style={{ padding: "4px 8px", fontSize: 12, minWidth: 130 }} />
        </div>
        <span style={{ flex: 1 }} />
        <Btn kind="ghost" size="sm">Cancel</Btn>
        <Btn kind="primary" size="sm">Add update</Btn>
      </div>
    </div>
  );
}

function KindPill({ label, active, accent = "var(--calv-slate)" }) {
  return (
    <button style={{
      fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
      letterSpacing: ".08em", textTransform: "uppercase",
      padding: "5px 10px", borderRadius: 3,
      background: active ? accent : "#fff",
      color: active ? "#fff" : "var(--calv-slate)",
      border: active ? `1px solid ${accent}` : "1px solid var(--calv-slate-35)",
      cursor: "pointer",
    }}>{label}</button>
  );
}

// ─────────────────────────────────────────────────────────────
// V1 · Entry attached to a project (rich)
// ─────────────────────────────────────────────────────────────
function ViewerProjectAttached() {
  return (
    <ModalChrome
      title="627 S Lincoln Ave · Walnutport"
      idLabel="COMM-7184"
      breadcrumb={<>Housing <span style={{ color: "var(--calv-slate-35)" }}>›</span> Communication Log <span style={{ color: "var(--calv-slate-35)" }}>›</span> COMM-7184</>}
      subtitle={(
        <span style={{ display: "inline-flex", alignItems: "center", gap: 10 }}>
          <span>Apr 21, 2026 · 11:44 AM</span>
          <span style={{ color: "var(--calv-slate-35)" }}>·</span>
          <span>Created by <strong style={{ color: "var(--calv-slate)" }}>Kaitlyn Wise</strong></span>
          <span style={{ color: "var(--calv-slate-35)" }}>·</span>
          <span>Last update <strong style={{ color: "var(--calv-slate)" }}>2h ago</strong></span>
        </span>
      )}
      width="100%"
      footer={(
        <>
          <div style={{ display: "flex", alignItems: "center", gap: 14, fontSize: 12, color: "var(--calv-slate-65)" }}>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <Kbd>E</Kbd> edit · <Kbd>N</Kbd> add update · <Kbd>Esc</Kbd> close
            </span>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn kind="ghost" size="md">Close</Btn>
            <Btn kind="quiet" size="md" icon={Ic.edit}>Edit entry <Kbd>E</Kbd></Btn>
            <Btn kind="primary" size="md">Add update <Kbd>N</Kbd></Btn>
          </div>
        </>
      )}
    >
      <div style={{ padding: 0 }}>

        {/* Project banner — same spine as D2 edit */}
        <div style={{
          padding: "12px 22px",
          background: "var(--calv-teal)", color: "#fff",
          display: "flex", alignItems: "center", gap: 16,
        }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 10,
            letterSpacing: ".12em", textTransform: "uppercase",
            background: "rgba(255,255,255,.15)", padding: "3px 8px", borderRadius: 3,
          }}>Project</div>
          <div style={{
            fontFamily: "var(--font-h1)", fontSize: 18, letterSpacing: ".02em",
            textTransform: "uppercase", lineHeight: 1,
          }}>PHARE Healthy Homes · Walnutport</div>
          <div style={{ fontSize: 12, opacity: .85 }}>· F0621 / F0623</div>
          <span style={{ flex: 1 }} />
          <a style={{ color: "#fff", fontSize: 12, textDecoration: "underline", cursor: "pointer", opacity: .9 }}>
            Open project →
          </a>
        </div>

        {/* Top chip row — direction · type · category · priority */}
        <div style={{
          padding: "14px 22px 0", display: "flex", alignItems: "center",
          gap: 6, flexWrap: "wrap",
        }}>
          <MetaChip kind="slate">Outgoing</MetaChip>
          <MetaChip kind="slate" icon={Ic.phone}>Phone call</MetaChip>
          <MetaChip kind="neutral">General</MetaChip>
          <PriorityBadge level="normal" />
          <span style={{ flex: 1 }} />
          <MetaChip kind="amber">3 follow-ups · 1 open</MetaChip>
        </div>

        {/* Two-column body */}
        <div style={{
          display: "grid", gridTemplateColumns: "1.55fr 1fr", gap: 22,
          padding: "16px 22px 22px",
        }}>

          {/* LEFT — Summary + Updates thread */}
          <div>
            <ReadCard
              title="Summary"
              right={<a style={{ fontSize: 11, color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>edit summary</a>}
            >
              <div style={{ fontSize: 14, lineHeight: 1.65, color: "var(--calv-slate)" }}>
                <p style={{ margin: "0 0 10px" }}>
                  Mother with 4 kids with high blood lead levels. Kids ages 9 and 10. A lot of deteriorating
                  paint. Behavior problems with boy have been since birth. Income around $47,000. 2 older
                  children are 15 and 19 — no indication of issues.
                </p>
                <p style={{ margin: 0 }}>
                  10-year-old (male) has highest levels and most severe behavioral issues — <strong>10.7 ug/dL</strong>.
                </p>
              </div>
            </ReadCard>

            {/* APPLICATION REQUIREMENTS — aggregated from tagged services */}
            <RequirementsPanel
              items={mergeWithInstances(
                aggregateTemplates(["HCP-PHFA-HEMAP", "HCP-RentalHousing"]),
                SAMPLE_REQS_MARIA
              )}
              hint="from 2 tagged services · dedup'd"
            />

            <ReadCard
              title="Activity updates & follow-ups"
              hint="3 updates · 1 follow-up open · oldest at top"
              right={(
                <div style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 11, color: "var(--calv-slate-65)" }}>
                  <span>Filter</span>
                  <Sel value="All updates" style={{ padding: "3px 8px", fontSize: 11, minWidth: 110 }} />
                </div>
              )}
            >
              <FollowupComposer defaultKind="Followup" />

              <div>
                <UpdateItem
                  kind="Note"
                  who="Kaitlyn Wise"
                  when="Apr 21, 2026 · 12:47 PM"
                  body={'Emailed contact info for agencies in NC, the "protect your home from lead" pamphlet, and our application.'}
                  attachments={["lead-pamphlet.pdf", "PHARE-app.pdf"]}
                />
                <UpdateItem
                  kind="Action"
                  who="Kaitlyn Wise"
                  when="Apr 23, 2026 · 9:12 AM"
                  body="Spoke with PA DOH case worker (Linda H.) — they have the BLL labs on file and confirmed eligibility. Will share their inspection report once available."
                />
                <UpdateItem
                  kind="Status"
                  who="System"
                  when="Apr 28, 2026 · 11:00 AM"
                  body={(
                    <span>
                      Tagged with fund code <span style={{ fontFamily: "ui-monospace, Menlo, monospace", padding: "1px 4px", background: "var(--calv-sand-15)", borderRadius: 2 }}>F0621</span> · linked to project <strong>PHARE Healthy Homes · Walnutport</strong>.
                    </span>
                  )}
                />
                <UpdateItem
                  kind="Followup"
                  who="Kaitlyn Wise"
                  when="May 21, 2026 · 3:30 PM · open"
                  body={(
                    <span>
                      Inspector slot held for <strong>Tues 5/26 11:00 AM</strong>. Maria to confirm Mon by 4pm. If no
                      reply, call sister Sandra at 610-555-0188 per Maria's request.
                    </span>
                  )}
                />
              </div>
            </ReadCard>
          </div>

          {/* RIGHT — Contact / Address / Funds / Services / Tags / Linked */}
          <div>
            <ReadCard title="Contact" right={<a style={{ fontSize: 11, color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>see all 7 entries →</a>}>
              <KV label="Name"  value={<strong>Maria Pimentel-Fernandez</strong>} />
              <KV label="Role"  value="Homeowner" />
              <KV label="Phone" value="484-522-4989" mono />
              <KV label="Email" value="yosky2227@gmail.com" />
            </ReadCard>

            <ReadCard title="Address">
              <div style={{ fontSize: 13, color: "var(--calv-slate)", lineHeight: 1.55 }}>
                627 S Lincoln Ave.<br />
                Walnutport, PA 18088
              </div>
              <div style={{ marginTop: 6 }}>
                <a style={{ fontSize: 11, color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>
                  3 prior entries at this address →
                </a>
              </div>
            </ReadCard>

            <ReadCard title="Fund codes" hint="scoped to PHARE-26 · 2 of 2">
              <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                <FundLine code="F0621" name="PHARE Healthy Homes" status="soon" tail="Expires 23d" />
                <FundLine code="F0623" name="Thriving Health (PHARE)" status="perpetual" tail="No expiry" />
              </div>
            </ReadCard>

            <ReadCard title="Program services">
              <ChipRow>
                <Tag label="HCP · PHFA HEMAP" kind="teal" />
                <Tag label="HCP · Rental Housing" kind="teal" />
              </ChipRow>
            </ReadCard>

            <ReadCard title="Tags">
              <ChipRow>
                <Tag label="lead paint" kind="sand" />
                <Tag label="callback needed" kind="sand" />
                <Tag label="spanish-speaking" kind="sand" />
                <Tag label="blood lead level" kind="sand" />
              </ChipRow>
            </ReadCard>

            <ReadCard
              title="Linked records"
              right={<a style={{ fontSize: 11, color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>+ link</a>}
              hint="2 linked"
            >
              <LinkedRow type="Project" code="PHARE-26" label="PHARE Healthy Homes · Walnutport" />
              <LinkedRow type="Entry" code="COMM-7102" label="(Phone) Initial intake · 4/14/2026" />
            </ReadCard>
          </div>
        </div>
      </div>
    </ModalChrome>
  );
}

function FundLine({ code, name, status, tail }) {
  return (
    <div style={{
      display: "flex", alignItems: "center", gap: 8,
      padding: "6px 8px", borderRadius: 3,
      background: "var(--calv-sand-15)",
    }}>
      <FundStatusDot status={status} />
      <span style={{
        fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600,
        color: "var(--calv-slate)",
      }}>{code}</span>
      <span style={{ flex: 1, fontSize: 12, color: "var(--calv-slate)", minWidth: 0, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
        {name}
      </span>
      <span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>{tail}</span>
    </div>
  );
}

function LinkedRow({ type, code, label }) {
  return (
    <div style={{
      display: "flex", alignItems: "center", gap: 8,
      padding: "6px 0", fontSize: 13, color: "var(--calv-slate)",
    }}>
      <span style={{
        fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
        letterSpacing: ".08em", textTransform: "uppercase",
        padding: "2px 6px", borderRadius: 2,
        background: type === "Project" ? "var(--calv-teal-15)" : "var(--calv-slate-15)",
        color: type === "Project" ? "var(--calv-teal)" : "var(--calv-slate)",
      }}>{type}</span>
      <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, color: "var(--calv-slate-65)" }}>{code}</span>
      <a style={{ flex: 1, color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer", minWidth: 0, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{label}</a>
      <span style={{ color: "var(--calv-slate-65)", cursor: "pointer", fontSize: 12 }}>✕</span>
    </div>
  );
}

// ─────────────────────────────────────────────────────────────
// V2 · General intake — Convert-to-Project promotion path
// ─────────────────────────────────────────────────────────────
function ViewerGeneralIntake() {
  return (
    <ModalChrome
      title="COMM-7191 · Untitled intake"
      idLabel="DRAFT · GENERAL"
      breadcrumb={<>Housing <span style={{ color: "var(--calv-slate-35)" }}>›</span> Communication Log <span style={{ color: "var(--calv-slate-35)" }}>›</span> COMM-7191</>}
      subtitle={(
        <span style={{ display: "inline-flex", alignItems: "center", gap: 10 }}>
          <span>May 22, 2026 · 2:14 PM</span>
          <span style={{ color: "var(--calv-slate-35)" }}>·</span>
          <span>Created by <strong style={{ color: "var(--calv-slate)" }}>Kaitlyn Wise</strong></span>
          <span style={{ color: "var(--calv-slate-35)" }}>·</span>
          <span><strong style={{ color: "var(--calv-slate)" }}>0</strong> updates · <strong style={{ color: "var(--calv-amber)" }}>follow-up due tomorrow</strong></span>
        </span>
      )}
      width="100%"
      footer={(
        <>
          <div style={{ display: "flex", alignItems: "center", gap: 14, fontSize: 12, color: "var(--calv-slate-65)" }}>
            <span>This entry is not attached to a project yet.</span>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn kind="ghost" size="md">Close</Btn>
            <Btn kind="quiet" size="md" icon={Ic.edit}>Edit entry</Btn>
            <Btn kind="primary" size="md">Add update <Kbd>N</Kbd></Btn>
          </div>
        </>
      )}
    >
      <div style={{ padding: 0 }}>
        {/* No-project banner — sand instead of teal */}
        <div style={{
          padding: "12px 22px",
          background: "var(--calv-sand-15)",
          borderBottom: "1px solid var(--calv-sand-35)",
          display: "flex", alignItems: "center", gap: 14, flexWrap: "wrap",
        }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 10,
            letterSpacing: ".12em", textTransform: "uppercase",
            color: "var(--calv-slate-65)",
            background: "#fff", padding: "3px 8px", borderRadius: 3,
            border: "1px solid var(--calv-slate-35)",
          }}>General intake</div>
          <div style={{ fontSize: 13, color: "var(--calv-slate)" }}>
            <strong>No project attached.</strong> If this is the start of a real housing case,
            promote it now and the contact / address / notes will carry over.
          </div>
          <span style={{ flex: 1 }} />
          <Btn kind="secondary" size="sm" icon={Ic.arrow}>Convert to project</Btn>
          <Btn kind="quiet" size="sm">Attach to existing project</Btn>
        </div>

        {/* Top chip row */}
        <div style={{
          padding: "14px 22px 0", display: "flex", alignItems: "center",
          gap: 6, flexWrap: "wrap",
        }}>
          <MetaChip kind="slate">Incoming</MetaChip>
          <MetaChip kind="slate" icon={Ic.phone}>Phone call</MetaChip>
          <MetaChip kind="neutral">General</MetaChip>
          <PriorityBadge level="high" />
          <span style={{ flex: 1 }} />
          <MetaChip kind="amber">Follow-up due May 24</MetaChip>
        </div>

        <div style={{
          display: "grid", gridTemplateColumns: "1.55fr 1fr", gap: 22,
          padding: "16px 22px 22px",
        }}>
          {/* LEFT */}
          <div>
            <ReadCard title="Summary">
              <div style={{ fontSize: 14, lineHeight: 1.65, color: "var(--calv-slate)" }}>
                <p style={{ margin: 0 }}>
                  Walk-in / called the office. Family of three, currently doubled up with relatives in
                  Bethlehem; <strong>landlord raised rent $400/mo</strong> at the unit they were leaving.
                  Two kids under 10. Asked about rent assistance & whether we can help find a unit under
                  $1,400 in the Allentown school district. <strong>Spanish preferred.</strong>
                </p>
                <p style={{ margin: "10px 0 0" }}>
                  No prior CALV record under either name (verified). Would qualify for HCP rental
                  counseling and possibly Sixth Street if shelter is needed. Need to follow up before
                  weekend — they're at the relatives' apartment until 5/31 only.
                </p>
              </div>
            </ReadCard>

            {/* APPLICATION REQUIREMENTS — gates Convert-to-Project below */}
            <RequirementsPanel
              items={mergeWithInstances(
                aggregateTemplates(["HCP-RentalHousing"]),
                SAMPLE_REQS_ESPERANZA
              )}
              title="Application requirements"
              hint="must be met before Convert-to-Project"
            />

            <ReadCard
              title="Activity updates & follow-ups"
              hint="0 updates yet · 1 follow-up pending"
            >
              <FollowupComposer defaultKind="Followup" />

              <div style={{
                padding: "18px 14px", textAlign: "center",
                color: "var(--calv-slate-65)", fontSize: 13,
                background: "var(--calv-sand-15)",
                border: "1px dashed var(--calv-sand-35)",
                borderRadius: 4,
              }}>
                No updates yet. Once this becomes a project, follow-ups land here and on the project timeline.
              </div>
            </ReadCard>
          </div>

          {/* RIGHT */}
          <div>
            <ReadCard title="Contact">
              <KV label="Name"  value={<strong>Esperanza Villarroel-Cruz</strong>} />
              <KV label="Role"  value="Tenant" />
              <KV label="Phone" value="484-712-0418" mono />
              <KV label="Email" value="—" />
            </ReadCard>

            <ReadCard title="Address" hint="provisional · entry subject">
              <div style={{ fontSize: 13, color: "var(--calv-slate)", lineHeight: 1.55 }}>
                <em style={{ color: "var(--calv-slate-65)" }}>Temp:</em> c/o Aunt — 519 N New St.<br />
                Bethlehem, PA 18018
              </div>
            </ReadCard>

            {/* Fund codes intentionally hidden in general-intake view */}
            <ReadCard
              title="Fund codes"
              hint="hidden · no project yet"
            >
              <div style={{ fontSize: 12, color: "var(--calv-slate-65)", lineHeight: 1.55 }}>
                Hidden by default for general intake. They appear automatically once this entry is
                attached to (or promoted into) a project.
              </div>
            </ReadCard>

            <ReadCard title="Program services" hint="suggested">
              <ChipRow>
                <Tag label="HCP · Rental Housing" kind="teal" />
                <Tag label="HCP · Pre-Purchase Counseling" kind="teal" />
                <span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>· based on summary keywords</span>
              </ChipRow>
            </ReadCard>

            <ReadCard title="Tags">
              <ChipRow>
                <Tag label="spanish-speaking" kind="sand" />
                <Tag label="intake" kind="sand" />
                <Tag label="rent burden" kind="sand" />
                <Tag label="children in household" kind="sand" />
              </ChipRow>
            </ReadCard>

            <ReadCard title="Linked records">
              <div style={{ fontSize: 12, color: "var(--calv-slate-65)", fontStyle: "italic" }}>
                No linked records.
              </div>
            </ReadCard>
          </div>
        </div>
      </div>
    </ModalChrome>
  );
}

Object.assign(window, {
  ViewerProjectAttached, ViewerGeneralIntake,
  MetaChip, PriorityBadge, KV, ReadCard, UpdateItem, FollowupComposer,
});
