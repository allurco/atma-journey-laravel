# ATMA Journey — Doctor Availability & Capacity-Aware Waiting List Design

**Status:** Draft (awaiting user review) · **Date:** 2026-06-09
**Scope:** Un-defers the PRD-4 deferral of *doctor working-hours/availability* and builds the
**fila de espera** (waiting list) on top of it as a capacity-aware demand layer. Sits on the program
design (`2026-06-05-atma-journey-program-design.md`), the Scheduling PRD (`../../prds/04-scheduling.md`),
and the domain glossary (`../../../CONTEXT.md`).

---

## 1. What this is

Two features that together turn the agenda from a free-form whiteboard into a **capacity model where
demand meets supply in one gesture**:

- **Disponibilidade médica (doctor shifts)** — concrete dated blocks of time a doctor is available to
  see patients. This is the **supply/capacity** layer. It *gates* booking: an appointment can only be
  created inside a doctor's shift, and an **open slot** is the derivation *shift − appointments*.
- **Fila de espera (waiting list)** — the **demand** layer: patients who want a slot that isn't
  available yet (earlier date, specific doctor, preferred period). Lives as a closable side panel on the
  agenda; a card is **dragged onto an open slot** to book + convert it.

This directly serves the core promise — *no patient is forgotten*: a patient who can't get their
preferred slot is captured as demand instead of vanishing, and a freed slot (a cancellation) can be
matched back to a waiting patient.

This is **not a new bounded context** — it is the Scheduling context (PRD-4) finally growing the
availability model it deferred, plus a thin demand layer that feeds the existing booking seam.

---

## 2. Decisions locked

| Area | Decision | Rationale |
|---|---|---|
| **Shift model** | **Concrete dated blocks** `(doctor_id, date, start_time, end_time, unit?)`. No recurrence in v1. | User chose concreteness over a weekly template. A "duplicate last week" helper is the future ergonomic fix, not recurrence. |
| **Availability role** | Availability **gates booking**. Open slot = shift − appointments. | The whole reason for shifts-first: capacity must be real for demand to be computable. |
| **Booking guards** | Inside `ScheduleAppointment`: (1) booking must fall **within a shift**, (2) **no double-book** of the same doctor in overlapping time. | Single chokepoint — calendar click, action callers, and fila conversion all inherit it; UI guards would drift. |
| **Units** | One tenant = one unit → **no units catalog**. `unit` stays optional free-text as it is on `appointments`. | YAGNI; revisit only if multi-unit clinics appear. |
| **Day view** | **Resource timeline**: doctor **rows** (vertical scroll for many doctors), time horizontal, **specialty filter** (reuses `doctor_specialty`). Inline `+ Disponibilidade`; click an open span to book. | Standard multi-doctor scheduling view; the only surface where writes happen. |
| **Fila placement** | A **closable side panel** on the day view. Filterable (search / priority / status). | Demand sits next to capacity. |
| **Conversion** | **Drag a fila card → open slot → pre-filled booking modal (confirm)** → `ScheduleAppointment` → entry `Agendado` + linked `appointment_id` + timeline event. | Reuses the booking form; staff confirm/adjust; never a silent commit. |
| **Panel scoping** | While a doctor is selected/visible, the panel shows entries whose desired doctor is **that doctor or "qualquer"**, so every visible card is droppable. | Keeps every drag valid. |
| **Week / month views** | Read-only **occupancy overviews** (resource×days counts; month per-day totals), click-through to day. **Deferred** to a follow-up card. | Read-only projections of the same data — cheap once the day writer exists; not on the critical path. |

---

## 3. Domain shape

### 3.1 `DoctorShift` (supply)

Tenant table `doctor_shifts`:

- `doctor_id` (FK → doctors, cascade)
- `date` (date)
- `start_time`, `end_time` (string `HH:mm`, mirroring `appointments`)
- `unit` (string, nullable — mirrors appointments)
- timestamps

