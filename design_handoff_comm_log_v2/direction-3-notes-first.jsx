// direction-3-notes-first.jsx — Direction 3: Two-pane, Notes-First.
// Left rail: ALL metadata compact. Right pane: case notes are the artifact.

// Compact metadata row used in left rail
function MetaRow({ label, value, mono, muted, kbd, accent, children }) {
  return (
    <div style={{
      display: "grid", gridTemplateColumns: "76px 1fr", gap: 8,
      alignItems: "baseline", padding: "5px 0",
    }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
        letterSpacing: ".1em", textTransform: "uppercase",
        color: "var(--calv-slate-65)",
      }}>{label}</div>
      <div style={{
        fontFamily: mono ? "ui-monospace, Menlo, monospace" : "var(--font-body)",
        fontSize: 13, color: muted ? "var(--calv-slate-65)" : "var(--calv-slate)",
        display: "flex", alignItems: "center", gap: 6, flexWrap: "wrap",
        ...(accent ? { color: accent } : {}),
      }}>
        {value}
        {children}
        {kbd && <span style={{ marginLeft: "auto" }}><Kbd>{kbd}</Kbd></span>}
      </div>
    </div>
  );
}

function D3Form({ mode = "primary" }) {
  // mode: "primary" | "blank"
  const blank = mode === "blank";

  return (
    <ModalChrome
      title={blank ? "New Communication" : "Edit Communication"}
      idLabel={blank ? "DRAFT" : "COMM-7188"}
      breadcrumb={<>Housing <span style={{ color: "var(--calv-slate-35)" }}>›</span> Communication Log <span style={{ color: "var(--calv-slate-35)" }}>›</span> {blank ? "New" : "COMM-7188"}</>}
      width="100%"
      footer={(
        <>
          <div style={{ display: "flex", gap: 14, fontSize: 12, color: "var(--calv-slate-65)" }}>
            {!blank ? (
              <>
                <span><strong style={{ color: "var(--calv-slate)" }}>847</strong> words · 4 min read</span>
                <span>Autosaved 11s ago · v7</span>
                <span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}>
                  <Kbd>⌘/</Kbd> formatting
                </span>
              </>
            ) : (
              <>
                <span>Subject auto-set when you fill address</span>
                <span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}>
                  <Kbd>⌘/</Kbd> formatting · <Kbd>⌘K</Kbd> jump
                </span>
              </>
            )}
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn kind="ghost" size="md">Cancel <Kbd>Esc</Kbd></Btn>
            <Btn kind="primary" size="md">{blank ? "Create entry" : "Update entry"} <Kbd>⌘⏎</Kbd></Btn>
          </div>
        </>
      )}
    >
      <div style={{ display: "grid", gridTemplateColumns: "320px 1fr", minHeight: 700 }}>

        {/* ─── LEFT RAIL — all metadata, compact ─── */}
        <aside style={{
          background: "var(--calv-sand-15)",
          borderRight: "1px solid var(--calv-sand-35)",
          padding: "16px 18px", overflowY: "auto",
        }}>
          {/* Project anchor */}
          <div style={{ marginBottom: 14 }}>
            <div style={{
              fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
              letterSpacing: ".1em", textTransform: "uppercase",
              color: "var(--calv-slate-65)", marginBottom: 4,
            }}>Project</div>
            {blank ? (
              <button style={{
                width: "100%", textAlign: "left",
                background: "transparent",
                border: "1px dashed var(--calv-slate-35)",
                borderRadius: 4, padding: "10px 12px", cursor: "pointer",
                fontFamily: "var(--font-body)", fontSize: 13, color: "var(--calv-slate-65)",
                display: "flex", alignItems: "center", gap: 8,
              }}>
                {Ic.link} <span>Attach to a project…</span>
                <span style={{ marginLeft: "auto" }}><Kbd>⌘P</Kbd></span>
              </button>
            ) : (
              <div style={{
                padding: "10px 12px", background: "#fff",
                border: "1px solid var(--calv-slate-15)", borderRadius: 4,
              }}>
                <div style={{ display: "flex", alignItems: "center", gap: 6, marginBottom: 4 }}>
                  <span style={{
                    fontFamily: "ui-monospace, Menlo, monospace", fontSize: 10, fontWeight: 600,
                    background: "var(--calv-teal-15)", color: "var(--calv-teal)",
                    padding: "1px 5px", borderRadius: 3,
                  }}>F0530</span>
                  <span style={{ fontSize: 11, color: "var(--calv-slate-65)", marginLeft: "auto" }}>
                    last entry 3d ago
                  </span>
                </div>
                <div style={{
                  fontFamily: "var(--font-sub)", fontSize: 13, fontWeight: 700,
                  letterSpacing: ".02em", textTransform: "uppercase",
                  color: "var(--calv-slate)", lineHeight: 1.2,
                }}>136 S. 8th St. Rehab</div>
                <div style={{ marginTop: 6, fontSize: 11, color: "var(--calv-slate-65)", display: "flex", gap: 10 }}>
                  <span>4 contacts</span>
                  <span>2 funds</span>
                  <a style={{ color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer", marginLeft: "auto" }}>change</a>
                </div>
              </div>
            )}
          </div>

          {/* Meta */}
          <div style={{
            background: "#fff", border: "1px solid var(--calv-slate-15)",
            borderRadius: 4, padding: "8px 12px", marginBottom: 14,
          }}>
            <MetaRow label="Date"      value={blank ? "05/23/2026" : "05/23/2026"} mono />
            <MetaRow label="Type"      value={blank ? "Phone Call" : "Phone Call"} />
            <MetaRow label="Direction" value={blank ? "Outgoing" : "Incoming"} />
            <MetaRow label="Category"  value={blank ? "—" : "Case work"} muted={blank} />
          </div>

          {/* Contact */}
          <div style={{ marginBottom: 14 }}>
            <SectionLabel right={(
              <a style={{ fontSize: 10, color: "var(--calv-teal)", cursor: "pointer", textDecoration: "underline" }}>
                {blank ? "search…" : "edit"}
              </a>
            )}>Contact</SectionLabel>
            <div style={{
              background: "#fff", border: "1px solid var(--calv-slate-15)",
              borderRadius: 4, padding: "10px 12px",
            }}>
              {blank ? (
                <div style={{ fontSize: 13, color: "var(--calv-slate-65)" }}>
                  <input placeholder="First name *" style={{
                    width: "100%", border: 0, fontFamily: "var(--font-body)", fontSize: 13,
                    padding: "2px 0", outline: "none",
                    borderBottom: "1px solid var(--calv-slate-15)",
                    marginBottom: 6, background: "transparent",
                  }} />
                  <input placeholder="Last name" style={{
                    width: "100%", border: 0, fontFamily: "var(--font-body)", fontSize: 13,
                    padding: "2px 0", outline: "none",
                    borderBottom: "1px solid var(--calv-slate-15)",
                    background: "transparent",
                  }} />
                </div>
              ) : (
                <>
                  <div style={{ fontSize: 14, color: "var(--calv-slate)", fontWeight: 600, lineHeight: 1.2 }}>
                    Devon Caraballo
                  </div>
                  <div style={{ fontSize: 11, color: "var(--calv-slate-65)", marginTop: 2 }}>
                    Tenant · this address
                  </div>
                  <div style={{ display: "flex", flexDirection: "column", gap: 4, marginTop: 8, fontSize: 12 }}>
                    <div style={{ display: "flex", alignItems: "center", gap: 6, color: "var(--calv-slate)" }}>
                      <span style={{ color: "var(--calv-slate-65)" }}>{Ic.phone}</span>
                      <span style={{ fontFamily: "ui-monospace, Menlo, monospace" }}>610-820-0093</span>
                    </div>
                    <div style={{ display: "flex", alignItems: "center", gap: 6, color: "var(--calv-slate)" }}>
                      <span style={{ color: "var(--calv-slate-65)" }}>{Ic.mail}</span>
                      <span>devon.c@gmail.com</span>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>

          {/* Address */}
          <div style={{ marginBottom: 14 }}>
            <SectionLabel
              hint={blank ? "(sets subject)" : null}
              right={!blank && <a style={{ fontSize: 10, color: "var(--calv-teal)", cursor: "pointer", textDecoration: "underline" }}>edit</a>}
            >
              Address
            </SectionLabel>
            <div style={{
              background: "#fff", border: "1px solid var(--calv-slate-15)",
              borderRadius: 4, padding: "10px 12px",
              fontSize: 13, color: blank ? "var(--calv-slate-65)" : "var(--calv-slate)",
              lineHeight: 1.5,
            }}>
              {blank ? (
                <span style={{ color: "var(--calv-slate-65)" }}>—</span>
              ) : (
                <>
                  <div>136 S. 8th St.</div>
                  <div>Allentown, PA 18101</div>
                </>
              )}
            </div>
          </div>

          {/* Fund codes */}
          {!blank && (
            <div style={{ marginBottom: 14 }}>
              <SectionLabel hint="2 of 2 on project">Fund codes</SectionLabel>
              <div style={{ display: "flex", flexDirection: "column", gap: 4 }}>
                <FundPill code="F0530" name="136 S. 8th St." status="ok" days="2y left" checked />
                <FundPill code="F4206" name="LC roof & HVAC (CDBG)" status="ok" days="10mo left" />
                <button style={{
                  background: "transparent", border: "1px dashed var(--calv-slate-35)",
                  borderRadius: 4, padding: "5px 8px", textAlign: "left",
                  fontFamily: "var(--font-body)", fontSize: 11, color: "var(--calv-slate-65)",
                  cursor: "pointer",
                }}>
                  + add fund code
                </button>
              </div>
            </div>
          )}

          {/* Services */}
          {!blank && (
            <div style={{ marginBottom: 14 }}>
              <SectionLabel hint="1 selected">Program services</SectionLabel>
              <div style={{ display: "flex", flexWrap: "wrap", gap: 4 }}>
                <ServicePill program="CAH" label="Insulation" checked />
                <button style={{
                  background: "transparent", border: "1px dashed var(--calv-slate-35)",
                  borderRadius: 999, padding: "3px 8px",
                  fontFamily: "var(--font-body)", fontSize: 11, color: "var(--calv-slate-65)",
                  cursor: "pointer",
                }}>+ add</button>
              </div>
            </div>
          )}

          {/* Tags */}
          {!blank && (
            <div style={{ marginBottom: 14 }}>
              <SectionLabel>Tags</SectionLabel>
              <div style={{ display: "flex", flexWrap: "wrap", gap: 4 }}>
                <Tag label="winter heat" removable kind="sand" />
                <Tag label="callback needed" removable kind="sand" />
                <button style={{
                  background: "transparent", border: "1px dashed var(--calv-slate-35)",
                  borderRadius: 999, padding: "3px 8px",
                  fontFamily: "var(--font-body)", fontSize: 11, color: "var(--calv-slate-65)",
                  cursor: "pointer",
                }}>+ tag</button>
              </div>
            </div>
          )}

          {/* Meta footer — entry history */}
          {!blank && (
            <div style={{
              paddingTop: 12, borderTop: "1px dashed var(--calv-sand-65)",
              fontSize: 11, color: "var(--calv-slate-65)",
            }}>
              <div>Created 5/23 · K. Schreiner</div>
              <div>Edited 6× · last 11s ago</div>
              <div style={{ marginTop: 6 }}>
                <a style={{ color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>
                  Open entry timeline →
                </a>
              </div>
            </div>
          )}
        </aside>

        {/* ─── RIGHT PANE — case notes editor ─── */}
        <main style={{
          padding: "20px 32px 24px",
          display: "flex", flexDirection: "column",
          background: "#fff", minHeight: 0,
        }}>
          {/* Subject line — derived but editable */}
          <div style={{ marginBottom: 6 }}>
            <div style={{
              fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
              letterSpacing: ".1em", textTransform: "uppercase",
              color: "var(--calv-slate-65)", marginBottom: 4,
              display: "flex", alignItems: "center", gap: 8,
            }}>
              Subject
              {!blank && (
                <span style={{
                  fontWeight: 400, fontSize: 10, letterSpacing: 0, textTransform: "none",
                  color: "var(--calv-slate-65)",
                }}>
                  · auto from address
                </span>
              )}
            </div>
            <input
              defaultValue={blank ? "" : "136 S. 8th St · Allentown"}
              placeholder="Auto · COMM-7188"
              style={{
                width: "100%", border: 0, outline: "none",
                fontFamily: "var(--font-h1)", fontWeight: 700,
                fontSize: 28, letterSpacing: ".01em", textTransform: "uppercase",
                color: blank ? "var(--calv-slate-65)" : "var(--calv-slate)",
                background: "transparent", padding: 0, lineHeight: 1.1,
              }}
            />
          </div>

          {/* Toolbar mini */}
          <div style={{
            display: "flex", alignItems: "center", gap: 6,
            padding: "8px 0 6px", borderBottom: "1px solid var(--calv-slate-15)",
            fontSize: 12, color: "var(--calv-slate-65)",
          }}>
            <ToolbarBtn>B</ToolbarBtn>
            <ToolbarBtn italic>I</ToolbarBtn>
            <span style={{ color: "var(--calv-slate-35)" }}>|</span>
            <ToolbarBtn>H2</ToolbarBtn>
            <ToolbarBtn>• List</ToolbarBtn>
            <ToolbarBtn>1. List</ToolbarBtn>
            <ToolbarBtn>☐ Task</ToolbarBtn>
            <span style={{ color: "var(--calv-slate-35)" }}>|</span>
            <ToolbarBtn>@ mention</ToolbarBtn>
            <ToolbarBtn>/ insert</ToolbarBtn>
            <span style={{ flex: 1 }} />
            <span>Markdown OK · <Kbd>⌘/</Kbd></span>
          </div>

          {/* Notes body */}
          <div style={{
            flex: 1, paddingTop: 16,
            fontFamily: "var(--font-body)", fontSize: 15,
            lineHeight: 1.65, color: "var(--calv-slate)",
            overflow: "hidden",
          }}>
            {blank ? (
              <div style={{ color: "var(--calv-slate-65)", fontSize: 15, lineHeight: 1.7 }}>
                <p style={{ margin: "0 0 14px" }}>What happened on this call?</p>
                <p style={{ margin: "0 0 14px", fontSize: 13 }}>
                  Tip: lead with the outcome.<br />
                  Use <kbd style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, padding: "1px 4px", background: "var(--calv-slate-15)", borderRadius: 2 }}>/</kbd> to drop in a checklist, callback reminder, or block-quote of what they said.
                </p>
                <div style={{ display: "flex", gap: 8, marginTop: 20, flexWrap: "wrap" }}>
                  <TemplateChip>· Lead-paint intake</TemplateChip>
                  <TemplateChip>· HEMAP eligibility call</TemplateChip>
                  <TemplateChip>· Contractor scheduling</TemplateChip>
                  <TemplateChip>· Landlord check-in</TemplateChip>
                  <TemplateChip>· Voicemail (no answer)</TemplateChip>
                </div>
              </div>
            ) : (
              <NotesBody />
            )}
          </div>
        </main>
      </div>
    </ModalChrome>
  );
}

function SectionLabel({ children, hint, right }) {
  return (
    <div style={{
      display: "flex", alignItems: "baseline", justifyContent: "space-between",
      marginBottom: 4,
    }}>
      <span style={{
        fontFamily: "var(--font-sub)", fontSize: 9.5, fontWeight: 700,
        letterSpacing: ".1em", textTransform: "uppercase",
        color: "var(--calv-slate-65)",
      }}>
        {children}
        {hint && <span style={{ fontWeight: 400, fontSize: 9.5, letterSpacing: 0, textTransform: "none", color: "var(--calv-slate-65)", marginLeft: 6 }}>{hint}</span>}
      </span>
      {right}
    </div>
  );
}

function FundPill({ code, name, status, days, checked }) {
  return (
    <button style={{
      display: "flex", alignItems: "center", gap: 8,
      background: checked ? "#fff" : "transparent",
      border: checked ? "1px solid var(--calv-teal-35)" : "1px solid var(--calv-slate-15)",
      borderRadius: 4, padding: "6px 8px", cursor: "pointer", textAlign: "left",
    }}>
      <span style={{
        width: 12, height: 12, borderRadius: 2,
        background: checked ? "var(--calv-teal)" : "transparent",
        border: checked ? "0" : "1.5px solid var(--calv-slate-35)",
        display: "inline-flex", alignItems: "center", justifyContent: "center", flex: "0 0 auto",
      }}>
        {checked && <svg width="8" height="8" viewBox="0 0 12 12" fill="none">
          <path d="M2.5 6.5L5 9L9.5 3.5" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>}
      </span>
      <span style={{
        fontFamily: "ui-monospace, Menlo, monospace", fontSize: 10.5, fontWeight: 600,
        color: "var(--calv-slate)",
      }}>{code}</span>
      <span style={{ flex: 1, fontSize: 12, color: "var(--calv-slate)", lineHeight: 1.2, minWidth: 0, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
        {name}
      </span>
      <FundStatusDot status={status} />
    </button>
  );
}

function ServicePill({ program, label, checked }) {
  const palette = { CAH: "var(--calv-amber)", HCP: "var(--calv-teal)", SSS: "var(--calv-sage)" };
  const bg = { CAH: "var(--calv-amber-15)", HCP: "var(--calv-teal-15)", SSS: "var(--calv-sage-15)" };
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: 5,
      background: checked ? bg[program] : "#fff",
      border: checked ? `1px solid ${palette[program]}` : "1px solid var(--calv-slate-15)",
      borderRadius: 999, padding: "3px 8px 3px 5px",
      fontFamily: "var(--font-body)", fontSize: 11, color: "var(--calv-slate)",
      cursor: "pointer",
    }}>
      <span style={{
        fontFamily: "var(--font-sub)", fontSize: 8.5, fontWeight: 700,
        letterSpacing: ".06em", textTransform: "uppercase",
        background: palette[program], color: "#fff",
        padding: "1px 4px", borderRadius: 2,
      }}>{program}</span>
      {label}
    </span>
  );
}

function ToolbarBtn({ children, italic }) {
  return (
    <button style={{
      fontFamily: "var(--font-body)", fontSize: 12,
      fontStyle: italic ? "italic" : "normal", fontWeight: 600,
      background: "transparent", border: 0, color: "var(--calv-slate)",
      cursor: "pointer", padding: "3px 6px", borderRadius: 3,
    }}>{children}</button>
  );
}

function TemplateChip({ children }) {
  return (
    <button style={{
      background: "var(--calv-sand-15)",
      border: "1px solid var(--calv-sand-35)",
      borderRadius: 4, padding: "6px 12px",
      fontFamily: "var(--font-body)", fontSize: 13, color: "var(--calv-slate)",
      cursor: "pointer",
    }}>{children}</button>
  );
}

// A long, paragraph-rich notes body to show overflow handling
function NotesBody() {
  return (
    <div>
      <p style={{ margin: "0 0 12px" }}>
        <strong>Outcome:</strong> Devon will be home for the heat-tech walk-through 5/27 at 9am — confirmed.
        Tenant relations team has been looped in. Below: full call notes.
      </p>

      <h3 style={{
        fontFamily: "var(--font-sub)", fontSize: 13, fontWeight: 700,
        letterSpacing: ".06em", textTransform: "uppercase",
        color: "var(--calv-slate)", margin: "18px 0 6px",
      }}>Background</h3>
      <p style={{ margin: "0 0 12px" }}>
        Devon C. (tenant at 136 S 8th, second floor) called 5/23 morning to follow up on the heating-system replacement
        we'd flagged during the December insulation audit. He'd been told by the landlord (J. Reyes, separate entry) that
        "the city" was handling it, which is incorrect — we are the project sponsor under <span style={{ fontFamily: "ui-monospace, Menlo, monospace", background: "var(--calv-sand-15)", padding: "1px 4px", borderRadius: 2 }}>F0530</span> and
        contractor scheduling runs through Kaitlyn.
      </p>

      <h3 style={{
        fontFamily: "var(--font-sub)", fontSize: 13, fontWeight: 700,
        letterSpacing: ".06em", textTransform: "uppercase",
        color: "var(--calv-slate)", margin: "18px 0 6px",
      }}>What he said</h3>
      <ul style={{ margin: "0 0 12px", paddingLeft: 20 }}>
        <li style={{ marginBottom: 4 }}>Second-floor radiators have been cold "since the cold snap in January."</li>
        <li style={{ marginBottom: 4 }}>Space heaters in two rooms (concern: load + fire risk; he understands).</li>
        <li style={{ marginBottom: 4 }}>Wife is 7mo pregnant — they don't want a multi-day shutdown without backup heat.</li>
        <li style={{ marginBottom: 4 }}>Has the landlord's permission in writing to allow contractor access; will forward email.</li>
      </ul>

      <h3 style={{
        fontFamily: "var(--font-sub)", fontSize: 13, fontWeight: 700,
        letterSpacing: ".06em", textTransform: "uppercase",
        color: "var(--calv-slate)", margin: "18px 0 6px",
      }}>Plan</h3>
      <ol style={{ margin: "0 0 12px", paddingLeft: 20 }}>
        <li style={{ marginBottom: 4 }}>Schedule walk-through with Jimmy McAllister (HVAC) — <em>5/27 9am, confirmed</em>.</li>
        <li style={{ marginBottom: 4 }}>Hold one portable heat-pump unit from the WHR cache for overnight if replacement runs &gt;1 day.</li>
        <li style={{ marginBottom: 4 }}>Loop Devon's email into the F0530 distribution list so he gets schedule updates directly.</li>
        <li style={{ marginBottom: 4 }}>Coordinate with tenant relations on a written status update for landlord J. Reyes.</li>
      </ol>

      <div style={{
        margin: "14px 0", padding: "10px 14px",
        background: "var(--calv-sand-15)",
        borderLeft: "3px solid var(--calv-amber)",
        fontStyle: "italic", color: "var(--calv-slate)", fontSize: 14,
      }}>
        "Just tell me what day, I'll be home. We just need to know."  — Devon, paraphrased
      </div>

      <p style={{ margin: "0 0 0", color: "var(--calv-slate-65)", fontSize: 13 }}>
        — Continued: see attached email thread (forwarded 5/23 11:42a).
      </p>
    </div>
  );
}

Object.assign(window, { D3Form });
