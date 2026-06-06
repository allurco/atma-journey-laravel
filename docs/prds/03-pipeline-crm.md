# PRD-3 — Pipeline CRM ★

> **Status:** in progress · **Phase:** MVP · **Depends on:** PRD-1 (Settings/Identity), PRD-2 (Patients)
> **Source of truth:** `../atma-journey-ts/src/services/PipelineService.ts` (business rules) +
> `../atma-journey-ts/src/components/Pipeline.tsx` (visual design).

## Why this PRD

This is **the differentiator** — the retention engine that delivers the core promise *no patient is
forgotten at any stage of the funnel*. It's a **Kanban** of the patient journey from first contact to
post-treatment. Moving a card is not a cosmetic drag: it **syncs the patient's status**, **logs a
timeline event**, and is the hinge other contexts pull (Scheduling's no-show drops a card to
*Desistentes*; Financial's approved budget advances one). PRD-3 builds the board, the move
automation, the signature drag-and-drop, and the create/edit flow — and the **seams** the later PRDs call.

It also de-risks our biggest UI bet early: the **in-house `x-ui.kanban`** (SortableJS + Alpine), the
first of the Pro-gated components we build ourselves rather than license Flux Pro.

## Scope

**In:** the `PipelineCard` model + `PipelineStage`/`ContactType` enums, the **Pipeline** board
(columns per stage, per-column count + total, header total pipeline value), **moving a card** between
stages (buttons *and* drag-and-drop), the **one-active-card-per-patient** invariant, the
`MoveCardToStage` action, `PipelineStageChanged → SyncPatientStatus`, a stage-change **timeline
event**, and **create/edit a card** (incl. "adicionar ao pipeline" from the patient detail). The
sidebar **Pipeline** goes live.

**Out (owned elsewhere):** appointments + the no-show → *Desistentes* listener (PRD-4), budgets +
`BudgetApproved → MovePipelineCardForward` and the `budget_id` link's write side (PRD-5), the lead
webhook → create-patient-and-card (PRD-8), pipeline KPIs/conversion analytics on the dashboard
(PRD-9). PRD-3 creates the `budget_id` **column** (nullable) and *shows* a "Orçamento vinculado"
badge; Financial writes it.

## Entities (tenant DB)

| Table | Columns | Notes |
|---|---|---|
| `pipeline_cards` | `patient_id (fk cascade, **unique**), stage, treatment, value (decimal), last_contact (date), contact_type, budget_id (nullable)` | One **active** card per patient — enforced by the unique index *and* the action. |

- **`PipelineStage` enum** (`app/Enums/`): `PrimeiroContato='primeiro_contato'`, `Avaliacao='avaliacao'`,
  `EmAnalise='em_analise'`, `OrcamentoEnviado='orcamento_enviado'`, `Negociando='negociando'`,
  `OrcamentoAceito='orcamento_aceito'`, `Agendado='agendado'`, `Retorno='retorno'`,
  `Concluido='concluido'`, `Desistentes='desistentes'` (TitleCase keys, snake values matching the TS
  app). Carries `label()` (PT titles), per-stage `dotClasses()`/`headerClasses()` (the column palette
  from `Pipeline.tsx`), an **ordered linear flow** helper (`desistentes` excluded — it's a special
  drop column), and `patientStatus(): PatientStatus` (the stage→status map).
- **`ContactType` enum**: `Whatsapp='whatsapp'`, `Phone='phone'`, `Email='email'` with `label()` + icon.

## Key decisions

- **One active card per patient — a real invariant.** Enforce with a **unique index on `patient_id`**
  *and* in `MoveCardToStage`/`CreateCard` (replace/delete any other card for the patient). The TS app
  also dedups at read time keeping the **most-advanced** card; we make that impossible-to-violate at
  the DB instead. Re-treatment after *Concluído* replaces the card (new treatment), it doesn't stack.
- **Moving a card is the automation hinge.** `MoveCardToStage(card, stage)` is the single write path:
  it updates the stage, **syncs patient status** via `PipelineStageChanged → SyncPatientStatus`, and
  **appends a timeline event** (`AppendTimelineEvent`, the PRD-2 seam) — *no patient forgotten* made
  literal. PRDs 4/5 call this same action from their listeners.
- **Stage → status map** (ported from `PipelineService`): `orcamento_aceito, agendado, retorno,
  concluido → Ativo`; `desistentes → Inativo`; `primeiro_contato … negociando → Lead`. Lives on the
  `PipelineStage` enum so it's one source of truth.
- **Build the Kanban ourselves — as a buildable resource + a Livewire bridge.** SortableJS is an
  **npm dependency** (MIT) imported in `resources/js/` and **bundled by Vite**, *not* a CDN tag or an
  inline `<script>` blob. It's registered as a named **Alpine component** (e.g. `Alpine.data('kanban', …)`
  in `resources/js/kanban.js`, imported by `app.js`) that the `x-ui.kanban` Blade mounts via
  `x-data="kanban()"`. The **bridge to Livewire is explicit**: on drop, the Alpine handler calls
  `$wire.moveCard(cardId, stage)` (the slice-2 action) — Alpine owns the drag DOM, Livewire owns the
  state and re-render. Buttons (*Voltar/Avançar/Desistentes/Reativar*) ship first so the move logic is
  testable headless; drag-and-drop is the thin JS layer over the same `moveCard`. Guard the re-render
  with `wire:ignore` on the Sortable container (or re-init on `morph` ) so Livewire's DOM diffing
  doesn't fight SortableJS.
