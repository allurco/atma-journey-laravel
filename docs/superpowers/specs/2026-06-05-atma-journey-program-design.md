# ATMA Journey — Laravel Migration: Program Design

**Status:** Approved (program level) · **Date:** 2026-06-05
**Scope:** Migration of the TypeScript/React + Firebase app (`atma-journey-ts`) to Laravel 13.
This is the authoritative architectural reference. Individual feature PRDs live in `docs/prds/`.

---

## 1. What ATMA Journey is

A **multitenant SaaS for health clinics** that digitizes the *Método Atma Soma* patient journey.
Its core promise: **no patient is forgotten at any stage of the funnel**. It unifies CRM/retention,
scheduling, clinical records (prontuário), and finance into one timeline-based product.

The differentiator is the **Pipeline / retention engine**: a Kanban of the patient journey from first
contact to post-treatment, with automation that keeps the pipeline, patient status, scheduling, and
billing in sync.

Source of truth for the domain is the existing TS app (`../atma-journey-ts`), which is cleanly layered
(Components → Hooks → Services → Repositories). We port **business rules**, not infrastructure.

---

## 2. Foundational decisions (locked)

| Area | Decision | Rationale |
|---|---|---|
| **Frontend** | Pure **Livewire 4 + Alpine** on the **Livewire Starter Kit**. Use **free Flux (MIT)** for the generic shell, layout, and forms (button, input, modal, dropdown, navbar, card, table…) — keep the starter kit's existing auth/settings. **Never Flux Pro** (no license). | Reuses working, tested scaffolding; free Flux covers generic CRUD at zero cost. |
| **Signature interactive UI** | Build the **Pro-gated** components ourselves as in-house `x-ui.*` Blade + Alpine components, styled to match Flux's tokens: **Kanban** (Pipeline, via SortableJS), **calendar + date/time pickers** (Scheduling), **charts** (Dashboard), **combobox/searchable-select**, **file upload**, **tabs**. No React. | These are exactly our differentiating features; we'd hand-build them regardless, so we avoid the Pro license. |
| **Multitenancy** | **`stancl/tenancy`**, **database-per-tenant**, **domain-based** identification, **self-serve** provisioning + custom domain mapping. Target ~100 clinics. | Strong isolation (LGPD story), easy white-label ("Powered by Atma"), self-serve signup. |
| **Data** | **Greenfield.** Factories + seeders for demo data. No Firestore ETL (app not launched). | Nothing in production to migrate. |
| **Domain layer** | **Fat Eloquent models + invokable Action classes.** No Repository wrapper over Eloquent. | Idiomatic Laravel; Actions are independently testable (mandatory TDD). |
| **Automation** | **Domain Events + Listeners** (queued when slow). | Replaces Firestore triggers; explicit, testable cross-domain side-effects. |
| **Testing** | **Pest, feature-test-first, TDD** (red→green→refactor). Factory per model. | Per project CLAUDE.md; `stancl/tenancy` test helpers isolate tenant context. |

**Approved dependency change:** add `stancl/tenancy`. (CLAUDE.md requires approval for dependency changes — granted for this package.)

---

## 3. Architecture spine

### 3.1 Tenant boundary — central vs. tenant databases

| Central DB ("landlord") | Tenant DB (one per clinic) |
|---|---|
| `tenants` (clinic registry: id, plan, status) | `users` (clinic staff + role) |
| `domains` (custom domain → tenant) | `patients`, `doctors`, `specialties` |
| `subscriptions` / billing *(Phase 2)* | `appointments`, `pipeline_cards`, `timeline_events` |
| Atma super-admin users | `procedures`, `budgets`, `budget_items`, `transactions`, `transaction_items` |
| | `clinical_notes`, `prescriptions`, `anamneses`, `patient_documents`, `exam_results`, `exam_findings` |
| | `conversations`, `messages` |

In the TS app a user belonged to exactly one clinic, so **`users` lives in the tenant DB**. The central
DB is just registry + routing. Self-serve signup = central route that creates the tenant, runs tenant
migrations, maps the domain, and seeds the first `admin` user.

### 3.2 Layered architecture

```
Livewire Component (presentation, thin)
      │ calls
Action class (one domain operation, e.g. ApproveBudget)   ← unit-tested
      │ uses
Eloquent Model (relationships + simple invariants)        ← feature-tested
      │
Tenant database (auto-scoped by stancl/tenancy)
```

Events fire from Actions/models; Listeners handle cross-domain reactions.

### 3.3 Automation map (Events → Listeners)

| Event | Listener | Effect |
|---|---|---|
| `BudgetApproved` | `MovePipelineCardForward` | Approved/completed budget advances the linked card |
| `AppointmentCancelled` | `MoveCardToDesistentes` | Cancellation drops the patient's card to "desistentes" |
| `PipelineStageChanged` | `SyncPatientStatus` | Stage maps to patient status (active / lead / inactive) |
| `LeadReceived` (webhook) | `CreatePatientAndPipelineCard` | Dedup by phone, create patient + card + timeline event |

### 3.4 Conventions

