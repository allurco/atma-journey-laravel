# ATMA Journey — Domain Context

Ubiquitous language for the project. Use these terms verbatim in code, tests, issues, and docs.
UI language is **Brazilian Portuguese**; several domain terms stay in Portuguese (they are canonical).
Full architecture: `docs/superpowers/specs/2026-06-05-atma-journey-program-design.md`.

## What the product is

A multitenant SaaS for health clinics that digitizes the patient journey.
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
| **Sinal (Signal)** | A single prioritized front-desk action item: a patient who needs attention now, raised by a producer — retention rules (recall / no-show / quote follow-up) or a **clinical finding the doctor approved**. One entity. Carries a suggested action (a draft message + WhatsApp, or a plain task), a **price tag** (see below), and a status the attendant works to closure. |
| **Price tag (valor do sinal)** | A Sinal's estimated monetary weight, **typed by its source** — its meaning is not uniform: a recall's tag is the patient's *LTV* (past worth, a re-engagement proxy — labeled "valor do paciente," never "em jogo"); a no-show's is the missed procedure's price; a quote-follow-up's is the budget total; a clinical Sinal's is **absent** (it ranks on urgency, not money). It is an explicitly-labeled estimate. Price tags of **different types are never summed into one headline** — only comparable types aggregate, and always with a precise label. |
| **Priority tier (prioridade)** | The band that decides a Sinal's position in the feed: `critica → alta → media → baixa`. The **tier is set by the producer**, not computed from money. The feed orders by tier first; the price tag only breaks ties *within* a tier. Clinical Sinais from an approved doctor finding enter at `critica`/`alta` **by rule**, so a revenue Sinal can never outrank a health flag. The feed shows clinical-critical as its own labeled lane above the revenue-driven Sinais. |
| **Active care (em atendimento)** | A patient is *in active care* when they have a pipeline card in stages `primeiro_contato` through `retorno` — i.e. someone is actively moving them through the funnel right now. `concluido` (treatment finished) and `desistentes` (dropped), and *no card at all*, are **not** active care. This is the suppression gate: revenue Sinais (recall / no-show / quote) are **not raised for a patient already in active care** — the pipeline already owns that relationship. A Sinal is the **on-ramp back into the pipeline** for patients who are *not* in active care; the Sinal resolves when the patient (re)enters it. |
| **Sinal status** | `pendente` (raised, untouched) → `em_andamento` (attendant has acted — e.g. opened WhatsApp — but the outcome isn't in yet) → `resolvido` (the real outcome happened: patient re-entered the pipeline / booked / approved a budget — closed **by outcome event, not by the click**) · `dispensado` (attendant dismissed it) · `adiado` (snoozed until a date). Acting via click-to-WhatsApp moves `pendente → em_andamento` and logs a Timeline Event phrased as **action initiated** (*"Contato por WhatsApp iniciado"*) — never a delivery claim, since `wa.me` returns no delivery truth. |
| **Copilot (AI role)** | The AI **never contacts the patient and never sends anything**. It *orchestrates the need for contact* — raises the Sinal — and supplies the **reason to the front desk** (an internal explanation). The **front desk is always the human sender**: a trained attendant composes/sends the patient message. For clinical Sinais the doctor approves the finding first; the front desk then receives only the *reason to make contact*, never a patient-facing diagnosis. Any drafted message is a **suggestion the human edits and sends**, not an autonomous action. |
| **Taxa de reativação (reactivation rate)** | The defensible effectiveness metric for the Sinal funnel: `resolvido ÷ ever-actioned` — of the patients the front desk *acted on* (a Sinal that reached `em_andamento` at least once), how many returned (re-entered the pipeline / booked / approved within a window). Measures the clinic's outreach effectiveness; makes **no causal claim** about the AI. |
| **Valor associado a retornos (associated value)** | A revenue headline framed as **observation, not causation**: the summed value of patients who returned *after* a front-desk contact, within a window (e.g. 30 days) — *"R$ X em valor associado a retornos após contato."* Never *"recuperado pela IA"* — the figure claims an observed association (they returned after we acted), never that the Copilot caused the return. |
| **Front-desk dashboard (painel da recepção)** | The `staff` default home: the **live Sinal feed** itself — the ranked daily worklist with tiers/lanes, act buttons, status and snooze. The front desk's workspace. |
| **Analytics dashboard (painel do dono)** | The `admin` default home: the owner's read-mostly business-health view — taxa de reativação, valor associado a retornos, pipeline value, no-shows. No act buttons. Both dashboards are reachable by both roles; the role only sets the **default landing**. |

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
