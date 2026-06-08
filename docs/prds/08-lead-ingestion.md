# PRD-8 · Lead Ingestion

**Phase:** v1.1 · **Depends on:** PRD-2 (patients, `App\Support\Phone`, `AppendTimelineEvent`),
PRD-3 (pipeline — `primeiro_contato` card, `SyncPatientStatus`) ·
**Consumed by:** Pipeline (the funnel front door), Dashboard/Copilot (leads are the top of the funnel).

## Why this PRD

Atma is the clinic's **system of record for the patient journey** — "no patient is forgotten" only holds
if leads land *inside* Atma, owned by the Pipeline, from the very first contact. This PRD builds the
**inbound front door**: a per-clinic webhook that any lead source — Meta Lead Ads, Google, a website form,
Zapier, or another CRM — POSTs to, turning a raw lead into a `lead` patient at `primeiro_contato` with the
contact recorded on the timeline. It also hardens the identity the whole thing pivots on — the **phone
number** — with a change history so dedup stays reliable as patients change numbers.

**Direction is inbound only.** Atma *ingests*; it is the CRM, not a feeder to one. Pushing Atma events
*out* to an external CRM is a real future capability, but a different feature with a different goal — it
rides the **same per-clinic secret** and the **domain events we already emit** (`PipelineStageChanged`,
`BudgetApproved`, …), and belongs in a later **Outbound / Integrations** PRD, not here.

## Scope

**In:** a per-tenant lead webhook (`POST {clinic-domain}/webhooks/leads`), authenticated by a per-clinic
secret; a generic JSON payload (provider-agnostic); the `IngestLead` action (dedup by phone → create
`lead` patient + `primeiro_contato` card + timeline event + `LeadReceived` event, or record a re-contact
on a matched patient); idempotency via `external_id`; a `patient_phone_history` log + dedup that matches
current **and** past numbers; a clinic-settings panel exposing the webhook URL + secret (+ regenerate).

**Out (deferred / owned elsewhere):** **outbound** push to external CRMs (future Integrations PRD);
provider-native adapters (Meta signature verification, etc. — start provider-agnostic); real WhatsApp/chat
(PRD-7, deferred; PRD-11); a separate "leads view" (PRD-2's Pacientes index already filters by
`status = lead`); a dedicated manual lead-intake screen (PRD-2 patient create already makes a `lead`; an
optional "+ Lead" button reusing `IngestLead` may fold into card 1 if wanted).

## Entities (tenant DB)

| Table / column | Shape | Notes |
|---|---|---|
| `clinics.webhook_secret` | `string`, nullable | Per-clinic webhook secret, auto-generated; verified by the auth middleware. Rotatable from settings. |
| `lead_ingestions` | `id, source, external_id (nullable, indexed), phone, patient_id (fk nullable), matched (bool), payload (json), received_at` | Append-only audit of every inbound lead — debugging ("did it arrive?"), idempotency (dedup on `external_id`), and a tight record. |
| `patient_phone_history` | `id, patient_id (fk cascade), phone (E.164, indexed), source (registration\|lead\|edit), recorded_at` | Append-only log of every number a patient has held. `patients.phone` stays the **current** number. |

No new patient/pipeline tables — leads reuse `patients` (`status = lead`, `lead_source`),
`pipeline_cards` (`primeiro_contato`), and `timeline_events`.

- **`LeadSource`** — keep a free `lead_source` string (matches PRD-2: `website/meta/google/referral/manual…`);
  no enum (sources are open-ended).
- **`PhoneHistorySource` enum** — `Registration`, `Lead`, `Edit`.

## Key decisions

