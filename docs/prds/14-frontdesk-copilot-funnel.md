# PRD-14 · Front-desk Copilot — Funnel & Recall

**Phase:** v1.1 (next) · **Depends on:** PRD-2 (patients, `scopeNeedingRecall`, `AppendTimelineEvent`),
PRD-3 (pipeline stages, active-care, the events that drive `SyncPatientStatus`) ·
**Consumed by:** PRD-15 (Retention core), PRD-16 (Clinical signals).
**Design:** `../superpowers/specs/2026-06-07-frontdesk-copilot-sinal-design.md` · **ADRs:** 0001, 0002.

## Why this PRD

The Pipeline is the differentiator, but it only tracks patients **someone already chose to work**.
Patients who finished treatment (`concluido`) or dropped (`desistentes`) fall off the board and get
forgotten — the exact failure the product promises to prevent. The **Sinal** is the on-ramp that pulls
them back. This PRD builds the **feed** (the front desk's daily worklist) *and* its first real producer
(**recall**, from data that already exists) so the feed is alive on day one — never an empty screen. It
establishes the `RaiseSinal` seam, the lifecycle, the act (click-to-WhatsApp), and the metrics that PRDs
15–16 plug into without touching the consumer.

## Scope

**In:** the `Sinal` model + `SinalType`/`SinalTier`/`SinalStatus` enums + migration/factory; the
`RaiseSinal` action (the seam: active-care suppression, one-open-per-`(patient,type)` dedup, `SinalRaised`
event); the **recall producer** (scheduled scan over `Patient::needingRecall()`); the **front-desk
dashboard** = the live feed (tier-ranked, act/snooze/dismiss); `App\Support\Phone` (E.164) + click-to-
WhatsApp; outcome-based resolution listeners (pipeline re-entry); lifecycle metrics
(`sinal_raised/actioned/resolved/dismissed`); role-based default landing.

**Out (later PRDs):** no-show & quote producers, LLM drafting (PRD-15); clinical signals + doctor
approval + exam AI (PRD-16); the analytics/owner dashboard's full metric set (PRD-15 — this PRD records
the metrics; the owner view that *charts* them lands with 15); the explicit "não contatar" opt-out
(deferred).

## Entities (tenant DB)

| Table | Columns | Notes |
|---|---|---|
| `sinais` | `patient_id (fk cascade), type, tier, status, value (decimal nullable), value_kind (nullable), reason, draft_message (text nullable), doctor_id (fk nullable), due_at, snoozed_until, context (json nullable)` | One feed row. Indexed `(status, tier)` and `(patient_id, type, status)` for the dedup gate. |

- **`SinalType` enum:** `Recall='recall'`, `NoShow='no_show'`, `QuoteFollowup='quote_followup'`,
  `Clinical='clinical'` (PRD-14 only *raises* `Recall`; the others are declared so the feed/enum are
  stable, raised by 15/16).
- **`SinalTier` enum:** `Critica`, `Alta`, `Media`, `Baixa` — set by the producer (ADR-0001).
- **`SinalStatus` enum:** `Pendente`, `EmAndamento`, `Resolvido`, `Dispensado`, `Adiado`.
- **`ValueKind` enum:** `Ltv`, `Procedure`, `Budget` (nullable on the row; clinical has none).

## Key decisions

