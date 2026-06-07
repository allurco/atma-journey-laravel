# ATMA Journey — Front-desk Copilot (Sinal) Design

**Status:** Draft (awaiting user review) · **Date:** 2026-06-07
**Scope:** The agentic retention layer — a prioritized front-desk worklist of `Sinais` raised by
producers (retention rules + doctor-approved clinical findings) and worked to closure by the front desk.
Sits on the program design (`2026-06-05-atma-journey-program-design.md`) and the domain glossary
(`../../../CONTEXT.md`). Decisions recorded as ADRs `0001` and `0002`.

---

## 1. What this is

A **Sinal** is a single prioritized front-desk action item: *a patient who needs attention now*. Producers
raise Sinais; the front desk works them. The goal is twofold retention — bring the **clinic's patients**
back (no patient is forgotten), and keep the **SaaS clinics** subscribed by making that recovery visible.

The feature is one consumer (the front-desk feed) fed by several producers across three build phases,
joined by a single seam. The AI **never contacts a patient** — it orchestrates the *need* for contact and
hands the *reason* to the front desk, which is always the human sender.

This is not a new bounded context; it's a thin layer over the existing Pipeline, Patients, Scheduling,
Financial, and (future) Clinical contexts. The Pipeline remains the differentiator; the Sinal feed is its
**on-ramp** for patients who have fallen outside active attention.

---

## 2. Decisions locked

| Area | Decision | Rationale / ref |
|---|---|---|
| **Entity count** | **One** entity, `Sinal`. Any producer raises it; one feed consumes it. | YAGNI on a producer-Signal + consumer-Action split. |
| **Price tag** | A **typed, labeled estimate** per source (recall→patient LTV, no-show→procedure price, quote→budget total, clinical→none). **Never summed across types** into one "em jogo" headline. | Heterogeneous money; a fake hero number kills trust. |
| **Ranking** | **Priority tier** (`critica→alta→media→baixa`) set by the producer; price tag only breaks ties *within* a tier. Clinical findings hard-gated to the top. | ADR-0001. Money must never outrank a health flag. |
| **Lifecycle** | A Sinal is the **pipeline on-ramp**; revenue producers suppress when the patient is in **active care**; resolution is **by outcome** (domain events), not by the click. | ADR-0002. |
| **The act** | Draft + **click-to-WhatsApp** (`wa.me`); requires E.164 normalization; the click is an *attempt*, logged as **action initiated**, not delivery, not resolution. | `wa.me` returns no delivery truth. |
| **AI role** | Copilot **orchestrates need + reason to the front desk**; never messages the patient; the human always sends. Clinical findings pass a **doctor-approval gate** first. | LGPD + safety; human-in-the-loop. |
| **LGPD** | The care relationship (patient/lead) **is** the legal basis (LGPD Art. 7). No consent capture. Explicit-objection opt-out **deferred**. | No marketing-consent machinery needed. |
| **Measurement** | Full Sinal lifecycle through the existing `Metric` machinery. Headlines: **taxa de reativação** and **valor associado a retornos** — observed association, never "recuperado pela IA". | Credibility is the SaaS retention lever. |
| **UI** | **Two dashboards:** front-desk = the live feed (`staff` default landing); analytics = owner metrics (`admin` default landing). Both reachable by both roles. | Different audiences, different densities. |
| **Clinical origin** | **AI → doctor approves → transferred to front desk.** No manual intake. | Doctor is the sole gate. |
| **Build order** | **A** (Funnel + recall) → **B** (Retention core) → **C** (Clinical core), all behind one `RaiseSinal` seam. | Never ship the feed empty; producers are additive. |

---

## 3. The `Sinal` entity (tenant DB)

A single tenant table `sinais` (or `copilot_signals` if we prefer English column names — TBD at build,
following existing table-naming convention). Indicative shape:

| Field | Type | Notes |
|---|---|---|
| `id` | uuid/id | |
| `patient_id` | fk → patients | the subject |
| `type` | enum | `recall`, `no_show`, `quote_followup`, `clinical` |
| `tier` | enum | `critica`, `alta`, `media`, `baixa` — set by the producer (ADR-0001) |
| `status` | enum | `pendente`, `em_andamento`, `resolvido`, `dispensado`, `adiado` |
| `value` | decimal(10,2) nullable | the **price tag** — typed estimate, nullable (clinical) |
| `value_kind` | enum nullable | what the R$ *is*: `ltv`, `procedure`, `budget` — drives the UI label |
| `reason` | string | internal explanation shown to the front desk |
| `draft_message` | text nullable | suggested patient message (revenue types); the human edits/sends |
| `doctor_id` | fk nullable | for clinical Sinais — who approved the finding |
| `due_at` | datetime nullable | when it became actionable |
| `snoozed_until` | datetime nullable | set by `adiar` |
| `context` | json nullable | producer-specific payload (e.g. budget_id, appointment_id) for auto-resolution |
| timestamps | | |

