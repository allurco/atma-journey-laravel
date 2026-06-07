# PRD-16 · Clinical Signals — Exam AI & Doctor Approval

**Phase:** v1.1 · **Depends on:** PRD-14 (the `RaiseSinal` seam, the feed + clinical-critical lane,
lifecycle), PRD-6 (Clinical/EHR — exam documents exist to analyze), the agnostic AI layer from PRD-15 ·
**Consumed by:** the same front-desk feed.
**Design:** `../superpowers/specs/2026-06-07-frontdesk-copilot-sinal-design.md` · **ADR:** 0001 (clinical
hard-gated to the top).

> Heaviest of the trilogy, built last. **This PRD gets its own design pass** (exam-upload UX, the AI
> analysis contract, the doctor-approval queue) before it is built — the section below is the shape, not
> the final detail.

## Why this PRD

A doctor reading an exam may spot something that needs a follow-up the front desk should act on — but the
front desk must never see a diagnosis, and an AI must never reach the patient or the front desk on its own.
This PRD adds the **clinical producer**: AI analyzes an uploaded exam → **signals the doctor** → the doctor
reviews & **approves** → the approved finding is transferred to the front desk as a `clinical` Sinal in the
existing critical lane. It is purely additive to the consumer built in PRD-14 — the feed already knows how
to rank and work a `clinical` Sinal.

## Scope

**In:** exam-document AI analysis producing a **draft finding for the doctor** (never the front desk); a
**doctor approval queue/gate** (approve / edit / reject); on approval, `RaiseSinal(clinical, …)` into the
feed's critical lane with a staff-only **`reason`** and a **logistics-only** `draft_message`; outcome
resolution (the patient enters the pipeline / books the requested visit — recall that a clinical visit
implies a budget that lives in the pipeline, ADR-0002).

**Out:** the exam-parsing/extraction pipeline itself if owned by PRD-10/PRD-6 (this PRD *consumes* exam
documents, it doesn't re-build upload/storage); autonomous AI-to-front-desk anything (forbidden); the
"não contatar" opt-out (deferred).

## Key decisions

- **The doctor is the sole gate.** The AI's exam analysis is a **suggestion to a clinician**, never an
  autonomous Sinal. No clinical Sinal exists without an explicit doctor approval. There is **no manual
  intake** — the flow is always AI → doctor → front desk.
- **Clinical detail never reaches the patient draft.** The clinical drafter is **structurally starved** of
  clinical context: it receives only patient first name + "the doctor would like a return visit" + clinic
  name, so the worst it can emit is logistics. The doctor's finding stays in the staff-only `reason`. This
  is a structural guarantee, not a prompt instruction.
- **Clinical Sinais are hard-gated to the top** (ADR-0001): `tier ∈ {critica, alta}`, exempt from the
  active-care suppression (a health flag surfaces regardless of funnel position).
- **Resolution by outcome:** when the patient is brought into the pipeline for the requested visit (a card
  created → a budget built → approved), the clinical Sinal resolves via the same domain events.

## Design (shape — refined in this PRD's own design pass)

- **Doctor approval queue:** a doctor-facing list of AI findings awaiting review; approve (sets tier +
  staff `reason`) / edit / reject. Approval calls `RaiseSinal`.
- **Front-desk view:** the clinical Sinal appears in the critical lane with the **reason** (logistics
  framing for the desk) — never the clinical detail — and a logistics-only suggested message.

## Build slices (tracer bullets, TDD) — provisional, pending the design pass

1. **Doctor approval → clinical Sinal** — given an (AI- or test-) produced finding, the approval action
   raises a `clinical` Sinal (critical lane, staff `reason`, logistics draft). Reject raises nothing.
2. **AI exam analysis → doctor finding** — analyze an exam document into a draft finding *for the doctor*
   (behind the agnostic AI layer, mocked in tests); never reaches the front desk pre-approval.
3. **Resolution** — clinical Sinal resolves when the patient enters the pipeline for the requested visit.

## Testing

- **Approval (feature):** approve → `clinical` Sinal in the critical lane with staff `reason`; reject →
  nothing; tier always `critica`/`alta`; exempt from active-care suppression.
- **Drafter (critical):** the clinical drafter is **never given clinical context** — assert the draft
  contains no finding/diagnosis text; only logistics.
- **AI analysis:** mocked; output goes only to the doctor queue, never to the feed pre-approval.
- **Resolution:** pipeline entry for the requested visit resolves the Sinal.
- **Browser pass** for the doctor queue and the critical-lane rendering.

## Deferred

Exam upload/extraction infra (PRD-6/PRD-10) · autonomous AI delivery (forbidden by design) · "não
contatar" opt-out · richer clinical-finding taxonomy. **This PRD's detailed design is authored before build.**
