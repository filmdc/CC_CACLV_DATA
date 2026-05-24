// direction-1-power-form.jsx — Direction 1: The Dense Sheet.
// Single-page power form for Kaitlyn. Keyboard-first. Hierarchy by type, not by cards.

function D1Form({ mode = "primary" }) {
  // mode: "primary" | "error" | "fund-detail"
  const errs = mode === "error";

  // Pre-selected fund-code chips (for primary state)
  const checked = new Set(["F0621", "F0623"]);

  return (
    <ModalChrome
      title="Edit Communication"
      idLabel="COMM-7184"
      subtitle={(
        <span>
          <span style={{ color: "var(--calv-slate)" }}>Subject auto-set from address →</span>{" "}
          <span style={{ fontFamily: "var(--font-sub)", letterSpacing: ".04em", textTransform: "uppercase", fontWeight: 700, color: "var(--calv-slate)" }}>
            627 S Lincoln Ave · Walnutport
          </span>{" "}
          <a style={{ color: "var(--calv-teal)", textDecoration: "underline", marginLeft: 6, cursor: "pointer" }}>edit</a>
        </span>
      )}
      breadcrumb={<>Housing · <span style={{ color: "var(--calv-slate-35)" }}>›</span> Communication Log <span style={{ color: "var(--calv-slate-35)" }}>›</span> COMM-7184</>}
      width="100%"
      footer={(
        <>
          <div style={{ display: "flex", alignItems: "center", gap: 18, fontSize: 12, color: "var(--calv-slate-65)" }}>
            <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
              <span style={{ width: 7, height: 7, borderRadius: 999, background: errs ? "var(--calv-red)" : "var(--calv-sage)" }} />
              {errs ? "2 fields need attention" : "All required fields complete"}
            </span>
            <span>Autosaved 14s ago · v3</span>
            <span style={{ display: "flex", alignItems: "center", gap: 4 }}>
              <Kbd>Tab</Kbd> next · <Kbd>Shift</Kbd>+<Kbd>Tab</Kbd> back · <Kbd>⌘K</Kbd> jump
            </span>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn kind="ghost" size="md">Cancel <Kbd>Esc</Kbd></Btn>
            <Btn kind="quiet" size="md">Save & New <Kbd>⌘⏎N</Kbd></Btn>
            <Btn kind="primary" size="md">Update entry <Kbd>⌘⏎</Kbd></Btn>
          </div>
        </>
      )}
    >
      <div style={{ padding: "16px 22px 18px" }}>

        {/* ─── Row 1: the 5 meta fields in one keyboard-fluent line ─── */}
        <div style={{
          display: "grid",
          gridTemplateColumns: "150px 170px 130px 170px 1fr",
          gap: 10, marginBottom: 14, alignItems: "end",
        }}>
          <div>
            <FieldLabel required>Date</FieldLabel>
            <TI value={errs ? "04/32/2026" : "05/23/2026"} error={errs} iconRight={Ic.cal} />
            {errs && <ErrTip>Invalid date</ErrTip>}
          </div>
          <div>
            <FieldLabel required>Type</FieldLabel>
            <Sel value={errs ? null : "Phone Call"} placeholder="Select…" error={errs} />
            {errs && <ErrTip>Required</ErrTip>}
          </div>
          <div>
            <FieldLabel required>Direction</FieldLabel>
            <Sel value="Outgoing" />
          </div>
          <div>
            <FieldLabel required>Category</FieldLabel>
            <Sel value="Case work" />
          </div>
          <div>
            <FieldLabel hint="(scopes fund codes & services below)">
              Related to project
            </FieldLabel>
            <div style={{
              display: "flex", alignItems: "center", gap: 8,
              padding: "8px 10px", borderRadius: 4,
              background: "var(--calv-teal-15)",
              border: "1px solid var(--calv-teal-35)",
              fontSize: 13,
            }}>
              <span style={{
                fontFamily: "var(--font-sub)", fontWeight: 700, fontSize: 10,
                letterSpacing: ".08em", textTransform: "uppercase",
                color: "var(--calv-teal)", background: "#fff",
                padding: "2px 6px", borderRadius: 3,
              }}>PHARE-26</span>
              <span style={{ color: "var(--calv-slate)" }}>PHARE Healthy Homes · Walnutport</span>
              <span style={{ flex: 1 }} />
              <a style={{ fontSize: 11, color: "var(--calv-teal)", cursor: "pointer", textDecoration: "underline" }}>change</a>
            </div>
          </div>
        </div>

        {/* ─── Two-column body ─── */}
        <div style={{ display: "grid", gridTemplateColumns: "1.55fr 1fr", gap: 22 }}>

          {/* LEFT — contact & notes */}
          <div>
            <FormSection
              title="Contact"
              hint="Press ⌘K to pull from project roster"
              dense
              style={{ borderTop: "0", paddingTop: 0 }}
              right={(
                <a style={{ fontSize: 11, color: "var(--calv-teal)", cursor: "pointer", textDecoration: "underline" }}>
                  Pull from project (3) ↓
                </a>
              )}
            >
              <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1.4fr 1fr", gap: 10, marginBottom: 10 }}>
                <div>
                  <FieldLabel required>First name</FieldLabel>
                  <TI value={errs ? "" : "Maria"} placeholder={errs ? "Required" : ""} error={errs} />
                  {errs && <ErrTip>Required</ErrTip>}
                </div>
                <div>
                  <FieldLabel>Last name</FieldLabel>
                  <TI value="Pimentel-Fernandez" />
                </div>
                <div>
                  <FieldLabel>Role</FieldLabel>
                  <Sel value="Homeowner" />
                </div>
              </div>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1.4fr", gap: 10 }}>
                <div>
                  <FieldLabel>Phone</FieldLabel>
                  <TI value="484-522-4989" iconRight={Ic.phone} />
                </div>
                <div>
                  <FieldLabel>Email</FieldLabel>
                  <TI value="yosky2227@gmail.com" iconRight={Ic.mail} />
                </div>
              </div>
            </FormSection>

            <FormSection
              title="Address"
              hint="Sets the entry subject if blank"
              dense
              right={<a style={{ fontSize: 11, color: "var(--calv-teal)", cursor: "pointer" }}>Copy from contact ↓</a>}
            >
              <div style={{ display: "grid", gridTemplateColumns: "2fr 1.2fr 70px 100px", gap: 10 }}>
                <div>
                  <FieldLabel>Street</FieldLabel>
                  <TI value="627 S Lincoln Ave." iconRight={Ic.pin} />
                </div>
                <div>
                  <FieldLabel>City</FieldLabel>
                  <TI value="Walnutport" />
                </div>
                <div>
                  <FieldLabel>State</FieldLabel>
                  <Sel value="PA" />
                </div>
                <div>
                  <FieldLabel>ZIP</FieldLabel>
                  <TI value="18088" />
                </div>
              </div>
            </FormSection>

            <FormSection
              title="Case notes"
              hint="The body of the entry. Markdown, lists, paragraphs all fine."
              right={<span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>312 words · ⌘⇧K to format</span>}
            >
              <TA
                rows={9}
                value={`Maria called back regarding her 4 kids' lead levels. Two younger (ages 9 and 10) have elevated BLLs — she has the pediatrician's letter from St. Luke's dated 5/14 and will fax it.

Income ~$47,000, two older kids (15, 19), no other adults in HH. Significant deteriorating paint on porch + window sills (south + east). Behavior changes in the 10-year-old since birth; she connects this to the housing. No prior remediation in the unit.

Confirmed eligible for PHARE-26 Healthy Homes assessment. I have a tentative inspector slot Tues 5/26 11:00am — she will confirm by EOD Monday. Asked her to keep kids off the porch in the meantime.

Next step: hold inspector slot; follow up Mon by 4pm; if no answer, call Sandra (sister) at 610-555-0188 per Maria's request.`}
              />
            </FormSection>
          </div>

          {/* RIGHT — project context, fund codes, services, tags */}
          <div>
            {/* Project context block — only because Related-to-Project is set */}
            <div style={{
              background: "var(--calv-sand-15)",
              border: "1px solid var(--calv-sand-35)",
              borderRadius: 4, padding: "12px 14px", marginBottom: 14,
            }}>
              <div style={{
                fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
                letterSpacing: ".1em", textTransform: "uppercase",
                color: "var(--calv-slate-65)", marginBottom: 6,
              }}>Project context</div>
              <div style={{ fontFamily: "var(--font-h1)", fontSize: 18, letterSpacing: ".02em", textTransform: "uppercase", color: "var(--calv-slate)", lineHeight: 1.1 }}>
                PHARE Healthy Homes · Walnutport
              </div>
              <div style={{ fontSize: 12, color: "var(--calv-slate-65)", marginTop: 4, display: "flex", gap: 12 }}>
                <span><strong style={{ color: "var(--calv-slate)" }}>12</strong> contacts</span>
                <span><strong style={{ color: "var(--calv-slate)" }}>2</strong> fund codes</span>
                <span><strong style={{ color: "var(--calv-slate)" }}>34</strong> prior entries</span>
              </div>
            </div>

            <FormSection
              title="Fund codes"
              hint="Scoped to project · 2 of 8"
              dense
              style={{ borderTop: 0, paddingTop: 0 }}
              right={<a style={{ fontSize: 11, color: "var(--calv-teal)", cursor: "pointer" }}>show all 8 ↓</a>}
            >
              <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                {FUND_CODES.filter(f => ["F0621", "F0623"].includes(f.code)).map(f => {
                  const status = fundExpiryStatus(f);
                  return (
                    <CheckChip
                      key={f.code}
                      checked={checked.has(f.code)}
                      kind="teal"
                      label={(
                        <span style={{ display: "flex", alignItems: "center", gap: 8 }}>
                          <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>{f.code}</span>
                          <span>{f.name}</span>
                        </span>
                      )}
                      sub={(
                        <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
                          <FundStatusDot status={status.kind} />
                          <span style={{ fontSize: 10 }}>{status.label}</span>
                        </span>
                      )}
                    />
                  );
                })}
                {/* Other (out-of-project) row — collapsed */}
                <button style={{
                  display: "flex", alignItems: "center", gap: 8,
                  background: "transparent", border: "1px dashed var(--calv-slate-35)",
                  borderRadius: 4, padding: "6px 10px", cursor: "pointer",
                  fontFamily: "var(--font-body)", fontSize: 12, color: "var(--calv-slate-65)",
                }}>
                  {Ic.plus} Add a fund code not on this project (6 hidden)
                </button>
              </div>
            </FormSection>

            <FormSection
              title="Program services"
              hint="Project suggests 3 · pick any"
              dense
              right={(
                <div style={{ display: "flex", alignItems: "center", gap: 6, fontSize: 11 }}>
                  <Tag label="HCP" kind="teal" />
                  <Tag label="SSS" kind="sage" />
                  <Tag label="CAH" kind="amber" />
                </div>
              )}
            >
              <div style={{ display: "flex", flexWrap: "wrap", gap: 6, marginBottom: 8 }}>
                {/* Project-suggested */}
                <CheckChip checked kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> PHFA HEMAP</>)} />
                <CheckChip checked kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> Rental Housing</>)} />
                <CheckChip kind="teal" label={(<><Tag label="HCP" kind="teal" style={{ marginRight: 4 }} /> Housing Counseling</>)} />
              </div>
              <div style={{ fontSize: 11, color: "var(--calv-slate-65)", marginBottom: 6 }}>Other services</div>
              <input placeholder="Search 20 program services… (try 'lead' or 'fore')" style={{
                fontFamily: "var(--font-body)", fontSize: 13,
                border: "1px solid var(--calv-slate-35)", borderRadius: 4,
                padding: "7px 10px", width: "100%", boxSizing: "border-box",
                color: "var(--calv-slate-65)",
              }} />
            </FormSection>

            <FormSection title="Tags" dense>
              <div style={{ display: "flex", flexWrap: "wrap", gap: 6, alignItems: "center" }}>
                <Tag label="lead paint" removable kind="sand" />
                <Tag label="callback needed" removable kind="sand" />
                <Tag label="spanish-speaking" removable kind="sand" />
                <input placeholder="Type to search or create…" style={{
                  fontFamily: "var(--font-body)", fontSize: 12,
                  border: 0, padding: "4px 6px", outline: "none",
                  minWidth: 160, flex: 1,
                  borderBottom: "1px dashed var(--calv-slate-35)",
                  background: "transparent",
                }} />
              </div>
            </FormSection>
          </div>
        </div>
      </div>
    </ModalChrome>
  );
}

