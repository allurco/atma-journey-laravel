# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`CONTEXT.md`** at the repo root — the domain glossary / ubiquitous language.
- **`docs/superpowers/specs/2026-06-05-atma-journey-program-design.md`** — the authoritative architecture
  spine (tenancy model, layered architecture, event map, full entity glossary, PRD map).
- **`docs/prds/README.md`** — the PRD collection and build order; then the specific `docs/prds/NN-*.md`
  for the area you're working in.
- **`docs/adr/`** — read ADRs that touch the area you're about to work in.

If any of these files don't exist, **proceed silently**. Don't flag their absence or suggest creating
them upfront. The producer skill (`/grill-with-docs`) creates ADRs lazily when decisions get resolved.

## File structure

Single-context repo:

```
/
├── CONTEXT.md
├── docs/
│   ├── adr/                         ← architectural decision records (created lazily)
│   ├── prds/                        ← one PRD spec per bounded context
│   └── superpowers/specs/           ← program-level design docs
└── app/                             ← Laravel application code
```

## Use the glossary's vocabulary

When your output names a domain concept (an issue title, a refactor proposal, a hypothesis, a test
name), use the term as defined in `CONTEXT.md` — the Portuguese domain terms where they are canonical
(e.g. *prontuário*, *orçamento*, pipeline stage keys like `orcamento_enviado`). Don't drift to synonyms.

If a concept isn't in the glossary yet, that's a signal — either you're inventing language the project
doesn't use (reconsider), or there's a real gap (note it for `/grill-with-docs`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR-0003 (database-per-tenant) — but worth reopening because…_
