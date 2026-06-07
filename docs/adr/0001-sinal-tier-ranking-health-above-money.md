---
status: accepted
---

# Sinais rank by priority tier, with health hard-gated above money

The front-desk feed orders Sinais by a **priority tier** enum (`critica → alta → media → baixa`) set by the producer, and only uses the price tag (R$ at stake) to break ties *within* a tier. Clinical Sinais (a doctor-approved finding) enter at `critica`/`alta` **by rule**, so a high-value revenue Sinal can never visually outrank a health flag. We chose this over the obvious single blended score (`value × urgency`) because that score lets money bury a health issue — the one failure a clinic cannot forgive — and because it makes the safety invariant a tuning accident waiting to happen.

## Considered options

- **Single numeric `priority = value × urgency`, clinical given a large constant.** Rejected: works until two clinical Sinais need ordering, or until someone retunes the recall weight and a fat budget leaks above a health flag. The invariant lives in a magic number instead of the structure.
- **Tier first, value as in-tier tiebreaker (chosen).** The "money never outranks health" invariant is structural — a revenue Sinal is in a lower band, full stop.

## Consequences

- Producers must set a tier when raising a Sinal; the tier is part of the `RaiseSinal` contract, not derived from money.
- The feed query orders by tier, then by an in-tier score — never by a single global score.
- The price tag is heterogeneous and sometimes absent (clinical Sinais have none), so it *cannot* be the primary sort key anyway. The tier carries that weight instead.
