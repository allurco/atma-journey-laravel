# Deploying ATMA Journey on Laravel Forge

Multitenant, **database-per-tenant** (`stancl/tenancy`), domain-based identification.
Central DB holds `tenants`/`domains`/`sessions`; each clinic gets its own database,
created and migrated **automatically** on signup.

## 1. `.env`

Copy [`.env.production.example`](../../.env.production.example) to `.env` on the server, then:

```bash
php artisan key:generate
```

Key values: `APP_URL`/`TENANCY_CENTRAL_DOMAINS` = your central domain;
`SESSION_CONNECTION=central` (don't change — see the session note below).

## 2. The MySQL user needs CREATE DATABASE (important)

stancl creates a **new database per tenant at runtime** (on signup). So the app's DB
user must be allowed to create/drop databases — Forge's default per-site user is not.
Create a dedicated user (or grant the forge user) global create rights:

```sql
CREATE USER 'atma'@'%' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON *.* TO 'atma'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

Point `DB_USERNAME`/`DB_PASSWORD` at that user. (To restrict scope instead of `*.*`,
use stancl's `PermissionControlledMySQLDatabaseManager`.)

## 3. Wildcard domain + TLS

Tenant subdomains (`{slug}.atmajourney.com.br`) all hit the same Forge site:

- **DNS:** an `A`/`AAAA` record for the apex **and** a wildcard `*.atmajourney.com.br` → server IP.
- **Forge site nginx:** `server_name atmajourney.com.br *.atmajourney.com.br;` (add the wildcard alias).
- **TLS:** a **wildcard certificate** for `*.atmajourney.com.br` (Let's Encrypt via DNS challenge, or a purchased cert). This is the production equivalent of local `herd secure` — one cert covers every clinic, no per-tenant cert work.

## 4. Deploy script (Forge → site → Deploy Script)

Forge atomic (zero-downtime) release format. The one multitenancy-specific line is
`tenants:migrate --force` — without it, **new tenant migrations never reach existing
clinics**. It runs after the central `migrate` and before `$ACTIVATE_RELEASE`, so a bad
tenant migration aborts the deploy instead of going live.

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force            # central DB
$FORGE_PHP artisan tenants:migrate --force    # every existing tenant DB

npm ci || npm install
npm run build

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

> **New tenants need no script** — `RegisterClinic` → `TenantCreated` runs `CreateDatabase` +
> `MigrateDatabase` automatically. `tenants:migrate` is only for applying **new** tenant
> migrations to **existing** tenants on each deploy.
>
> `artisan optimize` caches config/routes/views, so the server `.env` (incl.
> `SESSION_CONNECTION=central`) must be set before deploy. Forge re-runs the script on
> every deploy, so the cache refreshes whenever `.env` changes — no manual `config:clear`.

Useful stancl commands: `tenants:list`, `tenants:migrate`, `tenants:migrate-fresh`,
`tenants:seed`, `tenants:run "<cmd>"`, `tenants:rollback`.

## 5. Queue worker (recommended)

Tenant DB creation+migration is currently **synchronous** (`shouldBeQueued(false)` in
`TenancyServiceProvider`), so signup blocks while the DB is built — fine at low volume.
For scale, flip those to `true` and add a **Forge queue worker** (`php artisan queue:work`)
so signup returns instantly and provisioning runs in the background.

## 6. Notes

- **Sessions** use the database driver pinned to the fixed `central` connection
  (`SESSION_CONNECTION=central`). This is required: `StartSession` runs before tenancy
  initializes, so sessions must use a connection stancl never swaps, or they split across
  the central and tenant databases and break auth.
- **Tenant files** (logos, later patient docs) use tenant-scoped storage; for production
  scale, point `FILESYSTEM_DISK` at S3.
