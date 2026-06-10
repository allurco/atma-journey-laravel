# PRD-17 · Patient Insights

**Phase:** v1.1 · **Depends on:** PRD-2 (patient + Visão geral + timeline), PRD-4 (appointments),
PRD-5 (budgets/transactions), PRD-6 (prontuário: anamnese, evolução, receitas, exames, documentos);
**the doctor AI slice additionally depends on PRD-15** (the agnostic AI layer). **Supersedes:** the
*"IA para resumo de prontuário"* backlog card — that scope is absorbed here. **Consumed by:** the doctor
and the front desk (a read surface, not other PRDs).

## Why this PRD

A patient's story is already in the system — scattered across appointments, evolução, exames, documentos
and finance. Whoever opens a patient has to reassemble it by hand. This PRD collapses that into **two
glance surfaces, one per audience**:

- the **doctor** gets an **AI-summarized Resumo** of the clinical chart (the "antes e durante a consulta"
  view) — the *"IA para resumo de prontuário"* card, folded in here;
- the **front desk** gets a **redesigned Visão geral** that leads with the *actionable* operational state
  (pendências, funil, agenda, fila) over the static facts.

Same data lake, **different gates, densities, and — crucially — AI boundary**: the AI clinical summary
lives **only** in the doctor's context; the front desk never sees an AI clinical summary, only structured
operational data. This mirrors the trilogy's rule that clinical AI stays in clinical context.

## Scope

**In:**
- **Doctor — Resumo tab (AI):** a new first tab in the **Prontuário** showing a precomputed, model-
  generated summary of the chart, with structured **alertas** and grounded AI **pontos de atenção**.
- **Front desk — Visão geral redesign (no AI):** the patient detail overview tab in **Pacientes**,
  redesigned insights-first (pendências/funil/agenda/fila + the existing PRD-2 facts), assembled by a
  pure-aggregation query action.
- The AI engine: a `ChartSummarizer` task interface on **PRD-15's agnostic AI layer**, observers + a
  debounced queued job, an audited summary table, and a per-clinic opt-in.

**Out:** the data itself (every source PRD keeps owning its writes); patient-facing drafts/messages
(PRD-15/16); cross-patient dashboards/KPIs (PRD-9); the Copilot recall feed (PRD-14).

## Entities (tenant DB)

| Table / field | Columns | Notes |
|---|---|---|
| `patient_chart_summaries` (new) | `patient_id (fk cascade), summary (text), highlights (json), model (string), covers_through (timestamp), generated_at (timestamp)` | **Audited** — one row per generation, latest = newest. `highlights` = the grounded *pontos de atenção* (each `{text, source}`). `covers_through` = max `updated_at` of clinical records at generation (staleness check). |
| `clinics.ai_summary_enabled` (new column) | `boolean default false` | Per-clinic **opt-in** (LGPD). Observers/job no-op unless true. |

No other new tables — the **front-desk slice is pure aggregation** over existing models.

## Data sources (read-only aggregation)

| Insight | Source |
|---|---|
| AI chart summary + pontos de atenção | `patient_chart_summaries` (latest row) |
| Alertas (hard) | `Patient.allergies` (json) + `SpecialCondition` |
| Última visita / nº visitas / faltas | `Patient.last_visit_date`, `total_appointments`, `missed_appointments` |
| Próximo / último agendamento | `Appointment` |
| Funil + último contato | `PipelineCard` (`stage`, `last_contact`, `contact_type`) |
| Pendências — assinatura | `PatientDocument` where `signature_status = Pendente` |
| Pendências — orçamento | `Budget` where status = **`Sent`** (enviado, sem resposta) |
| Pendências — pagamento | `Transaction` where `PaymentStatus = Pending` |
| Está na fila? | `WaitlistEntry::open()` |
| Resumo input (chart) | `Anamnesis`, `ClinicalNote`, `ExamResult`+`ExamFinding`, `Prescription` |

## Key decisions

