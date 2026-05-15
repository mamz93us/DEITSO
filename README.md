# DEITAM — IT Solutions Platform

A multi-tenant **ITSM / MSP** platform for IT service companies in MENA. Web app
built on Laravel 11 + Filament 3, with per-organization branding, automatic SSL,
HR workflows, asset lifecycle, ticketing, field-service visits, and cPanel /
WhatsApp integrations.

> **Single source of truth:** [PROJECT.md](PROJECT.md) — mission, modules, data
> models, workflow specs, sprint plan, coding standards.
> **Working with Claude on this repo:** [CLAUDE.md](CLAUDE.md) — codified
> conventions (tenancy, actions, secrets, testing) that AI contributors must
> follow.

## Stack

- PHP 8.3, Laravel 11
- Filament 3 (admin panels at `/app` and `/system`; portal at `/portal`)
- MySQL 8 / MariaDB 10.6+ · Redis (cache, queue, sessions) · Meilisearch
- Caddy v2 (on-demand TLS per client hostname)
- Laravel Horizon · Laravel Reverb · Laravel Sanctum
- Pest / PHPUnit · Pint

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
# fill in DB_*, REDIS_*, GODADDY_*, INTERNAL_ROUTES_TOKEN, MAIL_*
php artisan migrate --seed
npm install && npm run build
php artisan serve            # http://localhost:8000/app
php artisan horizon          # queue worker + dashboard at /horizon
php artisan reverb:start     # websockets (optional in dev)
```

Three Filament entry points after seeding:

- `/system` — platform admins (`is_system_admin = true`)
- `/app`    — organization admins/staff (org-scoped)
- `/portal` — end users (per-org, read-only self-service)

## Quality gates

| Command | What it checks |
|---|---|
| `vendor/bin/pint --test` | PSR-12 + `declare(strict_types=1)`, import ordering, unused imports |
| `vendor/bin/pest` | Feature + unit suite (in-memory SQLite) |
| `composer audit --no-dev` | Known-vulnerable runtime dependencies |

CI (`.github/workflows/ci.yml`) runs all three on `push` and `pull_request` to
`main` / `develop`.

## Security

- Tenant isolation: every tenant-scoped model uses the `BelongsToOrganization`
  trait → `OrganizationScope` global scope. Org binding is established by
  `SetActiveOrganization` middleware and verified per-request (stale sessions
  are rejected).
- Auth: bcrypt cost 12. Login/password-reset are rate-limited per IP via the
  named limiter `filament-auth` (`app/Providers/AppServiceProvider.php`).
- Secrets: all third-party credentials are encrypted at rest via Laravel's
  `encrypted` cast (AES-256-CBC, keyed off `APP_KEY`). Never log decrypted
  values; the `activitylog` trait uses `logOnly([...])` whitelists.
- Caddy on-demand TLS callback is gated by token + IP allowlist + throttle (see
  [routes/internal.php](routes/internal.php)).

Report vulnerabilities privately — do not file public issues.

## Contributing

PRs against `develop` only. Match the action-based pattern under
`app/Actions/<Module>/<VerbNoun>.php`. CI must be green; new business logic
needs Pest coverage. Read [CLAUDE.md](CLAUDE.md) before touching tenancy code.

## License

Proprietary. © DEITAM.
