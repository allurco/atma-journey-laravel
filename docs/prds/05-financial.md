# PRD-5 — Financial

> **Status:** in progress · **Phase:** MVP (the last MVP PRD) · **Depends on:** PRD-1 (Procedures), PRD-2 (Patients), PRD-3 (Pipeline)
> **Source of truth:** `../atma-journey-ts/src/services/BudgetService.ts` + `FinancialService.ts` (rules) +
> `../atma-journey-ts/src/components/FinancialCheckout.tsx` (design).

## Why this PRD

Financial closes the **money** half of the retention loop: a budget (orçamento) is what the clinic
sends a prospect, its approval is what advances them through the pipeline, and the paid transaction is
what fills the patient's **LTV** — the headline number PRD-2 created but left for us to write. It's the
last MVP PRD; with it, ATMA can attract, retain, schedule, **and bill**.

## Scope

**In:** the `Budget` + `BudgetItem` + `Transaction` models, the **budget builder** (pick a patient,
add procedure line items, auto-total), the budget **status flow** (draft → sent → approved →
completed), the **budget → pipeline** automation (sending creates/links a card; `BudgetApproved` →
`MovePipelineCardForward`), **converting** an approved budget to a transaction, **marking a transaction
paid** → the patient **LTV** rollup, and a **Financeiro** page (budgets + transactions). The sidebar
**Financeiro** goes live.

**Out (owned elsewhere):** the real payment gateway / Pix / boleto (PRD-12) — transactions are recorded
manually here; revenue KPIs + charts (PRD-9 dashboard) — PRD-5 shows simple totals only; recurring
billing/subscriptions (PRD-13); messaging the budget to the patient (PRD-7). Procedure catalog CRUD
already exists (PRD-1 Settings) — budgets *consume* it.

## Entities (tenant DB)

| Table | Columns | Notes |
|---|---|---|
| `budgets` | `patient_id (fk cascade), total (decimal), status, notes` | Header; items in `budget_items`. |
| `budget_items` | `budget_id (fk cascade), procedure_id (fk null), name, unit_price (decimal), quantity (int), discount (decimal)` | Line = `unit_price × quantity − discount`. |
| `transactions` | `patient_id (fk cascade), budget_id (fk null), total (decimal), payment_method, status` | Recorded sale; `status` pending → paid. |
| `transaction_items` | `transaction_id (fk cascade), name, price (decimal), discount (decimal)` | Snapshot of the budget lines at conversion. |

- **`BudgetStatus` enum**: `Draft='draft'`, `Sent='sent'`, `Approved='approved'`, `Completed='completed'`.
- **`PaymentMethod` enum**: `Credit='credit'`, `Debit='debit'`, `Cash='cash'`, `Pix='pix'`.
- **`PaymentStatus` enum**: `Pending='pending'`, `Paid='paid'`.
- All values match the TS app; all carry PT `label()` + badge classes.

## Key decisions

- **Totals are derived, never trusted from input.** A budget's `total` = `Σ(unit_price × quantity −
  discount)` recomputed whenever items change (in the action), like the TS app. Same for transactions.
- **Sending a budget puts the patient on the pipeline.** On `draft → sent`, create (or link) a
  `PipelineCard` in `orcamento_enviado` with `budget_id` set and `value = budget.total` — writing the
  `budget_id` column PRD-3 created. Reuses the one-card-per-patient rule (replace any existing card).
- **Approval advances the card — through the PRD-3 seam.** `BudgetApproved` → `MovePipelineCardForward`
  listener moves the linked card forward to `orcamento_aceito` via `MoveCardToStage`, so the status-sync
  (→ Ativo) and timeline event fire for free. No new mover logic.
- **Budget → Transaction is a guarded conversion.** Only `approved`/`completed` budgets convert (else
  throw); conversion snapshots the line items into `transaction_items`, creates a `pending` transaction,
  and flips the budget to `completed` — ported from `BudgetService.convertBudgetToTransaction`.
- **Marking a transaction paid fills LTV.** `pending → paid` adds the transaction `total` to the
  patient's `ltv` (the denormalized column PRD-2 created and the detail already shows). Idempotent —
  paying an already-paid transaction is a no-op.
- **Build the line-item editor + checkout ourselves** as `x-ui.*` (Blade + Alpine), no Flux Pro. Any
  JS follows the PRD-3 rule: a Vite-bundled resource + a Livewire bridge, never a CDN/inline blob.

## Design (port from the TS app)

- **Budget builder / checkout** ports `FinancialCheckout.tsx`: a patient picker, a line-item table
  (procedure select → unit price, quantity, discount, line total), a running total, notes, and the
  status actions. Brand: teal, `#FAFAF8`, rounded-`xl`.
- **Financeiro list**: budgets with patient, total, status badge; transactions with payment method +
  paid/pending badge; a simple revenue strip (total / paid / pending). Patient detail gains a financial
  summary (budgets + transactions + LTV).

## Build slices (tracer bullets, TDD)

1. **Budget builder** — `Budget`/`BudgetItem` models + migrations + `BudgetStatus`/`PaymentMethod`/
   `PaymentStatus` enums + the **Financeiro** page listing budgets + create/edit a budget (patient +
   procedure line items with quantity/discount, auto-total) + status transitions (draft → sent →
   approved → completed). Sidebar **Financeiro** goes live. (No pipeline/transaction wiring yet.)
2. **Budget → pipeline** — sending a budget (`draft → sent`) creates/links a `PipelineCard` in
   `orcamento_enviado` (`budget_id`, value = total); a `BudgetApproved` event → `MovePipelineCardForward`
   listener advances the linked card to `orcamento_aceito` via `MoveCardToStage` (status-sync + timeline
   for free).
3. **Budget → transaction + mark paid + LTV** — `Transaction`/`TransactionItem` models + the guarded
   `ConvertBudgetToTransaction` action (approved/completed only → pending transaction + snapshot items +
   budget → completed) + **mark paid** → patient `ltv += total` (idempotent).
4. **Financeiro overview + patient financials** — the transactions list + a revenue strip
   (total/paid/pending) on the Financeiro page, and a **financial section on the patient detail**
   (budgets + transactions + LTV), plus an **"orçamento" entry point** from the patient detail.

*Dependency note:* 1 → 2, 1 → 3 (2 and 3 both build on the budget; 3 needs the approved status 2 also
uses); 4 surfaces 1–3. 2 and 3 are independent of each other.

## Testing

- Feature (TenantTestCase): budget total recompute; status transitions; sending creates/links a card in
  `orcamento_enviado`; `BudgetApproved` advances the card (+ patient Ativo + timeline); convert guarded
  (non-approved throws); convert snapshots items + flips budget completed; mark paid adds to LTV
  (idempotent); tenant isolation.
- Unit: enum labels; total/line math.
- Automation: approve → card forward; the budget_id link is set on send.
- **Browser pass** for the budget builder (line-item editor) and the Financeiro page.

## Deferred

Payment gateway / Pix / boleto (PRD-12) · revenue KPIs + charts (PRD-9) · subscriptions/billing
(PRD-13) · messaging the budget (PRD-7) · partial payments / installments. PRD-5 records money and
drives the pipeline + LTV; real collection and analytics arrive with their owning PRDs.