Index `(doctor_id, date)` for the lane query and the gating lookup. A doctor has many shifts; a shift
has many appointments by time-containment (not a hard FK — appointments already carry `doctor_id` + `date`
+ `start_time`).

**Open-slot derivation** (read model, no new table): for a `(doctor, date)`, the shift's
`[start_time, end_time)` minus the union of that doctor's non-cancelled appointment intervals on that date.

### 3.2 `WaitlistEntry` (demand)

Tenant table `waitlist_entries`, mirroring the appointment shape so conversion is lossless:

- `patient_id` (FK cascade)
- `doctor_id` (nullable — "médico desejado"; null = qualquer)
- `procedure_id` (nullable) + `service_type` (nullable) — mirror of `appointments`
- `unit` (nullable, free-text)
- `preferred_period` — enum `WaitlistPeriod {Manha, Tarde, Noite, Qualquer}`
- `priority` — enum `WaitlistPriority {Alta, Media, Baixa}`
- `status` — enum `WaitlistStatus {Aguardando, Chamado, Agendado, Cancelado}`, default `Aguardando`
- `notes` (text, nullable) — "observações internas"
- `appointment_id` (nullable FK) — set on conversion
- timestamps

Index `(status, priority)` for the panel ordering, `(patient_id)`.

### 3.3 `WaitlistContact` (chase log)

Tenant table `waitlist_contacts`: `waitlist_entry_id` (FK cascade), `user_id` (who), `channel` (nullable),
`note`, `contacted_at`. Per-entry contact history.

---

## 4. Reused seams (no reinvention)

- **`ScheduleAppointment` + `ScheduleAppointmentData`** — the single booking path; gains the availability
  guards. Conversion calls it; it already syncs special conditions and appends the `Agendamento` timeline
  event, so a converted entry inherits every booking side-effect.
- **`AppendTimelineEvent`** — "paciente adicionado à fila", "removido da fila", conversion logged on the
  patient timeline ("no patient forgotten" seam).
- **`Doctor`, `Specialty`, `doctor_specialty`** — lanes + specialty filter.
- **`Procedure`** — service type + duration for the booking modal.
- **Pipeline SortableJS drag pattern (PRD-3)** — the drag mechanism for the fila panel.
- **Patients Index search/filter/paginate pattern** — the fila managed list.
- **`SpecialCondition` catalog/settings pattern** — the shape any future small catalog mirrors.

---

## 5. Build order

**Card — Disponibilidade médica (build first; un-defers PRD-4 availability)**

- **S1 — Capacity backend (TDD, no UI):** `DoctorShift` model + migration + factory; `ScheduleAppointment`
  gains the within-shift + no-double-book guards; open-slot derivation helper.
- **S2 — Resource day view:** agenda becomes a doctor-row resource timeline (scroll, specialty filter),
  renders shift blocks vs locked cells, inline `+ Disponibilidade`, click an open span to book.

**Card — Fila de espera (build second; the original Trello card)**

- **F1 — Registry:** `WaitlistEntry` + the three enums + migration + factory + a managed list
  (search/filter) + "adicionado à fila" timeline event.
- **F2 — Panel + drag-to-convert:** closable fila panel on the day view; drag a card onto an open slot →
  pre-filled booking confirm → `ConvertWaitlistEntry` (status `Agendado` + linked appointment + timeline).
- **F3 — Lifecycle + histórico de contato:** chamar/cancelar transitions with guards; `WaitlistContact`
  chase log + per-entry panel.

**Card — Agenda · visão semana e mês (deferred follow-up)**

- Read-only week (resource × 7 days, booked/livre counts) + month (per-day totals) overviews, click-through
  to the day view.

---

## 6. Out of scope (v1)

Recurring shift templates · shift exceptions (folga/férias) · multi-unit / rooms / resources · conflict
detection beyond doctor double-book · group/overlapping-capacity slots · auto-matching demand to freed
slots (the fila is worked manually via the panel). All are natural follow-ups once the loop is live.
