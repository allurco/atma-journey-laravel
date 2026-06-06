# PRD-4 — Scheduling

> **Status:** in progress · **Phase:** MVP · **Depends on:** PRD-1 (Doctors/Procedures), PRD-2 (Patients), PRD-3 (Pipeline)
> **Source of truth:** `../atma-journey-ts/src/services/AppointmentService.ts` (state machine) +
> `../atma-journey-ts/src/components/WeeklyCalendar.tsx` + `ScheduleAppointmentModal.tsx` (design).

## Why this PRD

Scheduling closes two thirds of the retention loop: it's where a patient who accepted a budget actually
gets seen, and where the **no-show** — the classic way a patient gets forgotten — is caught and fed
back into the pipeline. It also feeds the denormalized **visit counters** PRD-2 created but left for us
to write (`total_appointments`, `missed_appointments`, `last_visit_date`, `first_visit_date`).

It's the second in-house **Pro-gated** UI: the **weekly calendar** grid plus the **date/time pickers**
for booking — built ourselves as `x-ui.*`, no Flux Pro, no React.

## Scope

**In:** the `Appointment` model + `AppointmentStatus` enum, the **weekly calendar** (Mon–Fri × hourly
slots, week navigation, appointment blocks), **booking** an appointment (the date/time picker + modal,
from a slot or from a patient), the **lifecycle** state machine (check-in → complete, cancel, no-show)
with guards, the **visit-counter** rollups onto the patient, and the **cancel/no-show → pipeline**
automation (`MoveActiveCardToDesistentes`). The sidebar **Agenda** goes live.

**Out (owned elsewhere):** budgets/transactions + `BudgetApproved → MovePipelineCardForward` (PRD-5),
prontuário written *during* a completed appointment (PRD-6), reminders/confirmations messaging
(PRD-7/11), the lead webhook (PRD-8), calendar adoption / no-show KPIs on the dashboard (PRD-9).
Doctor availability/working-hours and room/resource booking are **deferred** (book against any slot for
the MVP).

## Entities (tenant DB)

| Table | Columns | Notes |
|---|---|---|
| `appointments` | `patient_id (fk cascade), doctor_id (fk null), procedure_id (fk null), service_type, date, start_time, end_time, status` | Indexed `(date, start_time)`. `service_type` is a free label (colours the block) even when no procedure is linked. |

- **`AppointmentStatus` enum** (`app/Enums/`): `Scheduled='scheduled'`, `CheckedIn='checked-in'`,
  `Completed='completed'`, `Cancelled='cancelled'`, `NoShow='no-show'` (values match the TS app) with
  `label()` (PT) + `badgeClasses()` (the status palette from `WeeklyCalendar.tsx`).
- Reuses PRD-1 **Doctor** (name, crm, specialties) and **Procedure** (name, base_price, **duration**
  minutes) — a chosen procedure's `duration` pre-fills `end_time`.

## Key decisions

- **The lifecycle is a guarded state machine, in one place.** Ported from `AppointmentService`:
  `scheduled → checked-in → completed`; `scheduled|checked-in → cancelled`; `scheduled → no-show`.
  Illegal transitions throw. An invokable action per transition (or one `TransitionAppointment` action)
  is the only writer; the calendar/flyout call it. Reschedule resets a non-terminal appointment to
  `scheduled` with a new date/time.
- **Completing/▸no-show writes the patient counters PRD-2 deferred.** `complete` →
  `total_appointments++`, set `last_visit_date = date` (and `first_visit_date` if null); `no-show` →
  `missed_appointments++`. These are the rollups the patient detail + `scopeNeedingRecall` already read.
- **No-show *and* cancel drop the pipeline card.** `AppointmentNoShow`/`AppointmentCancelled` →
  `MoveActiveCardToDesistentes` (a new PRD-3 action: move the patient's active, non-concluded card to
  `desistentes`, preferring the `agendado` card). This reuses `MoveCardToStage`, so the status-sync +
  timeline event from PRD-3 fire for free — the funnel reflects the miss automatically.
