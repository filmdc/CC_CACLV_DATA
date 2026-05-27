// direction-2-project-first.jsx — Direction 2: Project-First / Context-Driven.
// "Related to a Project?" stops being a buried field and becomes the architectural anchor.
// The form's composition CHANGES based on whether a project is selected.

// ─────────────────────────────────────────────────────────────
// D2_NoProject — general intake state
// ─────────────────────────────────────────────────────────────
function D2NoProject() {
  return (
    <ModalChrome
      title="New Communication"
      idLabel="COMM-7185 · DRAFT"
      breadcrumb={<>Housing <span style={{ color: "var(--calv-slate-35)" }}>›</span> Communication Log <span style={{ color: "var(--calv-slate-35)" }}>›</span> New</>}
      width="100%"
      footer={(
        <>
          <div style={{ display: "flex", gap: 14, fontSize: 12, color: "var(--calv-slate-65)" }}>
            <span>Subject will be set from address or numbered automatically</span>
            <span>Autosaved 3s ago</span>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn kind="ghost" size="md">Cancel</Btn>
            <Btn kind="primary" size="md">Create entry <Kbd>⌘⏎</Kbd></Btn>
          </div>
        </>
      )}
    >
      <div style={{ padding: "0" }}>

        {/* PROJECT PICKER — the hero of this direction */}
        <div style={{
          padding: "22px 24px",
          background: "var(--calv-sand-15)",
          borderBottom: "1px solid var(--calv-sand-35)",
        }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
            letterSpacing: ".12em", textTransform: "uppercase",
            color: "var(--calv-slate-65)", marginBottom: 8,
          }}>Step 1 · Anchor this entry</div>

          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
            {/* Active option — General intake */}
            <button style={{
              textAlign: "left", padding: "16px 18px", cursor: "pointer",
              background: "#fff",
              border: "2px solid var(--calv-slate)",
              borderRadius: 4, boxShadow: "var(--shadow-sm)",
              fontFamily: "inherit", color: "inherit",
            }}>
              <div style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 6 }}>
                <div style={{
                  width: 16, height: 16, borderRadius: 999,
                  border: "5px solid var(--calv-slate)", background: "#fff",
                }} />
                <span style={{
                  fontFamily: "var(--font-h1)", fontSize: 18, letterSpacing: ".02em",
                  textTransform: "uppercase", color: "var(--calv-slate)",
                }}>General intake</span>
              </div>
              <div style={{ fontSize: 12, color: "var(--calv-slate-65)", lineHeight: 1.5, paddingLeft: 24 }}>
                Stand-alone call or message not tied to a housing project. Fund codes and program
                services stay hidden until you need them.
              </div>
            </button>

            {/* Inactive — pick project */}
            <button style={{
              textAlign: "left", padding: "16px 18px", cursor: "pointer",
              background: "#fff",
              border: "1.5px solid var(--calv-slate-15)",
              borderRadius: 4,
              fontFamily: "inherit", color: "inherit",
            }}>
              <div style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 6 }}>
                <div style={{
                  width: 16, height: 16, borderRadius: 999,
                  border: "1.5px solid var(--calv-slate-35)", background: "#fff",
                }} />
                <span style={{
                  fontFamily: "var(--font-h1)", fontSize: 18, letterSpacing: ".02em",
                  textTransform: "uppercase", color: "var(--calv-slate-65)",
                }}>Attach to project…</span>
                <span style={{ marginLeft: "auto" }}><Kbd>⌘P</Kbd></span>
              </div>
              <div style={{ fontSize: 12, color: "var(--calv-slate-65)", lineHeight: 1.5, paddingLeft: 24 }}>
                Recent · PHARE Healthy Homes · 136 S. 8th St. · Allentown Better Homes · LC Urgent HVAC · Sixth Street FY26
              </div>
            </button>
          </div>
        </div>

        {/* SIMPLIFIED FORM — no fund codes, no services panel */}
        <div style={{ padding: "18px 24px" }}>
          <div style={{ display: "grid", gridTemplateColumns: "130px 160px 120px 160px 140px 1fr", gap: 10, marginBottom: 16, alignItems: "end" }}>
            <div><FieldLabel required>Date</FieldLabel><TI value="05/23/2026" iconRight={Ic.cal} /></div>
            <div><FieldLabel required>Type</FieldLabel><Sel value="Email" /></div>
            <div><FieldLabel required>Direction</FieldLabel><Sel value="Incoming" /></div>
            <div><FieldLabel required>Category</FieldLabel><Sel value="General" /></div>
            <div>
              <FieldLabel>Priority</FieldLabel>
              <Sel value="● Normal" />
            </div>
            <div>
              <FieldLabel hint="(auto from address if blank)">Subject</FieldLabel>
              <TI placeholder="Auto · COMM-7185" value="" />
            </div>
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 22 }}>
            <div>
              <FormSection title="Contact" style={{ borderTop: 0, paddingTop: 0 }} dense>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10, marginBottom: 10 }}>
                  <div><FieldLabel required>First name</FieldLabel><TI value="Daniel" /></div>
                  <div><FieldLabel>Last name</FieldLabel><TI value="Wexler-Imai" /></div>
                </div>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10, marginBottom: 10 }}>
                  <div><FieldLabel>Role</FieldLabel><Sel value="Partner" /></div>
                  <div><FieldLabel>Phone</FieldLabel><TI value="" placeholder="Optional" iconRight={Ic.phone} /></div>
                </div>
                <div><FieldLabel>Email</FieldLabel><TI value="dwexler@lvplanning.org" iconRight={Ic.mail} /></div>
              </FormSection>

              <FormSection title="Address" hint="optional · sets entry subject" dense>
                <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr 70px 90px", gap: 10 }}>
                  <div><FieldLabel>Street</FieldLabel><TI value="" placeholder="" iconRight={Ic.pin} /></div>
                  <div><FieldLabel>City</FieldLabel><TI value="" placeholder="" /></div>
                  <div><FieldLabel>State</FieldLabel><Sel value="PA" /></div>
                  <div><FieldLabel>ZIP</FieldLabel><TI value="" placeholder="" /></div>
                </div>
              </FormSection>
            </div>

            <div>
              <FormSection title="Case notes" hint="48 words" style={{ borderTop: 0, paddingTop: 0 }}>
                <TA rows={11} value={`Dan from Lehigh Valley Planning emailed — county is interested in coordinating our PHARE rosters with their housing-needs assessment for the next funding cycle. Asked if we'd join a working group meeting in June.

Will loop in David before responding.`} />
              </FormSection>

              <FormSection title="Tags" dense>
                <div style={{ display: "flex", flexWrap: "wrap", gap: 6, alignItems: "center" }}>
                  <Tag label="partner outreach" removable kind="sand" />
                  <Tag label="follow-up" removable kind="sand" />
                  <input placeholder="Add tag…" style={{
                    fontFamily: "var(--font-body)", fontSize: 12,
                    border: 0, padding: "4px 6px", outline: "none", flex: 1,
                    borderBottom: "1px dashed var(--calv-slate-35)",
                    background: "transparent",
                  }} />
                </div>
              </FormSection>

              {/* Collapsed disclosure: program services & fund codes only when needed */}
              <div style={{ marginTop: 12, fontSize: 12 }}>
                <a style={{ color: "var(--calv-teal)", cursor: "pointer", textDecoration: "underline" }}>
                  + tag with a program service or fund code
                </a>
                <span style={{ color: "var(--calv-slate-65)", marginLeft: 8 }}>(rarely needed for general intake)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ModalChrome>
  );
}

