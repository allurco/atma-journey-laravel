# PRD-2 · Patients

**Phase:** MVP · **Depends on:** PRD-0 (foundation), PRD-1 (clinic/doctors/specialties exist) ·
**Consumed by:** Pipeline (3), Scheduling (4), Financial (5), Clinical/EHR (6), Communication (7),
Lead Ingestion (8), Dashboard (9).

## Why this PRD

The **Patient** is the hub entity of the whole product — almost every other context foreign-keys
to it. And the **timeline** is the literal embodiment of the core promise (*no patient is forgotten*):
an append-only activity log that later PRDs write to whenever something happens to a patient (a stage
change, an appointment, a budget, a message). This PRD builds the registry, the patient detail, the
photo, and the timeline **seam** — but deliberately stops at the edges of the contexts that own the
events that *fill* the timeline.

## Scope

**In:** patient registry (list/search/create/edit), patient detail (overview), patient photo, the
`TimelineEvent` model + the `AppendTimelineEvent` action other PRDs call, the "Linha do tempo" tab,
manual timeline entries, and an auto "paciente cadastrado" event. The sidebar **Pacientes** goes live.

**Out (owned elsewhere):** pipeline card (PRD-3), appointments (PRD-4), budgets/transactions (PRD-5),
prontuário — clinical notes, prescriptions, anamnesis, documents, exams (PRD-6), conversations/messages
(PRD-7), the lead webhook (PRD-8), dashboard KPIs/recall UI (PRD-9). **Prontuário stays a separate nav
item** (PRD-6) — Pacientes is the registry, Prontuário is the clinical record opened *from* a patient.

## Entities (tenant DB)

| Table | Columns | Notes |
|---|---|---|
| `patients` | `name, phone, email, cpf, birth_date, address, photo_path, status, blood_type, allergies (json), ltv (decimal), last_visit_date, first_visit_date, total_appointments (int), missed_appointments (int), lead_source` | The hub. `cpf` unique-per-tenant (nullable). |
| `timeline_events` | `patient_id (fk cascade), type, title, description, occurred_at` | Append-only activity log; indexed `(patient_id, occurred_at)`. |

- **`PatientStatus` enum** (`app/Enums/`): `Ativo='active'`, `Inativo='inactive'`, `Lead='lead'`
  (TitleCase keys, string values matching the TS app). Cast on the model.
- **`TimelineEventType` enum**: `Whatsapp, Agendamento, LigacaoPerdida, Email, Concluido`
  (values `whatsapp, appointment, missed-call, email, completed` — matching the TS app; later PRDs may
  add types, so design the enum to grow).
- **`allergies`** → a cast `array` (JSON) column. Free-text tags; no separate table.

## Key decisions

- **Counters are written by other PRDs, displayed read-only here.** `ltv`, `total_appointments`,
  `missed_appointments`, `last_visit_date`, `first_visit_date` are denormalized rollups. PRD-2 creates
  the columns (default 0 / null) and *shows* them; **Scheduling** updates visit counters + dates,
  **Financial** updates `ltv`. Don't build write logic for them here beyond manual create defaults.
- **Status default.** A manually-created patient defaults to `active`. Leads created by the PRD-8
  webhook are `lead`. `PipelineStageChanged → SyncPatientStatus` (PRD-3) keeps status in sync later —
  PRD-2 only sets the initial value and allows manual edit.
- **The timeline is a seam, not a feature silo.** Establish one invokable action,
  `AppendTimelineEvent(AppendTimelineEventData)`, that is the *only* way events are written. PRD-2 calls
  it for the auto "paciente cadastrado" event and manual entries; PRDs 3/4/5/7 call it from their
  listeners. This keeps "no patient forgotten" enforceable in one place.
- **Recalls live as a model scope now, the UI later.** `Patient::scopeNeedingRecall($months = 6)`
  (active patients whose `last_visit_date` is older than N months, nulls excluded) ships here with
  tests; the **Dashboard (PRD-9)** renders the list. Cheap to add now, avoids a PRD-9 reach-back.
