# PRD-6 · Clinical / Prontuário (EHR)

**Phase:** v1.1 · **Depends on:** PRD-2 (patients — the hub the record hangs off), PRD-1 (doctors —
notes/prescriptions reference a profissional), PRD-2 timeline (`AppendTimelineEvent` seam) ·
**Consumed by:** PRD-10 (AI Exam Parsing — fills `exam_findings`), PRD-16 (Clinical Signals — reads the
uploaded exam, raises a clinical Sinal on a doctor-approved finding).
**Source of truth:** the TS app's clinical screens/services (`../atma-journey-ts/src/`).

## Why this PRD

Pacientes (PRD-2) is the *registry*; the **Prontuário** is the *clinical record* opened from a patient —
the doctor's working surface. It is a distinct nav item, deliberately deferred out of PRD-2. This PRD
builds the EHR: anamnesis (medical history), clinical notes per visit, prescriptions, and patient
documents — **including the exam-upload + storage seam** that the AI features (PRD-10 parsing, PRD-16
signals) later consume. It builds the **manual** record fully; AI extraction of exam values stays Phase 2.

## Scope

**In:** the **Prontuário** nav/route opened from a patient; **anamnese** (one per patient, structured
medical history); **clinical notes** (append-only per visit, optionally tied to an appointment + doctor);
**prescriptions** (create/list/print); **patient documents** (upload PDF/image to the tenant disk +
tenant-scoped serving, categorized — *exam* vs other); the **`exam_results`/`exam_findings`** tables with
**manual** entry; timeline events for clinical activity via the PRD-2 seam.

**Out (owned elsewhere):** AI extraction of structured values from an uploaded exam (**PRD-10**, Phase 2 —
this PRD stores the document + allows manual findings); clinical **Sinais** / doctor-approval queue
(**PRD-16** — reads what this PRD stores); messaging/chat (PRD-7); billing of procedures (PRD-5).
Finer clinical RBAC (medical vs reception) stays **deferred** — `admin`/`staff` only for now (clinical
staff are `staff`).

## Entities (tenant DB)

| Table | Columns (indicative) | Notes |
|---|---|---|
| `anamneses` | `patient_id (fk, unique), chief_complaint, history (text), medications (text), family_history (text), lifestyle (json), updated_by` | **One per patient.** The medical history. |
| `clinical_notes` | `patient_id (fk cascade), doctor_id (fk nullable), appointment_id (fk nullable), content (text), occurred_at` | Append-only; indexed `(patient_id, occurred_at)`. |
| `prescriptions` | `patient_id (fk cascade), doctor_id (fk), items (json), notes, issued_at` | `items` = array-shape `[{drug, dose, frequency, duration}]`. Rendered to **PDF** for print. |
| `patient_documents` | `patient_id (fk cascade), category, title, file_path, mime, size, uploaded_by, uploaded_at` | `category` enum incl. `exam`. Tenant private disk + scoped serving. |
| `exam_results` | `patient_id (fk cascade), patient_document_id (fk nullable), exam_type, collected_at, source` | Header for a set of findings. `source` = `manual` (PRD-6) or `ai` (PRD-10). |
| `exam_findings` | `exam_result_id (fk cascade), label, value, unit, reference_range, flag` | `flag` = `normal/high/low/critical`. Manual now; AI-filled in PRD-10. |

**Clinic prescription-print setting** (extends PRD-1 clinic settings, central or tenant clinic record):

| Setting | Type | Effect |
|---|---|---|
| `uses_custom_prescription_paper` | bool (default `false`) | When `true`, the PDF **suppresses the generated letterhead** (clinic prints onto its own pre-printed receituário) and reserves top space; when `false`, the PDF renders a generated letterhead (clinic logo/name/address + doctor name/CRM). |
| `prescription_header_margin_mm` | int nullable | Optional reserved top margin (mm) when custom paper is on. Sensible default if null. |

- **`DocumentCategory` enum:** `Exam`, `Report`, `Consent`, `Image`, `Other` (grows over time).
- **`ExamFindingFlag` enum:** `Normal`, `High`, `Low`, `Critical`.
- **`TimelineEventType`** gains clinical types as needed (e.g. a `prontuario`/`exam` entry) — extend the
  PRD-2 enum, don't fork it.

## Key decisions

- **The Prontuário is opened *from* a patient, as its own surface** — a route like
  `pacientes/{patient}/prontuario` (or a top-level Prontuário nav that takes a patient) with tabs:
  **Anamnese · Evolução (notes) · Receitas · Documentos/Exames**. Pacientes stays the registry.
- **Documents reuse the established upload pattern** exactly — private `local` disk (tenant-suffixed) +
  a tenant-scoped serving route/controller (the PRD-1 logo / PRD-2 photo plumbing). Clinical PII is
  auth-gated; cross-tenant fetch impossible. **No public disk.**
- **The exam upload is the seam the AI consumes.** PRD-6 stores the exam as a `patient_document`
  (`category = exam`) and optionally a `manual` `exam_result`. **PRD-10** adds AI extraction that fills
  `exam_findings`; **PRD-16** reads a finding the doctor approved and raises a clinical Sinal. PRD-6 owns
  *storage + manual entry*, nothing AI.
