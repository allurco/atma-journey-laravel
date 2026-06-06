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
| 4 | Scheduling | MVP | 🚧 in progress | `04-scheduling.md` |
| 5 | Financial | MVP | ⬜ planned | `05-financial.md` |
| 6 | Clinical / EHR | v1.1 | ⬜ planned | `06-clinical-ehr.md` |
| 7 | Communication | v1.1 | ⬜ planned | `07-communication.md` |
| 8 | Lead Ingestion | v1.1 | ⬜ planned | `08-lead-ingestion.md` |
| 9 | Dashboard & Analytics | v1.1 | ⬜ planned | `09-dashboard-analytics.md` |
| 10 | AI Exam Parsing | Phase 2 | ⬜ backlog | `10-ai-exam-parsing.md` |
| 11 | Messaging Automation | Phase 2 | ⬜ backlog | `11-messaging-automation.md` |
| 12 | Payment Gateway | Phase 2 | ⬜ backlog | `12-payment-gateway.md` |
| 13 | Billing & Subscriptions | Phase 2 | ⬜ backlog | `13-billing-subscriptions.md` |

**MVP = PRDs 0–5** (the sellable retention loop).

Legend: 🔜 next · 🚧 in progress · ✅ done · ⬜ planned