**Invariants**
- Revenue Sinais (`recall`/`no_show`/`quote_followup`) are **not raised for a patient in active care**.
- At most one **open** (`pendente`/`em_andamento`) Sinal per `(patient, type)` — producers update, not duplicate.
- A `clinical` Sinal is exempt from the active-care suppression and always carries `tier ∈ {critica, alta}`.
- `draft_message` for a `clinical` Sinal is **logistics-only** — the drafter is never given clinical detail
  (structurally starved of context, not prompt-restrained). The doctor's finding stays internal (`reason`).

`Sinal` is a **fat Eloquent model** (relationships + simple invariants + the `inActiveCare` check delegated
to the patient/pipeline). No repository wrapper.

---

## 4. The `RaiseSinal` seam

One invokable Action — `App\Actions\Copilot\RaiseSinal` — is the **only** way a Sinal is created. Every
producer calls it with typed input (a readonly DTO), e.g.:

```
RaiseSinal(new RaiseSinalData(
    patient: $patient,
    type: SinalType::Recall,
    tier: SinalTier::Media,
    value: $patient->ltv,
    valueKind: ValueKind::Ltv,
    reason: 'Sem visita há 8 meses.',
    context: [...],
))
```

`RaiseSinal` enforces the shared rules in one place: the **active-care suppression gate** (skipped for
`clinical`), the **one-open-per-(patient,type)** dedup (update vs. create), and emitting a `SinalRaised`
event (→ metric). Producers stay dumb; the seam owns policy. The consumer (feed) never knows who raised what.

### Producers

| Producer | Phase | Trigger | Tier | Value |
|---|---|---|---|---|
| **Recall** | A | scheduled scan over `Patient::needingRecall()` (status active/finished, overdue, **not in active care**) | `media`/`alta` by overdue-ness | patient LTV (`ltv`) |
| **No-show recovery** | B | `AppointmentNoShow` / cancellation domain event | `alta` | missed procedure price (`procedure`) |
| **Quote follow-up** | B | budget `sent`/`negociando` stalls past a threshold | `media` | budget total (`budget`) |
| **Clinical** | C | AI exam analysis → **doctor approves** → transfer | `critica`/`alta` | none |

---

## 5. Lifecycle & resolution

```
        producer ──RaiseSinal──▶  pendente
                                     │  attendant acts (click-to-WhatsApp)
                                     ▼
                                  em_andamento ──adiar──▶ adiado ──(date)──▶ pendente
                                     │                        │
              real outcome event ───┤                        └─dispensar─▶ dispensado
              (re-enter pipeline /  │
               book / approve)      ▼
                                  resolvido
```

- **Active care = a pipeline card in `primeiro_contato`…`retorno`** (ADR-0002). `concluido` and `desistentes`
  and *no card* are **not** active care — those are the Sinal candidates.
- **Acting ≠ resolving.** Click-to-WhatsApp moves `pendente → em_andamento`, logs a Timeline Event
  (`whatsapp`, phrased *"Contato por WhatsApp iniciado"*), records a `sinal_actioned` metric. The Sinal
  stays open.
- **Resolution is event-driven.** Listeners on the domain events we already emit (`AppointmentScheduled`,
  `BudgetApproved`, pipeline re-entry) close the matching open Sinal automatically — no manual "done" tick.
  This is what makes the feed feel agentic rather than a stale to-do list.
- `adiar` sets `snoozed_until` (suppressed from the feed until then); `dispensar` closes as `dispensado`.

---

## 6. The act — click-to-WhatsApp

- **`App\Support\Phone`** — a normalizer sibling to `App\Support\Money`. Clinic input → E.164, `+55`
  default, Brazilian 9th-digit mobile rules. Unit-tested against messy real inputs.
- If a number **can't** be normalized, the Sinal still surfaces but the WhatsApp button is **disabled**
  with *"número inválido — atualize o cadastro"*. Never generate a broken `wa.me` link.
- The Timeline Event records the **action taken** (*"Contato por WhatsApp iniciado"*), never a delivery
  claim — `wa.me` hands off to WhatsApp and returns nothing observable.

---

## 7. Copilot (AI) boundary

- The AI **never contacts the patient and never sends anything.** It raises the Sinal (orchestrates the
  *need*) and supplies the **reason** to the front desk (internal).
- For **clinical** Sinais: AI analyzes an exam → **signals the doctor** → the doctor reviews & approves →
  the approved finding is transferred to the front desk as a `clinical` Sinal. The doctor is the sole gate;
  the AI never reaches the front desk directly.