- **`RaiseSinal` is the only way to create a Sinal** (the seam). It owns policy in one place: the
  **active-care suppression gate** (`Patient::inActiveCare()` = a card in `primeiro_contato`…`retorno`,
  skipped for `clinical`), the **one-open-per-`(patient,type)`** dedup (refresh, don't duplicate), and
  emitting `SinalRaised`. Producers are dumb. See ADR-0002.
- **Recall reuses `Patient::needingRecall()`** — no new query logic. The producer is a scheduled command
  that scans the scope, filters out active-care patients, and calls `RaiseSinal` with `value = ltv`,
  `value_kind = ltv`, tier by overdue-ness (`media` < 12 mo, `alta` ≥ 12 mo).
- **Acting ≠ resolving.** Click-to-WhatsApp → `pendente → em_andamento` + an `AppendTimelineEvent`
  (`whatsapp`, *"Contato por WhatsApp iniciado"* — action, not delivery) + a `sinal_actioned` metric. The
  Sinal stays open. Resolution is event-driven: a listener closes the open recall Sinal when the patient
  re-enters the pipeline (a new/moved card into an active stage) — reusing the PRD-3 stage-change event.
- **The front-desk dashboard replaces the hardcoded "Recalls urgentes" card.** Today's `Dashboard`
  renders `scopeNeedingRecall` read-only; this PRD turns that into a `Sinal`-backed, actionable worklist.
  Role sets the **default landing** (`staff` → feed); both roles can reach it.
- **`App\Support\Phone`** (sibling to `App\Support\Money`): clinic input → E.164, `+55` default, BR
  9th-digit rules. Un-normalizable number → WhatsApp button **disabled** (*"número inválido"*), never a
  broken `wa.me` link.
- **Metrics ride the existing machinery** (`Metric::record` / `RecordDomainMetric`). PRD-14 *records* the
  lifecycle; PRD-15 builds the owner view that charts taxa de reativação + valor associado.

## Design

- **Feed (front-desk dashboard):** clinical-critical lane on top (empty until PRD-16), then revenue
  Sinais ranked by tier, value tag as in-tier tiebreaker. Each row: patient avatar/name, the **reason**,
  the typed/labeled price tag (e.g. *"Valor do paciente · R$ 3.200"* — never "em jogo"), and actions:
  **WhatsApp** (or disabled), **Adiar** (date), **Dispensar**. Brand: teal, `#FAFAF8`, rounded-`xl`,
  `<x-ui.*>` for any Pro-gated control (snooze date picker). No TS equivalent — design in the ATMA language.
- **Empty state:** *"Nenhum sinal pendente — todos os pacientes em dia. 🎉"* (mirrors the current recall card).

## Build slices (tracer bullets, TDD)

1. **The `Sinal` model + `RaiseSinal` seam** — model/migration/factory + the four enums +
   `RaiseSinal(RaiseSinalData)` with the active-care gate and one-open-per-`(patient,type)` dedup +
   `SinalRaised` event. Unit-tested in isolation (no UI): suppression when in active care, dedup updates
   the open Sinal, tier/value passthrough.
2. **Recall producer** — a scheduled command scanning `Patient::needingRecall()`, skipping active-care
   patients, raising `recall` Sinais with LTV value + overdue-based tier. Idempotent (re-running doesn't
   duplicate). Feature-tested end-to-end into the `sinais` table.
3. **The feed (front-desk dashboard)** — the Livewire feed reading open Sinais, tier-ranked, with the
   clinical lane scaffold. Replaces the dashboard's recall card; role-based default landing. Renders the
   typed price-tag label. No actions yet — just ranked display.
4. **The act** — `App\Support\Phone` + click-to-WhatsApp (→ `em_andamento` + timeline event + metric),
   **Adiar** (`snoozed_until`, drops from feed), **Dispensar** (`dispensado`). Disabled button on bad phone.
5. **Outcome resolution + metrics** — listener closing the open recall Sinal on pipeline re-entry; record
   `sinal_raised/actioned/resolved/dismissed` metrics. Assert a click never auto-resolves.

## Testing

- **Action (unit):** `RaiseSinal` suppresses in active care; raises for `concluido`/`desistentes`/cardless;
  dedup updates not duplicates; tier + value_kind correct.
- **Producer (feature):** recall scan raises the right patients, idempotent, respects the active-care gate.
- **`Phone` (unit):** messy BR inputs → E.164; un-normalizable flagged.
- **Feed (feature/Livewire):** tier ordering; clinical lane on top; act → `em_andamento` + timeline +
  metric; snooze/dismiss; button disabled on bad phone; role-based default landing; tenant isolation.
- **Resolution (feature):** pipeline re-entry closes the open recall Sinal; a click alone does **not**.
- **Browser pass mandatory** for the feed (Livewire-action-heavy + the `wa.me` link/Alpine).

## Deferred

No-show & quote producers · LLM drafting · clinical signals/doctor approval/exam AI · the charted owner
dashboard · "não contatar" opt-out. The **seam, feed, lifecycle, and metrics exist here**; later PRDs add
producers behind `RaiseSinal` without touching the consumer.