// ─────────────────────────────────────────────────────────────
// D2_WithProject — same form, but project chosen → reshaped
// ─────────────────────────────────────────────────────────────
function D2WithProject() {
  return (
    <ModalChrome
      title="Edit Communication"
      idLabel="COMM-7186"
      breadcrumb={<>Housing <span style={{ color: "var(--calv-slate-35)" }}>›</span> Communication Log <span style={{ color: "var(--calv-slate-35)" }}>›</span> COMM-7186</>}
      width="100%"
      footer={(
        <>
          <div style={{ display: "flex", gap: 14, fontSize: 12, color: "var(--calv-slate-65)" }}>
            <span>Subject: <strong style={{ color: "var(--calv-slate)" }}>1843 Lehigh St · Allentown</strong></span>
            <span>Autosaved 6s ago</span>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn kind="ghost" size="md">Cancel</Btn>
            <Btn kind="primary" size="md">Update entry <Kbd>⌘⏎</Kbd></Btn>
          </div>
        </>
      )}
    >
      <div style={{ padding: 0 }}>

        {/* PROJECT BANNER — anchored at top, becomes the page's spine */}
        <div style={{
          padding: "14px 22px",
          background: "var(--calv-teal)",
          color: "#fff",
          display: "flex", alignItems: "center", gap: 16,
        }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 11,
            letterSpacing: ".12em", textTransform: "uppercase",
            background: "rgba(255,255,255,.15)", padding: "3px 8px", borderRadius: 3,
          }}>Project</div>
          <div>
            <div style={{
              fontFamily: "var(--font-h1)", fontSize: 20, letterSpacing: ".02em",
              textTransform: "uppercase", lineHeight: 1,
            }}>Allentown Better Homes · Block 7</div>
            <div style={{ fontSize: 12, marginTop: 4, opacity: .85, display: "flex", gap: 12 }}>
              <span>F0531</span>
              <span>·</span>
              <span>18 contacts on file</span>
              <span>·</span>
              <span>2 fund codes available</span>
              <span>·</span>
              <span>last entry 2d ago</span>
            </div>
          </div>
          <div style={{ flex: 1 }} />
          <a style={{ color: "#fff", fontSize: 12, textDecoration: "underline", cursor: "pointer", opacity: .9 }}>
            Change project
          </a>
          <a style={{ color: "#fff", fontSize: 12, textDecoration: "underline", cursor: "pointer", opacity: .9 }}>
            Open project →
          </a>
        </div>

        <div style={{ padding: "16px 22px 18px" }}>
          <div style={{ display: "grid", gridTemplateColumns: "130px 160px 120px 160px 140px 1fr", gap: 10, marginBottom: 16, alignItems: "end" }}>
            <div><FieldLabel required>Date</FieldLabel><TI value="05/23/2026" iconRight={Ic.cal} /></div>
            <div><FieldLabel required>Type</FieldLabel><Sel value="Phone Call" /></div>
            <div><FieldLabel required>Direction</FieldLabel><Sel value="Outgoing" /></div>
            <div><FieldLabel required>Category</FieldLabel><Sel value="Case work" /></div>
            <div>
              <FieldLabel>Priority</FieldLabel>
              <Sel value="● Normal" />
            </div>
            <div>
              <FieldLabel hint="(auto from address)">Subject</FieldLabel>
              <TI value="1843 Lehigh St · Allentown" />
            </div>
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr", gap: 22 }}>
            {/* LEFT */}
            <div>
              <FormSection
                title="Contact"
                hint="18 contacts on this project"
                style={{ borderTop: 0, paddingTop: 0 }}
                dense
                right={(
                  <div style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 11, color: "var(--calv-slate-65)" }}>
                    <span>Project roster</span><Kbd>⌘K</Kbd>
                  </div>
                )}
              >
                {/* Project-roster suggestion strip */}
                <div style={{
                  background: "var(--calv-sand-15)",
                  border: "1px solid var(--calv-sand-35)",
                  borderRadius: 4, padding: "8px 10px", marginBottom: 10,
                  fontSize: 12, display: "flex", alignItems: "center", gap: 8, flexWrap: "wrap",
                }}>
                  <span style={{
                    fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
                    letterSpacing: ".08em", textTransform: "uppercase",
                    color: "var(--calv-slate-65)",
                  }}>From this project →</span>
                  <PersonChip name="Pastor Reginald Booker" role="Partner" />
                  <PersonChip name="Jasmin Ortiz" role="Landlord" active />
                  <PersonChip name="Yvette Diaz" role="Homeowner" />
                  <PersonChip name="+ 15 more" muted />
                </div>

                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 110px", gap: 10, marginBottom: 10 }}>
                  <div><FieldLabel required>First name</FieldLabel><TI value="Jasmin" /></div>
                  <div><FieldLabel>Last name</FieldLabel><TI value="Ortiz" /></div>
                  <div><FieldLabel>Role</FieldLabel><Sel value="Landlord" /></div>
                </div>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1.4fr", gap: 10 }}>
                  <div><FieldLabel>Phone</FieldLabel><TI value="484-201-1199" iconRight={Ic.phone} /></div>
                  <div><FieldLabel>Email</FieldLabel><TI value="jortiz.realty@gmail.com" iconRight={Ic.mail} /></div>
                </div>
              </FormSection>

              <FormSection title="Address" hint="auto-populates subject if blank" dense>
                <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr 70px 90px", gap: 10 }}>
                  <div><FieldLabel>Street</FieldLabel><TI value="1843 Lehigh St." iconRight={Ic.pin} /></div>
                  <div><FieldLabel>City</FieldLabel><TI value="Allentown" /></div>
                  <div><FieldLabel>State</FieldLabel><Sel value="PA" /></div>
                  <div><FieldLabel>ZIP</FieldLabel><TI value="18103" /></div>
                </div>
              </FormSection>

              <FormSection title="Case notes" hint="186 words">
                <TA rows={8} value={`Reached Jasmin re: 1843 Lehigh. She confirmed tenant (J. Reyes) moved out 5/15 — unit is empty for inspection this week. She'd like to apply for the ABH rehab incentive for the kitchen + roof. I quoted her the F0531 rehab cap and walked through eligibility.

Sent over the application packet (en español). She'll have docs back by Friday. Also confirmed she'd be okay with PHFA HEMAP referral if the new tenant qualifies.

Need to: (1) flag this address for inspection coordinator, (2) hold a Block 7 slot for September close.`} />
              </FormSection>
            </div>

            {/* RIGHT — scoped fund codes & services */}
            <div>
              <FormSection
                title="Fund codes"
                hint="scoped to project · 2 of 2 available"
                style={{ borderTop: 0, paddingTop: 0 }}
                dense
              >
                <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                  <CheckChip
                    checked kind="teal"
                    label={(<span style={{ display: "flex", alignItems: "center", gap: 8 }}>
                      <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F0531</span>
                      <span>Allentown Better Homes</span>
                    </span>)}
                    sub={(<span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
                      <FundStatusDot status="warning" /><span style={{ fontSize: 10 }}>Expires in 84d</span>
                    </span>)}
                  />
                  <CheckChip
                    kind="teal"
                    label={(<span style={{ display: "flex", alignItems: "center", gap: 8 }}>
                      <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F4205</span>
                      <span>LC Urgent roof & HVAC</span>
                    </span>)}
                    sub={(<span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
                      <FundStatusDot status="soon" /><span style={{ fontSize: 10, color: "var(--calv-red)" }}>Expires in 7d</span>
                    </span>)}
                  />
                </div>

                {/* Warning callout because a fund is expiring */}
                <div style={{
                  marginTop: 10, padding: "8px 10px",
                  background: "var(--calv-red-15)",
                  borderLeft: "3px solid var(--calv-red)",
                  borderRadius: 3,
                  fontSize: 12, color: "var(--calv-slate)",
                  display: "flex", alignItems: "flex-start", gap: 8,
                }}>
                  <span style={{ color: "var(--calv-red)", flex: "0 0 auto", marginTop: 1 }}>{Ic.warn}</span>
                  <span><strong>F4205 expires in 7 days.</strong> Any work tagged to it must close by 5/30.</span>
                </div>

                <button style={{
                  marginTop: 8, display: "flex", alignItems: "center", gap: 8,
                  background: "transparent", border: "1px dashed var(--calv-slate-35)",
                  borderRadius: 4, padding: "6px 10px", cursor: "pointer",
                  fontFamily: "var(--font-body)", fontSize: 12, color: "var(--calv-slate-65)",
                  width: "100%",
                }}>
                  {Ic.plus} Use a fund code not on this project
                </button>
              </FormSection>

              <FormSection
                title="Program services"
                hint="3 suggested by project"
                dense
              >
                <div style={{ display: "flex", flexWrap: "wrap", gap: 6, marginBottom: 8 }}>
                  <CheckChip checked kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> First-Time Homebuyer Ed.</>)} />
                  <CheckChip kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> Pre-Closing Seminar</>)} />
                  <CheckChip kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> Pre-Purchase Counseling</>)} />
                </div>
                <div style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>
                  <a style={{ color: "var(--calv-teal)", textDecoration: "underline", cursor: "pointer" }}>+ search 17 other services</a>
                </div>
              </FormSection>

              <FormSection title="Tags" dense>
                <div style={{ display: "flex", flexWrap: "wrap", gap: 6, alignItems: "center" }}>
                  <Tag label="rehab in progress" removable kind="sand" />
                  <Tag label="landlord" removable kind="sand" />
                  <Tag label="spanish-speaking" removable kind="sand" />
                  <input placeholder="Type to search or create…" style={{
                    fontFamily: "var(--font-body)", fontSize: 12,
                    border: 0, padding: "4px 6px", outline: "none", flex: 1,
                    borderBottom: "1px dashed var(--calv-slate-35)",
                    background: "transparent", minWidth: 140,
                  }} />
                </div>
              </FormSection>
            </div>
          </div>
        </div>
      </div>
    </ModalChrome>
  );
}