- **Build the calendar + pickers ourselves.** `x-ui.calendar-week` (the Mon–Fri grid) and
  `x-ui.date-picker` / `x-ui.time-select` (booking) are Alpine + Blade, styled to Flux tokens. Where
  client interactivity needs JS, follow the PRD-3 pattern: a **Vite-bundled resource + a Livewire
  bridge**, never a CDN tag or inline blob.
- **Week is the unit.** The calendar loads one Mon–Fri week and navigates by ±7 days, matching the TS
  app. Slots are hourly `08:00–18:00`. Cancelled appointments are hidden from the grid.
- **Times are tenant-local wall-clock.** Store `date` + `start_time`/`end_time` as date/time columns;
  no per-appointment timezone for the MVP.

## Design (port from the TS app)

- **Calendar** ports `WeeklyCalendar.tsx`: a week header with prev/next + the week label, columns
  Segunda–Sexta, rows `08:00…18:00`, appointment blocks coloured by **service type** (teal/emerald/
  violet/amber) with a **status** badge (scheduled=blue, checked-in=emerald, completed=slate,
  no-show=amber; cancelled hidden). Search by patient + doctor/procedure filters. Clicking a block opens
  a **detail flyout** with the lifecycle actions; clicking an empty slot opens the booking modal.
- **Booking** ports `ScheduleAppointmentModal.tsx`: patient (searchable), doctor (optional), procedure
  (optional — pre-fills duration/service), date, start/end time. Brand: teal, `#FAFAF8`, rounded-`xl`.

## Build slices (tracer bullets, TDD)

1. **Appointment model + week calendar (read)** — `Appointment` model/migration/factory +
   `AppointmentStatus` enum + the **weekly calendar** Livewire (Mon–Fri × hourly grid, week nav,
   appointment blocks coloured by service/status, cancelled hidden) + sidebar **Agenda** live.
   Read-only; demoable with factory appointments.
2. **Book an appointment** — the booking modal + the in-house `x-ui.date-picker`/`x-ui.time-select`
   (patient, optional doctor/procedure with duration→end_time, date, start/end), creating a
   `scheduled` appointment from an empty slot and from the patient detail.
3. **Appointment lifecycle + counters** — the guarded state machine (check-in → complete, cancel,
   no-show) via a single transition action, the detail flyout with action buttons + status badges, and
   the **patient visit-counter** rollups (complete → total/last/first visit; no-show → missed).
4. **No-show/cancel → pipeline automation** — `MoveActiveCardToDesistentes` action (PRD-3) +
   `AppointmentCancelled`/`AppointmentNoShow` events → listener that drops the patient's active card to
   `desistentes` (reusing `MoveCardToStage`, so status-sync + timeline fire). Wire into slice 3's
   cancel/no-show transitions.

*Dependency note:* 1 → 2 → 3 → 4 (4 reuses PRD-3's `MoveCardToStage`; 3 owns the transitions 4 hooks).

## Testing

- Feature (TenantTestCase): calendar groups appointments into the right day/slot; week navigation;
  booking creates a scheduled appointment (duration→end_time); the state machine guards (each illegal
  transition throws; each legal one updates status); complete/no-show update the patient counters;
  cancelled appointments hidden; tenant isolation.
- Unit: `AppointmentStatus` transitions/labels; counter math.
- Automation: cancel + no-show move the patient's active card to `desistentes` (and via PRD-3 flip
  status to Inativo + log a timeline event).
- **Browser pass is mandatory** for the calendar grid and the date/time picker — the JS-heavy,
  Livewire-bridge surfaces (cf. PRD-3 drag-and-drop).

## Deferred

Doctor working-hours/availability + conflict detection · rooms/resources · recurring appointments ·
reminders/confirmations (PRD-7/11) · prontuário capture on completion (PRD-6) · budget→appointment link
(PRD-5) · calendar/no-show KPIs (PRD-9). The visit-counter rollups and the `MoveActiveCardToDesistentes`
seam ship here; messaging and clinical capture arrive with their owning PRDs.
