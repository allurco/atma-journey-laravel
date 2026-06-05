# ATMA Journey — Domain Context

Ubiquitous language for the project. Use these terms verbatim in code, tests, issues, and docs.
UI language is **Brazilian Portuguese**; several domain terms stay in Portuguese (they are canonical).
Full architecture: `docs/superpowers/specs/2026-06-05-atma-journey-program-design.md`.

## What the product is

A multitenant SaaS for health clinics that digitizes the *Método Atma Soma* patient journey.
Core promise: **no patient is forgotten at any stage of the funnel** (*nenhum paciente é esquecido*).
It unifies retention/CRM, scheduling, clinical records, and finance on one timeline.

## Bounded contexts

- **Tenancy & Identity** — Clinic (tenant), User (admin/staff), Doctor, Specialty.
- **Patients** — the hub entity every other context references.
- **Pipeline (CRM)** — the retention engine; the patient journey as a Kanban. *The differentiator.*
- **Scheduling** — Appointments and the weekly calendar.
- **Clinical / Prontuário (EHR)** — clinical notes, prescriptions, anamnesis, documents, exam results.
- **Financial** — procedures (catalog), orçamentos (budgets), transactions.
- **Communication** — internal/patient chat.

## Glossary

| Term | Meaning |
| --- | --- |
| **Clinic / Tenant** | A clinic account. Database-per-tenant; one clinic per user. |
| **Patient (Paciente)** | A person in the clinic's care; status is `active`, `lead`, or `inactive`. |
| **Pipeline Card** | One patient's position in the retention funnel. One active card per patient. |
| **Pipeline stage** | `primeiro_contato`, `avaliacao`, `em_analise`, `orcamento_enviado`, `negociando`, `orcamento_aceito`, `agendado`, `retorno`, `concluido`; plus `desistentes` (dropouts). |
| **Timeline Event** | An entry in a patient's activity history (whatsapp, appointment, missed-call, email, completed). |
| **Appointment (Agendamento)** | A scheduled visit: `scheduled → checked-in → completed / cancelled / no-show`. |
| **Doctor (Profissional)** | A practitioner with a CRM number and one or more specialties. |
| **Specialty (Especialidade)** | A medical specialty a doctor practices. |
| **Procedure (Procedimento)** | A billable service in the catalog (base price, duration). |
| **Orçamento (Budget)** | A quote: `draft → sent → approved → completed`. Drives the pipeline and converts to a transaction. |
| **Transaction (Transação)** | A payment record: `pending → paid`; method `credit/debit/cash/pix`. |
| **Prontuário (EHR)** | The clinical record: clinical notes, prescriptions, anamnesis, documents. |
| **Anamnesis (Anamnese)** | The patient's medical history (one per patient). |
| **Exam Result** | AI-parsed lab result extracted from an uploaded document (Phase 2). |
| **Lead** | An unconverted prospect, often ingested via webhook; a patient with status `lead`. |
| **LTV** | A patient's accumulated lifetime value (revenue). |

## Roles

`admin` and `staff` (enum on the tenant `users` table). Finer RBAC (reception / medical / financial)
is designed-for but **deferred** — don't build it yet.

## Key invariants & automation

- A patient has **at most one active pipeline card**.
- Pipeline stage drives patient status: `orcamento_aceito/agendado/retorno/concluido` → `active`;
  `desistentes` → `inactive`; otherwise `lead`.
- **Orçamento approved/completed** advances the linked pipeline card.
- **Appointment cancelled** moves the patient's card to `desistentes`.
- Automation is implemented with **domain Events + Listeners**, not inline side-effects.