- **Per-tenant URL on the clinic domain + per-clinic secret.** `POST https://{clinic}.atma.test/webhooks/leads`,
  tenant resolved by the existing domain middleware. A middleware verifies a `webhook_secret` (header/token,
  constant-time compare); **CSRF-exempt** (it's a machine endpoint), **rate-limited**. No central routing
  table; isolation is structural. Each clinic self-serves its URL+secret from settings.
- **Provider-agnostic JSON.** Accept `{ name, phone, email?, source?, message?, external_id?, … }`. Any
  form/Zapier/Meta-via-Zapier can post; provider-native signature adapters are a later concern.
- **`IngestLead` is the one ingestion path** (action, typed `IngestLeadData`). Called by the webhook and
  (optionally) a manual "+ Lead" button. Behaviour:
  - **Normalize the phone** to E.164 (`App\Support\Phone`); reject a payload with no usable phone.
  - **No match** → create `lead` patient (+ seed `patient_phone_history`) + a `primeiro_contato`
    `pipeline_card` + a timeline event (*"Lead recebido via {source}"*) + fire `LeadReceived`.
  - **Match found** (current **or** historical phone, within tenant) → **never duplicate**: append a
    timeline event (*"Lead recebido via {source}"*), **enrich only empty fields** (email/name, `lead_source`
    if blank), and **do not** change status, stage, or create a second active card. A *historical-only*
    match adds *"Possível correspondência por telefone antigo"* (telecoms recycle numbers — surface, don't
    silently merge).
  - **Idempotency:** if `external_id` was already ingested, no-op (record the duplicate hit, take no action).
- **Phone identity is tracked, not just stored.** A `Patient` `saved` observer detects a dirty `phone`
  and appends a `patient_phone_history` row + a timeline event (*"Telefone alterado de X para Y"*). Seeded
  on patient create. Dedup matches **current → historical**. This retrofits PRD-2's registry edit, cheaply.
- **The clinic owns its integration.** Settings show the webhook URL + secret (masked, copyable) + a
  **regenerate** button (rotating invalidates the old secret) + a one-line "paste this into your lead source".

## Design

- **Webhook** is headless (no UI) — a controller + auth middleware. Returns `2xx` on accept (incl. dedup
  no-ops), `401` on bad secret, `422` on an unusable payload (no phone).
- **Settings panel** (new section in clinic settings, admin-only): URL, masked secret with copy + reveal,
  regenerate (with a confirm), and the integration blurb. Brand: teal, `#FAFAF8`, rounded-`xl`, `x-ui.*`.
- No TS app source — this is greenfield; the only ported rule is "lead → patient + card + timeline".

## Build slices (tracer bullets, TDD)

1. **Lead webhook (ingest) — the tracer.** `webhook_secret` on `clinics` (auto-generated) + the auth
   middleware; `POST {clinic}/webhooks/leads` → `IngestLead(IngestLeadData)`: normalize phone, dedup by
   **current** phone, create `lead` patient + `primeiro_contato` card + timeline event + `LeadReceived`
   event; matched phone → timeline event + enrich; `lead_ingestions` audit row + `external_id` idempotency.
   *(Optional: a "+ Lead" button reusing `IngestLead`.)*
2. **Phone history + hardened dedup.** `patient_phone_history` + `PhoneHistorySource` enum + the `Patient`
   `saved` observer (record change + timeline event), seeded on create (registry + webhook). Dedup now
   matches **current → historical**; historical-only match → timeline note. Retrofits PRD-2 edit.
3. **Webhook settings UI.** Clinic-settings panel: webhook URL + secret (masked/copyable) + **regenerate**
   + integration instructions. Admin-only (`manage-clinic-settings`).

## Testing

- **Webhook (feature):** a valid signed payload creates a `lead` patient + `primeiro_contato` card +
  timeline event + fires `LeadReceived`; a bad/missing secret → `401`; a payload with no phone → `422`;
  cross-tenant isolation (a secret for clinic A can't post to clinic B).
- **Dedup (feature/unit on `IngestLead`):** new phone → create; matching **current** phone → no duplicate,
  timeline event + enrichment, no stage/status change; matching **historical** phone → attach + the
  "telefone antigo" note; repeated `external_id` → idempotent no-op.
- **Phone history (feature):** seeded on create; editing a patient's phone appends a history row + a
  timeline event; `patients.phone` is the current number.
- **Settings (feature):** secret shown to admin; regenerate rotates it and the **old** secret stops
  authenticating; staff/guest blocked.
- **`App\Support\Phone`** reuse — messy inbound numbers normalize to E.164 before dedup.

## Deferred

**Outbound** push to external CRMs (future Integrations PRD, on the same secret + existing events) ·
provider-native adapters (Meta signature, etc.) · a dedicated leads view (PRD-2 filter covers it) ·
real chat/WhatsApp (PRD-7 deferred · PRD-11) · lead scoring / routing rules.
