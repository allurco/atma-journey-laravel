---
status: accepted
---

# A Sinal is the pipeline's on-ramp; it resolves by outcome, not by effort

A `Sinal` exists only to pull a *forgotten* patient back into the Pipeline. Revenue producers (recall / no-show / quote follow-up) check **active care** before raising — defined as a pipeline card in stages `primeiro_contato` through `retorno` — and **suppress** the Sinal if the patient is already there, because the Pipeline is the system's record of "being worked right now." A Sinal resolves when the real outcome happens (the patient re-enters the pipeline / books / approves a budget — observed via the domain events we already emit), **not** when the attendant clicks WhatsApp. Acting only moves it `pendente → em_andamento`.

This is surprising in two ways a future reader will trip on, so it's worth recording: (1) producers query pipeline *stage*, which looks like coupling but is the deduplication mechanism — the Pipeline replaces a per-(patient,type) Sinal counter; (2) `concluido` is deliberately **excluded** from active care even though its patient `status` is `active`, because a finished treatment is exactly the drift-risk patient recall must catch. The patient `status` enum is a summary of funnel position, never the suppression gate.

## Considered options

- **Resolve-by-click + a standalone one-Sinal-per-(patient,type) dedup counter.** Rejected: optimizes for activity (messages sent) over outcome (patients recovered) — how these systems rot into spam — and duplicates state the Pipeline already holds.
- **Gate on the patient `status` enum.** Rejected: `status = active` includes `concluido` (finished), which would suppress the recall the product exists to send.
- **Pipeline-presence gate + resolve-by-outcome (chosen).** No new dedup state; resolution rides existing domain events; the "no patient is forgotten" promise is enforced by the same record that defines "being worked."

## Consequences

- Producers depend on a single `inActiveCare()` predicate (card in `primeiro_contato`…`retorno`). Changing the stage set changes who gets Sinais.
- Resolution is event-driven: listeners on `AppointmentScheduled` / `BudgetApproved` / pipeline re-entry close matching open Sinais. No manual "done" tick is required.
- Clinical Sinais are exempt from the active-care suppression (a health flag surfaces regardless of funnel position) — they ride a different axis.