```
app/
  Actions/{Domain}/        e.g. Actions/Pipeline/MoveCardToStage.php
  Livewire/{Domain}/       e.g. Livewire/Pipeline/Board.php
  Models/                  Eloquent models (flat, Laravel default)
  Events/  Listeners/  Policies/
resources/views/
  components/ui/           our in-house x-ui.* interactive kit (Flux-styled)
  flux/                    free Flux components (shell/forms — from starter kit)
  livewire/{domain}/
database/migrations/tenant/   tenant-DB migrations (stancl convention)
tests/Feature/{Domain}/    Pest feature tests (primary)
tests/Unit/{Domain}/       Action/model unit tests
```

### 3.5 Roles

Start with the TS model: **`admin`** and **`staff`** (enum on tenant `users`). RBAC for finer roles
(reception / medical / financial) is designed-for but deferred. Authorization via Laravel Policies.

---

## 4. Domain glossary (entities ported from the TS app)

| Entity | Key fields | Notes |
|---|---|---|
| **Clinic / Tenant** | name, email, phone, address, cnpj, logo, plan, status | Central registry; per-tenant DB holds clinic profile |
| **User** | name, email, password, role(admin/staff) | Tenant DB; one clinic per user |
| **Patient** | name, phone, email, cpf, birth_date, status(active/lead/inactive), ltv, allergies, visit counters, lead_source | Hub entity |
| **PipelineCard** | patient_id, stage(10), treatment, value, last_contact, contact_type, budget_id? | One active card per patient |
| **TimelineEvent** | patient_id, type, title, description, date | Activity log |
| **Appointment** | patient_id, doctor_id?, procedure_id?, date, start/end, status | scheduled→checked-in→completed/cancelled/no-show |
| **Doctor** | name, crm, specialties[], phone, email, active | N:N Specialty |
| **Specialty** | name, active | |
| **Procedure** | name, base_price, duration, category, active | Catalog |
| **Budget** | patient_id, items[], total, status(draft/sent/approved/completed), notes | → Transaction; triggers pipeline |
| **BudgetItem** | procedure_id, name, unit_price, quantity, discount | |
| **Transaction** | patient_id, items[], total, payment_method, status(pending/paid), budget_id? | |
| **ClinicalNote** | patient_id, content, date | Prontuário |
| **Prescription** | patient_id, medication, dosage, duration, date | |
| **Anamnesis** | patient_id, chief_complaint, history fields… | 1:1 patient |
| **PatientDocument** | patient_id, name, file_type, storage_url, category, exam_result_id? | Laravel Storage |
| **ExamResult** | patient_id, document_id, parse_status, ocr_confidence, raw_text, ai_summary, findings[] | AI parse (Phase 2) |
| **Conversation** | patient_id, last_message, unread_count | |
| **Message** | conversation_id, text, sender(clinic/patient), status | |

Pipeline stages: `primeiro_contato → avaliacao → em_analise → orcamento_enviado → negociando →
orcamento_aceito → agendado → retorno → concluido`, plus `desistentes` (dropouts column).

---

## 5. PRD map & build order

Each PRD is its own spec → plan → TDD build cycle. Arrows = hard dependencies.

| # | PRD | Depends on | Phase | Why here |
|---|---|---|---|---|
| 0 | **Platform Foundation** | — | MVP | Tracer bullet: tenancy + signup + auth + `x-ui` kit + TDD harness end-to-end |
| 1 | **Settings & Identity** | 0 | MVP | Catalog entities (clinic, users, doctors, specialties, procedures) others FK to |
| 2 | **Patients** | 0 | MVP | Hub entity; ~6 contexts read it |
| 3 | **Pipeline CRM ★** | 2 | MVP | The differentiator; de-risks drag-drop early |
| 4 | **Scheduling** | 1, 2 | MVP | Calendar + no-show → feeds pipeline automation |
| 5 | **Financial** | 1, 2 | MVP | Budget→Transaction + Budget→Pipeline automation |
| 6 | **Clinical / EHR** | 2 | v1.1 | Prontuário (notes, prescriptions, anamnesis, documents) |
| 7 | **Communication** | 2 | v1.1 | Internal chat |
| 8 | **Lead Ingestion** | 2, 3 | v1.1 | Webhook → patient + card |
| 9 | **Dashboard & Analytics** | most | v1.1 | KPIs, urgent recalls, activity timeline |
| 10 | **AI Exam Parsing** | 6 | Phase 2 | OCR + LLM lab-result parsing |
| 11 | **Messaging Automation** | 7 | Phase 2 | WhatsApp / Email reminders & campaigns |
| 12 | **Payment Gateway** | 5 | Phase 2 | Asaas/Stripe (Pix, card, boleto) |
| 13 | **Billing & Subscriptions** | 0 | Phase 2 | Central-DB plans, trials, metering |

**MVP (sellable core) = PRDs 0–5.** A clinic can sign up, configure, register patients, run the
retention pipeline, schedule, and bill — the full "no patient gets forgotten" loop.

---

## 6. How PRDs are produced

Just-in-time: the detailed spec for PRD-N is brainstormed and written immediately **before** building it,
not all up front (avoids churn as we learn). Each PRD spec lands in `docs/prds/NN-<name>.md`, then goes
through `writing-plans` → TDD implementation. This document is the stable spine they all reference.