- LLM access sits behind an **agnostic `CopilotDrafter` interface** (Laravel AI / LLM-agnostic layer; exact
  package chosen at PRD-B/C planning). Mockable in tests, metered, feature-gated as a premium capability.
- The clinical drafter is **structurally starved of clinical context** — the worst it can produce is a
  logistics message. Clinical detail lives in the staff-only `reason`, never in `draft_message`.

---

## 8. Two dashboards

- **Front-desk dashboard (`painel da recepção`, `staff` default):** *is* the live Sinal feed — ranked by
  tier then in-tier score, clinical-critical as its own lane on top, act/snooze/dismiss buttons, status.
  Replaces today's hardcoded "Recalls urgentes" card with a real `Sinal`-backed worklist.
- **Analytics dashboard (`painel do dono`, `admin` default):** read-mostly owner metrics — taxa de
  reativação, valor associado a retornos, pipeline value, no-shows. No act buttons.
- Role sets the **default landing only**; both routes reachable by both roles. Both read the same `Sinal`
  query — the analytics page aggregates, the feed renders a worklist (no duplicated logic).

---

## 9. Measurement

- Instrument the full lifecycle via the existing `Metric` machinery / `RecordDomainMetric` listener:
  `sinal_raised`, `sinal_actioned`, `sinal_resolved`, `sinal_dismissed` (type + value in payload).
- **Taxa de reativação** = `resolvido ÷ ever-actioned` (Sinais that reached `em_andamento` at least once,
  whether or not they later resolved) — the clinic's outreach effectiveness, no causal AI claim.
- **Valor associado a retornos** = summed value of patients who returned within a window (e.g. 30 days)
  *after* a front-desk contact — framed as observed association (*"R$ X em valor associado a retornos após
  contato"*), never *"recuperado pela IA"*.
- Surface the gap too (raised-but-unworked, acted-but-unresolved) — honest, and it drives behavior.

---

## 10. LGPD posture

- Legal basis is the **care relationship** (patient/lead), LGPD Art. 7 — not consent. No opt-in capture,
  no consent column, producers don't check one.
- The patient's **right to object** (an explicit "stop messaging me") is acknowledged but **deferred** to a
  later slice (a manual "não contatar" flag). Until then the front desk handles objections by moving the
  patient to `desistentes`.
- Patient PII (photo, contact) stays auth-gated per existing routes; the Sinal feed is tenant-isolated.

---

## 11. The three PRDs & build order

All behind one `RaiseSinal` seam and one feed. Each slice is a tracer bullet through every layer.

**PRD-A — Front-desk Funnel + Recall (fused).** The feed exists *and* has a live producer on day one.
Recall Sinais from `Patient::needingRecall()` (active-care-gated) → feed → act (WhatsApp / snooze /
dismiss) → resolve on pipeline re-entry. Front-desk dashboard becomes the feed; `Phone` normalizer; lifecycle
metrics. Verifiable end-to-end with data that already exists.

**PRD-B — Retention core.** Adds the `no_show` and `quote_followup` producers as domain-event listeners
behind the same seam. Feed unchanged. Analytics dashboard (reativação + valor associado). LLM drafting
(`CopilotDrafter`) for revenue messages.

**PRD-C — Clinical / Diagnostic core.** Heaviest, last. Exam upload → AI analysis → **doctor approval
queue/gate** → raises a `clinical` Sinal into the *same* feed and its critical lane. Purely additive to the
consumer. Logistics-only clinical drafter.

---

## 12. Testing approach

Mandatory TDD (Pest, red→green→refactor), feature-test-first, with `stancl/tenancy` isolation.

- **Actions** (`RaiseSinal`, producers, resolution listeners) get focused unit tests: suppression gate,
  dedup (one-open-per-(patient,type)), tier assignment, auto-resolution on each domain event.
- **`App\Support\Phone`** unit-tested against messy Brazilian inputs (missing `+55`, 8- vs 9-digit, formatting).
- **Feed** (Livewire) feature tests: tier ordering, clinical lane on top, act → `em_andamento` + timeline +
  metric, snooze/dismiss, button disabled on bad phone.
- **Dashboards** feature tests: role-based default landing; both reachable; metrics math (reativação,
  associated value within window).
- **Copilot** tests mock the `CopilotDrafter`; assert the clinical drafter is never given clinical context.

---

## 13. Deferred / open

- Explicit-objection opt-out ("não contatar" flag) — later slice.
- Exact LLM package (Laravel AI vs alternative) — decided at PRD-B/C planning.
- Per-channel consent, double-opt-in — out of scope (registration concern, not this feature).
- English vs Portuguese column/table names — follow existing convention at build.
- PRD-C internals (exam upload UX, AI analysis contract, doctor approval queue) — its own design pass
  before PRD-C is built.
