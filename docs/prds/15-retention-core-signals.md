# PRD-15 · Retention Core — No-show & Quote Signals + Drafting

**Phase:** v1.1 · **Depends on:** PRD-14 (the `RaiseSinal` seam, the feed, lifecycle, metrics),
PRD-4 (scheduling — the no-show/cancellation events), PRD-5 (financial — budget status events) ·
**Consumed by:** PRD-16 (rides the same drafting + analytics surfaces).
**Design:** `../superpowers/specs/2026-06-07-frontdesk-copilot-sinal-design.md`.

> Written just-in-time: detail-complete before build. The LLM package choice is finalized at this PRD's
> planning (the drafter sits behind an agnostic interface, so the choice is contained).

## Why this PRD

PRD-14 proved the feed with one cheap producer. This PRD adds the two remaining **revenue** producers —
**no-show recovery** and **quote follow-up** — and the **LLM drafting** that turns a Sinal's reason into a
suggested (human-edited, human-sent) message. It also stands up the **analytics/owner dashboard** that
charts the metrics PRD-14 began recording, so the *clinic* can see the tool earns its keep. Every producer
is additive behind `RaiseSinal`; the feed and lifecycle are untouched.

## Scope

**In:** the **no-show producer** (listener on the scheduling no-show/cancellation event); the **quote
follow-up producer** (budget stalled in `orcamento_enviado`/`negociando` past a threshold); the
`CopilotDrafter` agnostic interface + a concrete LLM implementation (mockable, metered, premium-gated)
producing `draft_message` for revenue Sinais; outcome-resolution listeners for both new types
(`BudgetApproved`, appointment booked); the **analytics dashboard** (taxa de reativação, valor associado
a retornos, raised/worked/unworked gaps) as `admin`'s default landing.

**Out:** clinical signals / doctor approval / exam AI (PRD-16); the "não contatar" opt-out (deferred);
real WhatsApp delivery integration (still `wa.me` click — no delivery API in scope).

## Producers added (behind `RaiseSinal`)

| Producer | Trigger | Tier | Value / kind | Resolves on |
|---|---|---|---|---|
| **No-show recovery** | scheduling no-show / cancellation domain event | `alta` | missed procedure price / `procedure` | patient re-books (appointment scheduled) |
| **Quote follow-up** | budget stalls in `orcamento_enviado`/`negociando` past N days (scheduled scan) | `media` | budget total / `budget` | `BudgetApproved` / re-negotiation movement |

- Both still pass the **active-care suppression gate** — but note a no-show often *drops* the card to
  `desistentes` (PRD-3 automation), which is precisely *not* active care, so the Sinal raises correctly.
- Dedup unchanged: one open Sinal per `(patient, type)`.

## Key decisions

- **`CopilotDrafter` is an interface, not a vendor.** `draft(SinalDraftContext): string`. The context for
  revenue Sinais carries only non-clinical data (name, last visit, procedure, budget value, clinic name).
  Swapping LLM providers is a one-class change. Tests mock it.
- **Drafting is a suggestion.** The draft populates the WhatsApp message the attendant **edits and sends**;
  the AI never sends. A failed/slow draft degrades gracefully — the Sinal still works as a plain task with
  the `reason`, button still functional.
- **Metered + premium-gated.** Draft calls are counted (cost control) and gated behind a feature flag so
  drafting can be sold as a premium capability without blocking the free feed.
- **The analytics dashboard charts, never claims causation.** taxa de reativação (`resolvido ÷
  ever-actioned`) and valor associado a retornos (windowed, observed association) — never "recuperado pela
  IA". Reads the same `Metric` rows PRD-14 records.

## Design

- **Analytics dashboard** (`admin` default): KPI cards (reativação %, valor associado 30d, Sinais
  trabalhados, gap: raised-but-unworked), simple trend over time. `<x-ui.*>` charts (no Flux Pro, no
  React). Read-only — no act buttons (those live on the front-desk feed).
- **Drafting in the feed:** a "sugerir mensagem" affordance on a revenue Sinal fills the editable message
  before the `wa.me` hand-off. Latency-tolerant (Alpine loading state).

## Build slices (tracer bullets, TDD)

1. **No-show producer** — listener on the scheduling no-show/cancellation event → `RaiseSinal(no_show, …)`;
   resolves on re-book. Feature-tested through the event.
2. **Quote follow-up producer** — scheduled scan for stalled budgets → `RaiseSinal(quote_followup, …)`;
   resolves on `BudgetApproved`. Feature-tested.
3. **`CopilotDrafter` + drafting** — the interface, a concrete impl behind a flag, mocked in tests; the
   feed's "sugerir mensagem" fills the editable draft; graceful degradation on failure.
4. **Analytics dashboard** — `admin` default landing; reativação + valor associado + gaps from the metric
   rows; tenant-isolated; numbers match a known fixture.

## Testing

- **Producers (feature):** each event/scan raises the right Sinal with correct tier/value_kind; active-care
  gate respected; dedup holds; correct outcome event resolves it.
- **Drafter:** mocked in producer/feed tests; the revenue draft context never contains clinical data;
  failure degrades to a plain-task Sinal.
- **Analytics (feature):** reativação and valor-associado math against a fixture; window boundary correct;
  no causal-claim copy; role-based default landing.
- **Browser pass** for the drafting affordance (async Alpine) and the dashboard charts.

## Deferred

Clinical signals + doctor approval + exam AI (PRD-16) · "não contatar" opt-out · real WhatsApp delivery API.