| Area | Decision | Why |
|---|---|---|
| **Two surfaces** | Doctor → new **Resumo** tab in Prontuário (default on open). Front desk → **redesign** the Visão geral. | Different audiences/surfaces; the trilogy's clinical-AI-stays-clinical rule. |
| **AI is doctor-only** | The AI chart summary appears **only** in the Resumo tab. The front-desk Visão geral has **no AI**. | Recepção works operational data, not AI clinical summaries. |
| **Full-chart AI summary** | The Resumo summarizes the whole chart (anamnese + evoluções + exames + receitas). **Absorbs** the *"IA para resumo de prontuário"* card. | One engine, one surface; no duplicate AI-summary card. |
| **Model-agnostic** | Reuse **PRD-15's agnostic AI layer**; add a sibling **`ChartSummarizer`** (`summarize(ChartContext): SummaryResult`). No model/package pick here — PRD-15 owns it; tests mock it. | PRD-15 finalizes the LLM package; PRD-16 already reuses the layer. |
| **Precompute, event-driven** | **Eloquent observers** on `ClinicalNote`/`Anamnesis`/`ExamResult`(+`ExamFinding`)/`Prescription` dispatch a queued summary job; the tab reads the stored row instantly. | Observers catch every write path; a multi-second paid call can't run on page load. |
| **Debounce** | Each clinical write resets a ~30s debounce (cache token + delayed job that aborts if superseded); a whole consult coalesces into **one** call. | Cost ≈ one call per consult, not per write. |
| **Storage** | A dedicated **audited** `patient_chart_summaries` table (history, model, covers-through). | AI-authored clinical content needs a medical-legal/LGPD trail. |
| **Opt-in per clinic** | `ai_summary_enabled` (default **off**). Observers/job guard on it. | The clinic consents before clinical data leaves for the model. |
| **Grounded + safe** | Structured sections, "summarize-only, no inference, cite-your-source" prompt, mandatory UI disclaimer. | Hallucination on clinical data is dangerous. |
| **Two-tier alerts** | **Tier 1 alertas** = `allergies` + `SpecialCondition` (structured, authoritative, never AI). **Tier 2 pontos de atenção** = AI highlights, each **grounded with a chart source**, labeled "Sugerido por IA", no new diagnoses. | A safety flag can't be hallucinated *or* missed; the AI highlights, it never asserts. |
| **Failure handling** | On LLM failure: **keep the last good summary**, leave `generated_at`, retry with backoff; first-ever failure shows "Resumo ainda não disponível". Never blank the panel or block a write. | Resilience; staleness is visible via "atualizado em". |
| **Pendências financeiras** | `Budget = Sent` + `Transaction = Pending`. Exclude `Draft`/`Approved`/`Completed` and `Paid`. | The concrete "someone must act" items. |

## Design

- **Resumo tab (doctor):** the AI summary in structured sections (*Queixa/contexto · Evoluções recentes ·
  Exames · Medicações*), the disclaimer line, Tier-1 **alertas** (amber, structured) up top, Tier-2
  **pontos de atenção** (softer, each with its chart source + "Sugerido por IA"), and an "atualizado em
  DD/MM · Atualizar" affordance. A "Gerando resumo…" state on first generation.
- **Visão geral (front desk):** redesigned insights-first — Tier-1 alertas, then a **pendências** block
  (counts + links to resolve: Documentos / Financeiro), then funil + último contato, próximo/último
  agendamento, fila/no-show, then the PRD-2 facts (LTV, visitas, etc.) reorganized below. Teal, `#FAFAF8`,
  rounded-`xl`, `<x-ui.*>`.

## Build slices (tracer bullets, TDD)

1. **S1 — Insights da recepção** *(no AI; independent; value now)*. `BuildFrontdeskInsights(Patient):
   FrontdeskInsightsData` query action + DTO; redesign the Visão geral (alertas Tier-1 · pendências de
   assinatura/orçamento/pagamento + links · funil + contato · próximo/último agendamento · fila/no-show ·
   the PRD-2 facts). Pure aggregation. Carry/adapt the existing Visão geral tests.
2. **S2 — Motor de resumo por IA (backend)** *(depends on PRD-15's layer; built against a mock)*.
   `ChartSummarizer` interface + `SummaryResult` (summary + grounded highlights) + `patient_chart_summaries`
   migration/model + `clinics.ai_summary_enabled` + observers on the four clinical models + the **debounced**
   queued job (opt-in guarded, ShouldQueue, keep-last-good on failure, covers-through staleness). TDD with a
   **mocked** `ChartSummarizer` (no real LLM); the concrete impl arrives via PRD-15.
3. **S3 — Aba "Resumo" do médico (UI)**. The Prontuário **Resumo** tab (default on open): renders the
   stored summary + Tier-1 alertas (structured) + Tier-2 pontos de atenção (labeled/cited) + disclaimer +
   staleness/"Atualizar" + "Gerando…" state. Move the Prontuário default tab Anamnese → Resumo (carry the
   tab tests).

**Order:** S1 → S2 → S3. S1 ships immediately and AI-free; S2 stands the engine up behind a mock; S3 is
the doctor surface.

## Testing

- **S1 (feature/unit):** `BuildFrontdeskInsights` builds the right DTO — pendências count pending
  signatures + `Sent` budgets + `Pending` transactions; funil/agenda/fila reflect the models; alertas =
  allergies + special conditions; the Visão geral renders it; empty states; a doctor can't reach it (gate).
- **S2 (unit, mocked LLM):** observers dispatch the job on each clinical write; **debounce** coalesces a
  burst into one job; opt-in **off** ⇒ no job; the job stores an audited row with `covers_through`; LLM
  failure keeps the last good row; highlights carry a source. The `ChartSummarizer` is mocked — **no real
  model call in tests**.
- **S3 (feature):** the Resumo tab renders summary + Tier-1 alertas + Tier-2 pontos (with source +
  "Sugerido por IA") + disclaimer; stale/missing → "Gerando…"/"não disponível"; default tab is Resumo.
- **Browser pass** on both surfaces (aggregation-heavy Livewire).

## Deferred

The concrete LLM provider/model (PRD-15 owns it) · patient-facing drafts (PRD-15/16) · cross-patient
dashboards (PRD-9) · configurable/pinned insight cards · "Atualizar" rate limits beyond the debounce ·
critical-pending-exam auto-alerting. This PRD assembles two glances and stops there.
