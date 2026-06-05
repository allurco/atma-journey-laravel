# PRD-0 — Platform Foundation

**Phase:** MVP · **Depends on:** — · **Status:** spec
**Architecture spine:** `docs/superpowers/specs/2026-06-05-atma-journey-program-design.md`
**Board label:** `Fundação/Tenancy` · new cards land in `Backlog Geral`

---

## 1. Goal — the tracer bullet

Prove the whole architecture spine end-to-end with the thinnest possible vertical slice:

> A clinic **self-serve signs up** on the central domain → its **own MySQL tenant database** is
> provisioned and migrated, with a seeded **admin** user → it **logs in on its own subdomain**
> (`clinica.atma.test`, over HTTPS) → it sees an **authenticated app shell** with a placeholder
> dashboard. Covered by Pest tests, including **tenant isolation**.

No clinical/CRM features yet — this PRD exists to de-risk tenancy, provisioning, auth-in-tenant,
the Flux shell, and the TDD harness before any domain work begins.

## 2. Environment & dependencies

- **Add dependency:** `stancl/tenancy` (approved). No other dependency changes.
- **Runtime:** Laravel Livewire Starter Kit (already installed: Fortify, Livewire 4, free Flux, 2FA, passkeys).
- **Local serving:** **Laravel Herd** (Pro). Link this directory as the `atma` site → central domain
  `atma.test`; tenant subdomains `*.atma.test` route to the same site automatically.
