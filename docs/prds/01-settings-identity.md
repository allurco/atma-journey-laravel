# PRD-1 — Settings & Identity

**Phase:** MVP · **Depends on:** PRD-0 · **Status:** spec
**Spine:** `docs/superpowers/specs/2026-06-05-atma-journey-program-design.md`
**Board label:** `Configurações` · cards land in `Backlog Geral`

---

## 1. Goal

The clinic's **Settings** area: configure the clinic profile and the **catalog entities** the rest of
the product builds on — specialties, procedures, and doctors. Ported from the TS `Settings` screen
(tabs: Clínica, Especialidades, Procedimentos, Médicos). The existing Fortify **Conta** (profile) and
**Segurança** (password) screens are restyled onto the new tenant layout. Staff invites/roles and
notification preferences are **deferred** (later PRDs).

These are the entities **Scheduling (PRD-4)** and **Financial (PRD-5)** depend on (a booking needs a
doctor + procedure; an orçamento needs procedures).

## 2. Entities (tenant database)

| Table | Columns | Notes |
|---|---|---|
| `clinic` (singleton) | name, email, phone, address, cnpj, logo_path (nullable), timestamps | One row per tenant; **source of truth for the clinic profile** |
| `specialties` | name, active (default true), timestamps | |
| `procedures` | name, base_price (decimal 10,2), duration (int, minutes), category (nullable), active, timestamps | |
| `doctors` | name, crm, phone, email, active, timestamps | **N:N** specialties |
| `doctor_specialty` | doctor_id, specialty_id | pivot (replaces the TS denormalized names array) |

Models: `Clinic`, `Specialty`, `Procedure`, `Doctor` (with `specialties()` belongsToMany). Factories +
seeders for each. All tenant-scoped (they live only in tenant DBs).

### Clinic name sync (central ⇄ tenant)
The `clinic` singleton is the source of truth. The central `tenants.name` is a **denormalized copy** kept
in sync for routing/sidebar/billing display: an `UpdateClinicProfile` action updates the singleton and,
when the name changes, calls `tenant()->update(['name' => …])`. **`RegisterClinic` (PRD-0) is extended**
to seed the `clinic` singleton (name from signup) when provisioning a tenant.

## 3. UI — the Settings section

A tenant route `/configuracoes` renders a **Settings shell**: left sub-nav tabs (ported from TS) +
the active panel. The app-shell sidebar's **Configurações** item (currently *em breve*) becomes active
and links here.

Tabs:
- **Clínica** — clinic profile form (name, cnpj, email, phone, address) + logo upload.
- **Especialidades** — list + create/edit/toggle-active specialties.
- **Procedimentos** — list + create/edit/toggle-active procedures (name, price, duration, category).
- **Médicos** — list + create/edit/toggle doctors, with a multi-select for specialties.
- **Conta** — the existing Fortify profile screen, restyled onto the tenant layout.
- **Segurança** — the existing Fortify password/2FA screen, restyled.

Design ported from the TS `Settings` (teal accents, `#FAFAF8`, rounded-xl inputs, the settings tab nav).
Each tab is a Livewire component under `app/Livewire/Settings/`. Only `admin` role can edit
(staff read-only — enforce via a Policy; finer RBAC stays deferred).

## 4. Domain layer

- Simple CRUD (specialties, procedures, doctors) lives in **thin Livewire components over Eloquent**
  (fat models, validation in the component/form objects).
- Multi-step operations get **Action** classes: `UpdateClinicProfile` (update singleton + sync central
  name + handle logo), `SaveDoctor` (upsert + sync specialty pivot).
- Authorization via `ClinicSettingsPolicy` (admin writes; staff read-only).

## 5. Logo handling
Stored on the tenant `public` disk (stancl suffixes storage per tenant → isolated). `clinic.logo_path`
holds the relative path; served through a small tenant-scoped route (we disabled `asset_helper_tenancy`,
so don't rely on `asset()` for tenant files). Logo is optional.

## 6. Build slices (TDD; see board for cards)

1. **Settings shell + nav** — `/configuracoes` tabbed shell, sidebar wiring, restyle Conta/Segurança onto the tenant layout.
2. **Specialties** CRUD (list/create/edit/toggle) — tenant-scoped, admin-only.
3. **Procedures** CRUD (price/duration/category).
4. **Doctors** CRUD + specialty pivot (`SaveDoctor`).
5. **Clinic profile** — `clinic` singleton, `UpdateClinicProfile` (+ central name sync), logo upload; extend `RegisterClinic` to seed the singleton.

## 7. Tests (Pest, tenant context)
- Each entity: create/update/toggle persists in the tenant DB; tenant isolation holds; admin-only writes (staff forbidden).
- Doctor ↔ specialty pivot syncs correctly.
- `UpdateClinicProfile` updates the singleton **and** the central `tenants.name`.
- `RegisterClinic` seeds the `clinic` singleton on provisioning.
- Settings tabs render in a tenant context; sidebar `Configurações` links to settings.

## 8. Out of scope (deferred)
Staff invites / user management / finer RBAC (later PRD), notification preferences (PRD-11 messaging),
appearance/theme settings beyond what the starter kit ships.