- **Photo reuses the PRD-1 logo pattern** exactly: private `local` disk (tenant-suffixed) + a
  tenant-scoped serving route/controller, not the public disk. The Livewire-upload-under-tenancy
  plumbing is already solved.
- **CPF** is validated for format (and uniqueness per tenant) but **optional** — leads often arrive
  without one.

## Design (port from the TS app)

- **Detail** ports `src/components/PatientProfile.tsx` — header (photo/avatar, name, status badge, age,
  contact), then tabs: **Visão geral** (counters: LTV, total/missed appointments, first/last visit;
  allergies, blood type, lead source, address) and **Linha do tempo** (the timeline).
- **List** (no dedicated TS component — design in the ATMA language): a table/cards of patients with
  avatar, name, phone, status badge, last visit; **search** (name/phone/cpf) and **status filter**;
  row → detail. The sidebar **Pacientes** item goes live. Brand: teal, `#FAFAF8`, rounded-`xl`,
  `<x-ui.*>` where a Pro-gated control is needed (searchable select for status).

## Build slices (tracer bullets, TDD)

1. **Patient registry** — `Patient` model/migration/factory + `PatientStatus` enum + **Pacientes** list
   (search by name/phone/cpf, status filter, paginated) + create/edit form (validate name/phone
   required, cpf format + unique-per-tenant, email, birth_date) + the sidebar **Pacientes** goes live.
   Admin **and** staff can manage patients (clinical staff need this — *not* gated to admin like
   settings). Auto-create a "paciente cadastrado" timeline event on create (needs slice 4's action, so
   either land slice 4 first or stub the event and wire it in 4).
2. **Patient detail — Visão geral** — the detail route + header (avatar, status badge, age from
   birth_date, contact) + the overview tab (read-only counters, allergies tags, blood type, lead
   source, address). Row in the list links here.
3. **Patient photo** — photo upload on the patient edit/detail (image, ≤2 MB) on the tenant disk +
   `PatientPhotoController` + a tenant-scoped `patients/{patient}/photo` route; avatar shows the photo
   in the list + detail (falls back to initials). Mirrors PRD-1 logo.
4. **Patient timeline** — `TimelineEvent` model/migration/factory + `TimelineEventType` enum +
   `AppendTimelineEvent` invokable action + readonly DTO (the cross-PRD seam) + the **Linha do tempo**
   tab (chronological, typed icons) + a manual "registrar contato/observação" entry form. Backfill
   slice 1's create event through the action. Add `Patient::scopeNeedingRecall()` + tests.

*Ordering note:* slice 4 defines the timeline action slice 1 wants. Build **1 (stub the create-event) →
2 → 3 → 4 (wire the create-event)**, or reorder 4 ahead of 1's event hook. The slicer (`/to-issues`)
will settle the exact dependency.

## Testing

- Feature (TenantTestCase): patient CRUD; search + status filter; cpf format + per-tenant uniqueness;
  tenant isolation; staff *can* manage patients (unlike settings).
- Photo: upload persists + serves; cross-tenant fetch denied (mirror PRD-1 logo tests, incl. the real
  Livewire-upload path).
- Timeline: `AppendTimelineEvent` writes an event; the auto create-event fires; manual entry; events
  ordered; `scopeNeedingRecall` returns the right patients.
- **Browser pass is mandatory** for the photo upload and any Livewire-update-heavy screen — the
  tenancy/session/upload bugs from PRD-1 prove unit tests miss the middleware stack.

## Deferred

Pipeline card · appointments · budgets/transactions · prontuário (notes/prescriptions/anamnesis/
documents/exams) · conversations/messages · lead webhook · dashboard KPIs. The counters and timeline
**columns/seam** exist here; the **events that fill them** arrive with their owning PRDs.
