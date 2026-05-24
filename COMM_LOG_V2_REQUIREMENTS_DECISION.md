# Application Requirements — one decision before we build

**For:** Kaitlyn (+ David for FYI)
**From:** David / engineering
**Date:** 2026-05-24
**What we need:** a 15-minute call (or written reply) on the question on the next page.

---

## What this is about

The Comm Log v2 redesign introduces a checklist of **application requirements** — the documents, forms, data points, and verifications you need from a client before you can promote an intake into a real project. The mockup calls it the "Requirements Panel," and it's the thing that decides whether the **Convert to Project** button is enabled.

Two months ago we built something **closely related but not identical**: a per-fund-code "intake requirements" feature you haven't seen yet because it never got a UI. The model and the backend wiring are done; the screen to use it from is not.

Now the redesign asks for what is arguably the same checklist, but anchored on **program services** instead of fund codes. Before we write the migration, we need you to tell us whether these are **one thing** or **two things**.

---

## The question, in your terms

When you sit down with a new applicant and start working through "what do I need from this person before I can move them forward," do you think of that as:

**(A)** **One checklist** — a single, deduplicated list of "stuff I need from this client," and the source of each item (a fund code rule, a program service rule, or me adding it on the fly) is just metadata.

**(B)** **Two checklists** — eligibility paperwork (fund-code-driven, e.g. "can we even serve this person under this funding stream?") and application paperwork (service-driven, e.g. "what do they need to complete the actual housing case?"). You want to see them separately because they answer different questions.

**(C)** **Something else** — and we should talk before assuming either.

---

## What each answer costs

| | If you say (A) "one checklist" | If you say (B) "two checklists" |
|---|---|---|
| **What you see in the UI** | One Requirements panel per Comm Log entry. Each row shows a small chip (e.g. "from PHFA HEMAP" or "from Insulation fund") so you can tell where it came from. | Two stacked panels — "Eligibility" (from fund codes) and "Application" (from services). Each gates the Convert button independently or together (we'd confirm). |
| **What we build** | Extend the table we already shipped. Both fund-code-driven and service-driven items live in one place, deduped by what they actually are ("Photo ID" is just "Photo ID," no matter who asked for it). | Keep the fund-code system as-is. Build a second, separate system for service-driven application paperwork. |
| **Effort impact** | +3 days of migration work; preserves what we shipped. | +1.5 weeks (we build a parallel system); preserves what we shipped but you'll have two panels to maintain. |
| **Risk** | If "eligibility" and "application" really are different concepts, mashing them into one list will feel muddy later. | Two panels mean two places to look. Easy to lose track. |

---

## My read (not the decision)

If you naturally say "Photo ID" once when an applicant walks in — not "Photo ID for HEMAP eligibility" plus "Photo ID for the rental case" — then it's one checklist (**A**). If you have to track eligibility separately because the fund stream owner audits it on its own, then it's two (**B**). I'd guess (A) is closer to how you actually work, but you'll know in 10 seconds whether that's right.

---

## What we also need from you, once the above is settled

Q13 in the spec: **the actual list of requirements per program service**. The mockup uses fake placeholders (Photo ID, Hardship Statement, PHFA HEMAP eligibility). Before we run the seed migration, we need your real list — what do you actually require from a client for each program service? A spreadsheet or a 30-minute screen-share works. This blocks the rest of the build.

---

## Reply with

- **Your A / B / C answer** (and any nuance)
- **Best time this week for the Q13 list session** — 30 min, screen-share or in-person