- **Database:** **MySQL** (Herd's MySQL service, port 3306) for **local and the test suite** — no SQLite.
  Central DB `atma_central`; each tenant gets its own DB `tenant_<id>` created by stancl's
  `MySQLDatabaseManager`. Production swaps host/credentials via env.
- **`.env`:** none exists yet — create from `.env.example`. Key values:
  - `APP_NAME="ATMA Journey"`, `APP_URL=https://atma.test`
  - `DB_CONNECTION=mysql`, `DB_DATABASE=atma_central`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`
  - `SESSION_DRIVER=database`, `SESSION_DOMAIN=null` (**host-only cookies** → per-subdomain sessions)
  - `TENANCY_CENTRAL_DOMAINS=atma.test` (+ `localhost` for convenience)
- **`.env.testing`:** separate central test DB `atma_central_test`; tenant DBs created/dropped per test.

## 3. Architecture

### 3.1 Central vs. tenant databases

| Central DB (`atma_central`) | Tenant DB (`tenant_<id>`) |
|---|---|
| `tenants` (id, name, slug, plan, status, data json) | `users` (name, email, password, role) |
| `domains` (domain → tenant_id) | `password_reset_tokens`, `sessions`* |
| `sessions`*, `cache`, `jobs` | `two_factor_*` columns, `passkeys` |
| (Atma super-admins — later) | (everything clinical — later PRDs) |

> *`SESSION_DRIVER=database`, and stancl's database bootstrapper swaps the **default connection** to the
> tenant DB on tenant requests. So the `sessions` table must exist in **both** the central DB (used on the
> signup/marketing domain) and **each tenant DB** (used by logged-in clinic requests) — whichever
> connection is active is read. Combined with **host-only cookies** (§3.4) this gives strong per-tenant
> session isolation.

**Migrations:** the starter kit ships a combined `create_users_table` migration that also creates
`password_reset_tokens` **and** `sessions`. Split it: keep a central `sessions` migration (+ `cache`,
`jobs`, and the new `tenants`/`domains`) in `database/migrations/`; move `users`, `password_reset_tokens`,
a tenant `sessions`, the two-factor columns, and `passkeys` into `database/migrations/tenant/`.

### 3.2 Tenant identification

Domain-based via `stancl/tenancy`'s `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains`
middleware. Central domains (`atma.test`, `localhost`) serve marketing + signup; everything else is a
tenant host. `routes/tenant.php` holds tenant routes; `routes/web.php` becomes central-only.

### 3.3 Self-serve signup → provisioning (the core new build)

Central Livewire page (`Livewire/Onboarding/Register`, free-Flux form): **clinic name**, **slug**,
**admin name / email / password**. On submit, the `App\Actions\Tenancy\RegisterClinic` action:

1. Validates the slug (unique across `domains`, DNS-safe, reserved-word blocklist).
2. Creates the `Tenant` (central) with `name`, `slug`, `plan=trial`, `status=active`.
3. Maps the domain `"{slug}.{central_domain}"` to the tenant.
4. Runs tenant migrations **and** a tenant seeder that creates the first `User` (role `admin`).
5. **Local env only:** shells out to `herd secure {slug}.atma.test` so the subdomain has TLS immediately.
6. Redirects to the tenant subdomain's login (auto-login optional, deferred).

This action is the riskiest part of the system and is built **test-first**.

### 3.4 Auth (tenant context)

Reuse the starter kit's Fortify flows — **login, logout, password reset, email verification, 2FA,
passkeys** — operating against the **tenant** `users` table once `InitializeTenancyByDomain` has run.
**Tenant self-registration is disabled** (registration = central signup; staff invites are a later PRD).
Host-only session cookies (`SESSION_DOMAIN=null`) ensure a session minted on `clinica-a.atma.test` is
never sent to `clinica-b.atma.test`.

### 3.5 App shell

Adapt the starter kit's Flux app layout: ATMA branding, PT-BR nav, a single placeholder
`dashboard` Livewire page that renders the authenticated tenant context (e.g. "Bem-vindo, {clinic name}").
No real widgets — the dashboard is PRD-9.

## 4. Data model (this PRD)

- **Tenant** (central): `id` (string/uuid), `name`, `slug`, `plan`, `status`, `data` (json), timestamps.
- **Domain** (central): stancl default (`domain`, `tenant_id`).
- **User** (tenant): `name`, `email` (unique within tenant DB), `password`, `role` enum(`admin`,`staff`),
  email verification + two-factor + passkey columns from the starter kit. Factory + seeder.

## 5. Local dev: Herd + SSL + subdomains

1. `herd link atma` in the project root → `https://atma.test`.
2. `herd secure atma.test` (central domain TLS).
3. Tenant subdomains auto-route; each is secured on creation by the local-only `herd secure` hook (§3.3).
4. `APP_URL=https://atma.test`; verify a provisioned `clinica.atma.test` loads over HTTPS and logs in.

## 6. Build sequence (TDD, red → green → refactor)

1. Install `stancl/tenancy`; publish + configure (`config/tenancy.php`, central domains, MySQL tenant manager).
2. Create `.env` / `.env.testing`; `atma_central` + test DB; `php artisan key:generate`.
3. Move auth migrations to `database/migrations/tenant/`; central keeps `tenants`/`domains`/cache/jobs/sessions.
4. `Tenant` + `Domain` models (stancl), `Tenant` factory.
5. `RegisterClinic` action — **unit test first** (provisions tenant, DB, domain, admin user; slug rules).
6. Central signup Livewire page + route — **feature test** (happy path + slug collision).
7. Wire tenant routes/middleware; relocate auth to tenant context — **feature test** (login on subdomain).
8. App shell + placeholder dashboard — **feature test** (auth redirect; renders clinic name).
9. Tenant-isolation **feature test**. Run full suite green; `vendor/bin/pint`.

## 7. Test list (Pest)

- **Unit** `RegisterClinic`: creates tenant + domain + tenant DB + one admin; rejects duplicate/invalid slug.
- **Feature** signup: posting the form provisions a tenant and the admin can log in on the new subdomain.
- **Feature** isolation: a `User` created in tenant A does not exist in tenant B's DB.
- **Feature** auth: login on a tenant subdomain authenticates against that tenant's `users`; the central
  domain exposes no clinic login; tenant self-registration route is absent/disabled.
- **Feature** guard: unauthenticated request to the tenant `dashboard` redirects to the tenant login.

## 8. Out of scope (deferred)

Custom-domain mapping UI, staff invites & finer RBAC (PRD-1+), real dashboard widgets (PRD-9),
billing/plans/trials (PRD-13), Atma super-admin panel, queue/cache tenancy bootstrappers (add when needed),
production wildcard DNS/TLS (infra task).

## 9. Open items

- **Production central domain** not yet chosen (e.g. `atmajourney.com.br`) — kept as an env value; does
  not block local build.
