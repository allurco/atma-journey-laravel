# PRD-18 · Patient Timeline (Unified Clinical History)

**Phase:** v1.1 · **Depends on:** PRD-2 (the `TimelineEvent` seam + the existing timeline tab),
PRD-4 (appointments), PRD-6 (clinical artifacts: clinical notes, prescriptions, exams, documents).
Shares the clinical data-source layer with **PRD-17**. **Consumed by:** the doctor and the front desk
(a read surface).

## Why this PRD

This is **one feature: evolve the patient timeline we already have.** Today's timeline (PRD-2) is an
**event-log** — it records what *happened* (contacts, scheduling, signatures, notes) as `TimelineEvent`
rows. It does **not** surface the clinical **artifacts that were produced** — prescrições, exames,
documentos, fotos, questionários, consultas — which live in their own models and were never appended as
events. So roughly **half the history a doctor wants is missing** from it.

This PRD turns that single timeline into a **unified chronological history**: events **+** the real
clinical artifacts, with **quick filters + counters**, shown **in the Prontuário** (full) and reused —
filtered — in **Pacientes** (replacing today's flat list). **One feature, one component, two surfaces.**

## Scope

**In:**
- A read-only **aggregation** action over the six sources → a common **`TimelineItem`**.
- **One reusable timeline component:** quick filters + per-filter **counters** + **click-through** to the
  real record (a prescription → Receitas, an exam → Exames, …).
- A new **"Linha do tempo"** tab in the **Prontuário** showing the full clinical history.
- The **Pacientes** timeline replaced by the **same component, filtered to events** (retires the flat list).

**Out:** new clinical models (photos/contracts/questionnaires are already `PatientDocument` **categories**);
**editing** artifacts from the timeline (writes stay in their owning tabs); the AI summary (PRD-17).

## Entities

**None new.** Pure aggregation over existing models.

## Data sources → `TimelineItem`

| Filter | Source | Date field | Notes |
|---|---|---|---|
| Consultas | `Appointment` | `date` | the visit (status: agendada/realizada/…) |
| Procedimentos | `Appointment` (with `procedure_id`) | `date` | facet of the appointment |
| Evoluções | `ClinicalNote` | `occurred_at` | doctor's notes |
| Prescrições | `Prescription` | `issued_at` | |
| Exames | `ExamResult` (+ `PatientDocument` `category=Exam`) | `collected_at` | "anexado" = `patient_document_id` set |
| Documentos | `PatientDocument` (`Contract`/`Consent`/`Report`/`Other`) | `uploaded_at` | contratos = `Contract` |
| Fotos | `PatientDocument` `category=Image` | `uploaded_at` | clinical photos |
| Questionários | `PatientDocument` `category=Questionnaire` | `uploaded_at` | |
| Observações | `TimelineEvent` | `occurred_at` | the existing event-log (contatos, agendamentos, assinaturas, notas) |

Each maps to `TimelineItem { type, date, title, summary, icon, link }` — `link` opens the owning record/tab.

## Key decisions

| Area | Decision | Why |
|---|---|---|
| **Aggregation, not materialization** | Union the **real models** at read time into `TimelineItem`s. Never copy artifacts into `TimelineEvent`. | Single source of truth; the timeline *reflects* the chart instead of holding a copy that goes stale. |
| **One component, two surfaces** | Build the timeline **once**; the Prontuário shows everything, Pacientes shows it **filtered to Observações** (replacing the flat event-log). | "Only one feature" — no parallel timelines. |
| **Read-only** | The timeline displays; writes happen in the owning tabs. Clicking an item **opens its record**. | The timeline is an index, not an editor. |
| **Filters + counters** | Quick filters (the table above), each with a **count**; default = all. | The card's explicit ask. |
| **Pagination** | PHP merge + `LengthAwarePaginator` for v1; swap to a `UNION` index if a history gets huge. | Bounded histories; simplest correct first cut, interface-stable. |
| **Shared with PRD-17** | Reuse the same clinical-source mapping PRD-17 aggregates. | One place that knows "the patient's clinical sources." |

## Build slices (tracer bullets, TDD)

1. **Aggregation + unified component + Prontuário tab.** `BuildClinicalTimeline(Patient, filters, page)`
   query action → paginated `TimelineItem`s + per-filter counters; the reusable component (filters,
   counters, click-through); a new **"Linha do tempo"** tab in the Prontuário (full history).
2. **Pacientes swap.** Replace the Pacientes flat event-log list with the **same component filtered to
   Observações**; keep the manual "registrar observação" entry; carry/adapt the PRD-2 timeline tests.

## Testing

- `BuildClinicalTimeline` merges the six sources in date order; filters narrow the set; counters are
  correct per filter; each item links to the right record; pagination holds.
- Prontuário "Linha do tempo" renders the full history; empty state.
- Pacientes shows the Observações-filtered timeline; manual entry still works; old PRD-2 timeline tests
  adapted to the new component.
- **Browser pass** (aggregation-heavy Livewire on both surfaces).

## Deferred

`UNION`-index pagination · AI chart summary (PRD-17) · editing/quick-actions from the timeline · export ·
a dedicated multi-photo clinical model (clinical photos remain `PatientDocument` `Image`).