function PersonChip({ name, role, active, muted }) {
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: 6,
      padding: "3px 8px", borderRadius: 999,
      background: active ? "var(--calv-teal)" : "#fff",
      color: active ? "#fff" : (muted ? "var(--calv-slate-65)" : "var(--calv-slate)"),
      border: active ? "1px solid var(--calv-teal)" : "1px solid var(--calv-slate-15)",
      fontSize: 11, cursor: "pointer",
    }}>
      <span style={{
        width: 16, height: 16, borderRadius: 999,
        background: active ? "rgba(255,255,255,.2)" : "var(--calv-slate-15)",
        color: active ? "#fff" : "var(--calv-slate-65)",
        display: "inline-flex", alignItems: "center", justifyContent: "center",
        fontSize: 9, fontWeight: 700,
      }}>{name === "+ 15 more" ? "+" : name.split(" ").map(s => s[0]).join("").slice(0, 2)}</span>
      <span style={{ fontWeight: active ? 600 : 400 }}>{name}</span>
      {role && <span style={{ opacity: .7, fontSize: 10 }}>· {role}</span>}
    </span>
  );
}

// ─────────────────────────────────────────────────────────────
// D2_Picker — the project picker overlay (⌘P)
// Shown as its own artboard for the project-first direction
// ─────────────────────────────────────────────────────────────
function D2Picker() {
  return (
    <div style={{
      width: "100%", height: "100%", minHeight: "100%",
      background: "rgba(40,42,44,.55)",
      display: "flex", alignItems: "flex-start", justifyContent: "center",
      padding: "60px 0",
    }}>
      <div style={{
        width: 540, background: "#fff",
        borderRadius: 6, boxShadow: "var(--shadow-lg)",
        overflow: "hidden",
      }}>
        <div style={{
          padding: "12px 16px", borderBottom: "1px solid var(--calv-slate-15)",
          display: "flex", alignItems: "center", gap: 10,
        }}>
          <span style={{ color: "var(--calv-slate-65)" }}>{Ic.search}</span>
          <input
            autoFocus defaultValue="all"
            style={{
              flex: 1, border: 0, outline: "none",
              fontFamily: "var(--font-body)", fontSize: 15,
              color: "var(--calv-slate)",
            }}
          />
          <Kbd>esc</Kbd>
        </div>
        <div style={{ padding: 8 }}>
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate-65)", padding: "6px 8px",
          }}>Anchor</div>
          <PickerRow
            kind="none"
            primary="General intake"
            sub="No project · fund codes & services hidden"
          />
          <div style={{
            fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate-65)", padding: "10px 8px 6px",
          }}>Active projects · 5</div>
          <PickerRow
            kind="project" code="F0531" primary="Allentown Better Homes · Block 7"
            sub="18 contacts · 2 funds (1 expiring 84d) · last entry 2d ago"
            active
          />
          <PickerRow
            kind="project" code="F0530" primary="136 S. 8th St. Rehab"
            sub="4 contacts · 2 funds · last entry 5d ago"
          />
          <PickerRow
            kind="project" code="PHARE-26" primary="PHARE Healthy Homes · Walnutport"
            sub="12 contacts · 2 funds (1 expiring 23d ⚠) · last entry today"
          />
          <PickerRow
            kind="project" code="F4205" primary="LC Urgent HVAC · 519 Turner"
            sub="6 contacts · 3 funds (1 expired 7d ⚠) · CLOSING"
            warn
          />
          <PickerRow
            kind="project" code="SSS-26" primary="Sixth Street Shelter · Op Support FY26"
            sub="9 contacts · 1 fund · last entry 1d ago"
          />
        </div>
        <div style={{
          padding: "8px 16px", borderTop: "1px solid var(--calv-slate-15)",
          background: "var(--calv-sand-15)",
          display: "flex", alignItems: "center", justifyContent: "space-between",
          fontSize: 11, color: "var(--calv-slate-65)",
        }}>
          <span style={{ display: "flex", alignItems: "center", gap: 4 }}>
            <Kbd>↑</Kbd><Kbd>↓</Kbd> nav · <Kbd>⏎</Kbd> select · <Kbd>N</Kbd> new project
          </span>
          <a style={{ color: "var(--calv-teal)", cursor: "pointer", textDecoration: "underline" }}>
            Browse all 17 active projects →
          </a>
        </div>
      </div>
    </div>
  );
}

function PickerRow({ kind, code, primary, sub, active, warn }) {
  return (
    <div style={{
      display: "flex", alignItems: "center", gap: 10,
      padding: "8px 8px", borderRadius: 4,
      background: active ? "var(--calv-teal-15)" : "transparent",
      cursor: "pointer",
    }}>
      <div style={{
        width: 36, padding: "2px 0", textAlign: "center",
        fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 10,
        letterSpacing: ".06em", textTransform: "uppercase",
        background: kind === "none" ? "var(--calv-slate-15)" : (warn ? "var(--calv-red-15)" : "var(--calv-teal-15)"),
        color: kind === "none" ? "var(--calv-slate-65)" : (warn ? "var(--calv-red)" : "var(--calv-teal)"),
        borderRadius: 3,
      }}>
        {kind === "none" ? "—" : code}
      </div>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ fontSize: 14, color: "var(--calv-slate)", fontWeight: active ? 600 : 400 }}>{primary}</div>
        <div style={{ fontSize: 11, color: "var(--calv-slate-65)", marginTop: 2 }}>{sub}</div>
      </div>
      {active && <Kbd>⏎</Kbd>}
    </div>
  );
}

Object.assign(window, { D2NoProject, D2WithProject, D2Picker, PersonChip });
