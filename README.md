# ATMA Journey

Multitenant SaaS for health clinics that digitizes the *Método Atma Soma* patient journey — unifying
retention/CRM, scheduling, clinical records (prontuário), and finance on one timeline. Laravel 13 +
Livewire 4, database-per-tenant via `stancl/tenancy`. UI is Brazilian Portuguese.

> Architecture & domain: `docs/superpowers/specs/2026-06-05-atma-journey-program-design.md`,
> `CONTEXT.md`, and the PRD collection in `docs/prds/`.

## Local development

Served by [Laravel Herd](https://herd.laravel.com) with MySQL.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# create the central databases (Herd MySQL, root / no password)
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS atma_central; CREATE DATABASE IF NOT EXISTS atma_central_test;"
php artisan migrate
herd link atma && herd secure atma.test   # → https://atma.test
npm run dev
```

## Quality gates

Three gates run on every PR via GitHub Actions (`.github/workflows/ci.yml`) and locally:

| Gate | Command | Tool |
| --- | --- | --- |
| Code style | `composer lint` (fix) · `composer lint:check` | Laravel Pint |
| Static analysis | `composer stan` | Larastan / PHPStan (level 5, `app`/`config`/`database`/`routes`) |
| Tests | `php artisan test` | Pest (MySQL) |

Run all three at once:

```bash
composer test
```

> Static analysis intentionally excludes `tests/` — Pest's fluent test API isn't statically
> analysable; tests are covered by Pest itself.