- **`desistentes` is a drop column, not a flow step.** It sits last, styled rose, and offers
  *Reativar* (→ `primeiro_contato`). The linear flow is the other nine stages in order.
- **`value` and `last_contact` are card-local.** `value` is the treatment's quoted value (Financial
  may later supersede with a real budget); `last_contact` + `contact_type` drive the "há X dias" recency
  hint. No denormalized rollup onto the patient here.

## Design (port from the TS app)

- **Board** ports `Pipeline.tsx`: a horizontal scroll of fixed-width (`w-72`) columns; each column a
  white `rounded-xl` card with a tinted header (per-stage dot + title + count badge + `R$` column
  total). Cards show patient name, treatment, `R$ value` (emerald), a contact-type icon + relative
  `last_contact`, an optional "Orçamento vinculado" badge, and a footer of move controls. Header shows
  **Pipeline de Pacientes** + the **total pipeline value**. Brand: teal primary, `#FAFAF8` bg.
- **Stage palette** (header `bg`/`border` + dot): slate, sky, indigo, violet, amber, lime, teal, cyan,
  emerald, rose — exactly the `stageStyles` map in `Pipeline.tsx`.
- Cards link to the patient detail (`pacientes.show`).

## Build slices (tracer bullets, TDD)

1. **Pipeline board + create/edit** — `PipelineStage` + `ContactType` enums, `PipelineCard`
   model/migration/factory (unique `patient_id`), the **Board** Livewire rendering the Kanban columns
   from existing cards (per-column count + total, header total pipeline value, card layout ported from
   `Pipeline.tsx`), **create/edit a card** (treatment, value, contact_type, initial stage) enforcing
   one active card per patient (replace existing), an **"adicionar ao pipeline"** entry point from the
   patient detail, and the sidebar **Pipeline** goes live. No moving between stages yet.
2. **Move a card + status sync** — `MoveCardToStage` action (update stage + enforce one-card-per-patient
   + append a stage-change timeline event) + `PipelineStageChanged → SyncPatientStatus` listener
   (stage→`PatientStatus`) + the footer buttons (*Voltar/Avançar/Desistentes/Reativar*). Headless-testable.
3. **Drag-and-drop** — the in-house `x-ui.kanban`: **SortableJS added to `package.json`**, bundled via
   Vite in `resources/js/kanban.js` and registered as an Alpine component in `app.js`; on drop the
   bridge calls `$wire.moveCard(cardId, stage)` (slice 2's action). The signature interaction — reuses
   slice 2's proven automation, adds only the JS layer. (`npm run build` required.)

*Dependency note:* 1 → 2 → 3 (3 needs 2's move action; 2 needs 1's board + cards).

## Testing

- Feature (TenantTestCase): board groups cards by stage with correct counts/totals; one-card-per-patient
  enforced (unique index + action); `MoveCardToStage` updates stage, syncs status, appends a timeline
  event; the stage→status map (each bucket); *Reativar* from desistentes; create replaces an existing
  card; tenant isolation.
- Unit: `PipelineStage::patientStatus()` map; linear-flow ordering helpers.
- **Browser pass is mandatory** for the board and especially **drag-and-drop** — SortableJS + Livewire
  round-trips are exactly the JS-heavy path unit tests can't cover (cf. PRD-1 upload lessons).

## Deferred

Appointments + no-show automation (PRD-4) · budgets/transactions + `BudgetApproved` automation and the
`budget_id` write side (PRD-5) · lead webhook (PRD-8) · pipeline conversion KPIs (PRD-9). The
`budget_id` **column** and the cross-PRD **listener seams** (`MoveCardToStage`,
`MoveActiveCardToDesistentes`) exist here; their **callers** arrive with the owning PRDs.
