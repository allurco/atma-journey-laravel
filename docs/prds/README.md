# ATMA Journey — PRD Collection

This directory holds one PRD per bounded context. Each is built **TDD-first** as its own
spec → plan → implementation cycle. The shared architecture they all stand on is in
[`../superpowers/specs/2026-06-05-atma-journey-program-design.md`](../superpowers/specs/2026-06-05-atma-journey-program-design.md).

PRDs are written **just-in-time** — the detailed spec for a PRD is authored right before it's built.

## The collection

| # | PRD | Phase | Status | Spec |
|---|---|---|---|---|
| 0 | Platform Foundation | MVP | ✅ done | `00-platform-foundation.md` |
| 1 | Settings & Identity | MVP | ✅ done | `01-settings-identity.md` |
| 2 | Patients | MVP | ✅ done | `02-patients.md` |
| 3 | Pipeline CRM ★ | MVP | ✅ done | `03-pipeline-crm.md` |
| 4 | Scheduling | MVP | ✅ done | `04-scheduling.md` |
| 5 | Financial | MVP | ✅ done | `05-financial.md` |
| 5.5 | Hardening & Value ⚙ | MVP+ | 🚧 in progress | `05.5-hardening-and-value.md` |
| 6 | Clinical / EHR | v1.1 | ⬜ planned | `06-clinical-ehr.md` |
| 7 | Communication | v1.1 | ⬜ planned | `07-communication.md` |
| 8 | Lead Ingestion | v1.1 | ⬜ planned | `08-lead-ingestion.md` |
| 9 | ~~Dashboard & Analytics~~ — **retired** (split: retention analytics → 15, recall UI → 14, ops reports → Relatórios) | v1.1 | ⚠️ superseded | `09-dashboard-analytics.md` |
| 10 | AI Exam **Extraction Engine** (exam doc → structured `ExamFinding`; feeds 16/17/18) | Phase 2 | ⬜ backlog (reframed) | `10-ai-exam-parsing.md` |
| 11 | Messaging **Delivery + transactional reminders** (drafting → 15; human-sent) | Phase 2 | ⬜ backlog (rescoped) | `11-messaging-automation.md` |
| 12 | Payment Gateway | Phase 2 | ⬜ backlog | `12-payment-gateway.md` |
| 13 | Billing & Subscriptions | Phase 2 | ⬜ backlog | `13-billing-subscriptions.md` |
| 14 | Front-desk Copilot — Funnel & Recall ✦ | v1.1 | 🔜 next | `14-frontdesk-copilot-funnel.md` |
| 15 | Retention Core — No-show & Quote Signals ✦ | v1.1 | ⬜ planned | `15-retention-core-signals.md` |
| 16 | Clinical Signals — Exam AI & Doctor Approval ✦ | v1.1 | ⬜ planned | `16-clinical-signals.md` |
| 17 | Patient Insights — doctor & front-desk glance panels | v1.1 | ⬜ planned | `17-patient-insights.md` |
| 18 | Patient Timeline — unified clinical history (evolves PRD-2) | v1.1 | ⬜ planned | `18-patient-timeline.md` |

**MVP = PRDs 0–5** (the sellable retention loop).

✦ The **Front-desk Copilot** trilogy (14–16) — the agentic retention layer. Shared design:
[`../superpowers/specs/2026-06-07-frontdesk-copilot-sinal-design.md`](../superpowers/specs/2026-06-07-frontdesk-copilot-sinal-design.md);
decisions in `../adr/0001`, `../adr/0002`. Built behind one `RaiseSinal` seam + one feed, in order 14 → 15 → 16.
They **absorb and refine** the earlier sketches. **Reconciliation (resolved 2026-06-09):**
- **9 · Dashboard & Analytics → retired.** The recall UI is PRD-14's feed; the retention analytics
  (reativação, valor associado) is PRD-15's owner dashboard; general operational reporting is the
  **Relatórios** cards. Nothing distinct remains — don't author a PRD-9 spec.
- **10 · AI Exam Parsing → kept, reframed as the *extraction engine*.** PRD-16 *consumes* exam infra and
  defers the pipeline here. PRD-10 turns an exam document into structured `ExamFinding`s (`source='ai'`),
  shared by **16** (clinical signal), **17** (insights) and **18** (timeline). Boundary: **10 extracts,
  16 decides-and-signals.** Sequence 10 before/with 16.
- **11 · Messaging Automation → rescoped.** PRD-15's `CopilotDrafter` owns the *suggested message*
  (human-edited, human-sent); the marketing-blast intent dies with the human-in-the-loop model. What
  survives: a **real delivery channel** (WhatsApp Business API / SMS / email — PRD-15 defers it) +
  **transactional reminders/confirmations**.

Legend: 🔜 next · 🚧 in progress · ✅ done · ⬜ planned · ✦ Copilot trilogy