- **Clinical activity writes to the timeline** through the PRD-2 `AppendTimelineEvent` action (the one
  seam) — a new note or uploaded exam appears on the patient timeline. Keeps "no patient forgotten" in one place.
- **Anamnese is one-per-patient, edited in place** (upsert), not append-only — it's the current history,
  not a log. Notes and prescriptions *are* append-only/event-like.
- **Prescriptions render to a PDF for print, with a clinic letterhead toggle.** A `prescriptions/{id}/pdf`
  route streams a generated PDF. The layout respects `uses_custom_prescription_paper`: **off** → generated
  letterhead (clinic logo/name/address + doctor name/CRM, footer signature line); **on** → blank top
  (suppress our header, reserve `prescription_header_margin_mm`) so it overlays the clinic's pre-printed
  receituário. The PDF is generated on demand (not persisted) unless we later choose to archive it as a
  `patient_document`. **Requires a server-side PDF dependency — needs approval** (see below).
- **PDF library: `barryvdh/laravel-dompdf` (approved).** Pure PHP, no headless-Chrome/system binary,
  renders a Blade template to PDF; sufficient for a text-based receituário and the cheapest to run under
  per-tenant queues. (CLAUDE.md requires approval for dependency changes — granted for this package.)
- **Staff can manage the prontuário** (clinical staff are `staff`) — not admin-gated. Finer RBAC deferred.

## Design (port from the TS app)

- Port the prontuário/medical-record screens from the TS app's clinical components
  (`../atma-journey-ts/src/components/`) — replicate layout/UX in Blade + Tailwind (no React). Confirm the
  exact component names at build. Brand: teal, `#FAFAF8`, rounded-`xl`, `<x-ui.*>` for any Pro-gated
  control (file upload, tabs, combobox for doctor/exam-type select).
- **Evolução (notes)** reads like a clinical timeline; **Documentos/Exames** is a list with category
  filter + the upload control + inline exam-finding entry.

## Build slices (tracer bullets, TDD)

1. **Prontuário shell + Anamnese** — the Prontuário route opened from a patient + the tabbed shell +
   `anamneses` model/migration/factory + the Anamnese tab (upsert one-per-patient medical history). Nav live.
2. **Evolução — clinical notes** — `clinical_notes` model/migration/factory + the Evolução tab (append a
   note, optionally tied to a doctor/appointment) + a timeline event via `AppendTimelineEvent`.
3. **Receitas — prescriptions (CRUD)** — `prescriptions` model/migration/factory + the Receitas tab
   (create/list, doctor select, `items` array, notes, issued_at).
4. **Receitas — PDF + own-paper config** — add `barryvdh/laravel-dompdf`; a `prescriptions/{id}/pdf` route
   streaming a generated PDF (Blade template → PDF); the `uses_custom_prescription_paper` (+
   `prescription_header_margin_mm`) clinic setting + its control; the PDF layout honours it (generated
   letterhead vs blank-top for pre-printed receituário).
5. **Documentos & Exames — upload** — `patient_documents` + `DocumentCategory` enum + upload (PDF/image,
   size-limited) to the tenant disk + a tenant-scoped serving route/controller + the Documentos tab with
   category filter. **This is the exam-upload seam PRD-16 needs.** Mirrors PRD-1/PRD-2 file handling.
6. **Exam results (manual)** — `exam_results` + `exam_findings` + `ExamFindingFlag` enum + manual
   finding entry against an uploaded exam document (`source = manual`). Schema is ready for PRD-10's AI to
   fill `source = ai`.

*Ordering note:* slices 2, 3, 5 hang off slice 1 in parallel; 4 follows 3; 6 follows 5. **Slice 5 is the
hard dependency for PRD-16** and can be prioritized if the clinical-signals work is pulled forward.

## Testing

- **Feature (TenantTestCase):** anamnese upsert (one-per-patient); note append + timeline event written;
  prescription create; tenant isolation throughout; **staff can manage** the prontuário.
- **Prescription PDF:** the `pdf` route returns a `application/pdf` stream with the prescription content;
  **with `uses_custom_prescription_paper = false`** the letterhead (clinic name + doctor CRM) is present;
  **with `true`** the generated letterhead is absent and the top margin is reserved. Assert on rendered
  text / structure, not pixels.
- **Documents:** upload persists to the tenant disk + serves; **cross-tenant fetch denied** (mirror the
  PRD-1 logo / PRD-2 photo tests, incl. the real Livewire-upload path under tenancy).
- **Exam findings:** manual `exam_result` + `exam_findings` against a document; `flag` cast; the
  `source = manual` path leaves room for the AI path.
- **Browser pass mandatory** for the document upload and any Livewire-update-heavy tab — the
  tenancy/session/upload middleware stack isn't covered by unit tests.

## Deferred

- **AI exam parsing** (extract `exam_findings` from an uploaded exam) → **PRD-10** (Phase 2).
- **Clinical Sinais** + the doctor-approval queue → **PRD-16** (reads this PRD's stored exam + findings).
- Finer clinical RBAC (medical vs reception roles) · prescription e-signature / external integrations ·
  conversations/chat (PRD-7).