function ErrTip({ children }) {
  return (
    <div style={{
      display: "flex", alignItems: "center", gap: 5,
      fontSize: 11, color: "var(--calv-red)", marginTop: 4,
    }}>
      {Ic.warn} {children}
    </div>
  );
}

// ─────────────────────────────────────────────────────────────
// Fund-code detail callout (focused state for the right rail)
// ─────────────────────────────────────────────────────────────
function D1FundDetail() {
  return (
    <div style={{ padding: 28, background: "var(--calv-sand-15)", minHeight: "100%" }}>
      <div style={{
        fontFamily: "var(--font-sub)", fontSize: 10, fontWeight: 700,
        letterSpacing: ".1em", textTransform: "uppercase",
        color: "var(--calv-slate-65)", marginBottom: 6,
      }}>Detail · fund-code panel</div>
      <h3 style={{
        fontFamily: "var(--font-h1)", fontSize: 26, letterSpacing: ".02em",
        textTransform: "uppercase", color: "var(--calv-slate)", margin: "0 0 14px", lineHeight: 1.05,
      }}>Surface expiry as a glanceable dot, not a field.</h3>
      <p style={{ fontSize: 13, color: "var(--calv-slate-65)", margin: "0 0 20px", maxWidth: 540, lineHeight: 1.55 }}>
        Three states are encoded in one position: color dot + days-left text. Kaitlyn can scan a list
        of 20 codes and triage in seconds. Out-of-project codes are hidden behind a single dashed
        "Add" row — they're available but don't compete for attention.
      </p>

      <div style={{
        background: "#fff", border: "1px solid var(--calv-slate-15)",
        borderRadius: 4, padding: 18, maxWidth: 520,
      }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 10 }}>
          <h4 style={{
            fontFamily: "var(--font-sub)", fontSize: 11, fontWeight: 700,
            letterSpacing: ".1em", textTransform: "uppercase",
            color: "var(--calv-slate)", margin: 0,
          }}>Fund codes</h4>
          <span style={{ fontSize: 11, color: "var(--calv-slate-65)" }}>scoped to PHARE-26 · 2 of 8 shown</span>
        </div>
        <div style={{ display: "flex", flexDirection: "column", gap: 6, marginBottom: 14 }}>
          <CheckChip
            checked kind="teal"
            label={(<span style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F0621</span>
              <span>PHARE Healthy Homes</span>
            </span>)}
            sub={(<span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="soon" /><span style={{ fontSize: 10 }}>Expires in 23d</span>
            </span>)}
          />
          <CheckChip
            checked kind="teal"
            label={(<span style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600 }}>F0623</span>
              <span>Thriving Health (PHARE)</span>
            </span>)}
            sub={(<span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="perpetual" /><span style={{ fontSize: 10 }}>No expiry</span>
            </span>)}
          />
        </div>

        <div style={{ fontSize: 11, color: "var(--calv-slate-65)", marginBottom: 6 }}>Available outside this project</div>
        <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          <CheckChip
            kind="neutral"
            label={(<span style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600, opacity: .7 }}>F4205</span>
              <span style={{ opacity: .8 }}>LC Urgent roof & HVAC (CDBG-CV)</span>
            </span>)}
            sub={(<span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="soon" /><span style={{ fontSize: 10, color: "var(--calv-red)" }}>Expires in 7d</span>
            </span>)}
          />
          <CheckChip
            kind="neutral"
            label={(<span style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <span style={{ fontFamily: "ui-monospace, Menlo, monospace", fontSize: 11, fontWeight: 600, opacity: .7 }}>F0531</span>
              <span style={{ opacity: .8 }}>Allentown Better Homes</span>
            </span>)}
            sub={(<span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="warning" /><span style={{ fontSize: 10 }}>Expires in 84d</span>
            </span>)}
          />
        </div>

        <div style={{ marginTop: 18, paddingTop: 14, borderTop: "1px dashed var(--calv-slate-15)" }}>
          <div style={{ display: "flex", gap: 14, fontSize: 11, color: "var(--calv-slate-65)" }}>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="soon" /> ≤30d
            </span>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="warning" /> ≤90d
            </span>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="ok" /> Active
            </span>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <FundStatusDot status="perpetual" /> No expiry
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { D1Form, D1FundDetail });
